<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;

class SettingsPermission
{
	const READ = 1 << 0;
	const EDIT = 1 << 2;
	const NO_ACCESS = 0;

	public function __construct(
		private int $permissionBitwise
	)
	{
	}

	public function canRead(): bool
	{
		return $this->permissionBitwise & static::READ;
	}

	public function canEdit(): bool
	{
		return $this->permissionBitwise & static::EDIT;
	}

	public function getPermission(): int
	{
		return $this->permissionBitwise;
	}

	static public function initByUser(CurrentUser $user): static
	{
		if (Loader::includeModule("intranet") && Loader::includeModule("bitrix24"))
		{
			if ($user->CanDoOperation('bitrix24_config'))
			{
				return new static(static::READ | static::EDIT);
			}
			else
			{
				return new static(static::READ);
			}
		}
		else
		{
			if ($user->isAdmin())
			{
				return new static(static::READ | static::EDIT);
			}
			else
			{
				return new static(static::READ);
			}
		}
	}

	static public function initForPage(CurrentUser $user, string $pageType): static
	{
		$notAdminRight = $pageType === ToolsSettings::TYPE ? static::READ : static::NO_ACCESS;

		if (Loader::includeModule("intranet") && Loader::includeModule("bitrix24"))
		{
			if ($user->CanDoOperation('bitrix24_config'))
			{
				return new static(static::READ | static::EDIT);
			}
			else
			{
				return new static($notAdminRight);
			}
		}
		else
		{
			if ($user->isAdmin())
			{
				return new static(static::READ | static::EDIT);
			}
			else
			{
				return new static($notAdminRight);
			}
		}
	}
}