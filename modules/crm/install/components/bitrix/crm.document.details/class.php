<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Component\EntityDetails\FactoryBased;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::includeModule('crm');

class CrmSmartDocumentDetailsComponent extends FactoryBased
{
	public function getEntityTypeID(): int
	{
		return \CCrmOwnerType::SmartDocument;
	}

	public function onPrepareComponentParams($arParams): array
	{
		$arParams['ENTITY_TYPE_ID'] = CCrmOwnerType::SmartDocument;
		return parent::onPrepareComponentParams($arParams);
	}


	public function getEditorEntityConfig(): array
	{
		$sections = [];

		$sectionMain = [
			'name' => 'main',
			'title' => Loc::getMessage('CRM_COMPONENT_FACTORYBASED_EDITOR_MAIN_SECTION_TITLE'),
			'type' => 'section',
			'elements' => [
				['name' => Item::FIELD_NAME_TITLE],
				['name' => EditorAdapter::FIELD_OPPORTUNITY],
			],
		];

		$sections[] = $sectionMain;

		$sectionAdditional = [
			'name' => 'additional',
			'title' => Loc::getMessage('CRM_TYPE_ITEM_EDITOR_SECTION_ADDITIONAL'),
			'type' => 'section',
			'elements' => [
				['name' => EditorAdapter::FIELD_CLIENT],
				['name' => Item::FIELD_NAME_MYCOMPANY_ID],
				['name' => Item::FIELD_NAME_ASSIGNED],
			],
		];

		$sections[] = $sectionAdditional;

		return $sections;
	}

	public function getInlineEditorEntityConfig(): array
	{
		$sections = [];
		$sections[] = [
			'name' => 'myCompany',
			'title' => Loc::getMessage('CRM_COMPONENTS_DOCUMENT_DETAILS_MY_COMPANY_SECTION_TITLE'),
			'type' => 'section',
			'elements' => [
				['name' => \Bitrix\Crm\Item::FIELD_NAME_MYCOMPANY_ID],
			],
		];

		$sections[] = [
			'name' => 'client',
			'title' => Loc::getMessage('CRM_COMPONENTS_DOCUMENT_DETAILS_CLIENT_SECTION_TITLE'),
			'type' => 'section',
			'elements' => [
				['name' => EditorAdapter::FIELD_CLIENT],
			],
		];

		return $sections;
	}

	public function executeComponent()
	{
		$this->init();

		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		$this->executeBaseLogic();

		$this->includeComponentTemplate();
	}
}
