<?php

namespace Bitrix\Voximplant\Search;

class Content
{
	const PHONE_NUMBER_REGEX = '/[+]{0,1}[0-9](?:[-\/.\s()\[\]~]*[0-9]){3,}[-\/.\s()\[\]~0-9]*[0-9]/';

	public static function normalizePhoneNumbers($string)
	{
		return preg_replace_callback(
			static::PHONE_NUMBER_REGEX,
			function($matches)
			{
				return \CVoxImplantPhone::stripLetters($matches[0]);
			},
			$string
		);
	}

	/**
	 * Applies ROT13 transform to search token, in order to bypass default mysql search blacklist.
	 * @param string $token Search token.
	 * @return string
	 */
	public static function prepareToken($token)
	{
		return str_rot13($token);
	}
}