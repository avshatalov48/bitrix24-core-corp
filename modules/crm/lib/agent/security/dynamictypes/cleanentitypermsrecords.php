<?php

namespace Bitrix\Crm\Agent\Security\DynamicTypes;

use Bitrix\Crm\EntityPermsTable;
use Bitrix\Crm\Model\ItemCategoryTable;
use Bitrix\Crm\Security\Controller\DynamicItem;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Config\Option;
use CCrmOwnerType;

/**
 * Clear entries from the entity permissions table associated with an entity type that has been converted to use atrr tables.
 */
class CleanEntityPermsRecords
{
	public const DONE = false;

	public const CONTINUE = true;

	private const DEFAULT_RM_LIMIT = 100;

	public const CLEAN_LIMIT_OPTION_NAME = 'cleanentitypermsrecords_clean_limit';

	public static function scheduleAgent(int $entityTypeId): void
	{
		$agentName = get_called_class()."::run($entityTypeId);";

		\CAgent::AddAgent(
			$agentName,
			'crm',
			'N',
			60,
		);
	}

	public static function run(int $entityTypeId): string
	{
		return static::doRun($entityTypeId) ? get_called_class()."::run($entityTypeId);" : '';
	}

	public static function doRun(int $entityTypeId): bool
	{
		$instance = new self();
		$result = $instance->execute($entityTypeId);

		if ($result === self::DONE)
		{
			$instance->removeOptions($entityTypeId);
		}

		return $result;
	}

	public function execute(int $entityTypeId): bool
	{
		if (!$this->validateEntityTypeId($entityTypeId))
		{
			return self::DONE;
		}

		$ids = $this->getIdsToClean($entityTypeId, $this->getLimit());

		if (empty($ids))
		{
			return self::DONE;
		}

		EntityPermsTable::deleteByIds($ids);

		return self::CONTINUE;
	}

	private function getIdsToClean(int $entityTypeId, int $limit): array
	{
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);

		$entityNamesWithCats = [];
		if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			$entityNamesWithCats = $this->getEntityNamesWithCategories($entityTypeId);
		}

		$query = EntityPermsTable::query()
			->setSelect(['ID'])
			->setLimit($limit);

		if (empty($entityNamesWithCats))
		{
			$query->whereLike('ENTITY', $entityTypeName . '%');
		}
		else
		{
			$query->whereIn('ENTITY', $entityNamesWithCats);
		}

		$rows = $query->fetchAll();

		return array_column($rows, 'ID');
	}

	private function getLimit(): int
	{
		return (int)Option::get('crm', self::CLEAN_LIMIT_OPTION_NAME, self::DEFAULT_RM_LIMIT);
	}

	private function validateEntityTypeId(int $entityTypeId): bool
	{
		return DynamicItem::isSupportedType($entityTypeId);
	}

	private function getEntityNamesWithCategories(int $entityTypeId): array
	{
		$names = [];
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);

		$catIds = $this->getCatsIds($entityTypeId);

		if (empty($catIds))
		{
			return [];
		}

		foreach ($catIds as $id)
		{
			$names[] = $entityTypeName . '_C' . $id;
		}

		return $names;
	}

	private function getCatsIds(int $entityTypeId): array
	{
		$catIds = $this->getCategoriesFromOption($entityTypeId);

		if (empty($catIds))
		{
			$catIds = $this->queryCategoriesIds($entityTypeId);
			$this->saveCategoriesToOptions($entityTypeId, $catIds);
		}

		return $catIds;
	}

	private function queryCategoriesIds(int $entityTypeId): array
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);

		return array_map(fn ($cat) => $cat->getId(), $factory->getCategories());
	}

	private function getCategoriesFromOption(int $entityTypeId): array
	{
		$catsStr = Option::get('crm', $this->categoriesOptionName($entityTypeId), '');

		$cats = explode(',', $catsStr);

		if (empty($cats))
		{
			return [];
		}

		return array_filter(array_map(fn($cat) => (int)$cat, $cats), fn($cat) => $cat > 0);
	}

	private function saveCategoriesToOptions(int $entityTypeId, array $cats): void
	{
		$catsStr = implode(',', $cats);
		Option::set('crm', $this->categoriesOptionName($entityTypeId), $catsStr);
	}

	private function categoriesOptionName(int $entityTypeId): string
	{
		return "cleanentitypermsrecords_categories_$entityTypeId";
	}

	public function removeOptions(int $entityTypeId): void
	{
		Option::delete('crm', ['name' => $this->categoriesOptionName($entityTypeId)]);
	}
}