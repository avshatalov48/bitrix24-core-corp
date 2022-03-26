<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2023 Bitrix
 */
namespace Bitrix\Tasks\Internals\Marketing\OneOff;

class ResultTutorial
{
	private const USER_OPTION_CATEGORY = 'tasks';
	private const USER_OPTION = 'result_tutorial_disabled';

	private $userId;

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		return !!\CUserOptions::GetOption(self::USER_OPTION_CATEGORY, self::USER_OPTION, false, $this->userId);
	}

	/**
	 *
	 */
	public function disable(): void
	{
		\CUserOptions::SetOption(self::USER_OPTION_CATEGORY, self::USER_OPTION, 'Y', false, $this->userId);
	}
}