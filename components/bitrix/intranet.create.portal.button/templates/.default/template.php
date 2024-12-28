<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $APPLICATION;
$APPLICATION->SetPageProperty('HeaderClass', 'intranet-header--with-controls');
$this->setFrameMode(true);

?>

<a
	class="ui-btn intranet-button-create-portal ui-btn-sm ui-btn-primary ui-btn-no-caps ui-btn-round"
	href="<?= \CUtil::JSEscape($arResult['CREATE_URL']) ?>"
	target="_blank"
	id="intranet-button-create-portal"
>
	<span class="ui-btn-text"><?= \CUtil::JSEscape(Loc::getMessage('INTRANET_CREATE_PORTAL_BUTTON_TITLE')) ?></span>
</a>