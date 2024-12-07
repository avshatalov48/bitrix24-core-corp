<?php

namespace Bitrix\Crm\Activity\Provider\ToDo\Block;

use Bitrix\Crm\Activity\Provider\ToDo\OptionallyConfigurable;
use Bitrix\Main\Loader;

final class Address extends Base
{
	public const TYPE_NAME = 'address';

	public function prepareEntity(OptionallyConfigurable $entity, bool $skipActiveSectionCheck = false): void
	{
		$addressFormatted = trim($this->blockData['addressFormatted'] ?? '');
		if (empty($addressFormatted))
		{
			return;
		}

		$settings = $fields['SETTINGS'] ?? [];
		$settings['ADDRESS_FORMATTED'] = $addressFormatted;
		$fields['SETTINGS'] = $settings;

		$entity->appendAdditionalFields($fields);
	}

	public function fetchSettings(): array
	{
		$addressFormatted = $this->activityData['settings']['ADDRESS_FORMATTED'] ?? null;
		if (!$addressFormatted || !Loader::includeModule('fileman'))
		{
			return [];
		}

		return [
			'addressFormatted' => $addressFormatted,
		];
	}
}
