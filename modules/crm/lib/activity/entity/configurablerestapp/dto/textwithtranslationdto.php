<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Main\Application;

final class TextWithTranslationDto extends Dto
{
	private ?string $text = null;
	private ?array $translations = null;

	public function __construct($value = null)
	{
		parent::__construct();

		if (is_scalar($value))
		{
			$this->text = (string)$value;
		}
		if (is_array($value))
		{
			$this->translations = $value;
		}
	}

	public function __toString(): string
	{
		return $this->text ?? $this->getTranslatedText($this->translations ?? []);
	}

	public function jsonSerialize(): string|array|null
	{
		return $this->text ?? $this->translations;
	}

	private function getTranslatedText(array $translations): string
	{
		if (empty($translations))
		{
			return '';
		}

		$defaultLang = 'en';
		$currentLang = Application::getInstance()->getContext()->getLanguage() ?? $defaultLang;

		return (string)($translations[$currentLang] ?? $translations[$defaultLang] ?? reset($translations));
	}
}
