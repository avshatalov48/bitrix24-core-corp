<?php

namespace Bitrix\Voximplant\Rest;

use Bitrix\Crm\Integration\StorageType;
use Bitrix\Disk\File;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\IO;
use Bitrix\Main\Loader;
use Bitrix\Main\PhoneNumber\Format;
use Bitrix\Main\PhoneNumber\Parser;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\Uri;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\EventTable;
use Bitrix\Voximplant\Call;
use Bitrix\Voximplant\Model\CallTable;
use Bitrix\Voximplant\HttpClientFactory;
use Bitrix\Voximplant\Integration\Im;
use Bitrix\Voximplant\Model\ExternalLineTable;
use Bitrix\Voximplant\Model\StatisticMissedTable;
use Bitrix\Voximplant\PhoneTable;
use Bitrix\Voximplant\Result;
use Bitrix\Voximplant\Security;
use Bitrix\Voximplant\StatisticTable;

class Helper
{
	const EVENT_START_EXTERNAL_CALL = 'OnExternalCallStart';
	const EVENT_START_EXTERNAL_CALLBACK = 'OnExternalCallBackStart';
	const PLACEMENT_CALL_CARD = 'CALL_CARD';
	const FILE_FIELD = 'file';

	/**
	 * Returns user id of the user with given inner phone number, or false if user is not found.
	 * @param string $phoneNumber Inner phone number.
	 * @return int|false
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getUserByPhone($phoneNumber)
	{
		$row = PhoneTable::getList([
			'select' => ['USER_ID'],
			'filter' => [
				'=PHONE_NUMBER' => $phoneNumber,
				'=PHONE_MNEMONIC' => 'UF_PHONE_INNER',
				'=USER.ACTIVE' => 'Y',
				'=USER.IS_REAL_USER' => 'Y'
			]
		])->fetch();

		return is_array($row) ? (int)$row['USER_ID'] : false;
	}

	/**
	 * Register call, started to perform in external PBX. Auto creates
	 * @param array $fields
	 * <li> EXTERNAL_CALL_ID string | optional
	 * <li> USER_ID int
	 * <li> PHONE_NUMBER string
	 * <li> LINE_NUMBER string
	 * <li> TYPE int
	 * <li> CALL_START_DATE date
	 * <li> CRM bool
	 * <li> CRM_CREATE bool
	 * <li> CRM_SOURCE
	 * <li> CRM_ENTITY_TYPE
	 * <li> CRM_ENTITY_ID
	 * <li> CRM_ACTIVITY_ID
	 * <li> CRM_BINDINGS
	 * <li> REST_APP_ID
	 * <li> SHOW
	 * <li> CALL_LIST_ID
	 * @return Result
	 */
	public static function registerExternalCall(array $fields)
	{
		$fields['USER_ID'] = (int)$fields['USER_ID'];

		$result = new Result();
		$callId = 'externalCall.'.md5(uniqid($fields['REST_APP_ID'].$fields['USER_ID'].$fields['PHONE_NUMBER'], true)).'.'.time();

		$phoneNumber = \CVoxImplantPhone::stripLetters($fields['PHONE_NUMBER']);
		if (!$phoneNumber)
		{
			$result->addError(new Error('Unsupported phone number format'));
			return $result;
		}

		$userCheck = \Bitrix\Main\UserTable::getRow([
			'select' => ['ID'],
			'filter' => ['=ID' => $fields['USER_ID'], '=ACTIVE' => 'Y', '=IS_REAL_USER' => 'Y']
		]);
		if (!$userCheck)
		{
			$result->addError(new Error('User is not found or is not active'));
			return $result;
		}

		$lineId = null;
		if (isset($fields['LINE_NUMBER']))
		{
			$fields['LINE_NUMBER'] = trim($fields['LINE_NUMBER']);
			if ($fields['LINE_NUMBER'] != '')
			{
				$lineNumber = substr($fields['LINE_NUMBER'], 0, 50);// field length in db

				$retry = 0;
				do
				{
					$row = ExternalLineTable::getRow([
						'cache' => ['ttl' => 0],
						'select' => ['ID'],
						'filter' => [
							'=REST_APP_ID' => $fields['REST_APP_ID'],
							'=NUMBER' => $lineNumber,
						],
					]);
					if ($row)
					{
						$lineId = $row['ID'];
						break;
					}
					else
					{
						try
						{
							$insertResult = ExternalLineTable::add([
								'REST_APP_ID' => $fields['REST_APP_ID'],
								'NUMBER' => $lineNumber,
							]);
							if ($insertResult->isSuccess())
							{
								$lineId = $insertResult->getId();
								break;
							}
						}
						catch (SqlQueryException $exception) // todo: Replace with \Bitrix\Main\DB\DuplicateEntryException
						{
							// Ignore error (1062) Duplicate entry for key 'IX_VI_EXTERNAL_LINE_1'
							if (!str_contains($exception->getMessage(), 'Duplicate entry'))
							{
								throw $exception;
							}
							$retry ++;
							usleep(10);
						}
					}
				}
				while ($retry <= 2);
			}
		}

		$initEventData = [
			'CALL_ID' => $callId,
			'CALL_TYPE' => $fields['TYPE'],
			'CALLER_ID' => $phoneNumber,
			'REST_APP_ID' => $fields['REST_APP_ID']
		];
		$initEvent = new Event('voximplant', 'onCallInit', $initEventData);
		$initEvent->send();
		if ($initEvent->getResults() != null)
		{
			foreach ($initEvent->getResults() as $eventResult)
			{
				if ($eventResult->getType() === EventResult::SUCCESS)
				{
					$eventResultData = $eventResult->getParameters();
					if (isset($eventResultData['CALLER_ID']))
					{
						$phoneNumber = $eventResultData['CALLER_ID'];
					}
				}
			}
		}

		// checking for the internal call
		$portalCall = false;
		$portalUserId = null;
		$userData = PhoneTable::getList([
			'select' => ['USER_ID'],
			'filter' => [
				'=PHONE_NUMBER' => $phoneNumber,
				'=PHONE_MNEMONIC' => 'UF_PHONE_INNER',
				'=USER.ACTIVE' => 'Y'
			],
		])->fetch();
		if ($userData)
		{
			$portalCall = true;
			$portalUserId = $userData['USER_ID'];
		}

		$duplicateFilter = [
			'=USER_ID' => $fields['USER_ID'],
			'=CALLER_ID' => $phoneNumber,
			'=INCOMING' => $fields['TYPE'],
			'>DATE_CREATE' => (new DateTime())->add('-T30M'),
			'=REST_APP_ID' => $fields['REST_APP_ID'],
		];
		if ($lineId)
		{
			$duplicateFilter['=EXTERNAL_LINE_ID'] = $lineId;
		}
		if (isset($fields['EXTERNAL_CALL_ID']))
		{
			$duplicateFilter['=EXTERNAL_CALL_ID'] = $fields['EXTERNAL_CALL_ID'];
		}

		$duplicateCall = CallTable::getRow([
			'filter' => $duplicateFilter
		]);

		if ($duplicateCall)
		{
			$callId = $duplicateCall['CALL_ID'];
			$call = Call::load($callId);

			if ($call)
			{
				if ($fields['SHOW'] ?? null)
				{
					self::showExternalCall([
						'CALL_ID' => $callId
					]);
				}

				$createdEntities = array_map(
					function($e)
					{
						return [
							'ENTITY_TYPE' => $e['ENTITY_TYPE'],
							'ENTITY_ID' => $e['ENTITY_ID'],
						];
					},
					$call->getCreatedCrmEntities()
				);

				return $result->setData([
					'CALL_ID' => $call->getCallId(),
					'CRM_CREATED_LEAD' => (int)$call->getCreatedCrmLead() ?: null,
					'CRM_CREATED_ENTITIES' => $createdEntities,
					'CRM_ENTITY_TYPE' => $call->getPrimaryEntityType(),
					'CRM_ENTITY_ID' => $call->getPrimaryEntityId() ?: null,
				]);

			}
		}

		$crmCreate = (($fields['CRM'] ?? null) || $fields['CRM_CREATE']) && !$portalCall;
		$callFields = [
			'USER_ID' => $fields['USER_ID'],
			'CALL_ID' => $callId,
			'INCOMING' => $fields['TYPE'],
			'DATE_CREATE' => (($fields['CALL_START_DATE'] ?? null) ?: new DateTime()),
			'CALLER_ID' => $phoneNumber,
			'PORTAL_USER_ID' => $portalUserId,
			'CRM' => $portalCall ? 'N' : 'Y',
			'REST_APP_ID' => $fields['REST_APP_ID'],
			'EXTERNAL_LINE_ID' => $lineId ?? null,
			'PORTAL_NUMBER' => $lineNumber ?: \CVoxImplantConfig::MODE_REST_APP . ":" . $fields['REST_APP_ID'],
			'LAST_PING' => null,
			'QUEUE_ID' => null,
		];

		if (isset($fields['EXTERNAL_CALL_ID']))
		{
			$callFields['EXTERNAL_CALL_ID'] = $fields['EXTERNAL_CALL_ID'];
		}
		if ($fields['CALL_LIST_ID'] > 0)
		{
			$callFields['CRM_CALL_LIST'] = (int)$fields['CALL_LIST_ID'];
		}
		if ($fields['CRM_ACTIVITY_ID'] > 0)
		{
			$callFields['CRM_ACTIVITY_ID'] = (int)$fields['CRM_ACTIVITY_ID'];
		}

		$call = Call::create($callFields);
		if (isset($fields['CRM_ENTITY_TYPE'], $fields['CRM_ENTITY_ID']))
		{
			$call->addCrmEntities([
				[
					'ENTITY_TYPE' => $fields['CRM_ENTITY_TYPE'],
					'ENTITY_ID' => $fields['CRM_ENTITY_ID'],
					'IS_CREATED' => 'N',
					'IS_PRIMARY' => 'Y'
				]
			]);

			if (is_array($fields['CRM_BINDINGS']))
			{
				$activityBindings = \CVoxImplantCrmHelper::createActivityBindings([
					'CRM_ENTITY_TYPE' => $fields['CRM_ENTITY_TYPE'],
					'CRM_ENTITY_ID' => $fields['CRM_ENTITY_ID'],
					'CRM_BINDINGS' => $fields['CRM_BINDINGS']
				]);
			}
			else
			{
				$activityBindings = \CVoxImplantCrmHelper::getActivityBindings($call);
			}

			if (is_array($activityBindings) && !empty($activityBindings))
			{
				$call->updateCrmBindings($activityBindings);
			}
		}
		else
		{
			$crmData = \CVoxImplantCrmHelper::getCrmEntities($call);
			$call->updateCrmEntities($crmData);
			$activityBindings = \CVoxImplantCrmHelper::getActivityBindings($call);
			if (is_array($activityBindings) && !empty($activityBindings))
			{
				$call->updateCrmBindings($activityBindings);
			}
		}

		if ($crmCreate)
		{
			$createResult = \CVoxImplantCrmHelper::registerCallInCrm(
				$call,
				[
					'CRM' => 'Y',
					'CRM_CREATE' => \CVoxImplantConfig::CRM_CREATE_LEAD,
					'CRM_CREATE_CALL_TYPE' => \CVoxImplantConfig::CRM_CREATE_CALL_TYPE_ALL,
					'CRM_SOURCE' => $fields['CRM_SOURCE']
				]
			);

			if (!$createResult)
			{
				$leadCreationError = \CVoxImplantCrmHelper::$lastError;
			}
		}

		if (\CVoxImplantConfig::GetLeadWorkflowExecution() == \CVoxImplantConfig::WORKFLOW_START_IMMEDIATE)
		{
			\CVoxImplantCrmHelper::StartCallTrigger($call);
		}

		\CVoxImplantMain::sendCallStartEvent([
			'CALL_ID' => $callId,
			'USER_ID' => $fields['USER_ID']
		]);

		if ($fields['SHOW'])
		{
			self::showExternalCall([
				'CALL_ID' => $callId
			]);
		}

		$createdEntities = array_map(
			function($e)
			{
				return [
					'ENTITY_TYPE' => $e['ENTITY_TYPE'],
					'ENTITY_ID' => $e['ENTITY_ID'],
				];
			},
			$call->getCreatedCrmEntities()
		);

		$resultData = [
			'CALL_ID' => $call->getCallId(),
			'CRM_CREATED_LEAD' => (int)$call->getCreatedCrmLead() ?: null,
			'CRM_CREATED_ENTITIES' => $createdEntities,
			'CRM_ENTITY_TYPE' => (string)$call->getPrimaryEntityType(),
			'CRM_ENTITY_ID' => (int)$call->getPrimaryEntityId() ?: null,
		];

		if (isset($leadCreationError))
		{
			$resultData['LEAD_CREATION_ERROR'] = $leadCreationError;
		}

		$result->setData($resultData);
		return $result;
	}

	/**
	 * Finishes call, initiated externally and updates crm lead and activity
	 * @param array $fields
	 * <li> CALL_ID
	 * <li> USER_ID
	 * <li> DURATION - call duration in seconds
	 * <li> COST - call's cost
	 * <li> COST_CURRENCY
	 * <li> STATUS_CODE
	 * <li> FAILED_REASON
	 * <li> RECORD_URL
	 * <li> VOTE
	 * <li> ADD_TO_CHAT
	 * @return Result
	 */
	public static function finishExternalCall(array $fields)
	{
		$result = new Result();

		if (!is_string($fields['CALL_ID']))
		{
			$result->addError(new Error('CALL_ID must be a string'));

			return $result;
		}

		$call = Call::load($fields['CALL_ID']);

		/*$call = CallTable::getRow(array(
			'select' => array(
				'*',
				'EXTERNAL_LINE_NUMBER' => 'EXTERNAL_LINE.NUMBER',
				'EXTERNAL_LINE_NAME' => 'EXTERNAL_LINE.NAME'
			),
			'filter' => array(
				'=CALL_ID' => $fields['CALL_ID']
			)
		));*/

		if (!$call)
		{
			$result->addError(new Error('Call is not found (call should be registered prior to finishing'));
			return $result;
		}

		if (isset($fields['USER_ID']))
		{
			$userCheck = \Bitrix\Main\UserTable::getRow([
				'select' => ['ID'],
				'filter' => ['=ID' => $fields['USER_ID'], '=ACTIVE' => 'Y', '=IS_REAL_USER' => 'Y']
			]);
			if (!$userCheck)
			{
				$result->addError(new Error('User is not found or is not active'));
				return $result;
			}
		}

		self::hideExternalCall([
			'CALL_ID' => $call->getCallId(),
			'USER_ID' => isset($fields['USER_ID']) ? (int)$fields['USER_ID'] : $call->getUserId()
		]);

		$fields['DURATION'] = (int)$fields['DURATION'];
		$fields['STATUS_CODE'] = $fields['STATUS_CODE'] ?: ($fields['DURATION'] > 0 ? '200' : '304');
		$fields['ADD_TO_CHAT'] = isset($fields['ADD_TO_CHAT']) ? (bool)$fields['ADD_TO_CHAT'] : true;

		$statisticRecord = [
			'CALL_ID' => $call->getCallId(),
			'EXTERNAL_CALL_ID' => $call->getExternalCallId(),
			'PORTAL_USER_ID' => isset($fields['USER_ID']) ? (int)$fields['USER_ID'] : $call->getUserId(),
			'PHONE_NUMBER' => $call->getCallerId(),
			'PORTAL_NUMBER' => $call->getPortalNumber(),
			'INCOMING' => $call->getIncoming(),
			'CALL_DURATION' => $fields['DURATION'] ?: 0,
			'CALL_START_DATE' => $call->getDateCreate(),
			'CALL_STATUS' => $fields['DURATION'] > 0 ? 1 : 0,
			'CALL_VOTE' => $fields['VOTE'],
			'COST' => $fields['COST'],
			'COST_CURRENCY' => $fields['COST_CURRENCY'],
			'CALL_FAILED_CODE' => $fields['STATUS_CODE'],
			'CALL_FAILED_REASON' => $fields['FAILED_REASON'],
			'REST_APP_ID' => $call->getRestAppId(),
			'REST_APP_NAME' => self::getRestAppName($call->getRestAppId()),
			'CRM_ACTIVITY_ID' => (int)$call->getCrmActivityId() ?: null,
			'COMMENT' => $call->getComment(),
		];

		\CVoxImplantCrmHelper::updateCrmEntities(
			$call->getCreatedCrmEntities(),
			[
				'ASSIGNED_BY_ID' => $statisticRecord['PORTAL_USER_ID']
			],
			$statisticRecord['PORTAL_USER_ID']
		);

		if ($call->getPrimaryEntityType() != '' && $call->getPrimaryEntityId() > 0)
		{
			$statisticRecord['CRM_ENTITY_TYPE'] = $call->getPrimaryEntityType();
			$statisticRecord['CRM_ENTITY_ID'] = $call->getPrimaryEntityId();

			$viMain = new \CVoxImplantMain($statisticRecord["PORTAL_USER_ID"]);
			$dialogData = $viMain->GetDialogInfo($statisticRecord['PHONE_NUMBER'], '', false);
			if (!$dialogData['UNIFIED'])
			{
				\CVoxImplantMain::UpdateChatInfo(
					$dialogData['DIALOG_ID'],
					[
						'CRM' => $call->isCrmEnabled() ? 'Y' : 'N',
						'CRM_ENTITY_TYPE' => $call->getPrimaryEntityType(),
						'CRM_ENTITY_ID' => $call->getPrimaryEntityId()
					]
				);
			}
		}

		if (
			$call->getCrmActivityId()
			&& \CVoxImplantCrmHelper::shouldAttachCallToActivity($statisticRecord, $call->getCrmActivityId())
		)
		{
			\CVoxImplantCrmHelper::attachCallToActivity($statisticRecord, $call->getCrmActivityId());
			$statisticRecord['CRM_ACTIVITY_ID'] = $call->getCrmActivityId();
		}
		else
		{
			$statisticRecord['CRM_ACTIVITY_ID'] = \CVoxImplantCrmHelper::AddCall($statisticRecord, array(
				'CRM_BINDINGS' => $call->getCrmBindings()
			));
			if (!$statisticRecord['CRM_ACTIVITY_ID'])
			{
				$activityCreationError = \CVoxImplantCrmHelper::$lastError;
			}

			if ($call->getCrmActivityId() && \CVoxImplantCrmHelper::shouldCompleteActivity($statisticRecord))
			{
				\CVoxImplantCrmHelper::completeActivity($call->getCrmActivityId());
			}
		}

		if (\CVoxImplantConfig::GetLeadWorkflowExecution() == \CVoxImplantConfig::WORKFLOW_START_DEFERRED)
		{
			\CVoxImplantCrmHelper::StartCallTrigger($call);
		}

		if ($statisticRecord["CALL_FAILED_CODE"] == 304 && self::isIncomingCall($call))
		{
			\CVoxImplantCrmHelper::StartMissedCallTrigger($call);
		}

		$insertResult = StatisticTable::add($statisticRecord);
		if (!$insertResult->isSuccess())
		{
			$result->addError(new Error('Unexpected database error'));
			$result->addErrors($insertResult->getErrors());
			return $result;
		}
		$statisticRecord['ID'] = $insertResult->getId();

		//recording a missed call
		if (
			$statisticRecord["CALL_FAILED_CODE"] == 304
			&& self::isIncomingCall($call)
		)
		{
			$missedCall = [
				'ID' => $statisticRecord['ID'],
				'CALL_START_DATE' => $statisticRecord['CALL_START_DATE'],
				'PHONE_NUMBER' => $statisticRecord['PHONE_NUMBER'],
				'PORTAL_USER_ID' => $statisticRecord['PORTAL_USER_ID']
			];

			$insertMissedCallResult = StatisticMissedTable::add($missedCall);
			if (!$insertMissedCallResult->isSuccess())
			{
				$result->addError(new Error('Unexpected database error'));
				$result->addErrors($insertMissedCallResult->getErrors());
				return $result;
			}
		} //if our call answering any missed calls
		elseif (
			$statisticRecord["CALL_FAILED_CODE"] == 200
			&& $call->getIncoming() == \CVoxImplantMain::CALL_OUTGOING
		)
		{
			$missedCalls = StatisticMissedTable::getList([
				'select' => ['ID'],
				'filter' => [
					'=PHONE_NUMBER' => $statisticRecord['PHONE_NUMBER'],
					'=CALLBACK_ID' => null
				],
			])->fetchAll();

			if ($missedCalls)
			{
				foreach ($missedCalls as $missedCall)
				{
					StatisticMissedTable::update($missedCall['ID'], [
							'CALLBACK_ID' => $statisticRecord['ID'],
							'CALLBACK_CALL_START_DATE' => $statisticRecord['CALL_START_DATE']
						]
					);
				}
			}
		}

		$hasRecord = ($fields['RECORD_URL'] != '');
		if ($hasRecord)
		{
			if (!mb_check_encoding($fields['RECORD_URL'], 'UTF-8'))
			{
				$result->addError(new Error('RECORD_URL contains invalid symbols for UTF-8 encoding'));
				return $result;
			}
			$recordUrl = Uri::urnEncode($fields['RECORD_URL']);
			\CVoxImplantHistory::DownloadAgent($insertResult->getId(), $recordUrl, $call->isCrmEnabled());
		}

		if ($fields['ADD_TO_CHAT'])
		{
			$chatMessage = \CVoxImplantHistory::GetMessageForChat($statisticRecord, $hasRecord);
			if ($chatMessage != '')
			{
				$attach = null;

				if (\CVoxImplantConfig::GetChatAction() == \CVoxImplantConfig::INTERFACE_CHAT_APPEND)
				{
					$attach = \CVoxImplantHistory::GetAttachForChat($statisticRecord, $hasRecord);
				}

				if ($attach)
				{
					\CVoxImplantHistory::SendMessageToChat($statisticRecord["PORTAL_USER_ID"], $statisticRecord["PHONE_NUMBER"], $statisticRecord["INCOMING"], null, $attach);
				}
				else
				{
					\CVoxImplantHistory::SendMessageToChat($statisticRecord["PORTAL_USER_ID"], $statisticRecord["PHONE_NUMBER"], $statisticRecord["INCOMING"], $chatMessage);
				}
			}
		}

		if (\CVoxImplantConfig::GetLeadWorkflowExecution() == \CVoxImplantConfig::WORKFLOW_START_DEFERRED)
		{
			$createdCrmEntities = $call->getCreatedCrmEntities();

			foreach ($createdCrmEntities as $entity)
			{
				if ($entity['ENTITY_TYPE'] === 'LEAD')
				{
					\CVoxImplantCrmHelper::StartLeadWorkflow($entity['ENTITY_ID']);
				}
			}
		}

		Call::delete($fields['CALL_ID']);

		\CVoxImplantHistory::sendCallEndEvent($statisticRecord);
		$resultData = $statisticRecord;
		if (isset($activityCreationError))
		{
			$resultData['ERRORS']['ACTIVITY_CREATION'] = $activityCreationError;
		}

		$result->setData($resultData);
		return $result;
	}

	/**
	 * Shows card with CRM info on a call to the user.
	 * @param array $params Function parameters:
	 * <li> CALL_ID
	 * <li> USER_ID
	 * @return bool
	 */
	public static function showExternalCall(array $params)
	{
		$callId = $params['CALL_ID'];
		$call = Call::load($callId);
		if (!$call)
		{
			return false;
		}

		if ($call->getExternalLineId())
		{
			$externalLine = ExternalLineTable::getRowById($call->getExternalLineId());
		}


		if (isset($params['USER_ID']))
		{
			if (is_array($params['USER_ID']))
			{
				$userId = $params['USER_ID'];
			}
			else {
				$userId = [(int)$params['USER_ID']];
			}
		}
		else
		{
			$userId = [$call->getUserId()];
		}

		\CVoxImplantMain::SendPullEvent([
			'COMMAND' => 'showExternalCall',
			'CALL_ID' => $callId,
			'USER_ID' => $userId,
			'PHONE_NUMBER' => (string)$call->getCallerId(),
			'LINE_NUMBER' => $externalLine ? $externalLine['NUMBER'] : null,
			'COMPANY_PHONE_NUMBER' => $externalLine ? ($externalLine['NAME'] ?: $externalLine['NUMBER']) : null,
			'INCOMING' => $call->getIncoming(),
			'SHOW_CRM_CARD' => $call->isCrmEnabled(),
			'CRM_ENTITY_TYPE' => $call->getPrimaryEntityType(),
			'CRM_ENTITY_ID' => $call->getPrimaryEntityId(),
			'CRM_BINDINGS' => \CVoxImplantCrmHelper::resolveBindingNames($call->getCrmBindings()),
			'CRM' => \CVoxImplantCrmHelper::GetDataForPopup($call->getCallId(), $call->getCallerId(), $userId),
			'CONFIG' => [
				'CRM_CREATE' => 'none'
			],
			'PORTAL_CALL' => $call->isInternalCall() ? 'Y' : 'N',
			'PORTAL_CALL_USER_ID' => $call->getPortalUserId(),
			'PORTAL_CALL_DATA' => $call->isInternalCall() ? Im::getUserData(['ID' => [$call->getUserId(), $call->getPortalUserId()], 'DEPARTMENT' => 'N', 'HR_PHOTO' => 'Y']) : []
		]);
		return true;
	}

	/**
	 * Hides card with CRM info on a call.
	 * @param array $params Function parameters:
	 * <li> CALL_ID
	 * <li> USER_ID
	 * @return bool
	 */
	public static function hideExternalCall(array $params)
	{
		$callId = $params['CALL_ID'];
		$call = CallTable::getByCallId($callId);
		if (!$call)
		{
			return false;
		}

		if (isset($params['USER_ID']))
		{
			if(is_array($params['USER_ID']))
			{
				$userId = $params['USER_ID'];
			}
			else
			{
				$userId = [(int)$params['USER_ID']];
			}
		}
		else
		{
			$userId = [$call['USER_ID']];
		}

		\CVoxImplantMain::SendPullEvent([
			'COMMAND' => 'hideExternalCall',
			'USER_ID' => $userId,
			'CALL_ID' => $callId
		]);

		return true;
	}

	/**
	 * Returns rest application name by its client id.
	 * @param string $clientId Application's client id.
	 * @return string|false
	 */
	public static function getRestAppName($clientId)
	{
		if (!Loader::includeModule('rest'))
		{
			return false;
		}

		$row = AppTable::getByClientId($clientId);

		if (!is_array($row))
		{
			return false;
		}

		if ($row['MENU_NAME'] != '')
		{
			$result = $row['MENU_NAME'];
		}
		else if ($row['MENU_NAME_DEFAULT'] != '')
		{
			$result = $row['MENU_NAME_DEFAULT'];
		}
		else if ($row['MENU_NAME_LICENSE'] != '')
		{
			$result = $row['MENU_NAME_LICENSE'];
		}
		else
		{
			$result = $row['APP_NAME'];
		}

		return $result;
	}

	/**
	 * Returns array of applications, capable of creating externally initiated calls
	 */
	public static function getExternalCallHandlers()
	{
		return static::getEventSubscribers(self::EVENT_START_EXTERNAL_CALL);
	}

	/**
	 * Returns array of applications, capable of starting callback
	 */
	public static function getExternalCallbackHandlers()
	{
		return static::getEventSubscribers(self::EVENT_START_EXTERNAL_CALLBACK);
	}

	protected static function getEventSubscribers($eventName)
	{
		$result = [];
		if (!Loader::includeModule('rest'))
		{
			return $result;
		}

		$cursor = EventTable::getList([
			'select' => [
				'APP_ID' => 'APP_ID',
				'TITLE' => 'TITLE',
				'APP_NAME' => 'REST_APP.APP_NAME',
				'MENU_NAME' => 'REST_APP.LANG.MENU_NAME',
				'DEFAULT_MENU_NAME' => 'REST_APP.LANG_DEFAULT.MENU_NAME'
			],
			'filter' => [
				'=EVENT_NAME' => $eventName
			]
		]);

		while ($row = $cursor->fetch())
		{
			$appId = $row['APP_ID'];
			if ($appId == 0)
			{
				$appName = $row['TITLE'];
			}
			else if ($row['MENU_NAME'] != '')
			{
				$appName = $row['MENU_NAME'];
			}
			else if ($row['DEFAULT_MENU_NAME'] != '')
			{
				$appName = $row['DEFAULT_MENU_NAME'];
			}
			else
			{
				$appName = $row['APP_NAME'];
			}

			$result[$appId] = $appName;
		}

		return $result;
	}

	/**
	 * Returns id of the rest application, set as external call handler, or false if the external call handler is not set.
	 * @param int $userId Id of the user.
	 * @return string|false
	 */
	public static function getExternalCallHandler($userId)
	{
		$defaultLineId = \CVoxImplantUser::getUserOutgoingLine($userId);
		$line = \CVoxImplantConfig::GetLine($defaultLineId);

		return ($line && $line['TYPE'] === 'REST') ? $line : false;
	}

	/**
	 * Sends event to start call to the configured rest application
	 * @param string $number Phone number to call.
	 * @param int $userId User id of the user, initiated the call.
	 * @param array $parameters Additional parameters.
	 * @return Result
	 */
	public static function startCall($number, $userId, $lineId = '', array $parameters = array())
	{
		$entityType = $parameters['ENTITY_TYPE'] ?? '';
		$entityId = $parameters['ENTITY_ID'];
		if (mb_strpos($entityType, 'CRM_') === 0)
		{
			$entityType = mb_substr($entityType, 4);
		}
		else if (isset($parameters['ENTITY_TYPE_NAME']) && isset($parameters['ENTITY_ID']))
		{
			$entityType = $parameters['ENTITY_TYPE_NAME'];
			$entityId = $parameters['ENTITY_ID'];
		}
		else
		{
			$entityType = '';
			$entityId = null;
		}

		if ($lineId)
		{
			$line = \CVoxImplantConfig::GetLine($lineId);
		}
		else
		{
			$line = self::getExternalCallHandler($userId);
		}
		if (!$line)
		{
			$result = new Result();
			return $result->addError(new Error("Outgoing line is not found", "LINE_NOT_FOUND"));
		}
		$lineNumber = mb_substr($line['LINE_NUMBER'], 0, 8) === 'REST_APP' ? '' : $line['LINE_NUMBER'];

		[$extensionSeparator, $extension] = Parser::getInstance()->stripExtension($number);
		$eventFields = [
			'PHONE_NUMBER' => $number,
			'PHONE_NUMBER_INTERNATIONAL' => Parser::getInstance()->parse($number)->format(Format::E164),
			'EXTENSION' => $extension,
			'USER_ID' => $userId,
			'CALL_LIST_ID' => (int)($parameters['CALL_LIST_ID'] ?? null),
			'APP_ID' => $line['REST_APP_ID'],
			'LINE_NUMBER' => $lineNumber,
			'IS_MOBILE' => $parameters['IS_MOBILE'] === true,
		];

		$registerResult = static::registerExternalCall([
			'USER_ID' => $userId,
			'PHONE_NUMBER' => $number,
			'LINE_NUMBER' => $lineNumber,
			'TYPE' => \CVoxImplantMain::CALL_OUTGOING,
			'CRM_CREATE' => ($line['CRM_AUTO_CREATE'] ?? 'Y') === 'Y',
			'CRM_ENTITY_TYPE' => $entityType,
			'CRM_ENTITY_ID' => $entityId,
			'CRM_ACTIVITY_ID' => (int)($parameters['SRC_ACTIVITY_ID'] ?? null),
			'CRM_BINDINGS' => is_array(($parameters['BINDINGS'] ?? null)) ? $parameters['BINDINGS'] : null,
			'REST_APP_ID' => $line['REST_APP_ID'],
			'CALL_LIST_ID' => (int)($parameters['CALL_LIST_ID'] ?? null),
		]);
		if($registerResult->isSuccess())
		{
			$callData = $registerResult->getData();
			$eventFields['CALL_ID'] = $callData['CALL_ID'];
			$eventFields['CRM_ENTITY_TYPE'] = $callData['CRM_ENTITY_TYPE'];
			$eventFields['CRM_ENTITY_ID'] = $callData['CRM_ENTITY_ID'];
			$eventFields['CRM_CREATED_LEAD'] = $callData['CRM_CREATED_LEAD'];
			$eventFields['CRM_CREATED_ENTITIES'] = $callData['CRM_CREATED_ENTITIES'];

			$event = new Event(
				'voximplant',
				'onExternalCallStart',
				$eventFields
			);
			$event->send();
		}

		return $registerResult;
	}

	/**
	 * Send event to start callback to the rest application.
	 * @param array $parameters Array of parameters.
	 * @return void
	 */
	public static function startCallBack(array $parameters)
	{
		$eventFields = [
			'PHONE_NUMBER' => $parameters['PHONE_NUMBER'],
			'TEXT' => $parameters['TEXT'],
			'VOICE' => $parameters['VOICE'],
			'CRM_ENTITY_TYPE' => $parameters['CRM_ENTITY_TYPE'],
			'CRM_ENTITY_ID' => $parameters['CRM_ENTITY_ID'],
			'APP_ID' => $parameters['APP_ID'],
			'LINE_NUMBER' => $parameters['LINE_NUMBER']
		];

		$event = new Event(
			'voximplant',
			'onExternalCallBackStart',
			$eventFields
		);
		$event->send();
	}

	/**
	 * @param string $callId Id of the call.
	 * @param string $fileName Name of file containing record.
	 * @param string $fileContent Base64-encoded string with file contents.
	 * @param \CRestServer $restServer Rest server.
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function attachRecord($callId, $fileName, $fileContent, $restServer)
	{
		$result = new Result();

		$statisticRecord = StatisticTable::getByCallId($callId);
		if (!$statisticRecord)
		{
			$result->addError(new Error("Call is not found in the statistic table. Looks like it is not finished yet."));
			return $result;
		}

		if ($fileContent === null)
		{
			$result->setData([
				'uploadUrl' => \CRestUtil::getUploadUrl(['callId' => $callId], $restServer),
				'fieldName' => static::FILE_FIELD
			]);
			return $result;
		}

		if ($fileName == '')
		{
			$result->addError(new Error("File name is empty"));
			return $result;
		}

		$allowedExtensions = ['wav', 'mp3'];
		if (!in_array(GetFileExtension($fileName), $allowedExtensions))
		{
			$result->addError(new Error("Wrong file extension. Only wav and mp3 are allowed"));
			return $result;
		}

		$fileArray = \CRestUtil::saveFile($fileContent, $fileName);

		if ($fileArray === false)
		{
			$result->addError(new Error("File content is empty."));
			return $result;
		}

		if (is_null($fileArray))
		{
			$result->addError(new Error("File content is not properly encoded. Base64 encoding is expected."));
			return $result;
		}

		if (!is_array($fileArray))
		{
			$result->addError(new Error("Unknown error encountered while saving file."));
			return $result;
		}

		$isCorrectFileResult = Security\RecordFile::isCorrectFromArray($fileArray);
		if (!$isCorrectFileResult->isSuccess())
		{
			return $result->addErrors($isCorrectFileResult->getErrors());
		}

		$saveResult =
			static::saveFile(
				$statisticRecord['CALL_START_DATE']->format("Y-m"),
				$fileName,
				$fileArray,
				$statisticRecord['PORTAL_USER_ID']
			)
		;
		if (!$saveResult->isSuccess())
		{
			$result->addErrors($saveResult->getErrors());
			return $result;
		}
		$saveResultData = $saveResult->getData();
		$file = $saveResultData['FILE'];

		if ($statisticRecord['CALL_WEBDAV_ID'])
		{
			Application::getInstance()->addBackgroundJob(
				[__CLASS__, 'deleteFile'],
				[$statisticRecord['CALL_WEBDAV_ID'], $statisticRecord['PORTAL_USER_ID']]
			);
		}

		$attachResult = static::attachFile($callId, $file);
		if (!$attachResult->isSuccess())
		{
			$result->addErrors($attachResult->getErrors());
			return $result;
		}

		$result->setData([
			'FILE_ID' => $file->getId()
		]);

		return $result;
	}

	/**
	 * Downloads and attaches record to the existing call.
	 * @param string $callId Id of the call.
	 * @param string $recordUrl Url of the record.
	 * @param string $fileName [Optional] Name of the file. If omitted, file name will taken from the url.
	 * @return Result
	 */
	public static function attachRecordWithUrl($callId, $recordUrl, $fileName = '')
	{
		$result = new Result();

		$statisticRecord = StatisticTable::getByCallId($callId);
		if (!$statisticRecord)
		{
			$result->addError(new Error("Call is not found in the statistic table. Looks like it is not finished yet."));
			return $result;
		}

		$urlPath = parse_url($recordUrl, PHP_URL_PATH);
		if ($fileName !== '')
		{
			$tempPath = \CFile::GetTempName('', bx_basename($fileName));
		}
		else if ($urlPath && $urlPath !== '')
		{
			$tempPath = \CFile::GetTempName('', bx_basename($urlPath));
		}
		else
		{
			$tempPath = \CFile::GetTempName('', bx_basename($recordUrl));
		}

		try
		{
			IO\Directory::createDirectory(IO\Path::getDirectory($tempPath));
			if (IO\Directory::isDirectoryExists(IO\Path::getDirectory($tempPath)) === false)
			{
				return $result->addError(new Error('Could not create temporary directory', 'INTERNAL_ERROR'));
			}

			$file = new IO\File($tempPath);
			$handler = $file->open("w+");
		}
		catch(\Exception $exception)
		{
			$result->addError(new Error($exception->getMessage(), 'INTERNAL_ERROR'));

			return $result;
		}

		$httpClient = HttpClientFactory::create([
			"disableSslVerification" => true
		]);

		$httpClient->setOutputStream($handler);
		$queryResult = $httpClient->query('GET', $recordUrl);
		$httpClient->getResult();
		$file->close();

		if ($queryResult === false)
		{
			$httpClientErrors = $httpClient->getError();
			if (!empty($httpClientErrors))
			{
				foreach ($httpClientErrors as $code => $message)
				{
					return $result->addError(new Error($code . ": " . $message, 'SERVER_NOT_AVAILABLE'));
				}
			}
		}

		if ($httpClient->getStatus() !== 200)
		{
			return $result->addError(new Error('Server returns HTTP error code ' . $httpClient->getStatus()));
		}

		//check for http errors once more
		$httpClientErrors = $httpClient->getError();
		if (!empty($httpClientErrors))
		{
			foreach ($httpClientErrors as $code => $message)
			{
				return $result->addError(new Error($code . ": " . $message, 'SERVER_NOT_AVAILABLE'));
			}
		}

		$fileArray = \CFile::MakeFileArray($tempPath);

		$isCorrectFileResult = Security\RecordFile::isCorrectFromArray($fileArray);
		if (!$isCorrectFileResult->isSuccess())
		{
			return $result->addErrors($isCorrectFileResult->getErrors());
		}

		$saveResult = static::saveFile($statisticRecord['CALL_START_DATE']->format("Y-m"), $fileName, $fileArray, $statisticRecord['PORTAL_USER_ID']);
		if (!$saveResult->isSuccess())
		{
			$result->addErrors($saveResult->getErrors());
			return $result;
		}
		$saveResultData = $saveResult->getData();
		$file = $saveResultData['FILE'];

		if ($statisticRecord['CALL_WEBDAV_ID'])
		{
			Application::getInstance()->addBackgroundJob(
				[__CLASS__, 'deleteFile'],
				[$statisticRecord['CALL_WEBDAV_ID'], $statisticRecord['PORTAL_USER_ID']]
			);
		}

		$attachResult = static::attachFile($callId, $file, $recordUrl);
		if (!$attachResult->isSuccess())
		{
			$result->addErrors($attachResult->getErrors());
			return $result;
		}

		$result->setData([
			'FILE_ID' => $file->getId()
		]);

		return $result;
	}

	/**
	 * @param string $callId
	 * @param string $fileName
	 * @return Result
	 * @throws SystemException
	 */
	public static function uploadRecord($callId)
	{
		$result = new Result();
		if (!is_array($_FILES[self::FILE_FIELD]))
		{
			$result->addError(new Error("Error: required parameter " . self::FILE_FIELD . " is not found"));
			return $result;
		}

		$fileArray = $_FILES[self::FILE_FIELD];
		$fileName = $fileArray['name'];

		$allowedExtensions = array('wav', 'mp3');
		if (!in_array(GetFileExtension($fileName), $allowedExtensions))
		{
			$result->addError(new Error("Wrong file extension. Only wav and mp3 are allowed"));
			return $result;
		}

		$statisticRecord = \Bitrix\Voximplant\StatisticTable::getByCallId($callId);
		if (!$statisticRecord)
		{
			$result->addError(new Error("Call is not found in the statistic table. Looks like it is not finished yet."));
			return $result;
		}

		$saveResult = static::saveFile($statisticRecord['CALL_START_DATE']->format("Y-m"), $fileName, $fileArray, $statisticRecord['PORTAL_USER_ID']);
		if (!$saveResult->isSuccess())
		{
			$result->addErrors($saveResult->getErrors());
			return $result;
		}
		$saveResultData = $saveResult->getData();
		$file = $saveResultData['FILE'];

		$attachResult = static::attachFile($callId, $file);
		if (!$attachResult->isSuccess())
		{
			$result->addErrors($attachResult->getErrors());
			return $result;
		}

		$result->setData([
			'FILE_ID' => $file->getId()
		]);

		return $result;
	}

	public static function searchCrmEntities($phoneNumber)
	{
		$result = new Result();

		if (!Loader::includeModule('crm'))
		{
			$result->addError(new Error('CRM is not installed.'));
			return $result;
		}

		$timeManInstalled = Loader::includeModule('timeman');

		$userId = Security\Helper::getCurrentUserId();
		$searchResult = \CCrmSipHelper::findByPhoneNumber($phoneNumber, ['USER_ID' => $userId]);
		$resultData = [];
		$userIds = [];
		$entities = [];

		$entityNames = [\CCrmOwnerType::ContactName, \CCrmOwnerType::LeadName, \CCrmOwnerType::CompanyName];
		foreach ($entityNames as $entityName)
		{
			if (isset($searchResult[$entityName]))
			{
				foreach ($searchResult[$entityName] as $entityData)
				{
					$resultData[] = [
						'CRM_ENTITY_TYPE' => $entityName,
						'CRM_ENTITY_ID' => $entityData['ID'],
						'ASSIGNED_BY_ID' => $entityData['ASSIGNED_BY_ID']
					];
					$userIds[] = $entityData['ASSIGNED_BY_ID'];
					$entities[] = [
						'TYPE' => $entityName,
						'ID' => $entityData['ID']
					];
				}
			}
		}

		$crmEntityFields = \CVoxImplantCrmHelper::resolveEntitiesFields($entities);

		foreach ($resultData as $k => $v)
		{
			if (isset($crmEntityFields[$v['CRM_ENTITY_TYPE'] . ':' . $v['CRM_ENTITY_ID']]))
			{
				$resultData[$k]['NAME'] = $crmEntityFields[$v['CRM_ENTITY_TYPE'] . ':' . $v['CRM_ENTITY_ID']]['NAME'];
			}
		}

		$userCursor = UserTable::getList([
			'select' => ['ID', 'UF_PHONE_INNER', 'WORK_PHONE', 'PERSONAL_PHONE' , 'PERSONAL_MOBILE'],
			'filter' => [
				'=ID' => $userIds
			]
		]);

		$userData = [];
		while ($row = $userCursor->fetch())
		{
			$userId = $row['ID'];
			$userData[$userId] = $row;

			if ($timeManInstalled)
			{
				$tmUser = new \CTimeManUser($userId);
				$tmSettings = $tmUser->GetSettings(['UF_TIMEMAN']);
				if (!$tmSettings['UF_TIMEMAN'])
				{
					$userData[$userId]['TIMEMAN_STATUS'] = 'UNAVAILABLE';
				}
				else
				{
					$userData[$userId]['TIMEMAN_STATUS'] = $tmUser->State();
				}
			}
			else
			{
				$userData[$userId]['TIMEMAN_STATUS'] = 'NOT_INSTALLED';
			}
		}

		foreach ($resultData as $k => $v)
		{
			$row = $userData[$v['ASSIGNED_BY_ID']];
			$resultData[$k]['ASSIGNED_BY'] = [
				'ID' => $row['ID'],
				'TIMEMAN_STATUS' => $row['TIMEMAN_STATUS'],
				'USER_PHONE_INNER' => $row['UF_PHONE_INNER'],
				'WORK_PHONE' => $row['WORK_PHONE'],
				'PERSONAL_PHONE' => $row['PERSONAL_PHONE'],
				'PERSONAL_MOBILE' => $row['PERSONAL_MOBILE'],
			];
		}
		$result->setData($resultData);

		return $result;
	}

	/**
	 * @param array{NUMBER: string, NAME: string, CRM_AUTO_CREATE: string} $externalLine
	 * @param $restAppId
	 * @return Result
	 */
	public static function addExternalLine(array $externalLine, $restAppId)
	{
		$result = new Result();
		$number = trim($externalLine['NUMBER']);
		if ($number == '')
		{
			$result->addError(new Error('NUMBER should not be empty'));
			return $result;
		}

		try
		{
			$insertResult = ExternalLineTable::add([
				'NUMBER' => $number,
				'NAME' => $externalLine['NAME'],
				'CRM_AUTO_CREATE' => $externalLine['CRM_AUTO_CREATE'],
				'REST_APP_ID' => $restAppId
			]);

			if(!$insertResult->isSuccess())
			{
				return $result->addErrors($insertResult->getErrors());
			}
		}
		catch (SqlQueryException $exception)
		{
			if (mb_strpos($exception->getMessage(), '(1062)') !== false)
			{
				return $result->addError(new Error("Line already exists"));
			}
			else
			{
				self::writeToLogException($exception);
				return $result->addError(new Error("DB error"));
			}
		}
		Application::getInstance()->addBackgroundJob(
			["CVoxImplantUser", "clearCache"],
			[],
			Application::JOB_PRIORITY_LOW
		);
		$result->setData([
			'ID' => $insertResult->getId()
		]);
		return $result;
	}

	public static function updateExternalLine($number, $updatingFields, $restAppId)
	{
		$result = new Result();
		$number = trim($number);
		if ($number == '')
		{
			$result->addError(new Error('NUMBER should not be empty'));
			return $result;
		}

		$row = ExternalLineTable::getRow([
			'filter' => [
				'=NUMBER' => $number,
				'=REST_APP_ID' =>$restAppId
			]
		]);

		if (!$row)
		{
			$result->addError(new Error('Could not find line with number ' . $number));
			return $result;
		}

		$updateResult = ExternalLineTable::update($row['ID'], $updatingFields);

		if (!$updateResult->isSuccess())
		{
			$result->addErrors($updateResult->getErrors());
			return $result;
		}

		Application::getInstance()->addBackgroundJob(
			["CVoxImplantUser", "clearCache"],
			[],
			Application::JOB_PRIORITY_LOW
		);

		$updateResult->setData([
			'ID' => $updateResult->getId()
		]);

		return $updateResult;
	}

	public static function deleteExternalLine($number, $restAppId)
	{
		$result = new Result();
		$number = trim($number);
		if ($number == '')
		{
			$result->addError(new Error('NUMBER should not be empty'));
			return $result;
		}

		$row = ExternalLineTable::getRow([
			'filter' => [
				'=NUMBER' => $number,
				'=REST_APP_ID' =>$restAppId
			]
		]);

		if (!$row)
		{
			$result->addError(new Error('Could not find line with number ' . $number));
			return $result;
		}

		$deleteResult = ExternalLineTable::delete($row['ID']);
		if (!$deleteResult->isSuccess())
		{
			$result->addErrors($deleteResult->getErrors());
			return $result;
		}

		Application::getInstance()->addBackgroundJob(
			["CVoxImplantUser", "clearCache"],
			[],
			Application::JOB_PRIORITY_LOW
		);

		return $result;
	}

	public static function getExternalLines($restAppId)
	{
		$result = new Result();

		$query =
			ExternalLineTable::query()
				->setSelect([
					'NUMBER',
					'NAME',
					'CRM_AUTO_CREATE',
				])
				->where('REST_APP_ID', $restAppId)
		;

		$data = [];
		foreach ($query->exec() as $row)
		{
			$data[] = [
				'NUMBER' => $row['NUMBER'],
				'NAME' => $row['NAME'],
				'CRM_AUTO_CREATE' => $row['CRM_AUTO_CREATE'],
			];
		}
		$result->setData($data);

		return $result;
	}

	public static function onRestAppInstall($params)
	{
		if(!\Bitrix\Main\Loader::includeModule('rest'))
		{
			return;
		}

		\CVoxImplantUser::clearCache();
	}

	public static function onRestAppDelete($params)
	{
		if (!\Bitrix\Main\Loader::includeModule('rest'))
		{
			return;
		}
		$restAppId = $params['APP_ID'];
		$externalNumberIds = [];

		$cursor = ExternalLineTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=REST_APP_ID' => $restAppId
			]
		]);
		while ($row = $cursor->fetch())
		{
			$externalNumberIds[] = $row['ID'];
		}

		foreach ($externalNumberIds as $externalNumberId)
		{
			ExternalLineTable::delete($externalNumberId);
		}

		Application::getInstance()->addBackgroundJob(
			["CVoxImplantUser", "clearCache"],
			[],
			Application::JOB_PRIORITY_LOW
		);
	}

	public static function deleteFile($fileId, $userId): void
	{
		$oldRecord = File::loadById($fileId);
		if ($oldRecord instanceof File)
		{
			$oldRecord->delete($userId);
		}
	}

	/**
	 * @param $folderName
	 * @param $fileName
	 * @param $fileArray
	 * @param $userId
	 * @return Result
	 */
	protected static function saveFile($folderName, $fileName, $fileArray, $userId)
	{
		$result = new Result();
		if (!Loader::includeModule('disk'))
		{
			return $result->addError(new Error('Disk module is not installed'));
		}

		$uploadFolder = \CVoxImplantDiskHelper::GetRecordsFolder($folderName);
		if (!$uploadFolder)
		{
			return $result->addError(new Error('Could not create shared folder for call records'));
		}

		$accessCodes = [];
		$rightsManager = \Bitrix\Disk\Driver::getInstance()->getRightsManager();
		$fullAccessTaskId = $rightsManager->getTaskIdByName($rightsManager::TASK_FULL);

		$accessCodes[] = [
			'ACCESS_CODE' => 'U'.intval($userId),
			'TASK_ID' => $fullAccessTaskId,
		];

		$file = $uploadFolder->uploadFile(
			$fileArray,
			[
				'NAME' => $fileName,
				'CREATED_BY' => $userId
			],
			$accessCodes
		);

		if ($file)
		{
			$result->setData([
				'FILE' => $file
			]);
		}
		else
		{
			$result->addErrors($uploadFolder->getErrors());
		}

		return $result;
	}

	/**
	 * Attaches record to the call and call activity.
	 * @param string $callId
	 * @param \Bitrix\Disk\File $file
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Exception
	 */
	protected static function attachFile(string $callId, File $file, string $recordUrl = null): Result
	{
		$result = new Result();

		if (!Loader::includeModule('crm'))
		{
			return $result->addError(new Error("CRM is not installed"));
		}

		$statisticRecord = StatisticTable::getByCallId($callId);
		if (!$statisticRecord)
		{
			return $result->addError(new Error("Call is not found in the statistic table. Looks like it is not finished yet."));
		}

		StatisticTable::update($statisticRecord['ID'], [
			'CALL_RECORD_ID' => $file->getFileId(),
			'CALL_WEBDAV_ID' => $file->getId(),
			'CALL_RECORD_URL' => $recordUrl,
		]);

		if ($statisticRecord['CRM_ACTIVITY_ID'])
		{
			$activity = \CCrmActivity::GetByID($statisticRecord['CRM_ACTIVITY_ID'], false);
		}
		else
		{
			$activity = \CCrmActivity::GetByOriginID('VI_' . $statisticRecord['CALL_ID'], false);
		}

		if ($activity)
		{
			$activityFields = [
				'STORAGE_TYPE_ID' => StorageType::Disk,
				'STORAGE_ELEMENT_IDS' => [$file->getId()]
			];

			$updateResult = \CCrmActivity::Update($activity['ID'], $activityFields, false);
			if(!$updateResult)
			{
				return $result->addError(new Error(\CCrmActivity::GetLastErrorMessage()));
			}
		}

		return $result;
	}

	protected static function isIncomingCall(Call $call): bool
	{
		return in_array(
			(int)$call->getIncoming(),
			[\CVoxImplantMain::CALL_INCOMING, \CVoxImplantMain::CALL_INCOMING_REDIRECT],
			true
		);
	}

	protected static function writeToLogException(\Throwable $e)
	{
		$exceptionHandler = Application::getInstance()->getExceptionHandler();
		$exceptionHandler->writeToLog($e);
	}
}
