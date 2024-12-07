<?php

namespace Bitrix\Crm\Activity\Provider\ToDo\Block;

use Bitrix\Crm\Activity\Provider\ToDo\OptionallyConfigurable;
use Bitrix\Main\Localization\Loc;

final class Link extends Base
{
	public const TYPE_NAME = 'link';

	public function prepareEntity(OptionallyConfigurable $entity, bool $skipActiveSectionCheck = false): void
	{
		$link = trim($this->blockData['link'] ?? '');
		if (empty($link))
		{
			return;
		}

		$settings = $fields['SETTINGS'] ?? [];
		$settings['LINK'] = $link;

		$fields['SETTINGS'] = $settings;

		$fields['CALENDAR_ADDITIONAL_DESCRIPTION_DATA'] = $fields['CALENDAR_ADDITIONAL_DESCRIPTION_DATA'] ?? [];
		$fields['CALENDAR_ADDITIONAL_DESCRIPTION_DATA']['LINK'] = [
			'TITLE' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_TODO_LINK'),
			'ITEMS' => [
				'[URL=' . $link . ']' .  $link . '[/URL]',
			],
		];

		$entity->appendAdditionalFields($fields);
	}

	public function fetchSettings(): array
	{
		$link = $this->activityData['settings']['LINK'] ?? null;
		if (!$link)
		{
			return [];
		}

		return [
			'link' => $link,
		];
	}
}
