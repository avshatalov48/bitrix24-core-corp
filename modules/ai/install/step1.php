<?php
/** @global CMain $APPLICATION */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;

if (!check_bitrix_sessid())
{
	return;
}
$zone = Application::getInstance()->getLicense()->getRegion() ?? 'en';
$mapByZone = [
	'ru' => 'https://www.bitrix24.ru/about/terms-of-use-ai.php',
	'kz' => 'https://www.bitrix24.kz/about/terms-of-use-ai.php',
	'by' => 'https://www.bitrix24.by/about/terms-of-use-ai.php',
	'en' => 'https://www.bitrix24.com/terms/bitrix24copilot-rules.php',
];
$termsLink = $mapByZone[$zone] ?? $mapByZone['en'];

$messageLink = [
	"<a target=\"_blank\" href=\"{$termsLink}\">",
	'</a>',
];

?>
<form action="<?= $APPLICATION->GetCurPage() ?>" name="form1">
	<div style="width: 700px">
	<?php
		\CAdminMessage::ShowMessage([
			'TYPE'    => 'PROGRESS',
			'DETAILS' => Loc::getMessage('AI_INSTALL_COPILOT_AGREEMENT_NOTIFY', [
				'#LINK#' => $messageLink[0],
				'#/LINK#' => $messageLink[1],
			]),
			'MESSAGE' => Loc::getMessage('AI_INSTALL_COPILOT_AGREEMENT_NOTIFY_TITLE'),
			'HTML'    => true,
	]);
	?>
	</div>
	<?= bitrix_sessid_post() ?>
	<input type="hidden" name="lang" value="<?= LANG ?>">
	<input type="hidden" name="id" value="ai">
	<input type="hidden" name="install" value="Y">
	<input type="hidden" name="step" value="2">
	<input class="adm-btn-green" type="submit" name="inst" value="<?= GetMessage('MOD_INSTALL') ?>">
</form>
