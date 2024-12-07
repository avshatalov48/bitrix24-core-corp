<?php

namespace Bitrix\Sign\Contract\Chat;

use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Member;

interface Message extends \Bitrix\Sign\Contract\Item
{
	public function getUserFrom(): int;
	public function getUserTo(): int;
	public function getStageId(): string;
	public function getFallbackText(): string;
	public function getHelpId(): ?int;
	public function getDocument(): ?Document;
	public function getMember(): ?Member;
	public function getLink(): ?string;
}
