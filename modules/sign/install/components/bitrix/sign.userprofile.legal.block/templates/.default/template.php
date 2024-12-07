<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */

/** @var $APPLICATION */

\Bitrix\Main\UI\Extension::load(['ui.button']);

$uri = new \Bitrix\Main\Web\Uri('/bitrix/components/bitrix/sign.legal.form/slider.php');
$uri->addParams([
	'profileId' => $arParams['PROFILE_ID'],
]);

function canEdit (): bool
{
	$currentUserId = \Bitrix\Main\Engine\CurrentUser::get()->getId();

	return
		$currentUserId !== null
		&& (
			\Bitrix\Sign\Config\LegalInfo::canEdit($currentUserId)
			|| \Bitrix\Sign\Config\LegalInfo::canAdd($currentUserId)
		)
	;
}

$textButton = canEdit()
	? Loc::getMessage('SIGN_USER_PROFILE_LEGAL_BUTTON_TEXT_EDIT')
	: Loc::getMessage('SIGN_USER_PROFILE_LEGAL_BUTTON_TEXT_VIEW')
;
?>

<div class="sign-user-profile-about sign-user-profile-about-profile">
	<div class="sign-user-profile-post-edit-stub-default"><?=Loc::getMessage('SIGN_USER_PROFILE_LEGAL_LABEL')?></div>

	<div id="sign-legal-info-button-container"></div>
</div>


<script>
	(function() {
		const button = new BX.UI.Button({
			text: "<?= $textButton ?>",
			color: BX.UI.Button.Color.LIGHT_BORDER,
			size: BX.UI.Button.Size.SMALL,
			round: true,
			onclick: function(button, event) {
				top.BX.SidePanel.Instance.open('<?= CUtil::JSescape($uri) ?>', {cacheable: false, customLeftBoundary: 0, width: 800});
			}
		});
		const buttonContainer = document.getElementById('sign-legal-info-button-container');
		button.renderTo(buttonContainer);
	})();
</script>
