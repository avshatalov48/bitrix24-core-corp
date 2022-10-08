<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Component\EntityDetails\FactoryBased;
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

	public function getEditorConfig(): array
	{
		$config = parent::getEditorConfig();

		foreach ($config['ENTITY_FIELDS'] as &$field)
		{
			if(
				$field['name'] === EditorAdapter::FIELD_CLIENT
				&& isset($field['data']['clientEditorFieldsParams'][\CCrmOwnerType::ContactName]['REQUISITES'])
			)
			{
				$field['data']['clientEditorFieldsParams'][\CCrmOwnerType::ContactName]['REQUISITES']['isHidden'] = true;
				break;
			}
		}

		return $config;
	}
}
