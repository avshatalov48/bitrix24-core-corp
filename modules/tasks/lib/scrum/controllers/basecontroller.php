<?php

namespace Bitrix\Tasks\Scrum\Controllers;

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Tasks\Integration\SocialNetwork\Group;

class BaseController extends Controller
{
	protected const ERROR_COULD_NOT_LOAD_MODULE = 'TASKS_EC_01';
	protected const ERROR_ACCESS_DENIED = 'TASKS_EC_02';

	protected int $userId;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);
		$this->userId = (int) CurrentUser::get()->getId();
	}

	protected function processBeforeAction(Action $action): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_EC_ERROR_INCLUDE_MODULE_ERROR'),
					self::ERROR_COULD_NOT_LOAD_MODULE
				)
			);

			return false;
		}

		$post = $this->request->getPostList()->toArray();

		$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

		if (!Group::canReadGroupTasks($this->userId, $groupId))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_EC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return false;
		}

		return parent::processBeforeAction($action);
	}
}