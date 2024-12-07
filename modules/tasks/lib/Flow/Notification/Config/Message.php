<?php

namespace Bitrix\Tasks\Flow\Notification\Config;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Notification\Exception\InvalidPayload;
use Bitrix\Tasks\ValueObjectInterface;

class Message implements ValueObjectInterface
{
	private string $text;

	public function __construct(string $translationKeyOrPlainText)
	{
		if (strlen($translationKeyOrPlainText) > 1024)
		{
			throw new InvalidPayload('Message must be less or equal to 1024 characters');
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