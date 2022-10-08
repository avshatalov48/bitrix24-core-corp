<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;

/** @var \CAllMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
/** @var \CBitrixComponentTemplate $this */

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");

Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.alerts',
	'ui.icons',
	'ui.forms',
	'color_picker',
	'sidepanel',
	'clipboard',
	'seo.ads.client_selector',
	'seo.ads.login',
	'ui.info-helper',
]);

$this->addExternalCss($this->GetFolder() . '/utm.css');

$name = htmlspecialcharsbx($arResult['ROW']['NAME']);
$code = htmlspecialcharsbx($arResult['ROW']['CODE']);

$containerId = 'crm-analytics-source-ads-editor';
?>

<script type="text/javascript">
	BX.ready(function () {
		top.BX.onCustomEvent(
			top,
			'crm-analytics-source-edit',
			[<?=Json::encode([
				'row' => $arResult['ROW'],
				'enabled' => $arResult['ROW']['ID'] > 0 && $arResult['ROW']['UTM_SOURCE'],
				'added' => $arParams['IS_ADDED'],
			])?>]
		);

		<?if ($arResult['FEATURE_CODE']):?>
			BX.UI.InfoHelper.show('<?=$arResult['FEATURE_CODE']?>');
		<?endif;?>
	});
</script>

<div id="<?=htmlspecialcharsbx($containerId)?>" class="crm-analytics-source-wrap">

	<?
	if ($arResult['ROW']['ID'])
	{
		$this->SetViewTarget('pagetitle');
		?>
		<button id="crm-tracking-expenses" type="button" class="ui-btn ui-btn-light-border">
			<?=Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_EXPENSES')?>
		</button>
		<?
		$this->EndViewTarget();
	}

	$APPLICATION->IncludeComponent(
		'bitrix:ui.feedback.form',
		'',
		\Bitrix\Crm\Tracking\Provider::getFeedbackParameters()
	);
	?>

	<?if (empty($arResult['ROW']['CONFIGURABLE'])):?>
		<div class="crm-analytics-source-block crm-analytics-source-block-desc">
				<span class="crm-analytics-source-icon <?=htmlspecialcharsbx($arResult['ROW']['ICON_CLASS'])?>">
					<i></i>
				</span>
			<?
			$sourceDesc = Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_DESC_'.mb_strtoupper($arResult['ROW']['CODE']) . '1')
				?: Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_DESC_'.mb_strtoupper($arResult['ROW']['CODE']))
					?: Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_AUTO_CONFIGURED');
			?>
			<div class="crm-analytics-source-section">
				<div class="crm-analytics-source-header"><?=Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_CONNECTED', ['%name%' => $name])?></div>
				<div class="crm-analytics-source-desc">
						<span class="crm-analytics-source-desc-text">
							<?=$sourceDesc?>
						</span>
				</div>
			</div>
		</div>

		<?
		$APPLICATION->IncludeComponent(
			'bitrix:ui.button.panel',
			'',
			[
				'BUTTONS' => ['close' => $arParams['PATH_TO_LIST'],]
			]
		);
		return;
	endif;?>

	<form method="post">
		<?=bitrix_sessid_post();?>

		<?if ($arResult['ROW']['ADVERTISABLE']):?>
			<div data-role="crm/tracking/desc"
				class="crm-analytics-source-block crm-analytics-source-block-desc"
				style="<?=($arResult['HAS_AUTH'] ? 'display: none;' : '')?>"
			>
				<span class="crm-analytics-source-icon <?=htmlspecialcharsbx($arResult['ROW']['ICON_CLASS'])?>">
					<i></i>
				</span>
				<div class="crm-analytics-source-section">
					<div class="crm-analytics-source-header"><?=Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_TITLE', ['%name%' => $name])?></div>
					<div class="crm-analytics-source-desc">
						<span class="crm-analytics-source-desc-text">
							<?=Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_TEXT', ['%name%' => $name])?>
							<?if (!$arResult['AD_ACCESSIBLE']):?>
								<br>
								<span class="ui-alert ui-alert-warning">
									<?=Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_INSTALL_SEO')?>
								</span>
							<?endif;?>
						</span>
					</div>
				</div>
			</div>

			<?if ($arResult['AD_ACCESSIBLE']):?>
				<div data-role="crm/tracking/connect"
					class="crm-analytics-source-block"
					style="<?=($arResult['HAS_AUTH'] ? 'display: none;' : '')?>"
				>
					<div class="crm-analytics-source-title">
						<span class="crm-analytics-source-title-text">
							<?=Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_AUTH', ['%name%' => $name])?>
						</span>
					</div>
					<div class="crm-analytics-source-connect">
						<span class="crm-analytics-source-desc-text">
							<?=Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_AUTH_DESC', ['%name%' => $name])?>
						</span>

							<?php if($code === 'google'):?>
								<style>
									@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@500&display=swap');
								</style>
								<div class="crm-analytics-source-connect-btn-goo">
									<a
										data-role="crm/tracking/connect/btn"
										href="<?=htmlspecialcharsbx($arResult['PROVIDER']['AUTH_URL'])?>"
										onclick="BX.Seo.Ads.LoginFactory.getLoginObject(<?=
											htmlspecialcharsbx(Json::encode($arResult['PROVIDER']))
										?>).login(); return false;"
										class="crm-ads-goo-btn"
									>
										<div class="crm-ads-goo-btn-icon"></div>
										<div class="crm-ads-goo-btn-text"><?php echo Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_CONNECT_GOOGLE')?></div>
									</a>
								</div>
							<?php else:?>
								<div class="crm-analytics-source-connect-btn">
										<span class="crm-analytics-source-connect-btn-icon <?=htmlspecialcharsbx($arResult['ROW']['ICON_CLASS'])?>">
											<i></i>
										</span>

										<a  data-role="crm/tracking/connect/btn" type="button"
											href="<?=htmlspecialcharsbx($arResult['PROVIDER']['AUTH_URL'])?>"
											onclick="BX.Seo.Ads.LoginFactory.getLoginObject(<?=
												htmlspecialcharsbx(Json::encode($arResult['PROVIDER']))
											?>).login(); return false;"
											class="ui-btn ui-btn-light-border"
										><?=Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_CONNECT')?></a>
								</div>
							<?php endif;?>

					</div>
				</div>

				<div data-role="crm/tracking/connected"
					class="crm-analytics-source-block crm-analytics-source-block-desc"
					style="<?=(!$arResult['HAS_AUTH'] ? 'display: none;' : '')?>"
				>
					<span class="crm-analytics-source-icon <?=htmlspecialcharsbx($arResult['ROW']['ICON_CLASS'])?>">
						<i></i>
					</span>
					<div class="crm-analytics-source-section">
						<div class="crm-analytics-source-header"><?=Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_CONNECTED', ['%name%' => $name])?></div>
						<div class="crm-analytics-source-desc">
							<input type="hidden"
								name="AD_CLIENT_ID"
								data-role="crm/tracking/ad/client"
								value="<?=htmlspecialcharsbx($arResult['ROW']['AD_CLIENT_ID'])?>"
							>
							<div data-role="crm/tracking/profile" class="crm-analytics-source-user">

							</div>
						</div>
					</div>
				</div>
			<?endif;?>
		<?else:?>


		<?endif;?>

		<?if ($arResult['AD_ACCESSIBLE'] && $arResult['PROVIDER']['HAS_ACCOUNTS'] && $arResult['ROW']['CODE']):?>
			<div class="crm-analytics-source-block"
				data-role="crm/tracking/ad/accounts"
				style="<?=($arResult['HAS_AUTH'] ? '' : 'display: none;')?>"
			>
				<div class="crm-analytics-source-title">
					<span class="crm-analytics-source-title-text">
						<?=Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_AD_ACCOUNT')?>
					</span>
				</div>

				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					<select
						data-role="crm/tracking/ad/accounts/view"
						class="ui-ctl-element"
						disabled=""
					>
					</select>
					<input type="hidden"
						name="AD_ACCOUNT_ID"
						data-role="crm/tracking/ad/accounts/data"
						value="<?=htmlspecialcharsbx($arResult['ROW']['AD_ACCOUNT_ID'])?>"
					>
				</div>
			</div>
		<?endif;?>

		<div class="crm-analytics-source-block crm-analytics-source-block-utm">
			<div class="crm-analytics-utm-editor-block">
				<div class="crm-analytics-utm-editor-field">
					<div class="crm-analytics-utm-editor-subject">
						<span class="crm-analytics-utm-editor-subject-text"><?= Loc::getMessage("CRM_TRACKING_SOURCE_EDIT_UTM_NAME") ?></span>
					</div>
					<div class="crm-analytics-utm-editor-field-input-block">
						<input class="crm-analytics-utm-editor-field-input"
							name="NAME" type="text" placeholder=""
							value="<?=htmlspecialcharsbx($arResult['ROW']['NAME'])?>"
						>
					</div>
					<div class="crm-analytics-utm-editor-field-decs"><?= Loc::getMessage("CRM_TRACKING_SOURCE_EDIT_UTM_NAME_DESC") ?></div>
				</div>
			</div>
		</div>

		<div class="crm-analytics-source-block crm-analytics-source-block-utm">
			<div class="crm-analytics-utm-editor-block">
				<div class="crm-analytics-utm-editor-field">
					<div class="crm-analytics-utm-editor-subject">
						<span class="crm-analytics-utm-editor-subject-text"><?= Loc::getMessage("CRM_TRACKING_SOURCE_EDIT_UTM_TAGS") ?></span>
					</div>
					<div class="crm-analytics-utm-editor-field-input-block">
						<div class="crm-analytics-utm-editor-field-input-decs">utm_source</div>
						<?
						$GLOBALS['APPLICATION']->includeComponent(
							'bitrix:ui.tile.selector',
							'',
							array(
								'ID' => 'utm-source',
								'INPUT_NAME'=> "UTM_SOURCE",
								'MULTIPLE' => true,
								'LIST' => $arResult['ROW']['UTM_SOURCE'],
								'CAN_REMOVE_TILES' => true,
								'SHOW_BUTTON_SELECT' => true,
								'SHOW_BUTTON_ADD' => false,
								'BUTTON_SELECT_CAPTION' => Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_UTM_SOURCE_BTN_ADD'),
								'BUTTON_SELECT_CAPTION_MORE' => Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_UTM_SOURCE_BTN_ADD'),
							)
						);
						?>
					</div>
					<div class="crm-analytics-utm-editor-field-decs"><?= Loc::getMessage("CRM_TRACKING_SOURCE_EDIT_UTM_SOURCE_DESC") ?></div>
				</div>
				<? if ($arResult['ROW']['UTM_CONTENT']): ?>
				<div class="crm-analytics-utm-editor-field" style="margin-top: 30px;">
					<div class="crm-analytics-utm-editor-field-input-block">
						<div class="crm-analytics-utm-editor-field-input-decs">utm_content</div>
						<div class="crm-analytics-source-block-utm-content">
							<div class="crm-analytics-source-block-utm-content-val"><?=htmlspecialcharsbx($arResult['ROW']['UTM_CONTENT'])?></div>
							<div class="crm-analytics-source-block-utm-content-btn"
								id="crm-analytics-source-block-utm-content-btn"
							><?=Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_BTN_COPY')?></div>
						</div>
					</div>
					<div class="crm-analytics-utm-editor-field-decs">
						<?= Loc::getMessage("CRM_TRACKING_SOURCE_EDIT_UTM_CONTENT_DESC") ?>
						<span class="crm-analytics-source-hint" onclick="top.BX.Helper.show('redirect=detail&code=12526974');"></span>
					</div>
				</div>
				<? endif ?>
			</div>
		</div>

		<?if (!$arResult['ROW']['CODE']):?>
			<div class="crm-analytics-source-block crm-analytics-source-block-utm">
				<div class="crm-analytics-utm-editor-block">
					<div class="crm-analytics-utm-editor-subject">
							<span class="crm-analytics-utm-editor-subject-text"><?= Loc::getMessage("CRM_TRACKING_SOURCE_EDIT_ICON_COLOR") ?></span>
					</div>
					<div class="crm-analytics-utm-editor-field-color">
						<span class="ui-icon ui-icon-service-universal crm-analytics-utm-editor-color-icon">
							<i id="crm-analytics-utm-editor-color-icon-value"></i>
						</span>
						<span id="crm-analytics-utm-editor-color-select" class="crm-analytics-utm-editor-color-select"><?= Loc::getMessage("CRM_TRACKING_SOURCE_EDIT_ICON_COLOR_CHOOSE") ?></span>
						<input id="crm-analytics-utm-editor-color-input"
							type="hidden" name="ICON_COLOR"
							value="<?=htmlspecialcharsbx($arResult['ROW']['ICON_COLOR'])?>"
						>
					</div>
				</div>
			</div>

			<div class="crm-analytics-source-block">
				<div class="crm-analytics-utm-editor-block">
					<div class="crm-analytics-utm-editor-field">
						<div class="crm-analytics-utm-editor-subject">
							<span class="crm-analytics-utm-editor-subject-text"><?= Loc::getMessage("CRM_TRACKING_SOURCE_EDIT_REF_DOMAIN_NAME") ?></span>
						</div>
						<div class="crm-analytics-utm-editor-field-input-block">
							<?
							$GLOBALS['APPLICATION']->includeComponent(
								'bitrix:ui.tile.selector',
								'',
								array(
									'ID' => 'ref-domain',
									'INPUT_NAME' => 'REF_DOMAIN',
									'MULTIPLE' => true,
									'LIST' => $arResult['ROW']['REF_DOMAIN'],
									'CAN_REMOVE_TILES' => true,
									'SHOW_BUTTON_SELECT' => true,
									'SHOW_BUTTON_ADD' => false,
									'BUTTON_SELECT_CAPTION' => Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_REF_DOMAIN_ADD'),
									'BUTTON_SELECT_CAPTION_MORE' => Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_REF_DOMAIN_ADD'),
								)
							);
							?>
						</div>
						<div class="crm-analytics-utm-editor-field-decs"><?= Loc::getMessage("CRM_TRACKING_SOURCE_EDIT_REF_DOMAIN_HINT") ?></div>
					</div>
				</div>
			</div>
		<?endif;?>


		<?if ($arResult['AD_UPDATE_ACCESSIBLE'] && $arResult['ROW']['CODE']):?>

			<div class="crm-analytics-source-block">
				<div class="crm-analytics-utm-editor-block">
					<div class="crm-analytics-utm-editor-subject">
						<span class="crm-analytics-utm-editor-subject-text"><?= Loc::getMessage("CRM_TRACKING_SOURCE_EDIT_REPLACE_TITLE") ?></span>
					</div>
					<div class="crm-analytics-source-contact">
						<div class="crm-analytics-source-desc">
							<span class="crm-analytics-source-desc-text"><?= Loc::getMessage("CRM_TRACKING_SOURCE_EDIT_REPLACE_EXISTED") ?></span>
						</div>
						<div class="crm-analytics-source-contact-block">
							<div class="crm-analytics-source-contact-item">
								<div class="crm-analytics-source-contact-title">
									<span class="crm-analytics-source-contact-title-icon ui-icon ui-icon-common-phone">
										<i></i>
									</span>
									<span class="crm-analytics-source-contact-title-text"><?= Loc::getMessage("CRM_TRACKING_SOURCE_EDIT_REPLACE_PHONE") ?></span>
								</div>
								<div class="crm-analytics-source-contact-content">
									<span class="crm-analytics-source-contact-value">63-03-03</span>
									<span class="crm-analytics-source-contact-value">8 (952) 118-91-12</span>
									<span class="crm-analytics-source-contact-value">8 (911) 200-66-00</span>
								</div>
							</div>
							<div class="crm-analytics-source-contact-item">
								<div class="crm-analytics-source-contact-title">
									<span class="crm-analytics-source-contact-title-icon ui-icon ui-icon-service-email">
										<i></i>
									</span>
									<span class="crm-analytics-source-contact-title-text"><?= Loc::getMessage("CRM_TRACKING_SOURCE_EDIT_REPLACE_EMAIL") ?></span>
								</div>
								<div class="crm-analytics-source-contact-content">
									<span class="crm-analytics-source-contact-value">zamki39@example.com</span>
									<span class="crm-analytics-source-contact-value">shawervma@inbox.ru</span>
								</div>
							</div>
						</div>
					</div>
					<div class="crm-analytics-source-contact crm-analytics-source-contact-replace">
						<div class="crm-analytics-source-desc">
							<span class="crm-analytics-source-desc-text"><?= Loc::getMessage("CRM_TRACKING_SOURCE_EDIT_REPLACE_NEW") ?></span>
						</div>
						<div class="crm-analytics-source-contact-block">
							<div class="crm-analytics-source-contact-item">
								<div class="crm-analytics-source-contact-content">
									<span class="crm-analytics-source-contact-value">8 (911) 200-66-00</span>
								</div>
							</div>
							<div class="crm-analytics-source-contact-item">
								<div class="crm-analytics-source-contact-content">
									<div class="crm-analytics-source-contact-notice">
										<span class="crm-analytics-source-contact-notice-text"><?= Loc::getMessage("CRM_TRACKING_SOURCE_EDIT_REPLACE_NOT_SETUP") ?></span>
										<a class="crm-analytics-source-contact-notice-link" href="#"><?= Loc::getMessage("CRM_TRACKING_SOURCE_EDIT_REPLACE_SETUP") ?></a>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="crm-analytics-source-contact-settings">
						<input class="crm-analytics-source-contact-checkbox" type="checkbox" id="replace">
						<label class="crm-analytics-source-contact-label" for="replace"><?= Loc::getMessage("CRM_TRACKING_SOURCE_EDIT_REPLACE_BUTTON") ?></label>
					</div>
				</div>
			</div>
		<?endif;?>

		<?$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
			'BUTTONS' => [
				($arResult['FEATURE_CODE'] ? null : 'save'),
				'cancel' => $arParams['PATH_TO_LIST'],
				(
					$arResult['ROW']['ID'] &&
					$arResult['ROW']['ACTIVE'] != 'N'
				)
					? ['TYPE' => 'remove', 'NAME' => 'archive', 'CAPTION' => Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_ARCHIVE'),]
					: null
				,
			]
		]);?>

	</form>

	<script type="text/javascript">
		BX.ready(function () {
			BX.Crm.Tracking.Source.Editor.init(<?=Json::encode(array(
				'containerId' => $containerId,
				'signedParameters' => $this->getComponent()->getSignedParameters(),
				'componentName' => $this->getComponent()->getName(),
				'provider' => $arResult['PROVIDER'],
				'pathToExpenses' => $arResult['PATH_TO_EXPENSES'],
				'mess' => array(
					'errorAction' => Loc::getMessage('CRM_ADS_RTG_ERROR_ACTION'),
					'dlgBtnClose' => Loc::getMessage('CRM_ADS_RTG_CLOSE'),
					'dlgBtnCancel' => Loc::getMessage('CRM_ADS_RTG_APPLY'),
					'loading' => Loc::getMessage('CRM_TRACKING_SOURCE_EDIT_LOADING'),
				)
			))?>);
		});
	</script>
</div>