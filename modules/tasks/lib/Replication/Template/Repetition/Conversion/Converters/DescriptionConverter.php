<?php

namespace Bitrix\Tasks\Replication\Template\Repetition\Conversion\Converters;

use Bitrix\Tasks\Replication\Template\Repetition\Conversion\ConverterInterface;
use Bitrix\Tasks\Replication\RepositoryInterface;
use Bitrix\Tasks\UI;

final class DescriptionConverter implements ConverterInterface
{
	public function convert(RepositoryInterface $repository): array
	{
		$taskFields = [];

		$template = $repository->getEntity();
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