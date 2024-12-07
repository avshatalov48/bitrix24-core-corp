<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;

/** @var CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");

Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'clipboard',
	'promise',
	'sidepanel',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.hint',
	'ui.progressbar',
	'ui.icons',
	'popup',
	'ui.forms',
	'crm.tracking.connector',
	'ui.sidepanel-content',
]);

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/crm.analytics.channel.phone/templates/.default/style.css');

$containerId = 'crm-tracking-site';
?>

<div id="<?=htmlspecialcharsbx($containerId)?>" class="crm-tracking-site-wrap">

	<?
		if ($arResult['ROW']['ID']):
			$this->SetViewTarget('pagetitle');
	?>
			<div id="crm-tracking-site-remove" class="ui-btn ui-btn-light-border ui-btn-icon-remove">
				<?=Loc::getMessage('CRM_TRACKING_SITE_BTN_REMOVE')?>
			</div>
	<?
			$this->EndViewTarget();
		endif;
	?>
	<?
	$APPLICATION->IncludeComponent(
		'bitrix:ui.feedback.form',
		'',
		\Bitrix\Crm\Tracking\Provider::getFeedbackParameters()
	);
	?>

	<form method="post" id="crm-tracking-site-form">
		<?=bitrix_sessid_post();?>
		<input id="crm-tracking-site-remove-input" type="hidden" name="remove" value="N">
		<div class="ui-slider-section">
			<div class="crm-tracking-site-desc">
				<span class="crm-tracking-site-desc-text">
					<?=Loc::getMessage('CRM_TRACKING_SITE_DESC')?>
				</span>
			</div>
			<div id="DISCONNECTED" style="<?=($arResult['ROW']['IS_INSTALLED'] != 'Y' ? '' : 'display: none;')?>"
				class="crm-tracking-site-field"
			>
				<div class="crm-tracking-site-field-inner">
					<span class="crm-analytics-channel-field-title">
						<?=Loc::getMessage('CRM_TRACKING_SITE_NAME_INPUT')?>
					</span>
					<input type="url" placeholder="https://example.com"
						name="ADDRESS" id="ADDRESS"
						value="<?=htmlspecialcharsbx($arResult['ROW']['ADDRESS'])?>"
						class="crm-analytics-channel-field-input" title="<?=Loc::getMessage('CRM_TRACKING_SITE_NAME')?>"
					>
				</div>
				<div class="crm-tracking-site-field-inner">
					<a id="site-btn-connect" class="ui-btn ui-btn-light-border">
						<?=Loc::getMessage('CRM_TRACKING_SITE_CONNECT')?>
					</a>
				</div>
			</div>
			<div id="CONNECTED" style="<?=($arResult['ROW']['IS_INSTALLED'] == 'Y' ? '' : 'display: none;')?>"
				class="crm-tracking-site-field crm-tracking-site-field-connect"
			>
				<div class="crm-tracking-site-field-inner">
					<a id="SITE_NAME" class="crm-tracking-site-field-link">
						<?=htmlspecialcharsbx($arResult['ROW']['HOST'])?>
					</a>
				</div>
				<div class="crm-tracking-site-field-inner">
					<a id="site-btn-check" class="ui-btn ui-btn-light-border">
						<?=Loc::getMessage('CRM_TRACKING_SITE_CHECK')?>
					</a>
					<a id="site-btn-disconnect" class="ui-btn ui-btn-light-border">
						<?=Loc::getMessage('CRM_TRACKING_SITE_DISCONNECT')?>
					</a>
				</div>
			</div>
			<div class="crm-tracking-site-hidden crm-tracking-site-hidden-closed" id="crm-tracking-site-hidden">
				<div class="crm-tracking-site-header">
					<div class="crm-tracking-site-header-title" id="crm-tracking-site-code-header-title">
						<div class="crm-tracking-site-header-text"><?=Loc::getMessage('CRM_TRACKING_SITE_SCRIPT')?></div>
					</div>
					<div class="crm-tracking-site-code-status">
						<span class="crm-tracking-site-code-status-text"><?=Loc::getMessage('CRM_TRACKING_SITE_SCRIPT_STATUS')?>:</span>
						<span id="site-status" class="crm-tracking-site-code-status-value">
							<?=Loc::getMessage('CRM_TRACKING_SITE_SCRIPT_STATUS_NONE')?>
						</span>
					</div>
				</div>

				<input type="hidden"
					id="IS_INSTALLED" name="IS_INSTALLED"
					value="<?=($arResult['ROW']['IS_INSTALLED'] == 'Y' ? 'Y' : 'N')?>"
				>
				<div id="site-status-bar" style="display: none; margin: 10px 0 0 0;"></div>

				<div class="crm-tracking-site-hidden-inner">
					<div class="crm-tracking-site-desc-text" style="margin: 10px 0;">
						<?=Loc::getMessage('CRM_TRACKING_SITE_SCRIPT_DESC', [
							'%tag_body%' => htmlspecialcharsbx('</body>')
						])?>
					</div>
					<div class="crm-tracking-site-hidden-value">
						<div class="crm-tracking-site-hidden-value-inner">
							<textarea id="script-text"
								class="crm-analytics-script-textarea"
								readonly
							><?=htmlspecialcharsbx($arResult['SCRIPT_LOADER']);?></textarea>
						</div>
						<span id="script-copy-btn" class="ui-btn ui-btn-light-border">
							<?=Loc::getMessage('CRM_TRACKING_SITE_BTN_COPY')?>
						</span>
					</div>
				</div>
			</div>
		</div>
		<div id="result-container" style="display: none;"
			class="crm-tracking-site-block crm-tracking-site-block-replace"
		>
			<div class="crm-tracking-site-subject"><?=Loc::getMessage('CRM_TRACKING_SITE_SUB_TITLE')?></div>
			<div class="crm-tracking-site-desc">
				<span class="crm-tracking-site-desc-text">
					<?=Loc::getMessage('CRM_TRACKING_SITE_SUB_DESC')?>
				</span>
			</div>
			<div class="crm-tracking-site-hidden">
				<div class="crm-tracking-site-header">
					<div class="crm-tracking-site-header-title" style="width: 100%;">
						<div style="float: left;">
							<?=Loc::getMessage('CRM_TRACKING_SITE_FOUND')?>:
							<span class="crm-tracking-site-header-result"
								data-text="<?=Loc::getMessage('CRM_TRACKING_SITE_FOUND_ITEMS')?>"
							>
							</span>
							<span class="crm-tracking-site-header-decs">
								<?=Loc::getMessage('CRM_TRACKING_SITE_FOUND_ITEMS_DESC')?>
							</span>
						</div>
						<span id="site-btn-edit" class="ui-btn ui-btn-primary" style="float: right;">
							<?=Loc::getMessage('CRM_TRACKING_SITE_BTN_VIEW')?>
						</span>
					</div>
				</div>
				<div class="crm-tracking-site-hidden-inner">
					<div class="crm-tracking-site-hidden-value">
						<div class="crm-analytics-channel-phone-inner">
							<div class="crm-analytics-channel-field-title">
								<?=Loc::getMessage('CRM_TRACKING_SITE_PHONES')?>:
							</div>
							<div class="crm-analytics-channel-phone-inner-field">
								<?
								$GLOBALS['APPLICATION']->includeComponent(
									'bitrix:ui.tile.selector',
									'',
									array(
										'ID' => 'available-phone-list',
										'INPUT_NAME' => 'PHONES',
										'MULTIPLE' => true,
										'LIST' => $arResult['PHONES'],
										'CAN_REMOVE_TILES' => true,
										'SHOW_BUTTON_SELECT' => false,
										'SHOW_BUTTON_ADD' => false,
									)
								);
								?>
							</div>
							<div class="crm-analytics-channel-phone-inner-btn">
								<span id="crm-tracking-phone-add" class="crm-tracking-site-item-add">
									<?=Loc::getMessage("CRM_TRACKING_SITE_ADD_PHONE")?>
								</span>
							</div>
						</div>
						<div class="crm-analytics-channel-phone-inner">
							<div class="crm-analytics-channel-field-title">
								<?=Loc::getMessage('CRM_TRACKING_SITE_EMAILS')?>:
							</div>
							<div class="crm-analytics-channel-phone-inner-field">
								<?
								$GLOBALS['APPLICATION']->includeComponent(
									'bitrix:ui.tile.selector',
									'',
									array(
										'ID' => 'available-email-list',
										'INPUT_NAME' => 'EMAILS',
										'MULTIPLE' => true,
										'LIST' => $arResult['EMAILS'],
										'CAN_REMOVE_TILES' => true,
										'SHOW_BUTTON_SELECT' => false,
										'SHOW_BUTTON_ADD' => false,
									)
								);
								?>
							</div>
							<div class="crm-analytics-channel-phone-inner-btn">
								<span id="crm-tracking-email-add" class="crm-tracking-site-item-add">
									<?=Loc::getMessage("CRM_TRACKING_SITE_ADD_EMAIL")?>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="crm-analytics-channel-addition">
				<div class="crm-analytics-channel-addition-alt" id="crm-analytics-channel-addition-btn">
					<div class="crm-analytics-channel-addition-more"><?=Loc::getMessage("CRM_TRACKING_SITE_ADDITIONAL")?></div>
					<div class="crm-analytics-channel-addition-alt-promo">
						<span class="crm-analytics-channel-addition-alt-promo-text"><?=Loc::getMessage('CRM_TRACKING_SITE_ENRICH_TEXT_SHORT')?></span>
						<span class="crm-analytics-channel-addition-alt-promo-text"><?=Loc::getMessage('CRM_TRACKING_SITE_REPLACE_TEXT_SHORT')?></span>
						<span class="crm-analytics-channel-addition-alt-promo-text"><?=Loc::getMessage('CRM_TRACKING_SITE_RESOLVE_DUP_SHORT')?></span>
					</div>
				</div>
				<div class="crm-analytics-channel-addition-options" id="crm-analytics-channel-addition-options">
					<div class="crm-analytics-channel-addition-options-item">
						<input class="crm-analytics-channel-addition-input" type="checkbox"
							   id="REPLACE_TEXT" name="REPLACE_TEXT" value="Y"
							<?=($arResult['ROW']['REPLACE_TEXT'] === 'Y' ? 'checked' : '')?>
						>
						<label class="crm-analytics-channel-addition-label" for="REPLACE_TEXT">
							<?=Loc::getMessage('CRM_TRACKING_SITE_REPLACE_TEXT')?>
						</label>
						<span data-hint="<?=Loc::getMessage('CRM_TRACKING_SITE_REPLACE_TEXT_HINT')?>" class="ui-hint"></span>

						<div class="crm-analytics-channel-addition-options-subitem">
							<input class="crm-analytics-channel-addition-input" type="checkbox"
								id="ENRICH_TEXT" name="ENRICH_TEXT" value="Y"
								<?=($arResult['ROW']['ENRICH_TEXT'] === 'Y' ? 'checked' : '')?>
							>
							<label class="crm-analytics-channel-addition-label" for="ENRICH_TEXT">
								<?=Loc::getMessage('CRM_TRACKING_SITE_ENRICH_TEXT')?>
							</label>
							<span data-hint="<?=Loc::getMessage('CRM_TRACKING_SITE_ENRICH_TEXT_HINT1')?>" class="ui-hint"></span>
						</div>
					</div>
					<div class="crm-analytics-channel-addition-options-item">
						<input class="crm-analytics-channel-addition-input" type="checkbox"
							id="RESOLVE_DUPLICATES" name="RESOLVE_DUPLICATES" value="Y"
							<?=($arResult['ROW']['RESOLVE_DUPLICATES'] === 'Y' ? 'checked' : '')?>
						>
						<label class="crm-analytics-channel-addition-label" for="RESOLVE_DUPLICATES">
							<?=Loc::getMessage('CRM_TRACKING_SITE_RESOLVE_DUP')?>
						</label>
						<span data-hint="<?=Loc::getMessage('CRM_TRACKING_SITE_RESOLVE_DUP_HINT')?>" class="ui-hint"></span>
					</div>
				</div>
			</div>
			<!--
			<div class="crm-tracking-site-desc">
				<span class="crm-tracking-site-desc-text">Setup
				<a href="#" class="crm-tracking-site-desc-link">phone numbers</a>
				 and <a href="#" class="crm-tracking-site-desc-link">emails</a> for.</span>
			</div>
			-->
			<!--
			<div class="crm-analytics-channel-btn-block">
				<button class="ui-btn ui-btn-primary">check</button>
			</div>
			-->
		</div>

		<?$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
			'BUTTONS' => [
				'save', 'cancel' => $arParams['PATH_TO_LIST'],
				[
					'TYPE' => 'checkbox',
					'NAME' => 'deactivate',
					'CHECKED' => $arResult['ROW']['ACTIVE'] != 'Y',
					'CAPTION' => Loc::getMessage('CRM_TRACKING_SITE_REPLACEMENT_DISABLE')
				]
			]
		]);?>
	</form>

	<div style="display: none;">
		<div id="crm-tracking-dialog-add">
			<div class="ui-ctl ui-ctl-textbox ui-ctl-inline ui-ctl-w100">
				<input id="item-add-name"
					type="text" class="ui-ctl-element"
					placeholder=""
					data-placeholder-email="<?=htmlspecialcharsbx(Loc::getMessage('CRM_TRACKING_SITE_ITEM_DEMO_EMAIL'))?>"
					data-placeholder-phone="<?=htmlspecialcharsbx(Loc::getMessage('CRM_TRACKING_SITE_ITEM_DEMO_PHONE'))?>"
				>
			</div>
			<div class="crm-tracking-phone-popup-buttons">
				<a id="item-add-name-btn-add" class="ui-btn ui-btn-primary ui-btn-sm">
					<?=Loc::getMessage('CRM_TRACKING_SITE_BTN_ADD')?>
				</a>
				<a id="item-add-name-btn-close" class="ui-btn ui-btn-link ui-btn-sm">
					<?=Loc::getMessage('CRM_TRACKING_SITE_BTN_CANCEL')?>
				</a>
			</div>
		</div>
	</div>

	<script>
		BX.ready(function () {
			BX.Crm.Tracking.Site.init(<?=Json::encode([
				'containerId' => $containerId,
				'isInstalled' => $arResult['ROW']['IS_INSTALLED'] == 'Y',
				'sources' => $arResult['SOURCES'],
				'mess' => [
					'statusNone' => Loc::getMessage('CRM_TRACKING_SITE_SCRIPT_STATUS_NONE'),
					'statusProcess' => Loc::getMessage('CRM_TRACKING_SITE_SCRIPT_STATUS_PROCESS'),
					'statusError' => Loc::getMessage('CRM_TRACKING_SITE_SCRIPT_STATUS_ERROR'),
					'statusSuccess' => Loc::getMessage('CRM_TRACKING_SITE_SCRIPT_STATUS_SUCCESS'),
					'oldBrowser' => Loc::getMessage('CRM_TRACKING_SITE_OLD_BROWSER'),
				]
			])?>);

			BX.UI.Hint.init(BX('<?=htmlspecialcharsbx($containerId)?>'));
		});
	</script>
</div>
