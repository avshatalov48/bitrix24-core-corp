<?php
namespace Bitrix\ImBot;

class Log
{
	public static function write($data, $title = '')
	{
		if (!\Bitrix\Main\Config\Option::get("imbot", "debug"))
			return false;

		if (is_array($data))
		{
			unset($data['HASH']);
			unset($data['BX_HASH']);
		}
		else if (is_object($data))
		{
			if ($data->HASH)
			{
				$data->HASH = '';
			}
			if ($data->BX_HASH)
			{
				$data->BX_HASH = '';
			}
		}

		$title = strlen($title) > 0? $title: "DEBUG";

		return self::writeToFile("imbot.log", $data, $title);
	}

	public static function writeToFile($fileName, $data, $title = '')
	{
		$log = "\n------------------------\n";
		$log .= date("Y.m.d G:i:s")."\n";
		if (strlen($title) > 0)
		{
			$log .= $title."\n";
		}
		$log .= print_r($data, 1);
		$log .= "\n------------------------\n";

		if (function_exists('BXSiteLog'))
		{
			BXSiteLog($fileName, $log);
		}
		else
		{
			\Bitrix\Main\IO\File::putFileContents($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$fileName, $log, \Bitrix\Main\IO\File::APPEND);
		}

		return true;
	}
}