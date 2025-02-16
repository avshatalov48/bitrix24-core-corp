<?php
if(!CModule::IncludeModule('rest'))
{
		return;
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rest\Exceptions\ArgumentTypeException;
use Bitrix\Rest\OAuth;
use Bitrix\Rest\APAuth;
use Bitrix\Voximplant\Security;
use Bitrix\Voximplant\Rest;
use Bitrix\Voximplant\Integration;
use Bitrix\Voximplant\StatisticTable;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

class CVoxImplantRestService extends IRestService
{
	private static $allowedFilterOperations = array('', '!', '<', '<=', '>', '>=', '><', '!><', '?', '=', '!=', '%', '!%', '');

	public static function OnRestServiceBuildDescription(): array
	{
		return [
			'telephony' => [
				'voximplant.url.get' => [__CLASS__, 'urlGet'],
				'voximplant.sip.get' => [__CLASS__, 'sipGet'],
				'voximplant.sip.add' => [__CLASS__, 'sipAdd'],
				'voximplant.sip.update' => [__CLASS__, 'sipUpdate'],
				'voximplant.sip.delete' => [__CLASS__, 'sipDelete'],
				'voximplant.sip.status' => [__CLASS__, 'sipStatus'],
				'voximplant.sip.connector.status' => [__CLASS__, 'sipConnectorStatus'],
				'voximplant.statistic.get' => [__CLASS__, 'statisticGet'],
				'voximplant.line.outgoing.set' => [__CLASS__, 'lineOutgoingSet'],
				'voximplant.line.outgoing.get' => [__CLASS__, 'lineOutgoingGet'],
				'voximplant.line.outgoing.sip.set' => [__CLASS__, 'lineOutgoingSipSet'],
				'voximplant.line.get' => [__CLASS__, 'lineGet'],
				'voximplant.tts.voices.get' => [__CLASS__, 'getVoiceList'],
				'voximplant.user.get' => [__CLASS__, 'getUser'],
				'voximplant.user.getDefaultLineId' => [__CLASS__, 'getUserDefaultLineId'],
				'voximplant.user.activatePhone' => [__CLASS__, 'activatePhone'],
				'voximplant.user.deactivatePhone' => [__CLASS__, 'deactivatePhone'],
				'voximplant.user.getNode' => [
					'callback' => [__CLASS__, 'getNode'],
					'options' => ['private' => true]
				],
				'voximplant.authorization.get' => [
					'callback' => [__CLASS__, 'getAuthorization'],
					'options' => ['private' => true]
				],
				'voximplant.authorization.signOneTimeKey' => [
					'callback' => [__CLASS__, 'signOneTimeKey'],
					'options' => ['private' => true]
				],
				'voximplant.authorization.onError' => [
					'callback' => [__CLASS__, 'onAuthorizationError'],
					'options' => ['private' => true]
				],
				'voximplant.call.init' => [ //not sure if this is still needed
					'callback' => [__CLASS__, 'initCall'],
					'options' => ['private' => true]
				],
				'voximplant.call.startWithDevice' => [
					'callback' => [__CLASS__, 'startCallWithDevice'],
					'options' => ['private' => true]
				],
				'voximplant.call.hangupDevice' => [
					'callback' => [__CLASS__, 'hangupDeviceCall'],
					'options' => ['private' => true]
				],
				'voximplant.call.sendWait' => [
					'callback' => [__CLASS__, 'sendWait'],
					'options' => ['private' => true]
				],
				'voximplant.call.sendReady' => [
					'callback' => [__CLASS__, 'sendReady'],
					'options' => ['private' => true]
				],
				'voximplant.call.answer' => [
					'callback' => [__CLASS__, 'answerCall'],
					'options' => ['private' => true]
				],
				'voximplant.call.skip' => [
					'callback' => [__CLASS__, 'skipCall'],
					'options' => ['private' => true]
				],
				'voximplant.call.busy' => [
					'callback' => [__CLASS__, 'rejectCallWithBusy'],
					'options' => ['private' => true]
				],
				'voximplant.call.hold' => [
					'callback' => [__CLASS__, 'holdCall'],
					'options' => ['private' => true]
				],
				'voximplant.call.unhold' => [
					'callback' => [__CLASS__, 'unholdCall'],
					'options' => ['private' => true]
				],
				'voximplant.call.startViaRest' => [
					'callback' => [__CLASS__, 'startCallViaRest'],
					'options' => ['private' => true]
				],
				'voximplant.call.get' => [
					'callback' => [__CLASS__, 'getCall'],
					'options' => ['private' => true]
				],
				'voximplant.call.intercept' => [
					'callback' => [__CLASS__, 'interceptCall'],
					'options' => ['private' => true]
				],
				'voximplant.call.saveComment' => [
					'callback' => [__CLASS__, 'saveCallComment'],
					'options' => ['private' => true]
				],
				'voximplant.call.startTransfer' => [
					'callback' => [__CLASS__, 'startCallTransfer'],
					'options' => ['private' => true]
				],
				'voximplant.call.completeTransfer' => [
					'callback' => [__CLASS__, 'completeCallTransfer'],
					'options' => ['private' => true]
				],
				'voximplant.call.cancelTransfer' => [
					'callback' => [__CLASS__, 'cancelCallTransfer'],
					'options' => ['private' => true]
				],
				'voximplant.call.onConnectionError' => [
					'callback' => [__CLASS__, 'onCallConnectionError'],
					'options' => ['private' => true]
				],

				'telephony.externalCall.searchCrmEntities' => [__CLASS__, 'searchCrmEntities'],
				'telephony.externalCall.register' => [__CLASS__, 'registerExternalCall'],
				'telephony.externalCall.finish' => [__CLASS__, 'finishExternalCall'],
				'telephony.externalCall.show' => [__CLASS__, 'showExternalCall'],
				'telephony.externalCall.hide' => [__CLASS__, 'hideExternalCall'],
				'telephony.externalCall.attachRecord' => [__CLASS__, 'attachRecord'],
				'telephony.call.attachTranscription' => [__CLASS__, 'attachTranscription'],
				'telephony.externalLine.add' => [__CLASS__, 'addExternalLine'],
				'telephony.externalLine.update' => [__CLASS__, 'updateExternalLine'],
				'telephony.externalLine.delete' => [__CLASS__, 'deleteExternalLine'],
				'telephony.externalLine.get' => [__CLASS__, 'getExternalLines'],
				CRestUtil::METHOD_UPLOAD => [__CLASS__, 'uploadRecord'],

				CRestUtil::EVENTS => [
					'OnVoximplantCallInit' => ['voximplant', 'onCallInit', [__CLASS__, 'onCallInit'], ["category" => \Bitrix\Rest\Sqs::CATEGORY_TELEPHONY]],
					'OnVoximplantCallStart' => ['voximplant', 'onCallStart', [__CLASS__, 'onCallStart'], ["category" => \Bitrix\Rest\Sqs::CATEGORY_TELEPHONY]],
					'OnVoximplantCallEnd' => ['voximplant', 'onCallEnd', [__CLASS__, 'onCallEnd'], ["category" => \Bitrix\Rest\Sqs::CATEGORY_TELEPHONY]],
					Rest\Helper::EVENT_START_EXTERNAL_CALL => ['voximplant', 'onExternalCallStart', [__CLASS__, 'filterApp'], ["category" => \Bitrix\Rest\Sqs::CATEGORY_TELEPHONY]],
					Rest\Helper::EVENT_START_EXTERNAL_CALLBACK => ['voximplant', 'onExternalCallBackStart', [__CLASS__, 'filterApp'], ["category" => \Bitrix\Rest\Sqs::CATEGORY_TELEPHONY]],
				],
				CRestUtil::PLACEMENTS => [
					Rest\Helper::PLACEMENT_CALL_CARD => [],
					Integration\Rest\AppPlacement::ANALYTICS_MENU => []
				]
			],
			'call' => [
				'voximplant.callback.start' => [__CLASS__, 'startCallback'],
				'voximplant.infocall.startwithtext' => [__CLASS__, 'startInfoCallWithText'],
				'voximplant.infocall.startwithsound' => [__CLASS__, 'startInfoCallWithSound'],
			]
		];
	}

	public static function urlGet()
	{
		return [
			'detail_statistics' => CVoxImplantHttp::GetServerAddress().CVoxImplantMain::GetPublicFolder().'detail.php',
			'buy_connector' => CVoxImplantHttp::GetServerAddress().'/settings/license_phone_sip.php',
			'edit_config' => CVoxImplantHttp::GetServerAddress().CVoxImplantMain::GetPublicFolder().'edit.php?ID=#CONFIG_ID#',
			'lines' => CVoxImplantHttp::GetServerAddress().CVoxImplantMain::GetPublicFolder().'lines.php',
		];
	}

	/**
	 * @param array{
	 *     FILTER: ?array,
	 *     SORT: string,
	 *     ORDER: string
	 * } $arParams
	 * @param $nav
	 * @param $server
	 * @return array|Countable
	 */
	public static function sipGet($arParams, $nav, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if (!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$arParams = array_change_key_case($arParams, CASE_UPPER);
		$arParams['FILTER'] ??= null;
		$arParams['SORT'] ??= null;
		$arParams['ORDER'] ??= null;

		$sort = $arParams['SORT'];
		$order = $arParams['ORDER'];

		if (isset($arParams['FILTER']) && is_array($arParams['FILTER']))
		{
			$arFilter = array_change_key_case($arParams['FILTER'], CASE_UPPER);
		}
		else
		{
			$arFilter = [];
		}
		$arFilter['APP_ID'] = $server->getAppId();

		$arReturn = [];

		$cnt = \Bitrix\Voximplant\SipTable::getCount($arFilter);
		if ($cnt > 0)
		{
			$arNavParams = self::getNavData($nav, true);

			$arSort = [];
			if ($sort && $order)
			{
				$arSort[$sort] = $order;
			}

			$dbRes = \Bitrix\Voximplant\SipTable::getList([
				'order' => $arSort,
				'select' => ['*', 'TITLE'],
				'filter' => $arFilter,
				'limit' => $arNavParams['limit'],
				'offset' => $arNavParams['offset'],
			]);

			$result = [];
			while ($arData = $dbRes->fetch())
			{
				unset($arData['ID']);
				unset($arData['APP_ID']);
				if ($arData['TYPE'] == CVoxImplantSip::TYPE_CLOUD)
				{
					unset($arData['INCOMING_SERVER']);
					unset($arData['INCOMING_LOGIN']);
					unset($arData['INCOMING_PASSWORD']);
				}
				else
				{
					unset($arData['REG_ID']);
				}
				$result[] = $arData;
			}

			return self::setNavData(
				$result,
				[
					"count" => $cnt,
					"offset" => $arNavParams['offset']
				]
			);
		}

		return $arReturn;
	}

	public static function sipAdd($arParams, $nav, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if (!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$arParams = array_change_key_case($arParams, CASE_UPPER);
		$arParams['TYPE'] ??= null;
		$arParams['TITLE'] ??= null;
		$arParams['SERVER'] ??= null;
		$arParams['LOGIN'] ??= null;
		$arParams['PASSWORD'] ??= null;

		if (!isset($arParams['TYPE']))
		{
			$arParams['TYPE'] = CVoxImplantSip::TYPE_CLOUD;
		}

		$viSip = new CVoxImplantSip();
		$configId = $viSip->Add([
			'TYPE' => mb_strtolower($arParams['TYPE']),
			'PHONE_NAME' => $arParams['TITLE'],
			'SERVER' => $arParams['SERVER'],
			'LOGIN' => $arParams['LOGIN'],
			'PASSWORD' => $arParams['PASSWORD'],
			'APP_ID' => $server->getAppId()
		]);
		if (!$configId || $viSip->GetError()->error)
		{
			throw new Bitrix\Rest\RestException($viSip->GetError()->msg, $viSip->GetError()->code, CRestServer::STATUS_WRONG_REQUEST);
		}

		$result = $viSip->Get($configId, ['WITH_TITLE' => true]);
		unset($result['APP_ID']);
		unset($result['REG_STATUS']);

		return $result;
	}

	public static function sipUpdate($arParams, $nav, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if (!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$arParams = array_change_key_case($arParams, CASE_UPPER);
		$arParams['CONFIG_ID'] ??= null;
		$arParams['TYPE'] ??= null;
		$arParams['TITLE'] ??= null;
		$arParams['SERVER'] ??= null;
		$arParams['LOGIN'] ??= null;
		$arParams['PASSWORD'] ??= null;

		$cnt = \Bitrix\Voximplant\SipTable::getCount([
			'CONFIG_ID' => $arParams["CONFIG_ID"],
			'APP_ID' => $server->getAppId()
		]);
		if ($cnt <= 0)
		{
			throw new Bitrix\Rest\RestException("Specified CONFIG_ID is not found", Bitrix\Rest\RestException::ERROR_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
		}

		if (!isset($arParams['TYPE']))
		{
			$arParams['TYPE'] = CVoxImplantSip::TYPE_CLOUD;
		}

		$arUpdate = [
			'TYPE' => $arParams['TYPE'],
			'NEED_UPDATE' => "Y",
		];
		if (isset($arParams['TITLE']))
		{
			$arUpdate['TITLE'] = $arParams['TITLE'];
		}
		if (isset($arParams['SERVER']))
		{
			$arUpdate['SERVER'] = $arParams['SERVER'];
		}
		if (isset($arParams['LOGIN']))
		{
			$arUpdate['LOGIN'] = $arParams['LOGIN'];
		}
		if (isset($arParams['PASSWORD']))
		{
			$arUpdate['PASSWORD'] = $arParams['PASSWORD'];
		}

		if (count($arUpdate) == 2)
		{
			return 1;
		}

		$viSip = new CVoxImplantSip();
		$result = $viSip->Update($arParams["CONFIG_ID"], $arUpdate);
		if (!$result || $viSip->GetError()->error)
		{
			throw new Bitrix\Rest\RestException($viSip->GetError()->msg, $viSip->GetError()->code, CRestServer::STATUS_WRONG_REQUEST);
		}

		return 1;
	}

	public static function sipDelete($arParams, $nav, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if (!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$arParams = array_change_key_case($arParams, CASE_UPPER);
		$arParams["CONFIG_ID"] ??= null;

		$cnt = \Bitrix\Voximplant\SipTable::getCount([
			'CONFIG_ID' => $arParams["CONFIG_ID"],
			'APP_ID' => $server->getAppId()
		]);
		if ($cnt <= 0)
		{
			throw new Bitrix\Rest\RestException("Specified CONFIG_ID is not found", Bitrix\Rest\RestException::ERROR_NOT_FOUND, CRestServer::STATUS_WRONG_REQUEST);
		}

		$viSip = new CVoxImplantSip();
		$result = $viSip->Delete($arParams['CONFIG_ID']);
		if (!$result || $viSip->GetError()->error)
		{
			throw new Bitrix\Rest\RestException($viSip->GetError()->msg, $viSip->GetError()->code, CRestServer::STATUS_WRONG_REQUEST);
		}

		return 1;
	}

	public static function sipStatus($arParams)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if (!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$arParams = array_change_key_case($arParams, CASE_UPPER);
		$arParams['REG_ID'] ??= null;

		$viSip = new CVoxImplantSip();
		$result = $viSip->GetSipRegistrations($arParams['REG_ID']);

		if (!$result)
		{
			throw new Bitrix\Rest\RestException($viSip->GetError()->msg, $viSip->GetError()->code, CRestServer::STATUS_WRONG_REQUEST);
		}

		$viSip->updateSipRegistrationStatus([
			'sip_registration_id' => $result->reg_id,
			'error_message' => $result->error_message,
			'status_code' => $result->status_code,
			'successful' => $result->status_result === 'success'
		]);

		return [
			'REG_ID' => $result->reg_id,
			'LAST_UPDATED' => $result->last_updated,
			'ERROR_MESSAGE' => $result->error_message,
			'STATUS_CODE' => $result->status_code,
			'STATUS_RESULT' => $result->status_result,
		];
	}

	public static function sipConnectorStatus()
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if (!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$ViHttp = new CVoxImplantHttp();
		$info = $ViHttp->GetSipInfo();
		if (!$info || $ViHttp->GetError()->error)
		{
			throw new Bitrix\Rest\RestException($ViHttp->GetError()->msg, $ViHttp->GetError()->code, CRestServer::STATUS_WRONG_REQUEST);
		}

		$result = [
			'FREE_MINUTES' => intval($info->FREE),
			'PAID' => $info->ACTIVE,
		];

		if ($info->ACTIVE)
		{
			$result['PAID_DATE_END'] = CRestUtil::ConvertDate($info->DATE_END);
		}

		return $result;
	}

	public static function statisticGet($arParams, $start, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if (!$permissions->canPerform(Security\Permissions::ENTITY_CALL_DETAIL, Security\Permissions::ACTION_VIEW))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		if (isset($arParams['SORT']) && !is_string($arParams['SORT']))
		{
			unset($arParams['SORT']);
			//throw new ArgumentTypeException('SORT', 'string');
		}
		if (isset($arParams['ORDER']) && !is_string($arParams['ORDER']))
		{
			unset($arParams['ORDER']);
			//throw new ArgumentTypeException('ORDER', 'string');
		}

		$sort = isset($arParams['SORT']) ? self::filterSortParam(trim($arParams['SORT'])) : null;
		$order = isset($arParams['ORDER']) ? self::filterOrderParam(trim($arParams['ORDER'])) : null;
		$arFilter = self::checkStatisticFilter($arParams['FILTER']);

		$allowedUserIds = Security\Helper::getAllowedUserIds(
			$permissions->getUserId(),
			$permissions->getPermission(Security\Permissions::ENTITY_CALL_DETAIL, Security\Permissions::ACTION_VIEW)
		);
		if (is_array($allowedUserIds))
		{
			$arFilter['PORTAL_USER_ID'] = $allowedUserIds;
		}

		$totalCount = $start >= 0 ? \Bitrix\Voximplant\StatisticTable::getCount($arFilter) : 0;

		$arNavParams = self::getNavData($start, true);

		$arSort = [];
		if ($sort && $order)
		{
			$arSort[$sort] = $order;
		}

		$result = [];
		$dbRes = \Bitrix\Voximplant\StatisticTable::getList([
			'order' => $arSort,
			'filter' => $arFilter,
			'limit' => $arNavParams['limit'],
			'offset' => $arNavParams['offset'],
		]);

		while ($arData = $dbRes->fetch())
		{
			$arData['RECORD_FILE_ID'] = (int)$arData['CALL_WEBDAV_ID'] ?: null;
			unset($arData['ACCOUNT_ID']);
			unset($arData['APPLICATION_ID']);
			unset($arData['APPLICATION_NAME']);
			unset($arData['CALL_LOG']);
			unset($arData['CALL_RECORD_ID']);
			unset($arData['CALL_WEBDAV_ID']);
			unset($arData['CALL_STATUS']);
			unset($arData['CALL_DIRECTION']);
			$arData['CALL_TYPE'] = $arData['INCOMING'];
			unset($arData['INCOMING']);
			$arData['CALL_START_DATE'] = CRestUtil::ConvertDateTime($arData['CALL_START_DATE']);
			$result[] = $arData;
		}

		return self::setNavData(
			$result,
			[
				"count" => $totalCount,
				"offset" => $arNavParams['offset']
			]
		);
	}

	private static function splitToOperationAndField($filterDefinition)
	{
		if (preg_match('/^([^a-zA-Z]*)(.*)/', $filterDefinition, $matches))
		{
			return ['operation' => $matches[1], 'field' => $matches[2]];
		}

		return null;
	}

	public static function checkStatisticFilter($arFilter)
	{
		if (!is_array($arFilter))
		{
			return [];
		}

		$arFilter = array_change_key_case($arFilter, CASE_UPPER);

		foreach ($arFilter as $key => $value)
		{
			$splittedFilterDefinition = self::splitToOperationAndField($key);

			if (is_array($value))
			{
				// ticket 178188: error happens when trying to filter by ID field using non-plain array as a value
				// It should be temporary fix as it should be handled in the ORM module (I think so)
				$value = $splittedFilterDefinition['field'] === 'ID' ? array_values($value) : $value;

				if (empty($value))
				{
					unset($arFilter[$key]);
					continue;
				}

				$isPlainArray = true;
				foreach ($value as $subKey => $subValue)
				{
					if(!is_int($subKey))
					{
						$isPlainArray = false;
						break;
					}
				}

				if (!$isPlainArray)
				{
					$subFilter = static::checkStatisticFilter($value);
					if (!empty($subFilter) && is_array($subFilter))
					{
						$arFilter[$key] = $subFilter;
					}
					else
					{
						unset($arFilter[$key]);
					}
					continue;
				}
			}

			if (!is_null($splittedFilterDefinition))
			{
				$operation = $splittedFilterDefinition['operation'];
				$field = $splittedFilterDefinition['field'];

				if (!in_array($operation, self::$allowedFilterOperations))
				{
					unset($arFilter[$key]);
				}
				else
				{
					$checkField = true;
					switch ($field)
					{
						case 'CALL_START_DATE':
							$value = CRestUtil::unConvertDateTime($value, true);
							break;
						case 'CALL_TYPE':
							$field = 'INCOMING';
							break;
						case 'CRM_BINDINGS.ENTITY_TYPE':
						case 'CRM_BINDINGS.ENTITY_ID':
							$checkField = false;
							if ($operation == '')
							{
								$operation = '=';
							}
							break;
					}

					if (StatisticTable::getEntity()->hasField($field))
					{
						if ($operation == '')
						{
							$operation = '=';
						}
					}
					elseif ($checkField)
					{
						unset($arFilter[$key]);
						continue;
					}

					$newKey = $operation . $field;
					if ($key != $newKey)
					{
						unset($arFilter[$key]);
					}
					$arFilter[$newKey] = $value;
				}
			}
			else
			{
				unset($arFilter[$key]);
			}
		}

		return $arFilter;
	}

	private static function filterSortParam(string $field): ?string
	{
		if (StatisticTable::getEntity()->hasField($field))
		{
			return $field;
		}

		return null;
	}

	private static function filterOrderParam(string $order): ?string
	{
		if (in_array($order, ['ASC', 'DESC']))
		{
			return $order;
		}

		return null;
	}

	public static function lineGet()
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if (!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		return CVoxImplantConfig::GetPortalNumbers(false);
	}

	public static function lineOutgoingSipSet($arParams)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if (!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$arParams = array_change_key_case($arParams, CASE_UPPER);
		$arParams['CONFIG_ID'] ??= null;

		$result = CVoxImplantConfig::SetPortalNumberByConfigId($arParams['CONFIG_ID']);
		if (!$result)
		{
			throw new Bitrix\Rest\RestException('Specified CONFIG_ID is not found', Bitrix\Rest\RestException::ERROR_ARGUMENT, CRestServer::STATUS_WRONG_REQUEST);
		}

		return 1;
	}

	public static function lineOutgoingSet($arParams)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if (!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$arParams = array_change_key_case($arParams, CASE_UPPER);
		$arParams['LINE_ID'] ??= null;

		CVoxImplantConfig::SetPortalNumber($arParams['LINE_ID']);

		return 1;
	}

	public static function lineOutgoingGet()
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if (!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}
		return CVoxImplantConfig::GetPortalNumber();
	}

	public static function getVoiceList()
	{
		return \Bitrix\Voximplant\Tts\Language::getList();
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 * @return array
	 */
	public static function getUser($params, $n, $server)
	{
		if (!isset($params['USER_ID']))
		{
			throw new \Bitrix\Rest\RestException('Parameter USER_ID is not set');
		}

		if (is_array($params['USER_ID']))
		{
			$userIds = array_map('intval', $params['USER_ID']);
		}
		else
		{
			$userIds = [(int)$params['USER_ID']];
		}

		$permissions = Security\Permissions::createWithCurrentUser();
		$allowedUserIds = Security\Helper::getAllowedUserIds(
			Security\Helper::getCurrentUserId(),
			$permissions->getPermission(Security\Permissions::ENTITY_USER, Security\Permissions::ACTION_MODIFY)
		);

		if (is_array($allowedUserIds))
		{
			$userIds = array_intersect($userIds, $allowedUserIds);
		}

		if (empty($userIds))
		{
			throw new \Bitrix\Rest\AccessException('You have no permission to query selected users');
		}

		if (Integration\Bitrix24::isInstalled())
		{
			$admins = Integration\Bitrix24::getAdmins();
		}
		else
		{
			$admins = [];
			$cursor = \CAllGroup::GetGroupUserEx(1);
			while ($row = $cursor->fetch())
			{
				$admins[] = (int)$row['USER_ID'];
			}
		}

		if (isset($admins[Security\Helper::getCurrentUserId()]))
		{
			$admins = [Security\Helper::getCurrentUserId()];
		}

		$server->requestConfirmation(
			$admins,
			GetMessage(
				'VI_REST_GET_USERS_CONFIRM',
				array('#APPLICATION_NAME#' => \Bitrix\Voximplant\Rest\Helper::getRestAppName($server->getClientId()))
			)
		);

		$arExtParams = [
			'FIELDS' => ['ID'],
			'SELECT' => [
				'UF_VI_PASSWORD',
				'UF_VI_BACKPHONE',
				'UF_VI_PHONE',
				'UF_VI_PHONE_PASSWORD',
				'UF_PHONE_INNER',
			]
		];

		$cursor = CUser::GetList(
			'',
			'',
			['ID' => join(' | ', $userIds)],
			$arExtParams
		);
		$result = [];

		$account = new CVoxImplantAccount();
		while ($row = $cursor->Fetch())
		{
			$result[] = [
				'ID' => $row['ID'],
				'DEFAULT_LINE' => $row['UF_VI_BACKPHONE'],
				'PHONE_ENABLED' => $row['UF_VI_PHONE'],
				'SIP_SERVER' => str_replace('voximplant.com', 'bitrixphone.com', $account->GetCallServer()),
				'SIP_LOGIN' => 'phone'.$row['ID'],
				'SIP_PASSWORD' => $row['UF_VI_PHONE_PASSWORD'],
				'INNER_NUMBER' => $row['UF_PHONE_INNER'],
			];
		}

		return $result;
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function getUserDefaultLineId($params, $n, $server)
	{
		$userId = static::getCurrentUserId();

		return [
			'defaultLineId' => CVoxImplantUser::getUserOutgoingLine($userId),
		];
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function activatePhone($params, $n, $server)
	{
		$params['USER_ID'] ??= null;

		$userId = (int)$params['USER_ID'];
		if ($userId === 0)
		{
			throw new \Bitrix\Rest\RestException('Parameter USER_ID is not set');
		}

		$permissions = Security\Permissions::createWithCurrentUser();
		if (!CVoxImplantUser::canModify($userId, $permissions))
		{
			throw new \Bitrix\Rest\RestException('You are not allowed to modify user\'s settings');
		}

		$user = new CVoxImplantUser();
		$user->SetPhoneActive($userId, true);
		return 1;
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function deactivatePhone($params, $n, $server)
	{
		$params['USER_ID'] ??= null;

		$userId = (int)$params['USER_ID'];
		if ($userId === 0)
		{
			throw new \Bitrix\Rest\RestException('Parameter USER_ID is not set');
		}

		$permissions = Security\Permissions::createWithCurrentUser();
		if (!CVoxImplantUser::canModify($userId, $permissions))
		{
			throw new \Bitrix\Rest\RestException('You are not allowed to modify user\'s settings');
		}

		$user = new CVoxImplantUser();
		$user->SetPhoneActive($userId, true);

		return 1;
	}

	public static function getNode(): array
	{
		$ViHttp = new CVoxImplantHttp();
		$accountNode = $ViHttp->GetAccountNode();

		if ($accountNode === false)
		{
			return [
				'error' => true,
				'code' => $ViHttp->GetError()->code,
				'message' => $ViHttp->GetError()->msg
			];
		}

		return [
			'node' => $accountNode->node
		];
	}


	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function getAuthorization($params, $n, $server)
	{
		$allowedAuthTypes = [\Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE => true];
		if (Loader::includeModule('im') && class_exists('\Bitrix\Im\Call\Auth'))
		{
			$allowedAuthTypes[\Bitrix\Im\Call\Auth::AUTH_TYPE] = true;
		}
		if (!isset($allowedAuthTypes[$server->getAuthType()]))
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$userId = static::getCurrentUserId();
		$viUser = new CVoxImplantUser();

		$result = $viUser->getAuthorizationInfo($userId, true);
		if (!$result->isSuccess())
		{
			$errors = $result->getErrors();
			throw new Bitrix\Rest\RestException($errors[0]->getMessage(), $errors[0]->getCode());
		}
		$data = $result->getData();

		return [
			'SERVER' => $data['server'],
			'LOGIN' => $data['login']
		];
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function signOneTimeKey($params, $n, $server)
	{
		$allowedAuthTypes = [\Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE => true];
		if (Loader::includeModule('im') && class_exists('\Bitrix\Im\Call\Auth'))
		{
			$allowedAuthTypes[\Bitrix\Im\Call\Auth::AUTH_TYPE] = true;
		}
		if (!isset($allowedAuthTypes[$server->getAuthType()]))
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$voxMain = new CVoxImplantMain(static::getCurrentUserId());
		$result = $voxMain->GetOneTimeKey($_POST['KEY']);
		if (!$result)
		{
			throw new \Bitrix\Rest\RestException($voxMain->GetError()->msg, $voxMain->GetError()->code);
		}

		return [
			'HASH' => $result,
			'ERROR' => ''
		];
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function onAuthorizationError($params, $n, $server)
	{
		$allowedAuthTypes = [\Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE => true];
		if (Loader::includeModule('im') && class_exists('\Bitrix\Im\Call\Auth'))
		{
			$allowedAuthTypes[Bitrix\Im\Call\Auth::AUTH_TYPE] = true;
		}
		if (!isset($allowedAuthTypes[$server->getAuthType()]))
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$voxMain = new CVoxImplantMain(static::getCurrentUserId());
		$voxMain->ClearUserInfo();
		$voxMain->ClearAccountInfo();
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function initCall($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$voxMain = new CVoxImplantMain(static::getCurrentUserId());
		$result = $voxMain->GetDialogInfo($_POST['NUMBER']);
		if (!$result)
		{
			throw new \Bitrix\Rest\RestException($voxMain->GetError()->msg, $voxMain->GetError()->code);
		}

		return $result;
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function startCallWithDevice($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$number = $params['NUMBER'];
		$callParams = $params['PARAMS'];
		$userId = static::getCurrentUserId();

		if (!CVoxImplantUser::GetPhoneActive($userId))
		{
			throw new \Bitrix\Rest\RestException("User has no phone.", "NO_PHONE", \CRestServer::STATUS_NOT_FOUND);
		}

		return  CVoxImplantOutgoing::StartCall($userId, $number, $callParams);
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function hangupDeviceCall($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		CVoxImplantIncoming::SendCommand([
			'CALL_ID' => $params['CALL_ID'],
			'COMMAND' => CVoxImplantIncoming::RULE_HUNGUP
		]);

		return 1;
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 * @return array
	 * @throws \Bitrix\Rest\RestException
	 */
	public static function sendWait($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$call = \Bitrix\Voximplant\Model\CallTable::getByCallId($params['CALL_ID']);
		if (!$call)
		{
			throw new Bitrix\Rest\RestException("Call is not found, or already finished", Bitrix\Rest\RestException::ERROR_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
		}

		if ($call['STATUS'] !== \Bitrix\Voximplant\Model\CallTable::STATUS_WAITING)
		{
			throw new Bitrix\Rest\RestException("Call is already answered", "ERROR_WRONG_STATE");
		}

		$result = CVoxImplantIncoming::SendCommand(
			[
				'CALL_ID' => $params['CALL_ID'],
				'COMMAND' => CVoxImplantIncoming::RULE_WAIT,
				'DEBUG_INFO' => $params['DEBUG_INFO']
			],
			true
		);

		if (!$result->isSuccess())
		{
			$errors = $result->getErrors();
			throw new Bitrix\Rest\RestException($errors[0]->getMessage(), $errors[0]->getCode());
		}

		return [
			"SUCCESS" => true
		];
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function sendReady($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$call = \Bitrix\Voximplant\Model\CallTable::getByCallId($params['CALL_ID']);

		if ($call)
		{
			\Bitrix\Voximplant\Model\CallTable::update($call['ID'], [
				'STATUS' => \Bitrix\Voximplant\Model\CallTable::STATUS_CONNECTING
			]);
		}

		CVoxImplantIncoming::SendCommand([
			'CALL_ID' => $params['CALL_ID'],
			'COMMAND' => CVoxImplantIncoming::RULE_USER,
			'USER_ID' => static::getCurrentUserId()
		]);
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function answerCall($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$callId = $params['CALL_ID'];
		$userId = static::getCurrentUserId();
		$call = \Bitrix\Voximplant\Model\CallTable::getByCallId($callId);
		if (!$call)
		{
			throw new Bitrix\Rest\RestException("Call is not found, or already finished", Bitrix\Rest\RestException::ERROR_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
		}

		if ($call['STATUS'] !== \Bitrix\Voximplant\Model\CallTable::STATUS_WAITING)
		{
			throw new Bitrix\Rest\RestException("Call is already answered", "ERROR_WRONG_STATE");
		}

		$result = CVoxImplantIncoming::SendCommand(
			[
				'CALL_ID' => $callId,
				'COMMAND' => CVoxImplantIncoming::RULE_WAIT
			],
			true
		);

		if (!$result->isSuccess())
		{
			$errors = $result->getErrors();
			throw new Bitrix\Rest\RestException($errors[0]->getMessage(), $errors[0]->getCode());
		}

		CVoxImplantIncoming::SendPullEvent([
			'COMMAND' => 'answer_self',
			'USER_ID' => $userId,
			'CALL_ID' => $callId,
		]);

		return [
			"SUCCESS" => true
		];
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function skipCall($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		CVoxImplantIncoming::SendCommand([
			'CALL_ID' => $params['CALL_ID'],
			'COMMAND' => CVoxImplantIncoming::RULE_QUEUE
		]);
	}

	public static function rejectCallWithBusy($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		CVoxImplantIncoming::SendCommand(Array(
			'CALL_ID' => $params['CALL_ID'],
			'COMMAND' => CVoxImplantIncoming::COMMAND_BUSY,
			'DEBUG_INFO' => $params['DEBUG_INFO'],
		));
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function holdCall($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$userId = static::getCurrentUserId();
		$call = \Bitrix\Voximplant\Call::load($params['CALL_ID']);
		if ($call)
		{
			$call->getSignaling()->sendHold($userId);
			$call->getScenario()->sendHold($userId);
		}
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function unholdCall($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$userId = static::getCurrentUserId();
		$call = \Bitrix\Voximplant\Call::load($params['CALL_ID']);
		if ($call)
		{
			$call->getSignaling()->sendUnHold($userId);
			$call->getScenario()->sendUnHold($userId);
		}
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function startCallViaRest($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$callParams = is_array($params['PARAMS']) ? $params['PARAMS'] : [];
		$userId = static::getCurrentUserId();
		$isMobile = \Bitrix\Main\Context::getCurrent()->getRequest()->get('bx_mobile') === 'Y';
		$callParams['IS_MOBILE'] = $isMobile;

		$startResult = \Bitrix\Voximplant\Rest\Helper::startCall(
			$params['NUMBER'],
			$userId,
			$params['LINE_ID'],
			$callParams
		);

		$result = $startResult->toArray();
		if ($startResult->isSuccess())
		{
			$callId = $result['DATA']['CALL_ID'];

			$result['DATA']['CRM'] = CVoxImplantCrmHelper::GetDataForPopup($callId, $params['NUMBER'], $userId);

			if ($params['SHOW'] === 'Y')
			{
				Rest\Helper::showExternalCall([
					'USER_ID' => $userId,
					'CALL_ID' => $callId
				]);
			}
		}
		return $result;
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function getCall($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$call = \Bitrix\Voximplant\Model\CallTable::getByCallId($params['CALL_ID']);

		if (!$call)
		{
			throw new \Bitrix\Rest\RestException("Call is not found, or finished", "NOT_FOUND", \CRestServer::STATUS_NOT_FOUND);
		}

		return $call;
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function interceptCall($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$interceptResult = false;
		$userId = static::getCurrentUserId();
		$callId = CVoxImplantIncoming::findCallToIntercept($userId);
		if ($callId)
		{
			$interceptResult = CVoxImplantIncoming::interceptCall($userId, $callId);
		}

		$result =  [
			'FOUND' => $interceptResult ? 'Y' : 'N'
		];
		if (!$interceptResult)
		{
			$result['ERROR'] = Loc::getMessage('VI_REST_CALL_FOR_INTERCEPT_NOT_FOUND');
		}

		return $result;
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function saveCallComment($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$callId = $params['CALL_ID'];
		$comment = $params['COMMENT'];
		$call = \Bitrix\Voximplant\Model\CallTable::getByCallId($callId);
		if ($call)
		{
			\Bitrix\Voximplant\Model\CallTable::update($call['ID'], [
				'COMMENT' => $comment
			]);

			return 1;
		}

		$statisticCall = \Bitrix\Voximplant\StatisticTable::getRow(['filter' => [
			'=CALL_ID' => $callId
		]]);
		if($statisticCall)
		{
			\Bitrix\Voximplant\StatisticTable::update($statisticCall['ID'], [
				'COMMENT' => $comment
			]);

			return 1;
		}

		throw new \Bitrix\Rest\RestException('Call is not found, or finished', 'NOT_FOUND', \CRestServer::STATUS_NOT_FOUND);
	}

	public static function startCallTransfer($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$result = Bitrix\Voximplant\Transfer\Transferor::initiateTransfer(
			$params['CALL_ID'],
			Security\Helper::getCurrentUserId(),
			$params['TARGET_TYPE'],
			$params['TARGET_ID'],
		);
		return $result->toArray();
	}

	public static function completeCallTransfer($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}
		$call = \Bitrix\Voximplant\Call::load($params['CALL_ID']);
		if (!$call)
		{
			throw new \Bitrix\Rest\RestException("Call is not found, or finished", "NOT_FOUND", \CRestServer::STATUS_NOT_FOUND);
		}

		$call->getScenario()->sendCompleteTransfer(Security\Helper::getCurrentUserId());

		return null;
	}

	public static function cancelCallTransfer($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$call = \Bitrix\Voximplant\Call::load($params['CALL_ID']);
		if (!$call)
		{
			throw new \Bitrix\Rest\RestException("Call is not found, or finished", "NOT_FOUND", \CRestServer::STATUS_NOT_FOUND);
		}

		$call->getScenario()->sendCancelTransfer(Security\Helper::getCurrentUserId());

		return null;
	}

	public static function onCallConnectionError($params, $n, $server)
	{
		if ($server->getAuthType() !== \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("This method is only available for internal usage.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$callId = $params['CALL_ID'];
		$error = $params['ERROR'];
		$userId = Security\Helper::getCurrentUserId();

		$call = \Bitrix\Voximplant\Call::load($callId);
		if(!$call)
		{
			throw new \Bitrix\Rest\RestException("Call is not found, or finished", "NOT_FOUND", \CRestServer::STATUS_NOT_FOUND);
		}
		$call->getScenario()->sendConnectionError($userId, $error);
	}

	public static function startCallback($params, $n, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if (!$permissions->canPerform(Security\Permissions::ENTITY_CALL, Security\Permissions::ACTION_PERFORM, Security\Permissions::PERMISSION_ANY))
		{
			throw new \Bitrix\Rest\AccessException();
		}
		$params['FROM_LINE'] ??= null;
		$params['TO_NUMBER'] ??= null;
		$params['TEXT_TO_PRONOUNCE'] ??= null;
		$params['VOICE'] ??= null;

		$fromLine = $params['FROM_LINE'];
		$toNumber = $params['TO_NUMBER'];
		$textToPronounce = $params['TEXT_TO_PRONOUNCE'];
		$voice = $params['VOICE'];

		$callbackResult = CVoxImplantOutgoing::startCallBack($fromLine, $toNumber, $textToPronounce, $voice);
		if (!$callbackResult->isSuccess())
		{
			throw new \Bitrix\Rest\RestException(implode('; ', $callbackResult->getErrorMessages()));
		}

		$callbackData = $callbackResult->getData();
		$result = [
			'RESULT' => true,
			'CALL_ID' => $callbackData['CALL_ID']
		];

		return $result;
	}

	public static function startInfoCallWithText($params, $n, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if (!$permissions->canPerform(Security\Permissions::ENTITY_CALL, Security\Permissions::ACTION_PERFORM, Security\Permissions::PERMISSION_ANY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$params['FROM_LINE'] ??= null;
		$params['TO_NUMBER'] ??= null;
		$params['TEXT_TO_PRONOUNCE'] ??= null;
		$params['VOICE'] ??= null;

		$fromLine = $params['FROM_LINE'];
		$toNumber = $params['TO_NUMBER'];
		$textToPronounce = $params['TEXT_TO_PRONOUNCE'];
		$voice = $params['VOICE'];

		$infoCallResult = CVoxImplantOutgoing::StartInfoCallWithText($fromLine, $toNumber, $textToPronounce, $voice);
		if (!$infoCallResult->isSuccess())
		{
			throw new \Bitrix\Rest\RestException(implode('; ', $infoCallResult->getErrorMessages()));
		}

		$infoCallData = $infoCallResult->getData();
		$result = [
			'RESULT' => true,
			'CALL_ID' => $infoCallData['CALL_ID']
		];

		return $result;
	}

	public static function startInfoCallWithSound($params, $n, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if (!$permissions->canPerform(Security\Permissions::ENTITY_CALL, Security\Permissions::ACTION_PERFORM, Security\Permissions::PERMISSION_ANY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$params['FROM_LINE'] ??= null;
		$params['TO_NUMBER'] ??= null;
		$params['URL'] ??= null;

		$fromLine = $params['FROM_LINE'];
		$toNumber = $params['TO_NUMBER'];
		$soundUrl = $params['URL'];

		$infoCallResult = CVoxImplantOutgoing::StartInfoCallWithSound($fromLine, $toNumber, $soundUrl);
		if (!$infoCallResult->isSuccess())
		{
			throw new \Bitrix\Rest\RestException(implode('; ', $infoCallResult->getErrorMessages()));
		}

		$infoCallData = $infoCallResult->getData();
		$result = [
			'RESULT' => true,
			'CALL_ID' => $infoCallData['CALL_ID']
		];

		return $result;
	}

	/**
	 * @param array $params
	 * @param $n
	 * @param CRestServer $server
	 */
	public static function searchCrmEntities($params, $n, $server)
	{
		$params['PHONE_NUMBER'] ??= null;

		$phoneNumber = (string)$params['PHONE_NUMBER'];

		if ($phoneNumber == '')
		{
			throw new \Bitrix\Rest\RestException('PHONE_NUMBER is empty');
		}

		$result = Rest\Helper::searchCrmEntities($phoneNumber);
		if (!$result->isSuccess())
		{
			throw new \Bitrix\Rest\RestException(implode('; ', $result->getErrorMessages()));
		}

		return $result->getData();
	}

	/**
	 * @param array $params
	 * @param $n
	 * @param CRestServer $server
	 */
	public static function registerExternalCall($params, $n, $server)
	{
		if ($server->getAuthType() !== Oauth\Auth::AUTH_TYPE && $server->getAuthType() !== APAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\AuthTypeException();
		}
		$params['USER_PHONE_INNER'] ??= null;
		$params['USER_ID'] ??= null;
		$params['PHONE_NUMBER'] ??= null;
		$params['CALL_START_DATE'] ??= null;
		$params['CRM_CREATE'] ??= null;
		$params['CRM_SOURCE'] ??= null;
		$params['CRM_ENTITY_TYPE'] ??= null;
		$params['CRM_ENTITY_ID'] ??= null;
		$params['SHOW'] ??= null;
		$params['CALL_LIST_ID'] ??= null;
		$params['LINE_NUMBER'] ??= null;
		$params['TYPE'] ??= null;

		/*
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_CALL_DETAIL, Security\Permissions::ACTION_MODIFY, Security\Permissions::PERMISSION_ANY))
		{
			throw new \Bitrix\Rest\AccessException();
		}
		*/

		$clientId = $server->getClientId();
		$row = \Bitrix\Rest\AppTable::getByClientId($clientId);
		$appId = $row['ID'];

		$userId = (int)$params['USER_ID'];
		if ($userId == 0)
		{
			$userId = Rest\Helper::getUserByPhone($params['USER_PHONE_INNER']);
		}

		if (!$userId)
		{
			throw new \Bitrix\Rest\RestException('USER_ID or USER_PHONE_INNER should be set');
		}

		if (!in_array($params['TYPE'], CVoxImplantMain::getCallTypes()))
		{
			throw new \Bitrix\Rest\RestException('Unknown TYPE');
		}

		if (isset($params['CALL_START_DATE']) && $params['CALL_START_DATE'] !== '')
		{
			$parsedDate = CRestUtil::unConvertDateTime($params['CALL_START_DATE']);
			if ($parsedDate === false)
			{
				throw new \Bitrix\Rest\RestException('CALL_START_DATE should be in the ISO-8601 format');
			}

			$startDate = new \Bitrix\Main\Type\DateTime($parsedDate);
		}
		else
		{
			$startDate = new \Bitrix\Main\Type\DateTime();
		}

		$result = Rest\Helper::registerExternalCall([
			'USER_ID' => $userId,
			'PHONE_NUMBER' => $params['PHONE_NUMBER'] ?? null,
			'LINE_NUMBER' => $params['LINE_NUMBER'] ?? null,
			'EXTERNAL_CALL_ID' => $params['EXTERNAL_CALL_ID'],
			'TYPE' => $params['TYPE'] ?? null,
			'CALL_START_DATE' => $startDate,
			'CRM' => $params['CRM'] ?? null,
			'CRM_CREATE' => $params['CRM_CREATE'] ?? null,
			'CRM_SOURCE' => $params['CRM_SOURCE'] ?? null,
			'CRM_ENTITY_TYPE' => $params['CRM_ENTITY_TYPE'] ?? null,
			'CRM_ENTITY_ID' => $params['CRM_ENTITY_ID'] ?? null,
			'REST_APP_ID' => $appId,
			'ADD_TO_CHAT' => $params['ADD_TO_CHAT'] ?? null,
			'SHOW' => isset($params['SHOW']) ? (bool)$params['SHOW'] : true
		]);

		if (!$result->isSuccess())
		{
			throw new \Bitrix\Rest\RestException(implode('; ', $result->getErrorMessages()));
		}

		$code = $row['CODE'] ? : 'webHook' . $server->getPasswordId();
		if ($code)
		{
			AddEventToStatFile(
				'voximplant',
				'callRegister',
				uniqid($code, true),
				$code,
				'type' . $params['TYPE']
			);
		}

		return $result->getData();
	}

	/**
	 * @param array $params
	 * @param $n
	 * @param CRestServer $server
	 */
	public static function finishExternalCall($params, $n, $server)
	{
		if ($server->getAuthType() !== Oauth\Auth::AUTH_TYPE && $server->getAuthType() !== APAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\AuthTypeException();
		}
		$params['CALL_ID'] ??= null;
		$params['USER_ID'] ??= null;
		$params['DURATION'] ??= null;
		$params['COST'] ??= null;
		$params['COST_CURRENCY'] ??= null;
		$params['STATUS_CODE'] ??= null;
		$params['FAILED_REASON'] ??= null;
		$params['RECORD_URL'] ??= null;
		$params['VOTE'] ??= null;
		$params['ADD_TO_CHAT'] ??= null;

		/*
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_CALL_DETAIL, Security\Permissions::ACTION_MODIFY, Security\Permissions::PERMISSION_ANY))
		{
			throw new \Bitrix\Rest\AccessException();
		}
		*/

		$userId = (int)$params['USER_ID'];
		if ($userId == 0)
		{
			$userId = Rest\Helper::getUserByPhone($params['USER_PHONE_INNER']);
		}

		if (!$userId)
		{
			throw new \Bitrix\Rest\RestException('USER_ID or USER_PHONE_INNER should be set');
		}

		$callId = $params['CALL_ID'];
		if (!is_string($callId))
		{
			throw new \Bitrix\Rest\RestException('CALL_ID must be a string', 'INVALID_ARGUMENT');
		}

		$result = Rest\Helper::finishExternalCall([
			'CALL_ID' => $callId,
			'USER_ID' => $userId,
			'DURATION' => (int)$params['DURATION'],
			'COST' => (double)$params['COST'],
			'COST_CURRENCY' => (string)$params['COST_CURRENCY'],
			'STATUS_CODE' => (string)$params['STATUS_CODE'],
			'FAILED_REASON' => (string)$params['FAILED_REASON'],
			'RECORD_URL' => (string)$params['RECORD_URL'],
			'VOTE' => (int)$params['VOTE'],
			'ADD_TO_CHAT' => $params['ADD_TO_CHAT'] != false,
		]);

		if (!$result->isSuccess())
		{
			throw new \Bitrix\Rest\RestException(implode('; ', $result->getErrorMessages()));
		}

		return $result->getData();
	}

	/**
	 * @param array $params
	 * @param $n
	 * @param CRestServer $server
	 */
	public static function showExternalCall($params, $n, $server)
	{
		$params['USER_ID'] ??= null;
		$params['CALL_ID'] ??= null;

		return Rest\Helper::showExternalCall([
			'CALL_ID' => (string)$params['CALL_ID'],
			'USER_ID' => $params['USER_ID'],
		]);
	}

	/**
	 * @param array $params
	 * @param $n
	 * @param CRestServer $server
	 */
	public static function hideExternalCall($params, $n, $server)
	{
		$params['USER_ID'] ??= null;
		$params['CALL_ID'] ??= null;

		return Rest\Helper::hideExternalCall([
			'CALL_ID' => (string)$params['CALL_ID'],
			'USER_ID' => $params['USER_ID']
		]);
	}

	/**
	 * @param array $params
	 * @param $n
	 * @param CRestServer $server
	 */
	public static function attachRecord($params, $n, $server)
	{
		$params['CALL_ID'] ??= null;
		$params['RECORD_URL'] ??= null;
		$params['FILENAME'] ??= null;
		$params['FILE_CONTENT'] ??= null;

		if (isset($params['RECORD_URL']))
		{
			$result = Rest\Helper::attachRecordWithUrl(
				$params['CALL_ID'],
				$params['RECORD_URL'],
				(string)$params['FILENAME']
			);
		}
		else if (isset($params['FILENAME']))
		{
			$result = Rest\Helper::attachRecord(
				$params['CALL_ID'],
				$params['FILENAME'],
				$params['FILE_CONTENT'],
				$server
			);
		}
		else
		{
			throw new \Bitrix\Rest\RestException('Required parameters are not set. Request should contain or URL or FILENAME parameter');
		}

		if (!$result->isSuccess())
		{
			throw new \Bitrix\Rest\RestException(implode('; ', $result->getErrorMessages()));
		}

		return $result->getData();
	}

	/**
	 * @param array $params
	 * @param $n
	 * @param CRestServer $server
	 */
	public static function attachTranscription($params, $n, $server)
	{
		$params['CALL_ID'] ??= null;
		$params['COST'] ??= null;
		$params['COST_CURRENCY'] ??= null;
		$params['MESSAGES'] ??= null;

		if (!isset($params['CALL_ID']))
		{
			throw new \Bitrix\Rest\RestException('CALL_ID should be set');
		}
		if (!is_array($params['MESSAGES']))
		{
			throw new \Bitrix\Rest\RestException('MESSAGES should be an array');
		}
		foreach ($params['MESSAGES'] as $k => $messageFields)
		{
			if ($messageFields['SIDE'] !== \Bitrix\Voximplant\Transcript::SIDE_CLIENT && $messageFields['SIDE'] !== \Bitrix\Voximplant\Transcript::SIDE_USER)
			{
				throw new \Bitrix\Rest\RestException('MESSAGES[' . $k . '][SIDE] should be either Client or User');
			}
			if ((int)$messageFields['START_TIME'] < 0)
			{
				throw new \Bitrix\Rest\RestException('MESSAGES[' . $k . '][START_TIME] should be greater or equal to zero');
			}
			if ((int)$messageFields['STOP_TIME'] <= 0)
			{
				throw new \Bitrix\Rest\RestException('MESSAGES[' . $k . '][STOP_TIME] should be greater than zero');
			}
			if ($messageFields['MESSAGE'] == '')
			{
				throw new \Bitrix\Rest\RestException('MESSAGES[' . $k . '][MESSAGE] is empty');
			}
		}

		$callId = $params['CALL_ID'];
		$callFields = \Bitrix\Voximplant\StatisticTable::getRow([
			'filter' => [
				'=CALL_ID' => $callId
			]
		]);

		if (!$callFields)
		{
			throw new \Bitrix\Rest\RestException('Call ' . $callId . ' is not found. Is it finished?');
		}

		$transcript = \Bitrix\Voximplant\Transcript::createWithLines($params['MESSAGES']);
		$transcript->setCallId($callId);
		if ($params['COST'])
		{
			$transcript->setCost((double)$params['COST']);
			$transcript->setCostCurrency((string)$params['COST_CURRENCY']);
		}
		$transcript->save();

		\Bitrix\Voximplant\StatisticTable::update($callFields['ID'], [
			'TRANSCRIPT_ID' => $transcript->getId(),
			'TRANSCRIPT_PENDING' => 'N'
		]);

		return [
			'TRANSCRIPT_ID' => $transcript->getId()
		];
	}

	/**
	 * @param array $params
	 * @param $n
	 * @param CRestServer $server
	 */
	public static function uploadRecord($params, $n, $server)
	{
		$result = Rest\Helper::uploadRecord(
			$params['callId']
		);

		if (!$result->isSuccess())
		{
			throw new \Bitrix\Rest\RestException(implode('; ', $result->getErrorMessages()));
		}

		return $result->getData();
	}

	/**
	 * @param array{
	 * NUMBER: string,
	 * NAME: ?string,
	 * CRM_AUTO_CREATE: ?string
	 * } $params
	 * @param $n
	 * @param CRestServer $server
	 */
	public static function addExternalLine($params, $n, $server)
	{
		if ($server->getAuthType() !== OAuth\Auth::AUTH_TYPE && $server->getAuthType() !== APAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\AuthTypeException();
		}

		$clientId = $server->getClientId();
		$row = \Bitrix\Rest\AppTable::getByClientId($clientId);
		$appId = $row['ID'];

		$newExternalLine = [
			'NAME' => (string)($params['NAME'] ?? ''),
			'NUMBER' => (string)($params['NUMBER'] ?? ''),
			'CRM_AUTO_CREATE' => ($params['CRM_AUTO_CREATE'] ?? 'Y') === 'Y' ? 'Y' : 'N',
		];
		$result = Rest\Helper::addExternalLine($newExternalLine, $appId);
		if (!$result->isSuccess())
		{
			throw new \Bitrix\Rest\RestException(implode('; ', $result->getErrorMessages()));
		}

		$code = $row['CODE'] ? : 'webHook' . $server->getPasswordId();
		if ($code)
		{
			AddEventToStatFile(
				'voximplant',
				'addExternalLine',
				uniqid($code, true),
				$code,
				'type' . $params['TYPE']
			);
		}

		return $result->getData();
	}

	/**
	 * @param array{
	 * NUMBER: string,
	 * NAME: ?string,
	 * CRM_AUTO_CREATE: ?string
	 * } $params
	 * @param $n
	 * @param CRestServer $server
	 */
	public static function updateExternalLine($params, $n, $server)
	{
		if ($server->getAuthType() !== OAuth\Auth::AUTH_TYPE && $server->getAuthType() !== APAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\AuthTypeException();
		}

		$clientId = $server->getClientId();
		$row = \Bitrix\Rest\AppTable::getByClientId($clientId);
		$appId = $row['ID'];

		$updatingFields = [];
		if (isset($params['NAME']))
		{
			$updatingFields['NAME'] = (string)$params['NAME'];
		}
		if (isset($params['CRM_AUTO_CREATE']))
		{
			$updatingFields['CRM_AUTO_CREATE'] = $params['CRM_AUTO_CREATE'] === 'Y' ? 'Y' : 'N';
		}

		if (empty($updatingFields))
		{
			throw new \Bitrix\Rest\RestException('There are no fields to update');
		}

		$result = Rest\Helper::updateExternalLine($params['NUMBER'], $updatingFields, $appId);
		if (!$result->isSuccess())
		{
			throw new \Bitrix\Rest\RestException(implode('; ', $result->getErrorMessages()));
		}

		return $result->getData();
	}

	/**
	 * @param array{NUMBER: string} $params
	 * @param $n
	 * @param CRestServer $server
	 */
	public static function deleteExternalLine($params, $n, $server)
	{
		if ($server->getAuthType() !== OAuth\Auth::AUTH_TYPE && $server->getAuthType() !== APAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\AuthTypeException();
		}

		$clientId = $server->getClientId();
		$row = \Bitrix\Rest\AppTable::getByClientId($clientId);
		$appId = $row['ID'];

		$result = Rest\Helper::deleteExternalLine($params['NUMBER'], $appId);
		if (!$result->isSuccess())
		{
			throw new \Bitrix\Rest\RestException(implode('; ', $result->getErrorMessages()));
		}

		\CVoxImplantUser::clearCache();

		return $result->getData();
	}

	/**
	 * @param array $params
	 * @param $n
	 * @param CRestServer $server
	 */
	public static function getExternalLines($params, $n, $server)
	{
		if ($server->getAuthType() !== OAuth\Auth::AUTH_TYPE && $server->getAuthType() !== APAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\AuthTypeException();
		}

		$clientId = $server->getClientId();
		$row = \Bitrix\Rest\AppTable::getByClientId($clientId);
		$appId = $row['ID'];

		$result = Rest\Helper::getExternalLines($appId);
		if (!$result->isSuccess())
		{
			throw new \Bitrix\Rest\RestException(implode('; ', $result->getErrorMessages()));
		}

		return $result->getData();
	}


	public static function onCallInit($arParams)
	{
		$arResult = $arParams[0];

		if ($arResult instanceof \Bitrix\Main\Event)
		{
			return $arResult->getParameters();
		}

		return $arResult;
	}

	public static function onCallStart($arParams)
	{
		$arResult = $arParams[0];

		return $arResult;
	}

	public static function onCallEnd($arParams)
	{
		$arResult = $arParams[0];
		$arResult['CALL_START_DATE'] = CRestUtil::ConvertDateTime($arResult['CALL_START_DATE']);

		return $arResult;
	}

	public static function filterApp($arParams, $arHandler)
	{
		/** @var \Bitrix\Main\Event $event */
		$event = $arParams[0];
		$eventData = $event->getParameters();

		$eventName = mb_strtoupper($arHandler['EVENT_NAME']);
		$events = [
			mb_strtoupper(Rest\Helper::EVENT_START_EXTERNAL_CALL),
			mb_strtoupper(Rest\Helper::EVENT_START_EXTERNAL_CALLBACK)
		];
		if (in_array($eventName, $events, true))
		{
			if ((int) $arHandler['APP_ID'] > 0)
			{
				$app = \Bitrix\Rest\AppTable::getByClientId((int) $arHandler['APP_ID']);
				if ($app['CODE'])
				{
					$code = $app['CODE'];
				}
				else
				{
					$code = 'app_'.$arHandler['ID'];
				}
			}
			else
			{
				$code = 'event_'.$arHandler['ID'];
			}

			AddEventToStatFile(
				'voximplant',
				'event' . $eventName,
				uniqid($code, true),
				$code
			);
		}

		if ($eventData['APP_ID'] == $arHandler['APP_ID'])
		{
			unset($eventData['APP_ID']);
			return $eventData;
		}

		throw new Exception('Wrong app!');
	}

	protected static function getCurrentUserId()
	{
		global $USER;
		return $USER->getId();
	}
}
?>
