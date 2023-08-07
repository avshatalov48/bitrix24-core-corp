<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @global \CMain $APPLICATION
 */

use Bitrix\Main\Localization\Loc;

$arResult['BACKURL'] = $APPLICATION->GetCurPageParam('', [
	'login',
	'login_form',
	'logout',
	'register',
	'forgot_password',
	'change_password',
	'confirm_registration',
	'confirm_code',
	'confirm_user_id',
	'logout_butt',
]);
?>
<div class="authorization-block">
	<a href="<?= SITE_DIR . 'auth/?backurl=' . $arResult['BACKURL'] ?>" class="authorization-text">
		<?= Loc::getMessage('AUTH_AUTH') ?>
	</a>
</div>
