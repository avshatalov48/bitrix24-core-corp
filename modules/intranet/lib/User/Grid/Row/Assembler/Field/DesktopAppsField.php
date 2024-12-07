<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field;

use Bitrix\Intranet\Util;
use Bitrix\Main\Localization\Loc;

class DesktopAppsField extends CustomUserFieldAssembler
{
	protected function prepareColumn($value): mixed
	{
		$apps = Util::getAppsInstallationConfig($value['ID']);

		$desktopApps = [];

		if ($apps['APP_WINDOWS_INSTALLED'])
		{
			$desktopApps[] = Loc::getMessage('INTRANET_USER_LIST_DESKTOP_APPS_FIELD_WINDOWS');
		}

		if ($apps['APP_MAC_INSTALLED'])
		{
			$desktopApps[] = Loc::getMessage('INTRANET_USER_LIST_DESKTOP_APPS_FIELD_MACOS');
		}

		if ($apps['APP_LINUX_INSTALLED'])
		{
			$desktopApps[] = Loc::getMessage('INTRANET_USER_LIST_DESKTOP_APPS_FIELD_LINUX');
		}

		return !empty($desktopApps)
			? implode(', ', $desktopApps)
			: '<span class="ui-color-light">' . Loc::getMessage('INTRANET_USER_LIST_DESKTOP_APPS_FIELD_NOT_INSTALLED') . '</span>';;
	}
}