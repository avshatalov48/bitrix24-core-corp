<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Factory;

use Bitrix\Booking\Entity\BaseEntityCollection;
use Bitrix\Main;
use Bitrix\Booking\Entity;
use Bitrix\Main\Result;

abstract class EntityFactory extends AbstractFactory
{
	public function createCollectionFromRestFields(array $items): BaseEntityCollection
	{
		$clientCollection = $this->createCollection();
		foreach ($items as $item)
		{
			$clientCollection->add($this->createFromRestFields($item));
		}

		return $clientCollection;
	}

	protected function createCollection(): BaseEntityCollection
	{
		throw new Main\NotImplementedException('Method createCollection() must be implemented');
	}

	abstract public function createFromRestFields(array $fields): Entity\EntityInterface;

	public function validateRestFieldsList(array $fields): Result
	{
		foreach ($fields as $item)
		{
			$validationResult = $this->validateRestFields($item);
			if (!$validationResult->isSuccess())
			{
				return $validationResult;
			}
		}

		return new Result();
	}
}
