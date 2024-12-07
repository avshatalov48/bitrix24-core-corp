<?php

namespace Bitrix\Tasks\Flow\Notification\Config;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Notification\Exception\InvalidPayload;
use Bitrix\Tasks\ValueObjectInterface;

class Caption implements ValueObjectInterface
{
	private string $text;

	public function __construct(string $translationKeyOrPlainText)
	{
		if (strlen($translationKeyOrPlainText) > 512)
		{
			throw new InvalidPayload('Caption must be less or equal to 512 characters');
		}

		$this->text = $translationKeyOrPlainText;
	}

	public function getValue(): string
	{
		return $this->text;
	}

	public function getTranslatedText(): string
	{
		return Loc::getMessage($this->text) ?? $this->text;
	}
}