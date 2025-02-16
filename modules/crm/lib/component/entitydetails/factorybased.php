<?php

namespace Bitrix\Crm\Component\EntityDetails;

use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Component\ComponentError;
use Bitrix\Crm\Component\EntityDetails\Files\CopyFilesOnItemClone;
use Bitrix\Crm\Controller\Entity;
use Bitrix\Crm\EO_Status;
use Bitrix\Crm\Field;
use Bitrix\Crm\Format\Money;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\Integration\UI\EntityEditor\SupportsEditorProvider;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MessageSender\Channel\ChannelRepository;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Product\Url\ProductBuilder;
use Bitrix\Crm\Relation;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Security\StagePermissions;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Operation;
use Bitrix\Crm\Service\ParentFieldManager;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\ObjectException;
use Bitrix\Main\Request;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserField\Dispatcher;
use Bitrix\Main\UserField\Types\DateTimeType;
use Bitrix\Main\UserField\Types\DoubleType;
use Bitrix\Main\UserField\Types\FileType;
use Bitrix\Main\Web\Uri;
use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar\ButtonLocation;
use CCrmComponentHelper;
use CLists;
use Bitrix\Crm\Component\EntityDetails;

abstract class FactoryBased extends BaseComponent implements Controllerable, SupportsEditorProvider
{
	use Traits\EditorInitialMode;
	use Traits\InitializeAdditionalFieldsData;

	public const TAB_NAME_EVENT = 'tab_event';
	public const TAB_NAME_PRODUCTS = 'tab_products';
	public const TAB_NAME_TREE = 'tab_tree';
	public const TAB_NAME_BIZPROC = 'tab_bizproc';
	public const TAB_NAME_AUTOMATION = 'tab_automation';
	public const TAB_NAME_ORDERS = 'tab_order';
	public const TAB_LISTS_PREFIX = 'tab_lists_';

	protected const MAX_ENTITIES_IN_TAB = 20;

	/** @var Factory */
	protected $factory;
	/** @var Item */
	protected $item;
	protected $operation;
	/** @var Category */
	protected $category;
	protected $categoryId;
	/** @var EO_Status */
	protected $stage;
	/** @var EditorAdapter */
	protected $editorAdapter;
	protected $context;
	protected $parentIdentifiers;
	protected $isReadOnly;

	private bool $isSearchHistoryEnabled = true;

	//@codingStandardsIgnoreStart
	public function onPrepareComponentParams($arParams): array
	{
		$arParams['ENTITY_TYPE_ID'] = (int)($arParams['ENTITY_TYPE_ID'] ?? \CCrmOwnerType::Undefined);
		$arParams['ENTITY_ID'] = (int)($arParams['ENTITY_ID'] ?? 0);

		$this->fillParameterFromRequest('categoryId', $arParams);
		$this->fillParameterFromRequest('parentId', $arParams);
		$this->fillParameterFromRequest('parentTypeId', $arParams);

		return parent::onPrepareComponentParams($arParams);
	}

	public function initializeParams(array $params): void
	{
		$mergedParams = array_merge($this->arParams, $params);
		$this->arParams = $this->onPrepareComponentParams($mergedParams);

		if (isset($this->arParams['CATEGORY_ID']) && is_numeric($this->arParams['CATEGORY_ID']))
		{
			$this->setCategoryId((int)$this->arParams['CATEGORY_ID']);
		}

		if (!empty($this->arParams['ENABLE_SEARCH_HISTORY']))
		{
			$this->enableSearchHistory($this->arParams['ENABLE_SEARCH_HISTORY'] === 'Y');
		}
	}

	public function setEntityTypeID(int $id): void
	{
		$this->arParams['ENTITY_TYPE_ID'] = $id;
	}

	public function getEntityTypeID(): int
	{
		return (int)($this->arParams['ENTITY_TYPE_ID'] ?? \CCrmOwnerType::Undefined);
	}

	public function setEntityID($entityID): void
	{
		$this->entityID = $entityID;
		$this->arParams['ENTITY_ID'] = $entityID;

		$this->userFields = null;
		$this->userFieldInfos = null;
	}
	//@codingStandardsIgnoreEnd

	public function enableSearchHistory($enable): void
	{
		$this->isSearchHistoryEnabled = (bool)$enable;
	}

	public function init(): void
	{
		parent::init();
		Loc::loadMessages(__FILE__);
		if($this->getErrors())
		{
			return;
		}

		$entityTypeId = $this->getEntityTypeID();
		if($entityTypeId > 0)
		{
			$this->factory = Container::getInstance()->getFactory($entityTypeId);
		}
		if(!$this->factory)
		{
			$this->addError(ComponentError::ENTITY_NOT_FOUND);
			return;
		}

		$this->entityTypeId = $entityTypeId;
		//@codingStandardsIgnoreStart
		$id = (int) ($this->arParams['ENTITY_ID'] ?? 0);
		//@codingStandardsIgnoreEnd

		if( ($id <= 0) || ($this->isCopyMode()) )
		{
			$this->item = $this->factory->createItem();

			$this->fillParentFields();
		}
		else
		{
			$this->item = $this->factory->getItems([
				'select' => $this->getSelect(),
				'filter' => ['=ID' => $id],
			])[0] ?? null;

			if (!$this->item)
			{
				$this->errorCollection[] = new Error(Loc::getMessage('CRM_TYPE_ITEM_NOT_FOUND'));

				return;
			}
		}

		$this->entityID = $this->item->getId();

		if($this->factory->isCategoriesSupported())
		{
			if($this->item->isNew())
			{
				//@codingStandardsIgnoreStart
				$categoryId = $this->arParams['categoryId'] ?? $this->categoryId;
				//@codingStandardsIgnoreEnd
				if($categoryId > 0)
				{
					$this->category = $this->factory->getCategory($categoryId);
					if(!$this->category)
					{
						$this->errorCollection[] = new Error(Loc::getMessage('CRM_TYPE_CATEGORY_NOT_FOUND_ERROR'));
						return;
					}
				}
				else
				{
					$this->category = $this->factory->createDefaultCategoryIfNotExist();
				}
				$this->item->setCategoryId($this->category->getId());
			}
			else
			{
				$this->category = $this->factory->getCategory($this->item->getCategoryId());
				if(!$this->category)
				{
					$this->errorCollection[] = new Error(Loc::getMessage('CRM_TYPE_CATEGORY_NOT_FOUND_ERROR'));
					return;
				}
			}
		}

		$categoryId = $this->category ? $this->category->getId() : 0;
		$this->setCategoryId($categoryId);

		if(!$this->tryToDetectMode())
		{
			return;
		}

		if($this->factory->isStagesSupported() && $this->item->isNew())
		{
			$this->factory->setStartStageIdPermittedForUser($this->item);
		}

		$userFieldEntityID = $this->getUserFieldEntityID();
		if($userFieldEntityID !== '')
		{
			$this->userType = new \CCrmUserType(Application::getUserTypeManager(), $userFieldEntityID);
			$this->userFieldDispatcher = Dispatcher::instance();
		}

		if ($id <= 0)
		{
			$this->fillItemFromRequest();
		}

		$this->editorAdapter = $this->factory->getEditorAdapter();
	}

	public function setCategoryId(int $categoryId): void
	{
		$this->categoryId = $categoryId;
	}

	protected function getFileHandlerUrl()
	{
		return Container::getInstance()->getRouter()->getFileUrlTemplate($this->getEntityTypeID());
	}

	public function initializeEditorAdapter(): void
	{
		if ($this->editorAdapter->hasData())
		{
			return;
		}

		$this->editorAdapter->enableSearchHistory($this->isSearchHistoryEnabled);
		$this->editorAdapter->setContext($this->getEditorContext());
		$this->editorAdapter->processByItem(
			$this->item,
			$this->factory->getStages($this->categoryId),
			[
				'mode' => $this->mode,
				'componentName' => $this->getComponentName(),
				'isPageTitleEditable' => $this->isPageTitleEditable(),
				'fileHandlerUrl' => $this->getFileHandlerUrl(),
				'entitySelectorContext' => $this->getEntitySelectorContext(),
				'conversionWizard' => $this->getConversionWizard(),
			]
		);
	}

	protected function tryToDetectMode(): bool
	{
		if (!empty($this->mode))
		{
			return true;
		}
		//@codingStandardsIgnoreStart
		if (!empty($this->arParams['COMPONENT_MODE']))
		{
			$this->mode = $this->arParams['COMPONENT_MODE'];

			return true;
		}
		//@codingStandardsIgnoreEnd

		return parent::tryToDetectMode();
	}

	protected function getSelect(): array
	{
		$select = ['*'];

		if ($this->factory->isLinkWithProductsEnabled())
		{
			$select[] = Item::FIELD_NAME_PRODUCTS;
		}

		if ($this->factory->isClientEnabled())
		{
			$select[] = Item::FIELD_NAME_CONTACTS;
		}

		if ($this->factory->isObserversEnabled())
		{
			$select[] = Item::FIELD_NAME_OBSERVERS;
		}

		return $select;
	}

	//@codingStandardsIgnoreStart
	protected function executeBaseLogic(): void
	{
		if ($this->isCopyMode())
		{
			CopyFilesOnItemClone::getInstance()->execute($this->item, $this->factory);
		}

		$this->getApplication()->SetTitle(htmlspecialcharsbx($this->getTitle()));

		$this->initializeEditorAdapter();

		$this->arResult['entityDetailsParams'] = $this->getEntityDetailsParams();
		$this->arResult['activityEditorParams'] = $this->getActivityEditorConfig();
		$this->arResult['jsParams'] = $this->getJsParams();

		if ($this->factory->isStagesEnabled())
		{
			$converter = Container::getInstance()->getStageConverter();
			$canWriteConfig = Container::getInstance()->getUserPermissions()->canWriteConfig();
			$stages = $this->factory->getStages($this->categoryId);
			$stagePermissions = new StagePermissions(
				$this->getEntityTypeID(),
				$this->categoryId === 0 ? null : $this->categoryId,
			);

			$stageId = $this->item->getStageId();
			$currentStage = null;
			foreach($stages as $stage)
			{
				if(!$currentStage)
				{
					$currentStage = $stage;
				}
				if($stage->getStatusId() === $stageId)
				{
					$currentStage = $stage;
				}

				$jsParamStage = $converter->toJson($stage);
				$jsParamStage['stagesToMove'] = $stagePermissions->getPermissionsByStatusId($stage->getStatusId());
				$jsParamStage['allowMoveToAnyStage'] =  $canWriteConfig || UserPermissions::isAlwaysAllowedEntity($this->getEntityTypeID());

				$this->arResult['jsParams']['stages'][] = $jsParamStage;
			}

			$this->arResult['jsParams']['currentStageId'] = $currentStage ? $currentStage->getId() : null;
		}

		$this->arResult['jsParams']['item'] = $this->item->jsonSerialize();

		if (!$this->item->isNew())
		{
			if (\Bitrix\Crm\Settings\HistorySettings::getCurrent()->isViewEventEnabled())
			{
				$trackedObject = $this->factory->getTrackedObject($this->item);
				Container::getInstance()->getEventHistory()->registerView($trackedObject);
			}

			$this->arResult['jsParams']['pullTag'] = Container::getInstance()->getPullManager()->subscribeOnItemUpdate(
				$this->getEntityTypeID(),
				$this->item->getId()
			);
		}
	}
	//@codingStandardsIgnoreEnd

	protected function checkIfEntityExists(): bool
	{
		return $this->item->getId() === $this->entityID;
	}

	protected function getTitle(): string
	{
		if($this->item->isNew())
		{
			return Loc::getMessage('CRM_COMPONENT_FACTORYBASED_NEW_ITEM_TITLE', [
				'#ENTITY_NAME#' => $this->factory->getEntityDescription(),
			]);
		}

		return $this->item->getHeading() ?? '';
	}

	protected function isPageTitleEditable(): bool
	{
		return true;
	}

	protected function getEntityName(): string
	{
		return \CCrmOwnerType::ResolveName($this->getEntityTypeID());
	}

	protected function getGuid(): string
	{
		$guid = $this->getEntityName().'_details';

		if ($this->categoryId > 0)
		{
			$guid .= '_C'.$this->categoryId;
		}

		return $guid;
	}

	protected function getJsParams(): array
	{
		$params = [
			'entityTypeId' => $this->getEntityTypeID(),
			'entityTypeName' => $this->factory->getEntityName(),
			'id' => $this->item->getId(),
			'messages' => $this->getJsMessages(),
			'signedParameters' => $this->getSignedParameters(),
			'isPageTitleEditable' => $this->isPageTitleEditable(),
			'editorContext' => $this->getEditorContext(),
			'serviceUrl' => $this->getServiceUrl(),
			'userFieldCreateUrl' => Container::getInstance()->getRouter()->getUserFieldDetailUrl(
				$this->getEntityTypeID(),
				0
			),
			'editorGuid' => $this->getEditorGuid(),
			'isStageFlowActive' => !$this->isReadOnly(),
		];

		if($this->isDocumentButtonAvailable())
		{
			$params['documentButtonParameters'] = DocumentGeneratorManager::getInstance()->getDocumentButtonParameters(
				DocumentGeneratorManager::getInstance()->getCrmOwnerTypeProvider($this->getEntityTypeID()),
				$this->getEntityID()
			);
			$params['documentButtonParameters']['buttonId'] = $this->getDocumentButtonId();
		}

		if ($this->factory->isCategoriesEnabled())
		{
			$params['categoryId'] = $this->categoryId;
			$params['categories'] = $this->getToolbarCategories(
				Container::getInstance()->getUserPermissions()->filterAvailableForAddingCategories(
					$this->factory->getCategories()
				)
			);
		}

		if (\Bitrix\Crm\Automation\Factory::isBizprocDesignerEnabled($this->item->getEntityTypeId()))
		{
			$starterConfig = $this->getBizprocStarterConfig();
			if ($starterConfig)
			{
				$params['bizprocStarterConfig'] = $starterConfig;
			}
		}

		if (\Bitrix\Crm\Automation\Factory::isAutomationAvailable($this->item->getEntityTypeId()))
		{
			$categoryId = $this->factory->isCategoriesEnabled() ? $this->item->getCategoryId() : 0;
			$checkAutomationTourGuideData = \CCrmBizProcHelper::getHowCheckAutomationTourGuideData(
				$this->item->getEntityTypeId(),
				$categoryId,
				$this->userID
			);
			if ($checkAutomationTourGuideData)
			{
				$params['automationCheckAutomationTourGuideData'] = [
					'options' => $checkAutomationTourGuideData,
				];
			}
		}

		if (!$this->item->isNew())
		{
			$params['receiversJSONString'] = \Bitrix\Main\Web\Json::encode(
				ChannelRepository::create(ItemIdentifier::createByItem($this->item))->getToList(),
			);
		}

		return $params;
	}

	protected function getJsMessages(): array
	{
		return [
			'deleteItemTitle' => Loc::getMessage('CRM_TYPE_ITEM_DELETE_CONFIRMATION_TITLE'),
			'deleteItemMessage' => Loc::getMessage('CRM_TYPE_ITEM_DELETE_CONFIRMATION_MESSAGE'),
			'crmTimelineHistoryStub' => $this->getTimelineHistoryStubMessage(),
			'partialEditorTitle' => Loc::getMessage('CRM_TYPE_ITEM_PARTIAL_EDITOR_TITLE'),
			'onCreateUserFieldAddMessage' => Loc::getMessage('CRM_TYPE_ITEM_SAVE_EDITOR_AND_RELOAD'),
		];
	}

	//@codingStandardsIgnoreStart
	protected function getTimelineHistoryStubMessage(): ?string
	{
		return $this->arParams['CRM_TIMELINE_HISTORY_STUB_MESSAGE']
			?? Loc::getMessage('CRM_COMPONENT_FACTORYBASED_TIMELINE_HISTORY_STUB');
	}
	//@codingStandardsIgnoreEnd

	protected function getEntityInfo(): array
	{
		$itemId = $this->item->getId();
		$entityTypeID = $this->getEntityTypeID();

		return [
			'ENTITY_ID' => $itemId,
			'ENTITY_TYPE_ID' => $entityTypeID,
			'ENTITY_TYPE_NAME' => $this->factory->getEntityName(),
			'TITLE' => $this->getTitle(),
			'SHOW_URL' => Container::getInstance()->getRouter()->getItemDetailUrl($entityTypeID, $itemId),
		];
	}

	protected function getExtras(): array
	{
		return [
			'CATEGORY_ID' => $this->categoryId,
		];
	}

	protected function setBizprocStarterConfig()
	{
		if (\Bitrix\Crm\Automation\Factory::isBizprocDesignerEnabled($this->item->getEntityTypeId()))
		{
			$this->arResult['bizprocStarterConfig'] = $this->getBizprocStarterConfig();
		}
	}

	protected function getEntityDetailsParams(): array
	{
		$entityId = $this->item->getId();
		if ($this->isCopyMode())
		{
			$entityId = 0;
		}

		return [
			'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
			'ENTITY_ID' => $entityId,
			'GUID' => $this->getGuid(),
			'ENTITY_INFO' => $this->getEntityInfo(),
			'EXTRAS' => $this->getExtras(),
			'READ_ONLY' => $this->isReadOnly(),
			'TABS' => $this->getTabs(),
			'EDITOR' => $this->getEditorConfig(),
			'TIMELINE' => $this->getTimelineConfig(),
			'ACTIVITY_EDITOR_ID' => $this->getActivityEditorId(),
			'MESSAGES' => $this->getEntityEditorMessages(),
		];
	}

	/**
	 * @return string[]
	 */
	protected function getEntityEditorMessages(): array
	{
		return [
			'COPY_PAGE_URL' => Loc::getMessage('CRM_COMPONENT_FACTORYBASED_COPY_PAGE_URL'),
			'PAGE_URL_COPIED' => Loc::getMessage('CRM_COMPONENT_FACTORYBASED_PAGE_URL_COPIED'),
			'MANUAL_OPPORTUNITY_CHANGE_MODE_TITLE' => Loc::getMessage('CRM_COMPONENT_FACTORYBASED_MANUAL_OPPORTUNITY_CHANGE_MODE_TITLE'),
			'MANUAL_OPPORTUNITY_CHANGE_MODE_TEXT' => Loc::getMessage('CRM_COMPONENT_FACTORYBASED_MANUAL_OPPORTUNITY_CHANGE_MODE_TEXT'),
		];
	}

	protected function isReadOnly(): bool
	{
		if ($this->isReadOnly === null)
		{
			if ($this->item->isNew())
			{
				$this->isReadOnly = !Container::getInstance()->getUserPermissions()->canAddItem($this->item);
			}
			else
			{
				$this->isReadOnly = !Container::getInstance()->getUserPermissions()->canUpdateItem($this->item);
			}
		}

		return $this->isReadOnly;
	}

	protected function getDefaultTabInfoByCode(string $tabCode): ?array
	{
		$toolsManager = \Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager();

		if($tabCode === static::TAB_NAME_EVENT)
		{
			$entityName = $this->factory->getEntityName();
			$entityId = $this->item->getId();

			$tabParams = [
				'id' => static::TAB_NAME_EVENT,
				'name' => Loc::getMessage('CRM_TYPE_ITEM_DETAILS_TAB_HISTORY'),
			];

			if ($this->item->isNew())
			{
				$tabParams['enabled'] = false;
			}
			else
			{
				if (!RestrictionManager::isHistoryViewPermitted())
				{
					$tabParams['tariffLock']  = RestrictionManager::getHistoryViewRestriction()->prepareInfoHelperScript();
				}
				else
				{
					$tabParams['loader'] = [
						'serviceUrl' =>
							'/bitrix/components/bitrix/crm.event.view/lazyload.ajax.php?&site='
							. SITE_ID . '&' . bitrix_sessid_get()
						,
						'componentData' => [
							'template' => '',
							'contextId' => $entityName."_{$entityId}_EVENT",
							'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
								'AJAX_OPTION_ADDITIONAL' => $entityName."_{$entityId}_EVENT",
								'ENTITY_TYPE' => $entityName,
								'ENTITY_ID' => $entityId,
								'TAB_ID' => static::TAB_NAME_EVENT,
								'INTERNAL' => 'Y',
								'SHOW_INTERNAL_FILTER' => 'Y',
								'PRESERVE_HISTORY' => true,
							], 'crm.event.view')
						],
					];
				}
			}

			return $tabParams;
		}
		if ($tabCode === static::TAB_NAME_PRODUCTS)
		{
			return [
				'id' => static::TAB_NAME_PRODUCTS,
				'name' => Loc::getMessage('CRM_COMMON_PRODUCTS'),
				'html' => $this->getProductsTabHtml(),
			];
		}
		if($tabCode === static::TAB_NAME_TREE)
		{
			return [
				'id' => static::TAB_NAME_TREE,
				'name' => Loc::getMessage('CRM_TYPE_ITEM_DETAILS_TAB_TREE'),
				'loader' => [
					'serviceUrl' => '/bitrix/components/bitrix/crm.entity.tree/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => [
						'template' => '.default',
						'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
							'ENTITY_ID' => $this->getEntityID(),
							'ENTITY_TYPE_NAME' => $this->getEntityName(),
						], 'crm.entity.tree')
					]
				],
				'enabled' => !$this->item->isNew(),
			];
		}
		if ($tabCode === static::TAB_NAME_BIZPROC)
		{
			if (!$toolsManager->checkBizprocAvailability())
			{
				return [
					'id' => static::TAB_NAME_BIZPROC,
					'name' => Loc::getMessage('CRM_TYPE_ITEM_DETAILS_TAB_BIZPROC'),
					'enabled' => !$this->item->isNew(),
					'availabilityLock' => \Bitrix\Crm\Restriction\AvailabilityManager::getInstance()
						->getBizprocAvailabilityLock()
					,
				];
			}

			return [
				'id' => static::TAB_NAME_BIZPROC,
				'name' => Loc::getMessage('CRM_TYPE_ITEM_DETAILS_TAB_BIZPROC'),
				'loader' => [
					'serviceUrl' => '/bitrix/components/bitrix/bizproc.document/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => [
						'template' => 'frame',
						'params' => [
							'MODULE_ID' => 'crm',
							'ENTITY' => \CCrmBizProcHelper::ResolveDocumentName($this->item->getEntityTypeId()),
							'DOCUMENT_TYPE' => $this->factory->getEntityName(),
							'DOCUMENT_ID' => $this->factory->getEntityName() . '_' . $this->item->getId(),
						]
					]
				],
				'enabled' => !$this->item->isNew(),
			];
		}
		if ($tabCode === static::TAB_NAME_AUTOMATION)
		{
			$availabilityLock = null;
			$robotsUrl = null;
			if (!$toolsManager->checkRobotsAvailability())
			{
				$availabilityLock = \Bitrix\Crm\Restriction\AvailabilityManager::getInstance()
					->getRobotsAvailabilityLock()
				;
			}
			else
			{
				$robotsUrl = Container::getInstance()->getRouter()
					->getAutomationUrl($this->factory->getEntityTypeId(), $this->item->getCategoryId())
					->addParams(['id' => $this->item->getId()]);
			}

			return [
				'id' => static::TAB_NAME_AUTOMATION,
				'name' => Loc::getMessage('CRM_TYPE_ITEM_DETAILS_TAB_AUTOMATION'),
				'url' => $robotsUrl,
				'enabled' => !$this->item->isNew(),
				'availabilityLock' => $availabilityLock,
			];
		}
		if ($tabCode === static::TAB_NAME_ORDERS)
		{
			return [
				'id' => static::TAB_NAME_ORDERS,
				'name' => \CCrmOwnerType::GetCategoryCaption(\CCrmOwnerType::Order),
				'loader' => [
					'serviceUrl' => '/bitrix/components/bitrix/crm.order.list/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => [
						'template' => '',
						'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
							'INTERNAL_FILTER' => [
								'ASSOCIATED_ENTITY_ID' => $this->item->getId(),
								'ASSOCIATED_ENTITY_TYPE_ID' => $this->factory->getEntityTypeId(),
							],
							'SUM_PAID_CURRENCY' => \Bitrix\Crm\Currency::getBaseCurrencyId(),
							'GRID_ID_SUFFIX' => $this->getGuid(),
							'TAB_ID' => static::TAB_NAME_ORDERS,
							'PRESERVE_HISTORY' => true,
							'BUILDER_CONTEXT' => ProductBuilder::TYPE_ID,
							'ANALYTICS' => [
								// we dont know where from this component was opened from - it could be anywhere on portal
								'c_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::getAnalyticsEntityType($this->factory->getEntityTypeId()) . '_section',
								'c_sub_section' => Integration\Analytics\Dictionary::SUB_SECTION_DETAILS,
							],
						], 'crm.order.list')
					]
				],
				'enabled' => !$this->item->isNew(),
			];
		}

		return [];
	}

	protected function getTabs(): array
	{
		$tabs = [];

		$tabCodes = $this->getTabCodes();
		foreach($tabCodes as $code => $params)
		{
			$data = $this->getDefaultTabInfoByCode($code);
			if(is_array($data))
			{
				$tabs[] = array_merge($data, $params);
			}
		}

		$relationManager = Container::getInstance()->getRelationManager();
		$relationTabs = $relationManager->getRelationTabsForDynamicChildren(
			$this->getEntityTypeID(),
			$this->getEntityID(),
			$this->item->isNew()
		);

		return array_merge($tabs, $relationTabs, $this->getAttachedListTabs());
	}

	protected function getTabCodes(): array
	{
		$tabs = [];
		if ($this->factory->isLinkWithProductsEnabled())
		{
			$tabs[static::TAB_NAME_PRODUCTS] = [];
		}

		$tabs[static::TAB_NAME_EVENT] = [];

		if (
			\CCrmBizProcHelper::needShowBPTab()
			&& \Bitrix\Crm\Automation\Factory::isBizprocDesignerEnabled($this->factory->getEntityTypeId())
		)
		{
			$tabs[static::TAB_NAME_BIZPROC] = [];
		}
		if ($this->factory->isAutomationEnabled())
		{
			$tabs[static::TAB_NAME_AUTOMATION] = [];
		}

		$tabs[static::TAB_NAME_TREE] = [];

		$relation = Container::getInstance()->getRelationManager()->getRelation(new RelationIdentifier(
			$this->factory->getEntityTypeId(),
			\CCrmOwnerType::Order
		));
		if ($relation && $relation->isChildrenListEnabled())
		{
			$tabs[static::TAB_NAME_ORDERS] = [];
		}

		return $tabs;
	}

	protected function getProductsTabHtml(): string
	{
		$router = Container::getInstance()->getRouter();
		$userPermissions = Container::getInstance()->getUserPermissions();

		ob_start();

		$locationId = null;
		$accountingService = Container::getInstance()->getAccounting();
		if ($this->item->hasField(Item::FIELD_NAME_LOCATION_ID) && $accountingService->isTaxMode())
		{
			$locationId = $this->item->getLocationId();
		}

		$this->getApplication()->includeComponent(
			'bitrix:crm.entity.product.list',
			'.default',
			[
				'INTERNAL_FILTER' => [
					'OWNER_ID' => $this->getEntityID(),
					'OWNER_TYPE' => $this->getEntityTypeID(),
				],
				'PATH_TO_ENTITY_PRODUCT_LIST' => ProductList::getComponentUrl(
					['site' => $this->getSiteId()],
					bitrix_sessid_get()
				),
				'ACTION_URL' => ProductList::getLoaderUrl(
					['site' => $this->getSiteId()],
					bitrix_sessid_get()
				),
				'ENTITY_ID' => $this->getEntityID(),
				'ENTITY_TYPE_NAME' => $this->getEntityName(),
				'ENTITY_TITLE' =>
					$this->item->isNew()
						? $this->getTitle()
						: $this->item->getTitlePlaceholder()
				,
				'CUSTOM_SITE_ID' => $this->getSiteId(),
				'CUSTOM_LANGUAGE_ID' => $this->getLanguageId(),
				'ALLOW_EDIT' => $this->isReadOnly() ? 'N' : 'Y',
				'ALLOW_ADD_PRODUCT' => $this->isReadOnly() ? 'N' : 'Y',
				// 'ALLOW_CREATE_NEW_PRODUCT' => (!$this->arResult['READ_ONLY'] ? 'Y' : 'N'),
				'ID' => $this->getProductEditorId(),
				'PREFIX' => $this->getProductEditorId(),
				'FORM_ID' => '',
				'PERMISSION_TYPE' => $this->isReadOnly() ? 'READ' : 'WRITE',
				'PERMISSION_ENTITY_TYPE' => $userPermissions::getPermissionEntityType($this->getEntityTypeID(), $this->categoryId),
				'PERSON_TYPE_ID' => $accountingService->resolvePersonTypeId($this->item),
				'CURRENCY_ID' => $this->item->getCurrencyId(),
				'ALLOW_LD_TAX' => Container::getInstance()->getAccounting()->isTaxMode() ? 'Y' : 'N',
				'LOCATION_ID' => $locationId,
				'CLIENT_SELECTOR_ID' => '', //TODO: Add Client Selector
				'PRODUCTS' => $this->getProductsData(),
				'PRODUCT_DATA_FIELD_NAME' => $this->getProductDataFieldName(),
				'BUILDER_CONTEXT' => ProductBuilder::TYPE_ID,
				/*
				'HIDE_MODE_BUTTON' => !$this->isEditMode ? 'Y' : 'N',
				'TOTAL_SUM' => isset($this->entityData['OPPORTUNITY']) ? $this->entityData['OPPORTUNITY'] : null,
				'TOTAL_TAX' => isset($this->entityData['TAX_VALUE']) ? $this->entityData['TAX_VALUE'] : null,
				'PATH_TO_PRODUCT_EDIT' => $this->arResult['PATH_TO_PRODUCT_EDIT'],
				'PATH_TO_PRODUCT_SHOW' => $this->arResult['PATH_TO_PRODUCT_SHOW'],
				'INIT_LAYOUT' => 'N',
				'INIT_EDITABLE' => $this->arResult['READ_ONLY'] ? 'N' : 'Y',
				'ENABLE_MODE_CHANGE' => 'N',
				'USE_ASYNC_ADD_PRODUCT' => 'Y',
				*/
			],
			false,
			[
				'HIDE_ICONS' => 'Y',
				'ACTIVE_COMPONENT' => 'Y',
			]
		);

		return ob_get_clean();
	}

	protected function getAttachedListTabs(): array
	{
		$tabs = [];

		if (!$this->item->isNew() && Loader::includeModule('lists'))
		{
			$attachedIblocks = CLists::getIblockAttachedCrm($this->getEntityName());
			foreach($attachedIblocks as $iblockId => $iblockName)
			{
				$tabId = static::TAB_LISTS_PREFIX . $iblockId;
				$tabs[] = [
					'id' => $tabId,
					'name' => $iblockName,
					'loader' => [
						'serviceUrl' => '/bitrix/components/bitrix/lists.element.attached.crm/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get().'',
						'componentData' => [
							'template' => '',
							'params' => [
								'ENTITY_ID' => $this->item->getId(),
								'ENTITY_TYPE' => $this->getEntityTypeID(),
								'TAB_ID' => $tabId,
								'IBLOCK_ID' => $iblockId,
							],
						],
					],
				];
			}
		}

		return $tabs;
	}

	protected function getProductEditorId(): string
	{
		return mb_strtolower($this->getGuid()).'_product_editor';
	}

	protected function getProductDataFieldName(): string
	{
		return $this->getEntityName().'_PRODUCT_DATA';
	}

	protected function getToolbarParameters(): array
	{
		$buttons = [];

		if(!$this->item->isNew())
		{
			if (Buttons\IntranetBindingMenu::isAvailable())
			{
				$buttons[ButtonLocation::AFTER_TITLE][] = Buttons\IntranetBindingMenu::createByComponentParameters(
					$this->getIntranetBindingMenuParameters()
				);
			}

			$buttons[ButtonLocation::AFTER_TITLE][] = $this->getSettingsToolbarButton();

			if($this->isDocumentButtonAvailable())
			{
				$buttons[ButtonLocation::AFTER_TITLE][] = $this->getDocumentToolbarButton();
			}
		}

		return array_merge(parent::getToolbarParameters(), [
			'buttons' => $buttons,
			'communications' => $this->getCommunicationToolbarParameters(),
			'hideBorder' => true,
		]);
	}

	protected function getSettingsToolbarButton(): Buttons\SettingsButton
	{
		$items = [];

		$itemCopyUrl = Container::getInstance()->getRouter()->getItemCopyUrl(
			$this->getEntityTypeID(),
			$this->item->getId(),
		);
		$userPermissions = Container::getInstance()->getUserPermissions();
		if ($itemCopyUrl && $userPermissions->canUpdateItem($this->item))
		{
			$analyticsEventBuilder = \Bitrix\Crm\Integration\Analytics\Builder\Entity\CopyOpenEvent::createDefault($this->getEntityTypeID())
				->setSubSection(\Bitrix\Crm\Integration\Analytics\Dictionary::SUB_SECTION_DETAILS)
				->setElement(\Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_SETTINGS_BUTTON)
			;
			if (!empty(\Bitrix\Crm\Integration\Analytics\Dictionary::getAnalyticsEntityType($this->getEntityTypeID())))
			{
				$analyticsEventBuilder
					->setSection(\Bitrix\Crm\Integration\Analytics\Dictionary::getAnalyticsEntityType($this->getEntityTypeID()).'_section');
			}

			$items[] = [
				'text' => Loc::getMessage('CRM_COMMON_ACTION_COPY'),
				'href' => $analyticsEventBuilder->buildUri($itemCopyUrl),
			];
		}
		if ($userPermissions->canDeleteItem($this->item))
		{
			$items[] = [
				'text' => $this->getDeleteMessage(),
				'onclick' => new Buttons\JsEvent('BX.Crm.ItemDetailsComponent:onClickDelete'),
			];
		}

		return new Buttons\SettingsButton([
			'menu' => [
				'items' => $items,
			],
		]);
	}

	protected function getDeleteMessage(): string
	{
		return (string)Loc::getMessage('CRM_COMMON_ACTION_DELETE');
	}

	protected function getBizprocStarterConfig(): array
	{
		$toolsManager = \Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager();
		if (!$toolsManager->checkBizprocAvailability() && $this->item->getId() > 0)
		{
			return [
				'availabilityLock' => \Bitrix\Crm\Restriction\AvailabilityManager::getInstance()
					->getBizprocAvailabilityLock()
				,
			];
		}
		$documentType = \CCrmBizProcHelper::ResolveDocumentType($this->item->getEntityTypeId());
		$documentId = \CCrmBizProcHelper::ResolveDocumentId($this->item->getEntityTypeId(), $this->item->getId());

		$config = [];
		if (Loader::includeModule('bizproc') && $this->item->getId() > 0)
		{
			$config = [
				'moduleId' => 'crm',
				'entity' => $documentType[1],
				'documentType' => $documentType[2],
				'documentId' => $documentId[2],
			];
		}

		return $config;
	}

	protected function getCommunicationToolbarParameters(): array
	{
		if(!$this->factory->isClientEnabled() || $this->item->isNew() || $this->isCopyMode())
		{
			return [];
		}
		$isEnabled = ModuleManager::isModuleInstalled('calendar');

		$multiFields = [];
		$clientData = $this->editorAdapter->getClientEntityData();
		if (!empty($clientData[EditorAdapter::FIELD_CLIENT . '_INFO']))
		{
			foreach ($clientData[EditorAdapter::FIELD_CLIENT . '_INFO'] as $clientTypeData)
			{
				foreach ($clientTypeData as $client)
				{
					if (isset($client['advancedInfo']['multiFields']))
					{
						// it is better this way than multiple queries to FieldMultiTable
						$multifieldsItem = $client['advancedInfo']['multiFields'];

						foreach ($multifieldsItem as &$multifieldItem)
						{
							if($multifieldItem['TYPE_ID'] === 'PHONE' || $multifieldItem['TYPE_ID'] === 'EMAIL')
							{
								$multifieldItem['OWNER'] = [
									'ID' => $client['id'],
									'TYPE_ID' => \CCrmOwnerType::ResolveID($client['typeName']),
									'TITLE' => $client['title'],
								];
							}
						}
						unset($multifieldItem);

						/** @noinspection SlowArrayOperationsInLoopInspection */
						$multiFields = array_merge($multiFields, $multifieldsItem);
					}
				}
			}
		}

		return [
			'isEnabled' => $isEnabled,
			'ownerInfo' => $this->getEntityInfo(),
			'multiFields' => $multiFields,
		];
	}

	protected function getActivityEditorConfig(): array
	{
		return [
			'EDITOR_ID' => $this->getActivityEditorId(),
			'PREFIX' => $this->getGuid(),
			'ENABLE_UI' => false,
			'ENABLE_TOOLBAR' => false,
			'OWNER_TYPE' => $this->factory->getEntityName(),
			'OWNER_ID' => $this->item->getId(),
			'MARK_AS_COMPLETED_ON_VIEW' => false,
			'SKIP_VISUAL_COMPONENTS' => 'Y',
		];
	}

	protected function getActivityEditorId(): string
	{
		return mb_strtolower($this->getGuid()).'_activity_editor';
	}

	//region editor
	public function getEditorConfig(): array
	{
		$userFieldEntityId = $this->getUserFieldEntityId();
		$isUserFieldCreationEnabled = Container::getInstance()->getUserPermissions($this->userID)->isAdminForEntity($this->entityTypeId);
		$editorGuid = $this->getEditorGuid();

		/** @var \Bitrix\Crm\Integration\Analytics\Builder\BuilderContract $analyticsBuilder */
		if ($this->isCopyMode())
		{
			$analyticsBuilder = Integration\Analytics\Builder\Entity\CopyEvent::createDefault($this->entityTypeId);
		}
		elseif ($this->isEditMode())
		{
			$analyticsBuilder = Integration\Analytics\Builder\Entity\UpdateEvent::createDefault($this->entityTypeId);
		}
		elseif ($this->isConversionMode())
		{
			$analyticsBuilder =
				Integration\Analytics\Builder\Entity\ConvertEvent::createDefault($this->entityTypeId)
					->setSrcEntityTypeId($this->getConversionWizard()->getEntityTypeID())
			;
		}
		else
		{
			$analyticsBuilder = Integration\Analytics\Builder\Entity\AddEvent::createDefault($this->entityTypeId);
		}

		return [
			'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
			'ENTITY_ID' => $this->isCopyMode() ? 0 : $this->getEntityID(),
			'IS_COPY_MODE' => $this->isCopyMode(),
			'EXTRAS' => $this->getExtras(),
			'READ_ONLY' => $this->isReadOnly(),
			'INITIAL_MODE' => $this->getInitialMode($this->isCopyMode()),
			'DETAIL_MANAGER_ID' => $editorGuid,
			'MODULE_ID' => 'crm',
			'SERVICE_URL' => $this->getServiceUrl(),
			'GUID' => $editorGuid,
			'CONFIG_ID' => $this->getEditorConfigId(),
			'ENTITY_CONFIG' => $this->getEditorEntityConfig(),
			'DUPLICATE_CONTROL' => [],
			'ENTITY_CONTROLLERS' => $this->getEntityControllers(),
			'ENTITY_FIELDS' => $this->editorAdapter->getEntityFields(),
			'ENTITY_DATA' => $this->editorAdapter->getEntityData(),
			'ADDITIONAL_FIELDS_DATA' => $this->getAdditionalFieldsData(),
			'ENABLE_SECTION_EDIT' => true,
			'ENABLE_SECTION_CREATION' => true,
			'ENABLE_PAGE_TITLE_CONTROLS' => true,
			'ENABLE_USER_FIELD_CREATION' => $isUserFieldCreationEnabled,
			'USER_FIELD_ENTITY_ID' => $userFieldEntityId,
			'USER_FIELD_CREATE_PAGE_URL' => Container::getInstance()->getRouter()->getUserFieldDetailUrl(
				$this->getEntityTypeID(),
				0
			),
			'USER_FIELD_CREATE_SIGNATURE' => ($isUserFieldCreationEnabled
				? $this->userFieldDispatcher->getCreateSignature(['ENTITY_ID' => $userFieldEntityId])
				: ''
			),
			'COMPONENT_AJAX_DATA' => [
				'COMPONENT_NAME' => $this->getName(),
				'ACTION_NAME' => 'save',
				'SIGNED_PARAMETERS' => $this->getSignedParameters(),
				'RELOAD_ACTION_NAME' => 'load',
			],
			'CONTEXT' => $this->getEditorContext(),
			'ATTRIBUTE_CONFIG' => $this->getEditorAttributeConfig(),
			'ENABLE_STAGEFLOW' => $this->factory->isStagesEnabled(),
			'USER_FIELD_PREFIX' => $this->factory->getUserFieldEntityId(),
			// this data is used to send analytics when user clicks 'save' button
			'ANALYTICS_CONFIG' => [
				'data' => $analyticsBuilder->buildData(),
				'appendParamsFromCurrentUrl' => true,
			],
		];
	}

	public function initializeEditorData(): void
	{
		$this->init();
		$this->initializeEditorAdapter();
	}

	public function getEditorConfigId(): string
	{
		return $this->getGuid();
	}

	protected function getEditorGuid(): string
	{
		return $this->getGuid() . '_editor';
	}

	public function getInlineEditorEntityConfig(): array
	{
		$editorConfig = $this->getEditorEntityConfig();

		foreach ($editorConfig as &$section)
		{
			if (empty($section['elements']))
			{
				continue;
			}

			foreach ($section['elements'] as $index => $element)
			{
				if ($element['name'] === EditorAdapter::FIELD_PRODUCT_ROW_SUMMARY)
				{
					unset($section['elements'][$index]);
				}
			}
		}
		unset($section);

		return $editorConfig;
	}

	public function getEditorEntityConfig(): array
	{
		$sectionMain = [
			'name' => 'main',
			'title' => Loc::getMessage('CRM_COMPONENT_FACTORYBASED_EDITOR_MAIN_SECTION_TITLE'),
			'type' => 'section',
			'elements' => [],
		];
		//@codingStandardsIgnoreStart
		$skipFields = ($this->arParams['skipFields'] ?? []);
		//@codingStandardsIgnoreEnd
		if ($this->factory->isStagesEnabled() && !in_array(Item::FIELD_NAME_STAGE_ID, $skipFields, true))
		{
			$sectionMain['elements'][] = ['name' => Item::FIELD_NAME_STAGE_ID];
		}

		if ($this->factory->isLinkWithProductsEnabled())
		{
			$sectionMain['elements'][] = ['name' => EditorAdapter::FIELD_OPPORTUNITY];
		}

		$sectionMain['elements'][] = ['name' => Item::FIELD_NAME_TITLE];

		$sections[] = $sectionMain;

		$sectionAdditional = [
			'name' => 'additional',
			'title' => Loc::getMessage('CRM_TYPE_ITEM_EDITOR_SECTION_ADDITIONAL'),
			'type' => 'section',
			'elements' => [],
		];

		foreach ($this->prepareEntityUserFields() as $fieldName => $userField)
		{
			$sectionAdditional['elements'][] = [
				'name' => $fieldName,
			];
		}

		$sections[] = $sectionAdditional;

		if ($this->factory->isLinkWithProductsEnabled())
		{
			$sections[] = [
				'name' => 'products',
				'title' => Loc::getMessage('CRM_COMMON_PRODUCTS'),
				'type' => 'section',
				'elements' => [
					['name' => EditorAdapter::FIELD_PRODUCT_ROW_SUMMARY],
				],
			];
		}

		return $sections;
	}

	protected function getEntityControllers(): array
	{
		$controllers = [];

		if($this->factory->isLinkWithProductsEnabled())
		{
			$controllers[] = $this->editorAdapter::getProductListController(
				$this->getProductEditorId(),
				$this->item->getCurrencyId()
			);
		}

		return $controllers;
	}

	public function prepareFieldInfos(): array
	{
		if ($this->factory === null)
		{
			$this->factory = Container::getInstance()->getFactory($this->getEntityTypeID());
			$this->item = $this->factory->createItem();
			$this->editorAdapter = $this->factory->getEditorAdapter();
		}

		$this->initializeEditorAdapter();

		return $this->editorAdapter->getEntityFields();
	}
	//endregion

	protected function getTimelineConfig(): array
	{
		return [
			'GUID' => mb_strtolower($this->getGuid()).'_timeline',
		];
	}

	protected function getUserFieldEntityId(): string
	{
		return \CCrmOwnerType::ResolveUserFieldEntityID($this->getEntityTypeID());
	}

	protected function listKeysSignedParameters(): array
	{
		return [
			'ENTITY_TYPE_ID',
			'ENTITY_ID',
		];
	}

	public function configureActions(): array
	{
		return [];
	}

	public function saveAction(array $data): ?array
	{
		//@codingStandardsIgnoreStart
		$this->arParams['categoryId'] = $data['CATEGORY_ID'] ?? null;
		//@codingStandardsIgnoreEnd
		$this->mode = isset($data['MODE']) ? (int)$data['MODE'] : $this->mode;

		$sourceEntityTypeId = (int)($data['CONVERSION_SOURCE']['entityTypeId'] ?? 0);
		$sourceEntityId = (int)($data['CONVERSION_SOURCE']['entityId'] ?? 0);
		if (($sourceEntityId > 0) && \CCrmOwnerType::IsDefined($sourceEntityTypeId))
		{
			$this->conversionSource = new ItemIdentifier($sourceEntityTypeId, $sourceEntityId);
		}

		$this->init();
		if($this->getErrors())
		{
			return null;
		}

		if(empty($data))
		{
			$this->errorCollection[] = new Error('No data');
			return null;
		}

		// Save to the variable before all actions because after save the item won't be new anyway
		$isNew = $this->item->isNew();

		$processedData = $this->processItemFieldValues($data);
		foreach($processedData as $name => $value)
		{
			try
			{
				$this->item->set($name, $value);
			}
			catch (ObjectException $exception)
			{
				$field = $this->factory->getFieldsCollection()->getField($name);
				if ($field)
				{
					$this->errorCollection[] = $field->getValueNotValidError();
				}
			}
		}

		if ($this->factory->isLinkWithProductsEnabled() && isset($data[$this->getProductDataFieldName()]))
		{
			$result = $this->editorAdapter->saveProductsData(
				$this->item,
				$data[$this->getProductDataFieldName()]
			);
			if (!$result->isSuccess())
			{
				$this->errorCollection->add($result->getErrors());
			}
		}

		if ($this->factory->isClientEnabled() && isset($data[EditorAdapter::FIELD_CLIENT_DATA_NAME]))
		{
			// TODO: compare incoming category ID with actual category ID from store

			$result = $this->editorAdapter->saveClientData(
				$this->item,
				$data[EditorAdapter::FIELD_CLIENT_DATA_NAME]
			);

			if ($result->isSuccess())
			{
				/** @var ItemIdentifier[] $processedEntities */
				$processedEntities = $result->getData()['processedEntities'] ?? [];
				foreach ($processedEntities as $identifier)
				{
					$this->addRecentlyUsedItem($identifier->getEntityTypeId(), $identifier->getEntityId());
				}
				$requisiteBinding = (array)($result->getData()['requisiteBinding'] ?? []);
				if (!empty($requisiteBinding))
				{
					$data = array_merge($data, $requisiteBinding);
				}
			}
			else
			{
				$this->errorCollection->add($result->getErrors());
			}
		}

		if ($this->factory->isMyCompanyEnabled() && isset($data[EditorAdapter::FIELD_MY_COMPANY_DATA_NAME]))
		{
			$result = $this->editorAdapter->saveMyCompanyDataFromEmbeddedEditor(
				$this->item,
				$data[EditorAdapter::FIELD_MY_COMPANY_DATA_NAME]
			);
			if (!$result->isSuccess())
			{
				$this->errorCollection->add($result->getErrors());
			}
			else
			{
				$data = array_merge($data, $result->getData());
			}
		}

		$beforeSaveData = $this->item->getData();

		$operation = $this->getOperation();
		$eventId = $data['EVENT_ID'] ?? null;
		if (!empty($eventId) && is_string($eventId))
		{
			$context = clone Container::getInstance()->getContext();
			$context->setEventId($eventId);

			$operation->setContext($context);
		}

		$result = $operation->launch();

		if(!$result->isSuccess())
		{
			$checkErrors = [];
			$errors = $result->getErrors();
			foreach ($errors as $error)
			{
				if (
					$error->getCode() === Field::ERROR_CODE_REQUIRED_FIELD_ATTRIBUTE
					&& !empty($error->getCustomData()['fieldName'])
				)
				{
					$checkErrors[$error->getCustomData()['fieldName']] = $error->getMessage();
				}
			}
			if (!empty($checkErrors))
			{
				return [
					'CHECK_ERRORS' => $checkErrors,
					'ERROR' => implode(', ', $result->getErrorMessages()),
				];
			}
			$this->errorCollection->add($result->getErrors());
			return null;
		}

		if(
			$this->factory->isClientEnabled()
			|| $this->factory->isMyCompanyEnabled()
		)
		{
			$this->saveRequisites($beforeSaveData, $data);
		}

		if ($this->factory->isCrmTrackingEnabled())
		{
			\Bitrix\Crm\Tracking\UI\Details::saveEntityData(
				$this->getEntityTypeID(),
				$this->item->getId(),
				$data,
				$isNew
			);
		}

		$this->initializeEditorAdapter();

		$result = [
			'ENTITY_ID' => $this->item->getId(),
			'ENTITY_DATA' => $this->editorAdapter->getEntityData(),
			'ADDITIONAL_FIELDS_DATA' => $this->getAdditionalFieldsData(),
		];

		if($isNew)
		{
			$result['REDIRECT_URL'] = Container::getInstance()->getRouter()->getItemDetailUrl(
				$this->getEntityTypeID(),
				$this->item->getId()
			);
		}

		$conversionWizard = $this->getConversionWizard();
		if ($this->isConversionMode() && $conversionWizard !== null)
		{
			$conversionWizard->attachNewlyCreatedEntity($this->factory->getEntityName(), $this->item->getId());
			$conversionRedirectUrl = $conversionWizard->getRedirectUrl();
			if (!empty($conversionRedirectUrl))
			{
				// override redirect url
				$result['REDIRECT_URL'] = $conversionRedirectUrl;
				$result['OPEN_IN_NEW_SLIDE'] = true;
				$result['EVENT_PARAMS'] = $conversionWizard->getClientEventParams();
			}
		}

		return $result;
	}

	public function loadAction(): ?array
	{
		$this->init();
		if ($this->getErrors())
		{
			return null;
		}

		$this->initializeEditorAdapter();

		$result = [
			'ENTITY_ID' => $this->item->getId(),
			'ENTITY_DATA' => $this->editorAdapter->getEntityData(),
			'ADDITIONAL_FIELDS_DATA' => $this->getAdditionalFieldsData(),
		];

		return $result;
	}

	protected function processItemFieldValues(array $data): array
	{
		$setData = [];

		global $USER_FIELD_MANAGER;
		$USER_FIELD_MANAGER->EditFormAddFields($this->getUserFieldEntityId(), $data, ['FORM' => $data]);

		$parentTypeId = (int)($data['PARENT_TYPE_ID'] ?? 0);
		$parentId = (int)($data['PARENT_ID'] ?? 0);
		if ($parentTypeId > 0 && $parentId > 0)
		{
			$data[ParentFieldManager::getParentFieldName($parentTypeId)] = $parentId;
		}
		foreach ($data as $name => $value)
		{
			$field = $this->factory->getFieldsCollection()->getField($name);
			if (!$field)
			{
				continue;
			}
			if ($field->isItemValueEmpty($this->item) && $field->isValueEmpty($value))
			{
				continue;
			}
			if ($field->isUserField())
			{
				$userType = $field->getUserField()['USER_TYPE']['USER_TYPE_ID'];
				$deletedFieldName = $name . '_del';
				if (isset($data[$deletedFieldName]) && $field->isFileUserField())
				{
					if (is_array($data[$name]) && is_array($data[$deletedFieldName]))
					{
						$value = array_diff($data[$name], $data[$deletedFieldName]);
					}
					elseif (is_numeric($data[$name]) && (int) $data[$name] === (int) $data[$deletedFieldName])
					{
						$value = null;
					}
				}
				elseif ($userType === DoubleType::USER_TYPE_ID)
				{
					$value = str_replace(',', '.', $value);
				}
				elseif (
					$userType === DateTimeType::USER_TYPE_ID
					/**
					 * @see \Bitrix\Main\UserField\Internal\PrototypeItemDataManager::convertSingleValueBeforeSave
					 * this case is already handled there
					 * @todo refactor and remove this crutch
					 */
					&& !\CCrmOwnerType::isUseDynamicTypeBasedApproach($this->item->getEntityTypeId())
				)
				{
					$useTimezone = $field->getUserField()['SETTINGS']['USE_TIMEZONE'] ?? 'Y';
					if ($useTimezone !== 'N')
					{
						if (is_array($value) && $field->isMultiple())
						{
							foreach ($value as &$singleValue)
							{
								if (is_string($singleValue))
								{
									$singleValue = DateTime::createFromUserTime($singleValue);
								}
							}
							unset($singleValue);
						}
						elseif (is_string($value))
						{
							$value = DateTime::createFromUserTime($value);
						}
					}
				}

				if ($userType === FileType::USER_TYPE_ID && $this->isCopyMode())
				{
					if (is_array($value) && $field->isMultiple())
					{
						foreach ($value as $singleValue)
						{
							CopyFilesOnItemClone::removeFileFromNotUsedCleanQueue($singleValue);
						}
					}
					elseif(is_numeric($value))
					{
						CopyFilesOnItemClone::removeFileFromNotUsedCleanQueue($value);
					}
				}
			}

			$setData[$name] = $value;
		}

		return $setData;
	}

	public function compatibleAction(int $entityTypeId, int $entityId): ?Json
	{
		$requestData = $this->request->toArray();
		$action = $requestData['ACTION'];
		if ($action === 'GET_FORMATTED_SUM')
		{
			$sum = (float)$requestData['SUM'];
			$currencyId = (string)$requestData['CURRENCY_ID'];

			return new Json([
				'FORMATTED_SUM' => Money::formatWithCustomTemplate($sum, $currencyId),
				'FORMATTED_SUM_WITH_CURRENCY' => Money::format($sum, $currencyId),
			]);
		}
		if($action === 'SAVE')
		{
			//@codingStandardsIgnoreStart
			// it would be better to use signedParameters, but processing is encapsulated in Engine\Controller
			$this->arParams['ENTITY_TYPE_ID'] = $entityTypeId;
			$this->arParams['ENTITY_ID'] = $entityId;
			//@codingStandardsIgnoreEnd

			$data = array_intersect_key($requestData, [
				Item::FIELD_NAME_TITLE => true,

				Item::FIELD_NAME_ASSIGNED => true,

				Item::FIELD_NAME_OBSERVERS => true,

				EditorAdapter::FIELD_REQUISITE_ID => true,
				EditorAdapter::FIELD_BANK_DETAIL_ID => true,
				Item::FIELD_NAME_MYCOMPANY_ID => true,
				EditorAdapter::FIELD_MY_COMPANY_REQUISITE_ID => true,
				EditorAdapter::FIELD_MY_COMPANY_BANK_DETAIL_ID => true,
			]);

			return new Json($this->saveAction($data));
		}

		return null;
	}

	protected function saveRequisites(array $beforeSaveData, array $data): void
	{
		$requisiteInfo = EntityLink::determineRequisiteLinkBeforeSave(
			$this->factory->getEntityTypeId(),
			$this->item->getId(),
			EntityLink::ENTITY_OPERATION_UPDATE,
			$beforeSaveData,
			false,
			$data[EditorAdapter::FIELD_REQUISITE_ID] ?? null,
			$data[EditorAdapter::FIELD_BANK_DETAIL_ID] ?? null,
			$data[EditorAdapter::FIELD_MY_COMPANY_REQUISITE_ID] ?? null,
			$data[EditorAdapter::FIELD_MY_COMPANY_BANK_DETAIL_ID] ?? null
		);

		EntityLink::register(
			$this->factory->getEntityTypeId(),
			$this->item->getId(),
			$requisiteInfo[EditorAdapter::FIELD_REQUISITE_ID] ?? null,
			$requisiteInfo[EditorAdapter::FIELD_BANK_DETAIL_ID] ?? null,
			$requisiteInfo[EditorAdapter::FIELD_MY_COMPANY_REQUISITE_ID] ?? null,
			$requisiteInfo[EditorAdapter::FIELD_MY_COMPANY_BANK_DETAIL_ID] ?? null
		);
	}

	protected function addRecentlyUsedItem(int $entityTypeId, int $id): void
	{
		// TODO: need to detect category ID when will implement real category params feature
		$categoryParams = CCrmComponentHelper::getEntityClientFieldCategoryParams($this->factory->getEntityTypeId());

		Entity::addLastRecentlyUsedItems(
			$this->getComponentName(),
			mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId)),
			[
				[
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_ID' => $id,
					'CATEGORY_ID' => $categoryParams[$entityTypeId]['categoryId'] ?? 0,
				]
			]
		);
	}

	protected function getOperation(): Operation
	{
		if($this->operation === null)
		{
			if($this->item->getId() > 0)
			{
				$this->operation = $this->factory->getUpdateOperation($this->item);
			}
			else
			{
				$this->operation = $this->factory->getAddOperation($this->item);
			}
		}

		return $this->operation;
	}

	protected function isDocumentButtonAvailable(): bool
	{
		return (
			!$this->item->isNew()
			&& DocumentGeneratorManager::getInstance()->isDocumentButtonAvailable()
			&& $this->factory->isDocumentGenerationEnabled()
		);
	}

	protected function getBizprocToolbarButton(): Buttons\Button
	{
		return new Buttons\Button([
			'baseClassName' => 'ui-btn',
			'classList' => [
				'ui-btn-md',
				'ui-btn-light-border',
				'ui-btn-themes',
				'crm-bizproc-starter-icon'
			],
			'onclick' => new Buttons\JsEvent('BX.Crm.ItemDetailsComponent:onClickBizprocTemplates'),
		]);
	}

	protected function getDocumentToolbarButton(): Buttons\Button
	{
		$button = new Buttons\Button([
			'text' => Loc::getMessage('CRM_COMMON_DOCUMENT'),
			'baseClassName' => 'ui-btn',
			'classList' => ['ui-btn-light-border', 'ui-btn-dropdown', 'ui-btn-themes', 'crm-btn-dropdown-document'],
		]);

		$button->addAttribute('id', $this->getDocumentButtonId());

		return $button;
	}

	protected function getDocumentButtonId(): string
	{
		return 'crm-document-button';
	}

	protected function getIntranetBindingMenuParameters(): ?array
	{
		return [
			'SECTION_CODE' => Integration\Intranet\BindingMenu\SectionCode::DETAIL,
			'MENU_CODE' => Integration\Intranet\BindingMenu\CodeBuilder::getMenuCode($this->factory->getEntityTypeId()),
			'CONTEXT' => [
				'ENTITY_ID' => $this->getEntityID(),
			],
		];
	}

	protected function getEditorAttributeConfig(): ?array
	{
		if(!$this->factory->isStagesEnabled())
		{
			return null;
		}

		$entityPhases = [];
		$stages = $this->factory->getStages($this->categoryId);
		foreach($stages as $stage)
		{
			$semantics = 'process';
			if ($stage->getSemantics() === PhaseSemantics::SUCCESS)
			{
				$semantics = 'success';
			}
			elseif ($stage->getSemantics() === PhaseSemantics::FAILURE)
			{
				$semantics = 'failure';
			}
			$entityPhases[] = [
				'id' => $stage->getStatusId(),
				'name' => $stage->getName(),
				'sort' => $stage->getSort(),
				'color' => $stage->getColor(),
				'semantics' => $semantics,
			];
		}

		return [
			'ENTITY_SCOPE' => FieldAttributeManager::getItemConfigScope($this->item),
			'CAPTIONS' => FieldAttributeManager::getCaptionsForEntityWithStages($this->entityTypeId),
			'ENTITY_PHASES' => $entityPhases,
		];
	}

	protected function getServiceUrl(): Uri
	{
		return UrlManager::getInstance()->createByBitrixComponent($this, 'compatible', [
			'entityTypeId' => $this->getEntityTypeID(),
			'entityId' => $this->getEntityID(),
			'sessid' => bitrix_sessid(),
		]);
	}

	protected function getEditorContext(): array
	{
		if ($this->context)
		{
			return $this->context;
		}

		$context = [
			'MODE' => $this->mode,
		];

		if ($this->isConversionMode() && $this->getConversionWizard())
		{
			$context = array_merge(
				$context,
				$this->getConversionWizard()->prepareEditorContextParams($this->factory->getEntityTypeId())
			);
		}

		if($this->category)
		{
			$context['CATEGORY_ID'] = $this->category->getId();
		}
		//@codingStandardsIgnoreStart
		if(
			!empty($this->arParams['parentTypeId'])
			&& !empty($this->arParams['parentId'])
			&& $this->isRelationExist($this->arParams['parentTypeId'])
		)
		{
			$context[EditorAdapter::CONTEXT_PARENT_TYPE_ID] = (int)$this->arParams['parentTypeId'];
			$context[EditorAdapter::CONTEXT_PARENT_TYPE_NAME] = \CCrmOwnerType::ResolveName($context['PARENT_TYPE_ID']);
			$context[EditorAdapter::CONTEXT_PARENT_ID] = (int)$this->arParams['parentId'];
		}
		//@codingStandardsIgnoreEnd

		foreach ($this->parseParentIdsFromRequest() as $fieldName => $parentIdentifier)
		{
			$context[$fieldName] = $parentIdentifier->getEntityId();
		}

		$this->context = $context;

		return $this->context;
	}

	protected function isRelationExist(int $parentTypeId): bool
	{
		$identifier = new RelationIdentifier($parentTypeId, $this->getEntityTypeID());

		return Container::getInstance()->getRelationManager()->areTypesBound($identifier);
	}

	/**
	 * @param Request|null $request
	 * @return ItemIdentifier[]
	 */
	public function parseParentIdsFromRequest(Request $request = null): array
	{
		if ($this->parentIdentifiers !== null)
		{
			return $this->parentIdentifiers;
		}
		if (!$request)
		{
			if ($this->request && $this->request instanceof Request)
			{
				$request = $this->request;
			}
		}
		if (!$request)
		{
			$request = Application::getInstance()->getContext()->getRequest();
		}
		$this->parentIdentifiers = [];
		$parentRelations = Container::getInstance()->getRelationManager()->getParentRelations($this->getEntityTypeID());
		foreach ($parentRelations as $relation)
		{
			$parentEntityTypeId = $relation->getParentEntityTypeId();
			$entityName = mb_strtolower(\CCrmOwnerType::ResolveName($parentEntityTypeId)) . '_id';
			$entityId = (int)$request->get($entityName);
			if ($entityId > 0)
			{
				$fieldName = $this->getEditorFieldNameForParent($relation);
				if ($fieldName)
				{
					$this->parentIdentifiers[$fieldName] = new ItemIdentifier($relation->getParentEntityTypeId(), $entityId);
				}
			}
		}

		return $this->parentIdentifiers;
	}

	protected function getEditorFieldNameForParent(Relation $relation): ?string
	{
		if ($relation->isPredefined())
		{
			return \CCrmOwnerType::ResolveName($relation->getParentEntityTypeId()) . '_ID';
		}

		return EditorAdapter::getParentFieldName($relation->getParentEntityTypeId());
	}

	protected function fillParentFields(): void
	{
		$parentItems = $this->parseParentIdsFromRequest();
		foreach ($parentItems as $fieldName => $itemIdentifier)
		{
			if ($this->item->hasField($fieldName))
			{
				$this->item->set($fieldName, $itemIdentifier->getEntityId());
			}
		}
	}

	protected function fillItemFromRequest(): void
	{
		$this->prepareEntityUserFields();
		$this->prepareEntityDataScheme();

		$fieldsValues = [];
		$userFieldsValues = [];
		foreach ($this->userFields as $userFieldName => $userFieldInfo)
		{
			$userFieldsValues[$userFieldName] = ['VALUE' => null];
		}
		\Bitrix\Crm\Entity\EntityEditor::mapRequestData(
			$this->entityDataScheme,
			$fieldsValues,
			$userFieldsValues
		);

		foreach ($fieldsValues as $fieldName => $fieldValue)
		{
			if ($this->item->hasField($fieldName))
			{
				$this->item->set($fieldName, $fieldValue);
			}
		}
		foreach ($userFieldsValues as $fieldName => $fieldValue)
		{
			if ($this->item->hasField($fieldName) && ($fieldValue['VALUE'] ?? null))
			{
				$this->item->set($fieldName, $fieldValue['VALUE']);
			}
		}
	}

	protected function getEntityFieldsInfo()
	{
		return $this->factory->getFieldsInfo();
	}

	protected function getProductsData(): ?array
	{
		return $this->editorAdapter->getSrcItemProductsEntityData();
	}

	protected function tryShowCustomErrors(): bool
	{
		if (empty($this->entityTypeId))
		{
			return false;
		}

		$userPermission = Container::getInstance()->getUserPermissions();

		if (!$this->item)
		{
			EntityDetails\Error::showError(EntityDetails\Error::EntityNotExist, $this->entityTypeId);

			return true;
		}

		if ($this->entityID <= 0)
		{
			if (!$userPermission->checkAddPermissions($this->entityTypeId, $this->category?->getId()))
			{
				EntityDetails\Error::showError(EntityDetails\Error::NoAddPermission, $this->entityTypeId);

				return true;
			}
			elseif (
				!$userPermission->checkReadPermissions($this->entityTypeId, $this->entityID, $this->category?->getId())
				|| $this->isIframe()
			)
			{
				EntityDetails\Error::showError(EntityDetails\Error::NoAccessToEntityType, $this->entityTypeId);

				return true;
			}
		}
		else
		{
			if (!$this->checkIfEntityExists())
			{
				EntityDetails\Error::showError(EntityDetails\Error::EntityNotExist, $this->entityTypeId);

				return true;
			}

			if ($this->isCopyMode())
			{
				if (!$userPermission->checkReadPermissions($this->entityTypeId, $this->entityID, $this->category?->getId()))
				{
					EntityDetails\Error::showError(EntityDetails\Error::NoReadPermission, $this->entityTypeId);

					return true;
				}
				elseif (!$userPermission->checkAddPermissions($this->entityTypeId, $this->category?->getId()))
				{
					EntityDetails\Error::showError(EntityDetails\Error::NoAddPermission, $this->entityTypeId);

					return true;
				}
			}
			else
			{
				if (!$userPermission->checkReadPermissions($this->entityTypeId))
				{
					EntityDetails\Error::showError(EntityDetails\Error::NoAccessToEntityType, $this->entityTypeId);

					return true;
				}
				elseif (!$userPermission->canReadItem($this->item))
				{
					EntityDetails\Error::showError(EntityDetails\Error::NoReadPermission, $this->entityTypeId);

					return true;
				}
			}
		}

		return false;
	}
}
