<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor\DefaultEntityConfig;

use Bitrix\Crm\Integration\UI\EntityEditor\AbstractDefaultEntityConfig;
use Bitrix\Main\Localization\Loc;

final class CompanyDefaultEntityConfig extends AbstractDefaultEntityConfig
{
	public function __construct(
		private readonly array $userFieldNames = [],
		private readonly array $multiFieldNames = [],
	)
	{
	}

	public function get(): array
	{
		$multiFieldConfigElements = $this->formatFieldNames($this->multiFieldNames);
		$userFieldConfigElements = $this->formatFieldNames($this->userFieldNames);

		return [
			[
				'name' => 'main',
				'title' => Loc::getMessage('CRM_COMPANY_SECTION_MAIN'),
				'type' => 'section',
				'elements' => [
					['name' => 'TITLE'],
					['name' => 'LOGO'],
					['name' => 'COMPANY_TYPE'],
					['name' => 'INDUSTRY'],
					['name' => 'REVENUE_WITH_CURRENCY'],
					//['name' => 'IS_MY_COMPANY'],
					...$multiFieldConfigElements,
					['name' => 'CONTACT'],
					['name' => 'ADDRESS'],
					['name' => 'REQUISITES'],
				],
			],
			[
				'name' => 'additional',
				'title' => Loc::getMessage('CRM_COMPANY_SECTION_ADDITIONAL'),
				'type' => 'section',
				'elements' => [
					['name' => 'EMPLOYEES'],
					['name' => 'OPENED'],
					['name' => 'ASSIGNED_BY_ID'],
					['name' => 'OBSERVER'],
					['name' => 'COMMENTS'],
					['name' => self::UTM_FIELD_CODE],
					...$userFieldConfigElements,
				],
			]
		];
	}
}
