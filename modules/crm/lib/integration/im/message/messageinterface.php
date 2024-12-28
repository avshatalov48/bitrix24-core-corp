<?php

namespace Bitrix\Crm\Integration\Im\Message;

interface MessageInterface
{
	public function getUserFrom(): int;
	public function getUserTo(): int;
	public function getTypeId(): string;
	public function getLink(): ?string;
	public function getHelpId(): ?int;
	public function getFallbackText(): string;
}
