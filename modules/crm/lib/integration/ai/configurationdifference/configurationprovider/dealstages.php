<?php

namespace Bitrix\Crm\Integration\AI\ConfigurationDifference\ConfigurationProvider;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Category\Entity\DealDefaultCategory;
use Bitrix\Crm\Integration\AI\ConfigurationDifference\Contract\ConfigurationProvider;
use Bitrix\Crm\Integration\AI\ConfigurationDifference\DifferenceItem;
use Bitrix\Crm\Integration\AI\ConfigurationDifference\DifferenceItemCollection;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use CCrmStatus;

class DealStages implements ConfigurationProvider
{
	private readonly Factory $factory;
	private readonly UserPermissions\EntityPermissions\Category $permissions;

	public function __construct(
		private readonly int $userId,
	)
	{
		$this->factory = Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
		$this->permissions = Container::getInstance()->getUserPermissions($this->userId)->category();
	}

	public function name(): string
	{
		return 'deal_stages';
	}

	public function default(): DifferenceItemCollection
	{
		$collection = new DifferenceItemCollection();

		if (!$this->canReadStages())
		{
			return $collection;
		}

		$stages = CCrmStatus::GetDefaultDealStages();
		foreach ($stages as $stage)
		{
			$id = $stage['STATUS_ID'];
			$values = [
				'NAME' => $stage['NAME'],
				'SORT' => $stage['SORT'],
				'COLOR' => $stage['COLOR'],
			];

			$collection->push(new DifferenceItem($id, $values));
		}

		return $collection;
	}

	public function actual(): DifferenceItemCollection
	{
		$collection = new DifferenceItemCollection([]);

		if (!$this->canReadStages())
		{
			return $collection;
		}

		$stageCollection = $this->factory->getStages($this->category()->getId());
		foreach ($stageCollection->getAll() as $stage)
		{
			$id = $stage->getStatusId();
			$values = [
				'NAME' => $stage->getName(),
				'SORT' => $stage->getSort(),
				'COLOR' => $stage->getColor(),
			];

			$collection->push(new DifferenceItem($id, $values));
		}

		return $collection;
	}

	public function fields(): array
	{
		return [
			'NAME',
			'SORT',
			'COLOR',
		];
	}

	private function canReadStages(): bool
	{
		return $this->permissions->canReadItems($this->category());
	}

	/**
	 * @return DealDefaultCategory
	 */
	private function category(): Category
	{
		return $this->factory->getDefaultCategory();
	}
}
