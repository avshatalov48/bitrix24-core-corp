<?php

use Bitrix\Crm\Activity;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Loader::includeModule('crm');

class CrmActivityPlannerComponent extends \Bitrix\Crm\Component\Base
{
	/** @var array */
	private $activityData;

	protected function getActivityId()
	{
		return isset($this->arParams['ELEMENT_ID']) ? (int) $this->arParams['ELEMENT_ID'] : 0;
	}

	protected function getCalendarEventId()
	{
		return isset($this->arParams['CALENDAR_EVENT_ID']) ? (int) $this->arParams['CALENDAR_EVENT_ID'] : 0;
	}

	protected function getOwnerTypeId()
	{
		if (!empty($this->arParams['OWNER_TYPE_ID']))
			return (int) $this->arParams['OWNER_TYPE_ID'];
		if (isset($this->arParams['OWNER_TYPE']))
			return CCrmOwnerType::ResolveID($this->arParams['OWNER_TYPE']);

		return 0;
	}

	protected function getOwnerId()
	{
		return isset($this->arParams['OWNER_ID']) ? (int) $this->arParams['OWNER_ID'] : 0;
	}

	protected function getActivityType()
	{
		return isset($this->arParams['TYPE_ID']) ? (int) $this->arParams['TYPE_ID'] : 0;
	}

	protected function getProviderId()
	{
		return isset($this->arParams['PROVIDER_ID']) ? (string) $this->arParams['PROVIDER_ID'] : '';
	}

	protected function getProviderTypeId()
	{
		return isset($this->arParams['PROVIDER_TYPE_ID']) ? (string) $this->arParams['PROVIDER_TYPE_ID'] : '';
	}

	protected function getAction()
	{
		return isset($this->arParams['ACTION'])? mb_strtoupper((string)$this->arParams['ACTION']) : '';
	}

	protected function getPlannerId()
	{
		return isset($this->arParams['PLANNER_ID']) ? (string) $this->arParams['PLANNER_ID'] : '';
	}

	protected function getFromActivityId()
	{
		return isset($this->arParams['FROM_ACTIVITY_ID']) ? (int) $this->arParams['FROM_ACTIVITY_ID'] : 0;
	}

	protected function getAssociatedEntityId()
	{
		return isset($this->arParams['ASSOCIATED_ENTITY_ID']) ? (int) $this->arParams['ASSOCIATED_ENTITY_ID'] : 0;
	}

	protected function getStorageTypeId()
	{
		return isset($this->arParams['STORAGE_TYPE_ID']) ? (int) $this->arParams['STORAGE_TYPE_ID'] : 0;
	}

	protected function getStorageElementIds()
	{
		return (isset($this->arParams['STORAGE_ELEMENT_IDS']) && is_array($this->arParams['STORAGE_ELEMENT_IDS'])) ? $this->arParams['STORAGE_ELEMENT_IDS'] : [];
	}

	protected function getActivityData(): array
	{
		if (!is_null($this->activityData))
		{
			return $this->activityData;
		}

		$activity = false;
		if ($this->getActivityId() > 0)
		{
			$activity = CCrmActivity::GetByID($this->getActivityId(), false);
		}
		elseif ($this->getCalendarEventId() > 0)
		{
			$activity = CCrmActivity::GetByCalendarEventId($this->getCalendarEventId(), false);
		}
		$activity = is_array($activity) ? $activity : [];

		$provider = $activity ? CCrmActivity::GetActivityProvider($activity) : null;

		if (empty($activity['PROVIDER_ID']) && $provider)
		{
			$activity['PROVIDER_ID'] = $provider::getId();
		}
		if (empty($activity['PROVIDER_TYPE_ID']) && $provider)
		{
			$activity['PROVIDER_TYPE_ID'] = $provider::getTypeId($activity);
		}

		$this->activityData = $activity;

		return $this->activityData;
	}

	protected function getActivityAdditionalData($activityId, &$activity, $provider = null)
	{
		//bindings
		if (empty($activity['BINDINGS']))
			$activity['BINDINGS'] = $activityId ? CCrmActivity::getBindings($activityId) : array();

		$commType = $provider ? $provider::getCommunicationType(isset($activity['PROVIDER_TYPE_ID']) ? $activity['PROVIDER_TYPE_ID'] : null) : '';
		$activity['__communications'] = array();
		if ($activity['OWNER_TYPE_ID'] > 0 && $activity['OWNER_ID'] > 0)
		{
			$activity['__communications'] = $this->getCrmEntityCommunications(
				$activity['OWNER_TYPE_ID'], $activity['OWNER_ID'], $commType
			);
		}

		//communications
		if (empty($activity['COMMUNICATIONS']))
		{
			$activity['COMMUNICATIONS'] = $activityId ? CCrmActivity::getCommunications($activityId) : array();

			/** @var Activity\Provider\Base $provider */
			if (!$activityId && $provider)
				$activity['COMMUNICATIONS'] = array_slice($activity['__communications'], 0, 1);
		}

		if (count($activity['__communications']) < 10 && !empty($commType))
		{
			$known = array();
			foreach ($activity['__communications'] as $item)
			{
				$known[] = sprintf(
					'CRM%s%u:%s',
					$item['ENTITY_TYPE'], $item['ENTITY_ID'],
					hash('crc32b', $item['TYPE'].':'.$item['VALUE'])
				);
			}

			$createdSince = new Bitrix\Main\Type\Datetime();

			$params = array(
				'runtime' => array(
					new \Bitrix\Main\Entity\ExpressionField(
						'LAST_CREATED', 'MAX(%s)', 'CREATED'
					),
					new \Bitrix\Main\Entity\ExpressionField(
						'ENTITY_TYPE',
						sprintf(
							"CASE WHEN %%1\$s = %u THEN '%s' WHEN %%1\$s = %u THEN '%s' WHEN %%1\$s = %u THEN '%s' END",
							\CCrmOwnerType::Lead, \CCrmOwnerType::LeadName,
							\CCrmOwnerType::Contact, \CCrmOwnerType::ContactName,
							\CCrmOwnerType::Company, \CCrmOwnerType::CompanyName
						),
						'OWNER_TYPE_ID'
					),
					new \Bitrix\Main\Entity\ReferenceField(
						'OWNER_MULTI',
						'Bitrix\Crm\FieldMulti',
						array(
							'=this.OWNER_ID' => 'ref.ELEMENT_ID',
							'=this.ENTITY_TYPE' => 'ref.ENTITY_ID'
						)
					)
				),
				'select' => array(
					'ENTITY_ID'      => 'OWNER_ID',
					'ENTITY_TYPE_ID' => 'OWNER_TYPE_ID',
					'VALUE' => 'OWNER_MULTI.VALUE',
					'VALUE_TYPE' => 'OWNER_MULTI.VALUE_TYPE',
				),
				'filter' => array(
					'=RESPONSIBLE_ID' => \CCrmSecurityHelper::getCurrentUserId(),
					'>CREATED' => $createdSince,
					'@OWNER_TYPE_ID' => array(\CCrmOwnerType::Lead, \CCrmOwnerType::Contact, \CCrmOwnerType::Company),
					'=OWNER_MULTI.TYPE_ID' => mb_strtoupper($commType),
				),
				'group' => array(
					'OWNER_TYPE_ID', 'OWNER_ID', 'OWNER_MULTI.VALUE_TYPE', 'OWNER_MULTI.VALUE',
				),
				'order' => array(
					'LAST_CREATED' => 'DESC',
				),
				'limit' => 20,
			);

			$t1 = microtime(true);
			$intervals = array(7, 30);
			while (count($activity['__communications']) < 10 && ($n = array_shift($intervals)) > 0)
			{
				$createdSince->add(sprintf('-%u days', $n));

				$res = \Bitrix\Crm\ActivityTable::getList($params);

				while (($item = $res->fetch()) && count($activity['__communications']) < 10)
				{
					$item['ENTITY_TYPE'] = \CCrmOwnerType::resolveName($item['ENTITY_TYPE_ID']);
					$item['TYPE'] = mb_strtoupper($commType);

					$id = sprintf(
						'CRM%s%u:%s',
						$item['ENTITY_TYPE'], $item['ENTITY_ID'],
						hash('crc32b', $item['TYPE'] . ':' . $item['VALUE'])
					);
					if (in_array($id, $known))
					{
						continue;
					}

					$activity['__communications'][] = $item;
					$known[] = $id;
				}

				if (microtime(true) - $t1 > 1)
				{
					break;
				}

				$createdSince->add(sprintf('%u days', $n));
			}
		}

		//attaches
		$activity['STORAGE_TYPE_ID'] = isset($activity['STORAGE_TYPE_ID']) ? (int) $activity['STORAGE_TYPE_ID'] : Integration\StorageType::Undefined;
		if(!Integration\StorageType::isDefined($activity['STORAGE_TYPE_ID']))
		{
			$activity['STORAGE_TYPE_ID'] = CCrmActivity::GetDefaultStorageTypeID();
		}

		$activity['FILES'] = $activity['WEBDAV_ELEMENTS'] = $activity['DISK_FILES'] = array();

		CCrmActivity::PrepareStorageElementIDs($activity);
		CCrmActivity::PrepareStorageElementInfo($activity);

		//other
		if(isset($activity['DEADLINE']) && CCrmDateTimeHelper::IsMaxDatabaseDate($activity['DEADLINE']))
		{
			$activity['DEADLINE'] = '';
		}
	}

	protected function isSlider()
	{
		return ($_REQUEST['IFRAME'] == 'Y' && $_REQUEST['IFRAME_TYPE'] == 'SIDE_SLIDER');
	}

	public function executeComponent()
	{
		global $APPLICATION;

		if (!Main\Loader::includeModule('crm'))
		{
			ShowError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));
			return;
		}

		if (!Main\Loader::includeModule('calendar'))
		{
			ShowError(Loc::getMessage('CALENDAR_MODULE_NOT_INSTALLED'));
			return;
		}

		\CModule::includeModule('socialnetwork');
		\CJSCore::init(array('socnetlogdest'));

		$this->arParams['PATH_TO_DEAL_DETAILS'] = \CrmCheckPath(
			'PATH_TO_DEAL_DETAILS',
			$this->arParams['PATH_TO_DEAL_DETAILS'] ?? '',
			$APPLICATION->getCurPage().'?deal_id=#deal_id#&details'
		);

		$this->arResult['ENTITY_TYPE_ID'] = \CCrmOwnerType::Activity;

		$activityTypeId = (int)($this->getActivityData()['TYPE_ID'] ?? 0);
		$providerTypeId = (string)($this->getActivityData()['PROVIDER_ID'] ?? '');
		$restriction = RestrictionManager::getActivityRestriction($activityTypeId, $providerTypeId);
		if (!$restriction->hasPermission())
		{
			$this->arResult['RESTRICTIONS_SCRIPT'] = $restriction->prepareInfoHelperScript();
			$this->initComponentTemplate('restrictions');

			return;
		}

		$action = $this->getAction();

		switch ($action)
		{
			case 'EDIT':
				$this->executeEditAction();
				break;
			default:
				$this->executeViewAction();
				break;
		}
	}

	protected function executeEditAction()
	{
		$activityId = $this->getActivityId();
		$calendarEventId = $this->getCalendarEventId();
		$isNew = false;
		$activity = $error = null;

		if ($activityId > 0)
			$activity = CCrmActivity::GetByID($activityId, false);
		elseif ($calendarEventId > 0)
			$activity = CCrmActivity::GetByCalendarEventId($calendarEventId, false);
		else
		{
			$isNew = true;
			$activity = array(
				'OWNER_ID' => $this->getOwnerId(),
				'OWNER_TYPE_ID' => $this->getOwnerTypeId(),
				'RESPONSIBLE_ID' => CCrmSecurityHelper::GetCurrentUserID(),
				'TYPE_ID' => $this->getActivityType(),
				'PROVIDER_ID' => $this->getProviderId(),
				'PROVIDER_TYPE_ID' => $this->getProviderTypeId(),
				'STORAGE_TYPE_ID' => $this->getStorageTypeId(),
				'STORAGE_ELEMENT_IDS' => $this->getStorageElementIds(),
			);

			if($this->getAssociatedEntityId() > 0)
				$activity['ASSOCIATED_ENTITY_ID'] = $this->getAssociatedEntityId();
		}

		if (empty($activity))
			$error = Loc::getMessage('CRM_ACTIVITY_PLANNER_NO_ACTIVITY');

		$provider = $activity ? CCrmActivity::GetActivityProvider($activity) : null;

		if (!$error && !$provider)
			$error = Loc::getMessage('CRM_ACTIVITY_PLANNER_NO_PROVIDER');

		if (!$error && !$isNew && !$provider::checkUpdatePermission($activity, \CCrmSecurityHelper::getCurrentUserId()))
			$error = Loc::getMessage('CRM_ACTIVITY_PLANNER_NO_UPDATE_PERMISSION');

		if ($error)
		{
			$this->arResult['ERROR'] = $error;
			$this->includeComponentTemplate('error');
			return;
		}

		//synchronize provider identification
		$activity['PROVIDER_ID'] = $provider::getId();
		$activity['PROVIDER_TYPE_ID'] = $provider::getTypeId($activity);

		if ($isNew && !empty($this->arParams['COMMUNICATIONS']))
		{
			$communicationType = $provider::getCommunicationType($activity['PROVIDER_TYPE_ID']);

			$activity['COMMUNICATIONS'] = [];
			foreach ((array) $this->arParams['COMMUNICATIONS'] as $item)
			{
				$item['OWNER_TYPE_ID'] = $item['OWNER_TYPE_ID'] ?: \CCrmOwnerType::resolveId($item['OWNER_TYPE']);
				if (!(\CCrmOwnerType::isDefined($item['OWNER_TYPE_ID']) && $item['OWNER_ID'] > 0))
					continue;

				$entityCommunications = [];
				if ($communicationType == $item['TYPE'] && !empty($item['VALUE']))
				{
					// @TODO: perf
					$entityCommunications = $this->getCrmEntityCommunications(
						$item['OWNER_TYPE_ID'],
						$item['OWNER_ID'],
						$communicationType
					);
					foreach ($entityCommunications as $subItem)
					{
						if ($item['VALUE'] == $subItem['VALUE'])
						{
							$activity['COMMUNICATIONS'][] = $subItem;
							continue 2;
						}
					}
				}

				$activity['COMMUNICATIONS'] = array_merge(
					$activity['COMMUNICATIONS'],
					$entityCommunications
				);
			}

			if (!($activity['OWNER_TYPE_ID'] > 0 && $activity['OWNER_ID'] > 0) && !empty($activity['COMMUNICATIONS']))
			{
				$preferredOwnerTypeId = $activity['OWNER_TYPE_ID'];

				$activity['OWNER_TYPE_ID'] = 0;
				$activity['OWNER_ID'] = 0;

				foreach ($activity['COMMUNICATIONS'] as $item)
				{
					if (!($activity['OWNER_ID'] > 0))
					{
						$activity['OWNER_TYPE_ID'] = $item['ENTITY_TYPE_ID'];
						$activity['OWNER_ID']      = $item['ENTITY_ID'];
					}

					if (!($preferredOwnerTypeId > 0))
						break;

					if ($item['ENTITY_TYPE_ID'] == $preferredOwnerTypeId)
					{
						$activity['OWNER_TYPE_ID'] = $item['ENTITY_TYPE_ID'];
						$activity['OWNER_ID']      = $item['ENTITY_ID'];

						break;
					}
				}
			}
		}

		$this->arResult['DURATION_VALUE'] = 1;
		$this->arResult['DURATION_TYPE'] = CCrmActivityNotifyType::Hour;

		if ($isNew)
		{
			$provider::fillDefaultActivityFields($activity);

			$defaults = \CUserOptions::GetOption('crm.activity.planner', 'defaults', array());
			if (isset($defaults['notify']) && isset($defaults['notify'][$provider::getId()]))
			{
				$activity['NOTIFY_VALUE'] = (int)$defaults['notify'][$provider::getId()]['value'];
				$activity['NOTIFY_TYPE'] = (int)$defaults['notify'][$provider::getId()]['type'];
			}

			if (isset($defaults['duration']) && isset($defaults['duration'][$provider::getId()]))
			{
				$this->arResult['DURATION_VALUE'] = min(999, (int)$defaults['duration'][$provider::getId()]['value']);
				$this->arResult['DURATION_TYPE'] = (int)$defaults['duration'][$provider::getId()]['type'];
			}

			if (!empty($this->arParams['MESSAGE_TYPE']))
				$activity['__message_type'] = $this->arParams['MESSAGE_TYPE'];

			if (!empty($this->arParams['OWNER_PSID']))
				$activity['__owner_psid'] = $this->arParams['OWNER_PSID'];

			$fromId = $this->getFromActivityId();
			if ($fromId > 0)
			{
				$fromActivity = CCrmActivity::GetByID($fromId);
				if ($fromActivity)
				{
					$fromActivity['COMMUNICATIONS'] = \CCrmActivity::getCommunications($fromId);
					$fromActivity['BINDINGS'] = \CCrmActivity::getBindings($fromId);

					$activity['SUBJECT'] = $fromActivity['SUBJECT'];
					$activity['PRIORITY'] = $fromActivity['PRIORITY'];
					if (in_array($activity['TYPE_ID'], array(CCrmActivityType::Call, CCrmActivityType::Meeting, CCrmActivityType::Email)))
					{
						if ((int)$fromActivity['TYPE_ID'] === CCrmActivityType::Email)
						{
							Activity\Provider\Email::uncompressActivity($fromActivity);
						}

						$activity['DESCRIPTION'] = $fromActivity['DESCRIPTION'];
						$activity['DESCRIPTION_TYPE'] = $fromActivity['DESCRIPTION_TYPE'];
						$activity['DESCRIPTION_HTML'] = $this->makeDescriptionHtml(
							$fromActivity['DESCRIPTION'],
							$fromActivity['DESCRIPTION_TYPE']
						);
					}
					if ($activity['TYPE_ID'] == CCrmActivityType::Meeting)
						$activity['LOCATION'] = $fromActivity['LOCATION'];
					if ($activity['TYPE_ID'] == CCrmActivityType::Email)
						$activity['__parent'] = $fromActivity;

					if (is_array($fromActivity['COMMUNICATIONS']))
					{
						$activity['COMMUNICATIONS'] = array();
						$commType = $provider::getCommunicationType($activity['PROVIDER_TYPE_ID']);

						foreach ($fromActivity['COMMUNICATIONS'] as $comm)
						{
							if ($comm['TYPE'] === $commType)
								$activity['COMMUNICATIONS'][] = $comm;
						}
					}
				}
			}
		}
		$this->getActivityAdditionalData($activityId, $activity, $provider);

		$activity['SUBJECT'] ??= $this->arParams['SUBJECT'];
		$activity['DESCRIPTION_HTML'] ??= $this->arParams['BODY'];

		$this->arResult['ACTIVITY'] = $activity;

		$this->arResult['PROVIDER'] = $provider;
		$this->arResult['DESTINATION_ENTITIES'] = $this->getDestinationEntities($activity);
		$this->arResult['COMMUNICATIONS_DATA'] = $this->getCommunicationsData($activity['COMMUNICATIONS']);
		$this->arResult['PLANNER_ID'] = $this->getPlannerId();

		$options = \CUserOptions::GetOption('crm.activity.planner', 'edit', array());
		$this->arResult['DETAIL_MODE'] = (isset($options['view_mode']) && $options['view_mode'] === 'detail');
		$this->arResult['ADDITIONAL_MODE'] = (isset($options['additional_mode']) && $options['additional_mode'] === 'open');
		$this->arResult['TYPE_ICON'] = $this->getTypeIcon($activity);

		$template = 'edit';
		if ($this->isSlider())
		{
			$template .= '_slider';
		}

		$this->includeComponentTemplate($template);
	}

	protected function executeViewAction()
	{
		$userId = CCrmSecurityHelper::GetCurrentUserID();

		$activityId = $this->getActivityId();
		$calendarEventId = $this->getCalendarEventId();

		$activity = $error = null;

		if ($activityId > 0)
			$activity = CCrmActivity::getList(array(), array('ID' => $activityId), false, false, array('*', 'UF_*'))->fetch();
		elseif ($calendarEventId > 0)
			$activity = CCrmActivity::GetByCalendarEventId($calendarEventId, false);

		if (empty($activity))
			$error = Loc::getMessage('CRM_ACTIVITY_PLANNER_NO_ACTIVITY');

		$provider = $activity ? CCrmActivity::GetActivityProvider($activity) : null;

		if (!$error && !$provider)
		{
			$error = Loc::getMessage('CRM_ACTIVITY_PLANNER_NO_PROVIDER');
		}

		if (empty($activity['PROVIDER_ID']) && $provider)
		{
			$activity['PROVIDER_ID'] = $provider::getId();
		}
		if (empty($activity['PROVIDER_TYPE_ID']) && $provider)
		{
			$activity['PROVIDER_TYPE_ID'] = $provider::getTypeId($activity);
		}

		if (!$error && !$provider::checkReadPermission($activity, \CCrmSecurityHelper::getCurrentUserId()))
		{
			$error = Loc::getMessage('CRM_ACTIVITY_PLANNER_NO_READ_PERMISSION');
		}

		if ($error)
		{
			$this->arResult['ERROR'] = $error;
			$this->includeComponentTemplate('error');
			return;
		}

		$this->getActivityAdditionalData($activityId, $activity, $provider);

		if ($activity['COMPLETED'] === 'N' && $provider::canCompleteOnView($activity['PROVIDER_TYPE_ID']))
		{
			$completeResult = \CCrmActivity::Complete($activity['ID']);
			if ($completeResult)
				$activity['COMPLETED'] = 'Y';
		}

		if ((int)$activity['TYPE_ID'] === CCrmActivityType::Email)
		{
			Activity\Provider\Email::uncompressActivity($activity);
		}

		$activity['DESCRIPTION_HTML'] = $this->makeDescriptionHtml(
			$activity['DESCRIPTION'],
			$activity['DESCRIPTION_TYPE']
		);
		$activity['COMMUNICATIONS'] = $this->prepareCommunicationsForView($activity['COMMUNICATIONS']);

		$this->arResult['COMMUNICATIONS'] = $activity['COMMUNICATIONS'];
		$this->arResult['PROVIDER'] = $provider;
		$this->arResult['ACTIVITY'] = $activity;
		$this->arResult['TYPE_ICON'] = $this->getTypeIcon($activity);
		$this->arResult['FILES_LIST'] = $this->prepareFilesForView($activity);

		$this->arResult['RESPONSIBLE_NAME'] = CCrmViewHelper::GetFormattedUserName($activity['RESPONSIBLE_ID'], $this->arParams['NAME_TEMPLATE']);
		$this->arResult['RESPONSIBLE_URL'] = CComponentEngine::MakePathFromTemplate(
				'/company/personal/user/#user_id#/',
				array('user_id' => $activity['RESPONSIBLE_ID'])
			);

		$this->arResult['DOC_BINDINGS'] = $this->prepareDocsBindingsForView($activity['BINDINGS']);

		$ownerID = (int)$activity['OWNER_ID'];
		$ownerTypeID = (int)$activity['OWNER_TYPE_ID'];

		if(!$ownerID && !$ownerTypeID || \CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerID))
		{
			if ($provider::isTypeEditable($activity['PROVIDER_TYPE_ID'], $activity['DIRECTION']))
			{
				$this->arResult['IS_EDITABLE'] = true;
			}
		}

		$template = 'view';
		if ($this->isSlider())
		{
			$template .= '_slider';
			$this->arResult['IS_SLIDER_ENABLED'] = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled();
		}

		if(isset($this->arResult['ACTIVITY']['SUBJECT'])) $this->arResult['ACTIVITY']['SUBJECT'] = \Bitrix\Main\Text\Emoji::decode($this->arResult['ACTIVITY']['SUBJECT']);

		$this->includeComponentTemplate($template);
	}

	private function prepareDocsBindingsForView($bindings)
	{
		$siteNameFormat = \CSite::getNameFormat();
		$docsBindings = array();

		$bindingsIds = array(
			\CCrmOwnerType::Deal => array(),
			\CCrmOwnerType::Invoice => array(),
			\CCrmOwnerType::Quote => array(),
		);
		foreach ($bindings as $item)
		{
			if (!array_key_exists($item['OWNER_TYPE_ID'], $bindingsIds))
				continue;

			$bindingsIds[$item['OWNER_TYPE_ID']][] = $item['OWNER_ID'];
		}

		foreach ($bindingsIds as $typeId => $ids)
		{
			if (empty($ids))
				continue;

			switch ($typeId)
			{
				case \CCrmOwnerType::Deal:
				{
					$res = \CCrmDeal::getListEx(
						array(),
						array('@ID' => $ids),
						false, false,
						array('ID', 'TITLE', 'COMPANY_TITLE', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME')
					);
					while ($deal = $res->fetch())
					{
						$showUrl = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Deal, $deal['ID']);
						$docsBindings[] = array(
							'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
							'OWNER_ID'      => $deal['ID'],
							'DOC_NAME'      => \CCrmOwnerType::getDescription(\CCrmOwnerType::Deal),
							'DOC_URL'       =>  $showUrl,

							'TITLE'         => $deal['TITLE'],
							'DESCRIPTION'   => join(', ', array_filter(array(
								$deal['COMPANY_TITLE'],
								\CUser::formatName(
									$siteNameFormat,
									array(
										'LOGIN'       => '',
										'NAME'        => $deal['CONTACT_NAME'],
										'SECOND_NAME' => $deal['CONTACT_SECOND_NAME'],
										'LAST_NAME'   => $deal['CONTACT_LAST_NAME'],
									),
									false, false
								)
							))),
							'CAPTION'		=> \CCrmOwnerType::getCaption(\CCrmOwnerType::Deal, $deal['ID']),
							'URL'			=> $showUrl,
						);
					}
				} break;
				default:
				{
					foreach ($ids as $item)
					{
						$docsBindings[] = array(
							'OWNER_TYPE_ID' => $typeId,
							'OWNER_ID'      => $item,
							'DOC_NAME'      => \CCrmOwnerType::getDescription($typeId),
							'CAPTION'       => \CCrmOwnerType::getCaption($typeId, $item),
							'URL'           => \CCrmOwnerType::GetEntityShowPath($typeId, $item),
						);
					}
				}
			}
		}

		return $docsBindings;
	}

	private function getTypeIcon($activity)
	{
		if ($activity['TYPE_ID'] == \CCrmActivityType::Call)
		{
			return $activity['DIRECTION'] == \CCrmActivityDirection::Outgoing ? 'call-outgoing' : 'call';
		}
		if ($activity['TYPE_ID'] == \CCrmActivityType::Meeting)
			return 'meet';
		if ($activity['TYPE_ID'] == \CCrmActivityType::Email)
		{
			return ($activity['DIRECTION'] ?? null) == \CCrmActivityDirection::Outgoing ? 'mail' : 'mail-send';
		}

		if ($activity['PROVIDER_ID'] == 'CRM_EXTERNAL_CHANNEL')
		{
			if (
				isset($activity['ORIGINATOR_ID']) &&
				in_array($activity['ORIGINATOR_ID'], array('BITRIX', 'WORDPRESS', 'DRUPAL', 'JOOMLA', 'MAGENTO'))
			)
			{
				return 'cmsplugins';
			}
			else
			{
				return 'onec';
			}
		}
		if ($activity['PROVIDER_ID'] == 'CRM_LF_MESSAGE')
			return 'live-feed';

		if ($activity['PROVIDER_ID'] == 'CRM_WEBFORM')
			return 'form';

		if ($activity['PROVIDER_ID'] == 'IMOPENLINES_SESSION')
			return 'chat';

		if ($activity['PROVIDER_ID'] == 'VISIT_TRACKER')
			return 'visit-tracker';

		if ($activity['PROVIDER_ID'] == 'CRM_REQUEST')
			return 'deal-request';

		if ($activity['PROVIDER_ID'] == 'CALL_LIST')
			return 'call-list';

		if ($activity['PROVIDER_ID'] == 'CRM_SMS')
			return 'crm-sms';

		if ($activity['PROVIDER_ID'] == 'REST_APP')
			return 'rest_app';

		return '';
	}

	private function prepareFilesForView(array $activity)
	{
		$result = array();

		if(!empty($activity['FILES']))
		{
			foreach($activity['FILES'] as $file)
			{
				$result[] = array(
					'fileId' => $file['fileID'],
					'fileName' => $file['fileName'],
					'viewURL' => '/bitrix/components/bitrix/crm.activity.planner/show_file.php?activity_id='
						.$activity['ID'].'&file_id='.$file['fileID'],
					'fileSize' => \CFile::formatSize($file['fileSize']),
				);
			}
		}
		elseif(!empty($activity['WEBDAV_ELEMENTS']))
		{
			foreach($activity['WEBDAV_ELEMENTS'] as $element)
			{
				$result[] = array(
					'fileId' => $element['FILE_ID'],
					'fileName' => $element['NAME'],
					'viewURL'  => $element['VIEW_URL'],
					'fileSize' => $element['SIZE'],
				);
			}
		}
		elseif(!empty($activity['DISK_FILES']))
		{
			foreach($activity['DISK_FILES'] as $file)
			{
				$result[] = array(
					'fileId' => $file['FILE_ID'],
					'fileName' => $file['NAME'],
					'viewURL' => $file['VIEW_URL'],
					'previewURL' => $file['PREVIEW_URL'],
					'fileSize' => $file['SIZE'],
					'objectId' => $file['ID'],
					'bytes' => $file['BYTES'],
				);
			}
		}

		return $result;
	}

	private function prepareCommunicationsForView($communications)
	{
		$result = array();
		$companyTypes = CCrmStatus::GetStatusListEx('COMPANY_TYPE');

		foreach($communications as $communication)
		{
			$entityTypeId = (int)$communication['ENTITY_TYPE_ID'];
			$entityId = (int)$communication['ENTITY_ID'];
			if ($entityTypeId === \CCrmOwnerType::CallList)
			{
				continue;
			}

			CCrmActivity::PrepareCommunicationInfo($communication);

			$communication['VIEW_URL'] = CCrmOwnerType::GetEntityShowPath($entityTypeId, $entityId);

			$communication['IMAGE_URL'] = '';
			$communication['FM'] = array();

			//At first fill FM`s with actual activity communications, later append FM`s from relative entities
			//TODO: remove duplicates
			if ($communication['TYPE'] !== '')
			{
				$communication['FM'][$communication['TYPE']] = array(array(
					'VALUE' => $communication['VALUE'],
					'VALUE_TYPE' => 'WORK'
				));
			}

			if ($entityTypeId === CCrmOwnerType::Contact)
			{
				$iterator = CCrmContact::GetListEx(
					array(),
					array('ID' => $entityId),
					false,
					false,
					array('PHOTO', 'POST')
				);

				$contact = $iterator ? $iterator->fetch() : null;

				if ($contact)
				{
					if ($contact['PHOTO'] > 0)
					{
						$file = new CFile();
						$fileInfo = $file->ResizeImageGet(
							$contact['PHOTO'],
							array('width' => 38, 'height' => 38),
							BX_RESIZE_IMAGE_EXACT
						);
						$communication['IMAGE_URL'] = is_array($fileInfo) && isset($fileInfo['src']) ? $fileInfo['src'] : '';
					}

					if ($contact['POST'])
						$communication['DESCRIPTION'] = $contact['POST'];
				}
			}
			elseif ($entityTypeId === CCrmOwnerType::Company)
			{
				$iterator = CCrmCompany::GetListEx(
					array(),
					array('ID' => $entityId),
					false,
					false,
					array('LOGO', 'COMPANY_TYPE')
				);

				$company = $iterator ? $iterator->fetch() : null;

				if ($company)
				{
					if ($company['LOGO'] > 0)
					{
						$file = new CFile();
						$fileInfo = $file->ResizeImageGet(
							$company['LOGO'],
							array('width' => 38, 'height' => 38),
							BX_RESIZE_IMAGE_EXACT
						);
						$communication['IMAGE_URL'] = is_array($fileInfo) && isset($fileInfo['src']) ? $fileInfo['src'] : '';
					}

					if ($company['COMPANY_TYPE'] && isset($companyTypes[$company['COMPANY_TYPE']]))
					{
						$communication['DESCRIPTION'] = $companyTypes[$company['COMPANY_TYPE']];
					}
				}
			}

			if ($entityId > 0)
			{
				$multiFieldsIterator = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => CCrmOwnerType::ResolveName($entityTypeId), 'ELEMENT_ID' => $entityId)
				);
				while($arMultiFields = $multiFieldsIterator->fetch())
				{
					$communication['FM'][$arMultiFields['TYPE_ID']][$arMultiFields['ID']] = array(
						'VALUE' => $arMultiFields['VALUE'],
						'VALUE_TYPE' => $arMultiFields['VALUE_TYPE']
					);
				}
			}

			$result[] = $communication;
		}

		return $result;
	}

	// Helpers
	private function getDestinationEntities($activity)
	{
		$result = array(
			'responsible' => array(
				array(
					'id' => 'U'.$activity['RESPONSIBLE_ID'],
					'entityId' => $activity['RESPONSIBLE_ID'],
					'name' => CCrmViewHelper::GetFormattedUserName(
						$activity['RESPONSIBLE_ID'],
						$this->arParams['NAME_TEMPLATE'] ?? null
					),
					'entityType' => 'users'
				)
			)
		);

		if ((int)$activity['OWNER_TYPE_ID'] === CCrmOwnerType::Deal)
		{
			$result['deal'] = array(
				array(
					'id' => 'D'.$activity['OWNER_ID'],
					'entityId' => $activity['OWNER_ID'],
					'name' => CCrmOwnerType::GetCaption($activity['OWNER_TYPE_ID'], $activity['OWNER_ID']),
					'entityType' => 'deals'
				)
			);
		}
		if ((int)$activity['OWNER_TYPE_ID'] === CCrmOwnerType::Order)
		{
			$result['order'] = array(
				array(
					'id' => 'O'.$activity['OWNER_ID'],
					'entityId' => $activity['OWNER_ID'],
					'name' => CCrmOwnerType::GetCaption($activity['OWNER_TYPE_ID'], $activity['OWNER_ID']),
					'entityType' => 'orders'
				)
			);
		}

		return $result;
	}

	public static function getDestinationData($params)
	{
		$type = isset($params['type']) ? $params['type'] : 'responsible';
		$result = array('LAST' => array());

		if ($type == 'responsible')
		{
			if (!Main\Loader::includeModule('socialnetwork'))
				return array();

			$arStructure = CSocNetLogDestination::GetStucture(array());
			$result['DEPARTMENT'] = $arStructure['department'];
			$result['DEPARTMENT_RELATION'] = $arStructure['department_relation'];
			$result['DEPARTMENT_RELATION_HEAD'] = $arStructure['department_relation_head'];

			$result['DEST_SORT'] = CSocNetLogDestination::GetDestinationSort(array(
				"DEST_CONTEXT" => "CRM_ACTIVITY",
			));

			CSocNetLogDestination::fillLastDestination(
				$result['DEST_SORT'],
				$result['LAST']
			);

			$destUser = array();
			foreach ($result["LAST"]["USERS"] as $value)
			{
				$destUser[] = str_replace("U", "", $value);
			}

			$result["USERS"] = \CSocNetLogDestination::getUsers(array("id" => $destUser));
		}
		elseif ($type == 'deal')
		{
			if (!Main\Loader::includeModule('crm'))
				return array();
			$deals = static::getDestinationDealEntities(array(), 12, array('ID' => 'DESC'));

			$lastDeals = array();
			foreach ($deals as $deal)
			{
				$lastDeals[$deal['id']] = $deal['id'];
			}

			$result['DEALS'] = $deals;
			$result['LAST']['DEALS'] = $lastDeals;
		}
		elseif ($type == 'order')
		{
			if (!Main\Loader::includeModule('crm'))
				return array();
			$orders = static::getDestinationOrderEntities(array(), 12, array('ID' => 'DESC'));

			$lastOrder = array();
			foreach ($orders as $order)
			{
				$lastOrder[$order['id']] = $order['id'];
			}

			$result['ORDERS'] = $orders;
			$result['LAST']['ORDERS'] = $lastOrder;
		}

		return $result;
	}

	public static function searchDestinationDeals($data)
	{
		$result = new Main\Result();

		if (!Main\Loader::includeModule('crm'))
		{
			$result->addError(new Main\Error('module "crm" is not installed.'));
			return $result;
		}

		$search = $data['SEARCH'];
		$searchConverted = (!empty($data['SEARCH_CONVERTED']) ? $data['SEARCH_CONVERTED'] : false);
		$deals = static::getDestinationDealEntities(array('%TITLE' => $search), 20);

		if (
			empty($deals)
			&& $searchConverted
			&& $search != $searchConverted
		)
		{
			$deals = static::getDestinationDealEntities(array('%TITLE' => $searchConverted), 20);
			$searchResults['SEARCH'] = $searchConverted;
		}

		$searchResults['DEALS'] = $deals;
		$searchResults['USERS'] = array();

		return $searchResults;
	}
	public static function searchDestinationOrders($data)
	{
		$result = new Main\Result();

		if (!Main\Loader::includeModule('crm'))
		{
			$result->addError(new Main\Error('module "crm" is not installed.'));
			return $result;
		}
		if (!Main\Loader::includeModule('sale'))
		{
			$result->addError(new Main\Error('module "sale" is not installed.'));
			return $result;
		}
		$search = $data['SEARCH'];
		$searchList = array(
			'LOGIC' => 'OR',
			'%ACCOUNT_NUMBER' => $search,
			'%ORDER_TOPIC' => $search,
		);
		$orders = static::getDestinationOrderEntities($searchList, 20);
		$searchResults['ORDERS'] = $orders;
		$searchResults['USERS'] = array();

		return $searchResults;
	}

	private static function getDestinationDealEntities($filter, $limit, $order = array())
	{
		$nameTemplate = CSite::GetNameFormat(false);
		$result = array();
		$iterator = CCrmDeal::GetListEx(
			$arOrder = $order,
			$arFilter = $filter,
			$arGroupBy = false,
			$arNavStartParams = array('nTopCount' => $limit),
			$arSelectFields = array('ID', 'TITLE', 'COMPANY_TITLE', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME')
		);

		while ($iterator && ($arDeal = $iterator->fetch()))
		{
			$arDesc = array();
			if ($arDeal['COMPANY_TITLE'] != '')
				$arDesc[] = $arDeal['COMPANY_TITLE'];
			$arDesc[] = CUser::FormatName(
				$nameTemplate,
				array(
					'LOGIN' => '',
					'NAME' => $arDeal['CONTACT_NAME'],
					'SECOND_NAME' => $arDeal['CONTACT_SECOND_NAME'],
					'LAST_NAME' => $arDeal['CONTACT_LAST_NAME']
				),
				false, false
			);

			$result['D'.$arDeal['ID']] = array(
				'id' => 'D'.$arDeal['ID'],
				'entityId' => $arDeal['ID'],
				'entityType' => 'deals',
				'name' => htmlspecialcharsbx($arDeal['TITLE']),
				'desc' => htmlspecialcharsbx(implode(', ', $arDesc))
			);
		}

		return $result;
	}
	private static function getDestinationOrderEntities($filter, $limit, $order = array())
	{
		$result = array();
		$params = array(
			'select' =>  array('ID', 'ACCOUNT_NUMBER'),
			'order' => !empty($order) ? $order : array('ID' => 'DESC'),
			'limit' => (int)$limit > 0 ? (int)$limit : 20
		);
		if (!empty($filter))
		{
			$params['filter'] = $filter;
		}
		$resultDB = \Bitrix\Crm\Order\Order::getList($params);
		while ($arRes = $resultDB->fetch())
		{
			$arRes['SID'] = 'O_'.$arRes['ID'];
			$result[$arRes['SID']] = array(
				'id' => 'O'.$arRes['ID'],
				'entityId' => $arRes['SID'],
				'type'  => 'orders',
				'name' => (str_replace(array(';', ','), ' ', $arRes['ACCOUNT_NUMBER'])),
				'desc' => htmlspecialcharsbx($arRes['ACCOUNT_NUMBER'])
			);
		}
		return $result;
	}

	private function getCommunicationsData(array $communications)
	{
		$result = array();

		foreach($communications as $arComm)
		{
			CCrmActivity::PrepareCommunicationInfo($arComm);
			$result[] = array(
				'id' => $arComm['ID'],
				'type' => $arComm['TYPE'],
				'value' => $arComm['VALUE'],
				'entityId' => $arComm['ENTITY_ID'],
				'entityType' => CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']),
				'entityTitle' => $arComm['TITLE'],
				'entityUrl' => CCrmOwnerType::GetEntityShowPath($arComm['ENTITY_TYPE_ID'], $arComm['ENTITY_ID'])
			);
		}

		return $result;
	}

	public static function saveActivity($data, $userID, $siteID)
	{
		$communicationsData = isset($data['communications']) ? $data['communications'] : array();

		if (!empty($data['dealId']))
		{
			$data['ownerType'] = 'DEAL';
			$data['ownerId'] = $data['dealId'];
		}
		elseif (!empty($data['orderId']))
		{
			$data['ownerType'] = \CCrmOwnerType::OrderName;
			$data['ownerId'] = $data['orderId'];
		}

		if (empty($data['ownerType']) && empty($data['ownerId']) && !empty($communicationsData[0]))
		{
			$data['ownerType'] = isset($communicationsData[0]['entityType'])? mb_strtoupper(strval($communicationsData[0]['entityType'])) : '';
			$data['ownerId'] = isset($communicationsData[0]['entityId']) ? intval($communicationsData[0]['entityId']) : 0;
		}

		$result = new Main\Result();

		if(count($data) == 0)
		{
			$result->addError(new Main\Error('SOURCE DATA ARE NOT FOUND!'));
			return $result;
		}

		$ID = isset($data['id']) ? intval($data['id']) : 0;
		$typeID = isset($data['type']) ? intval($data['type']) : CCrmActivityType::Activity;
		$providerId = isset($data['providerId'])? mb_strtoupper(strval($data['providerId'])) : '';
		$providerTypeId = isset($data['providerTypeId'])? mb_strtoupper(strval($data['providerTypeId'])) : '';

		$activity = array(
			'TYPE_ID' => $typeID,
			'PROVIDER_ID' => $providerId,
			'PROVIDER_TYPE_ID' => $providerTypeId
		);

		if($ID > 0)
		{
			$activity = CCrmActivity::GetByID($ID, false);
			if(!$activity)
			{
				$result->addError(new Main\Error('IS NOT EXISTS!'));
				return $result;
			}
		}

		$provider = CCrmActivity::GetActivityProvider($activity);
		if(!$provider)
		{
			$result->addError(new Main\Error('Provider not found!'));
			return $result;
		}

		$ownerTypeName = isset($data['ownerType'])? mb_strtoupper(strval($data['ownerType'])) : '';
		if($provider::checkOwner() && $ownerTypeName === '')
		{
			$result->addError(new Main\Error('OWNER TYPE IS NOT DEFINED!'));
			return $result;
		}

		$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
		if($provider::checkOwner() && !CCrmOwnerType::IsDefined($ownerTypeID))
		{
			$result->addError(new Main\Error('OWNER TYPE IS NOT SUPPORTED!'));
			return $result;
		}

		$ownerId = isset($data['ownerId']) ? intval($data['ownerId']) : 0;
		if($provider::checkOwner() && $ownerId <= 0)
		{
			$result->addError(new Main\Error('OWNER ID IS NOT DEFINED!'));
			return $result;
		}

		if($provider::checkOwner() && !CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerId))
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_ACTIVITY_PLANNER_NO_UPDATE_PERMISSION')));
			return $result;
		}

		$responsibleID = isset($data['responsibleId']) ? intval($data['responsibleId']) : 0;

		if($userID <= 0)
		{
			$userID = CCrmOwnerType::GetResponsibleID($ownerTypeID, $ownerId, false);
			if($userID <= 0)
			{
				$result->addError(new Main\Error('Responsible not found!'));
				return $result;
			}
		}

		$start = isset($data['startTime']) ? strval($data['startTime']) : '';
		$end = isset($data['endTime']) ? strval($data['endTime']) : '';
		if($start === '')
		{
			$start = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', $siteID);
		}

		if($end === '')
		{
			$end = $start;
		}

		$descr = isset($data['description']) ? strval($data['description']) : '';
		$priority = isset($data['important']) ? CCrmActivityPriority::High : CCrmActivityPriority::Medium;
		$location = isset($data['location']) ? strval($data['location']) : '';

		$direction = isset($data['direction']) ? intval($data['direction']) : CCrmActivityDirection::Undefined;

		$arBindings = array(
			"{$ownerTypeName}_{$ownerId}" => array(
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID' => $ownerId
			)
		);

		// Communications
		$communications = static::prepareCommunicationsForSave($communicationsData, $ownerTypeName,	$ownerId, $arBindings);

		$subject = isset($data['subject']) ? (string)$data['subject'] : '';
		if($subject === '' && isset($communications[0]))
		{
			$arCommInfo = array(
				'ENTITY_ID' => $communications[0]['ENTITY_ID'],
				'ENTITY_TYPE_ID' => $communications[0]['ENTITY_TYPE_ID']
			);
			CCrmActivity::PrepareCommunicationInfo($arCommInfo);

			$subject = $provider::generateSubject($activity['PROVIDER_ID'], $direction, array(
				'#DATE#'=> $start,
				'#TITLE#' => isset($arCommInfo['TITLE']) ? $arCommInfo['TITLE'] : $commValue,
				'#COMMUNICATION#' => $commValue
			));
		}

		$arFields = array(
			'PROVIDER_ID' => $providerId,
			'PROVIDER_TYPE_ID' => $providerTypeId,
			'TYPE_ID' =>  $typeID,
			'SUBJECT' => $subject,
			'COMPLETED' => isset($data['completed']) && $data['completed'] === 'Y' ? 'Y' : 'N',
			'PRIORITY' => $priority,
			'DESCRIPTION' => $descr,
			'DESCRIPTION_TYPE' => CCrmContentType::PlainText,
			'LOCATION' => $location,
			'DIRECTION' => $direction,
			'NOTIFY_TYPE' => CCrmActivityNotifyType::None,
		);

		$arFields['NOTIFY_TYPE'] = isset($data['notifyType']) ? (int)$data['notifyType'] : CCrmActivityNotifyType::Min;
		$arFields['NOTIFY_VALUE'] = isset($data['notifyValue']) ? (int)$data['notifyValue'] : 15;

		$isNew = $ID <= 0;
		$arPreviousFields = $ID > 0 ? CCrmActivity::GetByID($ID) : array();

		$disableStorageEdit = isset($data['disableStorageEdit']) && mb_strtoupper($data['disableStorageEdit']) === 'Y';
		if(!$disableStorageEdit)
		{
			$storageTypeID = isset($data['storageTypeID']) ? intval($data['storageTypeID']) : Integration\StorageType::Undefined;
			if($storageTypeID === Integration\StorageType::Undefined
				|| !Integration\StorageType::IsDefined($storageTypeID))
			{
				if($isNew)
				{
					$storageTypeID = CCrmActivity::GetDefaultStorageTypeID();
				}
				else
				{
					$storageTypeID = CCrmActivity::GetStorageTypeID($ID);
					if($storageTypeID === Integration\StorageType::Undefined)
					{
						$storageTypeID = CCrmActivity::GetDefaultStorageTypeID();
					}
				}
			}

			$arFields['STORAGE_TYPE_ID'] = $storageTypeID;
			if($storageTypeID === Integration\StorageType::File)
			{
				$arPermittedFiles = array();
				$arUserFiles = isset($data['files']) && is_array($data['files']) ? $data['files'] : array();
				if(!empty($arUserFiles) || !$isNew)
				{
					$arPreviousFiles = array();
					if(!$isNew)
					{
						CCrmActivity::PrepareStorageElementIDs($arPreviousFields);
						$arPreviousFiles = $arPreviousFields['STORAGE_ELEMENT_IDS'];
						if(is_array($arPreviousFiles) && !empty($arPreviousFiles))
						{
							$arPermittedFiles = array_intersect($arUserFiles, $arPreviousFiles);
						}
					}

					$uploadControlCID = isset($data['uploadControlCID']) ? strval($data['uploadControlCID']) : '';
					if($uploadControlCID !== '' && isset($_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"]))
					{
						$uploadedFiles = $_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"];
						if(!empty($uploadedFiles))
						{
							$arPermittedFiles = array_merge(
								array_intersect($arUserFiles, $uploadedFiles),
								$arPermittedFiles
							);
						}
					}

					$arFields['STORAGE_ELEMENT_IDS'] = $arPermittedFiles;
				}
			}
			elseif($storageTypeID === Integration\StorageType::WebDav || $storageTypeID === Integration\StorageType::Disk)
			{
				$fileKey = $storageTypeID === Integration\StorageType::Disk ? 'diskfiles' : 'webdavelements';
				$arFileIDs = isset($data[$fileKey]) && is_array($data[$fileKey]) ? $data[$fileKey] : array();
				if(!empty($arFileIDs) || !$isNew)
				{
					CCrmActivity::PrepareStorageElementIDs($arPreviousFields);
					$arPrevStorageElementIDs = $arPreviousFields['STORAGE_ELEMENT_IDS'];
					$arPersistentStorageElementIDs = array_intersect($arPrevStorageElementIDs, $arFileIDs);
					$arAddedStorageElementIDs = Bitrix\Crm\Integration\StorageManager::filterFiles(
						array_diff($arFileIDs, $arPrevStorageElementIDs),
						$storageTypeID,
						$userID
					);

					$arFields['STORAGE_ELEMENT_IDS'] = array_merge(
						$arPersistentStorageElementIDs,
						$arAddedStorageElementIDs
					);
				}
			}
		}

		//TIME FIELDS
		$arFields['START_TIME'] = $start;
		$arFields['END_TIME'] = $end;

		if($isNew)
		{
			$arFields['OWNER_ID'] = $ownerId;
			$arFields['OWNER_TYPE_ID'] = $ownerTypeID;
			$arFields['RESPONSIBLE_ID'] = $responsibleID > 0 ? $responsibleID : $userID;

			$arFields['BINDINGS'] = array_values($arBindings);
			$arFields['COMMUNICATIONS'] = $communications;

			$arFields['SETTINGS'] = array();

			$providerResult = $provider::postForm($arFields, $data);
			if(!$providerResult->isSuccess())
			{
				$result->addErrors($providerResult->getErrors());
				return $result;
			}

			$ID = CCrmActivity::Add($arFields, false, true, array('REGISTER_SONET_EVENT' => true));
			if($ID <= 0)
			{
				$result->addError(new Main\Error(CCrmActivity::GetLastErrorMessage()));
				return $result;
			}
			$provider::saveAdditionalData($ID, $arFields);

			//Region automation trigger
			if (
				$arFields['TYPE_ID'] === \CCrmActivityType::Call
				&& $arFields['DIRECTION'] === \CCrmActivityDirection::Incoming
			)
			{
				\Bitrix\Crm\Automation\Trigger\CallTrigger::execute($arFields['BINDINGS'], $arFields);
			}
			//end region
		}
		else
		{
			$dbResult = CCrmActivity::GetList(
				array(),
				array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('OWNER_ID', 'OWNER_TYPE_ID', 'START_TIME', 'END_TIME')
			);
			$presentFields = is_object($dbResult) ? $dbResult->Fetch() : null;
			if(!is_array($presentFields))
			{
				$result->addError(new Main\Error('COULD NOT FIND ACTIVITY!'));
				return $result;
			}

			$presentOwnerTypeID = intval($presentFields['OWNER_TYPE_ID']);
			$presentOwnerID = intval($presentFields['OWNER_ID']);
			$ownerChanged =  ($presentOwnerTypeID !== $ownerTypeID || $presentOwnerID !== $ownerId);

			$arFields['OWNER_ID'] = $ownerId;
			$arFields['OWNER_TYPE_ID'] = $ownerTypeID;

			if($responsibleID > 0)
			{
				$arFields['RESPONSIBLE_ID'] = $responsibleID;
			}

			//Merge new bindings with old bindings
			$presetCommunicationKeys = array();
			$presetCommunications = CCrmActivity::GetCommunications($ID);
			foreach($presetCommunications as $arComm)
			{
				$commEntityTypeName = CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']);
				$commEntityID = $arComm['ENTITY_ID'];
				$presetCommunicationKeys["{$commEntityTypeName}_{$commEntityID}"] = true;
			}

			$presentBindings = CCrmActivity::GetBindings($ID);
			foreach($presentBindings as &$binding)
			{
				$bindingOwnerID = (int)$binding['OWNER_ID'];
				$bindingOwnerTypeID = (int)$binding['OWNER_TYPE_ID'];
				$bindingOwnerTypeName = CCrmOwnerType::ResolveName($bindingOwnerTypeID);
				$bindingKey = "{$bindingOwnerTypeName}_{$bindingOwnerID}";

				//Skip present present owner if it is changed
				if($ownerChanged && $presentOwnerTypeID === $bindingOwnerTypeID && $presentOwnerID === $bindingOwnerID)
				{
					continue;
				}

				//Skip present communications - new communications already are in bindings
				if(isset($presetCommunicationKeys[$bindingKey]))
				{
					continue;
				}

				$arBindings[$bindingKey] = array(
					'OWNER_TYPE_ID' => $bindingOwnerTypeID,
					'OWNER_ID' => $bindingOwnerID
				);
			}
			unset($binding);
			$arFields['BINDINGS'] = array_values($arBindings);
			$arFields['COMMUNICATIONS'] = $communications;

			$providerResult = $provider::postForm($arFields, $data);
			if (!$providerResult->isSuccess())
			{
				$result->addErrors($providerResult->getErrors());
				return $result;
			}
			if(!CCrmActivity::Update($ID, $arFields, false, true, array('REGISTER_SONET_EVENT' => true)))
			{
				$result->addError(new Main\Error(CCrmActivity::GetLastErrorMessage()));
				return $result;
			}

			$provider::saveAdditionalData($ID, $arFields);
		}

		if($isNew)
		{
			$defaults = \CUserOptions::GetOption('crm.activity.planner', 'defaults', array());

			//save default notify settings
			if (!isset($defaults['notify']))
				$defaults['notify'] = array();

			$defaults['notify'][$provider::getId()] = array(
				'value' => $arFields['NOTIFY_VALUE'],
				'type' => $arFields['NOTIFY_TYPE']
			);

			//save default duration settings
			$durationValue = isset($data['durationValue']) ? (int)$data['durationValue'] : 0;
			$durationType = isset($data['durationType']) ? (int)$data['durationType'] : 0;
			if ($durationValue > 0 && $durationType > 0)
			{
				if (!isset($defaults['duration']))
					$defaults['duration'] = array();

				$defaults['duration'][$provider::getId()] = array(
					'value' => min(999, $durationValue),
					'type' => $durationType
				);
			}

			\CUserOptions::SetOption('crm.activity.planner', 'defaults', $defaults);
		}

		$result->setData(array(
			'ACTIVITY' => array(
				'ID' => $ID,
				'EDIT_URL' => CCrmOwnerType::GetEntityEditPath(CCrmOwnerType::Activity, $ID),
				'VIEW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Activity, $ID),
				'NEW' => ($isNew ? 'Y' : 'N')
			)
		));
		return $result;
	}

	private static function prepareCommunicationsForSave(array $rawData, $ownerTypeName, $ownerId, array &$arBindings)
	{
		$resultCommunications = array();

		foreach ($rawData as $commData)
		{
			$commID = isset($commData['id']) ? (int)$commData['id'] : 0;
			$commEntityType = isset($commData['entityType'])? mb_strtoupper(strval($commData['entityType'])) : '';
			$commEntityID = isset($commData['entityId']) ? intval($commData['entityId']) : 0;
			$commType = isset($commData['type'])? mb_strtoupper(strval($commData['type'])) : '';
			$commValue = isset($commData['value']) ? strval($commData['value']) : '';

			if($commEntityID <= 0 && $commType === CCrmFieldMulti::PHONE && $ownerTypeName !== 'DEAL')
			{
				// Communication entity ID is 0 (processing of new communications)
				// Communication type must present it determines TYPE_ID (is only 'PHONE' in current context)
				// Deal does not have multi fields.

				$fieldMulti = new CCrmFieldMulti();
				$arFieldMulti = array(
					'ENTITY_ID' => $ownerTypeName,
					'ELEMENT_ID' => $ownerId,
					'TYPE_ID' => CCrmFieldMulti::PHONE,
					'VALUE_TYPE' => 'WORK',
					'VALUE' => $commValue
				);

				$fieldMultiID = $fieldMulti->Add($arFieldMulti);
				if($fieldMultiID > 0)
				{
					$commEntityType = $ownerTypeName;
					$commEntityID = $ownerId;
				}
			}

			if($commEntityType !== '')
			{
				$resultCommunications[] = array(
					'ID' => $commID,
					'TYPE' => $commType,
					'VALUE' => $commValue,
					'ENTITY_ID' => $commEntityID,
					'ENTITY_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType)
				);

				$bindingKey = $commEntityID > 0 ? "{$commEntityType}_{$commEntityID}" : uniqid("{$commEntityType}_");
				if(!isset($arBindings[$bindingKey]))
				{
					$arBindings[$bindingKey] = array(
						'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType),
						'OWNER_ID' => $commEntityID
					);
				}
			}
		}

		return $resultCommunications;
	}

	private function getCrmEntityCommunications($entityTypeId, $entityId, $communicationType)
	{
		$communications = array();

		$result = function (&$data)
		{
			$communications = array();
			foreach ($data as $item)
			{
				$id = 'CRM'.$item['ENTITY_TYPE'].$item['ENTITY_ID'].':'.hash('crc32b', $item['TYPE'].':'.$item['VALUE']);
				if (!array_key_exists($id, $communications))
				{
					$communications[$id] = $item;
				}
			}

			return array_values($communications);
		};

		if (in_array($entityTypeId, array(\CCrmOwnerType::Lead, \CCrmOwnerType::Contact, \CCrmOwnerType::Company)))
		{
			$communications = array_merge(
				$communications,
				$this->getCommunicationsFromFM($entityTypeId, $entityId, $communicationType)
			);

			if (\CCrmOwnerType::Lead == $entityTypeId)
			{
				$entity = \CCrmLead::getById($entityId);
				if (empty($entity))
				{
					return $result($communications);
				}

				$entityCompanyId = isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;
				if ($entityCompanyId > 0)
				{
					$communications = array_merge(
						$communications,
						$this->getCrmEntityCommunications(\CCrmOwnerType::Company, $entityCompanyId, $communicationType)
					);
				}

				$entityContactsIds = \Bitrix\Crm\Binding\LeadContactTable::getLeadContactIds($entityId);
				if (!empty($entityContactsIds))
				{
					$communications = array_merge(
						$communications,
						$this->getCrmEntityCommunications(\CCrmOwnerType::Contact, $entityContactsIds, $communicationType)
					);
				}
			}
			else if (\CCrmOwnerType::Company == $entityTypeId)
			{
				$communications = array_merge(
					$communications,
					\CCrmActivity::getCompanyCommunications($entityId, $communicationType)
				);
			}
		}
		else if (\CCrmOwnerType::Deal == $entityTypeId || \CCrmOwnerType::DealRecurring == $entityTypeId)
		{
			$entity = \CCrmDeal::getById($entityId);
			if (empty($entity))
			{
				return $result($communications);
			}

			$entityCompanyId = isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;
			if ($entityCompanyId > 0)
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Company, $entityCompanyId, $communicationType)
				);
			}

			$entityContactsIds = \Bitrix\Crm\Binding\DealContactTable::getDealContactIds($entityId);
			if (!empty($entityContactsIds))
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Contact, $entityContactsIds, $communicationType)
				);
			}

			$communications = array_merge(
				$communications,
				\CCrmActivity::getCommunicationsByOwner(\CCrmOwnerType::DealName, $entityId, $communicationType)
			);
		}
		else if (\CCrmOwnerType::Invoice == $entityTypeId)
		{
			$entity = \CCrmInvoice::getById($entityId);
			if (empty($entity))
				return $result($communications);

			$entityContactId = isset($entity['UF_CONTACT_ID']) ? (int) $entity['UF_CONTACT_ID'] : 0;
			if ($entityContactId > 0)
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Contact, $entityContactId, $communicationType)
				);
			}

			$entityCompanyId = isset($entity['UF_COMPANY_ID']) ? (int) $entity['UF_COMPANY_ID'] : 0;
			if ($entityCompanyId > 0)
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Company, $entityCompanyId, $communicationType)
				);
			}

			$entityDealId = isset($entity['UF_DEAL_ID']) ? (int) $entity['UF_DEAL_ID'] : 0;
			if ($entityDealId > 0)
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Deal, $entityDealId, $communicationType)
				);
			}
		}
		else if (\CCrmOwnerType::Order == $entityTypeId)
		{
			$entity = \Bitrix\Crm\Order\Order::load((int)$entityId);
			if (empty($entity))
				return $result($communications);

			$ccCollection = $entity->getContactCompanyCollection();
			if ($primaryCompany = $ccCollection->getPrimaryCompany())
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Company, $primaryCompany->getField('ENTITY_ID'), $communicationType)
				);
			}

			if ($primaryContact = $ccCollection->getPrimaryContact())
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Contact, $primaryContact->getField('ENTITY_ID'), $communicationType)
				);
			}
		}
		else if (\CCrmOwnerType::Quote == $entityTypeId)
		{
			$entity = \CCrmQuote::getById($entityId);
			if (empty($entity))
				return $result($communications);

			$entityContactId = isset($entity['CONTACT_ID']) ? (int) $entity['CONTACT_ID'] : 0;
			if ($entityContactId > 0)
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Contact, $entityContactId, $communicationType)
				);
			}

			$entityCompanyId = isset($entity['COMPANY_ID']) ? (int) $entity['COMPANY_ID'] : 0;
			if ($entityCompanyId > 0)
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Company, $entityCompanyId, $communicationType)
				);
			}

			$entityDealId = isset($entity['DEAL_ID']) ? (int) $entity['DEAL_ID'] : 0;
			if ($entityDealId > 0)
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Deal, $entityDealId, $communicationType)
				);
			}

			$entityLeadId = isset($entity['LEAD_ID']) ? (int) $entity['LEAD_ID'] : 0;
			if ($entityLeadId > 0)
			{
				$communications = array_merge(
					$communications,
					$this->getCrmEntityCommunications(\CCrmOwnerType::Lead, $entityLeadId, $communicationType)
				);
			}
		}
		else
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory((int) $entityTypeId);
			if($factory)
			{
				$item = $factory->getItem($entityId);
				if($item)
				{
					if($item->hasField(\Bitrix\Crm\Item::FIELD_NAME_COMPANY_ID) && $item->getCompanyId() > 0)
					{
						$communications = array_merge(
							$communications,
							$this->getCrmEntityCommunications(\CCrmOwnerType::Company, $item->getCompanyId(), $communicationType)
						);
					}
					if($item->hasField(\Bitrix\Crm\Item::FIELD_NAME_CONTACT_BINDINGS))
					{
						foreach($item->getContacts() as $contact)
						{
							$communications = array_merge(
								$communications,
								$this->getCrmEntityCommunications(\CCrmOwnerType::Contact, $contact->getId(), $communicationType)
							);
						}
					}
					elseif($item->hasField(\Bitrix\Crm\Item::FIELD_NAME_CONTACT_ID) && $item->getContactId() > 0)
					{
						$communications = array_merge(
							$communications,
							$this->getCrmEntityCommunications(\CCrmOwnerType::Contact, $item->getContactId(), $communicationType)
						);
					}
				}
			}
		}

		return $result($communications);
	}

	private function getCommunicationsFromFM($entityTypeId, $entityId, $communicationType)
	{
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);
		$communications = array();

		if ($communicationType !== '')
		{
			$iterator = CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => $entityTypeName,
					'ELEMENT_ID' => $entityId,
					'TYPE_ID' => $communicationType
				)
			);

			while ($row = $iterator->fetch())
			{
				if (empty($row['VALUE']))
					continue;

				$communications[] = array(
					'ENTITY_ID' => $row['ELEMENT_ID'],
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_TYPE' => $entityTypeName,
					'TYPE' => $communicationType,
					'VALUE' => $row['VALUE'],
					'VALUE_TYPE' => $row['VALUE_TYPE']
				);
			}

			if (is_array($entityId))
			{
				usort(
					$communications,
					function ($a, $b) use (&$entityId)
					{
						return array_search($a['ENTITY_ID'], $entityId) - array_search($b['ENTITY_ID'], $entityId);
					}
				);
			}
		}
		else
		{
			foreach ((array) $entityId as $item)
			{
				$communications[] = array(
					'ENTITY_ID' => $item,
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_TYPE' => $entityTypeName,
					'TYPE' => $communicationType
				);
			}
		}

		return $communications;
	}
	private function makeDescriptionHtml($description, $type)
	{
		$type = (int)$type;
		if($type === CCrmContentType::BBCode)
		{
			$bbCodeParser = new CTextParser();
			$html = $bbCodeParser->convertText($description);
		}
		elseif($type === CCrmContentType::Html)
		{
			//Already sanitaized
			$html = $description;
		}
		else//CCrmContentType::PlainText and other
		{
			$html = preg_replace("/[\r\n]+/".BX_UTF_PCRE_MODIFIER, "<br>", htmlspecialcharsbx($description));
		}

		return \Bitrix\Main\Text\Emoji::decode($html);
	}
}
