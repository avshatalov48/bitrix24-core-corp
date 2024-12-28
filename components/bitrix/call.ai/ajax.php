<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Access\Exception\AccessException;
use Bitrix\Im\Call\Call;


class CallAiAjaxController extends Controller
{
	protected ?int $callId;
	protected ?Call $call;

	public function getDefaultPreFilters(): array
	{
		return [
			new Authentication(),
			new Csrf(),
			new Scope(Scope::AJAX),
		];
	}

	public function init(): bool
	{
		parent::init();

		try
		{
			return
				Loader::requireModule('im')
				&& Loader::requireModule('call');
		}
		catch (SystemException)
		{
			return false;
		}
	}

	protected function checkAccess(): bool
	{
		$currentUserId = \Bitrix\Main\Engine\CurrentUser::get()->getId();
		return $this->call->checkAccess($currentUserId);
	}


	/**
	 * @param int $callId
	 * @throws InvalidArgumentException
	 * @throws AccessException
	 * @return array
	 */
	public function getUsersAction(int $callId): array
	{
		$this->callId = $callId;
		if (!$this->callId)
		{
			throw new InvalidArgumentException('CallAI: Incorrect parameter callId');
		}

		$this->call = \Bitrix\Im\Call\Registry::getCallWithId($this->callId);
		if (!$this->call)
		{
			throw new InvalidArgumentException('CallAI: Incorrect parameter callId');
		}

		if (!$this->checkAccess())
		{
			throw new AccessException('You don\'t have access to call #'.$this->callId);
		}

		$callUsers = $this->call->getCallUsers();
		$userData = $this->call->getUserData();

		$users = [];
		foreach ($callUsers as $userId => $callUser)
		{
			if ($callUser->getFirstJoined())
			{
				$users[$userId] = $userData[$userId];
			}
		}
		if (!isset($users[$this->call->getInitiatorId()]))
		{
			$userId = $this->call->getInitiatorId();
			$users[$userId] = $userData[$userId];
		}

		return [
			'users' => $users
		];
	}
}