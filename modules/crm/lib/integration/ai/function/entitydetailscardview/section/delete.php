<?php

namespace Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Section;

use Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto\Section\AbstractParameters;
use Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto\Section\DeleteParameters;
use Bitrix\Crm\Integration\UI\EntityEditor\Configuration;
use Bitrix\Crm\Result;
use Bitrix\Main\Localization\Loc;

final class Delete extends AbstractFunction
{
	/**
	 * @param DeleteParameters $parameters
	 * @param Configuration $configuration
	 * @return Result
	 */
	protected function doInvoke(AbstractParameters $parameters, Configuration $configuration): Result
	{
		if ($configuration->getSection($parameters->name) === null)
		{
			return Result::fail(Loc::getMessage('CRM_INTEGRATION_AI_FUNCTION_ENTITY_DETAILS_CARD_VIEW_SECTION_NOT_FOUND_ERROR'), 'SECTION_NOT_FOUND');
		}

		$configuration->removeSection($parameters->name);
		if (empty($configuration->getSections()))
		{
			return Result::fail(Loc::getMessage('CRM_INTEGRATION_AI_FUNCTION_ENTITY_DETAILS_CARD_VIEW_CANNOT_DELETE_LAST_SECTION_ERROR'), 'DELETE_LAST_SECTION');
		}

		$configuration->save();

		return Result::success();
	}

	protected function parseParameters(array $args): DeleteParameters
	{
		return new DeleteParameters($args);
	}
}
