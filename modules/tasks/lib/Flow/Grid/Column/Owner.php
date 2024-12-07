<?php

namespace Bitrix\Tasks\Flow\Grid\Column;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Grid\Preload\UserPreloader;
use Bitrix\Tasks\Flow\User\User;

final class Owner extends Column
{
	private UserPreloader $preloader;

	public function __construct()
	{
		$this->init();
	}

	public function prepareData(Flow $flow, array $params = []): array
	{
		return [
			'flow' => $flow,
			'user' => $this->preloader->get($flow->getOwnerId()),
		];
	}

	private function init(): void
	{
		$this->id = 'OWNER_ID';
		$this->name = Loc::getMessage('TASKS_FLOW_LIST_COLUMN_OWNER');
		$this->sort = '';
		$this->default = true;
		$this->editable = false;
		$this->resizeable = true;
		$this->width = null;

		$this->preloader = new UserPreloader();
	}
}
