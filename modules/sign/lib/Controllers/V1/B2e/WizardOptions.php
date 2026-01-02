<?php

namespace Bitrix\Sign\Controllers\V1\B2e;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Sign\Config\Ui\SkipEditorWizardStepOption;
use Bitrix\Sign\Engine\Controller;

class WizardOptions extends Controller
{
	public function changeEditorStepVisibilityAction(bool $isSkipEditStep): array
	{
		$userId = (int)CurrentUser::get()->getId();
		if ($userId < 1)
		{
			$this->addError(new Error('Invalid user id'));
			return [];
		}

		$result = SkipEditorWizardStepOption::set($userId, $isSkipEditStep);
		if (!$result)
		{
			$this->addError(new Error('Failed to set user option'));
			return [];
		}

		return [
			'isSkipEditStep' => $isSkipEditStep,
		];
	}
}