<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Event;
use Bitrix\Main\PhoneNumber\Parser;
use Bitrix\Voximplant as VI;
use Bitrix\Crm\Activity\Provider;
use Bitrix\Crm\Tracking;
use Bitrix\Main\Localization\Loc;

class CVoxImplantCrmHelper
{
	public static $lastError;
	public static function GetCrmEntity($phoneNumber, $country = '')
	{
		if (!CModule::IncludeModule('crm'))
			return false;

		$entityManager = new \Bitrix\Crm\EntityManageFacility();
		$entityManager->getSelector()->appendPhoneCriterion($phoneNumber);

		if($country != '')
		{
			$parsedPhoneNumber = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($phoneNumber, $country);
			if($parsedPhoneNumber->isValid())
			{
				$entityManager->getSelector()->appendPhoneCriterion($parsedPhoneNumber->getNationalNumber());
			}
		}

		$entityManager->getSelector()->search();

		if($entityManager->getPrimaryId() > 0)
		{
			return array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName($entityManager->getPrimaryTypeId()),
				'ENTITY_TYPE' => $entityManager->getPrimaryTypeId(),
				'ENTITY_ID' => $entityManager->getPrimaryId(),
				'ASSIGNED_BY_ID' => $entityManager->getPrimaryAssignedById()
			);
		}
		else
			return false;
	}

	/**
	 * @param VI\Call $call
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getCrmEntities(VI\Call $call)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return [];
		}

		$entityManager = VI\Integration\Crm\EntityManagerRegistry::getWithCall($call);
		if(!$entityManager)
		{
			return [];
		}
		$entities = $entityManager->getSelector()->getEntities();

		$result = [];
		if(!is_array($entities))
		{
			return $result;
		}

		foreach ($entities as $i => $entity)
		{
			$result[] = [
				'ENTITY_TYPE' => CCrmOwnerType::ResolveName($entity['ENTITY_TYPE_ID']),
				'ENTITY_ID' => (int)$entity['ENTITY_ID'],
				'IS_PRIMARY' => ($i === 0) ? 'Y' : 'N',
				'IS_CREATED' => 'N'
			];
		}

		return $result;
	}

	public static function getActivityBindings(VI\Call $call)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return false;
		}
		$entityManager = VI\Integration\Crm\EntityManagerRegistry::getWithCall($call);
		if(!$entityManager)
		{
			return false;
		}

		return $entityManager->getActivityBindings();
	}

	public static function getCrmCard($entityType, $entityId)
	{
		global $APPLICATION;
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		ob_start();
		$APPLICATION->IncludeComponent('bitrix:crm.card.show',
			'',
			array(
				'ENTITY_TYPE' => $entityType,
				'ENTITY_ID' => (int)$entityId,
			)
		);
		return ob_get_clean();
	}

	public static function GetDataForPopup($callId, $phone, $userId = 0)
	{
		if ($phone == '' || !CModule::IncludeModule('crm'))
		{
			return false;
		}

		$dealStatuses = CCrmViewHelper::GetDealStageInfos();

		if ($userId > 0)
		{
			$findParams = array('USER_ID'=> $userId);
		}
		else
		{
			$findParams = array('ENABLE_EXTENDED_MODE'=> false);
		}

		$call = VI\Call::load($callId);
		$arResult = Array('FOUND' => 'N');
		$found = false;
		$entity = '';
		$entityData = Array();
		$entities = Array();

		if($call->getPrimaryEntityType() && $call->getPrimaryEntityId() > 0)
		{
			$entityTypeId = CCrmOwnerType::ResolveID($call->getPrimaryEntityType());
			$entityId = $call->getPrimaryEntityId();

			$entityFields = CCrmSipHelper::getEntityFields($entityTypeId, $entityId, $findParams);

			if(is_array($entityFields))
			{
				$found = true;
				$entity = $call->getPrimaryEntityType();
				$entityData = $entityFields;
				$arResult = self::convertEntityFields($call->getPrimaryEntityType(), $entityData);
				$entities = array($entity);
				$crm = array(
					$entity => array(
						0 => $entityData
					)
				);
			}
		}

		foreach ($entities as $entity)
		{
			if (isset($crm[$entity][0]['ACTIVITIES']))
			{
				foreach ($crm[$entity][0]['ACTIVITIES'] as $activity)
				{
					$overdue = 'N';
					if ($activity['DEADLINE'] <> '' && MakeTimeStamp($activity['DEADLINE']) < time())
					{
						$overdue = 'Y';
					}

					$arResult['ACTIVITIES'][$activity['ID']] = Array(
						'TITLE' => $activity['SUBJECT'],
						'DATE' => $activity['DEADLINE'] <> ''? $activity['DEADLINE']: $activity['END_TIME'],
						'OVERDUE' => $overdue,
						'URL' => $activity['SHOW_URL'],
					);
				}
				if (!empty($arResult['ACTIVITIES']))
				{
					$arResult['ACTIVITIES'] = array_values($arResult['ACTIVITIES']);
				}
			}

			if (isset($crm[$entity][0]['DEALS']))
			{
				foreach ($crm[$entity][0]['DEALS'] as $deal)
				{
					$opportunity = CCrmCurrency::MoneyToString($deal['OPPORTUNITY'], $deal['CURRENCY_ID']);
					if(mb_strpos('&', $opportunity))
					{
						$opportunity = CCrmCurrency::MoneyToString($deal['OPPORTUNITY'], $deal['CURRENCY_ID'], '#').' '.$deal['CURRENCY_ID'];
					}
					$opportunity = str_replace('.00', '', $opportunity);

					$arResult['DEALS'][$deal['ID']] = Array(
						'ID' => $deal['ID'],
						'TITLE' => $deal['TITLE'],
						'STAGE' => $dealStatuses[$deal['STAGE_ID']]['NAME'],
						'STAGE_COLOR' => $dealStatuses[$deal['STAGE_ID']]['COLOR']? $dealStatuses[$deal['STAGE_ID']]['COLOR']: "#5fa0ce",
						'OPPORTUNITY' => $opportunity,
						'URL' => $deal['SHOW_URL'],
					);
				}
				if (!empty($arResult['DEALS']))
				{
					$arResult['DEALS'] = array_values($arResult['DEALS']);
				}
			}
		}

		if(!$found)
		{
			$arResult = array('FOUND' => 'N');
			$userPermissions = CCrmPerms::GetUserPermissions($userId);
			if (CCrmLead::CheckCreatePermission($userPermissions))
			{
				$arResult['LEAD_URL'] = CCrmOwnerType::GetEditUrl(CCrmOwnerType::Lead, 0);
				if($arResult['LEAD_URL'] !== '')
				{
					$arResult['LEAD_URL'] = CCrmUrlUtil::AddUrlParams($arResult['LEAD_URL'], array("phone" => (string)$phone, 'origin_id' => 'VI_'.$callId));
				}
			}
			if (CCrmContact::CheckCreatePermission($userPermissions))
			{
				$arResult['CONTACT_URL'] = CCrmOwnerType::GetEditUrl(CCrmOwnerType::Contact, 0);
				if($arResult['CONTACT_URL'] !== '')
				{
					$arResult['CONTACT_URL'] = CCrmUrlUtil::AddUrlParams($arResult['CONTACT_URL'], array("phone" => (string)$phone, 'origin_id' => 'VI_'.$callId));
				}
			}
		}
		return $arResult;
	}

	/**
	 * Creates lead and activity for the call (if needed). Stores lead and activity id in the call record and the database.
	 * @param VI\Call $call Current call.
	 * @param array $config
	 * @return bool
	 * @throws \Bitrix\Main\SystemException
	 * @see \Bitrix\Voximplant\Model\CallTable.
	 */
	public static function registerCallInCrm(VI\Call $call, $config = null)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
		{
			static::$lastError = 'CRM module is not installed';
			return false;
		}

		if($call->getCallerId() == '')
		{
			static::$lastError = 'Can not register in CRM call without caller id';
			return false;
		}

		$isCallRegisteredInCrmEventSent = false;
		if (!empty($call->getCrmBindings()) && static::isNewCallScenarioEnabled())
		{
			static::sendCallRegisteredInCrmEvent($call);

			$isCallRegisteredInCrmEventSent = true;
		}

		$shouldCreateLead = true;
		if(!is_array($config))
		{
			$config = $call->getConfig();
		}

		if(is_array($config))
		{
			$shouldCreateLead = static::shouldCreateLead($call, $config);
		}
		if(!$shouldCreateLead)
		{
			return true;
		}

		$arFields = static::getLeadFields([
			'USER_ID' => $call->getUserId(),
			'PHONE_NUMBER' => $call->getCallerId(),
			'SEARCH_ID' => $call->getPortalNumber(),
			'EXTERNAL_LINE_ID' => $call->getExternalLineId(),
			'CRM_SOURCE' => $config['CRM_SOURCE'],
			'INCOMING' => $call->getIncoming(),
		]);

		if(!$arFields)
		{
			return false;
		}

		$entityManager = VI\Integration\Crm\EntityManagerRegistry::getWithCall($call);
		if(!$entityManager)
		{
			return false;
		}

		if ($call->getIncoming() == CVoxImplantMain::CALL_OUTGOING && !empty($entityManager->getActivityBindings()))
		{
			$entityManager->setRegisterMode($entityManager::REGISTER_MODE_ONLY_UPDATE);
		}

		$entityManager->setDirection(
			($call->getIncoming() == CVoxImplantMain::CALL_INCOMING || $call->getIncoming() == CVoxImplantMain::CALL_INCOMING_REDIRECT)
			?
			$entityManager::DIRECTION_INCOMING
			:
			$entityManager::DIRECTION_OUTGOING
		);

		$entityManager->setTrace(
			Tracking\Trace::create()->addChannel(
				new Tracking\Channel\Call($call->getPortalNumber())
			)
		);

		$isSuccessful = $entityManager->registerTouch(
			CCrmOwnerType::Lead,
			$arFields,
			true,
			[
				'CURRENT_USER' => $call->getUserId(),
				'DISABLE_USER_FIELD_CHECK' => true
			]
		);

		if(!$isSuccessful)
		{
			if($entityManager->hasErrors())
			{
				$errors = $entityManager->getErrorMessages();
				static::$lastError = end($errors);
				CVoxImplantHistory::WriteToLog(join(';', $entityManager->getErrorMessages()), 'ERROR CREATING LEAD');
			}
			return false;
		}

		if ($entityManager->getRegisteredTypeId() == \CCrmOwnerType::Lead)
		{
			\Bitrix\Crm\Integration\Channel\VoxImplantTracker::getInstance()->registerLead($entityManager->getRegisteredId());

			if(CVoxImplantConfig::GetLeadWorkflowExecution() == CVoxImplantConfig::WORKFLOW_START_IMMEDIATE)
			{
				self::StartLeadWorkflow($entityManager->getRegisteredId());
			}
		}
		else if ($entityManager->getRegisteredTypeId() == \CCrmOwnerType::Deal)
		{
			\Bitrix\Crm\Integration\Channel\VoxImplantTracker::getInstance()->registerDeal($entityManager->getRegisteredId());
		}

		$registeredEntites = [];
		/** @var \Bitrix\Crm\Entity\Identificator\Complex $registeredEntity */
		foreach ($entityManager->getRegisteredEntities() as $registeredEntity)
		{
			$registeredEntites[] = [
				'ENTITY_TYPE' => CCrmOwnerType::ResolveName($registeredEntity->getTypeId()),
				'ENTITY_ID' => $registeredEntity->getId(),
				'IS_CREATED' => 'Y',
				'IS_PRIMARY' => ($registeredEntity->getTypeId() == $entityManager->getPrimaryTypeId() && $registeredEntity->getId() == $entityManager->getPrimaryId()) ? 'Y' : 'N'
			];
		}

		CVoxImplantHistory::WriteToLog($registeredEntites, "Created CRM entities");

		$call->addCrmEntities($registeredEntites);
		if(!empty($registeredEntites))
		{
			$call->updateCrmBindings($entityManager->getActivityBindings());
		}

		if (!$isCallRegisteredInCrmEventSent && static::isNewCallScenarioEnabled())
		{
			static::sendCallRegisteredInCrmEvent($call);
		}

		return true;
	}

	/**
	 * Creates activity and returns id of the created activity.
	 * @param array $callFields Fields of the call, taken from the b_voximplant_statistic table.
	 *	<li>CALL_ID string
	 *  <li>PHONE_NUMBER string
	 *  <li>PORTAL_USER_ID int
	 *  <li>INCOMING
	 *  <li>DATE_CREATE
	 *  <li>PORTAL_NUMBER
	 * @return int|bool Id of the created activity or false in case of error.
	 */
	public static function AddCall(array $callFields, array $additionalParams = array())
	{
		static::$lastError = '';
		if (!CModule::IncludeModule('crm'))
		{
			static::$lastError = 'CRM is not installed';
			return false;
		}
		if ($callFields['PHONE_NUMBER'] == '')
		{
			static::$lastError = 'Can not create activity for call without caller id';
			return false;
		}
		CVoxImplantHistory::WriteToLog($callFields, 'CRM ADD CALL');

		$bindings = $additionalParams['CRM_BINDINGS'];
		if(!is_array($bindings) || empty($bindings))
		{
			$entityManager = new \Bitrix\Crm\EntityManageFacility();
			$entityManager->getSelector()->appendPhoneCriterion($callFields['PHONE_NUMBER']);
			$entityManager->getSelector()->search();
			$bindings = $entityManager->getActivityBindings();
		}

		if(empty($bindings) && isset($callFields['CRM_ENTITY_TYPE']) && isset($callFields['CRM_ENTITY_ID']))
		{
			$entityManager = new \Bitrix\Crm\EntityManageFacility();
			$entityManager->getSelector()->setEntity(
				CCrmOwnerType::ResolveID($callFields['CRM_ENTITY_TYPE']),
				$callFields['CRM_ENTITY_ID']
			);
			$entityManager->getSelector()->search();
			$bindings = $entityManager->getActivityBindings();
		}

		$bindings = array_values($bindings);

		if (empty($bindings))
		{
			static::$lastError = 'Could not find associated crm entity for the current call';
			return false;
		}

		if(
			isset($callFields['INCOMING'])
			&& (
				intval($callFields['INCOMING']) === CVoxImplantMain::CALL_INCOMING
				|| intval($callFields['INCOMING']) === CVoxImplantMain::CALL_INCOMING_REDIRECT
			)
		)
		{
			$direction = CCrmActivityDirection::Incoming;
		}
		else
		{
			$direction = CCrmActivityDirection::Outgoing;
		}

		$activityFields = array(
			'TYPE_ID' =>  CCrmActivityType::Call,
			'PROVIDER_ID' => Provider\Call::ACTIVITY_PROVIDER_ID,
			//'ASSOCIATED_ENTITY_ID' => $params['ID'],
			'CREATED' => $callFields['CALL_START_DATE'],
			'START_TIME' => $callFields['CALL_START_DATE'],
			'END_TIME' => static::getCallEndTime($callFields),
			'DEADLINE' => static::getCallEndTime($callFields),
			'PRIORITY' => CCrmActivityPriority::Medium,
			'LOCATION' => '',
			'NOTIFY_TYPE' => CCrmActivityNotifyType::None,
			'SUBJECT' => self::createActivitySubject($callFields),
			'RESPONSIBLE_ID' => $callFields['PORTAL_USER_ID'],
			'ORIGIN_ID' => 'VI_'.$callFields['CALL_ID'],
			'BINDINGS' => $bindings,
			'SETTINGS' => array(),
			'AUTHOR_ID' => $callFields['PORTAL_USER_ID'],
		);
		if (static::isNewCallScenarioEnabled())
		{
			$activityFields['IS_INCOMING_CHANNEL'] = ($direction === CCrmActivityDirection::Incoming ? 'Y' : 'N');
		}

		if($callFields['INCOMING'] === CVoxImplantMain::CALL_CALLBACK)
		{
			$activityFields['PROVIDER_TYPE_ID'] = Provider\Call::ACTIVITY_PROVIDER_TYPE_CALLBACK;
		}
		else
		{
			$activityFields['PROVIDER_TYPE_ID'] = Provider\Call::ACTIVITY_PROVIDER_TYPE_CALL;
			$activityFields['DIRECTION'] = $direction;
		}

		$activityFields['COMMUNICATIONS'] = array(
			array(
				'ID' => 0,
				'TYPE' => 'PHONE',
				'VALUE' => $callFields['PHONE_NUMBER'],
				'ENTITY_ID' => $callFields['CRM_ENTITY_ID'],
				'ENTITY_TYPE_ID' => CCrmOwnerType::ResolveID($callFields['CRM_ENTITY_TYPE'])
			)
		);

		if (static::isNewCallScenarioEnabled())
		{
			$activityFields['DESCRIPTION'] = $additionalParams['DESCRIPTION'] ?? '';
			$activityFields['SETTINGS']['IS_DESCRIPTION_ONLY'] = true;
		}
		else
		{
			$description = '';
			$params = CVoxImplantHistory::PrepereData($callFields);
			if (isset($additionalParams['DESCRIPTION']) && $additionalParams['DESCRIPTION'] <> '')
			{
				$description = $additionalParams['DESCRIPTION'];
			}
			else if ($additionalParams['WORKTIME_SKIPPED'] == 'Y')
			{
				$description = GetMessage('VI_WORKTIME_SKIPPED_CALL');
			}
			else
			{
				if ($params['CALL_DURATION'] > 0)
				{
					$description = GetMessage('VI_CRM_CALL_DURATION', ['#DURATION#' => $params['CALL_DURATION_TEXT']]);
				}
			}

			if ($callFields['INCOMING'] == CVoxImplantMain::CALL_INCOMING)
			{
				$portalNumbers = array_map(
					function($line)
					{
						return $line["SHORT_NAME"];
					},
					CVoxImplantConfig::GetLinesEx([
						"showRestApps" => true,
						"showInboundOnly" => true
					])
				);
				$portalNumber = isset($portalNumbers[$callFields['PORTAL_NUMBER']])? $portalNumbers[$callFields['PORTAL_NUMBER']]: '';
				if ($portalNumber)
				{
					$description = $description."\n".GetMessage('VI_CRM_CALL_TO_PORTAL_NUMBER', array('#PORTAL_NUMBER#' => $portalNumber));
				}
			}

			$activityFields['DESCRIPTION'] = $description;
		}

		$activityFields['DESCRIPTION_TYPE'] = CCrmContentType::PlainText;

		if (
			$callFields['INCOMING'] == CVoxImplantMain::CALL_INCOMING
			|| $callFields['INCOMING'] == CVoxImplantMain::CALL_CALLBACK
		)
		{
			if (!static::isNewCallScenarioEnabled())
			{
				$activityFields['COMPLETED'] = $callFields['CALL_FAILED_CODE'] != '304';
			}

			if ($callFields['CALL_FAILED_CODE'] === '304')
			{
				$activityFields['SETTINGS']['MISSED_CALL'] = true;
			}
		}
		else
		{
			$activityFields['COMPLETED'] = 'Y';
		}

		if($callFields['CALL_FAILED_CODE'] == '200')
		{
			if($callFields['INCOMING'] == CVoxImplantMain::CALL_INCOMING)
				$activityFields['RESULT_STREAM'] = \Bitrix\Crm\Activity\StatisticsStream::Incoming;
			else if($callFields['INCOMING'] == CVoxImplantMain::CALL_INCOMING_REDIRECT)
				$activityFields['RESULT_STREAM'] = \Bitrix\Crm\Activity\StatisticsStream::Incoming;
			else if($callFields['INCOMING'] == CVoxImplantMain::CALL_OUTGOING)
				$activityFields['RESULT_STREAM'] = \Bitrix\Crm\Activity\StatisticsStream::Outgoing;
			else if($callFields['INCOMING'] == CVoxImplantMain::CALL_CALLBACK)
				$activityFields['RESULT_STREAM'] = \Bitrix\Crm\Activity\StatisticsStream::Reversing;
		}
		else
		{
			$activityFields['RESULT_STREAM'] = \Bitrix\Crm\Activity\StatisticsStream::Missing;
		}

		if ($callFields['CALL_VOTE'] > 3)
		{
			$activityFields['RESULT_MARK'] = \Bitrix\Crm\Activity\StatisticsMark::Positive;
		}
		else if ($callFields['CALL_VOTE'] == 3 && static::isNewCallScenarioEnabled())
		{
			$activityFields['RESULT_MARK'] = \Bitrix\Crm\Activity\StatisticsMark::Neutral;
		}
		else if ($callFields['CALL_VOTE'] > 0)
		{
			$activityFields['RESULT_MARK'] = \Bitrix\Crm\Activity\StatisticsMark::Negative;
		}
		else
		{
			$activityFields['RESULT_MARK'] = \Bitrix\Crm\Activity\StatisticsMark::None;
		}

		$activityId = CCrmActivity::Add(
			$activityFields,
			false,
			true, [
				'REGISTER_SONET_EVENT' => true,
				'PRESERVE_CREATION_TIME' => true
			]
		);
		if($activityId > 0)
		{
			\Bitrix\Crm\Integration\Channel\VoxImplantTracker::getInstance()->registerActivity($activityId, array(
				'ORIGIN_ID' => $callFields['PORTAL_NUMBER']
			));
			CVoxImplantHistory::WriteToLog($activityFields, 'CREATED CRM ACTIVITY '.$activityId);
			return $activityId;
		}
		else
		{
			global $APPLICATION;
			if ($exception = $APPLICATION->GetException())
				static::$lastError = $exception->GetString();

			static::$lastError .= "\nDEBUG: Activity bindings: " . var_export($bindings, true);

			CVoxImplantHistory::WriteToLog(static::$lastError, 'ERROR CAUGHT DURING CREATING CRM ACTIVITY');
			return false;
		}
	}

	/**
	 * Returns true if lead should be created for the call
	 * @param VI\Call $call
	 * @param array $config
	 * @return bool
	 */
	public static function shouldCreateLead(VI\Call $call, $config = null)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		if(is_null($config))
		{
			$config = $call->getConfig();
		}

		if($call->getParentCallId() != '')
		{
			return false;
		}

		if($call->getPrimaryEntityType() == CCrmOwnerType::LeadName && $call->getPrimaryEntityId() > 0)
		{
			return false;
		}
		if(!empty($call->getCreatedCrmEntities()))
		{
			return false;
		}
		if(!$call->isCrmEnabled())
		{
			return false;
		}
		if($config['CRM_CREATE'] !== CVoxImplantConfig::CRM_CREATE_LEAD)
		{
			return false;
		}

		if($config['CRM_CREATE_CALL_TYPE'] === CVoxImplantConfig::CRM_CREATE_CALL_TYPE_ALL)
		{
			return true;
		}
		else if ($config['CRM_CREATE_CALL_TYPE'] === CVoxImplantConfig::CRM_CREATE_CALL_TYPE_INCOMING)
		{
			return $call->getIncoming() == CVoxImplantMain::CALL_INCOMING || $call->getIncoming() == CVoxImplantMain::CALL_INCOMING_REDIRECT;
		}
		else if ($config['CRM_CREATE_CALL_TYPE'] === CVoxImplantConfig::CRM_CREATE_CALL_TYPE_OUTGOING)
		{
			return $call->getIncoming() == CVoxImplantMain::CALL_OUTGOING;
		}
	}

	/**
	 * Returns true if call could be attached to the activity.
	 * @param array $statisticRecord
	 * @param $activityId
	 * @return bool
	 */
	public static function shouldAttachCallToActivity(array $statisticRecord, $activityId)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		$activityId = (int)$activityId;
		if($activityId === 0)
			return false;

		$activityFields = CCrmActivity::GetByID($activityId, false);
		if(!$activityFields)
			return false;

		if(    $activityFields['COMPLETED'] == 'N'
			&& $activityFields['ORIGIN_ID'] == ''
			&& $statisticRecord['INCOMING'] == CVoxImplantMain::CALL_OUTGOING
			&& $statisticRecord['CALL_DURATION'] > 0
			&& $statisticRecord['CALL_FAILED_CODE'] == '200'
		)
			return true;

		return false;
	}

	public static function attachCallToActivity(array $statisticRecord, $activityId)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		$activityId = (int)$activityId;
		if($activityId === 0)
			return false;

		$activityFields = CCrmActivity::GetByID($activityId, false);
		$communications = CCrmActivity::GetCommunications($activityId, false);
		if(!$activityFields)
			return false;

		$updatedFields = array(
			'ORIGIN_ID' => 'VI_' . $statisticRecord['CALL_ID'],
			'COMPLETED' => 'Y',
		);

		$communicationsUpdated = false;
		foreach ($communications as $k => $communication)
		{
			if ($communication['TYPE'] === \Bitrix\Crm\CommunicationType::PHONE_NAME)
			{
				$communications[$k]['VALUE'] = $statisticRecord['PHONE_NUMBER'];
				$communicationsUpdated = true;
				break;
			}
		}

		if($communicationsUpdated)
		{
			$updatedFields['COMMUNICATIONS'] = $communications;
		}

		CCrmActivity::Update($activityFields['ID'], $updatedFields, false);
		return true;
	}

	public static function shouldCompleteActivity(array $statisticRecord)
	{

		if(
			$statisticRecord['INCOMING'] == CVoxImplantMain::CALL_OUTGOING
			&& $statisticRecord['CALL_DURATION'] > 0
			&& $statisticRecord['CALL_FAILED_CODE'] == '200'
		)
			return true;

		return false;
	}


	public static function completeActivity($activityId)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		$activityId = (int)$activityId;
		if($activityId === 0)
			return false;

		$activityFields = CCrmActivity::GetByID($activityId, false);
		if(!$activityFields)
			return false;
		if($activityFields['COMPLETED'] == 'Y')
			return false;

		$updatedFields = array(
			'COMPLETED' => 'Y',
		);

		CCrmActivity::Update($activityFields['ID'], $updatedFields, false);
		return true;
	}

	/**
	 * Returns CALL_ID associated with CRM activity.
	 * @param int $activityId Id of the activity.
	 * @return string|false CALL_ID if found or false otherwise.
	 */
	public static function GetCallIdByActivityId($activityId)
	{
		if (!CModule::IncludeModule('crm'))
			return false;

		$activityId = (int)$activityId;
		if($activityId === 0)
			return false;

		$activity = CCrmActivity::GetByID($activityId, false);
		if(!$activity)
			return false;

		if($activity['PROVIDER_ID'] !== Bitrix\Crm\Activity\Provider\Call::ACTIVITY_PROVIDER_ID)
			return false;

		$callId = $activity['ORIGIN_ID'];

		if(mb_strpos($callId, 'VI_') !== 0)
			return false;

		$callId = mb_substr($callId, 3);

		return $callId;
	}

	public static function AttachRecordToCall($params)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		CVoxImplantHistory::WriteToLog($params, 'CRM ATTACH RECORD TO CALL');
		if ($params['CALL_WEBDAV_ID'] > 0 || $params['CALL_RECORD_ID'] > 0)
		{
			$activityId = CCrmActivity::GetIDByOrigin('VI_'.$params['CALL_ID']);
			if ($activityId)
			{
				$activityFields = CCrmActivity::GetByID($activityId);

				$storageElementIds = unserialize($activityFields['STORAGE_ELEMENT_IDS'], ['allowed_classes' => false]) ?: array();
				$doSave = false;
				if($params['CALL_WEBDAV_ID'] > 0)
				{
					$storageElementIds[] = $params['CALL_WEBDAV_ID'];
					$arFields['STORAGE_TYPE_ID'] = $activityFields['STORAGE_TYPE_ID'] ?: CCrmActivity::GetDefaultStorageTypeID();
					$doSave = true;
				}
				else if($params['CALL_RECORD_ID'] > 0 && empty($storageElementIds))
				{
					$storageElementIds[] = $params['CALL_RECORD_ID'];
					$arFields['STORAGE_TYPE_ID'] = \Bitrix\Crm\Integration\StorageType::File;
					$doSave = true;
				}
				if(!$doSave)
				{
					return false;
				}

				$arFields['STORAGE_ELEMENT_IDS'] = $storageElementIds;
				CCrmActivity::Update($activityId, $arFields, false);
			}
		}

		return true;
	}

	public static function RegisterEntity($params)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		$originId = $params['ORIGIN_ID'];
		if (mb_substr($originId, 0, 3) == 'VI_')
		{
			$callId = mb_substr($originId, 3);
		}
		else
		{
			return false;
		}

		$call = VI\Call::load($callId);
		if ($call)
		{
			$crmData = CVoxImplantCrmHelper::getCrmEntities($call);
			$call->updateCrmEntities($crmData);
			$activityBindings = CVoxImplantCrmHelper::getActivityBindings($call);
			if(is_array($activityBindings))
			{
				$call->updateCrmBindings($activityBindings);
			}

			CVoxImplantHistory::WriteToLog(Array($callId, $call), 'CRM ATTACH INIT CALL');
		}
		else
		{
			$res = VI\StatisticTable::getList(Array(
				'filter' => Array('=CALL_ID' => $callId),
			));
			if ($history = $res->fetch())
			{
				$history['USER_ID'] = $history['PORTAL_USER_ID'];
				$history['DATE_CREATE'] = $history['CALL_START_DATE'];

				CVoxImplantCrmHelper::AddCall($history);
				CVoxImplantCrmHelper::AttachRecordToCall(Array(
					'CALL_ID' => $history['CALL_ID'],
					'CALL_WEBDAV_ID' => $history['CALL_WEBDAV_ID'],
					'CALL_RECORD_ID' => $history['CALL_RECORD_ID'],
				));

				CVoxImplantHistory::WriteToLog(Array($callId), 'CRM ATTACH FULL CALL');
			}
		}

		return true;
	}

	public static function getLeadFields($params)
	{
		static::$lastError = '';
		if (!CModule::IncludeModule('crm'))
		{
			static::$lastError = 'CRM is not installed';
			return false;
		}

		if ($params['PHONE_NUMBER'] == '')
		{
			static::$lastError = 'PHONE_NUMBER is empty';
			return false;
		}

		if (intval($params['USER_ID']) <= 0)
		{
			static::$lastError = 'USER_ID is empty';
			return false;
		}

		$normalizedNumber = CVoxImplantPhone::Normalize($params['PHONE_NUMBER']);
		$result = VI\PhoneTable::getList([
			'select' => ['USER_ID', 'PHONE_MNEMONIC'],
			'filter' => [
				[
					'LOGIC' => 'OR',
					['=PHONE_NUMBER' => $params['PHONE_NUMBER']],
					['=PHONE_NUMBER' => $normalizedNumber],
				],
				'=USER.ACTIVE' => 'Y',
				'=USER.IS_REAL_USER' => 'Y'
			]
		]);
		if ($row = $result->fetch())
		{
			static::$lastError = 'Lead creation is disabled for local users';
			return false;
		}

		switch ($params['INCOMING'])
		{
			case CVoxImplantMain::CALL_INCOMING:
			case CVoxImplantMain::CALL_INCOMING_REDIRECT:
				$title = GetMessage('VI_CRM_CALL_INCOMING');
			break;
			case CVoxImplantMain::CALL_CALLBACK:
				$title = GetMessage('VI_CRM_CALL_CALLBACK');
			break;
			default:
				$title = GetMessage('VI_CRM_CALL_OUTGOING');
			break;
		}

		$arFields = [
			'TITLE' => Parser::getInstance()->parse($params['PHONE_NUMBER'] ?? '')->format() . ' - ' . $title,
			'PHONE_WORK' => $params['PHONE_NUMBER'],
		];

		$statuses = CCrmStatus::GetStatusList("SOURCE");
		if (isset($statuses[$params['CRM_SOURCE']]))
		{
			$arFields['SOURCE_ID'] = $params['CRM_SOURCE'];
		}
		else if (isset($statuses['CALL']))
		{
			$arFields['SOURCE_ID'] = 'CALL';
		}
		else if (isset($statuses['OTHER']))
		{
			$arFields['SOURCE_ID'] = 'OTHER';
		}

		$portalNumbers = CVoxImplantConfig::GetLinesEx([
			'showRestApps' => true,
			'showInboundOnly' => true
		]);
		$portalNumber = isset($portalNumbers[$params['SEARCH_ID']])? $portalNumbers[$params['SEARCH_ID']]['SHORT_NAME']: '';
		$externalLine = (int)$params['EXTERNAL_LINE_ID'] ? VI\Model\ExternalLineTable::getById($params['EXTERNAL_LINE_ID'])->fetchObject() : null;

		if($externalLine)
		{
			$arFields['SOURCE_DESCRIPTION'] = GetMessage('VI_CRM_CALL_TO_PORTAL_NUMBER', array('#PORTAL_NUMBER#' => $externalLine->getNormalizedNumber()));
		}
		else if ($portalNumber)
		{
			$arFields['SOURCE_DESCRIPTION'] = GetMessage('VI_CRM_CALL_TO_PORTAL_NUMBER', array('#PORTAL_NUMBER#' => $portalNumber));
		}

		$arFields['FM'] = CCrmFieldMulti::PrepareFields($arFields);

		return $arFields;
	}

	public static function UpdateLead($id, $params, $userId = 0)
	{
		$userId = (int)$userId;
		if (!isset($params['ASSIGNED_BY_ID']))
			return false;

		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		$update = Array('ASSIGNED_BY_ID' => $params['ASSIGNED_BY_ID']);
		$options = array();
		if($userId > 0)
			$options['CURRENT_USER'] = $userId;

		$CCrmLead = new CCrmLead(false);
		$CCrmLead->Update($id, $update, true, true, $options);

		return true;
	}

	public static function updateCrmEntities(array $crmEntities, $params, $userId = 0)
	{
		$userId = (int)$userId;
		if (!isset($params['ASSIGNED_BY_ID']))
		{
			return false;
		}

		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		$update = ['ASSIGNED_BY_ID' => $params['ASSIGNED_BY_ID']];

		$options = [];
		if($userId > 0)
		{
			$options['CURRENT_USER'] = $userId;
		}

		foreach ($crmEntities as $entity)
		{
			switch ($entity['ENTITY_TYPE'])
			{
				case CCrmOwnerType::LeadName:
					$CCrmLead = new CCrmLead(false);
					$CCrmLead->Update($entity['ENTITY_ID'], $update, true, true, $options);
					break;

				case CCrmOwnerType::ContactName:
					$CCrmContact = new CCrmContact(false);
					$CCrmContact->Update($entity['ENTITY_ID'], $update, true, true, $options);
					break;

				case CCrmOwnerType::CompanyName:
					$CCrmCompany = new CCrmCompany(false);
					$CCrmCompany->Update($entity['ENTITY_ID'], $update, true, true, $options);
					break;

				case CCrmOwnerType::DealName:
					$CCrmDeal = new CCrmDeal(false);
					$CCrmDeal->Update($entity['ENTITY_ID'], $update, true, true, $options);
					break;
			}
		}
	}

	public static function StartLeadWorkflow($leadId)
	{
		if (!CModule::IncludeModule('crm'))
			return;

		\CCrmBizProcHelper::AutoStartWorkflows(
			CCrmOwnerType::Lead,
			$leadId,
			CCrmBizProcEventType::Create,
			$arErrors
		);

		$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Lead, $leadId);
		$starter->setContextModuleId('voximplant')->runOnAdd();
	}

	// Starts call trigger for all associated entities, except for created lead.
	public static function StartCallTrigger(VI\Call $call, $onlyCreated = false)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return;
		}

		if($call->getIncoming() != CVoxImplantMain::CALL_INCOMING && $call->getIncoming() != CVoxImplantMain::CALL_INCOMING_REDIRECT)
		{
			return;
		}

		$crmEntities = $onlyCreated ? $call->getCreatedCrmEntities() : $call->getCrmEntities();
		$bindings = array_map(function($e)
		{
			return [
				'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($e['ENTITY_TYPE']),
				'OWNER_ID' => $e['ENTITY_ID']
			];
		}, $crmEntities);

		CVoxImplantHistory::WriteToLog($bindings, "Starting call trigger for call " . $call->getCallId() . "; bindings:");
		if(!empty($bindings) && is_array($bindings))
		{
			\Bitrix\Crm\Automation\Trigger\CallTrigger::execute($bindings, ['LINE_NUMBER' => $call->getPortalNumber()]);
		}
	}

	public static function StartMissedCallTrigger(VI\Call $call)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return;
		}
		if(!class_exists("\Bitrix\Crm\Automation\Trigger\MissedCallTrigger"))
		{
			return;
		}

		$crmEntities = $call->getCrmEntities();
		$bindings = array_map(function($e)
		{
			return [
				'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($e['ENTITY_TYPE']),
				'OWNER_ID' => $e['ENTITY_ID']
			];
		}, $crmEntities);

		CVoxImplantHistory::WriteToLog($bindings, "Starting missed call trigger for call " . $call->getCallId() . "; bindings:");
		if(!empty($bindings) && is_array($bindings))
		{
			\Bitrix\Crm\Automation\Trigger\MissedCallTrigger::execute($bindings, ['LINE_NUMBER' => $call->getPortalNumber()]);
		}
	}

	public static function findDealsByPhone($phone)
	{
		if ($phone == '')
		{
			return false;
		}

		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		$deals = array();

		$entityTypeIDs = array(CCrmOwnerType::Contact, CCrmOwnerType::Company);
		foreach($entityTypeIDs as $entityTypeID)
		{
			$results = CCrmDeal::FindByCommunication($entityTypeID, 'PHONE', $phone, false, array('ID', 'TITLE', 'STAGE_ID', 'CATEGORY_ID', 'ASSIGNED_BY_ID', 'COMPANY_ID', 'CONTACT_ID', 'DATE_MODIFY'));
			foreach($results as $fields)
			{
				$semanticID = \CCrmDeal::GetSemanticID(
					$fields['STAGE_ID'],
					(isset($fields['CATEGORY_ID']) ? $fields['CATEGORY_ID'] : 0)
				);

				if(Bitrix\Crm\PhaseSemantics::isFinal($semanticID))
				{
					continue;
				}

				$entityID = (int)($entityTypeID === CCrmOwnerType::Company ? $fields['COMPANY_ID'] : $fields['CONTACT_ID']);
				if($entityID <= 0)
				{
					continue;
				}

				$deals[$fields['ID']] = $fields;
			}
		}

		sortByColumn($deals, array('DATE_MODIFY' => array(SORT_DESC)));

		return $deals;
	}

	public static function OnCrmCallbackFormSubmitted($params)
	{
		if($params['STOP_CALLBACK'])
		{
			self::addMissedCall(array(
				'INCOMING' => CVoxImplantMain::CALL_CALLBACK,
				'CONFIG_SEARCH_ID' => $params['CALL_FROM'],
				'PHONE_NUMBER' => $params['CALL_TO'],
				'CRM_ENTITY_TYPE' => $params['CRM_ENTITY_TYPE'],
				'CRM_ENTITY_ID' => $params['CRM_ENTITY_ID']
			));
		}
		else
		{
			$startResult = CVoxImplantOutgoing::startCallBack(
				$params['CALL_FROM'],
				$params['CALL_TO'],
				$params['TEXT'],
				Bitrix\Voximplant\Tts\Language::getDefaultVoice(),
				array(
					'CRM_ENTITY_TYPE' => $params['CRM_ENTITY_TYPE'],
					'CRM_ENTITY_ID' => $params['CRM_ENTITY_ID'],
				)
			);
			if($startResult->isSuccess())
			{
				$callData = $startResult->getData();
				$callId = $callData['CALL_ID'];
				//todo: store associated crm entities
			}
		}
	}

	/**
	 * Creates fake missed call in the statistics table and all the crm stuff.
	 * @param array $params Call record parameters.
	 * @return bool.
	 */
	public static function addMissedCall(array $params)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		$config = CVoxImplantConfig::GetConfigBySearchId($params['CONFIG_SEARCH_ID']);
		if(!$config)
			return false;

		$callId = uniqid('call.', true);
		$entityFields = CCrmSipHelper::getEntityFields(
			CCrmOwnerType::ResolveID($params['CRM_ENTITY_TYPE']),
			$params['CRM_ENTITY_ID']
		);
		if(!is_array($entityFields))
			return false;

		$responsibleUserId = $entityFields['ASSIGNED_BY_ID'];
		$statisticsRecord = array(
			'INCOMING' => $params['INCOMING'] ?: CVoxImplantMain::CALL_INCOMING,
			'PORTAL_USER_ID' => $responsibleUserId,
			'PORTAL_NUMBER' => $params['CONFIG_SEARCH_ID'],
			'PHONE_NUMBER' => $params['PHONE_NUMBER'],
			'CALL_ID' => $callId,
			'CALL_DURATION' => 0,
			'CALL_START_DATE' => new \Bitrix\Main\Type\DateTime(),
			'CALL_FAILED_CODE' => '304',
			'CALL_FAILED_REASON' => 'Missed call',
			'CRM_ENTITY_TYPE' => $params['CRM_ENTITY_TYPE'],
			'CRM_ENTITY_ID' => $params['CRM_ENTITY_ID']
		);

		$insertResult = VI\StatisticTable::add($statisticsRecord);
		if(!$insertResult->isSuccess())
			return false;

		$statisticsRecord['ID'] =  $insertResult->getId();
		if($config['CRM'] == 'Y')
		{
			$activityId = static::AddCall($statisticsRecord);

			if($activityId > 0)
			{
				VI\StatisticTable::update($statisticsRecord['ID'], array(
					'CRM_ACTIVITY_ID' => $activityId
				));
			}

			$chatMessage = \CVoxImplantHistory::GetMessageForChat($statisticsRecord, false);
			if($chatMessage != '')
			{
				\CVoxImplantHistory::SendMessageToChat($statisticsRecord["PORTAL_USER_ID"], $statisticsRecord["PHONE_NUMBER"], $statisticsRecord["INCOMING"], $chatMessage);
			}
		}
	}

	private static function convertEntityFields($entityType, $entityData)
	{
		if(!CModule::IncludeModule('crm'))
			return false;

		$result = array(
			'FOUND' => 'N',
			'CONTACT' => array(),
			'COMPANY' => array(),
			'ACTIVITIES' => array(),
			'DEALS' => array(),
			'RESPONSIBILITY' => array()
		);

		switch ($entityType)
		{
			case CCrmOwnerType::ContactName:
				$result['FOUND'] = 'Y';
				$result['CONTACT'] = array(
					'NAME' => $entityData['FORMATTED_NAME'],
					'POST' => $entityData['POST'],
					'PHOTO' => '',
				);
				if (intval($entityData['PHOTO']) > 0)
				{
					$photo = CFile::ResizeImageGet(
						$entityData['PHOTO'],
						array('width' => 370, 'height' => 370),
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true
					);
					$result['CONTACT']['PHOTO'] = $photo['src'];
				}

				$result['COMPANY'] = $entityData['COMPANY_TITLE'];

				$result['CONTACT_DATA'] = array(
					'ID' => $entityData['ID'],
				);
				break;
			case CCrmOwnerType::LeadName:
				$result['FOUND'] = 'Y';
				$result['CONTACT'] = array(
					'ID' => 0,
					'NAME' => !empty($entityData['FORMATTED_NAME'])? $entityData['FORMATTED_NAME']: $entityData['TITLE'],
					'POST' => $entityData['POST'],
					'PHOTO' => '',
				);

				$result['COMPANY'] = $entityData['COMPANY_TITLE'];

				$result['LEAD_DATA'] = array(
					'ID' => $entityData['ID'],
					'ASSIGNED_BY_ID' => $entityData['ASSIGNED_BY_ID']
				);
				break;
			case CCrmOwnerType::CompanyName:
				$result['FOUND'] = 'Y';
				$result['COMPANY'] = $entityData['TITLE'];
				$result['COMPANY_DATA'] = array(
					'ID' => $entityData['ID'],
				);
				break;
		}

		if ($entityData['ASSIGNED_BY_ID'] > 0)
		{
			if ($user = Bitrix\Main\UserTable::getById($entityData['ASSIGNED_BY_ID'])->fetch())
			{
				$userPhoto = CFile::ResizeImageGet(
					$user['PERSONAL_PHOTO'],
					array('width' => 37, 'height' => 37),
					BX_RESIZE_IMAGE_EXACT,
					false,
					false,
					true
				);

				$result['RESPONSIBILITY'] = array(
					'ID' => $user['ID'],
					'NAME' => CUser::FormatName(CSite::GetNameFormat(false), $user, true, false),
					'PHOTO' => $userPhoto ? $userPhoto['src']: '',
					'POST' => $user['WORK_POSITION'],
				);
			}
		}

		if (isset($entityData['SHOW_URL']))
			$result['SHOW_URL'] = $entityData['SHOW_URL'];

		if (isset($entityData['ACTIVITY_LIST_URL']))
			$result['ACTIVITY_URL'] = $entityData['ACTIVITY_LIST_URL'];

		if (isset($entityData['INVOICE_LIST_URL']))
			$result['INVOICE_URL'] = $entityData['INVOICE_LIST_URL'];

		if (isset($entityData['DEAL_LIST_URL']))
			$result['DEAL_URL'] = $entityData['DEAL_LIST_URL'];

		return $result;
	}

	public static function attachCallToCallList($callListId, array $call)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return;

		$callListId = (int)$callListId;
		$crmEntityId = (int)$call['CRM_ENTITY_ID'];

		if($callListId == 0)
			throw new \Bitrix\Main\ArgumentException('Call List id is empty');

		if($crmEntityId == 0)
			throw new \Bitrix\Main\ArgumentException('Crm entity id is empty');

		$callList = \Bitrix\Crm\CallList\CallList::createWithId($callListId, false, [
			'checkPermissions' => false,
		]);

		$primary = [
			'LIST_ID' => $callListId,
			'ELEMENT_ID' => $crmEntityId
		];
		// to prevent dependency on crm_18.7.50
		if(method_exists($callList, 'getEntityTypeId'))
		{
			$primary['ENTITY_TYPE_ID'] = $callList->getEntityTypeId();
		}

		\Bitrix\Crm\CallList\Internals\CallListItemTable::update($primary, ['CALL_ID' => $call['ID']]);
	}

	/**
	 * Returns id of the crm responsible or false if entity is not found
	 * @param string $entityType String name of the entity type.
	 * @param int $entityId Entity id.
	 * @return bool|int
	 */
	public static function getResponsible($entityType, $entityId)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		$responsibleId = CCrmOwnerType::GetResponsibleID(CCrmOwnerType::ResolveID($entityType), $entityId, false);

		$user = CUser::GetByID($responsibleId)->Fetch();
		return ($user && $user["ACTIVE"] === 'Y') ? $responsibleId : false;
	}

	public static function getResponsibleWithCall(VI\Call $call)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		$crmEntityType = $call->getPrimaryEntityType();
		$crmEntityId = $call->getPrimaryEntityId();

		if(!$crmEntityType || !$crmEntityId)
		{
			return false;
		}

		return (int)\CVoxImplantCrmHelper::getResponsible($crmEntityType, $crmEntityId);
	}

	public static function createActivityBindings(array $params)
	{
		$result = [];
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return $result;

		if($params['CRM_ENTITY_TYPE'] && $params['CRM_ENTITY_ID'])
		{
			$result[] = array(
				'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($params['CRM_ENTITY_TYPE']),
				'OWNER_ID' => $params['CRM_ENTITY_ID']
			);
		}

		if(is_array($params['CRM_BINDINGS']))
		{
			foreach ($params['CRM_BINDINGS'] as $binding)
			{
				$correctBinding = [];
				if($binding['OWNER_TYPE_ID'])
				{
					$correctBinding['OWNER_TYPE_ID'] = $binding['OWNER_TYPE_ID'];
				}
				else if ($binding['OWNER_TYPE_NAME'])
				{
					$correctBinding['OWNER_TYPE_ID'] = CCrmOwnerType::ResolveID($binding['OWNER_TYPE_NAME']);
				}
				else
				{
					continue;
				}
				$correctBinding['OWNER_ID'] = $binding['OWNER_ID'];
				$result[] = $correctBinding;
			}
		}

		return $result;
	}

	public static function createActivitySubject(array $statisticRecord)
	{
		$phoneNumber = $statisticRecord['PHONE_NUMBER'] ?: $statisticRecord['CALLER_ID'];
		$formattedNumber = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($phoneNumber)->format();

		if($statisticRecord['INCOMING'] == CVoxImplantMain::CALL_OUTGOING)
			return Loc::getMessage('VI_CRM_ACTIVITY_SUBJECT_OUTGOING', array('#NUMBER#' => $formattedNumber));
		else if($statisticRecord['INCOMING'] == CVoxImplantMain::CALL_INCOMING || $statisticRecord['INCOMING'] == CVoxImplantMain::CALL_INCOMING_REDIRECT)
			return Loc::getMessage('VI_CRM_ACTIVITY_SUBJECT_INCOMING', array('#NUMBER#' => $formattedNumber));
		else if($statisticRecord['INCOMING'] == CVoxImplantMain::CALL_CALLBACK)
			return Loc::getMessage('VI_CRM_ACTIVITY_SUBJECT_CALLBACK', array('#NUMBER#' => $formattedNumber));
		else
			return Loc::getMessage('VI_CRM_CALL_TITLE');
	}

	public static function getActivityEditUrl($activityId)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		return \CCrmOwnerType::GetEditUrl(\CCrmOwnerType::Activity, $activityId, false);
	}

	public static function createActivityUpdateEvent($activityId)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		$activity = CCrmActivity::GetByID($activityId, false);
		if(!$activity)
			return false;

		CCrmActivity::Update(
			$activityId,
			array(
				'ORIGIN_ID' => $activity['ORIGIN_ID']
			),
			false
		);
	}

	public static function getActivityShowUrl($activityId)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		return \CCrmOwnerType::GetShowUrl(\CCrmOwnerType::Activity, $activityId, false);
	}

	public static function getActivityDescription()
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return '';

		return \CCrmOwnerType::GetDescription(\CCrmOwnerType::Activity);
	}

	/**
	 * Return crm entity caption.
	 * @param string $type CRM entity type name.
	 * @param int $id CRM entity id.
	 * @return mixed|string
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getEntityCaption($type, $id)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return '';

		return CCrmOwnerType::GetCaption(CCrmOwnerType::ResolveID($type), $id, false);
	}

	/**
	 * Returns crm entity type description.
	 * @param string $typeName Name of the crm entity type.
	 * @return string
	 */
	public static function getTypeDescription($typeName)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return '';

		return CCrmOwnerType::GetDescription(CCrmOwnerType::ResolveID($typeName));
	}

	public static function getEntityFields($typeName, $id)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		$fields = static::resolveEntitiesFields(array(
			array(
				'TYPE' => $typeName,
				'ID' => $id
			)
		));

		return isset($fields[$typeName.':'.$id]) ? $fields[$typeName.':'.$id] : false;

	}

	/**
	 * @param array $entities Array with keys TYPE, ID
	 * @return array Array with keys TYPE, ID, DESCRIPTION, NAME, SHOW_URL
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function resolveEntitiesFields(array $entities)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return $entities;

		$contactIds = array();
		$leadIds = array();
		$companyIds = array();

		foreach ($entities as $entity)
		{
			if($entity['TYPE'] === CCrmOwnerType::ContactName)
				$contactIds[] = $entity['ID'];
			else if($entity['TYPE'] === CCrmOwnerType::LeadName)
				$leadIds[] = $entity['ID'];
			else if($entity['TYPE'] === CCrmOwnerType::CompanyName)
				$companyIds[] = $entity['ID'];
		}

		$contactFields = !empty($contactIds) ? static::resolveContactsFields($contactIds) : array();
		$leadFields = !empty($leadIds) ? static::resolveLeadsFields($leadIds) : array();
		$companyFields = !empty($companyIds) ? static::resolveCompaniesFields($companyIds): array();

		$result = array();
		foreach ($entities as $entity)
		{
			$resolvedEntity = $entity;
			if($entity['TYPE'] === CCrmOwnerType::ContactName && isset($contactFields[$entity['ID']]))
			{
				$resolvedEntity['NAME'] = $contactFields[$entity['ID']]['NAME'];
				$resolvedEntity['PHOTO'] = $contactFields[$entity['ID']]['PHOTO'];
			}
			else if($entity['TYPE'] === CCrmOwnerType::CompanyName && isset($companyFields[$entity['ID']]))
			{
				$resolvedEntity['NAME'] = $companyFields[$entity['ID']]['NAME'];
				$resolvedEntity['PHOTO'] = $companyFields[$entity['ID']]['LOGO'];
			}
			else if($entity['TYPE'] === CCrmOwnerType::LeadName && isset($leadFields[$entity['ID']]))
			{
				$resolvedEntity['NAME'] = $leadFields[$entity['ID']]['NAME'];
				$resolvedEntity['PHOTO'] = null;
			}

			$resolvedEntity['DESCRIPTION'] = CCrmOwnerType::GetDescription(CCrmOwnerType::ResolveID($entity['TYPE']));
			$resolvedEntity['SHOW_URL'] = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::ResolveID($entity['TYPE']), $entity['ID'], false);

			$key = $entity['TYPE'] . ':' . $entity['ID'];
			$result[$key] = $resolvedEntity;

		}
		return $result;
	}

	public static function resolveContactsFields(array $ids)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return array();

		$filter = array(
			'=ID' => $ids,
			'CHECK_PERMISSIONS' => 'N'
		);
		$cursor = \CCrmContact::getListEx(array(), $filter, false, false, array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'POST', 'PHOTO'));

		$result = array();
		while ($row = $cursor->Fetch())
		{
			$formattedName = \CCrmContact::PrepareFormattedName(array(
				'HONORIFIC' => isset($row['HONORIFIC']) ? $row['HONORIFIC'] : '',
				'NAME' => isset($row['NAME']) ? $row['NAME'] : '',
				'SECOND_NAME' => isset($row['SECOND_NAME']) ? $row['SECOND_NAME'] : '',
				'LAST_NAME' => isset($row['LAST_NAME']) ? $row['LAST_NAME'] : ''
			));

			$result[$row['ID']] = array(
				'NAME' => $formattedName,
				'PHOTO' => $row['PHOTO'],
			);
		}

		return $result;
	}

	public static function resolveLeadsFields(array $ids)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return array();

		$filter = array(
			'=ID' => $ids,
			'CHECK_PERMISSIONS' => 'N'
		);

		$cursor = \CCrmLead::getListEx(array(), $filter, false, false, array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'POST', 'TITLE'));

		$result = array();
		while ($row = $cursor->Fetch())
		{
			if($row['NAME'] <> '' || $row['SECOND_NAME'] <> '' || $row['LAST_NAME'] <> '')
				$formattedName = \CCrmLead::PrepareFormattedName(
					array(
						'HONORIFIC' => isset($row['HONORIFIC']) ? $row['HONORIFIC'] : '',
						'NAME' => isset($row['NAME']) ? $row['NAME'] : '',
						'SECOND_NAME' => isset($row['SECOND_NAME']) ? $row['SECOND_NAME'] : '',
						'LAST_NAME' => isset($row['LAST_NAME']) ? $row['LAST_NAME'] : ''
					)
				);
			else
				$formattedName = $row['TITLE'];

			$result[$row['ID']] = array(
				'NAME' => $formattedName
			);
		}
		return $result;
	}

	public static function resolveCompaniesFields(array $ids)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return array();

		$filter = array(
			'=ID' => $ids,
			'CHECK_PERMISSIONS' => 'N'
		);

		$cursor = \CCrmCompany::getListEx(array(), $filter, false, false, array('ID', 'TITLE', 'ADDRESS', 'COMMENTS', 'LOGO'));

		$result = array();
		while ($row = $cursor->Fetch())
		{
			$result[$row['ID']] = array(
				'NAME' => $row['TITLE'],
				'LOGO' => $row['LOGO'],
			);
		}

		return $result;
	}

	public static function getCallEndTime( array $statisticRecord)
	{
		$startTime = $statisticRecord['CALL_START_DATE'];
		if(!$startTime instanceof \Bitrix\Main\Type\DateTime)
			return null;

		$endTime = clone $startTime;

		$duration = (int)$statisticRecord['CALL_DURATION'];
		if($duration === 0)
			return $statisticRecord['CALL_START_DATE'];

		$endTime->add($duration . ' seconds');
		return $endTime;
	}

	public static function isLeadEnabled()
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return false;
		}

		return \Bitrix\Crm\Settings\LeadSettings::isEnabled();
	}

	public static function resolveBindingNames($bindings)
	{
		if(!is_array($bindings))
		{
			return $bindings;
		}
		if(!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return $bindings;
		}

		return array_map(
			function($binding)
			{
				return ($binding['OWNER_TYPE_ID'] && $binding['OWNER_ID']) ?
					[
						'ENTITY_TYPE' => CCrmOwnerType::ResolveName($binding['OWNER_TYPE_ID']),
						'ENTITY_ID' => $binding['OWNER_ID']
					]
					:
					$binding;
			},
			$bindings
		);
	}

	/**
	 *
	 * Temporary method for independent operation of modules voximplant and crm
	 *
	 * @return bool
	 */
	public static function isNewCallScenarioEnabled(): bool
	{
		if(
			\Bitrix\Main\Loader::includeModule('crm')
			&& method_exists('\Bitrix\Crm\Settings\Crm', 'isUniversalActivityScenarioEnabled')
		)
		{
			return \Bitrix\Crm\Settings\Crm::isUniversalActivityScenarioEnabled();
		}

		return false;
	}

	private static function sendCallRegisteredInCrmEvent(VI\Call $call): void
	{
		$crmEventData = [
			'CALL_ID' => $call->getCallId(),
			'CALLER_ID' => $call->getCallerId(),
			'CRM_DATA' => $call->getCrmBindings(),
			'USER_ID' => $call->getUserId(),
			'INCOMING' => $call->getIncoming(),
		];

		(new Event('voximplant', 'onCallRegisteredInCrm', $crmEventData))->send();
	}
}
