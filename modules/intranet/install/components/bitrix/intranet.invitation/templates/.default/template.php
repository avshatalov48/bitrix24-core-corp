<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */

/** @global CMain $APPLICATION */

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\Socialnetwork\Helper\Feature;

Extension::load([
	"ui.forms",
	"ui.buttons",
	"ui.buttons.icons",
	"ui.alerts",
	"ui.selector",
	"ui.hint",
	'ui.entity-selector',
	'ui.feedback.form',
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.switcher',
	'ui.analytics',
	'ui.icon-set.main',
	'ui.entity-selector',
	'intranet.selector-button',
	'intranet.invitation-input',
	'intranet.department-control',
	'ui.notification',
]);

CJSCore::Init(['phone_number']);

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . 'no-background invite-body');

\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();
$menuContainerId = 'invitation-form-menu-' . $this->randString();
$contentContainerId = 'invitation-form-content-' . $this->randString();

$projectLimitFeatureId = Feature::PROJECTS_GROUPS;
$isProjectLimitExceeded = !Feature::isFeatureEnabled($projectLimitFeatureId);
if (Feature::canTurnOnTrial($projectLimitFeatureId))
{
	$isProjectLimitExceeded = false;
}

$APPLICATION->IncludeComponent(
	'bitrix:ui.feedback.form',
	'',
	[
		'ID' => 'intranet-invitation',
		'VIEW_TARGET' => null,
		'FORMS' => [
			['zones' => ['com.br'], 'id' => '259', 'lang' => 'br', 'sec' => 'wfjn1i'],
			['zones' => ['es'], 'id' => '257', 'lang' => 'la', 'sec' => 'csaico'],
			['zones' => ['de'], 'id' => '255', 'lang' => 'de', 'sec' => 'nxzhg1'],
			['zones' => ['ua'], 'id' => '251', 'lang' => 'ua', 'sec' => '3y1j08'],
			['zones' => ['ru', 'kz', 'by'], 'id' => '261', 'lang' => 'ru', 'sec' => 'sieyyr'],
			['zones' => ['en'], 'id' => '253', 'lang' => 'en', 'sec' => 'wg6548'],
		],
		'air' => true,
		'USE_UI_TOOLBAR' => 'Y',
	]
);

$APPLICATION->IncludeComponent("bitrix:ui.sidepanel.wrappermenu", "", array(
	"ID" => $menuContainerId,
	"ITEMS" => $arResult["MENU_ITEMS"],
	"TITLE" => Loc::getMessage("INTRANET_INVITE_DIALOG_TITLE")
));
$this->SetViewTarget('left-panel');

?>
<div class="invitation-department__menu-divider"></div>
<ul id="invitation-department__sub-menu" class="ui-sidepanel-menu --menu-light-gray">
	<?php foreach ($arResult["SUB_MENU_ITEMS"] as $item): ?>
		<li class="ui-sidepanel-menu-item
	<? if (isset($item['SHOW_LOCKED']) && $item['SHOW_LOCKED'] === true): ?>--lock<? endif; ?>">
			<a
			<?php foreach ($item["ATTRIBUTES"] as $attrName => $attrValue): ?>
				<?= $attrName ?>="<?= $attrValue ?>"
			<?php endforeach; ?>
			bx-operative="Y"
			class="ui-sidepanel-menu-link">
			<div class="ui-sidepanel-menu-link-text">
				<?= $item['NAME'] ?>
				<?php if (isset($item['SHOW_LOCKED']) && $item['SHOW_LOCKED'] === true): ?>
					<span class="ui-icon-set --lock"></span>
				<?php endif; ?>
			</div>
			</a>
		</li>

	<?php
	endforeach;
	?>
</ul>
<?php
$this->EndViewTarget();
if ($arResult["IS_CLOUD"])
{
	$APPLICATION->AddViewContent("left-panel", '');
}

if ($arResult["IS_CLOUD"] && $arResult['canCurrentUserInvite'])
{
	$isMaxUsersUnlimited = ($arResult["USER_MAX_COUNT"] == 0);

	$APPLICATION->AddViewContent(
		"left-panel-after", '
		<div class="invite-limit-counters-container">
			<div class="invite-limit-counters-row">
				<div class="invite-limit-counters-block-name">' . Loc::getMessage("INTRANET_INVITE_DIALOG_USER_MAX_COUNT") . '</div>
				<div class="' . ($isMaxUsersUnlimited
			? 'invite-limit-counters-block-value-unlimited' : 'invite-limit-counters-block-value-current') . '">'
		. ($isMaxUsersUnlimited ? Loc::getMessage("INTRANET_INVITE_DIALOG_UNLIMITED") : $arResult["USER_MAX_COUNT"])
		. '</div>
			</div>
			<div class="invite-limit-counters-row">
				<div class="invite-limit-counters-block-name">' . Loc::getMessage("INTRANET_INVITE_DIALOG_USER_CURRENT_COUNT") . '</div>
				<div class="invite-limit-counters-block-value-overflow">' . $arResult["USER_CURRENT_COUNT"] . '</div>
			</div>
		</div>
	'
	);
}
?>

<div data-id="<?= $contentContainerId ?>" class="popup-window-tabs-box">
	<div class="ui-alert ui-alert-danger" data-role="error-message" style="display: none;">

	</div>
	<div class="ui-alert ui-alert-success" data-role="success-message" style="display: none;">

	</div>

	<div class="popup-window-tabs-content popup-window-tabs-content-invite">
	</div>
</div>

<?php
$APPLICATION->IncludeComponent("bitrix:ui.button.panel", "", array(
	"BUTTONS" => [
		[
			'ID' => 'intranet-invitation-btn',
			'TYPE' => 'save',
			"CAPTION" => $arResult["IS_CLOUD"] ? Loc::getMessage("BX24_INVITE_DIALOG_ACTION_SAVE")
				: Loc::getMessage("BX24_INVITE_DIALOG_ACTION_INVITE"),
			'ONCLICK' => '',
		],
		[
			'TYPE' => 'close',
			'ONCLICK' => "BX.SidePanel.Instance.close();"
		]
	]
));
?>

<?php $this->SetViewTarget("below_page", 10); ?>
<div class="invite-wrap-decal-arrow">
	<svg width="79" height="74" xmlns="http://www.w3.org/2000/svg">
		<g stroke="#2FC6F6" stroke-width="2" fill="none" fill-rule="evenodd" opacity=".73" stroke-linecap="round"
		   stroke-linejoin="round">
			<path d="M71.747 72.41C59.827 32.816 36.576 9.119 1.992 1.32M76.4 62.11l-4.512 10.558-9.862-5.279"/>
		</g>
	</svg>
</div>
<div class="invite-wrap-decal" id="invite-wrap-decal">
	<div class="invite-wrap-decal-image"><?= Loc::getMessage("INTRANET_INVITE_DIALOG_PICTURE_TITLE") ?></div>
</div>
<?php $this->EndViewTarget(); ?>

<script>
	BX.message(<?=CUtil::phpToJsObject(Loc::loadLanguageFile(__FILE__))?>);
	BX.message({
		BX24_INVITE_DIALOG_USERS_LIMIT_TEXT: '<?=GetMessageJS('BX24_INVITE_DIALOG_USERS_LIMIT_TEXT', array(
			'#NUM#' => Application::getInstance()->getLicense()->getMaxUsers()))?>',
		INTRANET_INVITE_DIALOG_EMAIL_VALIDATE_ERROR: '<?=GetMessageJS('INTRANET_INVITE_DIALOG_VALIDATE_ERROR_EMAIL') ?>',
		INTRANET_INVITE_DIALOG_PHONE_VALIDATE_ERROR: '<?=GetMessageJS('INTRANET_INVITE_DIALOG_VALIDATE_ERROR_PHONE' )?>',
		INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_VALIDATE_ERROR: '<?=GetMessageJS('INTRANET_INVITE_DIALOG_VALIDATE_ERROR_EMAIL_AND_PHONE')?>',

		INTRANET_INVITE_DIALOG_EMAIL_EMPTY_ERROR: '<?=GetMessageJS('INTRANET_INVITE_DIALOG_EMPTY_ERROR_EMAIL')?>',
		INTRANET_INVITE_DIALOG_PHONE_EMPTY_ERROR: '<?=GetMessageJS('INTRANET_INVITE_DIALOG_EMPTY_ERROR_PHONE')?>',
		INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_EMPTY_ERROR: '<?=GetMessageJS('INTRANET_INVITE_DIALOG_EMPTY_ERROR_EMAIL_AND_PHONE')?>',

		INTRANET_INVITE_DIALOG_EMAIL_INPUT: '<?=GetMessageJS('INTRANET_INVITE_DIALOG_INPUT_EMAIL')?>',
		INTRANET_INVITE_DIALOG_PHONE_INPUT: '<?=GetMessageJS('INTRANET_INVITE_DIALOG_INPUT_PHONE')?>',
		INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_INPUT: '<?=GetMessageJS('INTRANET_INVITE_DIALOG_INPUT_EMAIL_AND_PHONE')?>',
	});

	BX.ready(function () {
		window.invitationForm = new BX.Intranet.Invitation.Form({
			signedParameters: '<?=$this->getComponent()->getSignedParameters()?>',
			componentName: '<?=$this->getComponent()->getName() ?>',
			userOptions: <?=CUtil::phpToJsObject($arParams['USER_OPTIONS'])?>,
			isCloud: '<?=$arResult['IS_CLOUD'] ? 'Y' : 'N'?>',
			isAdmin: '<?=$arResult['IS_CURRENT_USER_ADMIN'] ? 'Y' : 'N'?>',
			menuContainerNode: document.querySelector('#<?=$menuContainerId?>'),
			subMenuContainerNode: document.querySelector('#invitation-department__sub-menu'),
			contentContainerNode: document.querySelector('[data-id="<?=$contentContainerId?>"]'),
			isExtranetInstalled: '<?=$arResult['IS_EXTRANET_INSTALLED'] ? 'Y' : 'N'?>',
			regenerateUrlBase: '<?=$arResult['REGISTER_URL_BASE'] ?? ''?>',
			isInvitationBySmsAvailable: '<?=$arResult['IS_SMS_INVITATION_AVAILABLE'] ? 'Y' : 'N'?>',
			isCreatorEmailConfirmed: '<?=$arResult['IS_CREATOR_EMAIL_CONFIRMED'] ? 'Y' : 'N'?>',
			firstInvitationBlock: '<?=$arResult['FIRST_INVITATION_BLOCK']?>',
			isSelfRegisterEnabled: <?= CUtil::phpToJsObject(isset($arResult['REGISTER_SETTINGS']['REGISTER']) && $arResult['REGISTER_SETTINGS']['REGISTER'] === 'Y') ?>,
			analyticsLabel: <?= CUtil::phpToJsObject(
				Application::getInstance()->getContext()->getRequest()->get('analyticsLabel')
			) ?>,
			projectLimitExceeded: <?= Json::encode($isProjectLimitExceeded); ?>,
			projectLimitFeatureId: '<?= $projectLimitFeatureId ?>',
			wishlistValue: '<?= CUtil::JSEscape($arResult['REGISTER_SETTINGS']['REGISTER_WHITELIST'])?>',
			registerConfirm: <?= (isset($arResult['REGISTER_SETTINGS']['REGISTER_CONFIRM']) && $arResult['REGISTER_SETTINGS']['REGISTER_CONFIRM'] === 'Y' ? 'true' : 'false') ?>,
			isCollabEnabled: '<?= $arResult['IS_COLLAB_ENABLED'] ? 'Y' : 'N' ?>',
			canCurrentUserInvite: <?= $arResult['canCurrentUserInvite'] ? 'true' : 'false' ?>,
			useLocalEmailProgram: <?= $arResult['USE_INVITE_LOCAL_EMAIL_PROGRAM'] ? 'true' : 'false' ?>,
		});

		var imageMail = document.getElementById('invite-wrap-decal');
		var leftPanel = document.getElementById('left-panel');

		function adjustImageShow()
		{
			if (window.innerHeight - leftPanel.offsetHeight <= 240)
			{
				imageMail.style.display = 'none';
			}

			if (window.innerHeight - leftPanel.offsetHeight > 240)
			{
				imageMail.style.display = null;
			}
		}

		adjustImageShow();
		BX.bind(window, 'resize', BX.throttle(adjustImageShow, 100, this));
	});
</script>
