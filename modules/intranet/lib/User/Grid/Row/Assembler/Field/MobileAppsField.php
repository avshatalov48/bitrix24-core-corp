<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field;

use Bitrix\Intranet\Util;
use Bitrix\Main\Localization\Loc;

class MobileAppsField extends CustomUserFieldAssembler
{
	protected function prepareColumn($value): mixed
	{
		$apps = Util::getAppsInstallationConfig($value['ID']);

		$mobileApps = [];

		if ($apps['APP_IOS_INSTALLED'])
		{
			$mobileApps[] = Loc::getMessage('INTRANET_USER_LIST_MOBILE_APPS_FIELD_IOS');
		}

		if ($apps['APP_ANDROID_INSTALLED'])
		{
			$mobileApps[] = Loc::getMessage('INTRANET_USER_LIST_MOBILE_APPS_FIELD_ANDROID');
		}

		return !empty($mobileApps)
			? implode(', ', $mobileApps)
			: '<span class="ui-color-light">' . Loc::getMessage('INTRANET_USER_LIST_MOBILE_APPS_FIELD_NOT_INSTALLED') . '</span>';
	}
}