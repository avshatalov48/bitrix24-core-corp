<?php

namespace Bitrix\Imopenlines\Update;

use Bitrix\ImOpenLines\Im;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Update\Stepper;

Loc::loadMessages(__FILE__);

final class OrphanedSessionCleaner extends Stepper
{
	private const SESSION_LIMIT = 100;

	protected static $moduleId = "imopenlines";

	public function execute(array &$option): bool
	{
		if (!Loader::includeModule(self::$moduleId))
		{
			return self::FINISH_EXECUTION;
		}

		$outerParams = $this->getOuterParams();
		$configId = (int)($outerParams[0] ?? 0);
		if ($configId === 0)
		{
			return Stepper::FINISH_EXECUTION;
		}

		$sessions = SessionTable::getList([
			'select' => ['ID', 'CHAT_ID', 'CLOSED'],
			'filter' => [
				'USER_CODE' => '%|' . $configId . '|%|%',
			],
			'limit' => self::SESSION_LIMIT,
			'order' => [
				'ID' => 'DESC'
			],
		]);

		if ($sessions->getSelectedRowsCount() === 0)
		{
			return Stepper::FINISH_EXECUTION;
		}

		while($session = $sessions->fetch())
		{
			if ($session['CLOSED'] != 'Y')
			{
				Im::chatHide($session['CHAT_ID']);
			}

			\Bitrix\ImOpenLines\Session::deleteSession($session['ID']);
		}

		return Stepper::CONTINUE_EXECUTION;
	}
}
