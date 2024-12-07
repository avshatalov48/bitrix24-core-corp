<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Rest\Controllers\option;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\Internals\UserOption\Service\AutoMuteService;

class AutoMute extends Controller
{
	protected AutoMuteService $autoMuteService;
	protected int $userId;

	/** @restMethod tasks.option.automute.disable */
	public function disableAction(): ?array
	{
		$result = $this->autoMuteService->disable($this->userId);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return ['success' => true];
	}

	/** @restMethod tasks.option.automute.enable */
	public function enableAction(): ?array
	{
		$result = $this->autoMuteService->enable($this->userId);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return ['success' => true];
	}

	protected function init(): void
	{
		parent::init();

		$this->userId = (int)CurrentUser::get()->getId();
		$this->autoMuteService = ServiceLocator::getInstance()->get('tasks.user.option.automute.service');
	}
}