<?php

namespace Bitrix\Crm\Service;

use Bitrix\Main\Localization\Loc;

class Localization
{
	public function loadMessages(): array
	{
		return Loc::loadLanguageFile(__FILE__);
	}

	public function loadKanbanMessages(): array
	{
		return Loc::loadLanguageFile(__DIR__ . DIRECTORY_SEPARATOR .  '..' . DIRECTORY_SEPARATOR .  '..' . DIRECTORY_SEPARATOR . 'kanban.php');
	}

	public function appendOldVersionSuffix(?string $title): string
	{
		$message = Loc::getMessage(
			'CRM_OLD_VERSION_SUFFIX',
			[
				'#TITLE#' => $title
			]
		);

		if (!$message)
		{
			$message = $title . ' (old version)';
		}

		return (string)$message;
	}

	public function prepareFieldValueWithTitle(string $fieldTitle, string $value): ?string
	{
		return Loc::getMessage('CRM_LOCALIZATION_FIELD_VALUE_WITH_TITLE', [
			'#TITLE#' => $fieldTitle,
			'#VALUE#' => $value,
		]);
	}
}
