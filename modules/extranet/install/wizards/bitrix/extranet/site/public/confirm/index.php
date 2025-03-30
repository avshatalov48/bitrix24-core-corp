<?php

use Bitrix\Extranet\PortalSettings;

define('CONFIRM_PAGE', true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/extranet/public/confirm/index.php');

$APPLICATION->SetTitle(GetMessage("EXTRANET_CONFIRM_PAGE_TITLE"));

$collaberService = \Bitrix\Extranet\Service\ServiceContainer::getInstance()->getCollaberService();
$userId = (int)\Bitrix\Main\Context::getCurrent()->getRequest()->get('user_id');

?>
<?php
if (
	$userId > 0
	&& $collaberService->isCollaberById($userId)
	&& !PortalSettings::getInstance()->isEnabledCollabersInvitation()
):
	?>
	<div class="login-text"><?= GetMessage("EXTRANET_CONFIRM_DISABLED_COLLABERS_INVITATION") ?></div>
<?php else: ?>
<?php
$APPLICATION->IncludeComponent(
	"bitrix:system.auth.initialize",
	"",
	[
		'CHECKWORD_VARNAME' => 'checkword',
		'USERID_VARNAME' => 'user_id',
		'AUTH_URL' => SITE_DIR . 'auth.php',
	],
	false
);
?>
<?php endif; ?>
<?php

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
