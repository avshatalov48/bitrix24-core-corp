<?php
namespace Bitrix\ImOpenLines\User;

use Bitrix\ImOpenLines\Model\UserLogTable;

class Log
{
	public const TYPE_PAUSE = 'PAUSE';
	public const TYPE_PAUSE_Y = 'Y';
	public const TYPE_PAUSE_N = 'N';

	private int $userId;

	public function __construct(int $userId = 0)
	{
		if (!$userId)
		{
			$userId = \Bitrix\Im\User::getInstance()->getId();
		}

		$this->userId = $userId;
	}

	public function log(string $type, string $data): void
	{
		UserLogTable::add([
			'USER_ID' => $this->userId,
			'TYPE' => $type,
			'DATA' => $data,
		]);
	}
}
