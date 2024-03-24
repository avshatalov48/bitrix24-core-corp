<?php

namespace Bitrix\Tasks\Replication\Template\Repetition\Conversion\Converters;

use Bitrix\Main\ArgumentException;
use Bitrix\Tasks\Replication\Template\Repetition\Conversion\ConverterInterface;
use Bitrix\Tasks\Replication\RepositoryInterface;
use Bitrix\Tasks\Util\UserField\Task;
use Bitrix\Tasks\Util\UserField\Task\Template;

final class UfConverter implements ConverterInterface
{
	public function convert(RepositoryInterface $repository): array
	{
		$template = $repository->getEntity();
		// change inline attachments
		$taskFields = [
			'DESCRIPTION' => $template->getDescription(),
		];

		$ufTemplateController = new Template();
		$ufTaskController = new Task();
		$ufScheme = $ufTaskController::getScheme();
		foreach ($ufScheme as $fieldName => $fieldData)
		{
			// plus all user fields
			if ($ufTemplateController::isFieldExist($fieldName))
			{
				$taskFields[$fieldName] = $template->get($fieldName);
			}
		}

		try
		{
			$result = $ufTemplateController->cloneValues($taskFields, $ufTaskController, $template->getCreatedBy());
		}
		catch (ArgumentException)
		{
			return [];
		}

		if (!$result->isSuccess())
		{
			return [];
		}

		return $result->getData();
	}

	public function getTemplateFieldName(): string
	{
		return 'UF_*';
	}
}