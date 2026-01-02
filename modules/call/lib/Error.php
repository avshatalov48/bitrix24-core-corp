<?php

namespace Bitrix\Call;

use Bitrix\Main\Localization\Loc;


class Error extends \Bitrix\Main\Error
{
	public const
		CALL_NOT_FOUND = 'CALL_NOT_FOUND',
		SEND_PULL_ERROR = 'SEND_PULL_ERROR',
		WRONG_JWT = 'WRONG_JWT',
		BALANCER_ERROR = 'BALANCER_ERROR',
		PORTAL_REGISTER_ERROR = 'PORTAL_REGISTER_ERROR',
		PUBLIC_URL_EMPTY = 'PUBLIC_URL_EMPTY',
		PUBLIC_URL_MALFORMED = 'PUBLIC_URL_MALFORMED',
		PUBLIC_URL_LOCALHOST = 'PUBLIC_URL_LOCALHOST',
		PUBLIC_URL_CONVERTING_PUNYCODE = 'PUBLIC_URL_CONVERTING_PUNYCODE',
		PUBLIC_URL_FAIL = 'PUBLIC_URL_FAIL'
	;

	protected string $description = '';

	public function __construct(string $code, ...$args)
	{
		$message = null;
		$description = null;
		$customData = [];

		if (!empty($args))
		{
			$message = isset($args[0]) && is_string($args[0]) ? $args[0] : null;
			$description = isset($args[1]) && is_string($args[1]) ? $args[1] : null;
			$inx = count($args) - 1;
			$customData = isset($args[$inx]) && is_array($args[$inx]) ? $args[$inx] : [];
		}

		$replacements = [];
		foreach ($customData as $key => $value)
		{
			$replacements["#{$key}#"] = $value;
		}

		if (!is_string($message))
		{
			$message = $this->loadErrorMessage($code, $replacements);
		}

		if (is_string($message) && mb_strlen($message) > 0 && !is_string($description))
		{
			$description = $this->loadErrorDescription($code, $replacements);
		}

		if (!is_string($message) || mb_strlen($message) === 0)
		{
			$message = $code;
		}

		parent::__construct($message, $code, $customData);

		if (is_string($description))
		{
			$this->setDescription($description);
		}
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setDescription(string $description): void
	{
		$this->description = $description;
	}

	protected function loadErrorMessage($code, $replacements): string
	{
		return Loc::getMessage("ERROR_{$code}", $replacements) ?? '';
	}

	protected function loadErrorDescription($code, $replacements): string
	{
		return Loc::getMessage("ERROR_{$code}_DESC", $replacements) ?? '';
	}

	protected static function htmlToBbCodeLink(string $html): string
	{
		return preg_replace(
			[
				"#<a[^>]+href\\s*=\\s*('|\")(.+?)(?:\\1)[^>]*>(.*?)</a[^>]*>#isu",
				"#<a[^>]+href(\\s*=\\s*)([^'\">]+)>(.*?)</a[^>]*>#isu"
			],
			"[url=\\2]\\3[/url]", $html
		);
	}
}
