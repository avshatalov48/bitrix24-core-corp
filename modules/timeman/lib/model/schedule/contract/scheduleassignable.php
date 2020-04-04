<?php
namespace Bitrix\Timeman\Model\Schedule\Contract;

interface ScheduleAssignable
{
	/**
	 * @return string
	 */
	public function getEntityCode();
}