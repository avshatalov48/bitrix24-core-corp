<?php

namespace Bitrix\Crm\ItemMiniCard\Contract;

use Bitrix\Crm\ItemMiniCard\Layout\Avatar\AbstractAvatar;

interface Provider
{
	public function provideId(): string;

	public function provideTitle(): string;

	public function provideAvatar(): AbstractAvatar;

	public function provideControls(): array;

	public function provideFields(): array;

	public function provideFooterNotes(): array;
}
