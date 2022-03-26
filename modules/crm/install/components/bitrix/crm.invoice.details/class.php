<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Component\EntityDetails\FactoryBased;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

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

		$this->includeComponentTemplate();
	}

	public function getEditorEntityConfig(): array
	{
		$sections = [];

		$mainElements = [
			['name' => Item\SmartInvoice::FIELD_NAME_ACCOUNT_NUMBER],
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

		if($this->item->isNew())
		{
			return (string)Loc::getMessage('CRM_INVOICE_DETAILS_COMPONENT_TITLE_NEW');
		}

		return (string)$this->item->getHeading();
	}

	protected function isPageTitleEditable(): bool
	{
		return false;
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

		$locationString = CCrmLocations::getLocationString($this->item->getLocationId());
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
}
