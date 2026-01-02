<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor\DefaultEntityConfig;

use Bitrix\Crm\Integration\UI\EntityEditor\AbstractDefaultEntityConfig;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Main\Localization\Loc;

final class QuoteDefaultEntityConfig extends AbstractDefaultEntityConfig
{
	public function __construct(
		private readonly array $userFieldNames = [],
	)
	{
		Container::getInstance()->getLocalization()->loadMessages();
	}

	public function get(): array
	{
		$sectionMain = [
			'name' => 'main',
			'title' => self::getMainSectionTitle(),
			'type' => 'section',
			'elements' => [
				['name' => Item\Quote::FIELD_NAME_STAGE_ID],
				['name' => Item\Quote::FIELD_NAME_NUMBER],
				['name' => Item\Quote::FIELD_NAME_TITLE],
				['name' => EditorAdapter::FIELD_OPPORTUNITY],
				['name' => EditorAdapter::FIELD_CLIENT],
				['name' => Item\Quote::FIELD_NAME_MYCOMPANY_ID],
				['name' => EditorAdapter::FIELD_FILES],
			],
		];

		if (Container::getInstance()->getAccounting()->isTaxMode())
		{
			$sectionMain['elements'][] = [
				'name' => Item::FIELD_NAME_LOCATION_ID,
			];
		}

		$sections[] = $sectionMain;

		$sectionAdditional = [
			'name' => 'additional',
			'title' => Loc::getMessage('CRM_TYPE_ITEM_EDITOR_SECTION_ADDITIONAL'),
			'type' => 'section',
			'elements' => [
				['name' => Item\Quote::FIELD_NAME_LEAD_ID],
				['name' => Item\Quote::FIELD_NAME_DEAL_ID],
				['name' => Item\Quote::FIELD_NAME_BEGIN_DATE],
				['name' => Item\Quote::FIELD_NAME_ACTUAL_DATE],
				['name' => Item\Quote::FIELD_NAME_CLOSE_DATE],
				['name' => Item\Quote::FIELD_NAME_OPENED],
				['name' => Item\Quote::FIELD_NAME_ASSIGNED],
				['name' => Item\Quote::FIELD_NAME_CONTENT],
				['name' => Item\Quote::FIELD_NAME_TERMS],
				['name' => Item\Quote::FIELD_NAME_COMMENTS],
				['name' => Item\Quote::FIELD_NAME_CLOSED],
				['name' => EditorAdapter::FIELD_UTM],
			],
		];

		foreach ($this->userFieldNames as $fieldName)
		{
			$sectionAdditional['elements'][] = [
				'name' => $fieldName,
			];
		}
		$sections[] = $sectionAdditional;

		$sections[] = [
			'name' => 'products',
			'title' => Loc::getMessage('CRM_COMMON_PRODUCTS'),
			'type' => 'section',
			'elements' => [
				['name' => EditorAdapter::FIELD_PRODUCT_ROW_SUMMARY],
			],
		];

		return $sections;
	}

	public static function getMainSectionTitle(): ?string
	{
		return Loc::getMessage('CRM_QUOTE_DETAILS_EDITOR_MAIN_SECTION_TITLE_MSGVER_1');
	}
}
