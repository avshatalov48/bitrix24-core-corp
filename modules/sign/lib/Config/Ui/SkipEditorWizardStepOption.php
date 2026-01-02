<?php

namespace Bitrix\Sign\Config\Ui;

use CUserOptions;
use Bitrix\Main\Engine\CurrentUser;

class SkipEditorWizardStepOption
{
	private static function getName(): string
	{
		return 'needSkipEditorStep';
	}

	private static function get(): ?string
	{
		$userId = (int)CurrentUser::get()->getId();
		if ($userId < 1)
		{
			return null;
		}

		return CUserOptions::GetOption('sign', self::getName(), 'N', $userId);
	}

	public static function set(int $userId, bool $needSkipEditorStep): bool
	{
		return CUserOptions::SetOption(
			'sign',
			self::getName(),
			$needSkipEditorStep ? 'Y' : 'N',
			false,
			$userId,
		);
	}

	public static function needSkip(): bool
	{
		$skipEditorWizardStepOption = self::get();
		if ($skipEditorWizardStepOption === null)
		{
			return false;
		}

		return $skipEditorWizardStepOption === 'Y';
	}
}
