<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor\DefaultEntityConfig;

use Bitrix\Crm\Integration\UI\EntityEditor\AbstractDefaultEntityConfig;
use Bitrix\Main\Localization\Loc;

final class DealDefaultEntityConfig extends AbstractDefaultEntityConfig
{
	public function __construct(
		private readonly array $userFieldNames = [],
	)
	{
	}

	public function get(): array
	{
		$userFieldConfigElements = $this->formatFieldNames($this->userFieldNames);

		return [
			[
				'name' => 'main',
				'title' => Loc::getMessage('CRM_DEAL_SECTION_MAIN'),
				'type' => 'section',
				'elements' => [
					['name' => 'TITLE'],
					['name' => 'STAGE_ID'],
					['name' => 'OPPORTUNITY_WITH_CURRENCY'],
					['name' => 'CLOSEDATE'],
					['name' => 'CLIENT'],
				],
			],
			[
				'name' => 'additional',
				'title' => Loc::getMessage('CRM_DEAL_SECTION_ADDITIONAL'),
				'type' => 'section',
				'elements' => [
					['name' => 'TYPE_ID'],
					['name' => 'SOURCE_ID'],
					['name' => 'SOURCE_DESCRIPTION'],
					['name' => 'BEGINDATE'],
					//['name' => 'LOCATION_ID'],
					['name' => 'OPENED'],
					['name' => 'ASSIGNED_BY_ID'],
					['name' => 'OBSERVER'],
					['name' => 'COMMENTS'],
					['name' => 'UTM'],
					...$userFieldConfigElements,
				],
			],
			[
				'name' => 'products',
				'title' => Loc::getMessage('CRM_DEAL_SECTION_PRODUCTS'),
				'type' => 'section',
				'elements' => [
					['name' => "PRODUCT_ROW_SUMMARY"],
				],
			],
			[
				'name' => 'recurring',
				'title' => self::getRecurringSectionTitle(),
				'type' => 'section',
				'elements' => [
					['name' => 'RECURRING'],
				],
			],
		];
	}

	public static function getRecurringSectionTitle(): ?string
	{
		return Loc::getMessage('CRM_DEAL_SECTION_RECURRING');
	}
}
