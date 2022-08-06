<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Model\Dynamic\TypeTable;

class TypesMap
{
	/** @var string|TypeTable */
	protected $typeDataClass = TypeTable::class;

	/** @var Factory[] */
	protected $factories = [];

	/**
	 * Returns all implemented factories, both dynamic and static
	 *
	 * @return Factory[]
	 */
	public function getFactories(): array
	{
		if (!empty($this->factories))
		{
			return $this->factories;
		}

		foreach (\CCrmOwnerType::GetAll() as $entityTypeId)
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if ($factory)
			{
				$this->factories[] = $factory;
			}
		}

		$types = Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		])->getTypes();
		foreach ($types as $dynamicType)
		{
			$this->factories[] = Container::getInstance()->getDynamicFactoryByType($dynamicType);
		}

		return $this->factories;
	}

	/**
	 * Returns a Factory by $entityTypeId, if it exists
	 *
	 * @param int $entityTypeId
	 *
	 * @return Factory|null
	 */
	public function getFactory(int $entityTypeId): ?Factory
	{
		foreach ($this->getFactories() as $factory)
		{
			if ($factory->getEntityTypeId() === $entityTypeId)
			{
				return $factory;
			}
		}

		return null;
	}
}
