<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Infrastructure\Update\Menu;

use Bitrix\Intranet\Integration\Templates;

class SocialPresetMenuReverter extends BaseSocialPresetMenuConverter
{
	protected function processGlobal(Templates\Air\MenuConverter $menuConverter): void
	{
		$menuConverter->revert();
	}

	protected function processForUser(Templates\Air\MenuConverter $menuConverter, int $userId): void
	{
		$menuConverter->revertForUser($userId);
	}
}
