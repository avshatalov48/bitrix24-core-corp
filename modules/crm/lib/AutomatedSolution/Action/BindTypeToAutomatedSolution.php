<?php

namespace Bitrix\Crm\AutomatedSolution\Action;

use Bitrix\Crm\AutomatedSolution\AutomatedSolutionManager;
use Bitrix\Crm\AutomatedSolution\Entity\AutomatedSolutionTable;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Model\Dynamic\Type;
use Bitrix\Intranet\CustomSection\Entity\CustomSectionPageTable;
use Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Result;

final class BindTypeToAutomatedSolution implements Action
{
	private const MODULE_ID = AutomatedSolutionManager::MODULE_ID;

	public function __construct(
		private readonly Type $type,
		private readonly int $automatedSolutionId,
	)
	{
	}

	public function execute(): Result
	{
		if ($this->type->getCustomSectionId() === $this->automatedSolutionId)
		{
			// nothing to change
			return new Result();
		}

		if (!IntranetManager::isCustomSectionsAvailable())
		{
			return new Result();
		}

		$overallResult = new Result();

		$intranetUpdateResult = $this->updateIntranetPage();
		if (!$intranetUpdateResult->isSuccess())
		{
			return $overallResult->addErrors($intranetUpdateResult->getErrors());
		}

		$typeUpdateResult = $this->type
			->setCustomSectionId($this->automatedSolutionId)
			->save()
		;

		if ($typeUpdateResult->isSuccess())
		{
			$overallResult->setData(['isCustomSectionChanged' => true]);
		}
		else
		{
			$overallResult->addErrors($typeUpdateResult->getErrors());
		}

		return $overallResult;
	}

	private function updateIntranetPage(): Result
	{
		$intranetCustomSectionId = $this->getIntranetCustomSectionId();
		if ($intranetCustomSectionId <= 0)
		{
			return (new Result())->addError(
				new Error(
					'Intranet custom section id for this automated solution is not found. Possible DB data inconsistency',
					0,
					[
						'automatedSolutionId' => $this->automatedSolutionId,
					]
				)
			);
		}

		$page = $this->getExistingIntranetCustomSectionPage() ?? new EO_CustomSectionPage();

		// actualize all data
		$page
			->setTitle($this->type->getTitle())
			->setCode('') // empty string to provoke CODE regeneration
			->setModuleId(self::MODULE_ID)
			->setCustomSectionId($intranetCustomSectionId)
			->setSettings($this->getPageSettingsValue())
			->setSort($page->getSort() ?: $this->calculateSortForNewPage($intranetCustomSectionId))
		;

		return $page->save();
	}

	private function getIntranetCustomSectionId(): ?int
	{
		return AutomatedSolutionTable::query()
			->setSelect(['INTRANET_CUSTOM_SECTION_ID'])
			->where('ID', $this->automatedSolutionId)
			->fetchObject()
			?->getIntranetCustomSectionId()
		;
	}

	private function getExistingIntranetCustomSectionPage(): ?EO_CustomSectionPage
	{
		return CustomSectionPageTable::query()
			->setSelect(['*'])
			->where('MODULE_ID', self::MODULE_ID)
			->where('SETTINGS', $this->getPageSettingsValue())
			->fetchObject()
		;
	}

	private function getPageSettingsValue(): string
	{
		return IntranetManager::preparePageSettingsForItemsList($this->type->getEntityTypeId());
	}

	private function calculateSortForNewPage(int $intranetCustomSectionId): int
	{
		$row = CustomSectionPageTable::query()
			->setSelect([new ExpressionField('MAX_SORT', 'MAX(%s)', 'SORT')])
			->where('MODULE_ID', self::MODULE_ID)
			->where('CUSTOM_SECTION_ID', $intranetCustomSectionId)
			->fetch()
		;

		if (isset($row['MAX_SORT']))
		{
			// a newly added page should be displayed last
			return $row['MAX_SORT'] + 100;
		}

		// it seems it's the first page in section
		return 100;
	}
}
