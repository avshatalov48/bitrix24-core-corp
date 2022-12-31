<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm;
use Bitrix\Crm\Activity\Provider\Zoom;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class CCrmTimelineComponent extends CBitrixComponent
{
	/** @var int */
	protected $userID = 0;
	/** @var  CCrmPerms|null */
	protected $userPermissions = null;
	/** @var string */
	protected $guid = '';
	/** @var string */
	protected $entityTypeName = '';
	/** @var int */
	protected $entityTypeID = \CCrmOwnerType::Undefined;
	/** @var int */
	protected $categoryId = 0;
	/** @var int */
	protected $entityID = 0;
	/** @var array|null  */
	private $entityInfo = null;
	/** @var array|null  */
	private $extras = null;
	/** @var array */
	protected $errors = array();
	/** @var CTextParser|null  */
	protected $parser = null;
	/** @var string */
	protected $pullTagName = '';
	/** @var string */
	protected $historyFilterID = '';
	/** @var array */
	protected $historyFilter = array();

	/** @var \Bitrix\Crm\Service\Timeline\Repository */
	protected $repository;

	public function getGuid()
	{
		return $this->guid;
	}

	public function setGuid($guid)
	{
		$this->guid = $guid;
	}

	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}

	public function setEntityTypeID($entityTypeID)
	{
		$this->entityTypeID = $entityTypeID;
		$this->entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
	}

	public function getEntityID()
	{
		return $this->entityID;
	}

	public function setEntityID($entityID)
	{
		$this->entityID = $entityID;
	}

	public function getExtras(): ?array
	{
		return $this->extras;
	}

	public function setExtras(?array $extras): void
	{
		$this->extras = $extras;
	}

	public function getRepository(): \Bitrix\Crm\Service\Timeline\Repository
	{
		if (!$this->repository)
		{
			if ($this->entityID > 0)
			{
				$this->repository = new \Bitrix\Crm\Service\Timeline\Repository(
					new Crm\Service\Timeline\Context(
						new Crm\ItemIdentifier($this->entityTypeID, $this->entityID, $this->extras['CATEGORY_ID'] ?? 0),
						Crm\Service\Timeline\Context::DESKTOP,
					)
				);
			}
			else
			{
				$this->repository = new Crm\Service\Timeline\Repository\NullRepository();
			}
		}

		return $this->repository;
	}

	public function executeComponent()
	{
		$this->initialize();
		$this->arResult['ERRORS'] = $this->errors;

		$skipTemplate = $this->arParams['SKIP_TEMPLATE'] === 'Y';
		if (!$skipTemplate)
		{
			$this->includeComponentTemplate();
		}

		return $this->arResult;
	}
	protected function initialize()
	{
		global $APPLICATION;

		if(!Main\Loader::includeModule('crm'))
		{
			$this->errors[] = GetMessage('CRM_MODULE_NOT_INSTALLED');
			return;
		}

		$this->userID = CCrmSecurityHelper::GetCurrentUserID();
		$this->userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$this->guid = $this->arResult['GUID'] = isset($this->arParams['~GUID']) ? $this->arParams['~GUID'] : 'timeline';

		$entityTypeName = isset($this->arParams['~ENTITY_TYPE_NAME']) ? $this->arParams['~ENTITY_TYPE_NAME'] : '';
		$entityTypeID = isset($this->arParams['~ENTITY_TYPE_ID']) ? (int)$this->arParams['~ENTITY_TYPE_ID'] : 0;
		if($entityTypeName !== '')
		{
			$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
		}
		else if($entityTypeID > 0)
		{
			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
		}

		$this->entityTypeName = $entityTypeName;
		$this->entityTypeID = $entityTypeID;

		if(!\CCrmOwnerType::IsDefined($this->entityTypeID))
		{
			$this->errors[] = GetMessage('CRM_TIMELINE_ENTITY_TYPE_NOT_ASSIGNED');
			return;
		}

		$this->entityID = isset($this->arParams['ENTITY_ID']) ? (int)$this->arParams['ENTITY_ID'] : 0;

		$this->entityInfo = isset($this->arParams['~ENTITY_INFO']) && is_array($this->arParams['~ENTITY_INFO'])
			? $this->arParams['~ENTITY_INFO'] : array();

		$this->extras = isset($this->arParams['~EXTRAS']) && is_array($this->arParams['~EXTRAS'])
			? $this->arParams['~EXTRAS'] : [];

		if($this->entityID > 0 && !\Bitrix\Crm\Security\EntityAuthorization::checkReadPermission(
			$this->entityTypeID,
			$this->entityID,
			$this->userPermissions,
			$this->extras
		))
		{
			$this->errors[] = GetMessage('CRM_PERMISSION_DENIED');
			return;
		}

		$this->arResult['ACTIVITY_EDITOR_ID'] = isset($this->arParams['~ACTIVITY_EDITOR_ID']) ? $this->arParams['~ACTIVITY_EDITOR_ID'] : '';
		$this->arResult['ENABLE_WAIT'] = isset($this->arParams['~ENABLE_WAIT']) ? (bool)$this->arParams['~ENABLE_WAIT'] : false;
		$this->arResult['WAIT_TARGET_DATES'] = isset($this->arParams['~WAIT_TARGET_DATES']) && is_array($this->arParams['~WAIT_TARGET_DATES'])
			? $this->arParams['~WAIT_TARGET_DATES'] : array();
		$this->arResult['WAIT_CONFIG'] = \CUserOptions::GetOption(
			'crm.timeline.wait',
			mb_strtolower($this->guid),
			array()
		);

		if (isset($this->arParams['~ENABLE_TODO']))
		{
			$this->arResult['ENABLE_TODO'] = (bool)$this->arParams['~ENABLE_TODO'];
		}
		else
		{
			$this->arResult['ENABLE_TODO'] = Crm\Settings\Crm::isUniversalActivityScenarioEnabled();
		}

		if (isset($this->arParams['~ENABLE_ZOOM']))
		{
			$this->arResult['ENABLE_ZOOM'] = (bool)$this->arParams['~ENABLE_ZOOM'];
			$this->arResult['STATUS_ZOOM'] = true;
		}
		elseif (!Zoom::isAvailable())
		{
			$this->arResult['ENABLE_ZOOM'] = false;
			$this->arResult['STATUS_ZOOM'] = false;
		}
		elseif (!Zoom::isConnected())
		{
			$this->arResult['ENABLE_ZOOM'] = true;
			$this->arResult['STATUS_ZOOM'] = false;
		}
		else
		{
			$this->arResult['ENABLE_ZOOM'] = true;
			$this->arResult['STATUS_ZOOM'] = true;
		}

		if(!Crm\Integration\SmsManager::canUse())
		{
			$this->arResult['ENABLE_SMS'] = false;
		}
		else
		{
			$this->arResult['ENABLE_SMS'] = isset($this->arParams['~ENABLE_SMS']) ? (bool)$this->arParams['~ENABLE_SMS'] : true;
		}

		$this->arResult['SMS_MANAGE_URL'] = \Bitrix\Crm\Integration\SmsManager::getManageUrl();
		$this->arResult['SMS_CAN_SEND_MESSAGE'] = \Bitrix\Crm\Integration\SmsManager::canSendMessage();
		$this->arResult['SMS_STATUS_DESCRIPTIONS'] = \Bitrix\Crm\Integration\SmsManager::getMessageStatusDescriptions();
		$this->arResult['SMS_STATUS_SEMANTICS'] = \Bitrix\Crm\Integration\SmsManager::getMessageStatusSemantics();
		$this->arResult['SMS_CONFIG'] = \Bitrix\Crm\Integration\SmsManager::getEditorConfig(
			$this->entityTypeID,
			$this->entityID
		);

		if(!Main\ModuleManager::isModuleInstalled('calendar'))
		{
			$this->arResult['ENABLE_CALL'] = $this->arResult['ENABLE_MEETING'] = false;
		}
		else
		{
			$this->arResult['ENABLE_CALL'] = isset($this->arParams['~ENABLE_CALL']) ? (bool)$this->arParams['~ENABLE_CALL'] : true;
			$this->arResult['ENABLE_MEETING'] = isset($this->arParams['~ENABLE_MEETING']) ? (bool)$this->arParams['~ENABLE_MEETING'] : true;
		}

		if(Crm\Activity\Provider\Visit::isAvailable())
		{
			$this->arResult['ENABLE_VISIT'] = (bool)($this->arParams['~ENABLE_VISIT'] ?? true);
			$this->arResult['IS_VISIT_RESTRICTED'] = !Crm\Restriction\RestrictionManager::getVisitRestriction()->hasPermission();
			$this->arResult['VISIT_PARAMETERS'] = Crm\Activity\Provider\Visit::getPopupParameters();
		}
		else
		{
			$this->arResult['ENABLE_VISIT'] = false;
		}

		$this->arResult['ADDITIONAL_TABS'] = array();
		$this->arResult['ENABLE_REST'] = false;
		if(Main\Loader::includeModule('rest'))
		{
			$this->arResult['ENABLE_REST'] = true;
			\CJSCore::Init(array('marketplace'));

			$this->arResult['REST_PLACEMENT'] =
				Integration\Rest\AppPlacement::getDetailActivityPlacementCode($this->entityTypeID)
			;
			$placementHandlerList = \Bitrix\Rest\PlacementTable::getHandlersList($this->arResult['REST_PLACEMENT']);

			if(count($placementHandlerList) > 0)
			{
				\CJSCore::Init(array('applayout'));

				foreach($placementHandlerList as $placementHandler)
				{
					$this->arResult['ADDITIONAL_TABS'][] = array(
						'id' => 'activity_rest_'.$placementHandler['APP_ID'].'_'.$placementHandler['ID'],
						'name' => $placementHandler['TITLE'] <> ''
							? $placementHandler['TITLE']
							: $placementHandler['APP_NAME'],
					);
				}
			}

			$this->arResult['ADDITIONAL_TABS'][] = array(
				'id' => 'activity_rest_applist',
				'name' => Loc::getMessage('CRM_REST_BUTTON_TITLE_2')
			);
		}

		$this->arResult['ENABLE_EMAIL'] = isset($this->arParams['~ENABLE_EMAIL']) ? (bool)$this->arParams['~ENABLE_EMAIL'] : true;
		$this->arResult['ENABLE_TASK'] = isset($this->arParams['~ENABLE_TASK']) ? (bool)$this->arParams['~ENABLE_TASK'] : true;

		if (!\Bitrix\Crm\Settings\ActivitySettings::areOutdatedCalendarActivitiesEnabled())
		{
			$this->arResult['ENABLE_CALL'] = false;
			$this->arResult['ENABLE_MEETING'] = false;
			$this->arResult['ENABLE_WAIT'] = false;
		}

		$this->arResult['PROGRESS_SEMANTICS'] = isset($this->arParams['~PROGRESS_SEMANTICS']) ? $this->arParams['~PROGRESS_SEMANTICS'] : '';

		$this->arResult['CURRENT_URL'] = $APPLICATION->GetCurPageParam('', array('bxajaxid', 'AJAX_CALL'));
		$this->arResult['AJAX_ID'] = isset($this->arParams['AJAX_ID']) ? $this->arParams['AJAX_ID'] : '';
		$this->arResult['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $this->arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');

		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeID;
		$this->arResult['ENTITY_TYPE_NAME'] = $this->entityTypeName;
		$this->arResult['ENTITY_ID'] = $this->entityID;
		$this->arResult['ENTITY_INFO'] = $this->entityInfo;
		$this->arResult['EXTRAS'] = $this->extras;

		$this->parser = new CTextParser();
		$this->parser->allow['SMILES'] = 'N';

		$this->arResult['READ_ONLY'] = isset($this->arParams['~READ_ONLY']) && $this->arParams['~READ_ONLY'] === true;
		$this->arResult['USER_ID'] = \CCrmSecurityHelper::GetCurrentUserID();
		$this->arResult['LAYOUT_CURRENT_USER'] = Crm\Service\Timeline\Layout\User::current()->toArray();

		$this->prepareScheduleItems();
		$this->prepareHistoryFilter();
		$this->prepareHistoryItems();
		$this->prepareHistoryFixedItems();

		$this->prepareChatData();

		//region  Push&Pull
		if(Bitrix\Main\Loader::includeModule('pull'))
		{
			$this->pullTagName = $this->arResult['PULL_TAG_NAME'] = TimelineEntry::prepareEntityPushTag($this->entityTypeID, $this->entityID);
			\CPullWatch::Add($this->userID, $this->pullTagName);

			if ($this->arResult['ENABLE_SMS'])
			{
				\CPullWatch::Add($this->userID, 'MESSAGESERVICE');
			}

			if (Crm\Integration\NotificationsManager::canUse() && Crm\Integration\NotificationsManager::getPullTagName())
			{
				\CPullWatch::Add($this->userID, Crm\Integration\NotificationsManager::getPullTagName());
			}
		}
		//endregion

		//region salescenter
		if(isset($this->arParams['~ENABLE_SALESCENTER']) && $this->arParams['~ENABLE_SALESCENTER'] === false)
		{
			$this->arResult['ENABLE_SALESCENTER'] = false;
		}
		else
		{
			$this->arResult['ENABLE_SALESCENTER'] = ($this->arResult['ENABLE_SMS'] && Crm\Integration\SalesCenterManager::getInstance()->isShowApplicationInSmsEditor());
		}
		if(is_array($this->arResult['SMS_CONFIG']))
		{
			$this->arResult['SMS_CONFIG']['isSalescenterEnabled'] = $this->arResult['ENABLE_SALESCENTER'];
		}

		$this->arResult['ENABLE_DELIVERY'] = (
			$this->arResult['ENABLE_SALESCENTER']
			&& $this->entityTypeID === CCrmOwnerType::Deal
			&& Crm\Integration\SalesCenterManager::getInstance()->hasInstallableDeliveryItems()
		);

		//endregion

		$documentGeneratorManager = Crm\Integration\DocumentGeneratorManager::getInstance();
		$this->arResult['ENABLE_DOCUMENTS'] = $documentGeneratorManager->isDocumentButtonAvailable();
		if($this->arResult['ENABLE_DOCUMENTS'])
		{
			$extension = Main\UI\Extension::getConfig('documentgenerator.selector');
			if($extension)
			{
				$providersMap = $documentGeneratorManager->getCrmOwnerTypeProvidersMap();
				$provider = $providersMap[$this->entityTypeID];
				if(!$provider)
				{
					$this->arResult['ENABLE_DOCUMENTS'] = false;
				}
				else
				{
					$this->arResult['SMS_CONFIG']['documentsProvider'] = $provider;
					$this->arResult['SMS_CONFIG']['documentsValue'] = $this->entityID;
				}
			}
			else
			{
				$this->arResult['ENABLE_DOCUMENTS'] = false;
			}
		}
		$this->arResult['SMS_CONFIG']['isDocumentsEnabled'] = $this->arResult['ENABLE_DOCUMENTS'];

		$this->arResult['SHOW_FILES_FEATURE'] = false;
		$this->arResult['ENABLE_FILES'] = (Main\Loader::includeModule('disk') && \Bitrix\Disk\Configuration::isPossibleToShowExternalLinkControl());
		if($this->arResult['ENABLE_FILES'])
		{
			$this->arResult['SMS_CONFIG']['isFilesEnabled'] = $this->arResult['ENABLE_FILES'];
			$this->arResult['ENABLE_FILES_EXTERNAL_LINK'] = \Bitrix\Disk\Configuration::isEnabledManualExternalLink();
			$this->arResult['SMS_CONFIG']['isFilesExternalLinkEnabled'] = $this->arResult['ENABLE_FILES_EXTERNAL_LINK'];
			if($this->arResult['ENABLE_FILES_EXTERNAL_LINK'])
			{
				Asset::getInstance()->addJs('/bitrix/components/bitrix/disk.uf.file/templates/.default/script.js');
				Main\UI\Extension::load(['uploader']);
				$this->arResult['SMS_CONFIG']['diskUrls'] = [
					'urlSelect' => '/bitrix/tools/disk/uf.php?action=selectFile&SITE_ID='.SITE_ID,
					'urlRenameFile' => '/bitrix/tools/disk/uf.php?action=renameFile',
					'urlDeleteFile' => '/bitrix/tools/disk/uf.php?action=deleteFile',
					'urlUpload' => '/bitrix/tools/disk/uf.php?action=uploadFile&ncc=1',
				];
			}
			else
			{
				$this->arResult['SHOW_FILES_FEATURE'] = \Bitrix\Crm\Integration\Bitrix24Manager::isEnabled();
			}
		}
		else
		{
			$this->arResult['SMS_CONFIG']['isFilesEnabled'] = false;
		}
		$defaultRateOption = [
			'rate' => 1,
		];
		$audioPlaybackOptions = CUserOptions::GetOption('crm', 'timeline_audio_playback');

		$this->arResult['AUDIO_PLAYBACK_RATE'] = $audioPlaybackOptions['rate'] ?? $defaultRateOption['rate'];
	}

	public function getHistoryFilter()
	{
		$filter = new Crm\Filter\Filter(
			$this->historyFilterID,
			new Crm\Filter\TimelineDataProvider(
				new Crm\Filter\TimelineSettings(array('ID' => $this->historyFilterID))
			)
		);

		return $filter;
	}

	public function prepareHistoryFilter()
	{
		$this->arResult['HISTORY_FILTER_ID'] = $this->historyFilterID = mb_strtolower($this->entityTypeName).'_'.$this->entityID.'_timeline_history';
		$this->arResult['HISTORY_FILTER_PRESET_ID'] = mb_strtolower($this->entityTypeName).'_timeline_history';
		$this->arResult['HISTORY_FILTER_PRESETS'] = array(
			'communications' => array(
				'name' => Loc::getMessage('CRM_TIMELINE_FILTER_PRESET_COMMUNICATIONS'),
				'fields' => array(
					'ENTRY_CATEGORY_ID' => array(
						Crm\Filter\TimelineEntryCategory::SMS,
						Crm\Filter\TimelineEntryCategory::ACTIVITY_CALL,
						Crm\Filter\TimelineEntryCategory::ACTIVITY_VISIT,
						Crm\Filter\TimelineEntryCategory::ACTIVITY_MEETING,
						Crm\Filter\TimelineEntryCategory::ACTIVITY_EMAIL,
						Crm\Filter\TimelineEntryCategory::WEB_FORM,
						Crm\Filter\TimelineEntryCategory::CHAT,
						Crm\Filter\TimelineEntryCategory::ACTIVITY_ZOOM,
						Crm\Filter\TimelineEntryCategory::ACTIVITY_CALL_TRACKER
					)
				)
			),
			'comments' => array(
				'name' => Loc::getMessage('CRM_TIMELINE_FILTER_PRESET_COMMENTS'),
				'fields' => array(
					'ENTRY_CATEGORY_ID' => array(
						Crm\Filter\TimelineEntryCategory::COMMENT,
						Crm\Filter\TimelineEntryCategory::WAITING
					)
				)
			),
			'documents' => array(
				'name' => Loc::getMessage('CRM_TIMELINE_FILTER_PRESET_DOCUMENTS'),
				'fields' => array(
					'ENTRY_CATEGORY_ID' => array(
						Crm\Filter\TimelineEntryCategory::DOCUMENT
					)
				)
			),
			'tasks' => array(
				'name' => Loc::getMessage('CRM_TIMELINE_FILTER_PRESET_TASKS'),
				'fields' => array(
					'ENTRY_CATEGORY_ID' => array(
						Crm\Filter\TimelineEntryCategory::ACTIVITY_TASK
					)
				)
			),
			'business_processes' => array(
				'name' => Loc::getMessage('CRM_TIMELINE_FILTER_PRESET_BUSINESS_PROCESSES'),
				'fields' => array(
					'ENTRY_CATEGORY_ID' => array(
						Crm\Filter\TimelineEntryCategory::ACTIVITY_REQUEST,
						Crm\Filter\TimelineEntryCategory::BIZ_PROCESS
					)
				)
			),
			'system_events' => array(
				'name' => Loc::getMessage('CRM_TIMELINE_FILTER_PRESET_SYSTEM_EVENTS'),
				'fields' => array(
					'ENTRY_CATEGORY_ID' => array(
						Crm\Filter\TimelineEntryCategory::CREATION,
						Crm\Filter\TimelineEntryCategory::MODIFICATION,
						Crm\Filter\TimelineEntryCategory::CONVERSION,
						Crm\Filter\TimelineEntryCategory::LINK,
						Crm\Filter\TimelineEntryCategory::UNLINK,
						Crm\Filter\TimelineEntryCategory::LOG_MESSAGE,
					)
				)
			),
			'applications' => array(
				'name' => Loc::getMessage('CRM_TIMELINE_FILTER_PRESET_APPLICATIONS_2'),
				'fields' => array(
					'ENTRY_CATEGORY_ID' => array(
						Crm\Filter\TimelineEntryCategory::APPLICATION
					)
				)
			)
		);

		$this->arResult['HISTORY_FILTER'] = array();
		$filterOptions = new \Bitrix\Main\UI\Filter\Options(
			$this->historyFilterID,
			$this->arResult['HISTORY_FILTER_PRESETS'],
			$this->arResult['HISTORY_FILTER_PRESET_ID']
		);
		$filter = $this->getHistoryFilter();
		$effectiveFilterFieldIDs = $filterOptions->getUsedFields();
		if(empty($effectiveFilterFieldIDs))
		{
			$effectiveFilterFieldIDs = $filter->getDefaultFieldIDs();
		}

		foreach($effectiveFilterFieldIDs as $filterFieldID)
		{
			$filterField = $filter->getField($filterFieldID);
			if($filterField)
			{
				$this->arResult['HISTORY_FILTER'][] = $filterField->toArray();
			}
		}

		$this->historyFilter = $filterOptions->getFilter($this->arResult['HISTORY_FILTER']);
		$this->arResult['IS_HISTORY_FILTER_APPLIED'] = isset($this->historyFilter['FILTER_APPLIED'])
			&& $this->historyFilter['FILTER_APPLIED'];

		return $this->historyFilter;
	}
	public function getHistoryTimestamp(?DateTime $time = null)
	{
		return $time !== null ? $time->format('Y-m-d H:i:s') : '';
	}
	public function prepareScheduleItems()
	{
		$this->arResult['SCHEDULE_ITEMS'] = $this->getRepository()->getScheduledItems()->getItems();
	}

	public function prepareHistoryItems($offsetTime = null, $offsetID = 0)
	{
		$filter = $this->historyFilter;
		$entityFilter = $this->getHistoryFilter();
		$entityFilter->prepareListFilterParams($filter);

		$result = $this->getRepository()->getHistoryItemsPage(
			(new Crm\Service\Timeline\Repository\Query())
				->setOffsetId((int)$offsetID)
				->setOffsetTime($offsetTime ? DateTime::createFromUserTime($offsetTime) : null)
				->setFilter($filter)
				->setLimit(10)
		);

		$this->arResult['HISTORY_ITEMS'] = $result->getItems();

		$this->arResult['HISTORY_NAVIGATION'] = [
			'OFFSET_TIMESTAMP' => $this->getHistoryTimestamp($result->getOffsetTime()),
			'OFFSET_ID' => $result->getOffsetId(),
		];
	}
	public function prepareHistoryFixedItems()
	{
		$result = $this->getRepository()->getHistoryItemsPage(
			(new Crm\Service\Timeline\Repository\Query())
				->setSearchForFixedItems(true)
				->setLimit(3)
		);

		$this->arResult['FIXED_ITEMS'] = $result->getItems();
	}

	public function prepareChatData()
	{
		$this->arResult['CHAT_DATA'] = $this->getChatData();
	}

	private function getChatData(): array
	{
		$chatData = [];

		$isEnabled =
			$this->entityID > 0
			&& \Bitrix\Crm\Integration\Im\Chat::isEntitySupported($this->entityTypeID)
			&& Main\Loader::includeModule('im')
		;

		$chatData['ENABLED'] = $isEnabled;
		if (!$isEnabled)
		{
			return $chatData;
		}

		$chatData['IS_RESTRICTED'] = false;

		$chatRestriction = Crm\Restriction\RestrictionManager::getChatInDetailsRestriction();
		if (!$chatRestriction->hasPermission())
		{
			$chatData['IS_RESTRICTED'] = true;
			$chatData['LOCK_SCRIPT'] = $chatRestriction->prepareInfoHelperScript();
		}

		$chatID = Crm\Integration\Im\Chat::getChatId($this->entityTypeID, $this->entityID);
		if ($chatID <= 0)
		{
			$chatData['USER_INFOS'] = Crm\Integration\Im\Chat::prepareUserInfos(
				Crm\Integration\Im\Chat::getEntityUserIDs($this->entityTypeID, $this->entityID)
			);

			return $chatData;
		}

		$chatData['CHAT_ID'] = $chatID;
		$chatData['USER_INFOS'] = [];
		$relations = \Bitrix\Im\Chat::getRelation(
			$chatID,
			[
				'SELECT' => [
					'ID',
					'USER_ID',
					'COUNTER',
				],
			],
		);

		foreach ($relations as $relation)
		{
			$userID = $relation['USER_ID'];
			$userInfo = \Bitrix\Im\User::getInstance($userID)->getArray(['JSON' => 'Y']);
			$userInfo['counter'] = $relation['COUNTER'];
			$chatData['USER_INFOS'][$userID] = $userInfo;
		}

		$messageData = \Bitrix\Im\Chat::getMessages(
			$chatID,
			null,
			[
				'LIMIT' => 1,
				'USER_TAG_SPREAD' => 'Y',
				'JSON' => 'Y',
			],
		);
		if (is_array($messageData) && !empty($messageData['messages']) && is_array($messageData['messages']))
		{
			$messageData['messages'][0]['text'] = preg_replace_callback(
				"/\[USER=([0-9]{1,})\]\[\/USER\]/i",
				['\Bitrix\Im\Text', 'modifyShortUserTag'],
				$messageData['messages'][0]['text'],
			);
			$chatData['MESSAGE'] = $messageData['messages'][0];
		}

		return $chatData;
	}
}
