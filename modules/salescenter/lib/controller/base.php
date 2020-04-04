<?php

namespace Bitrix\SalesCenter\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\SalesCenter\Model\Page;
use Bitrix\SalesCenter\Model\PageTable;

abstract class Base extends Controller
{
	/**
	 * @return array|\Bitrix\Main\Engine\AutoWire\Parameter[]
	 */
	public function getAutoWiredParameters()
	{
		return [
			new \Bitrix\Main\Engine\AutoWire\ExactParameter(
				Page::class,
				'page',
				function($className, $id)
				{
					return PageTable::getById($id)->fetchObject();
				}
			),
		];
	}

	/**
	 * @param array $array
	 * @param array $requiredParams
	 * @return array
	 */
	protected function checkArrayRequiredParams(array $array, array $requiredParams)
	{
		$emptyParams = [];

		foreach($requiredParams as $param)
		{
			if(!isset($array[$param]) || empty($array[$param]))
			{
				$emptyParams[] = $param;
			}
		}

		return $emptyParams;
	}
}