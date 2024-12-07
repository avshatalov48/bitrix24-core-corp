<?php

namespace Bitrix\Crm\AutomatedSolution\Action;

use Bitrix\Crm\AutomatedSolution\AutomatedSolutionManager;
use Bitrix\Crm\AutomatedSolution\Entity\AutomatedSolutionTable;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Intranet\CustomSection\Entity\CustomSectionTable;
use Bitrix\Main\Result;

final class Add implements Action
{
	public function __construct(
		private readonly array $fields
	)
	{
	}

	public function execute(): Result
	{
		if (!IntranetManager::isCustomSectionsAvailable())
		{
			return new Result();
		}

		$overallResult = new Result();

		$intranetSection = CustomSectionTable::createObject([
			'TITLE' => $this->fields['TITLE'] ?? null,
			'MODULE_ID' => AutomatedSolutionManager::MODULE_ID,
		]);
		$intranetResult = $intranetSection->save();
		if (!$intranetResult->isSuccess())
		{
			return $overallResult->addErrors($intranetResult->getErrors());
		}

		$automatedSolution = AutomatedSolutionTable::createObject([
			'TITLE' => $intranetSection->getTitle(),
			'CODE' => $intranetSection->getCode(),
			'INTRANET_CUSTOM_SECTION_ID' => $intranetSection->getId(),
		]);
		$crmResult = $automatedSolution->save();
		if (!$crmResult->isSuccess())
		{
			return $overallResult->addErrors($crmResult->getErrors());
		}

		return $overallResult->setData(['fields' => $automatedSolution->collectValues()]);
	}
}
