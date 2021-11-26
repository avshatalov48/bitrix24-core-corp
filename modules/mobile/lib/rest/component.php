<?php

namespace Bitrix\Mobile\Rest;

class Component extends \IRestService
{
	public static function getMethods()
	{
		return [
			'mobile.component.customparams.set' => ['callback' => [__CLASS__, 'setParams'], 'options' => ['private' => false]],
		];
	}

	public static function setParams($params, $offset, \CRestServer $server)
	{
		global $USER;

		if (array_key_exists('clear', $params) && array_key_exists('name', $params)) {
			$userId = $USER->getId();
			$optionName = 'clear_'.$params['name'].'_'.$userId;
			\Bitrix\Main\Config\Option::set("mobile", $optionName, true);
		}

		return [];

	}


}