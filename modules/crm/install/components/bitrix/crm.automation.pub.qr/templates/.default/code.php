<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

/** @var array $arResult */

$imgSrc = 'data:image/png;base64,' . base64_encode($arResult['codeContent']);

\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

?>

<div class="crm-automation-pub-qr__wrap crm-automation-pub-qr crm-automation-pub-qr--scope">
	<div class="crm-automation-pub-qr__popup">
		<h1 class="crm-automation-pub-qr__popup_title"><?= Loc::getMessage('CRM_AUTOMATION_QR_CODE_TITLE') ?></h1>
		<div class="crm-automation-pub-qr__icon_box">
			<img class="crm-automation-pub-qr__icon" src="<?= $imgSrc ?>" width="142" height="142">
		</div>

		<div style="text-align: center;">
			<a
				class="ui-btn ui-btn-light-border ui-btn-round"
				href="<?= $imgSrc ?>" download="QR.png"
			><?= Loc::getMessage('CRM_AUTOMATION_QR_DOWNLOAD') ?>
			</a>
		</div>
	</div>
</div>
