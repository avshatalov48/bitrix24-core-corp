<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor\DefaultEntityConfig;

use Bitrix\Crm\CustomerType;
use Bitrix\Crm\Integration\UI\EntityEditor\AbstractDefaultEntityConfig;
use Bitrix\Main\Localization\Loc;

final class LeadDefaultEntityConfig extends AbstractDefaultEntityConfig
{
	public function __construct(
		private readonly array $userFieldNames = [],
		private readonly array $multiFieldNames = [],
		private readonly int $customerType = CustomerType::GENERAL,
	)
	{
	}

	public function get(): array
	{
		$userFieldConfigElements = $this->formatFieldNames($this->userFieldNames);
		$multiFieldConfigElements = $this->formatFieldNames($this->multiFieldNames);

		$sectionMain = [
			'name' => 'main',
			'title' => Loc::getMessage('CRM_LEAD_SECTION_MAIN'),
			'type' => 'section',
			'elements' => [],
		];

		$sectionAdditional = [
			'name' => 'additional',
			'title' => Loc::getMessage('CRM_LEAD_SECTION_ADDITIONAL'),
			'type' => 'section',
			'elements' => [],
		];

		$sectionProductRow = [
			'name' => 'products',
			'title' => Loc::getMessage('CRM_LEAD_SECTION_PRODUCTS'),
			'type' => 'section',
			'elements' => [
				['name' => 'PRODUCT_ROW_SUMMARY'],
			]
		];

		if ($this->customerType === CustomerType::GENERAL)
		{
			$sectionMain['elements'] = [
				['name' => 'TITLE'],
				['name' => 'STATUS_ID'],
				['name' => 'OPPORTUNITY_WITH_CURRENCY'],
				['name' => 'CLIENT'],
				['name' => 'HONORIFIC'],
				['name' => 'LAST_NAME'],
				['name' => 'NAME'],
				['name' => 'SECOND_NAME'],
				['name' => 'BIRTHDATE'],
				['name' => 'POST'],
				['name' => 'COMPANY_TITLE'],
				...$multiFieldConfigElements,
			];

			$sectionAdditional['elements'] = [
				['name' => 'SOURCE_ID'],
				['name' => 'SOURCE_DESCRIPTION'],
				['name' => 'OPENED'],
				['name' => 'ASSIGNED_BY_ID'],
				['name' => 'OBSERVER'],
				['name' => 'COMMENTS'],
				['name' => 'ADDRESS'],
				['name' => 'UTM'],
				...$userFieldConfigElements,
			];
		}
		elseif ($this->customerType === CustomerType::RETURNING)
		{
			$sectionMain['elements'] = [
				['name' => 'TITLE'],
				['name' => 'STATUS_ID'],
				['name' => 'OPPORTUNITY_WITH_CURRENCY'],
				['name' => 'CLIENT'],
			];

			$sectionAdditional['elements'] = [
				['name' => 'SOURCE_ID'],
				['name' => 'SOURCE_DESCRIPTION'],
				['name' => 'OPENED'],
				['name' => 'ASSIGNED_BY_ID'],
				['name' => 'COMMENTS'],
				['name' => 'UTM'],
				$userFieldConfigElements,
			];
		}

		return [
			$sectionMain,
			$sectionAdditional,
			$sectionProductRow,
		];
	}
}
