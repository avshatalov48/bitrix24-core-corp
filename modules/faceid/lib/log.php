<?php
namespace Bitrix\FaceId;

class Log
{
	public static function write($data, $title = '')
	{
		if (!\Bitrix\Main\Config\Option::get("faceid", "debug"))
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

		$log = "\n------------------------\n";
		$log .= date("Y.m.d G:i:s")."\n";
		$log .= (strlen($title) > 0 ? $title : 'DEBUG')."\n";
		$log .= print_r($data, 1);
		$log .= "\n------------------------\n";

		\Bitrix\Main\IO\File::putFileContents($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/faceid.log", $log, \Bitrix\Main\IO\File::APPEND);
		
		return true;
	}
}