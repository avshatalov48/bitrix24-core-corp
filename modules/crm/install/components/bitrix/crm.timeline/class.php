<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Crm\Activity\TodoPingSettingsProvider;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
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

		$skipTemplate = ($this->arParams['SKIP_TEMPLATE'] ?? null) === 'Y';
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
		$this->arResult['PROGRESS_SEMANTICS'] = $this->arParams['~PROGRESS_SEMANTICS'] ?? '';

		$this->arResult['CURRENT_URL'] = $APPLICATION->GetCurPageParam('', array('bxajaxid', 'AJAX_CALL'));
		$this->arResult['AJAX_ID'] = $this->arParams['AJAX_ID'] ?? '';
		$this->arResult['PATH_TO_USER_PROFILE'] = CrmCheckPath(
			'PATH_TO_USER_PROFILE',
			$this->arParams['PATH_TO_USER_PROFILE'] ?? '',
			'/company/personal/user/#user_id#/'
		);

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
		$this->arResult['PING_SETTINGS'] = (new TodoPingSettingsProvider(
			$this->entityTypeID,
			$this->extras['CATEGORY_ID'] ?? 0
		))->fetchForJsComponent();
		$this->arResult['CURRENCIES'] = $this->getCurrency();
		$this->arResult['CALENDAR_SETTINGS'] = (new Crm\Activity\ToDo\CalendarSettings\CalendarSettingsProvider())
			->fetchForJsComponent()
		;
		$this->arResult['COLOR_SETTINGS'] = (new Crm\Activity\ToDo\ColorSettings\ColorSettingsProvider())
			->fetchForJsComponent()
		;

		$this->prepareScheduleItems();
		$this->prepareHistoryFilter();
		$this->prepareHistoryItems();
		$this->prepareHistoryFixedItems();

		$this->prepareAutomationTourData();
		$this->prepareChatData();

		//region  Push&Pull
		if(Bitrix\Main\Loader::includeModule('pull'))
		{
			$this->pullTagName = $this->arResult['PULL_TAG_NAME'] = TimelineEntry::prepareEntityPushTag($this->entityTypeID, $this->entityID);
			\CPullWatch::Add($this->userID, $this->pullTagName);

			if (\Bitrix\Crm\Integration\SmsManager::canUse())
			{
				\CPullWatch::Add($this->userID, 'MESSAGESERVICE');
			}

			if (Crm\Integration\NotificationsManager::canUse() && Crm\Integration\NotificationsManager::getPullTagName())
			{
				\CPullWatch::Add($this->userID, Crm\Integration\NotificationsManager::getPullTagName());
			}
		}
		//endregion

		$defaultRateOption = [
			'rate' => 1,
		];
		$audioPlaybackOptions = CUserOptions::GetOption('crm', 'timeline_audio_playback');

		$this->arResult['AUDIO_PLAYBACK_RATE'] = $audioPlaybackOptions['rate'] ?? $defaultRateOption['rate'];
	}

	public function getHistoryFilter()
	{
		return new Crm\Filter\Filter(
			$this->historyFilterID,
			new Crm\Filter\TimelineDataProvider(
				new Crm\Filter\TimelineSettings([
					'ID' => $this->historyFilterID,
					'entityId' => $this->entityID,
					'entityTypeId' => $this->getEntityTypeID(),
				])
			)
		);
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

		//Adding the ability to filter by a previously unselected field ACTIVITY
		if (!in_array('ACTIVITY', $effectiveFilterFieldIDs))
		{
			$effectiveFilterFieldIDs[] = 'ACTIVITY';
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

	private function prepareAutomationTourData()
	{
		$this->arResult['BIZPROC_AVAILABLE'] = false;

		if (
			Main\Loader::includeModule('bizproc')
			&& \CCrmBizProcHelper::ResolveDocumentType($this->entityTypeID)
			&& CBPRuntime::isFeatureEnabled()
		)
		{
			$toolsManager = \Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager();
			if ($toolsManager->checkBizprocAvailability())
			{
				$this->arResult['BIZPROC_AVAILABLE'] = true;
				$documentId = \CCrmBizProcHelper::ResolveDocumentId($this->entityTypeID, $this->entityID);
				$runningIds = \Bitrix\Bizproc\Workflow\Entity\WorkflowInstanceTable::getIdsByDocument($documentId);

				if (!empty($runningIds))
				{
					$this->arResult['DOCUMENT_HAS_RUNNING_WORKFLOW'] = true;

					$taskIterator = CBPTaskService::GetList(
						['ID' => 'ASC'],
						[
							'WORKFLOW_ID' => $runningIds,
							'USER_ID' => $this->arResult['USER_ID'],
							'USER_STATUS' => \CBPTaskUserStatus::Waiting
						],
						false,
						['nTopCount' => 1],
						['ID', 'WORKFLOW_ID'],
					);
					if ($task = $taskIterator->getNext())
					{
						$this->arResult['DOCUMENT_HAS_WAITING_WORKFLOW_TASK'] = true;

						$result = \Bitrix\Crm\ActivityTable::getList([
							'filter' => [
								'=ORIGIN_ID' => $task['WORKFLOW_ID'],
								'=PROVIDER_ID' => 'CRM_BIZPROC_TASK',
								'=ASSOCIATED_ENTITY_ID' => $task['ID']
							],
							'select' => ['ID'],
						]);
						if ($activity = $result->fetch())
						{
							$this->arResult['WORKFLOW_FIRST_TOUR_WAS_CLOSED'] = false;
							$option = CUserOptions::getOption(
								'crm.tour',
								\Bitrix\Crm\Tour\Bizproc\WorkflowStarted::OPTION_NAME,
							);
							if (!empty($option))
							{
								$this->arResult['WORKFLOW_FIRST_TOUR_WAS_CLOSED'] = $option['closed'] === 'Y';
							}

							$this->arResult['WORKFLOW_TASK_ACTIVITY_ID'] = $activity['ID'];
						}
					}
				}
			}
		}
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

	protected function getCurrency()
	{
		$currencyList = [];

		if (Main\Loader::includeModule('currency'))
		{
			$currencyId = \CCrmCurrency::GetBaseCurrencyID();
			$currencyFormat = CCurrencyLang::GetFormatDescription($currencyId);
			$currencyList[] = [
				'CURRENCY' => $currencyId,
				'FORMAT' => [
					'FORMAT_STRING' => $currencyFormat['FORMAT_STRING'],
					'DEC_POINT' => $currencyFormat['DEC_POINT'],
					'THOUSANDS_SEP' => $currencyFormat['THOUSANDS_SEP'],
					'DECIMALS' => $currencyFormat['DECIMALS'],
					'THOUSANDS_VARIANT' => $currencyFormat['THOUSANDS_VARIANT'],
					'HIDE_ZERO' => $currencyFormat['HIDE_ZERO'],
				],
			];
		}

		return $currencyList;
	}
}
