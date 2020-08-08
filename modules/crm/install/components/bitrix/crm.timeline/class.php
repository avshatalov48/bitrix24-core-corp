<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Crm;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\Entity\TimelineBindingTable;
use Bitrix\Crm\Timeline\ActivityController;
use Bitrix\Crm\Timeline\TimelineEntry;

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
	protected $entityID = 0;
	/** @var array|null  */
	private $entityInfo = null;
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

	public function executeComponent()
	{
		$this->initialize();
		$this->arResult['ERRORS'] = $this->errors;
		$this->includeComponentTemplate();
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

		if($this->entityID > 0 && !\Bitrix\Crm\Security\EntityAuthorization::checkReadPermission($this->entityTypeID, $this->entityID))
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

		if(!Crm\Activity\Provider\Visit::isAvailable())
		{
			$this->arResult['ENABLE_VISIT'] = false;
		}
		else
		{
			$this->arResult['ENABLE_VISIT'] = isset($this->arParams['~ENABLE_VISIT']) ? (bool)$this->arParams['~ENABLE_VISIT'] : true;
			$this->arResult['VISIT_PARAMETERS'] = Crm\Activity\Provider\Visit::getPopupParameters();
		}

		$this->arResult['ADDITIONAL_TABS'] = array();
		$this->arResult['ENABLE_REST'] = false;
		if(Main\Loader::includeModule('rest'))
		{
			$this->arResult['ENABLE_REST'] = true;
			\CJSCore::Init(array('marketplace'));

			$this->arResult['REST_PLACEMENT'] = 'CRM_'.$this->entityTypeName.'_DETAIL_ACTIVITY';
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
				'name' => Loc::getMessage('CRM_REST_BUTTON_TITLE')
			);
		}

		$this->arResult['ENABLE_EMAIL'] = isset($this->arParams['~ENABLE_EMAIL']) ? (bool)$this->arParams['~ENABLE_EMAIL'] : true;
		$this->arResult['ENABLE_TASK'] = isset($this->arParams['~ENABLE_TASK']) ? (bool)$this->arParams['~ENABLE_TASK'] : true;

		$this->arResult['PROGRESS_SEMANTICS'] = isset($this->arParams['~PROGRESS_SEMANTICS']) ? $this->arParams['~PROGRESS_SEMANTICS'] : '';

		$this->arResult['CURRENT_URL'] = $APPLICATION->GetCurPageParam('', array('bxajaxid', 'AJAX_CALL'));
		$this->arResult['AJAX_ID'] = isset($this->arParams['AJAX_ID']) ? $this->arParams['AJAX_ID'] : '';
		$this->arResult['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $this->arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');

		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeID;
		$this->arResult['ENTITY_TYPE_NAME'] = $this->entityTypeName;
		$this->arResult['ENTITY_ID'] = $this->entityID;
		$this->arResult['ENTITY_INFO'] = $this->entityInfo;

		$this->parser = new CTextParser();
		$this->parser->allow['SMILES'] = 'N';

		$this->arResult['READ_ONLY'] = isset($this->arParams['~READ_ONLY']) && $this->arParams['~READ_ONLY'] === true;
		$this->arResult['USER_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

		$this->prepareScheduleItems();
		$this->prepareHistoryFilter();
		$this->prepareHistoryItems();
		$this->prepareHistoryFixedItems();

		//region Chat
		$this->arResult['CHAT_DATA'] = array();
		$this->arResult['CHAT_DATA']['ENABLED'] = $this->entityID > 0
			&& in_array($this->entityTypeID, array(CCrmOwnerType::Lead, CCrmOwnerType::Deal))
			&& Main\ModuleManager::isModuleInstalled('im');
		if($this->arResult['CHAT_DATA']['ENABLED'])
		{
			$this->prepareChatData();
		}
		//endregion

		//region  Push&Pull
		if(Bitrix\Main\Loader::includeModule('pull'))
		{
			$this->pullTagName = $this->arResult['PULL_TAG_NAME'] = TimelineEntry::prepareEntityPushTag($this->entityTypeID, $this->entityID);
			\CPullWatch::Add($this->userID, $this->pullTagName);

			if ($this->arResult['ENABLE_SMS'])
			{
				\CPullWatch::Add($this->userID, 'MESSAGESERVICE');
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
						Crm\Filter\TimelineEntryCategory::CHAT
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
						Crm\Filter\TimelineEntryCategory::CONVERSION
					)
				)
			),
			'applications' => array(
				'name' => Loc::getMessage('CRM_TIMELINE_FILTER_PRESET_APPLICATIONS'),
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
	public function getHistoryTimestamp(DateTime $time = null)
	{
		return $time !== null ? $time->format('Y-m-d H:i:s') : '';
	}
	public function prepareScheduleItems()
	{
		if($this->entityID <= 0)
		{
			return ($this->arResult['SCHEDULE_ITEMS'] = array());
		}

		$filter = array('STATUS' => CCrmActivityStatus::Waiting);
		$filter['BINDINGS'] = array(
			array('OWNER_TYPE_ID' => $this->entityTypeID, 'OWNER_ID' => $this->entityID)
		);

		$dbResult = \CCrmActivity::GetList(
			array('DEADLINE' => 'ASC'),
			$filter,
			false,
			false,
			array(
				'ID', 'OWNER_ID', 'OWNER_TYPE_ID',
				'TYPE_ID', 'PROVIDER_ID', 'PROVIDER_TYPE_ID', 'ASSOCIATED_ENTITY_ID', 'DIRECTION',
				'SUBJECT', 'STATUS', 'DESCRIPTION', 'DESCRIPTION_TYPE',
				'DEADLINE', 'RESPONSIBLE_ID', 'SETTINGS'
			),
			array('QUERY_OPTIONS' => array('LIMIT' => 100, 'OFFSET' => 0))
		);

		$items = array();
		while($fields = $dbResult->Fetch())
		{
			$items[$fields['ID']] = ActivityController::prepareScheduleDataModel($fields);
		}

		\Bitrix\Crm\Timeline\EntityController::prepareAuthorInfoBulk($items);

		$communications = \CCrmActivity::PrepareCommunicationInfos(
			array_keys($items),
			array(
				'ENABLE_PERMISSION_CHECK' => true,
				'USER_PERMISSIONS' => $this->userPermissions
			)
		);
		foreach($communications as $ID => $info)
		{
			$items[$ID]['ASSOCIATED_ENTITY']['COMMUNICATION'] = $info;
		}

		\Bitrix\Crm\Timeline\EntityController::prepareMultiFieldInfoBulk($items);

		$fields = \Bitrix\Crm\Pseudoactivity\WaitEntry::getRecentByOwner($this->entityTypeID, $this->entityID);
		if(is_array($fields))
		{
			$items[$fields['ID']] = \Bitrix\Crm\Timeline\WaitController::prepareScheduleDataModel(
				$fields,
				array('ENABLE_USER_INFO' => true)
			);
		}

		return ($this->arResult['SCHEDULE_ITEMS'] = array_values($items));
	}
	public function prepareHistoryItems($offsetTime = null, $offsetID = 0)
	{
		$this->arResult['HISTORY_ITEMS'] = array();

		$nextOffsetTime = null;
		$nextOffsetID = 0;

		do
		{
			if($nextOffsetTime !== null)
			{
				$offsetTime = $nextOffsetTime;
			}

			if($nextOffsetID > 0)
			{
				$offsetID = $nextOffsetID;
			}

			$this->arResult['HISTORY_ITEMS'] = array_merge(
				$this->arResult['HISTORY_ITEMS'],
				$this->loadHistoryItems(
					$offsetTime,
					$nextOffsetTime,
					$offsetID,
					$nextOffsetID,
					array('limit' => 10, 'filter' => $this->historyFilter)
				)
			);
		} while(count($this->arResult['HISTORY_ITEMS']) < 10 && $nextOffsetTime !== null);

		$this->arResult['HISTORY_NAVIGATION'] = array(
			'OFFSET_TIMESTAMP' => $this->getHistoryTimestamp($nextOffsetTime),
			'OFFSET_ID' => $nextOffsetID
		);

		return $this->arResult['HISTORY_ITEMS'];
	}
	public function prepareHistoryFixedItems()
	{
		return(
			$this->arResult['FIXED_ITEMS'] = $this->loadHistoryItems(
				null,
				$offsetTime,
				0,
				$offsetID,
				array('limit' => 3, 'onlyFixed' => true)
			)
		);
	}
	public function loadHistoryItems($offsetTime, &$nextOffsetTime, $offsetID, &$nextOffsetID, array $params = array())
	{
		if($this->entityID <= 0)
		{
			return array();
		}

		$limit = isset($params['limit']) ? (int)$params['limit'] : 0;
		$onlyFixed = isset($params['onlyFixed']) && $params['onlyFixed'] == true;
		$filter = isset($params['filter']) && is_array($params['filter']) ? $params['filter'] : array();

		//Permissions are already checked
		$query = new Query(TimelineTable::getEntity());
		$query->addSelect('*');

		$bindingQuery = new Query(TimelineBindingTable::getEntity());
		$bindingQuery->addSelect('OWNER_ID');
		$bindingQuery->addFilter('=ENTITY_TYPE_ID', $this->entityTypeID);
		$bindingQuery->addFilter('=ENTITY_ID', $this->entityID);

		if($onlyFixed)
		{
			$bindingQuery->addFilter('=IS_FIXED', 'Y');
		}

		$bindingQuery->addSelect('IS_FIXED');
		$query->addSelect('bind.IS_FIXED', 'IS_FIXED');

		$query->registerRuntimeField('',
			new ReferenceField('bind',
				Base::getInstanceByQuery($bindingQuery),
				array('=this.ID' => 'ref.OWNER_ID'),
				array('join_type' => 'INNER')
			)
		);

		//Client filter
		/*
		$bindingQuery1 = new Query(TimelineBindingTable::getEntity());
		$bindingQuery1->addSelect('OWNER_ID');

		$bindingQuery1->where(
			Main\Entity\Query::filter()
				->where('ENTITY_TYPE_ID', '=', 4)
				->where('ENTITY_ID', '=', 2414)
		);

		$query->registerRuntimeField('',
			new ReferenceField('bind1',
				Base::getInstanceByQuery($bindingQuery1),
				array('=this.ID' => 'ref.OWNER_ID'),
				array('join_type' => 'INNER')
			)
		);
		*/

		if(isset($filter['CREATED_to']))
		{
			$filter['CREATED_to'] = Main\Type\DateTime::tryParse($filter['CREATED_to']);
		}

		if(isset($filter['CREATED_from']))
		{
			$filter['CREATED_from'] = Main\Type\DateTime::tryParse($filter['CREATED_from']);
		}

		if($offsetTime instanceof DateTime && (!isset($filter['CREATED_to']) || $offsetTime < $filter['CREATED_to']))
		{
			$filter['CREATED_to'] = $offsetTime;
		}

		if(!empty($filter))
		{
			$entityFilter = $this->getHistoryFilter();
			$entityFilter->prepareListFilterParams($filter);
			Crm\Filter\TimelineDataProvider::prepareQuery($query, $filter);
		}

		$query->whereNotIn(
			'ASSOCIATED_ENTITY_TYPE_ID',
			Crm\Timeline\TimelineManager::getIgnoredEntityTypeIDs()
		);

		$query->setOrder(array('CREATED' => 'DESC', 'ID' => 'DESC'));
		if($limit > 0)
		{
			$query->setLimit($limit);
		}

		$items = array();
		$itemIDs = array();
		$offsetIndex = -1;
		$dbResult = $query->exec();
		while($fields = $dbResult->fetch())
		{
			$itemID = (int)$fields['ID'];
			$items[] = $fields;
			$itemIDs[] = $itemID;

			if($offsetID > 0 && $itemID === $offsetID)
			{
				$offsetIndex = count($itemIDs) - 1;
			}
		}
		if($offsetIndex >= 0)
		{
			$itemIDs = array_slice($itemIDs, $offsetIndex + 1);
			$items = array_splice($items, $offsetIndex + 1);
		}

		$nextOffsetTime = null;
		if(!empty($items))
		{
			$item = $items[count($items) - 1];
			if(isset($item['CREATED']) && $item['CREATED'] instanceof DateTime)
			{
				$nextOffsetTime = $item['CREATED'];
				$nextOffsetID = (int)$item['ID'];
			}
		}

		$itemsMap = array_combine($itemIDs, $items);
		\Bitrix\Crm\Timeline\TimelineManager::prepareDisplayData($itemsMap, $this->userID, $this->userPermissions);
		return array_values($itemsMap);
	}
	public function prepareChatData()
	{
		if(!Main\Loader::includeModule('im'))
		{
			return;
		}

		$chatID = Crm\Integration\Im\Chat::getChatId($this->entityTypeID, $this->entityID);
		if($chatID <= 0)
		{
			$this->arResult['CHAT_DATA']['USER_INFOS'] = Crm\Integration\Im\Chat::prepareUserInfos(
				Crm\Integration\Im\Chat::getEntityUserIDs($this->entityTypeID, $this->entityID)
			);
		}
		else
		{
			$this->arResult['CHAT_DATA']['CHAT_ID'] = $chatID;
			$this->arResult['CHAT_DATA']['USER_INFOS'] = array();
			$relations = \Bitrix\Im\Chat::getRelation(
				$chatID,
				array('SELECT' => array('ID', 'USER_ID', 'COUNTER'))
			);

			foreach($relations as $relation)
			{
				$userID = $relation['USER_ID'];
				$userInfo = \Bitrix\Im\User::getInstance($userID)->getArray(array('JSON' => 'Y'));
				$userInfo['counter'] = $relation['COUNTER'];
				$this->arResult['CHAT_DATA']['USER_INFOS'][$userID] = $userInfo;
			}

			$messageData = \Bitrix\Im\Chat::getMessages($chatID, null, array('LIMIT' => 1, 'JSON' => 'Y'));
			if(isset($messageData['messages']) && is_array($messageData['messages']) && !empty($messageData['messages']))
			{
				$this->arResult['CHAT_DATA']['MESSAGE'] = $messageData['messages'][0];
			}
		}
	}
}