<?php

namespace Bitrix\Sign\Helper;

class StringHelper
{
	public static function convertCssCaseToCamelCase(string $string): string
	{
		$word = ucwords($string, '-');
		$word = str_replace('-', '', $word);
		return lcfirst($word);
	}

	public static function convertKebabCaseToScreamingSnakeCase(string $string): string
	{
		$word = mb_strtoupper($string);

		return str_replace('-', '_', $word);
	}
}