<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Tasks\Util\User;

class Checklist extends Controller
{
	public function configureActions(): array
	{
		return [
			'getSettings' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getShowCompletedAction(): bool
	{
		return User::getOption(
			'task_options_checklist_show_completed',
			$this->getCurrentUser()->getId(),
			true
		);
	}
}
