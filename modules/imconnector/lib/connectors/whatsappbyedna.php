<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\Localization\Loc;

/**
 * Class WhatsappByEdna
 * @package Bitrix\ImConnector\Connectors
 */
class WhatsappByEdna extends Base
{
	protected static $smileToUnicode = [
		':)' => "\xF0\x9F\x99\x82",
		':-)' => "\xF0\x9F\x99\x82",
		';)' => "\xF0\x9F\x98\x89",
		';-)' => "\xF0\x9F\x98\x89",
		':D' => "\xF0\x9F\x98\x84",
		':-D' => "\xF0\x9F\x98\x84",
		'8-)' => "\xF0\x9F\x98\x8E",
		'8)' => "\xF0\x9F\x98\x8E",
		':facepalm:' => "\xF0\x9F\xA4\xA6",
		':{}' => "\xF0\x9F\x98\x97",
		':-{}' => "\xF0\x9F\x98\x97",
		':(' => "\xF0\x9F\x99\x81",
		':-(' => "\xF0\x9F\x99\x81",
		':-|' => "\xF0\x9F\x98\x90",
		':|' => "\xF0\x9F\x98\x90",
		':oops:' => "\xF0\x9F\x98\xB3",
		':cry:' => "\xF0\x9F\x98\xA2",
		':~(' => "\xF0\x9F\x98\xA2",
		':evil:' => "\xF0\x9F\x91\xBF",
		'>:-<' => "\xF0\x9F\x91\xBF",
		':o' => "\xF0\x9F\x98\xAE",
		':-o' => "\xF0\x9F\x98\xAE",
		':shock:' => "\xF0\x9F\x98\xAE",
		':/' => "\xF0\x9F\x98\x95",
		':-/' => "\xF0\x9F\x98\x95",
		':idea:' => "\xF0\x9F\x92\xA1",
		':?:' => "\xE2\x9D\x93\xEF\xB8\x8F",
		':!:' => "\xE2\x9D\x97\xEF\xB8\x8F",
		':like:' => "\xF0\x9F\x91\x8D",
	];

	/**
	 * @param string $text Message text.
	 * @return string
	 */
	public static function cleanTextOut(string $text = ''): string
	{
		$text = str_replace(["\n", "[br]", "#br#", "[BR]", "#BR#"], "\n", $text);
		$text = preg_replace("/\[[buis]\](.*?)\[\/[buis]\]/i", "$1", $text);
		$text = preg_replace("/\\[url\\](.*?)\\[\\/url\\]/i" . BX_UTF_PCRE_MODIFIER, "$1", $text);
		$text = preg_replace_callback("/\\[url\\s*=\\s*((?:[^\\[\\]]++|\\[ (?: (?>[^\\[\\]]+) | (?:\\1) )* \\])+)\\s*\\](.*?)\\[\\/url\\]/ixs" . BX_UTF_PCRE_MODIFIER,
			function (&$matches) {
				if ($matches[2] != $matches[1])
					return $matches[2] . ': ' . $matches[1];
				else
					return $matches[1];
			},
			$text);
		$text = preg_replace("/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/i", "$2", $text);
		$text = preg_replace("/\[CHAT=([0-9]{1,})\](.*?)\[\/CHAT\]/i", "$2", $text);
		$text = preg_replace("/\[SEND(?:=(.+?))?\](.+?)?\[\/SEND\]/i", "$2", $text);
		$text = preg_replace("/\[PUT(?:=(.+?))?\](.+?)?\[\/PUT\]/i", "$2", $text);
		$text = preg_replace("/\[CALL(?:=(.+?))?\](.+?)?\[\/CALL\]/i", "$2", $text);
		$text = preg_replace("/\[PCH=([0-9]{1,})\](.*?)\[\/PCH\]/i", "$2", $text);
		$text = preg_replace("/\[ATTACH=([0-9]{1,})\]/i", "", $text);
		$text = preg_replace("/\[ICON=(.+?)\]/i", "", $text);

		return $text;
	}

	/**
	 * Converts standard smiles to emoji symbols.
	 *
	 * @param string $text Input text.
	 * @return string
	 */
	public static function convertSmiles(string $text = ''): string
	{
		static $pattern = [];
		static $smileOut = [];

		if (empty($pattern))
		{
			foreach (static::$smileToUnicode as $smile => $emoji)
			{
				$pattern[] = '/(((?<=\\s)|^)(' . preg_quote($smile, '/') . ')(?=\\s|$|[!,.?]))/S';
				$smileOut[] = $emoji;
			}
		}
		return preg_replace($pattern, $smileOut, $text);
	}

	public function sendMessageProcessing(array $message, $line): array
	{
		if ($message['message']['text'])
		{
			$message['message']['text'] = self::convertSmiles($message['message']['text']);
			$message['message']['text'] = self::cleanTextOut($message['message']['text']);
		}

		return parent::sendMessageProcessing($message, $line);
	}

	//Input
	/**
	 * @param array $chat
	 * @return array
	 */
	protected function processingChat(array $chat): array
	{
		if (isset($chat['last_message']) && $chat['last_message'] !== '')
		{
			$chat['description'] = Loc::getMessage(
				'IMCONNECTOR_WHATSAPPBYEDNA_ADDITIONAL_DATA',
				[
					'#TEXT#' => $chat['last_message']
				]
			);

			unset($chat['last_message']);
		}

		return $chat;
	}
	//END Input
}
