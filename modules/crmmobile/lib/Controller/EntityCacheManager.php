<?php
declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;

final class EntityCacheManager
{
	private static ?EntityCacheManager $instance = null;
	private array $cachedItems = [];

	private function __construct() {}

	public static function getInstance(): EntityCacheManager
	{
		if (self::$instance === null)
		{
			self::$instance = new EntityCacheManager();
		}
		return self::$instance;
	}

	public function getItem(Factory $factory, ?int $entityId = null): ?Item
	{
		if ($entityId === null)
		{
			return null;
		}

		$entityTypeId = $factory->getEntityTypeId();

		if (isset($this->cachedItems[$entityTypeId][$entityId]))
		{
			return $this->cachedItems[$entityTypeId][$entityId];
		}

		$item = $factory->getItem($entityId);
		if ($item)
		{
			$this->cachedItems[$entityTypeId] = $this->cachedItems[$entityTypeId] ?? [];
			$this->cachedItems[$entityTypeId][$entityId] = $item;
		}

		return $item ?? null;
	}
}