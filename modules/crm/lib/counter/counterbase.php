<?php
namespace Bitrix\Crm\Counter;
abstract class CounterBase
{
	/** @var int */
	protected $userID = 0;

	public function getUserID()
	{
		return $this->userID;
	}
	/**
	 * @param int $userID User ID.
	 * @return void
	 */
	protected function setUserID($userID)
	{
		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}

		if($userID < 0)
		{
			$userID = 0;
		}

		$this->userID = $userID;
	}

	public abstract function getValue($recalculate = false);
	public abstract function getCode();
}