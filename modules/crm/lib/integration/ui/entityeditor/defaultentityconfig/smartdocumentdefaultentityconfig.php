<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor\DefaultEntityConfig;

use Bitrix\Crm\Integration\UI\EntityEditor\AbstractDefaultEntityConfig;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

final class SmartDocumentDefaultEntityConfig extends AbstractDefaultEntityConfig
{
	public function __construct(
		private readonly int $entityTypeId,
	)
	{
		Container::getInstance()->getLocalization()->loadMessages();
	}

	public function get(): array
	{
		$sections = [];

		$sectionMain = [
			'name' => 'main',
			'title' => Loc::getMessage('CRM_TYPE_ITEM_EDITOR_SECTION_MAIN'),
			'type' => 'section',
			'elements' => [
				['name' => Item::FIELD_NAME_TITLE],
				['name' =>  Item\SmartB2eDocument::FIELD_NAME_NUMBER],
			],
		];

		$sections[] = $sectionMain;

		$elements = [
			['name' => Item::FIELD_NAME_MYCOMPANY_ID],
			['name' => Item::FIELD_NAME_ASSIGNED],
		];

		if ($this->entityTypeId !== CCrmOwnerType::SmartB2eDocument)
		{
			$elements[] = ['name' => EditorAdapter::FIELD_CLIENT];
		}

		$sectionAdditional = [
			'name' => 'additional',
			'title' => Loc::getMessage('CRM_TYPE_ITEM_EDITOR_SECTION_ADDITIONAL'),
			'type' => 'section',
			'elements' => $elements,
		];

		$sections[] = $sectionAdditional;

		return $sections;
	}
}
