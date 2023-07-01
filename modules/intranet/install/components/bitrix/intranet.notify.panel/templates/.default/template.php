<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web;

$this->setFrameMode(true);

if (isset($arResult['notifyManager']['isAvailable']) && $arResult['notifyManager']['isAvailable']):
?><script>
	BX.ready(() => {
		BX.message(<?= Web\Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
		BX.Intranet.NotifyManager = new BX.Intranet.NotifyManager(<?= \CUtil::PhpToJSObject($arResult['notifyManager']) ?>);<?php

		if (isset($arResult['notifications']['panel']['isAvailable']) && $arResult['notifications']['panel']['isAvailable']):
			?>BX.Intranet.NotifyManager.getNotifyPanel(<?= \CUtil::PhpToJSObject($arResult['notifications']['panel']) ?>).show();<?php
		endif;

		if (isset($arResult['notifications']['license-popup']['isAvailable']) && $arResult['notifications']['license-popup']['isAvailable']):
			?>BX.Intranet.NotifyManager.getLicenseNotificationPopup(<?= \CUtil::PhpToJSObject($arResult['notifications']['license-popup']) ?>).show();<?php
		endif;
	?>})
</script><?php
endif;