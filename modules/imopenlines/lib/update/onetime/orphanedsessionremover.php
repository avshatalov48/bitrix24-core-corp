<?php

namespace Bitrix\Imopenlines\Update\Onetime;

use Bitrix\ImOpenLines\Im;
use Bitrix\ImOpenLines\Model\ConfigTable;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Update\Stepper;

Loc::loadMessages(__FILE__);

final class OrphanedSessionRemover extends Stepper
{
	private const SESSION_LIMIT = 100;
	private const STATUSES = [
		\Bitrix\ImOpenLines\Session::STATUS_NEW,
		\Bitrix\ImOpenLines\Session::STATUS_SKIP,
		\Bitrix\ImOpenLines\Session::STATUS_ANSWER,
		\Bitrix\ImOpenLines\Session::STATUS_CLIENT,
		\Bitrix\ImOpenLines\Session::STATUS_CLIENT_AFTER_OPERATOR,
		\Bitrix\ImOpenLines\Session::STATUS_OPERATOR,
		\Bitrix\ImOpenLines\Session::STATUS_WAIT_CLIENT,
	];

	protected static $moduleId = "imopenlines";

	public function execute(array &$option): bool
	{
		if (!Loader::includeModule(self::$moduleId))
		{
			return self::FINISH_EXECUTION;
		}

		$configs = ConfigTable::getList([
			'select' => ['ID'],
		]);

		$configIds = [];
		while($config = $configs->fetch())
		{
			$configIds[] = (int)$config['ID'];
		}

		if (!isset($option['session_status']))
		{
			$option['session_status'] = \Bitrix\ImOpenLines\Session::STATUS_NEW;
			$option['offset'] = 0;
		}

		$sessions = SessionTable::getList([
			'select' => ['ID', 'USER_CODE', 'CHAT_ID', 'CLOSED'],
			'filter' => [
				'=STATUS' => $option['session_status'],
			],
			'limit' => self::SESSION_LIMIT,
			'offset' => $option['offset'] ?? 0,
			'order' => [
				'DATE_CREATE' => 'ASC'
			],
		]);

		if ($sessions->getSelectedRowsCount() === 0)
		{
			if ($option['session_status'] == \Bitrix\ImOpenLines\Session::STATUS_WAIT_CLIENT)
			{
				return Stepper::FINISH_EXECUTION;
			}
			else
			{
				$currentStatusIndex = array_search($option['session_status'], self::STATUSES, true);
				if ($currentStatusIndex !== false && isset(self::STATUSES[$currentStatusIndex + 1]))
				{
					$option['session_status'] = self::STATUSES[$currentStatusIndex + 1];
					$option['offset'] = 0;
				}
				else
				{
					return Stepper::FINISH_EXECUTION;
				}
			}
		}
		else
		{
			$option['offset'] = ($option['offset'] ?? 0) + self::SESSION_LIMIT;
		}

		while($session = $sessions->fetch())
		{
			$userCode = explode('|', $session['USER_CODE']);
			if (!in_array((int)$userCode[1], $configIds, true))
			{
				if ($session['CLOSED'] != 'Y')
				{
					Im::chatHide($session['CHAT_ID']);
				}

				\Bitrix\ImOpenLines\Session::deleteSession($session['ID']);
			}
		}

		return Stepper::CONTINUE_EXECUTION;
	}
}
