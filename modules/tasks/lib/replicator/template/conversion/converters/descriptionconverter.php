<?php

namespace Bitrix\Tasks\Replicator\Template\Conversion\Converters;

use Bitrix\Tasks\Replicator\Template\Conversion\Converter;
use Bitrix\Tasks\Replicator\Template\Repository;
use Bitrix\Tasks\UI;

final class DescriptionConverter implements Converter
{
	public function convert(Repository $repository): array
	{
		$taskFields = [];

		$template = $repository->getTemplate();
		if(!$template->getDescriptionInBbcode() && !empty($template->getDescription()))
		{
			$taskFields['DESCRIPTION'] = UI::convertHtmlToBBCode($template->getDescription()); // do not spawn tasks with description in html format
		}
		else
		{
			$taskFields['DESCRIPTION'] = $template->getDescription();
		}

		return $taskFields;
	}

	public function getTemplateFieldName(): string
	{
		return 'DESCRIPTION';
	}
}