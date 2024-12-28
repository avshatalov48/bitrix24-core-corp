<?php

namespace Bitrix\AI\Payload\Formatter;

use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Context;

class Language extends Formatter implements IFormatter
{
	private const MARKER = '{language}';
	private const MARKER_PATTERN = '/\{language\.([a-zA-Z]{2})\}/';

	/**
	 * @inheritDoc
	 */
	public function format(array $additionalMarkers = []): string
	{
		if (str_contains($this->text, self::MARKER))
		{
			$this->text = str_replace(self::MARKER, $this->getLanguageName(), $this->text);
		}

		$this->text = preg_replace_callback(self::MARKER_PATTERN, function($matches) {
			return $this->getLanguageName($matches[1]);
		}, $this->text);

		return $this->text;
	}

	protected function getLanguageName(?string $langCode = null): string
	{
		$contextLang = $this->engine->getContext()->getLanguage();

		if ($contextLang)
		{
			return $contextLang->getName($langCode);
		}

		if ($langCode !== null)
		{
			$language = new Context\Language(Bitrix24::getUserLanguage());

			return $language->getName($langCode);
		}

		return Bitrix24::getUserLanguage();
	}
}