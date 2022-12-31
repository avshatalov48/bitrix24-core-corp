<?php

namespace Bitrix\Mobile\UI\StatefulList;

use Exception;

class BaseController extends \Bitrix\Main\Engine\Controller
{
	protected const PREFIX = '';

	protected function init()
	{
		parent::init();

		define('BX_MOBILE', true);
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function configureActions(): array
	{
		throw new Exception('Need implement this method in children');
	}

	/**
	 * @return array
	 */
	public static function getActionsList(): array
	{
		$actions = [];

		foreach ((new static())->listNameActions() as $action)
		{
			$actions[$action] = static::PREFIX . '.' . $action;
		}

		return $actions;
	}
}
