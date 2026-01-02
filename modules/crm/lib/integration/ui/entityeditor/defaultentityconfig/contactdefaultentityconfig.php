<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor\DefaultEntityConfig;

use Bitrix\Crm\Integration\UI\EntityEditor\AbstractDefaultEntityConfig;
use Bitrix\Main\Localization\Loc;

final class ContactDefaultEntityConfig extends AbstractDefaultEntityConfig
{
	public function __construct(
		private readonly array $userFieldNames = [],
		private readonly array $multiFieldNames = [],
	)
	{
	}

	public function get(): array
	{
		$userFieldConfigElements = $this->formatFieldNames($this->userFieldNames);
		$multiFieldConfigElements = $this->formatFieldNames($this->multiFieldNames);

		return [
			[
				'name' => 'main',
				'title' => Loc::getMessage('CRM_CONTACT_SECTION_MAIN'),
				'type' => 'section',
				'elements' => [
					['name' => 'HONORIFIC'],
					['name' => 'LAST_NAME'],
					['name' => 'NAME'],
					['name' => 'SECOND_NAME'],
					['name' => 'PHOTO'],
					['name' => 'BIRTHDATE'],
					['name' => 'POST'],
					...$multiFieldConfigElements,
					['name' => 'COMPANY'],
					['name' => 'ADDRESS'],
					['name' => 'REQUISITES'],
				],
			],
			[
				'name' => 'additional',
				'title' => Loc::getMessage('CRM_CONTACT_SECTION_ADDITIONAL'),
				'type' => 'section',
				'elements' => [
					['name' => 'TYPE_ID'],
					['name' => 'SOURCE_ID'],
					['name' => 'SOURCE_DESCRIPTION'],
					['name' => 'OPENED'],
					['name' => 'EXPORT'],
					['name' => 'ASSIGNED_BY_ID'],
					['name' => 'OBSERVER'],
					['name' => 'COMMENTS'],
					['name' => self::UTM_FIELD_CODE],
					...$userFieldConfigElements,
				],
			],
		];
	}
}
