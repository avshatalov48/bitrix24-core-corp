<?php

namespace Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Section;

use Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto\Section\AbstractParameters;
use Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto\Section\CreateParameters;
use Bitrix\Crm\Integration\UI\EntityEditor\Configuration;
use Bitrix\Crm\Integration\UI\EntityEditor\Configuration\Section;
use Bitrix\Crm\Integration\UI\EntityEditor\Enum\MarkTarget;
use Bitrix\Crm\Integration\UI\EntityEditor\MartaAIMarksRepository;
use Bitrix\Crm\Result;
use Bitrix\Main\Localization\Loc;

final class Create extends AbstractFunction
{
	/**
	 * @param CreateParameters $parameters
	 * @param Configuration $configuration
	 * @return Result
	 */
	protected function doInvoke(AbstractParameters $parameters, Configuration $configuration): Result
	{
		$sectionData = $parameters->section->toArray();
		/** @var Section $newSection */
		$newSection = Section::fromArray($sectionData);

		if ($configuration->getSection($newSection->getName()) !== null)
		{
			return Result::fail(Loc::getMessage('CRM_INTEGRATION_AI_FUNCTION_ENTITY_DETAILS_CARD_VIEW_CREATE_SECTION_NAME_NOT_UNIQUE', 'SECTION_NAME_NOT_UNIQUE'));
		}

		$fieldNames = array_keys($newSection->getElements());
		$configuration
			->removeElements($fieldNames)
			->addSection($newSection)
			->save();

		MartaAIMarksRepository::fromEntityEditorConfig($configuration->entityEditorConfig())
			->mark(MarkTarget::Field, $newSection->getElementNames())
			->mark(MarkTarget::Section, [$newSection->getName()]);

		return Result::success();
	}

	protected function parseParameters(array $args): CreateParameters
	{
		return new CreateParameters($args);
	}
}
