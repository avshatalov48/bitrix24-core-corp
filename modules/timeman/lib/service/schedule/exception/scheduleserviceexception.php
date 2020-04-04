<?php
namespace Bitrix\Timeman\Service\Schedule\Exception;

class ScheduleServiceException extends \Exception
{
	private $result;

	/**
	 * @return mixed
	 */
	public function getResult()
	{
		return $this->result;
	}

	/**
	 * @param mixed $result
	 * @return ScheduleServiceException
	 */
	public function setResult($result)
	{
		$this->result = $result;
		return $this;
	}
}