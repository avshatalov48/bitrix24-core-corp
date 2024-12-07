<?php

namespace Bitrix\Crm\AutomatedSolution\Action;

use Bitrix\Crm\AutomatedSolution\Entity\AutomatedSolutionTable;
use Bitrix\Crm\Integration\Intranet\CustomSection;
use Bitrix\Crm\Model\Dynamic\Type;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Main\Result;

final class LegacySet implements Action
{
	/**
	 * @param Type $type
	 * @param array{
	 *     'CUSTOM_SECTION_ID': string | int | null,
	 *     'CUSTOM_SECTIONS': Array<array{
	 *         'ID': string | int,
	 *         'TITLE': string,
	 *     }>|null
	 * } $fields
	 * @param Array<int, array> $exisingAutomatedSolutionsMap
	 * @param CustomSection[] $existingCustomSections
	 * @param bool $checkLimits
	 */
	public function __construct(
		private readonly Type $type,
		private readonly array $fields,
		private readonly array $exisingAutomatedSolutionsMap,
		private readonly array $existingCustomSections,
		private readonly bool $checkLimits = true,
	)
	{
	}

	public function execute(): Result
	{
		if (
			!array_key_exists('CUSTOM_SECTIONS', $this->fields)
			&& !array_key_exists('CUSTOM_SECTION_ID', $this->fields)
		)
		{
			return new Result();
		}

		$overallResult = new Result();
		$newCustomSectionIdsMap = [];
		if (isset($this->fields['CUSTOM_SECTIONS']) && is_array($this->fields['CUSTOM_SECTIONS']))
		{
			$setResult = $this->setCustomSections($this->fields['CUSTOM_SECTIONS']);
			if (!$setResult->isSuccess())
			{
				$overallResult->addErrors($setResult->getErrors());
			}

			$newCustomSectionIdsMap = $setResult->getData()['newIdsMap'];
			if ($setResult->getData()['isCustomSectionChanged'] ?? false)
			{
				$overallResult->setData(['isCustomSectionChanged' => true]);
			}
		}

		if (array_key_exists('CUSTOM_SECTION_ID', $this->fields))
		{
			$bindingResult = $this->bindOrUnbindType($this->fields['CUSTOM_SECTION_ID'], $newCustomSectionIdsMap);
			if ($bindingResult->isSuccess() && ($bindingResult->getData()['isCustomSectionChanged'] ?? false))
			{
				$overallResult->setData(['isCustomSectionChanged' => true]);
			}
			elseif (!$bindingResult->isSuccess())
			{
				$overallResult->addErrors($bindingResult->getErrors());
			}
		}

		return $overallResult;
	}

	private function setCustomSections(array $providedCustomSections): Result
	{
		[$toAdd, $toUpdate, $toDelete] = $this->separateByOperation($providedCustomSections);

		$overallResult = new Result();
		$isCustomSectionChanged = false;

		$newIdsMap = [];
		foreach ($toAdd as $customSection)
		{
			$addLimitCheckResult = $this->checkAddLimit();
			if ($addLimitCheckResult->isSuccess())
			{
				$addResult = (new Add($customSection))->execute();
				if ($addResult->isSuccess())
				{
					$newIdsMap[$customSection['ID']] = $addResult->getData()['fields']['INTRANET_CUSTOM_SECTION_ID'];
				}
				else
				{
					$overallResult->addErrors($addResult->getErrors());
				}
			}
			else
			{
				$overallResult->addErrors($addLimitCheckResult->getErrors());
			}
		}

		$overallResult->setData(['newIdsMap' => $newIdsMap]);

		foreach ($toUpdate as $customSection)
		{
			$automatedSolution = $this->getAutomatedSolutionByCustomSectionId($customSection['ID']);
			if ($automatedSolution)
			{
				$updateResult = (new Update($automatedSolution['ID'], $automatedSolution, $customSection))->execute();
				if (!$updateResult->isSuccess())
				{
					$overallResult->addErrors($updateResult->getErrors());
				}
				if ($updateResult->getData()['isCustomSectionChanged'] ?? false)
				{
					$isCustomSectionChanged = true;
				}
			}
		}

		foreach ($toDelete as $customSection)
		{
			$automatedSolution = $this->getAutomatedSolutionByCustomSectionId($customSection['ID']);
			if ($automatedSolution)
			{
				$deleteResult = (new Delete($automatedSolution['ID']))->execute();
				if ($deleteResult->isSuccess() && $this->type->getCustomSectionId() === $automatedSolution['ID'])
				{
					$isCustomSectionChanged = true;
				}
				elseif (!$deleteResult->isSuccess())
				{
					$overallResult->addErrors($deleteResult->getErrors());
				}
			}
		}

		return $overallResult->setData(
			$overallResult->getData() + ['isCustomSectionChanged' => $isCustomSectionChanged]
		);
	}

	private function separateByOperation(array $providedCustomSections): array
	{
		$toAdd = [];
		$toUpdate = [];

		$providedIds = [];
		foreach ($providedCustomSections as $providedCustomSection)
		{
			if (
				!isset($providedCustomSection['ID'])
				|| (is_string($providedCustomSection['ID']) && str_starts_with($providedCustomSection['ID'], 'new'))
			)
			{
				$toAdd[] = $providedCustomSection;
			}
			elseif ((int)$providedCustomSection['ID'] > 0)
			{
				$toUpdate[] = $providedCustomSection;
				$providedIds[] = (int)$providedCustomSection['ID'];
			}
		}

		$toDelete = array_filter(
			$this->existingCustomSections,
			fn(CustomSection $section) => !in_array($section->getId(), $providedIds, true),
		);

		return [
			$toAdd,
			$toUpdate,
			array_map(fn(CustomSection $section) => $section->toArray(), $toDelete),
		];
	}

	private function checkAddLimit(): Result
	{
		if (!$this->checkLimits)
		{
			return new Result();
		}

		return RestrictionManager::getAutomatedSolutionLimitRestriction()->check();
	}

	private function getAutomatedSolutionByCustomSectionId(int $customSectionId): ?array
	{
		return $this->exisingAutomatedSolutionsMap[$customSectionId] ?? null;
	}

	private function bindOrUnbindType(mixed $customSectionIdToBind, array $newCustomSectionIdsMap): Result
	{
		if (is_string($customSectionIdToBind) && str_starts_with($customSectionIdToBind, 'new'))
		{
			if (!isset($newCustomSectionIdsMap[$customSectionIdToBind]))
			{
				//invalid new custom section id

				return new Result();
			}

			$customSectionIdToBind = $newCustomSectionIdsMap[$customSectionIdToBind];
		}

		if ((int)$customSectionIdToBind > 0)
		{
			return $this->bindTypeToCustomSection((int)$customSectionIdToBind);
		}

		return $this->unbindTypeFromItsCurrentCustomSection();
	}

	private function bindTypeToCustomSection(int $customSectionId): Result
	{
		// we cant read it from in-memory cache since it may have been added during this action
		$automatedSolutionId = AutomatedSolutionTable::query()
			->setSelect(['ID'])
			->where('INTRANET_CUSTOM_SECTION_ID', $customSectionId)
			->fetchObject()
			?->getId()
		;
		if (!$automatedSolutionId)
		{
			return new Result();
		}

		return (new BindTypeToAutomatedSolution($this->type, $automatedSolutionId))->execute();
	}

	private function unbindTypeFromItsCurrentCustomSection(): Result
	{
		// type is not bound, nothing to do
		if ($this->type->getCustomSectionId() <= 0)
		{
			return new Result();
		}

		return (new UnbindTypeFromAutomatedSolution($this->type, $this->type->getCustomSectionId()))->execute();
	}
}
