<?php

namespace Bitrix\Sign\Integration\Im\Notification;

use Bitrix\Main\Localization\Loc;

class RuRegionPresenter extends CommonPresenter
{
	public function getModuleName(): ?string
	{
		return Loc::getMessage('SIGN_INTEGRATION_IM_NOTIFICATION_RU_PRESENTER_MODULE_NAME');
	}
}