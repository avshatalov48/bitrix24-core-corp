<?php
namespace Bitrix\Tasks\Rest\ActionFilter;


use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Engine\ActionFilter\Base;

final class Task extends Base
{
	/**
	 * List allowed values of scopes where the filter should work.
	 * @return array
	 */
	public function listAllowedScopes()
	{
		return array(
			Controller::SCOPE_AJAX,
			Controller::SCOPE_REST,
		);
	}
}