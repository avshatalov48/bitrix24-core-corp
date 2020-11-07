<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Main;

class Path
{
	public const INVALID_FILENAME_BYTES = Main\IO\Path::INVALID_FILENAME_BYTES;
	public const INVALID_FILENAME_CHARS = "<>:\"|?*\\/";

	public static function correctFilename($filename)
	{
		return self::replaceInvalidFilename($filename, static function(){
			return '_';
		});
	}

	public static function replaceInvalidFilename($filename, $callback)
	{
		return preg_replace_callback(
			"#([\x01-\x1F".preg_quote(self::INVALID_FILENAME_CHARS, "#")."]|".self::INVALID_FILENAME_BYTES.")#",
			$callback,
			$filename
		);
	}

	public static function validateFilename($filename): bool
	{
		if (!static::validateCommon($filename))
		{
			return false;
		}

		return (preg_match("#^[^\x01-\x1F".preg_quote(self::INVALID_FILENAME_CHARS, "#")."]+$#isD", $filename) > 0);
	}

	protected static function validateCommon($path): bool
	{
		/** @see \Bitrix\Main\IO\Path::validateCommon() */
		if (!is_string($path))
		{
			return false;
		}

		if (trim($path) === "")
		{
			return false;
		}

		if (strpos($path, "\0") !== false)
		{
			return false;
		}

		if (preg_match("#(" . Main\IO\Path::INVALID_FILENAME_BYTES . ")#", $path))
		{
			return false;
		}

		return true;
	}
}