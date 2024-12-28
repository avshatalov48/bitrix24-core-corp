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

\Bitrix\Main\UI\Extension::load([
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
]);

\CJSCore::Init(['phone_number']);

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-background invite-body');

$menuContainerId = 'invitation-form-menu-'.$this->randString();
$contentContainerId = 'invitation-form-content-'.$this->randString();

$projectLimitFeatureId = \Bitrix\Socialnetwork\Helper\Feature::PROJECTS_GROUPS;
$isProjectLimitExceeded = !\Bitrix\Socialnetwork\Helper\Feature::isFeatureEnabled($projectLimitFeatureId);
if (\Bitrix\Socialnetwork\Helper\Feature::canTurnOnTrial($projectLimitFeatureId))
{
	$isProjectLimitExceeded = false;
}

$APPLICATION->IncludeComponent(
	'bitrix:ui.feedback.form',
	'',
	[
		'ID' => 'intranet-invitation',
		'VIEW_TARGET' => 'inside_pagetitle',
		'FORMS' => [
			['zones' => ['com.br'], 'id' => '259','lang' => 'br', 'sec' => 'wfjn1i'],
			['zones' => ['es'], 'id' => '257','lang' => 'la', 'sec' => 'csaico'],
			['zones' => ['de'], 'id' => '255','lang' => 'de', 'sec' => 'nxzhg1'],
			['zones' => ['ua'], 'id' => '251','lang' => 'ua', 'sec' => '3y1j08'],
			['zones' => ['ru', 'kz', 'by'], 'id' => '261','lang' => 'ru', 'sec' => 'sieyyr'],
			['zones' => ['en'], 'id' => '253','lang' => 'en', 'sec' => 'wg6548'],
		],
	]
);

$APPLICATION->IncludeComponent("bitrix:ui.sidepanel.wrappermenu", "", array(
	"ID" => $menuContainerId,
	"ITEMS" => $arResult["MENU_ITEMS"],
	"TITLE" => Loc::getMessage("INTRANET_INVITE_DIALOG_TITLE")
));

if ($arResult["IS_CLOUD"])
{
	$APPLICATION->AddViewContent("left-panel", '');
}

if ($arResult["IS_CLOUD"] && $arResult['canCurrentUserInvite'])
{
	$isMaxUsersUnlimited = ($arResult["USER_MAX_COUNT"] == 0);

	$APPLICATION->AddViewContent("left-panel-after", '
		<div class="invite-limit-counters-container">
			<div class="invite-limit-counters-row">
				<div class="invite-limit-counters-block-name">'.Loc::getMessage("INTRANET_INVITE_DIALOG_USER_MAX_COUNT").'</div>
				<div class="'.($isMaxUsersUnlimited
					? 'invite-limit-counters-block-value-unlimited' : 'invite-limit-counters-block-value-current').'">'
					.($isMaxUsersUnlimited ? Loc::getMessage("INTRANET_INVITE_DIALOG_UNLIMITED") : $arResult["USER_MAX_COUNT"])
				.'</div>
			</div>
			<div class="invite-limit-counters-row">
				<div class="invite-limit-counters-block-name">'.Loc::getMessage("INTRANET_INVITE_DIALOG_USER_CURRENT_COUNT").'</div>
				<div class="invite-limit-counters-block-value-overflow">'.$arResult["USER_CURRENT_COUNT"].'</div>
			</div>
		</div>
	');
}
?>

<div data-id="<?=$contentContainerId?>" class="popup-window-tabs-box">
	<div class="ui-alert ui-alert-danger" data-role="error-message" style="display: none;">
		<span class="ui-alert-message"></span>
	</div>
	<div class="ui-alert ui-alert-success" data-role="success-message" style="display: none;">
		<span class="ui-alert-message"></span>
	</div>

	<div class="popup-window-tabs-content popup-window-tabs-content-invite">
		<?php
		//fast registration
		if ($arResult["IS_CLOUD"] && $arResult['canCurrentUserInvite'])
		{
			$isSelfRegisterEnable = $arResult["REGISTER_SETTINGS"]["REGISTER"] === "Y";
			?>
			<div class="invite-wrap js-intranet-invitation-block" data-role="self-block">
				<div class="invite-title-container">
					<div class="invite-title-icon invite-title-icon-link"></div>
					<div class="invite-title-text"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_FAST_REG_TITLE")?></div>
					<div class="invite-title-helper" onclick="top.BX.Helper.show('redirect=detail&code=6546149');"></div>
				</div>
				<form method="POST" name="SELF_DIALOG_FORM">
					<label class="invite-dialog-fast-reg-control-container js-invite-dialog-fast-reg-control-container <?=($isSelfRegisterEnable ? "" : "disallow-registration")?>" for="allow_register">
						<span class="invite-dialog-fast-reg-control-label">
							<?=Loc::getMessage("BX24_INVITE_DIALOG_REGISTER_ALLOW_N")?>
						</span>
						<div class="invite-dialog-fast-reg-control-switcher" data-role="self-switcher"></div>
					</label>

					<div class="invite-content-container">
						<div class="invite-form-container">
							<div style="border-top: none; <?= (!$isSelfRegisterEnable ? 'display: none;' : '') ?>" data-role="selfSettingsBlock" id="intranet-dialog-tab-content-self-block" class="invite-dialog-inv-link-block">
								<div>
									<div class="invite-form-container-reg-row" style="margin-bottom: 10px;">
										<div class="invite-form-container-reg-col">
											<span class="invite-form-ctl-title">
												<?=Loc::getMessage("BX24_INVITE_DIALOG_REGISTER_LINK")?>
											</span>
											<label class="ui-ctl ui-ctl-w100 ui-ctl-textbox">
												<input
													type="text"
													class="ui-ctl-element"
													value="<?=\Bitrix\Main\Text\Converter::getHtmlConverter()->encode($arResult["REGISTER_URL"])?>"
													id="allow_register_url"
													data-role="allowRegisterUrl"
													readonly="readonly"
												/>
											</label>
										</div>
										<span class="ui-btn ui-btn-light-border" data-role="copyRegisterUrlButton">
											<?=Loc::getMessage("BX24_INVITE_DIALOG_COPY_LINK")?>
										</span>
									</div>
									<div>
										<a
											href="javascript:void(0)"
											data-role="selfRegenerateSecretButton"
											class="invite-dialog-update-link"
										>
											<?=Loc::getMessage("BX24_INVITE_DIALOG_REGISTER_NEW_LINK")?>
										</a>
									</div>
									<div style="padding-top: 18px;">
										<label class="ui-ctl ui-ctl-w100 ui-ctl-checkbox">
											<input
												type="checkbox"
												class="ui-ctl-element"
												name="allow_register_confirm"
												id="allow_register_confirm"
												data-role="allowRegisterConfirm"
												<?= ($arResult["REGISTER_SETTINGS"]["REGISTER_CONFIRM"] === "Y" ? 'checked' : '') ?>
												<?= (!$arResult["IS_CURRENT_USER_ADMIN"] ? 'disabled' : '') ?>
											/>
											<div class="ui-ctl-label-text"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_FAST_REG_TYPE")?></div>
										</label>
									</div>
									<?php
									$style = ($arResult["REGISTER_SETTINGS"]["REGISTER_CONFIRM"] === "N" ? 'style="display: none"' : '');
									?>
									<div id="intranet-dialog-tab-content-self-whitelist" data-role="selfWhiteList" <?= $style ?>>
										<span class="invite-form-ctl-title">
											<?=Loc::getMessage("INTRANET_INVITE_DIALOG_FAST_REG_DOMAINS")?>
										</span>
										<label class="ui-ctl ui-ctl-w75 ui-ctl-textbox">
											<input
												type="text"
												<?= (!$arResult["IS_CURRENT_USER_ADMIN"] ? 'disabled' : '') ?>
												class="ui-ctl-element"
												name="allow_register_whitelist"
												value="<?= $arResult["REGISTER_SETTINGS"]["REGISTER_WHITELIST"]?>"
												placeholder="<?=Loc::getMessage("BX24_INVITE_DIALOG_REGISTER_TYPE_DOMAINS_PLACEHOLDER")?>"
											/>
										</label>
									</div>
									<input
										type="hidden"
										id="allow_register_secret"
										data-role="allowRegisterSecret"
										name="allow_register_secret"
										value="<?=htmlspecialcharsbx($arResult["REGISTER_SETTINGS"]["REGISTER_SECRET"])?>"
									/>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
			<?php
		}
		?>

		<!-- invite by email-->
		<?php if ($arResult['canCurrentUserInvite']): ?>
		<div class="invite-wrap js-intranet-invitation-block" data-role="invite-block">
			<div class="invite-title-container">
				<div class="invite-title-icon invite-title-icon-message"></div>
				<div class="invite-title-text">
					<?=Loc::getMessage("INTRANET_INVITE_DIALOG_TITLE_".
						($arResult["IS_SMS_INVITATION_AVAILABLE"] ? "EMAIL_AND_PHONE" : "EMAIL"))?>
				</div>
			</div>
			<div class="invite-content-container">
				<form method="POST" name="INVITE_DIALOG_FORM" class="invite-form-container">
					<div data-role="rows-container"></div>
					<div class="invite-form-buttons">
						<span class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-icon-add ui-btn-round"
							  data-role="invite-more"
						>
							<?=Loc::getMessage("INTRANET_INVITE_DIALOG_ADD_MORE")?>
						</span>
						<span style="padding: 0 10px;"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_OR")?></span>
						<a href="javascript:void(0)"
						   class="ui-link ui-link-primary ui-link-dotted"
						   data-role="invite-mass"
						>
							<?=Loc::getMessage("INTRANET_INVITE_DIALOG_ADD_MASSIVE")?>
						</a>
					</div>
				</form>
			</div>
		</div>

		<!-- mass invite by email-->
		<div class="invite-wrap js-intranet-invitation-block" data-role="mass-invite-block">
			<div class="invite-title-container">
				<div class="invite-title-icon invite-title-icon-mass"></div>
				<div class="invite-title-text"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_MASS_TITLE")?></div>
				<?php
				if ($arResult["IS_SMS_INVITATION_AVAILABLE"])
				{
					?>
					<div class="invite-title-helper"
						 data-hint="<?=Loc::getMessage("INTRANET_INVITE_DIALOG_MASS_INVITE_HINT")?>"
						 data-hint-no-icon
					>
					</div>
					<?php
				}
				?>
			</div>
			<div class="invite-content-container">
				<div class="invite-form-container">
					<form method="POST" name="MASS_INVITE_DIALOG_FORM" class="invite-form-container">
						<div class="ui-ctl-label-text">
							<?=Loc::getMessage("INTRANET_INVITE_DIALOG_MASS_LABEL_".
								($arResult["IS_SMS_INVITATION_AVAILABLE"] ? "EMAIL_AND_PHONE" : "EMAIL"))?>
						</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textarea ui-ctl-lg">
							<textarea name="mass_invite_emails" class="ui-ctl-element"></textarea>
						</div>
						<div class="invite-form-ctl-description">
							<?=Loc::getMessage("INTRANET_INVITE_DIALOG_MASS_DESC_".
								($arResult["IS_SMS_INVITATION_AVAILABLE"] ? "EMAIL_AND_PHONE" : "EMAIL"))?>
						</div>
					</form>
				</div>
			</div>
		</div>

		<!-- invite by email with group or structure-->
		<div class="invite-wrap js-intranet-invitation-block" data-role="invite-with-group-dp-block">
			<div class="invite-title-container">
				<div class="invite-title-icon invite-title-icon-message"></div>
				<div class="invite-title-text">
					<?=Loc::getMessage("INTRANET_INVITE_DIALOG_GROUP_OR_DEPARTMENT_TITLE")?>
				</div>
			</div>
			<div class="invite-content-container">
				<form method="POST" name="INVITE_WITH_GROUP_DP_DIALOG_FORM" class="invite-form-container">
					<div class="invite-form-row">
						<div class="invite-form-col">
							<div class="ui-ctl-label-text"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_GROUP_OR_DEPARTMENT_INPUT")?></div>
							<div data-role="entity-selector-container"></div>
						</div>
					</div>
					<div data-role="rows-container"></div>
					<div class="invite-form-buttons">
						<span class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-icon-add ui-btn-round" data-role="invite-more">
							<?=Loc::getMessage("INTRANET_INVITE_DIALOG_ADD_MORE")?>
						</span>
					</div>
				</form>
			</div>
		</div>

		<!-- add by email-->
		<div class="invite-wrap js-intranet-invitation-block" data-role="add-block">
			<form method="POST" name="ADD_DIALOG_FORM">
				<div class="invite-title-container">
					<div class="invite-title-icon invite-title-icon-registration"></div>
					<div class="invite-title-text"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_ADD_TITLE")?></div>
				</div>
				<div class="invite-content-container">
					<div class="invite-form-container">
						<div data-role="rows-container"></div>
						<div class="invite-form-row">
							<div class="invite-form-col">
								<div class="ui-ctl-label-text"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_GROUP_OR_DEPARTMENT_INPUT")?></div>
								<div data-role="entity-selector-container"></div>
							</div>
						</div>

						<div class="invite-form-row">
							<div class="invite-form-col">
								<div class="invite-dialog-inv-form-checkbox-wrap"><?php
									?>
									<input
										type="checkbox"
										name="ADD_SEND_PASSWORD"
										id="ADD_SEND_PASSWORD"
										value="Y"
										class="invite-dialog-inv-form-checkbox"
										<?= (isset($_POST["ADD_SEND_PASSWORD"]) && $_POST["ADD_SEND_PASSWORD"] === "Y" ? " checked" : "") ?>
									>
									<label class="invite-dialog-inv-form-checkbox-label" for="ADD_SEND_PASSWORD">
										<?=Loc::getMessage($arResult["IS_CLOUD"] ? "BX24_INVITE_DIALOG_ADD_WO_CONFIRMATION_TITLE" : "BX24_INVITE_DIALOG_ADD_SEND_PASSWORD_TITLE")?><?
										if (!$arResult["IS_CLOUD"])
										{
											?><span id="ADD_SEND_PASSWORD_EMAIL"></span><?php
										}
										?></label><?php
									?></div>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
		<?php endif; ?>

		<!-- extranet -->
		<?php
		if ($arResult["IS_EXTRANET_INSTALLED"])
		{
		?>
		<div class="invite-wrap js-intranet-invitation-block" data-role="extranet-block">
			<div class="invite-title-container">
				<div class="invite-title-icon invite-title-icon-extranet"></div>
				<div class="invite-title-text"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_EXTRANET_TITLE")?></div>
			</div>

			<div class="invite-content-container">
				<form method="POST" name="EXTRANET_DIALOG_FORM" class="invite-form-container">
					<div class="invite-form-row" style="margin-bottom: 15px;">
						<div class="invite-form-col">
							<div class="ui-ctl-label-text"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_EXTRANET_GROUP")?></div>
							<div data-role="entity-selector-container"></div>
						</div>
					</div>
					<div data-role="rows-container"></div>
					<div class="invite-form-buttons">
						<span class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-icon-add ui-btn-round" data-role="invite-more">
							<?=Loc::getMessage("INTRANET_INVITE_DIALOG_ADD_MORE")?>
						</span>
					</div>
				</form>
			</div>
		</div>
		<?php
		}
		?>

		<?php
		// integrator
		if ($arResult["IS_CLOUD"] && $arResult['canCurrentUserInvite'])
		{
		?>
			<div class="invite-wrap js-intranet-invitation-block" data-role="integrator-block">
				<form method="POST" name="INTEGRATOR_DIALOG_FORM">
					<div class="invite-title-container">
						<div class="invite-title-icon invite-title-icon-mass"></div>
						<div class="invite-title-text"><?=Loc::getMessage("BX24_INVITE_DIALOG_TAB_INTEGRATOR_TITLE")?></div>
						<div class="invite-title-helper" onclick="top.BX.Helper.show('redirect=detail&code=7725333');"></div>
					</div>
					<div class="invite-content-container">
						<div class="invite-form-container">
							<div data-role="rows-container"></div>
						</div>
					</div>
				</form>
			</div>
		<?php
		}
		?>

		<!-- Active Directory -->
		<?php if ($arResult['canCurrentUserInvite']): ?>
		<div class="invite-wrap js-intranet-invitation-block" data-role="active-directory-block">
			<div class="invite-title-container">
				<div class="invite-title-icon invite-title-icon-activedirectory"></div>
				<div class="invite-title-text"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_ACTIVE_DIRECTORY_TITLE")?></div>
			</div>
			<div class="invite-content-container">
				<div class="invite-form-container">
					<div class="invite-content">
						<div class="invite-content-title"><strong><?=Loc::getMessage("INTRANET_INVITE_DIALOG_ACTIVE_DIRECTORY_DESC")?>:</strong></div>
						<div>
							<ui class="invite-content-list">
								<li><?=Loc::getMessage("INTRANET_INVITE_DIALOG_ACTIVE_DIRECTORY_DESC1")?></li>
								<li><?=Loc::getMessage("INTRANET_INVITE_DIALOG_ACTIVE_DIRECTORY_DESC2")?></li>
								<li><?=Loc::getMessage("INTRANET_INVITE_DIALOG_ACTIVE_DIRECTORY_DESC3")?></li>
							</ui>
						</div>
						<div>
							<?=Loc::getMessage("INTRANET_INVITE_DIALOG_ACTIVE_DIRECTORY_DESC4")?>
							<br>
							<br>
							<a href="" class="ui-link ui-link-dashed"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_ACTIVE_DIRECTORY_MORE")?></a>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<div class="invite-wrap js-intranet-invitation-block" data-role="success-block"
			 style="position: fixed; left: 0; right: 0; top: 0; bottom: 0; background: #fff; z-index: 90;"
		>
			<div style="height: 78vh;" class="invite-send-success-wrap">
				<div class="invite-send-success-text"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_SUCCESS_SEND")?></div>
				<div class="invite-send-success-decal-1"></div>
				<div class="invite-send-success-decal-2"></div>
				<div class="invite-send-success-decal-3"></div>
				<div class="invite-send-success-decal-4"></div>
				<div class="invite-send-success-decal-5"></div>
			</div>
		</div>
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
		<g stroke="#2FC6F6" stroke-width="2" fill="none" fill-rule="evenodd" opacity=".73" stroke-linecap="round" stroke-linejoin="round">
			<path d="M71.747 72.41C59.827 32.816 36.576 9.119 1.992 1.32M76.4 62.11l-4.512 10.558-9.862-5.279"/>
		</g>
	</svg>
</div>
<div class="invite-wrap-decal" id="invite-wrap-decal">
	<div class="invite-wrap-decal-image"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_PICTURE_TITLE")?></div>
</div>
<?php $this->EndViewTarget(); ?>

<script type="text/javascript">
	BX.message(<?=CUtil::phpToJsObject(Loc::loadLanguageFile(__FILE__))?>);
	BX.message({
		BX24_INVITE_DIALOG_USERS_LIMIT_TEXT: "<?=GetMessageJS("BX24_INVITE_DIALOG_USERS_LIMIT_TEXT", array(
			"#NUM#" => Application::getInstance()->getLicense()->getMaxUsers()))?>",
		INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_VALIDATE_ERROR: "<?=Loc::getMessage("INTRANET_INVITE_DIALOG_VALIDATE_ERROR_".($arResult["IS_SMS_INVITATION_AVAILABLE"] ? "EMAIL_AND_PHONE" : "EMAIL"))?>",
		INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_EMPTY_ERROR: "<?=Loc::getMessage("INTRANET_INVITE_DIALOG_EMPTY_ERROR_".($arResult["IS_SMS_INVITATION_AVAILABLE"] ? "EMAIL_AND_PHONE" : "EMAIL"))?>",
		INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_INPUT: "<?=Loc::getMessage("INTRANET_INVITE_DIALOG_INPUT_".($arResult["IS_SMS_INVITATION_AVAILABLE"] ? "EMAIL_AND_PHONE" : "EMAIL"))?>"
	});

	BX.ready(function() {
		new BX.Intranet.Invitation.Form({
			signedParameters: '<?=$this->getComponent()->getSignedParameters()?>',
			componentName: '<?=$this->getComponent()->getName() ?>',
			userOptions: <?=CUtil::phpToJsObject($arParams['USER_OPTIONS'])?>,
			isCloud: '<?=$arResult["IS_CLOUD"] ? "Y" : "N"?>',
			isAdmin: '<?=$arResult["IS_CURRENT_USER_ADMIN"] ? "Y" : "N"?>',
			menuContainerNode: document.querySelector('#<?=$menuContainerId?>'),
			contentContainerNode: document.querySelector('[data-id="<?=$contentContainerId?>"]'),
			contentNodes: BX.findChildren(BX('intranet-dialog-tabs'), {className: 'popup-window-tab-content'}, true),
			isExtranetInstalled: '<?=$arResult["IS_EXTRANET_INSTALLED"] ? "Y" : "N"?>',
			regenerateUrlBase: '<?=$arResult["REGISTER_URL_BASE"] ?? ''?>',
			isInvitationBySmsAvailable: '<?=$arResult["IS_SMS_INVITATION_AVAILABLE"] ? "Y" : "N"?>',
			isCreatorEmailConfirmed: '<?=$arResult["IS_CREATOR_EMAIL_CONFIRMED"] ? "Y" : "N"?>',
			firstInvitationBlock: '<?=$arResult['FIRST_INVITATION_BLOCK']?>',
			isSelfRegisterEnabled: <?= CUtil::phpToJsObject(isset($arResult["REGISTER_SETTINGS"]["REGISTER"]) && $arResult["REGISTER_SETTINGS"]["REGISTER"] === "Y") ?>,
			analyticsLabel: <?= CUtil::phpToJsObject(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('analyticsLabel')) ?>,
			projectLimitExceeded: <?= \Bitrix\Main\Web\Json::encode($isProjectLimitExceeded); ?>,
			projectLimitFeatureId: '<?= $projectLimitFeatureId ?>',
			isCollabEnabled: '<?= $arResult["IS_COLLAB_ENABLED"] ? "Y" : "N" ?>',
		});

		var imageMail = document.getElementById("invite-wrap-decal");
		var leftPanel = document.getElementById("left-panel");

		function adjustImageShow()
		{
			if(window.innerHeight - leftPanel.offsetHeight <= 240)
			{
				imageMail.style.display = "none";
			}

			if(window.innerHeight - leftPanel.offsetHeight> 240)
			{
				imageMail.style.display = null;
			}
		}

		adjustImageShow();
		BX.bind(window, "resize", BX.throttle(adjustImageShow, 100, this));
	});
</script>
