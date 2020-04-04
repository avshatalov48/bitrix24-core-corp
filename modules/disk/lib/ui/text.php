<?php

namespace Bitrix\Disk\Ui;

use Bitrix\Disk\BaseObject;
use Bitrix\Main\IO\Path;

final class Text
{

	/**
	 * Get numeric case for lang messages.
	 * @param $number
	 * @param $once
	 * @param $multi21
	 * @param $multi2_4
	 * @param $multi5_20
	 * @return string
	 */
	public static function getNumericCase($number, $once, $multi21, $multi2_4, $multi5_20)
	{
		if($number == 1)
		{
			return $once;
		}

		if($number < 0)
		{
			$number = -$number;
		}

		$number %= 100;
		if($number >= 5 && $number <= 20)
		{
			return $multi5_20;
		}

		$number %= 10;
		if($number == 1)
		{
			return $multi21;
		}

		if($number >= 2 && $number <= 4)
		{
			return $multi2_4;
		}

		return $multi5_20;
	}

	/**
	 * Clean possible trash can suffix from string. We know suffix length.
	 * @param $string
	 * @return string
	 */
	public static function cleanTrashCanSuffix($string)
	{
		if(
			substr($string, -1) !== 'i' ||
			strlen($string) < 17 ||
			substr($string, -16, 1) !== 'i' ||
			!(
				preg_match('%i[0-9a-z]{0,4}[0-9]{10,12}i$%iU' . BX_UTF_PCRE_MODIFIER, $string) ||
				preg_match('%i[0-9]{11}[a-z]{3}i$%iU' . BX_UTF_PCRE_MODIFIER, $string) //our cp old version
			)
		)
		{
			return $string;
		}

		return substr($string, 0, -16);
	}

	/**
	 * Append trash can suffix to string.
	 * @param $string
	 * @return string
	 */
	public static function appendTrashCanSuffix($string)
	{
		return $string . 'i' . str_pad(strtr(microtime(true), array('.' => '')), 14, chr(rand(97, 122)), STR_PAD_LEFT) . 'i';
	}

	/**
	 * Kill all tags from text.
	 * @param $text
	 * @return string
	 */
	public static function killTags($text)
	{
		$text = strip_tags($text);
		return preg_replace(
			array(
				"/\<(\/?)(quote|code|font|color|video)([^\>]*)\>/is".BX_UTF_PCRE_MODIFIER,
				"/\[(\/?)(b|u|i|s|list|code|quote|font|color|url|img|video)([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER,
				"/\[[0-9a-zA-Z\W\=]+\]/iUs".BX_UTF_PCRE_MODIFIER,
			),
			"",
			$text);
	}

	/**
	 * Replaces invalid characters in filename by _.
	 * @param $filename
	 * @return string
	 */
	public static function correctFilename($filename)
	{
		return self::correctObjectName($filename);
	}

	/**
	 * Replaces invalid characters in folder name by _.
	 * Removes dots in folder name from end of string. It's for compatible with Windows.
	 * @param $folderName
	 * @return string
	 */
	public static function correctFolderName($folderName)
	{
		$folderName = self::correctObjectName($folderName);
		if(substr($folderName, -1) === '.')
		{
			return rtrim($folderName, '.');
		}

		return $folderName;
	}

	protected static function correctObjectName($objectName)
	{
		$objectName = trim($objectName);
		if(BaseObject::isValidValueForField('NAME', $objectName))
		{
			return $objectName;
		}

		return preg_replace("#([\x01-\x1F".preg_quote(Path::INVALID_FILENAME_CHARS, "#")."])#", '_', $objectName);
	}
}
