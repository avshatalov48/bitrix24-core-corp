<?php
namespace Bitrix\Tasks\Rest\Controllers;

use Bitrix\Main\Engine\Controller;

class UserOption extends Controller
{
	/**
	 * @return array
	 */
	public function getCalendarTimeVisibilityOptionAction(): array
	{
		return \CUserOptions::getOption('tasks.bx.calendar.deadline', 'time_visibility', []);
	}
}