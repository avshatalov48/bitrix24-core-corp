<?php

namespace Bitrix\Crm\Status;

use Bitrix\Crm\EO_Status;
use Bitrix\Crm\EO_Status_Collection;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\Type\Collection;

final class FunnelStatusCollectionRevalidator
{
	private array $statuses;

	private const REVALIDATE_SORT_STEP = 10;

	public function __construct(
		EO_Status_Collection $collection,
	)
	{
		$this->statuses = $collection->getAll();
	}

	public function revalidate(): self
	{
		$this->revalidateSemantics();
		$this->cleanDuplicateSuccessSemantics();
		$this->sortBySemantics();
		$this->correctSemanticsSort();

		return $this;
	}

	public function save(bool $ignoreEvents = true): Result
	{
		return $this
			->revalidate()
			->getCollection()
			->save($ignoreEvents)
		;
	}

	public function getCollection(): EO_Status_Collection
	{
		$collection = StatusTable::createCollection();
		array_map(static fn (EO_Status $status) => $collection->add($status), $this->statuses);

		return $collection;
	}

	private function cleanDuplicateSuccessSemantics(): void
	{
		foreach ($this->statuses as $status)
		{
			$semantics = $status->getSemantics();
			if (
				$semantics === PhaseSemantics::SUCCESS
				&& !$status->getSystem()
			)
			{
				$status->setSemantics(null);
			}
		}
	}

	private function sortBySemantics(): void
	{
		$semantics = [PhaseSemantics::PROCESS, PhaseSemantics::SUCCESS, PhaseSemantics::FAILURE];
		$result = [];

		foreach ($semantics as $semantic)
		{
			$statusesBySemantic = $this->getStatusesBySemantics($semantic);
			Collection::sortByColumn($statusesBySemantic, [
				'SYSTEM' => SORT_DESC,
				'SORT' => SORT_ASC,
			]);

			array_push($result, ...$statusesBySemantic);
		}

		$this->statuses = $result;
	}

	private function getStatusesBySemantics(string $semantics): array
	{
		return array_filter(
			$this->statuses,
			static fn (EO_Status $status) => ($status->getSemantics() ?? PhaseSemantics::PROCESS) === $semantics,
		);
	}

	private function revalidateSemantics(): void
	{
		foreach ($this->statuses as $status)
		{
			$semantics = $status->getSemantics() ?? PhaseSemantics::PROCESS;
			if (!PhaseSemantics::isDefined($semantics))
			{
				$status->setSemantics(null);
			}
		}
	}

	private function correctSemanticsSort(): void
	{
		$count = count($this->statuses);

		for ($i = 1; $i < $count; $i++)
		{
			$leftStatus = $this->statuses[$i - 1];
			$currentStatus = $this->statuses[$i];

			if ($leftStatus->getSort() >= $currentStatus->getSort())
			{
				$newSort = $leftStatus->getSort() + self::REVALIDATE_SORT_STEP;
				$currentStatus->setSort($newSort);
			}
		}
	}
}
