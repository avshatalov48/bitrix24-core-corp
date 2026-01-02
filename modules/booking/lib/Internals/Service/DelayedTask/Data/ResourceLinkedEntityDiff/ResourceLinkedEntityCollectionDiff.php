<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\DelayedTask\Data\ResourceLinkedEntityDiff;

use Bitrix\Booking\Entity\Resource\ResourceLinkedEntityCollection;
use Bitrix\Booking\Internals\Model\Enum\ResourceLinkedEntityType;
use Bitrix\Main\Web\Json;

class ResourceLinkedEntityCollectionDiff
{
	public function __construct(
		public readonly array $collection = [],
	)
	{
	}

	public static function fromCollections(
		ResourceLinkedEntityCollection $oldCollection,
		ResourceLinkedEntityCollection $newCollection
	): self
	{
		return (new self())->calculate($oldCollection, $newCollection);
	}

	public static function mapFromArray(array $params): self
	{
		$diffResult = [];
		foreach ($params['collection'] as $typeName => $diffData)
		{
			$type = ResourceLinkedEntityType::from($typeName);
			$differ = match ($type)
			{
				ResourceLinkedEntityType::Calendar => static fn($data) => (bool)$data,
				default => null,
			};

			if ($differ === null)
			{
				continue;
			}

			$diffResult[$type->value] = $differ($diffData);
		}

		return new self($diffResult);
	}

	public function getByType(ResourceLinkedEntityType $type): mixed
	{
		return $this->collection[$type->value] ?? null;
	}

	private function calculate(
		ResourceLinkedEntityCollection $oldCollection,
		ResourceLinkedEntityCollection $newCollection
	): self
	{
		$diffResult = [];
		foreach (ResourceLinkedEntityType::cases() as $type)
		{
			$diff = $this->calculateDiffForType($type, $oldCollection, $newCollection);
			if ($diff !== null)
			{
				$diffResult[$type->value] = $diff;
			}
		}

		return new self($diffResult);
	}

	private function calculateDiffForType(
		ResourceLinkedEntityType $type,
		ResourceLinkedEntityCollection $oldCollection,
		ResourceLinkedEntityCollection $newCollection
	): mixed
	{
		return match ($type)
		{
			ResourceLinkedEntityType::Calendar => $this->isCalendarIntegrationChanged($oldCollection, $newCollection),
			default => null,
		};
	}

	private function isCalendarIntegrationChanged(
		ResourceLinkedEntityCollection $oldCollection,
		ResourceLinkedEntityCollection $newCollection
	): bool
	{
		$oldTypedCollection = $oldCollection
			->getByTypeAndId(ResourceLinkedEntityType::Calendar)
			->getFirstCollectionItem()
		;
		$newTypedCollection = $newCollection
			->getByTypeAndId(ResourceLinkedEntityType::Calendar)
			->getFirstCollectionItem()
		;

		return Json::encode($oldTypedCollection?->toArray()) !== Json::encode($newTypedCollection?->toArray());
	}
}
