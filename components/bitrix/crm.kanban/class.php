<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Activity\TodoPingSettingsProvider;
use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Kanban;
use Bitrix\Crm\Kanban\EntityActivityCounter;
use Bitrix\Crm\Order;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline;
use Bitrix\Crm\Settings\CounterSettings;
use Bitrix\Main\Application;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;
use Bitrix\Sale;

class CrmKanbanComponent extends \CBitrixComponent
{
	protected const OPTION_CATEGORY = 'crm';
	protected const OPTION_NAME_HIDE_CONTACT_CENTER = 'kanban_cc_hide';
	protected const OPTION_NAME_HIDE_REST_DEMO = 'kanban_rest_hide';
	protected const COLUMN_NAME_DELETED = 'DELETED';

	/** @var \Bitrix\Crm\Kanban\Desktop */
	protected $kanban;
	protected $statusKey;

	protected $blockPage = 1;
	protected $application;

	protected $currentUserID = 0;
	protected $componentParams = [];
	protected bool $needPrepareColumns = false;

	protected ?\Bitrix\Crm\Service\Context $operationContext = null;

	public function onPrepareComponentParams($arParams): array
	{
		if (!Loader::includeModule('crm'))
		{
			return $arParams;
		}

		$arParams['HIDE_CC'] = \CUserOptions::getOption(
			static::OPTION_CATEGORY,
			static::OPTION_NAME_HIDE_CONTACT_CENTER,
			false
		);

		if (!isset($arParams['ADDITIONAL_FILTER']) || !is_array($arParams['ADDITIONAL_FILTER']))
		{
			$arParams['ADDITIONAL_FILTER'] = [];
		}
		if (!isset($arParams['EXTRA']) || !is_array($arParams['EXTRA']))
		{
			$arParams['EXTRA'] = [];
		}
		$arParams['PAGE'] = (int)($arParams['PAGE'] ?? null);
		$arParams['ONLY_COLUMNS'] = ($arParams['ONLY_COLUMNS'] ?? 'N')  !== 'Y' ? 'N' : 'Y';
		$arParams['ONLY_ITEMS'] = ($arParams['ONLY_ITEMS'] ?? 'N')  !== 'Y' ? 'N' : 'Y';
		$arParams['EMPTY_RESULT'] = ($arParams['EMPTY_RESULT'] ?? 'N') !== 'Y' ? 'N' : 'Y';
		$arParams['IS_AJAX'] = ($arParams['IS_AJAX'] ?? 'N') !== 'Y' ? 'N' : 'Y';
		$arParams['GET_AVATARS'] = ($arParams['GET_AVATARS'] ?? 'N') !== 'Y' ? 'N' : 'Y';
		$arParams['FORCE_FILTER'] = ($arParams['FORCE_FILTER'] ?? 'N') !== 'Y' ? 'N' : 'Y';
		$arParams['VIEW_MODE'] = \Bitrix\Crm\Kanban\ViewMode::normalize((string)($arParams['VIEW_MODE'] ?? ''));
		$arParams['PATH_TO_USER'] = $arParams['PATH_TO_USER'] ?? '/company/personal/user/#user_id#/';
		$arParams['PATH_TO_MERGE'] = $arParams['PATH_TO_MERGE'] ?? '';
		$arParams['PATH_TO_DEAL_KANBANCATEGORY'] = $arParams['PATH_TO_DEAL_KANBANCATEGORY'] ?? '';
		$arParams['HEADER_SECTIONS'] = $arParams['HEADER_SECTIONS'] ?? [];
		$arParams['PATH_TO_IMPORT'] = $arParams['PATH_TO_IMPORT'] ?? '';
		$arParams['~NAME_TEMPLATE'] = $arParams['~NAME_TEMPLATE'] ?? null;

		return $arParams;
	}

	protected function init(): bool
	{
		if (!Loader::includeModule('crm'))
		{
			ShowError(Loc::getMessage('CRM_KANBAN_CRM_NOT_INSTALLED'));

			return false;
		}

		$type = mb_strtoupper($this->arParams['ENTITY_TYPE'] ?? '');
		$params = [
			'NAME_TEMPLATE' => ($this->arParams['~NAME_TEMPLATE'] ?? ''),
			'PATH_TO_IMPORT' => ($this->arParams['PATH_TO_IMPORT'] ?? ''),
			'ONLY_COLUMNS' => ($this->arParams['ONLY_COLUMNS'] ?? 'N'),
			'ONLY_ITEMS' => ($this->arParams['ONLY_ITEMS'] ?? 'N'),
			'VIEW_MODE' => $this->arParams['VIEW_MODE'],
			'CATEGORY_ID' => (int)($this->arParams['EXTRA']['CATEGORY_ID'] ?? 0),
			'USE_ITEM_PLANNER' => ($this->arParams['USE_ITEM_PLANNER'] ?? 'N'),
			'SKIP_COLUMN_COUNT_CHECK' => ($this->componentParams['SKIP_COLUMN_COUNT_CHECK'] ?? 'N'),
			'CUSTOM_SECTION_CODE' => ($this->arParams['EXTRA']['CUSTOM_SECTION_CODE'] ?? null),
		];
		$this->kanban = Kanban\Desktop::getInstance($type, $params);

		if(!$this->getKanban()->isSupported())
		{
			ShowError(Loc::getMessage('CRM_KANBAN_NOT_SUPPORTED'));

			return false;
		}

		$entityTypeId = $this->getEntityTypeId();
		$skipCheckPermissions = $entityTypeId === \CCrmOwnerType::Activity;
		$categoryId = $this->getEntity()->getCategoryId();

		if (!$skipCheckPermissions)
		{
			$isReadPermissions = Container::getInstance()->getUserPermissions()->checkReadPermissions(
				$entityTypeId,
				0,
				$categoryId >= 0 ? $categoryId : null
			);

			if (!$isReadPermissions)
			{
				return false;
			}
		}

		$this->componentParams =  $this->getKanban()->getComponentParams();

		$this->arParams['ENTITY_TYPE_CHR'] = $this->componentParams['ENTITY_TYPE_CHR'];
		$this->arParams['ENTITY_TYPE_INT'] = $this->componentParams['ENTITY_TYPE_INT'];
		$this->arParams['ENTITY_TYPE_INFO'] = $this->componentParams['ENTITY_TYPE_INFO'];
		$this->arParams['IS_DYNAMIC_ENTITY'] = $this->componentParams['IS_DYNAMIC_ENTITY'];
		$this->arParams['ENTITY_PATH'] = $this->componentParams['ENTITY_PATH'];
		$this->arParams['EDITOR_CONFIG_ID'] = $this->componentParams['EDITOR_CONFIG_ID'];
		$this->arParams['HIDE_REST'] = $this->componentParams['HIDE_REST'];
		$this->arParams['REST_DEMO_URL'] = $this->componentParams['REST_DEMO_URL'];

		if($this->arParams['PAGE'] > 1)
		{
			$this->blockPage = $this->arParams['PAGE'];
		}

		if (
			!empty($this->arParams['HEADERS_SECTIONS'])
			&& is_array($this->arParams['HEADERS_SECTIONS'])
		)
		{
			$this->prepareHeaderSections();
		}

		$userId = Container::getInstance()->getContext()->getUserId();
		$this->currentUserID = $userId;
		$this->statusKey = $this->componentParams['STATUS_KEY'];

		$this->arParams['USER_ID'] = $userId;
		$this->arParams['LAYOUT_CURRENT_USER'] = Timeline\Layout\User::current()->toArray();
		$this->arParams['PING_SETTINGS'] = (new TodoPingSettingsProvider(
			$entityTypeId,
			$categoryId
		))->fetchForJsComponent();
		$this->arParams['CURRENCY'] = $this->componentParams['CURRENCY'];
		$this->arParams['PATH_TO_IMPORT'] = $this->componentParams['PATH_TO_IMPORT'];

		$this->application = $GLOBALS['APPLICATION'];

		return true;
	}

	/**
	 * Get stages description for current entity.
	 *
	 * @param boolean $isClear Clear static cache.
	 * @return array
	 */
	protected function getStatuses($isClear = false): array
	{
		return $this->getKanban()->getStatuses($isClear);
	}

	/**
	 * @param int $id
	 * @return array|null
	 */
	protected function getStatusById(int $id): ?array
	{
		$statuses = $this->getStatuses(true);
		return ($statuses[$id] ?? null);
	}

	/**
	 * Get columns.
	 * @param boolean $clear Clear static var.
	 * @param boolean $withoutCache Without static cache.
	 * @param array $params Some additional params.
	 * @return array
	 */
	protected function getColumns($clear = false, $withoutCache = false, array $params = []): array
	{
		$params['ONLY_ITEMS'] = $this->arParams['ONLY_ITEMS'];
		$params['FORCE_FILTER'] = $this->arParams['FORCE_FILTER'];
		$params['VIEW_MODE'] = ($this->arParams['VIEW_MODE'] ?? \Bitrix\Crm\Kanban\ViewMode::MODE_STAGES);

		return $this->getKanban()->getColumns($clear, $withoutCache, $params);
	}

	/**
	 * Base method for getting data.
	 * @param array $filter Additional filter.
	 * @return array
	 */
	protected function getItems(array $filter = []): array
	{
		$params = $this->getKanban()->getItems($filter, $this->blockPage);
		if ($params['RESTRICTED_VALUE_CLICK_CALLBACK'])
		{
			$this->arResult['RESTRICTED_VALUE_CLICK_CALLBACK'] = $params['RESTRICTED_VALUE_CLICK_CALLBACK'];
		}
		return $params['ITEMS'];
	}

	protected function isSkipColumnCountCheck(): bool
	{
		return ($this->arParams['SKIP_COLUMN_COUNT_CHECK'] ?? 'N') === 'Y';
	}

	/**
	 * @param array $fields
	 * @param int $id
	 */
	protected function addParentFields(array &$fields, int $id): void
	{
		if (isset($this->parents[$id]))
		{
			foreach ($this->parents[$id] as $parent)
			{
				if (!empty($parent['code']) && !empty($parent['value']))
				{
					$fields[] = [
						'code' => $parent['code'],
						'title' => $parent['entityDescription'],
						'value' => $parent['value'],
						'type' => 'url',
						'html' => true,
					];
				}
			}
		}
	}

	/**
	 * Get all activities by id's and type of entity.
	 * @param array $entityIds Id's.
	 * @param array $errors Count of errors (deadline).
	 * @param array $incoming Count of incoming.
	 * @return array
	 */
	protected function getActivityCounters($entityIds, &$errors = [], &$incoming = []): array
	{
		$errors ??= [];
		$entityActivityCounters = new Kanban\EntityActivityCounter(
			$this->getEntityTypeId(),
			$entityIds,
			$errors,
		);

		$errors = $entityActivityCounters->getDeadlines();

		if (\Bitrix\Main\Config\Option::get('crm', 'enable_entity_uncompleted_act', 'Y') === 'Y')
		{
			$incoming = $entityActivityCounters->getIncoming();
		}
		else
		{
			$incoming = [];
		}

		return $entityActivityCounters->getCounters();
	}

	protected function getEntityTypeId(): int
	{
		return $this->getEntity()->getTypeId();
	}

	/**
	 * Make some actions (set, update, etc).
	 * @return void
	 */
	protected function processRequestActions(): void
	{
		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();
		$action = $request->get('action');
		$id = $request->get('entity_id');

		// some actions for editor
		if ($action === 'get' && check_bitrix_sessid())
		{
			$ajaxParams = (array)$request->get('ajaxParams');
			if (isset($ajaxParams['editorReset']))
			{
				$this->resetCardFields();
			}
			if (isset($ajaxParams['editorSetCommon']))
			{
				$this->setCommonCardFields();
			}
		}

		//update fields
		if ($action && $id && check_bitrix_sessid())
		{
			$statuses = $this->getStatuses();
			$status = $request->get('status');
			$ids = (is_array($id) ? $id : [$id]);
			// skip action delete
			if ($action === 'status' && $status === static::COLUMN_NAME_DELETED)
			{
				$ids = [];
			}

			foreach ($ids as $id)
			{
				//delete
				if ($action === 'status' && $status === static::COLUMN_NAME_DELETED)
				{
					$this->actionDelete($id);
				}
				//change status / stage
				if ($action === 'status' && isset($statuses[$status]))
				{
					$result = $this->actionUpdateEntityStatus($id, $status, $statuses);
					if (!$result->isSuccess())
					{
						foreach ($result->getErrorMessages() as $errorMessage)
						{
							$this->arResult['ERROR'] = $errorMessage;
						}
					}
					else
					{
						$viewMode = $this->arParams['VIEW_MODE'] ?? '';
						$this->arResult['ITEMS']['isShouldUpdateCard'] = $viewMode === Kanban\ViewMode::MODE_DEADLINES;
					}
				}
			}

			if ($this->needPrepareColumns)
			{
				$this->getColumns(true);
				$this->needPrepareColumns = false;
			}

			if ($this->arParams['IS_AJAX'] !== 'Y')
			{
				$uri = new Uri($request->getRequestUri());
				\LocalRedirect($uri->deleteParams(['action', 'entity_id', 'status'])->getUri());
			}
		}
	}

	/**
	 * Base executable method.
	 * @return array
	 */
	public function executeComponent()
	{
		if (!$this->init())
		{
			return [];
		}

		$this->arResult['ITEMS'] = $this->componentParams['ITEMS'];
		$this->arResult['ADMINS'] = $this->componentParams['ADMINS'];
		$this->arResult['MORE_FIELDS'] = $this->componentParams['MORE_FIELDS'];
		$this->arResult['MORE_EDIT_FIELDS'] = $this->componentParams['MORE_EDIT_FIELDS'];
		$this->arResult['CATEGORIES'] = $this->componentParams['CATEGORIES'];
		$this->arResult['FIELDS_SECTIONS'] = $this->componentParams['FIELDS_SECTIONS'] ?? null;
		//$this->arResult['STUB'] = $this->getStub(); TODO: исправить, когда появятся актуальные тексты
		$this->arResult['SHOW_ERROR_COUNTER_BY_ACTIVITY_RESPONSIBLE'] = $this->showErrorCounterByActivityResponsible();
		$this->arResult['SKIP_COLUMN_COUNT_CHECK'] = $this->isSkipColumnCountCheck();
		$this->arResult['USE_ITEM_PLANNER'] = ($this->arParams['USE_ITEM_PLANNER'] ?? 'N') === 'Y';
		$this->arResult['USE_PUSH_CRM'] = ($this->arParams['USE_PUSH_CRM'] ?? 'Y') === 'Y';
		$this->arResult['PERFORMANCE'] = $this->arParams['PERFORMANCE'] ?? [];

		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();
		$action = $request->get('action');

		//new actions format
		if ($action && is_callable([$this, 'action' . $action]))
		{
			if (!check_bitrix_sessid())
			{
				return [
					'ERROR' => Loc::getMessage('CRM_KANBAN_ERROR_SESSION_EXPIRED'),
					'FATAL' => true,
				];
			}

			return $this->{'action' . $action}();
		}

		$this->processRequestActions();

		if ($this->arParams['EMPTY_RESULT'] !== 'Y')
		{
			$userPermissions = $this->getKanban()->getCurrentUserPermissions();
			$this->arResult['ACCESS_CONFIG_PERMS'] = $this->getKanban()->isCrmAdmin();
			$this->arResult = array_merge($this->arResult, $this->getEntity()->getPermissionParameters($userPermissions));

			//output
			if ($this->arParams['ONLY_COLUMNS'] === 'Y')
			{
				$this->arResult['ITEMS'] = [
					'items' => [],
					'columns' => array_values($this->getColumns()),
				];
			}
			else
			{
				$items = [];
				$columns = $this->getColumns();
				if (!empty($this->arParams['ADDITIONAL_FILTER']))
				{
					$filter = $this->arParams['ADDITIONAL_FILTER'];
					if (isset($filter['COLUMN']))
					{
						$filter[$this->statusKey] = $filter['COLUMN'];
						unset($filter['COLUMN']);
					}
					$items = $this->getItems($filter);
				}
				else
				{
					foreach ($columns as $k => $column)
					{
						if ($column['dropzone'])
						{
							continue;
						}

						if (
							$column['count'] > 0
							|| ($this->isSkipColumnCountCheck() && $column['count'] !== -1)
						)
						{
							$filter = [];
							$filter[$this->statusKey] = $column['id'];
							$items += $this->getItems($filter);
						}

						if ($column['count'] < 0)
						{
							$column['count'] = 0;
						}
					}
				}
				$this->getEntity()->appendMultiFieldData($items, $this->getKanban()->getAllowedFmTypes());
				$this->prepareItemsResult($items);

				$this->arResult['ITEMS'] = array(
					'columns' => array_values($columns),
					'items' => $items
				);

				//get activity
				if (!empty($this->arResult['ITEMS']['items']))
				{
					$itemIds = array_keys($this->arResult['ITEMS']['items']);

					if ($this->getEntity()->isActivityCountersSupported())
					{
						$errors ??= [];
						$entityActivityCounter = new EntityActivityCounter(
							$this->getEntityTypeId(),
							$itemIds,
							$errors,
						);
						$entityActivityCounter->appendToEntityItems($this->arResult['ITEMS']['items']);
						$this->arResult['ITEMS']['activityLimitIsExceeded'] = $entityActivityCounter->isLimitIsExceeded();
					}

					$entityBadges = new Kanban\EntityBadge($this->getEntityTypeId(), $itemIds);
					$entityBadges->appendToEntityItems($this->arResult['ITEMS']['items']);

					$this->arResult['ITEMS']['items'] = array_values($this->arResult['ITEMS']['items']);
				}
			}

			if ($this->getEntity()->isCategoriesSupported())
			{
				$this->arResult['CATEGORIES'] = $this->getEntity()->getCategoriesWithAddPermissions($userPermissions);
			}
		}

		$this->arResult['ITEMS']['last_id'] = $this->getEntity()->getItemLastId();
		$this->arResult['ITEMS']['scheme_inline'] = ($this->componentParams['SCHEME_INLINE'] ?? null);
		$this->arResult['ITEMS']['customFields'] = array_keys($this->arResult['MORE_FIELDS']);

		// items for demo import
		if (
			$this->arParams['IS_AJAX'] !== 'Y'
			&& isset($this->arResult['ITEMS']['columns'][0]['id'], $this->arResult['ITEMS']['columns'][0]['count'])
			&& ($this->blockPage * Kanban\Desktop::BLOCK_SIZE >= $this->arResult['ITEMS']['columns'][0]['count'])
		)
		{
			if ($this->getEntity()->isInlineEditorSupported() && $this->getEntity()->isContactCenterSupported())
			{
				$this->arResult['ITEMS']['items'] = array_merge(
					$this->arResult['ITEMS']['items'],
					[[
						'id' => -1,
						'name' => null,
						'countable' => false,
						'droppable' => true,
						'draggable' => true,
						'columnId' => $this->arResult['ITEMS']['columns'][0]['id'],
						'special_type' => 'import',
					]]
				);
			}
			$this->arResult['ITEMS']['items'] = array_merge(
				$this->arResult['ITEMS']['items'],
				[[
					'id' => -2,
					'name' => null,
					'countable' => false,
					'droppable' => true,
					'draggable' => true,
					'columnId' => $this->arResult['ITEMS']['columns'][0]['id'],
					'special_type' => 'rest',
				]]
			);
		}

		$this->arResult['CONFIG_BY_VIEW_MODE'] = $this->getConfigByViewMode();

		PullManager::getInstance()->subscribeOnKanbanUpdate(
			$this->arParams['ENTITY_TYPE'],
			($this->arParams['EXTRA'] ?? null)
		);

		if ($this->arParams['IS_AJAX'] === 'Y')
		{
			return $this->arResult;
		}

		$entity = $this->getEntity();
		$this->arResult['RESTRICTED_FIELDS_ENGINE'] = $entity->getFieldsRestrictionsEngine();
		$this->arResult['RESTRICTED_FIELDS'] = $entity->getFieldsRestrictions();
		$this->arResult['SORT_SETTINGS'] = $entity->getSortSettings();
		$this->arResult['IS_LAST_ACTIVITY_ENABLED'] = $entity->isLastActivityEnabled();

		$GLOBALS['APPLICATION']->setTitle($entity->getTitle());
		$this->IncludeComponentTemplate();
	}

	protected function prepareItemsResult(array &$items): void
	{
		$isOpenLinesInstalled = \Bitrix\Main\ModuleManager::isModuleInstalled('imopenlines');
		if ($isOpenLinesInstalled)
		{
			foreach ($items as &$item)
			{
				if (isset($item['im']) && is_array($item['im']))
				{
					$item['im'] = $this->getOpenLine($item['im']);
				}
			}
		}
	}

	protected function getOpenLine($openLines): string
	{
		$im = "";
		foreach ($openLines as $val)
		{
			if (isset($val['value']) && (mb_strpos($val['value'], 'imol|') === 0))
			{
				$im = $val['value'];
				break;
			}
			elseif (empty($im) && is_array($val))
			{
				return $this->getOpenLine($val);
			}
		}

		return $im;
	}

	protected function getConfigByViewMode(): array
	{
		$viewMode = $this->arParams['VIEW_MODE'];

		if (\Bitrix\Crm\Kanban\ViewMode::isDatesBasedView($viewMode))
		{
			$canChangeColumns = 'false';
			return [
				'accessConfigPerms' => false,
				'canAddColumn' => $canChangeColumns,
				'canEditColumn'=> $canChangeColumns,
				'canRemoveColumn' => $canChangeColumns,
				'canSortColumn' => $canChangeColumns,
				'canChangeItemStage' => 'true',
				'columnsRevert' => 'true',
			];
		}

		$demoAccess = \CJSCore::IsExtRegistered('intranet_notify_dialog') && ModuleManager::isModuleInstalled('im');
		$canChangeColumns = (($this->arResult['ACCESS_CONFIG_PERMS'] ?? null ) ? 'true' : 'false');

		return [
			'accessConfigPerms' => $this->arResult['ACCESS_CONFIG_PERMS'] ?? null,
			'canAddColumn' => ($demoAccess ? 'true' : $canChangeColumns),
			'canEditColumn'=> ($demoAccess ? 'true' : $canChangeColumns),
			'canRemoveColumn' => $canChangeColumns,
			'canSortColumn' => $canChangeColumns,
			'canChangeItemStage' => 'true',
			'columnsRevert' => 'false',
		];
	}

	/**
	 * Get request-var for http-hit.
	 * @param string $var Request-var code.
	 * @return mixed
	 */
	protected function request($var)
	{
		static $request = null;

		if ($request === null)
		{
			$context = Application::getInstance()->getContext();
			$request = $context->getRequest();
		}

		return $request->get($var);
	}

	/**
	 * Notify admin for get access.
	 * @return array
	 */
	protected function actionNotifyAdmin(): array
	{
		$userId = $this->request('userId');
		if ($userId && Loader::includeModule('im'))
		{
			$admins = $this->componentParams['ADMINS'];
			if (isset($admins[$userId]))
			{
				$pathColumnEdit = '/crm/configs/status/?ACTIVE_TAB=status_tab_' . $this->getKanban()->getEntity()->getStatusEntityId();
				$notifyMessageCallback = static fn (?string $languageId = null) =>
					Loc::getMessage(
						'CRM_ACCESS_NOTIFY_MESSAGE',
						[ '#URL#' => $pathColumnEdit ],
						$languageId,
					)
				;

				\CIMNotify::Add([
					'TO_USER_ID' => $userId,
					'FROM_USER_ID' => $this->currentUserID,
					'NOTIFY_TYPE' => IM_NOTIFY_FROM,
					'NOTIFY_MODULE' => 'crm',
					'NOTIFY_TAG' => 'CRM|NOTIFY_ADMIN|' . $userId . '|' . $this->currentUserID,
					'NOTIFY_MESSAGE' => $notifyMessageCallback,
				]);
			}
		}

		return [
			'status' => 'success',
		];
	}

	/**
	 * Add new stage, update stage, move stage.
	 * @return array
	 */
	protected function actionModifyStage(): array
	{
		if (!$this->getKanban()->isCrmAdmin())
		{
			return [];
		}
		// vars
		$stages = $this->getColumns(
			true,
			true,
			[
				'originalColumns' => true,
			]
		);
		$delete = $this->request('delete');
		$columnId = $this->request('columnId');
		$columnName = $this->request('columnName');
		$columnColor = $this->request('columnColor');
		$afterColumnId = $this->request('afterColumnId');

		$sort = 0;
		$semanticId = null;

		if ($afterColumnId !== null && (string)$afterColumnId === '0')
		{
			$sort = 0;
		}
		elseif (isset($stages[$afterColumnId]))
		{
			$sort = $stages[$afterColumnId]['real_sort'];
			if (($stages[$afterColumnId]['type'] ?? '') == 'LOOSE')
			{
				$semanticId = \Bitrix\Crm\PhaseSemantics::FAILURE;
			}
		}
		elseif (isset($stages[$columnId]))
		{
			$sort = $stages[$columnId]['real_sort'];
		}

		if ($columnName)
		{
			$columnName = $this->request('columnName');
		}

		$fields = [
			'ENTITY_ID' => $this->getEntity()->getStatusEntityId(),
			'SORT' => ++$sort,
		];
		if ($semanticId)
		{
			$fields['SEMANTICS'] = $semanticId;
		}

		if ($columnName)
		{
			$fields['NAME'] = $columnName;
			$fields['NAME_INIT'] = $columnName;
		}
		if ($columnColor)
		{
			$fields['COLOR'] = $columnColor;
		}

		$fields['CATEGORY_ID'] = (
			$this->getEntity()->getCategoryId() > 0
				? $this->getEntity()->getCategoryId()
				: null
		);
		//todo move to entity
		$isOrder = ($this->getEntity()->getTypeName() === \CCrmOwnerType::OrderName);
		if ($columnId !== '' && isset($stages[$columnId]))
		{
			if ($delete)
			{
				$result = $this->deleteStage($columnId, $stages);
			}
			else
			{
				$internalId = ($isOrder ? $columnId : $stages[$columnId]['real_id']);
				$result = $this->updateStage($internalId, $fields, ['STATUS_ID' => $columnId]);
			}
		}
		else
		{
			$result = $this->addStage($fields);
			$internalId = $result->getId();
		}
		if ($result->isSuccess())
		{
			if($delete)
			{
				return [];
			}
			if ($isOrder)
			{
				$statusId = $internalId;
			}
			else
			{
				$statusEntityId = $this->getEntity()->getStatusEntityId();
				$status = new \CCrmStatus($statusEntityId);
				$newStatus = $status->GetStatusById($internalId);
				$statusId = $newStatus['STATUS_ID'];
			}

			$stages = $this->getColumns(
				true,
				true,
				[
					'originalColumns' => true,
				]
			);
			if (isset($stages[$statusId]))
			{
				// range sorts
				$sort = 10;
				foreach ($stages as $stage)
				{
					$internalId = ($isOrder ? $stage['id'] : $stage['real_id']);
					$this->updateStage($internalId, ['SORT' => $sort]);
					$sort += 10;
				}

				return \htmlspecialcharsback($stages[$statusId]);
			}

			return [
				'ERROR' => 'Unknown error',
			];
		}

		$errors = $result->getErrorMessages();

		return [
			'ERROR' => current($errors),
		];
	}

	/**
	 * Delete status stage for 'actionModifyStage' method
	 *
	 * @param $columnId
	 * @param $stages
	 */
	private function deleteStage($columnId, $stages)
	{
		$result = new Result();

		if ($stages[$columnId]['type'] === 'WIN')
		{
			$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_STAGE_IS_WIN')));
			return $result;
		}

		if (!$this->getEntity()->isStageEmpty($columnId))
		{
			$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_STAGE_IS_NOT_EMPTY')));
			return $result;
		}

		$statusEntityId = $this->getEntity()->getStatusEntityId();
		if (empty($statusEntityId))
		{
			return $result;
		}

		//todo move to entity
		if ($this->getEntity()->getTypeName() === \CCrmOwnerType::OrderName)
		{
			$defaultStatuses = Order\OrderStatus::getDefaultStatuses();
			if (isset($defaultStatuses[$columnId]))
			{
				$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_STAGE_IS_SYSTEM')));
				return $result;
			}

			if (!Loader::includeModule('sale'))
			{
				return $result;
			}

			$result = Sale\Internals\StatusTable::delete($columnId);
			if ($result->isSuccess())
			{
				$statusLanguageRaw = Sale\Internals\StatusLangTable::getList([
					'filter' => ['STATUS_ID' => $columnId],
					'select' => [
						'STATUS_ID',
						'LID',
					],
				]);

				while ($langPrimary = $statusLanguageRaw->fetch())
				{
					Sale\Internals\StatusLangTable::delete($langPrimary);
				}
			}
		}
		else
		{
			$internalId = $stages[$columnId]['real_id'];
			$statusObject = new \CCrmStatus($statusEntityId);
			$statusInfo = $statusObject->GetStatusById($internalId);
			if ($statusInfo['SYSTEM'] === 'Y')
			{
				$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_STAGE_IS_SYSTEM')));
				return $result;
			}

			$statusObject->delete($internalId);
			if (!empty($statusObject->GetLastError()))
			{
				$result->addError(new Error($statusObject->GetLastError()));
			}
			else
			{
				$item = Kanban\Entity::getInstance($this->getEntity()->getTypeName())
					->createPullStage($statusInfo);

				PullManager::getInstance()->sendStageDeletedEvent(
					$item,
					[
						'TYPE' => $this->getEntity()->getTypeName(),
						'CATEGORY_ID' => (int)($this->arParams['EXTRA']['CATEGORY_ID'] ?? 0),
					]
				);
			}
		}

		return $result;
	}

	/**
	 * Save changes into status stage for 'actionModifyStage' method
	 *
	 * @param $id
	 * @param array $fields
	 * @param array|null $params
	 * @return Result
	 */
	private function updateStage($id, array $fields = [], ?array $params = []): Result
	{
		$result = new Result();

		//todo move to entity
		if ($this->getEntity()->getTypeName() !== \CCrmOwnerType::OrderName)
		{
			$statusEntityId = $this->getEntity()->getStatusEntityId();
			$status = new \CCrmStatus($statusEntityId);
			$status->update($id, $fields);
			if (!empty($status->GetLastError()))
			{
				$result->addError(new Error($status->GetLastError()));
			}
			else if (isset($params['STATUS_ID']))
			{
				$data = array_merge($fields, $params);
				$item = Kanban\Entity::getInstance($this->getEntity()->getTypeName())
					->createPullStage($data);

				PullManager::getInstance()->sendStageUpdatedEvent(
					$item,
					[
						'TYPE' => $this->getEntity()->getTypeName(),
						'CATEGORY_ID' => (int)($this->arParams['EXTRA']['CATEGORY_ID'] ?? 0),
					]
				);
			}
		}
		else
		{
			if (!Loader::includeModule('sale'))
			{
				return $result;
			}

			$updateFields = array_intersect_key($fields, array_flip(['SORT', 'COLOR']));
			if (!empty($updateFields['COLOR']))
			{
				$updateFields['COLOR'] = "#".$updateFields['COLOR'];
			}
			if (!empty($updateFields))
			{
				$result = Sale\Internals\StatusTable::update($id, $updateFields);
			}
			if (!empty($fields['NAME']) && $result->isSuccess())
			{
				Sale\Internals\StatusLangTable::update(
					[
						'STATUS_ID' => $id,
						'LID' => $this->getLanguageId(),
					],
					['NAME' => $fields['NAME']]
				);
			}
		}

		return $result;
	}

	/**
	 * Add new status stage
	 *
	 * @param array $fields
	 * @return AddResult
	 */
	private function addStage(array $fields = []): AddResult
	{
		$result = new AddResult();

		//todo move to entity
		if ($this->getEntity()->getTypeName() !== \CCrmOwnerType::OrderName)
		{
			$statusEntityId = $this->getEntity()->getStatusEntityId();
			$status = new \CCrmStatus($statusEntityId);
			$id = $status->add($fields);
			if (!empty($status->GetLastError()))
			{
				$result->addError(new Error($status->GetLastError()));
			}
			else
			{
				$result->setId($id);
			}
		}
		else
		{
			if (!Loader::includeModule('sale'))
			{
				return $result;
			}

			$createFields = array_intersect_key($fields, array_flip(['SORT', 'COLOR']));

			if(!empty($createFields['COLOR']) && mb_strpos($createFields['COLOR'], '#') !== 0)
			{
				$createFields['COLOR'] = '#' . $createFields['COLOR'];
			}

			$orderStatusIds = [];
			$statusRaw = Order\OrderStatus::getList([
				'select' => ['ID'],
			]);
			while ($data = $statusRaw->fetch())
			{
				$orderStatusIds[$data['ID']] = $data;
			}

			do
			{
				$newId = chr(random_int(65, 90)); //A-Z
				if (is_array($result) && count($result) >= 27)
				{
					$newId .= chr(random_int(65, 90));
				}
			}
			while (isset($orderStatusIds[$newId]));
			$createFields['ID'] = $newId;
			$createFields['TYPE'] = Order\OrderStatus::TYPE;
			$result = Sale\Internals\StatusTable::add($createFields);
			if ($result->isSuccess())
			{
				Sale\Internals\StatusLangTable::add([
					'STATUS_ID' => $result->getId(),
					'LID' => $this->getLanguageId(),
					'NAME' => $fields['NAME']
				]);
			}
		}
		if($result->isSuccess())
		{
			$item = Kanban\Entity::getInstance($this->getEntity()->getTypeName())
				->createPullStage($fields);

			PullManager::getInstance()->sendStageAddedEvent(
				$item,
				[
					'TYPE' => $this->getEntity()->getTypeName(),
					'CATEGORY_ID' => (int)($this->arParams['EXTRA']['CATEGORY_ID'] ?? 0),
				]
			);
		}

		return $result;
	}

	/**
	 * Remove all private cards, for setting common.
	 * @return void
	 */
	protected function setCommonCardFields(): void
	{
		$this->getKanban()->removeUserAdditionalSelectFields();
	}

	/**
	 * Reset fields in card.
	 * @return void
	 */
	protected function resetCardFields(): void
	{
		$this->arResult['MORE_FIELDS'] = $this->getKanban()->resetCardFields();
	}

	/**
	 * Save additional fields for card.
	 * @return array
	 */
	protected function actionSaveFields(): array
	{
		$type = (string) $this->request('type');
		$fields = $this->request('fields');

		return $this->getEntity()->saveAdditionalFields($fields, $type, $this->getKanban()->canEditSettings());
	}

	/**
	 * Delete entity.
	 * @param int|array $ids Optionally id for delete.
	 * @return array
	 */
	protected function actionDelete($ids = null): array
	{
		$ids = ($ids ?: $this->request('id'));
		$ids = (array)$ids;

		if (empty($ids))
		{
			return [];
		}

		$ignore = ($this->request('ignore') === 'Y');

		$params = [
			'eventId' => $this->request->getPost('eventId'),
		];

		try
		{
			$result = $this->getEntity()->deleteItemsV2($ids, $ignore, $this->getKanban()->getCurrentUserPermissions(), $params);
		}
		catch (\Exception $exception)
		{
			return [
				'error' => $exception->getMessage(),
			];
		}

		$data = array_merge($result->getData(), ['errors' => []]);

		/** @var Error $error */
		foreach ($result->getErrorCollection() as $error)
		{
			$data['errors'][] = [
				'message' => $error->getMessage(),
				'data' => $error->getCustomData(),
			];
		}

		return $data;
	}

	/**
	 * Update statuses
	 *
	 * @param $id
	 * @param $status
	 * @param array $statuses
	 *
	 * @return Result
	 */
	private function actionUpdateEntityStatus($id, $status, array $statuses = [])
	{
		$userPerms = $this->getKanban()->getCurrentUserPermissions();
		$request = Application::getInstance()->getContext()->getRequest();

		$result = new Result();

		if (!\CCrmPerms::IsAuthorized())
		{
			return $result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_ACCESS_DENIED')));
		}

		$item = $this->getEntity()->getItem($id, [$this->getEntity()->getDbStageFieldName()]);
		if(!$item)
		{
			return $result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_ACCESS_DENIED')));
		}

		if(!$this->getEntity()->checkUpdatePermissions($id, $userPerms))
		{
			return $result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_ACCESS_DENIED')));
		}

		if ($this->getEntity()->getSortSettings()->isUserSortSupported())
		{
			Kanban\SortTable::setPrevious([
				'ENTITY_TYPE_ID' => $this->getEntity()->getTypeName(),
				'ENTITY_ID' => $id,
				'PREV_ENTITY_ID' => $request->get('prev_entity_id')
			]);

			// remember last id
			$this->getKanban()->rememberLastId($this->getEntity()->getItemLastId());
		}

		$statusKey = $this->statusKey;
		$isStatusChanged = (($item[$statusKey] ?? null) !== $status);
		if(!$isStatusChanged)
		{
			return $result;
		}

		$ajaxParamsName = ((int) $request->getPost('version') === 2) ? 'ajaxParams' : 'status_params';
		$newStateParams = (array)$request->getPost($ajaxParamsName);

		$eventId = $request->getPost('eventId');
		if ($eventId)
		{
			$newStateParams['eventId'] = $eventId;
		}

		$result = $this->getEntity()->updateItemStage(
			$id,
			$status,
			$newStateParams,
			$statuses
		);
		if(!$result->isSuccess())
		{
			return $result;
		}

		// order synchronization
		if ($this->getEntity()->getTypeId() === \CCrmOwnerType::Deal)
		{
			(new \Bitrix\Crm\Order\OrderDealSynchronizer)->updateOrderFromDeal($id);
		}

		$this->needPrepareColumns = true;

		return $result;
	}

	/**
	 * Refresh deals accounts.
	 * @return array
	 */
	protected function actionRefreshAccount(): array
	{
		$ids = $this->request('id');
		$ids = (array)$ids;
		if(empty($ids) || $this->getEntity()->getTypeName() !== \CCrmOwnerType::DealName)
		{
			return [];
		}
		$idForUpdate = [];
		$userPermissions = $this->getKanban()->getCurrentUserPermissions();
		foreach ($ids as $id)
		{
			$categoryId = $this->getEntity()->getCategoryId();
			if (\CCrmDeal::checkUpdatePermission($id, $userPermissions, $categoryId))
			{
				$idForUpdate[] = $id;
			}
		}
		if (!empty($idForUpdate))
		{
			\CCrmDeal::refreshAccountingData($idForUpdate);
		}

		return [];
	}

	/**
	 * Set assigned id.
	 * @return array
	 */
	protected function actionSetAssigned(): array
	{
		$ids = (array)$this->request('ids');
		$assignedId = (int)$this->request('assignedId');
		if($assignedId <= 0 || empty($ids))
		{
			return [];
		}
		$userPerms = $this->getKanban()->getCurrentUserPermissions();

		$this->getEntity()->setItemsAssigned($ids, $assignedId, $userPerms);

		return [];
	}

	/**
	 * Make open / close.
	 * @return array
	 */
	protected function actionOpen(): array
	{
		$ids = (array)$this->request('id');
		if(empty($ids))
		{
			return [];
		}
		$isOpened = ($this->request('flag') === 'Y');

		$this->getEntity()->updateItemsOpened($ids, $isOpened);

		return [];
	}

	/**
	 * Change category of deals.
	 * @return array
	 */
	protected function actionChangeCategory(): array
	{
		$ids = (array)$this->request('id');
		if(empty($ids))
		{
			return [];
		}
		$categoryId = (int)$this->request('category');
		$userPermissions = $this->getKanban()->getCurrentUserPermissions();
		$result = $this->getEntity()->updateItemsCategory($ids, $categoryId, $userPermissions);

		if (!$result->isSuccess())
		{
			$errorMessages = $result->getErrorMessages();

			return [
				'ERROR' => reset($errorMessages),
			];
		}

		return [];
	}

	/**
	 * Show or hide contact center block in option.
	 * @return array
	 */
	protected function actionToggleCC(): array
	{
		$hidden = \CUserOptions::getOption(
			static::OPTION_CATEGORY,
			static::OPTION_NAME_HIDE_CONTACT_CENTER,
			false
		);
		\CUserOptions::setOption(
			static::OPTION_CATEGORY,
			static::OPTION_NAME_HIDE_CONTACT_CENTER,
			!$hidden
		);
		return [];
	}

	/**
	 * Show or hide REST demo block in option.
	 * @return array
	 */
	protected function actionToggleRest(): array
	{
		$hidden = \CUserOptions::getOption(
			static::OPTION_CATEGORY,
			static::OPTION_NAME_HIDE_REST_DEMO,
			false
		);
		\CUserOptions::setOption(
			static::OPTION_CATEGORY,
			static::OPTION_NAME_HIDE_REST_DEMO,
			!$hidden
		);
		return [];
	}

	protected function actionSetCurrentSortType($sortType = null): array
	{
		$sortType = $sortType ?: $this->request('sortType');

		$result = $this->getEntity()->setCurrentSortType((string)$sortType);
		if (!$result->isSuccess())
		{
			$errorMessages = $result->getErrorMessages();

			return [
				'ERROR' => reset($errorMessages),
			];
		}

		return [];
	}

	/**
	 * @param int[][] $crmEntities entityAbbreviation => [array of entityId]
	 *
	 * @return string[][] entityAbbreviation => [array of formatted html strings]
	 */
	protected function getLinksToCrmEntities(array $crmEntities): array
	{
		$result = [];

		$typesMap = Container::getInstance()->getTypesMap();
		$router = Container::getInstance()->getRouter();

		foreach ($crmEntities as $entityAbbreviation => $entityIds)
		{
			$entityTypeId = \CCrmOwnerTypeAbbr::ResolveTypeID($entityAbbreviation);

			$factory = $typesMap->getFactory($entityTypeId);
			// for example, $entityName = 'Deal';
			$entityName = mb_convert_case(\CCrmOwnerType::ResolveName($entityTypeId), MB_CASE_TITLE);
			$dataManager = $factory ? $factory->getDataClass() : ('\Bitrix\Crm\\' . $entityName . 'Table');

			if (!class_exists($dataManager) || !is_a($dataManager, DataManager::class, true))
			{
				continue;
			}

			$select = ['ID', 'TITLE'];
			if ($entityTypeId === \CCrmOwnerType::Contact)
			{
				$select = ['ID', 'NAME', 'LAST_NAME'];
			}

			/** @var array[] $rows */
			$rows = $dataManager::getList([
				'select' => $select,
				'filter' => [
					'@ID' => $entityIds,
				],
			])->fetchAll();

			foreach ($rows as $row)
			{
				$id = (int)$row['ID'];
				unset($row['ID']);

				// implode because in case of contact there are 2 strings in the array
				$title = implode(' ', $row);

				if (empty($title) && $factory)
				{
					$title = htmlspecialcharsbx($factory->getEntityDescription() . ' #' . $id);
				}

				$detailsUrl = $router->getItemDetailUrl($entityTypeId, $id);

				$result[$entityAbbreviation][$id] =
					'<a href="' . $detailsUrl . '">' . htmlspecialcharsbx($title) . '</a>'
				;
			}
		}

		return $result;
	}

	/**
	 * @return Kanban\Entity
	 * @throws Kanban\EntityNotFoundException
	 */
	protected function getEntity(): Kanban\Entity
	{
		return $this->getKanban()->getEntity();
	}

	/**
	 * @return Kanban
	 */
	protected function getKanban(): Kanban
	{
		return $this->kanban;
	}

	protected function prepareHeaderSections(): void
	{
		foreach($this->arParams['HEADERS_SECTIONS'] as $section)
		{
			$this->arResult['HEADERS_SECTIONS'][$section['id']] = $section;
			if (!empty($section['default']))
			{
				$this->arResult['DEFAULT_HEADER_SECTION_ID'] = $section['id'];
			}
		}
	}

	private function getStub(): array
	{
		$type = $this->getEntity()->getTypeName();

		// TODO: исправить, когда появятся актуальные тексты
		if ($type === CCrmOwnerType::LeadName)
		{
			return [
				'title' => Loc::getMessage('CRM_KANBAN_TITLE_LEAD'),
				'description' => Loc::getMessage('CRM_KANBAN_NO_DATA_TEXT')
			];
		}

		if ($type === CCrmOwnerType::DealName)
		{
			return [
				'title' => Loc::getMessage('CRM_KANBAN_TITLE_DEAL'),
				'description' => Loc::getMessage('CRM_KANBAN_NO_DATA_TEXT')
			];
		}

		if ($type === CCrmOwnerType::InvoiceName)
		{
			return [
				'title' => Loc::getMessage('CRM_KANBAN_TITLE_INVOICE'),
				'description' => Loc::getMessage('CRM_KANBAN_NO_DATA_TEXT')
			];
		}

		if ($type === CCrmOwnerType::QuoteName)
		{
			return [
				'title' => Loc::getMessage('CRM_KANBAN_TITLE_QUOTE_MSGVER_1'),
				'description' => Loc::getMessage('CRM_KANBAN_NO_DATA_TEXT')
			];
		}

		return [
			'title' => '',
			'description' => Loc::getMessage('CRM_KANBAN_NO_DATA_TEXT')
		];
	}

	private function showErrorCounterByActivityResponsible(): bool
	{
		return CounterSettings::getInstance()->useActivityResponsible();
	}
}
