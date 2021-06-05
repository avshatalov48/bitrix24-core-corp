<?php

namespace Bitrix\Transformer;

class Log
{
	const LOG = '/bitrix/modules/transformer.log';

	/**
	 * @return bool
	 */
	private static function getMode()
	{
		if(\Bitrix\Main\Config\Option::get('transformer', 'debug'))
		{
			return true;
		}
		return false;
	}

	/**
	 * @param string|array $str Record to write.
	 * @return void
	 */
	public static function write($str)
	{
		if(self::getMode())
		{
			if(is_array($str))
			{
				$str = print_r($str, 1);
			}
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].self::LOG, date('d.m.Y h:i:s').': '.$str.PHP_EOL, FILE_APPEND);
		}
	}

	/**
	 * clears log file.
	 * @return void
	 */
	public static function clear()
	{
		if(self::getMode())
		{
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].self::LOG, '');
		}
	}
}
