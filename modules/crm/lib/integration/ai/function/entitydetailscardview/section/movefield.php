<?php

namespace Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Section;

use Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto\Section\AbstractParameters;
use Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto\Section\MoveFieldParameters;
use Bitrix\Crm\Integration\UI\EntityEditor\Configuration;
use Bitrix\Crm\Integration\UI\EntityEditor\Enum\MarkTarget;
use Bitrix\Crm\Integration\UI\EntityEditor\MartaAIMarksRepository;
use Bitrix\Crm\Result;
use Bitrix\Main\Localization\Loc;

final class MoveField extends AbstractFunction
{
	/**
	 * @param MoveFieldParameters $parameters
	 * @param Configuration $configuration
	 * @return Result
	 */
	protected function doInvoke(AbstractParameters $parameters, Configuration $configuration): Result
	{
		$targetSection = $configuration->getSection($parameters->sectionName);
		if ($targetSection === null)
		{
			return Result::fail(Loc::getMessage('CRM_INTEGRATION_AI_FUNCTION_ENTITY_DETAILS_CARD_VIEW_TARGET_SECTION_NOT_FOUND_ERROR'), 'SECTION_NOT_FOUND');
		}

		$targetElement = null;
		foreach ($configuration->getSections() as $section)
		{
			$targetElement = $section->getElement($parameters->fieldName);
			if ($targetElement !== null)
			{
				$section->removeElement($targetElement->getName());

				break;
			}
		}

		$targetElement ??= new Configuration\Element(name: $parameters->fieldName, isShowAlways: true);
		$targetSection->addElement($targetElement);

		$configuration->save();

		MartaAIMarksRepository::fromEntityEditorConfig($configuration->entityEditorConfig())
			->mark(MarkTarget::Field, [$targetElement->getName()]);

		return Result::success();
	}

	protected function parseParameters(array $args): MoveFieldParameters
	{
		return new MoveFieldParameters($args);
	}
}
