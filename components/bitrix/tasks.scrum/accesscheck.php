<?php

namespace Bitrix\Tasks\Component\Scrum;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Util;

class AccessCheck extends Main\Engine\ActionFilter\Base
{
	private $groupId;

	public function __construct(int $groupId)
	{
		parent::__construct();

		$this->groupId = $groupId;
	}
	/**
	 * @param Main\Event $event
	 * @return Main\EventResult|null
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function onBeforeAction(Main\Event $event): ?Main\EventResult
	{
		if (!Main\Loader::includeModule('tasks') || !Main\Loader::includeModule('socialnetwork'))
		{
			$this->addError(new Main\Error('Access denied'));

			return new Main\EventResult(Main\EventResult::ERROR, null, null, $this);
		}

		$userId = Util\User::getId();

		if (!Group::canReadGroupTasks($userId, $this->groupId))
		{
			$this->addError(new Main\Error('Access denied'));

			return new Main\EventResult(Main\EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}