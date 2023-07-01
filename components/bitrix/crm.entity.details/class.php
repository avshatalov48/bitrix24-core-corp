<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

CModule::IncludeModule("crm");

use Bitrix\Crm\Component\EntityDetails\Traits\EditorInitialMode;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CCrmEntityPopupComponent extends CBitrixComponent
{
	use EditorInitialMode;

	/** @var string */
	private $guid = '';
	/** @var int */
	private $entityTypeID = CCrmOwnerType::Undefined;
	/** @var int */
	private $entityID = 0;
	/** @var array|null  */
	private $extras = null;
	/** @var array|null  */
	private $entityInfo = null;

	/** @var  CCrmPerms|null */
	private $userPermissions = null;
	/** @var bool */
	private $isPermitted = false;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->userPermissions = CCrmPerms::GetCurrentUserPermissions();
	}

	public function getEntityId(): int
	{
		return $this->entityID;
	}

	public function executeComponent()
	{
		$this->entityTypeID = isset($this->arParams['~ENTITY_TYPE_ID'])
			? (int)$this->arParams['~ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
		$this->entityID = isset($this->arParams['~ENTITY_ID'])
			? (int)$this->arParams['~ENTITY_ID'] : 0;
		$this->extras = isset($this->arParams['~EXTRAS']) && is_array($this->arParams['~EXTRAS'])
			? $this->arParams['~EXTRAS'] : array();
		$this->entityInfo = isset($this->arParams['~ENTITY_INFO']) && is_array($this->arParams['~ENTITY_INFO'])
			? $this->arParams['~ENTITY_INFO'] : array();

		if(isset($this->arParams['~GUID']))
		{
			$this->guid = $this->arResult['~GUID'] = $this->arParams['~GUID'];
		}
		else
		{
			$this->guid = $this->arResult['~GUID'] = mb_strtolower(CCrmOwnerType::ResolveName($this->entityTypeID)).'_'.$this->entityID;
		}

		$this->arResult['READ_ONLY'] = isset($this->arParams['~READ_ONLY'])
			&& $this->arParams['~READ_ONLY'] === true;

		$this->arResult['ENABLE_PROGRESS_BAR'] = isset($this->arParams['~ENABLE_PROGRESS_BAR'])
			&& $this->arParams['~ENABLE_PROGRESS_BAR'] === true;

		$this->arResult['ENABLE_STAGEFLOW'] = $this->arParams['EDITOR']['ENABLE_STAGEFLOW'] ?? false;

		$this->arResult['ENABLE_PROGRESS_CHANGE'] = isset($this->arParams['~ENABLE_PROGRESS_CHANGE'])
			? (bool)$this->arParams['~ENABLE_PROGRESS_CHANGE'] : !$this->arResult['READ_ONLY'];

		$this->arResult['CAN_CONVERT'] = isset($this->arParams['~CAN_CONVERT'])
			? (bool)$this->arParams['~CAN_CONVERT'] : false;

		$this->arResult['CONVERSION_TYPE_ID'] = isset($this->arParams['~CONVERSION_TYPE_ID'])
			? $this->arParams['~CONVERSION_TYPE_ID'] : 0;

		$this->arResult['CONVERSION_SCHEME'] = isset($this->arParams['~CONVERSION_SCHEME'])
			? $this->arParams['~CONVERSION_SCHEME'] : array();

		$this->arResult['MESSAGES'] = $this->arParams['MESSAGES'] ?? [];

		$this->isPermitted = \Bitrix\Crm\Security\EntityAuthorization::checkReadPermission(
			$this->entityTypeID,
			$this->entityID,
			$this->userPermissions
		);

		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeID;
		$this->arResult['ENTITY_TYPE_NAME'] = CCrmOwnerType::ResolveName($this->entityTypeID);
		$this->arResult['ENTITY_ID'] = $this->entityID;
		$this->arResult['ENTITY_INFO'] = $this->entityInfo;
		$this->arResult['EXTRAS'] = $this->extras;

		$this->arResult['EDITOR'] = isset($this->arParams['~EDITOR']) && is_array($this->arParams['~EDITOR']) ? $this->arParams['~EDITOR'] : array();
		$this->arResult['TIMELINE'] = isset($this->arParams['~TIMELINE']) && is_array($this->arParams['~TIMELINE']) ? $this->arParams['~TIMELINE'] : array();
		$this->arResult['PROGRESS_BAR'] = isset($this->arParams['~PROGRESS_BAR']) && is_array($this->arParams['~PROGRESS_BAR']) ? $this->arParams['~PROGRESS_BAR'] : array();

		$this->arResult['IS_PERMITTED'] = $this->isPermitted;

		$this->arResult['TABS'] = isset($this->arParams['TABS']) && is_array($this->arParams['TABS'])
			? $this->arParams['TABS'] : array();

		$this->arResult['TABS'] = $this->updateTabsByEvent($this->arResult['TABS']);

		// region rest placement
		$this->arResult['REST_USE'] = false;
		if(
			Main\Loader::includeModule('rest')
			&& ($this->arParams['REST_USE'] ?? null) !== 'N'
		)
		{
			$this->arResult['REST_USE'] = true;
			\CJSCore::Init(array('applayout'));

			$placement = \Bitrix\Crm\Integration\Rest\AppPlacement::getDetailTabPlacementCode($this->entityTypeID);
			$placementHandlerList = \Bitrix\Rest\PlacementTable::getHandlersList($placement);

			if(count($placementHandlerList) > 0)
			{
				foreach($placementHandlerList as $placementHandler)
				{
					$this->arResult['TABS'][] = array(
						'id' => 'tab_rest_'.$placementHandler['ID'],
						'name' => $placementHandler['TITLE'] <> ''
							? $placementHandler['TITLE']
							: $placementHandler['APP_NAME'],
						'enabled' => true,
						'loader' => array(
							'serviceUrl' => '/bitrix/components/bitrix/app.layout/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
							'componentData' => array(
								'template' => '',
								'params' => array(
									'PLACEMENT' => $placement,
									'PLACEMENT_OPTIONS' => array(
										'ID' => $this->entityID,
									),
									'ID' => $placementHandler['APP_ID'],
									'PLACEMENT_ID' => $placementHandler['ID'],
								),
							)
						)
					);
				}

			}

			$this->arResult['REST_PLACEMENT_CONFIG'] = array('PLACEMENT' => $placement);
		}
		// endregion

		foreach ($this->arResult['TABS'] as &$tab)
		{
			if (is_array($tab) && isset($tab['id']) && is_string($tab['id']))
			{
				$tab['id'] = mb_strtolower($tab['id']);
			}
		}

		$this->arResult['INITIAL_MODE'] = $this->getInitialMode();
		$this->arResult['GUID'] = $this->guid;
		$this->arResult['ACTIVITY_EDITOR_ID'] = $this->arParams['~ACTIVITY_EDITOR_ID'] ?? '';
		$this->arResult['SERVICE_URL'] = $this->arParams['~SERVICE_URL'] ?? '';

		$this->arResult['PATH_TO_QUOTE_EDIT'] = CrmCheckPath(
			'PATH_TO_QUOTE_EDIT',
			$this->arParams['PATH_TO_QUOTE_EDIT'] ?? '',
			''
		);
		$this->arResult['PATH_TO_INVOICE_EDIT'] = CrmCheckPath(
			'PATH_TO_INVOICE_EDIT',
			$this->arParams['PATH_TO_INVOICE_EDIT'] ?? '',
			''
		);
		$this->arResult['PATH_TO_ORDER_EDIT'] = CrmCheckPath(
			'PATH_TO_ORDER_EDIT',
			$this->arParams['PATH_TO_ORDER_EDIT'] ?? '',
			''
		);
		$this->arResult['PATH_TO_ORDER_SHIPMENT_EDIT'] = CrmCheckPath(
			'PATH_TO_ORDER_SHIPMENT_EDIT',
			$this->arParams['PATH_TO_ORDER_SHIPMENT_EDIT'] ?? '',
			''
		);
		$this->arResult['PATH_TO_ORDER_PAYMENT_EDIT'] = CrmCheckPath(
			'PATH_TO_ORDER_PAYMENT_EDIT',
			$this->arParams['PATH_TO_ORDER_PAYMENT_EDIT'] ?? '',
			''
		);
		$this->arResult['TODO_CREATE_NOTIFICATION_PARAMS'] = $this->getTodoCreateNotificationParams();

		$this->arResult['ENTITY_CREATE_URLS'] = array(
			\CCrmOwnerType::DealName =>
				\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Deal, 0, false),
			\CCrmOwnerType::LeadName =>
				\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Lead, 0, false),
			\CCrmOwnerType::CompanyName =>
				\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Company, 0, false),
			\CCrmOwnerType::ContactName =>
				\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Contact, 0, false),
			\CCrmOwnerType::QuoteName =>
				\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Quote, 0, false),
			\CCrmOwnerType::InvoiceName =>
				\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Invoice, 0, false),
			\CCrmOwnerType::OrderName =>
				\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Order, 0, false),
			\CCrmOwnerType::OrderShipmentName =>
				CComponentEngine::MakePathFromTemplate($this->arResult['PATH_TO_ORDER_SHIPMENT_EDIT'], array('shipment_id' => 0)),
			\CCrmOwnerType::OrderPaymentName =>
				CComponentEngine::MakePathFromTemplate($this->arResult['PATH_TO_ORDER_PAYMENT_EDIT'], array('payment_id' => 0))
		);

		$this->arResult['ENTITY_LIST_URLS'] = array(
			\CCrmOwnerType::DealName =>
				\CCrmOwnerType::GetListUrl(\CCrmOwnerType::Deal, false),
			\CCrmOwnerType::LeadName =>
				\CCrmOwnerType::GetListUrl(\CCrmOwnerType::Lead, false),
			\CCrmOwnerType::CompanyName =>
				\CCrmOwnerType::GetListUrl(\CCrmOwnerType::Company, false),
			\CCrmOwnerType::ContactName =>
				\CCrmOwnerType::GetListUrl(\CCrmOwnerType::Contact, false),
			\CCrmOwnerType::QuoteName =>
				\CCrmOwnerType::GetListUrl(\CCrmOwnerType::Quote, false),
			\CCrmOwnerType::InvoiceName =>
				\CCrmOwnerType::GetListUrl(\CCrmOwnerType::Invoice, false),
			\CCrmOwnerType::OrderName =>
				\CCrmOwnerType::GetListUrl(\CCrmOwnerType::Order, false),
			\CCrmOwnerType::OrderShipmentName =>
				\CCrmOwnerType::GetListUrl(\CCrmOwnerType::OrderShipment, false),
		);

		//region Deal Categories
		$this->arResult['DEAL_CATEGORY_ACCESS'] = array(
			'CREATE' => \CCrmDeal::GetPermittedToCreateCategoryIDs($this->userPermissions),
			'READ' => \CCrmDeal::GetPermittedToReadCategoryIDs($this->userPermissions),
			'UPDATE' => \CCrmDeal::GetPermittedToUpdateCategoryIDs($this->userPermissions)
		);
		//endregion

		$this->arResult['ANALYTIC_PARAMS'] = isset($this->arParams['~ANALYTIC_PARAMS']) && is_array($this->arParams['~ANALYTIC_PARAMS'])
			? $this->arParams['~ANALYTIC_PARAMS'] : array();

		$this->arResult['RESTRICTIONS_SCRIPT'] = $this->getRestrictionsScript();

		$this->applyFieldInfos();

		if ($this->arResult['RESTRICTIONS_SCRIPT'] !== '')
		{
			$this->includeComponentTemplate('restrictions');
		}
		else
		{
			$this->includeComponentTemplate();
		}
	}

	protected function updateTabsByEvent(array $tabs): array
	{
		$event = new Event('crm', 'onEntityDetailsTabsInitialized', [
			'entityID' => $this->entityID,
			'entityTypeID' => $this->entityTypeID,
			'guid' => $this->guid,
			'tabs' => $tabs,
		]);
		EventManager::getInstance()->send($event);
		foreach($event->getResults() as $result)
		{
			if($result->getType() === EventResult::SUCCESS)
			{
				$parameters = $result->getParameters();
				if(is_array($parameters) && is_array($parameters['tabs']))
				{
					$tabs = $parameters['tabs'];
				}
			}
		}

		return $tabs;
	}

	protected function getRestrictionsScript(): string
	{
		$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getItemDetailPageRestriction(
			$this->entityTypeID,
			$this->entityID
		);

		if (!$restriction->hasPermission())
		{
			return $restriction->prepareInfoHelperScript();
		}

		return '';
	}

	protected function getTodoCreateNotificationParams(): ?array
	{
		if (isset($this->arParams['~ENABLE_TODO_CREATE_NOTIFICATION']) && !$this->arParams['~ENABLE_TODO_CREATE_NOTIFICATION'])
		{
			return null;
		}

		$factory = Container::getInstance()->getFactory($this->entityTypeID);

		$todoCreateNotificationAvailable =
			$this->entityID
			&& !$this->arResult['READ_ONLY']
			&& $factory
			&& $factory->isSmartActivityNotificationEnabled()
		;
		if (!$todoCreateNotificationAvailable)
		{
			return null;
		}

		$stageIdField = $factory->getEntityFieldNameByMap(\Bitrix\Crm\Item::FIELD_NAME_STAGE_ID);
		$select = [
			\Bitrix\Crm\Item::FIELD_NAME_ID,
			$stageIdField
		];
		if ($factory->isCategoriesSupported())
		{
			$select[] = \Bitrix\Crm\Item::FIELD_NAME_CATEGORY_ID;
		}
		$item = $factory->getItems([
			'filter' => ['=ID' => $this->entityID],
			'select' => $select,
			'limit' => 1,
		])[0] ?? null;

		if (!$item)
		{
			return null;
		}

		$stages =
			$factory->isCategoriesSupported()
			? $factory->getStages($item->getCategoryId())
			: $factory->getStages()
		;
		$finalStages = [];
		foreach ($stages as $stage)
		{
			if (\Bitrix\Crm\PhaseSemantics::isFinal($stage->getSemantics()))
			{
				$finalStages[] = $stage->getStatusId();
			}
		}

		return [
			'entityTypeId' => $this->entityTypeID,
			'entityId' => $this->entityID,
			'entityStageId' => $item->getStageId(),
			'stageIdField' => $stageIdField,
			'finalStages' => $finalStages,
			'skipPeriod' => (new \Bitrix\Crm\Activity\TodoCreateNotification($this->entityTypeID))->getCurrentSkipPeriod(),
		];
	}

	private function applyFieldInfos(): void
	{
		if (empty($this->arResult['EDITOR']['ENTITY_FIELDS']))
		{
			return;
		}

		foreach ($this->arResult['EDITOR']['ENTITY_FIELDS'] as &$field)
		{
			if (in_array($field['type'], ['date', 'datetime']))
			{
				if (!empty($field['data']['dateViewFormat']))
				{
					continue;
				}

				$field['data']['dateViewFormat'] = Main\Type\Date::convertFormatToPhp(
					Main\Application::getInstance()->getContext()->getCulture()->getLongDateFormat()
				);

				$enableTime = !isset($field['data']['enableTime']) || $field['data']['enableTime'];
				if ($field['type'] === 'datetime' && $enableTime)
				{
					$field['data']['dateViewFormat'] .= ' ' . Main\Application::getInstance()->getContext()->getCulture()->getShortTimeFormat();
				}
			}
		}
		unset($field);
	}
}
