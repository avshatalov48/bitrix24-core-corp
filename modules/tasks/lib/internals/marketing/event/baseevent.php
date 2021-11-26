<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Marketing\Event;


use Bitrix\Tasks\Internals\Marketing\EventInterface;
use Bitrix\Tasks\Internals\Marketing\MarketingTable;

abstract class BaseEvent
	implements EventInterface
{
	protected $userId;
	protected $params;

	/**
	 * @param int $userId
	 * @param null $params
	 */
	public function __construct(int $userId, $params = null)
	{
		$this->userId = $userId;
		$this->params = $params;
	}

	/**
	 * @return int
	 */
	abstract public function getDateSheduled(): int;

	/**
	 * @return bool
	 */
	abstract public function execute(): bool;

	/***
	 * @return bool
	 */
	public function validate(): bool
	{
		if ($this->ifDisabled())
		{
			return false;
		}
		return true;
	}

	/**
	 * @return string
	 */
	public function getClass(): string
	{
		return static::class;
	}

	/**
	 * @return null
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 *
	 */
	protected function disableEvent()
	{
		\CUserOptions::SetOption('tasks', $this->getDisableOptionName(), 1, false, $this->userId);
	}

	/**
	 * @return bool
	 */
	protected function ifDisabled(): bool
	{
		return (bool) \CUserOptions::GetOption('tasks', $this->getDisableOptionName(), 0, $this->userId);
	}

	/**
	 * @return string
	 */
	protected function getDisableOptionName(): string
	{
		$res = $this->getClass();
		$res = explode('\\', $res);
		return 'disable'.$res[count($res)-1];
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function isEventExists(bool $onlyExecuted = false): bool
	{
		$filter = [
			'=USER_ID' => $this->userId,
			'=EVENT' => $this->getClass(),
		];

		if ($onlyExecuted)
		{
			$filter['>DATE_EXECUTED'] = 0;
		}

		$res = MarketingTable::getList([
			'filter' => $filter,
		]);

		if ($res->getSelectedRowsCount() > 0)
		{
			return true;
		}

		return false;
	}
}