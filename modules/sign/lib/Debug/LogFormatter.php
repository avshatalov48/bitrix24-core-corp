<?php
namespace Bitrix\Sign\Debug;

use Bitrix\Main\Diag\LogFormatter as BaseFormatter;
use Bitrix\Main\Type\Date;

class LogFormatter extends BaseFormatter
{
	public const SIGN_PLACEHOLDER_DUMP = 'sign_debug_dump';
	public const SIGN_PLACEHOLDER_HOST = 'sign_debug_host';
	public const PLACEHOLDER_TRACE = 'trace';
	public const PLACEHOLDER_EXCEPTION = 'exception';
	public const PLACEHOLDER_DATE = 'date';

	public function format($message, array $context = []): string
	{
		if (isset($context[self::SIGN_PLACEHOLDER_DUMP]))
		{
			self::cutFiles($context[self::SIGN_PLACEHOLDER_DUMP]);
		}

		if (!isset($context[self::PLACEHOLDER_DATE]))
		{
			$context[self::PLACEHOLDER_DATE] = new Date();
		}

		return parent::format($this->getFormattedMessage($message, $context), $context);
	}

	/**
	 * @param mixed $data 
	 */
	private static function cutFiles(&$data): void
	{
		if (is_array($data))
		{
			array_walk_recursive($data, static function (&$value, $key)
			{
				if (in_array($key, ['securityCode', 'token', 'pageToken']))
				{
					$value = substr($value, 0, 5) . ' ... <cut by logger>';
					return;
				}

				if (is_string($value))
				{
					$strlen = strlen($value);
					switch (true)
					{
						case $strlen > 300 && substr($value, 0, 4) === "%PDF":
						case $strlen > 300 && substr($value, 1, 3) === 'PNG':
						case $strlen > 300 && self::isContentLooksLikeBase64($value):
							$value = substr($value, 0, 50) . ' ... <content cut by logger>';
							break;
						case $strlen > 5000:
							$value = substr($value, 0, 300) . ' ... <content cut by logger>';
							break;
					}
				}
			});
		}
	}

	private static function isContentLooksLikeBase64(string $data, bool $exact = true): bool
	{
		$regexp = '(?:[A-Za-z0-9+\/]{4})*(?:[A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=)?';
		$regexp = $exact
			? '^'.$regexp.'$'
			: $regexp
		;
		return preg_match('/'.$regexp.'/', $data) === 1;
	}

	private function getFormattedMessage($message, array $context = []): string
	{
		$host = null;
		if (isset($context[self::SIGN_PLACEHOLDER_HOST]))
		{
			$host = $this->castToString($context[self::SIGN_PLACEHOLDER_HOST]);
		}

		$text = "\n";
		$text .= '{'.self::PLACEHOLDER_DATE.'}';
		$text .= ' - Host: ' . ($host ?: 'unknown');

		$text .= is_string($message) && strlen($message) <= 100
			? " - $message"
			: ' - sign debug'
		;

		if (isset($context[self::SIGN_PLACEHOLDER_HOST]))
		{
			$text .= "\nHOST: $host";
		}

		if (!empty($message))
		{
			$text .= "\nMESSAGE_START\n";
			$text .= $this->castToString($message);
			$text .= "\nMESSAGE_END";
		}

		if (isset($context[self::SIGN_PLACEHOLDER_DUMP]))
		{
			$text .= "\nDUMP_START\n";
			$text .= $this->castToString($context[self::SIGN_PLACEHOLDER_DUMP]);
			$text .= "\nDUMP_END";
		}

		if (isset($context[self::PLACEHOLDER_TRACE]))
		{
			$text .= "\nTRACE_START\n";
			$text .= '{'.self::PLACEHOLDER_TRACE.'}';
			$text .= "\nTRACE_END";
		}

		if (isset($context[self::PLACEHOLDER_EXCEPTION]))
		{
			$text .= "\nEXCEPTION_START\n";
			$text .= '{'.self::PLACEHOLDER_EXCEPTION.'}';
			$text .= "\nEXCEPTION_END";
		}

		$text .= "\n{delimiter}";
		return $text;
	}
}