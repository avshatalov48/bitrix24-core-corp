<?php
namespace Bitrix\Imopenlines\Update;

use \Bitrix\Main\Loader;

final class Config
{
	public static function update222000()
	{
		if (
			class_exists('\Bitrix\ImOpenLines\Config')
			&& method_exists('\Bitrix\ImOpenLines\Config', 'createPreset')
		)
		{
			(new \Bitrix\ImOpenLines\Config)->createPreset();

			return '';
		}

		return __METHOD__. '();';
	}
}