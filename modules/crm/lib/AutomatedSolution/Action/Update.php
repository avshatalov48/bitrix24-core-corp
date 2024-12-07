<?php

namespace Bitrix\Crm\AutomatedSolution\Action;

use Bitrix\Crm\AutomatedSolution\Entity\AutomatedSolutionTable;
use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Intranet\CustomSection\Entity\CustomSectionTable;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

final class Update implements Action
{
	public function __construct(
		private readonly int $id,
		private readonly array $previousFields,
		private readonly array $currentFields,
	)
	{
	}

	public function execute(): Result
	{
		$diff = ComparerBase::compareEntityFields($this->previousFields, $this->currentFields);

		if (!$diff->isChanged('TITLE'))
		{
			// nothing to change
			return new Result();
		}

		if (!IntranetManager::isCustomSectionsAvailable())
		{
			return new Result();
		}

		$overallResult = new Result();

		$automatedSolution = AutomatedSolutionTable::getById($this->id)->fetchObject();
		if (!$automatedSolution)
		{
			return $overallResult->addError(
				new Error('Automated solution is not found', 0, ['automatedSolutionId' => $this->id]),
			);
		}

		$intranetSection = CustomSectionTable::getById($automatedSolution->getIntranetCustomSectionId())->fetchObject();
		if (!$intranetSection)
		{
			return $overallResult->addError(
				new Error(
					'Intranet custom section not found. Possible DB data inconsistency',
					0,
					[
						'automatedSolutionId' => $this->id,
						'customSectionId' => $automatedSolution->getIntranetCustomSectionId(),
					]
				),
			);
		}

		$intranetSection
			->setTitle($diff->getCurrentValue('TITLE'))
			->setCode('') // to provoke CODE regeneration
		;

		$intranetResult = $intranetSection->save();
		if (!$intranetResult->isSuccess())
		{
			return $overallResult->addErrors($intranetResult->getErrors());
		}

		$automatedSolution
			->setTitle($intranetSection->getTitle())
			->setCode($intranetSection->getCode())
			->setIntranetCustomSectionId($intranetSection->getId())
			->setUpdatedTime(new DateTime())
			->setUpdatedBy(Container::getInstance()->getContext()->getUserId())
		;

		$crmResult = $automatedSolution->save();
		if (!$crmResult->isSuccess())
		{
			return $overallResult->addErrors($crmResult->getErrors());
		}

		return $overallResult->setData(['fields' => $automatedSolution->collectValues()]);
	}
}
