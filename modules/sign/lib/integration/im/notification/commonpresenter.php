<?php

namespace Bitrix\Sign\Integration\Im\Notification;

use Bitrix\Main\Localization\Loc;

class CommonPresenter
{
	public function getModuleName(): ?string
	{
		return Loc::getMessage('SIGN_INTEGRATION_IM_NOTIFICATION_COMMON_PRESENTER_MODULE_NAME');
	}
}