<?php

namespace Bitrix\Imopenlines\Update\Onetime;

use Bitrix\ImOpenLines\Im;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\ImOpenLines\Recent;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Update\Stepper;

Loc::loadMessages(__FILE__);

final class ClosedSessionsRecentRemover extends Stepper
{
	private const SESSION_LIMIT = 1000;

	private const STATUSES = [
		\Bitrix\ImOpenLines\Session::STATUS_CLOSE,
		\Bitrix\ImOpenLines\Session::STATUS_SPAM,
		\Bitrix\ImOpenLines\Session::STATUS_DUPLICATE,
		\Bitrix\ImOpenLines\Session::STATUS_SILENTLY_CLOSE,
	];

	protected static $moduleId = 'imopenlines';

	public function execute(array &$option): bool
	{
		if (!Loader::includeModule(self::$moduleId))
		{
			return self::FINISH_EXECUTION;
		}

		if (!isset($option['session_status']))
		{
			$option['session_status'] = \Bitrix\ImOpenLines\Session::STATUS_CLOSE;
			$option['offset'] = 0;
		}

		$sessions = SessionTable::getList([
			'select' => ['ID', 'CHAT_ID'],
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
		else
		{
			$option['offset'] = ($option['offset'] ?? 0) + self::SESSION_LIMIT;
		}

		while($session = $sessions->fetch())
		{
			$chat = \Bitrix\Im\V2\Chat::getInstance((int)$session['CHAT_ID']);
			if ($chat->getEntityData1())
			{
				$lastSessionId = (int)(explode('|', $chat->getEntityData1())[5] ?? 0);
				if ($lastSessionId > 0 && $lastSessionId === (int)$session['ID'])
				{
					Im::hideFromRecent($session['CHAT_ID'], false);
					Recent::clearRecent((int)$session['ID']);
				}
			}
		}

		return Stepper::CONTINUE_EXECUTION;
	}
}
