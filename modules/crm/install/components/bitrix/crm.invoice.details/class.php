<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Component\EntityDetails\FactoryBased;
use Bitrix\Crm\Item;
use Bitrix\Crm\Kanban\Entity\Deadlines\DeadlinesStageManager;
use Bitrix\Crm\Kanban\ViewMode;
use Bitrix\Crm\Security\EntityPermissionType;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use \Bitrix\Crm\Component\EntityDetails\Error;

Loader::includeModule('crm');

class CrmSmartInvoiceDetailsComponent extends FactoryBased
{
	public function getEntityTypeID(): int
	{
		return \CCrmOwnerType::SmartInvoice;
	}

	public function onPrepareComponentParams($arParams): array
	{
		$arParams['ENTITY_TYPE_ID'] = CCrmOwnerType::SmartInvoice;
		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		$this->init();

		if ($this->getErrors())
		{
			if ($this->tryShowCustomErrors())
			{
				return;
			}
			$this->includeComponentTemplate();

			return;
		}


		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		if ($this->isPreviewItemBeforeCopyMode())
		{
			$this->item->unset(Item\SmartInvoice::FIELD_NAME_ACCOUNT_NUMBER);
		}

		$this->executeBaseLogic();

		$this->setBizprocStarterConfig();

		$this->includeComponentTemplate();
	}

	public function getEditorEntityConfig(): array
	{
		$sections = [];

		$mainElements = [
			['name' => Item::FIELD_NAME_STAGE_ID],
			['name' => EditorAdapter::FIELD_OPPORTUNITY],
			['name' => Item::FIELD_NAME_BEGIN_DATE],
			['name' => Item::FIELD_NAME_CLOSE_DATE],
		];
		if (\Bitrix\Crm\Service\Container::getInstance()->getRelationManager()->areTypesBound(
			new \Bitrix\Crm\RelationIdentifier(
				\CCrmOwnerType::Deal,
				\CCrmOwnerType::SmartInvoice
			)
		))
		{
			$mainElements[] = [
				'name' => \Bitrix\Crm\Service\ParentFieldManager::getParentFieldName(\CCrmOwnerType::Deal),
			];
		}
		$mainElements[] = ['name' => Item\SmartInvoice::FIELD_NAME_COMMENTS];
		$mainElements[] = ['name' => Item\SmartInvoice::FIELD_NAME_ASSIGNED];

		$sections[] = [
			'name' => 'main',
			'title' => Loc::getMessage('CRM_INVOICE_DETAILS_COMPONENT_MAIN_SECTION'),
			'type' => 'section',
			'elements' => $mainElements,
		];

		$sections[] = [
			'name' => 'payer',
			'title' => Loc::getMessage('CRM_INVOICE_DETAILS_COMPONENT_PAYER_SECTION'),
			'type' => 'section',
			'elements' => [
				['name' => EditorAdapter::FIELD_CLIENT],
			],
		];

		$sections[] = [
			'name' => 'receiver',
			'title' => Loc::getMessage('CRM_INVOICE_DETAILS_COMPONENT_RECEIVER_SECTION'),
			'type' => 'section',
			'elements' => [
				['name' => Item\SmartInvoice::FIELD_NAME_MYCOMPANY_ID],
			],
		];

		$sections[] = [
			'name' => 'products',
			'title' => Loc::getMessage('CRM_COMMON_PRODUCTS'),
			'type' => 'section',
			'elements' => [
				['name' => EditorAdapter::FIELD_PRODUCT_ROW_SUMMARY],
			],
		];

		$sectionAdditional = [
			'name' => 'additional',
			'title' => Loc::getMessage('CRM_TYPE_ITEM_EDITOR_SECTION_ADDITIONAL'),
			'type' => 'section',
			'elements' => [],
		];
		foreach($this->prepareEntityUserFields() as $fieldName => $userField)
		{
			$sectionAdditional['elements'][] = [
				'name' => $fieldName,
			];
		}
		$sections[] = $sectionAdditional;

		return $sections;
	}

	protected function getTimelineHistoryStubMessage(): ?string
	{
		return Loc::getMessage('CRM_INVOICE_DETAILS_COMPONENT_TIMELINE_HISTORY_STUB');
	}

	protected function getTitle(): string
	{
		if ($this->isPreviewItemBeforeCopyMode())
		{
			return (string)Loc::getMessage('CRM_INVOICE_DETAILS_COMPONENT_TITLE_COPY');
		}

		if($this->isNewItem())
		{
			return (string)Loc::getMessage('CRM_INVOICE_DETAILS_COMPONENT_TITLE_NEW');
		}

		return (string)$this->item->getHeading();
	}

	protected function isPageTitleEditable(): bool
	{
		return true;
	}

	public function getInlineEditorEntityConfig(): array
	{
		$sections = [];
		$sections[] = [
			'name' => 'main',
			'title' => Loc::getMessage('CRM_INVOICE_DETAILS_COMPONENT_MAIN_SECTION'),
			'type' => 'section',
			'elements' => [
				['name' => EditorAdapter::FIELD_OPPORTUNITY],
				['name' => Item::FIELD_NAME_BEGIN_DATE],
				['name' => Item::FIELD_NAME_CLOSE_DATE],
				['name' => Item\SmartInvoice::FIELD_NAME_ASSIGNED],
				['name' => Item\SmartInvoice::FIELD_NAME_MYCOMPANY_ID],
				['name' => EditorAdapter::FIELD_CLIENT],
			],
		];

		return $sections;
	}

	public function initializeEditorAdapter(): void
	{
		parent::initializeEditorAdapter();

		$locationString = CCrmLocations::getLocationStringByCode($this->item->getLocationId());
		if (empty($locationString))
		{
			$locationString = '';
		}
		$this->editorAdapter->addEntityData(Item::FIELD_NAME_LOCATION_ID . '_VIEW_HTML', $locationString);
		$this->editorAdapter->addEntityData(
			Item::FIELD_NAME_LOCATION_ID . '_EDIT_HTML',
			EditorAdapter::getLocationFieldHtml(
				$this->item,
				Item::FIELD_NAME_LOCATION_ID
			)
		);
		$this->editorAdapter->addEntityData(
			'IS_USE_NUMBER_IN_TITLE_PLACEHOLDER',
			\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isUseNumberInTitlePlaceholder(),
		);
	}

	protected function getTabCodes(): array
	{
		$tabCodes = parent::getTabCodes();

		if (!CCrmSaleHelper::isWithOrdersMode())
		{
			unset($tabCodes['tab_order']);
		}

		return $tabCodes;
	}

	public function saveAction(array $data): ?array
	{
		$data = $this->calculateDefaultDataValues($data);
		$result = parent::saveAction($data);

		if (!$this->getErrors() && $this->item && !$this->isNewItem())
		{
			\Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->enqueueItemScheduledDocumentsForActualization(
				\Bitrix\Crm\ItemIdentifier::createByItem($this->item),
				$this->userID,
				\Bitrix\Crm\Integration\DocumentGeneratorManager::ACTUALIZATION_POSITION_BACKGROUND,
			);
		}

		return $result;
	}

	private function calculateDefaultDataValues(array $data): array
	{
		$deadlineStage = $data['DEADLINE_STAGE'] ?? '';
		$viewMode = $data['VIEW_MODE'] ?? '';
		$isNew = (int) $this->arParams['ENTITY_ID'] === 0;
		if (
			$isNew &&
			$viewMode === ViewMode::MODE_DEADLINES &&
			!empty($deadlineStage)
		)
		{
			$data = (new DeadlinesStageManager($this->getEntityTypeID()))
				->fillDeadlinesDefaultValues($data, $deadlineStage);
		}
		return $data;
	}

	public function isNewItem(): bool
	{
		return $this->item->isNew();
	}

	protected function getExtras(): array
	{
		$extras = parent::getExtras();

		$extras['ANALYTICS'] = [
			'c_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_SMART_INVOICE,
			'c_sub_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SUB_SECTION_DETAILS,
		];

		return $extras;
	}
}
