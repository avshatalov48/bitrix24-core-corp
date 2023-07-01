<?php

namespace Bitrix\Crm\Activity\Settings\Section;

use Bitrix\Crm\Activity\Settings\OptionallyConfigurable;
use Bitrix\Crm\Integration\UI\EntitySelector\TimelinePingProvider;
use Bitrix\Crm\Model\ActivityPingOffsetsTable;
use CCrmActivity;

final class Ping extends Base
{
	public const TYPE_NAME = 'ping';

	public function apply(): bool
	{
		return false;
	}

	public function fetchSettings(): array
	{
		$activityId = (int)($this->activityData['id'] ?? 0);
		$offsets = [];
		if ($activityId > 0)
		{
			$offsets = ActivityPingOffsetsTable::getOffsetsByActivityId($activityId);
		}

		if (empty($offsets))
		{
			$provider = CCrmActivity::GetProviderById($this->activityData['providerId']);
			if ($provider !== null)
			{
				$offsets = $provider::getDefaultPingOffsets();
			}
		}

		return [
			'id' => self::TYPE_NAME,
			'active' => true,
			'showToggleSelector' => false,
			'settings' => [
				'selectedItems' => array_column(TimelinePingProvider::getValuesByOffsets($offsets), 'id'),
			],
		];
	}

	public function prepareEntity(OptionallyConfigurable $entity, bool $skipActiveSectionCheck = false): void
	{
		if (isset($this->data['selectedItems']) && is_array($this->data['selectedItems']))
		{
			$entity->setAdditionalFields([
				'PING_OFFSETS' => TimelinePingProvider::getOffsetsByValues($this->data['selectedItems']),
			]);
		}
	}

	public function getOptions(OptionallyConfigurable $entity): array
	{
		return [];
	}
}
