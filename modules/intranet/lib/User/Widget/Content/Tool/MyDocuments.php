<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content\Tool;

use Bitrix\Intranet;
use Bitrix\Intranet\User;
use Bitrix\Main\Localization\Loc;

class MyDocuments extends BaseTool
{
	public static function isAvailable(User $user): bool
	{
		return self::isSignDocumentAvailable();
	}

	public function getConfiguration(): array
	{
		$isAvailable = self::isSignDocumentAvailable();

		if (!$isAvailable)
		{
			return [
				'isAvailable' => false,
			];
		}

		return [
			'isAvailable' => true,
			'isLocked' => $this->isSignDocumentLocked(),
			'title' => Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_MY_DOCUMENTS_TITLE'),
			'counter' => self::getCount(),
			'counterEventName' => self::getCounterEventName(),
		];
	}

	public function getName(): string
	{
		return 'myDocuments';
	}

	public static function getCount(): int
	{
		return Intranet\Internal\Integration\Sign\Documents::getCount();
	}

	public static function getCounterEventName(): string
	{
		return Intranet\Internal\Integration\Sign\Documents::getPullCounterEventName();
	}

	public static function isSignDocumentAvailable(): bool
	{
		return Intranet\Internal\Integration\Sign\Documents::isAvailable();
	}

	private function isSignDocumentLocked(): bool
	{
		return Intranet\Internal\Integration\Sign\Documents::isLocked();
	}
}
