<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web;

$this->setFrameMode(true);

if (isset($arResult['config']['notify']) && !empty($arResult['config']['notify']))
{
	Extension::load(['intranet.license-notify', 'ui.banner-dispatcher']);
	?>
	<script>
		BX.ready(() => {
			BX.message(<?= Web\Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
			const manager = new BX.Intranet.LicenseNotify(<?= \CUtil::PhpToJSObject($arResult['config']) ?>);
			manager.getProvider().show();
		});
	</script>
	<?php
}
