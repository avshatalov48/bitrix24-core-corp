<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Infrastructure\Update\Menu;

use Bitrix\Intranet\Integration\Templates;

class SocialPresetMenuConverter extends BaseSocialPresetMenuConverter
{
	protected function processGlobal(Templates\Air\MenuConverter $menuConverter): void
	{
		$menuConverter->convert();
	}

	protected function processForUser(Templates\Air\MenuConverter $menuConverter, int $userId): void
	{
		$menuConverter->convertForUser($userId);
	}
}
