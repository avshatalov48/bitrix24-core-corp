<?php
namespace Bitrix\ImConnector\Emoji;

class Emojione
{
	/**
	 * Converts the text short name and Unicode emoticons in the BB tag ICON.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function parseTextToEmoji($text)
	{
		$emoji = new Client;
		return $emoji->toImage($text);
	}
}
