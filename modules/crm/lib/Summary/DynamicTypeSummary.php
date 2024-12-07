<?php

namespace Bitrix\Crm\Summary;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Data\ManagedCache;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Type\DateTime;

final class DynamicTypeSummary
{
	private ManagedCache $cacheManager;

	public function __construct(private Factory\Dynamic $factory)
	{
		$this->cacheManager = \Bitrix\Main\Application::getInstance()->getManagedCache();
	}

	public function getLastActivityTime(): DateTime
	{
		return $this->rememberLastActivityTime(function (): int {
			$lastActivityTime = $this->factory->getDataClass()::getList([
				'select' => ['MAX'],
				'runtime' => [
					new ExpressionField(
						'MAX',
						'MAX(%s)',
						$this->factory->getEntityFieldNameByMap(Item::FIELD_NAME_UPDATED_TIME),
					)
				],
			])->fetch();

			/** @var DateTime $lastActivityTime */
			$lastActivityTime = $lastActivityTime['MAX'] ?? $this->factory->getType()->getCreatedTime();

			return $lastActivityTime->getTimestamp();
		});
	}

	private function rememberLastActivityTime(callable $calculateTimestampCallback): DateTime
	{
		$ttl = 86400;
		$cacheDir = "crm_dynamic_type_{$this->factory->getEntityTypeId()}";
		$cacheTag = "{$cacheDir}_last_activity_datetime";

		if ($this->cacheManager->read($ttl, $cacheTag, $cacheDir))
		{
			$timestamp = (int)$this->cacheManager->get($cacheTag);

			return DateTime::createFromTimestamp($timestamp);
		}

		$newTimestamp = $calculateTimestampCallback();

		$this->cacheManager->set($cacheTag, $newTimestamp);

		return DateTime::createFromTimestamp($newTimestamp);
	}
}
