<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load(["ui.forms", "ui.buttons", "ui.buttons.icons", "ui.alerts", "ui.selector", "ui.hint"]);
\CJSCore::Init(['phone_number']);

$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'invite-body');

$menuContainerId = 'invitation-form-menu-'.$this->randString();
$contentContainerId = 'invitation-form-content-'.$this->randString();

if (!function_exists('drawInviteDialogSelector'))
{
	function drawInviteDialogSelector($params)
	{
		global $APPLICATION;

		$action = $params['action'];
		$isExtranet = $params["isExtranet"];

		if ($isExtranet)
		{
			$options = [
				'lazyLoad' => 'Y',
				'context' => 'USER_INVITE',
				'contextCode' => 'SG',
				'enableUsers' => 'N',
				'enableSonetgroups' => 'Y',
				'socNetGroupsSiteId' => $params['extranetSiteId'],
				'enableDepartments' => 'N',
				'allowAddSocNetGroup' => 'Y',
			];
		}
		else
		{
			$options = [
				'lazyLoad' => 'Y',
				'context' => 'USER_INVITE',
				'contextCode' => 'SG',
				'enableUsers' => 'N',
				'enableSonetgroups' => 'Y',
				'socNetGroupsSiteId' => "",
				'enableDepartments' => 'Y',
				'allowAddSocNetGroup' => 'Y',
				'departmentSelectDisable' => 'N',
				'departmentFlatEnable' => "Y"
			];
		}

		$selectorName = $action.'_'.randString(6);

		$APPLICATION->IncludeComponent(
			"bitrix:main.user.selector",
			"",
			[
				"ID" => $selectorName,
				"LAZYLOAD" => 'Y',
				"INPUT_NAME" => 'GROUP_AND_DEPARTMENT[]',
				"USE_SYMBOLIC_ID" => true,
				"BUTTON_SELECT_CAPTION" => Loc::getMessage('BX24_INVITE_DIALOG_ACTION_ADD'),
				"BUTTON_SELECT_CAPTION_MORE" => Loc::getMessage('BX24_INVITE_DIALOG_DEST_LINK_2'),
				'API_VERSION' => '3',
				"SELECTOR_OPTIONS" => $options
			]
		);
	}
}

$APPLICATION->IncludeComponent(
	'bitrix:ui.feedback.form',
	'',
	[
		'ID' => 'intranet-invitation',
		'VIEW_TARGET' => 'pagetitle',
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
));
?>
<div data-id="<?=$contentContainerId?>" class="popup-window-tabs-box">
	<div class="ui-alert ui-alert-danger" data-role="error-message" style="display: none;">
		<span class="ui-alert-message"></span>
	</div>
	<div class="ui-alert ui-alert-success" data-role="success-message" style="display: none;">
		<span class="ui-alert-message"></span>
	</div>

	<div class="popup-window-tabs-content popup-window-tabs-content-invite">
		<?//fast registration
		if ($arResult["IS_CLOUD"])
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
						<input
							type="checkbox"
							name="allow_register"
							data-role="selfToggleSettingsButton"
							id="allow_register"
							value="Y"
							<?if ($isSelfRegisterEnable) echo "checked"?>
							style="display: none;"
						/>
						<div class="invite-dialog-fast-reg-control-switcher" data-role="self-switcher">
							<span class="invite-dialog-fast-reg-control-switcher-btn"></span>
							<span class="invite-dialog-fast-reg-control-switcher-on">
								<?=ToUpper(Loc::getMessage("INTRANET_INVITE_DIALOG_REG_ON"))?>
							</span>
							<span class="invite-dialog-fast-reg-control-switcher-off">
								<?=ToUpper(Loc::getMessage("INTRANET_INVITE_DIALOG_REG_OFF"))?>
							</span>
						</div>
					</label>

					<div class="invite-content-container">
						<div class="invite-form-container">
							<div style="border-top: none; <?if (!$isSelfRegisterEnable):?>display: none;<?endif?>" data-role="selfSettingsBlock" id="intranet-dialog-tab-content-self-block" class="invite-dialog-inv-link-block">
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
												<?if ($arResult["REGISTER_SETTINGS"]["REGISTER_CONFIRM"] == "Y") echo "checked"?>
												onchange="BX('intranet-dialog-tab-content-self-whitelist').style.display = this.checked ? 'block' : 'none'"
												<?if (!$arResult["IS_CURRENT_USER_ADMIN"]) echo "disabled";?>
											/>
											<div class="ui-ctl-label-text"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_FAST_REG_TYPE")?></div>
										</label>
									</div>

									<div id="intranet-dialog-tab-content-self-whitelist" <?if ($arResult["REGISTER_SETTINGS"]["REGISTER_CONFIRM"] == "N"):?>style="display: none" <?endif?>>
										<span class="invite-form-ctl-title">
											<?=Loc::getMessage("INTRANET_INVITE_DIALOG_FAST_REG_DOMAINS")?>
										</span>
										<label class="ui-ctl ui-ctl-w75 ui-ctl-textbox">
											<input
												type="text"
												<?if (!$arResult["IS_CURRENT_USER_ADMIN"]) echo "disabled";?>
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
		<?
		}
		?>

		<!-- invite by email-->
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
				<?if ($arResult["IS_SMS_INVITATION_AVAILABLE"]):?>
				<div class="invite-title-helper"
					 data-hint="<?=Loc::getMessage("INTRANET_INVITE_DIALOG_MASS_INVITE_HINT")?>"
					 data-hint-no-icon
				>
				</div>
				<?endif?>
			</div>
			<div class="invite-content-container">
				<div class="invite-form-container">
					<form method="POST" name="MASS_INVITE_DIALOG_FORM" class="invite-form-container">
						<div class="ui-ctl-label-text"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_MASS_LABEL")?></div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textarea ui-ctl-lg">
							<textarea name="mass_invite_emails" class="ui-ctl-element"></textarea>
						</div>
						<div class="invite-form-ctl-description"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_MASS_DESC")?></div>
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
							<?
							drawInviteDialogSelector(array(
								 'action' => 'inviteWithGroupDp',
								 'isExtranet' => false
							));
							?>
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
								<?
								drawInviteDialogSelector(array(
									 'action' => 'add',
									 'isExtranet' => false
								));
								?>
							</div>
						</div>

						<div class="invite-form-row">
							<div class="invite-form-col">
								<div class="invite-dialog-inv-form-checkbox-wrap"><?
									?>
									<input
										type="checkbox"
										name="ADD_SEND_PASSWORD"
										id="ADD_SEND_PASSWORD"
										value="Y"
										class="invite-dialog-inv-form-checkbox"
										<?=($_POST["ADD_SEND_PASSWORD"] == "Y" ? " checked" : "")?>
									>
									<label class="invite-dialog-inv-form-checkbox-label" for="ADD_SEND_PASSWORD">
										<?=Loc::getMessage($arResult["IS_CLOUD"] ? "BX24_INVITE_DIALOG_ADD_WO_CONFIRMATION_TITLE" : "BX24_INVITE_DIALOG_ADD_SEND_PASSWORD_TITLE")?><?
										if (!$arResult["IS_CLOUD"])
										{
											?><span id="ADD_SEND_PASSWORD_EMAIL"></span><?
										}
										?></label><?
									?></div>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>

		<!-- extranet -->
		<?
		if ($arResult["IS_EXTRANET_INSTALLED"])
		{
		?>
		<div class="invite-wrap js-intranet-invitation-block" data-role="extranet-block">
			<div class="invite-title-container">
				<div class="invite-title-icon invite-title-icon-message"></div>
				<div class="invite-title-text"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_EXTRANET_TITLE")?></div>
			</div>

			<div class="invite-content-container">
				<form method="POST" name="EXTRANET_DIALOG_FORM" class="invite-form-container">
					<div class="invite-form-row" style="margin-bottom: 15px;">
						<div class="invite-form-col">
							<div class="ui-ctl-label-text"><?=Loc::getMessage("INTRANET_INVITE_DIALOG_EXTRANET_GROUP")?></div>
							<?
							drawInviteDialogSelector(array(
								'action' => 'extranet',
								'extranetSiteId' => $arResult["EXTRANET_SITE_ID"],
								'isExtranet' => true
							));
							?>
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
		<?
		}
		?>

		<? // integrator
		if ($arResult["IS_CLOUD"])
		{
		?>
			<div class="invite-wrap js-intranet-invitation-block" data-role="integrator-block">
				<form method="POST" name="INTEGRATOR_DIALOG_FORM">
					<div class="invite-title-container">
						<div class="invite-title-icon invite-title-icon-mass"></div>
						<div class="invite-title-text"><?=Loc::getMessage("BX24_INVITE_DIALOG_TAB_INTEGRATOR_TITLE")?></div>
						<div class="invite-title-helper" onclick="top.BX.Helper.show('redirect=detail&code=6546149');"></div>
					</div>
					<div class="invite-content-container">
						<div class="invite-form-container">
							<div data-role="rows-container"></div>
						</div>
					</div>
				</form>
			</div>
		<?
		}
		?>

		<!-- Active Directory -->
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

		<div class="invite-wrap js-intranet-invitation-block" data-role="success-block">
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
<?
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

<?/*$this->SetViewTarget("below_page", 10);?>
<div style="position: absolute;bottom: 75px;right: 50%;margin-right: 39px;">
	<svg width="382px" height="204px" viewBox="0 0 382 204" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
		<defs>
			<filter x="-8.6%" y="-14.8%" width="117.1%" height="129.5%" filterUnits="objectBoundingBox" id="filter-5">
				<feOffset dx="0" dy="3" in="SourceAlpha" result="shadowOffsetOuter1" />
				<feColorMatrix values="0 0 0 0 0   0 0 0 0 0   0 0 0 0 0  0 0 0 0.09 0" type="matrix" in="shadowOffsetOuter1" result="shadowMatrixOuter1" />
				<feMerge>
					<feMergeNode in="shadowMatrixOuter1" />
					<feMergeNode in="SourceGraphic" />
				</feMerge>
			</filter>
		</defs>
		<g  stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
			<path d="M112.654315,21 C149.105393,21 178.654573,50.5486641 178.654573,87.0002578 C178.654573,123.45082 149.105393,153 112.654315,153 C76.2032368,153 46.6545727,123.45082 46.6545727,87.0002578 C46.6545727,50.5486641 76.2037524,21 112.654315,21 Z" fill="#2FC6F6" fill-rule="nonzero" opacity="0.113560268" />
			<g transform="translate(15, 45)">
				<g transform="translate(61.193103, 10.782918)" fill-rule="nonzero">
					<polygon fill="#38BBE5" points="75.0280308 21.9826949 0 21.9826949 37.5140154 0"/>
					<polygon fill="#1DB2E1" points="0 21.6903915 75.0280308 21.6903915 75.0280308 65.6565476 0 65.6565476"/>
				</g>
				<polygon fill="#fff" fill-rule="nonzero" points="117.344828 0.142348754 127.924138 10.7829181 127.924138 76.6725979 68.5172414 76.6725979 68.5172414 0.142734902"/>
				<g transform="translate(75.841379, 12.829181)" fill="#A8ADB4" opacity="0.3">
					<rect x="0" y="0" width="11.3931034" height="12.6868327" rx="2"/>
					<path d="M15.2621549,0.409252669 L41.7033624,0.409252669 C42.0423984,0.409252669 42.3172414,0.684095661 42.3172414,1.02313167 C42.3172414,1.36216768 42.0423984,1.63701068 41.7033624,1.63701068 L15.2621549,1.63701068 C14.9231189,1.63701068 14.6482759,1.36216768 14.6482759,1.02313167 C14.6482759,0.684095661 14.9231189,0.409252669 15.2621549,0.409252669 Z"/>
					<path d="M15.2621549,3.68327402 L41.7033624,3.68327402 C42.0423984,3.68327402 42.3172414,3.95811701 42.3172414,4.29715302 C42.3172414,4.63618904 42.0423984,4.91103203 41.7033624,4.91103203 L15.2621549,4.91103203 C14.9231189,4.91103203 14.6482759,4.63618904 14.6482759,4.29715302 C14.6482759,3.95811701 14.9231189,3.68327402 15.2621549,3.68327402 Z"/>
					<path d="M0.613879004,16.7793594 L41.7033624,16.7793594 C42.0423984,16.7793594 42.3172414,17.0542024 42.3172414,17.3932384 C42.3172414,17.7322744 42.0423984,18.0071174 41.7033624,18.0071174 L0.613879004,18.0071174 C0.274842992,18.0071174 4.15199367e-17,17.7322744 0,17.3932384 C-4.15199367e-17,17.0542024 0.274842992,16.7793594 0.613879004,16.7793594 Z"/>
					<path d="M15.2621549,6.95729537 L41.7033624,6.95729537 C42.0423984,6.95729537 42.3172414,7.23213837 42.3172414,7.57117438 C42.3172414,7.91021039 42.0423984,8.18505338 41.7033624,8.18505338 L15.2621549,8.18505338 C14.9231189,8.18505338 14.6482759,7.91021039 14.6482759,7.57117438 C14.6482759,7.23213837 14.9231189,6.95729537 15.2621549,6.95729537 Z"/>
					<path d="M0.613879004,20.0533808 L41.7033624,20.0533808 C42.0423984,20.0533808 42.3172414,20.3282238 42.3172414,20.6672598 C42.3172414,21.0062958 42.0423984,21.2811388 41.7033624,21.2811388 L0.613879004,21.2811388 C0.274842992,21.2811388 4.15199367e-17,21.0062958 0,20.6672598 C-4.15199367e-17,20.3282238 0.274842992,20.0533808 0.613879004,20.0533808 Z"/>
					<path d="M15.2621549,10.2313167 L26.64819,10.2313167 C26.987226,10.2313167 27.262069,10.5061597 27.262069,10.8451957 C27.262069,11.1842317 26.987226,11.4590747 26.64819,11.4590747 L15.2621549,11.4590747 C14.9231189,11.4590747 14.6482759,11.1842317 14.6482759,10.8451957 C14.6482759,10.5061597 14.9231189,10.2313167 15.2621549,10.2313167 Z"/>
					<path d="M0.613879004,23.3274021 L41.7033624,23.3274021 C42.0423984,23.3274021 42.3172414,23.6022451 42.3172414,23.9412811 C42.3172414,24.2803172 42.0423984,24.5551601 41.7033624,24.5551601 L0.613879004,24.5551601 C0.274842992,24.5551601 4.15199367e-17,24.2803172 0,23.9412811 C-4.15199367e-17,23.6022451 0.274842992,23.3274021 0.613879004,23.3274021 Z"/>
					<path d="M0.613879004,26.6014235 L18.1033624,26.6014235 C18.4423984,26.6014235 18.7172414,26.8762665 18.7172414,27.2153025 C18.7172414,27.5543385 18.4423984,27.8291815 18.1033624,27.8291815 L0.613879004,27.8291815 C0.274842992,27.8291815 4.15199367e-17,27.5543385 0,27.2153025 C-4.15199367e-17,26.8762665 0.274842992,26.6014235 0.613879004,26.6014235 Z"/>
				</g>
				<text font-family="OpenSans-Light, Open Sans" font-size="20" font-weight="300" fill="#525C69">
					<tspan x="3.51367188" y="134">Invite text! :)</tspan>
				</text>
				<polygon fill="#E0E3E9" fill-rule="nonzero" points="117.344828 0.142348754 117.344828 10.7829181 127.924138 10.7829181"/>
				<g filter="url(#filter-5)" transform="translate(61.193103, 33.291815)" fill-rule="nonzero">
					<polygon fill="#67D9FE" points="0 0 37.5140154 21.747724 0 43.4962063"/>
					<polygon fill="#67D9FE" points="75.3553947 0 37.8413793 21.747724 75.3553947 43.4962063"/>
					<polygon fill="#2FC6F6" points="37.637931 14.3238434 0 43.3807829 75.2758621 43.3807829"/>
				</g>
				<g transform="translate(29, 7.914591)">
					<path d="M67.9586207,0.149466192 C76.7950432,0.149466192 83.9586207,7.31278191 83.9586207,16.1494662 C83.9586207,24.9861505 76.7947815,32.1494662 67.9586207,32.1494662 C59.1216747,32.1494662 51.9586207,24.985627 51.9586207,16.1494662 C51.9586207,7.31252016 59.1221982,0.149466192 67.9586207,0.149466192 Z" fill="#9DCF00" fill-rule="nonzero"/>
					<path d="M70,8.08540925 L70,14.0854093 L76,14.0854093 L76,18.0854093 L70,18.0854093 L70,24.0854093 L66,24.0854093 L66,18.0844093 L60,18.0854093 L60,14.0854093 L66,14.0844093 L66,8.08540925 L70,8.08540925 Z" fill="#FFF"/>
					<path d="M19,13 L44,13 C45.1045695,13 46,13.8954305 46,15 C46,16.1045695 45.1045695,17 44,17 L19,17 C17.8954305,17 17,16.1045695 17,15 C17,13.8954305 17.8954305,13 19,13 Z" fill="#55D0E0" opacity="0.426"/>
				</g>
			</g>
			<g opacity="0.73" transform="translate(218, 105)" stroke="#2FC6F6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
				<path d="M157.401367,95.4106445 C128.701497,40.6785482 76.4322917,9.12760417 0.59375,0.7578125" id="Path-4" />
				<polyline id="Path-5" transform="translate(154.187500, 91.278809) rotate(-11) translate(-154.187500, -91.278809)" points="161.375 86 156.862305 96.5576172 147 91.2788086" />
			</g>
		</g>
	</svg>
</div>
<?$this->EndViewTarget();*/?>

<script type="text/javascript">
	BX.message(<?=CUtil::phpToJsObject(Loc::loadLanguageFile(__FILE__))?>);
	BX.message({
		BX24_INVITE_DIALOG_USERS_LIMIT_TEXT: "<?=GetMessageJS("BX24_INVITE_DIALOG_USERS_LIMIT_TEXT", array(
			"#NUM#" => COption::GetOptionString("main", "PARAM_MAX_USERS")))?>",
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
			menuContainerNode: document.querySelector('#<?=$menuContainerId?>'),
			contentContainerNode: document.querySelector('[data-id="<?=$contentContainerId?>"]'),
			contentNodes: BX.findChildren(BX('intranet-dialog-tabs'), {className: 'popup-window-tab-content'}, true),
			isExtranetInstalled: '<?=$arResult["IS_EXTRANET_INSTALLED"] ? "Y" : "N"?>',
			regenerateUrlBase: '<?=$arResult["REGISTER_URL_BASE"]?>',
			isInvitationBySmsAvailable: '<?=$arResult["IS_SMS_INVITATION_AVAILABLE"] ? "Y" : "N"?>'
		});
	});
</script>