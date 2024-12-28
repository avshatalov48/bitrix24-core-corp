<?php

namespace Bitrix\Crm\Integration\Im\Message;

use Bitrix\Main\Localization\Loc;

abstract class Message implements MessageInterface
{
	protected ?string $link = null;
	private ?string $lang = null;

	protected function __construct(
		private readonly int $fromUser,
		private readonly int $toUser
	)
	{
	}

	final public function getUserFrom(): int
	{
		return $this->fromUser;
	}

	final public function getUserTo(): int
	{
		return $this->toUser;
	}

	public function setLang(?string $lang): self
	{
		$this->lang = $lang;

		return $this;
	}

	public function getLink(): ?string
	{
		return $this->link;
	}

	public function getHelpId(): ?int
	{
		return null;
	}

	protected function getLocalizedFallbackMessage(string $id, array $replace = null, ?string $lang = null): ?string
	{
		$lang = $lang ?? $this->lang;
		
		return Loc::getMessage($id, $replace, $lang);
	}
}
