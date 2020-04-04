<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

/** @var array $arParams */
/** @var array $arResult */
/** @var \CAllMain $APPLICATION */

$links = $arResult['LINKS'];
$hasLinks = $arResult['HAS_LINKS'];


$destroyEventName = $arParams['JS_DESTROY_EVENT_NAME'];
$accountId = $arParams['ACCOUNT_ID'] ?: $arResult['ACCOUNT_ID'];
$formId = $arParams['FORM_ID'];
$crmFormId = $arParams['CRM_FORM_ID'];
$provider = $arParams['PROVIDER'];
$data = $arResult['DATA'];
$type = htmlspecialcharsbx($provider['TYPE']);
$typeUpped = strtoupper($type);

$crmFormSuccessUrl = $data['CRM_FORM_RESULT_SUCCESS_URL'];


$APPLICATION->SetTitle(Loc::getMessage('CRM_ADS_LEADADS_CAPTION_' . $typeUpped));
Extension::load('ui.buttons');
Extension::load('ui.hint');

if (!empty($arParams['CONTAINER_NODE_ID']))
{
	$containerNodeId = $arParams['CONTAINER_NODE_ID'];
}
else
{
	$containerNodeId = 'crm-lead-ads-container';
	?>
	<div id="<?=$containerNodeId?>"></div>
	<?
}
?>
<script id="template-crm-ads-dlg-settings" type="text/html">
	<div class="crm-ads-forms-block">
		<div class="crm-ads-forms-title">
			<?=Loc::getMessage('CRM_ADS_LEADADS_TITLE_' . $typeUpped)?>
			<a target="_blank" href="<?=htmlspecialcharsbx($provider['URL_INFO'])?>">
				<?=Loc::getMessage('CRM_ADS_LEADADS_MORE')?>
			</a>
		</div>
	</div>

	<div data-bx-ads-block="loading" style="display: none;" class="crm-ads-forms-block">
		<div class="crm-ads-forms-ldr-user-loader-item">
			<div class="crm-ads-forms-ldr-loader">
				<svg class="crm-ads-forms-ldr-circular" viewBox="25 25 50 50">
					<circle class="crm-ads-forms-ldr-path" cx="50" cy="50" r="20" fill="none" stroke-width="1" stroke-miterlimit="10"/>
				</svg>
			</div>
		</div>
	</div>

	<div data-bx-ads-block="login" style="display: none;" class="crm-ads-forms-block">
		<div class="crm-ads-forms-social crm-ads-forms-social-<?=$type?>">
			<a
				target="_blank"
				href="javascript: void(0);"
				onclick="BX.util.popup('<?=htmlspecialcharsbx($provider['AUTH_URL'])?>', 800, 600);"
				class="webform-small-button webform-small-button-transparent">
				<?=Loc::getMessage('CRM_ADS_LEADADS_LOGIN')?>
			</a>
		</div>
	</div>


	<div data-bx-ads-block="auth" style="display: none;">
		<div class="crm-ads-forms-block">
			<div class="crm-ads-forms-social crm-ads-forms-social-<?=$type?>">
				<div class="crm-ads-forms-social-avatar">
					<div data-bx-ads-auth-avatar="" class="crm-ads-forms-social-avatar-icon"></div>
				</div>
				<div class="crm-ads-forms-social-user">
					<a target="_top" data-bx-ads-auth-link="" data-bx-ads-auth-name="" class="crm-ads-forms-social-user-link" title=""></a>
				</div>
				<div class="crm-ads-forms-social-shutoff">
					<span data-bx-ads-auth-logout="" class="crm-ads-forms-social-shutoff-link"><?=Loc::getMessage('CRM_ADS_LEADADS_LOGOUT')?></span>
				</div>
			</div>
		</div>
	</div>


	<div data-bx-ads-block="refresh" style="display: none;">
		<div class="crm-ads-forms-block crm-ads-forms-wrapper crm-ads-forms-wrapper-center">
			<?=Loc::getMessage('CRM_ADS_LEADADS_REFRESH_TEXT')?>
			<br>
			<br>
			<span data-bx-ads-refresh-btn="" class="webform-small-button webform-small-button-transparent">
				<?=Loc::getMessage('CRM_ADS_LEADADS_REFRESH')?>
			</span>
		</div>
	</div>


	<div data-bx-ads-block="main" style="display: none;">
		<div class="crm-ads-forms-block crm-ads-forms-wrapper">

			<div class="crm-ads-forms-block" style="<?=((!$provider['GROUP']['IS_AUTH_USED'] || $provider['GROUP']['HAS_AUTH']) ? '' : 'display: none;')?>">
				<div class="crm-ads-forms-title-full"><?=Loc::getMessage('CRM_ADS_LEADADS_FORM_NAME_' . $typeUpped)?>:</div>

				<table class="crm-ads-forms-table">
					<tr>
						<td>
							<input data-bx-ads-form-name="" value="<?=htmlspecialcharsbx($data['CRM_FORM_NAME'])?>" class="crm-ads-forms-input">
						</td>
					</tr>
				</table>
			</div>

			<div class="crm-ads-forms-block" style="<?=((!$provider['GROUP']['IS_AUTH_USED'] || $provider['GROUP']['HAS_AUTH']) ? '' : 'display: none;')?>">
				<div class="crm-ads-forms-title-full"><?=Loc::getMessage('CRM_ADS_LEADADS_FORM_SUCCESS_URL')?>:</div>

				<table class="crm-ads-forms-table">
					<tr>
						<td>
							<input data-bx-ads-form-url="" value="<?=htmlspecialcharsbx($crmFormSuccessUrl)?>" placeholder="https://www.example.com/success.html" class="crm-ads-forms-input">
						</td>
					</tr>
				</table>
			</div>

			<div class="crm-ads-forms-block">
				<div class="crm-ads-forms-title-full"><?=Loc::getMessage('CRM_ADS_LEADADS_SELECT_ACCOUNT_' . $typeUpped)?>:</div>

				<table class="crm-ads-forms-table crm-ads-forms-ldr-table">
					<tr>
						<td>
							<select
								name="ACCOUNT_ID"
								data-bx-ads-account=""
								<?=($provider['GROUP']['HAS_AUTH'] ? 'data-blocked="" disabled ' : '')?>
								class="crm-ads-forms-dropdown"
								style="max-width: 400px;"
							>
							</select>

							<span
								class="ui-btn <?=($provider['GROUP']['HAS_AUTH'] ? 'ui-btn-light-border' : 'ui-btn-primary')?>"
								style="<?=($provider['GROUP']['IS_AUTH_USED'] ? '' : 'display: none;')?>"
								<?=($provider['GROUP']['HAS_AUTH'] ? 'data-bx-ads-group-logout=""' : 'data-bx-ads-group-reg=""')?>
							>
								<?=($provider['GROUP']['HAS_AUTH'] ?
									Loc::getMessage('CRM_ADS_LEADADS_GROUP_IS_AUTH_' . $typeUpped)
									:
									Loc::getMessage('CRM_ADS_LEADADS_GROUP_DO_AUTH_' . $typeUpped)
								)?>
							</span>

							<span data-hint="<?=(Loc::getMessage(($provider['GROUP']['HAS_AUTH'] ? 'CRM_ADS_LEADADS_GROUP_IS_AUTH_HINT_' : 'CRM_ADS_LEADADS_GROUP_DO_AUTH_HINT_') . $typeUpped))?>"></span>
						</td>
						<td>
							<div data-bx-ads-account-loader="" class="crm-ads-forms-ldr-loader-sm" style="display: none;">
								<svg class="crm-ads-forms-ldr-circular" viewBox="25 25 50 50">
									<circle class="crm-ads-forms-ldr-path" cx="50" cy="50" r="20" fill="none" stroke-width="1" stroke-miterlimit="10"/>
								</svg>
							</div>
						</td>
						<td align="right" width="1%">
							<a href="<?=htmlspecialcharsbx($provider['URL_ACCOUNT_LIST'])?>" target="_blank">
								<span class="crm-ads-link-copy"></span>
							</a>
						</td>
					</tr>
				</table>
			</div>

			<?if ($type == 'facebook'):?>
			<div class="crm-ads-forms-block">
				<div class="crm-ads-forms-title-full"><?=Loc::getMessage('CRM_ADS_LEADADS_LOCALE')?>:</div>

				<table class="crm-ads-forms-table crm-ads-forms-ldr-table">
					<tr>
						<td>
							<select
								name="LOCALE"
								data-bx-ads-form-locale=""
								<?=($provider['GROUP']['HAS_AUTH'] ? 'data-blocked="" disabled ' : '')?>
								class="crm-ads-forms-dropdown"
								style="max-width: 400px;"
							>
								<option value=""><?=Loc::getMessage('CRM_ADS_LEADADS_LOCALE_AUTO')?></option>
								<option value="AR_AR">Arabic (AR_AR)</option>
								<option value="CS_CZ">Czech (CS_CZ)</option>
								<option value="DA_DK">Danish (DA_DK)</option>
								<option value="NL_NL">Dutch (NL_NL)</option>
								<option value="FI_FI">Finnish (FI_FI)</option>
								<option value="HE_IL">Hebrew (HE_IL)</option>
								<option value="HI_IN">Hindi (HI_IN)</option>
								<option value="HU_HU">Hungarian (HU_HU)</option>
								<option value="ID_ID">Indonesian (ID_ID)</option>
								<option value="JA_JP">Japanese (JA_JP)</option>
								<option value="KO_KR">Korean (KO_KR)</option>
								<option value="NB_NO">Norwegian (NB_NO)</option>
								<option value="PT_PT">Portuguese (PT_PT)</option>
								<option value="RO_RO">Romanian (RO_RO)</option>
								<option value="SV_SE">Swedish (SV_SE)</option>
								<option value="TH_TH">Thai (TH_TH)</option>
								<option value="VI_VN">Vietnamese (VI_VN)</option>
								<option value="EN_US">English (EN_US)</option>
								<option value="EN_GB">English (EN_GB)</option>
								<option value="ZH_HK">Chinese (ZH_HK)</option>
								<option value="ZH_TW">Chinese (ZH_TW)</option>
								<option value="ZH_CN">Chinese (ZH_CN)</option>
								<option value="FR_FR">French (FR_FR)</option>
								<option value="DE_DE">German (DE_DE)</option>
								<option value="IT_IT">Italian (IT_IT)</option>
								<option value="PL_PL">Polish (PL_PL)</option>
								<option value="PT_BR">Portuguese (PT_BR)</option>
								<option value="RU_RU">Russian (RU_RU)</option>
								<option value="ES_ES">Spanish (ES_ES)</option>
								<option value="ES_LA">Spanish Latin (ES_LA)</option>
								<option value="TR_TR">Turkish (TR_TR)</option>
							</select>

						</td>
						<td>

						</td>
						<td align="right" width="1%">

						</td>
					</tr>
				</table>
			</div>
			<?endif;?>

			<div data-bx-ads-account-not-found="" class="crm-ads-forms-block" style="display: none;">
				<div class="crm-ads-forms-alert">
					<?=Loc::getMessage(
						'CRM_ADS_LEADADS_ERROR_NO_ACCOUNTS_' . $typeUpped,
						array(
							'%name%' => '<a data-bx-ads-audience-create-link="" href="' . htmlspecialcharsbx($provider['URL_AUDIENCE_LIST']) . '" '
								. 'target="_blank">'
								. Loc::getMessage('CRM_ADS_LEADADS_CABINET_' . $typeUpped)
								.'</a>'
						)
					)?>
				</div>
			</div>

			<div class="crm-ads-forms-block" style="<?=((!$provider['GROUP']['IS_AUTH_USED'] || $provider['GROUP']['HAS_AUTH']) ? '' : 'display: none;')?>">
				<table class="crm-ads-forms-table">
					<tr>
						<td width="1%">
							<span data-bx-ads-btn-export=""
								data-bx-state="<?=($hasLinks ? '1' : '')?>"
								class="ui-btn"
								style="display: none;"
								data-bx-text-send="<?=Loc::getMessage('CRM_ADS_LEADADS_BUTTON_EXPORT_' . $typeUpped)?>"
								data-bx-text-disconnect="<?=Loc::getMessage('CRM_ADS_LEADADS_BUTTON_UNLINK_' . $typeUpped)?>"
								data-bx-text-success="<?=Loc::getMessage('CRM_ADS_LEADADS_BUTTON_EXPORTED_SUCCESS')?>"
							>
								<?=Loc::getMessage('CRM_ADS_LEADADS_BUTTON_EXPORT_' . $typeUpped)?>
							</span>
						</td>
						<td class="crm-ads-vertical-align-middle">
							<span data-bx-ads-btn-hint="" class="crm-ads-hint" data-bx-text-enabled="<?=Loc::getMessage('CRM_ADS_LEADADS_AFTER_ENABLE_' . $typeUpped)?>" data-bx-text-disabled="<?=Loc::getMessage('CRM_ADS_LEADADS_AFTER_DISABLE_' . $typeUpped)?>"></span>
						</td>
					</tr>
					<tr>
						<td width="1%">
							<span data-bx-ads-btn-date="" class="crm-ads-btn-date" data-bx-text-now="<?=Loc::getMessage('CRM_ADS_LEADADS_NOW')?>">
								<?=Loc::getMessage('CRM_ADS_LEADADS_IS_LINKED')?> <?=htmlspecialcharsbx($arResult['LINK_DATE'])?>
							</span>
						</td>
						<td></td>
						<td>
							<span data-bx-ads-form-id="" class="crm-ads-hint-small">
								<?if ($hasLinks):?>
									<?=Loc::getMessage('CRM_ADS_LEADADS_FORM_ID')?>: <?=htmlspecialcharsbx($links[0]['ADS_FORM_ID'])?>
								<?endif;?>
							</span>
						</td>
					</tr>
				</table>
			</div>
		</div>

	</div>

</script>

<script>
	BX.ready(function () {

		var r = (Date.now()/1000|0);
		BX.loadCSS('<?=$this->GetFolder()?>/configurator.css?' + r);
		BX.loadScript('<?=$this->GetFolder()?>/configurator.js?' + r, function()
		{
			BX.CrmAdsLeadAds = new CrmAdsLeadAds(<?=\Bitrix\Main\Web\Json::encode(array(
				'provider' => $provider,
				'accountId' => $accountId,
				'formId' => $formId,
				'crmFormId' => $crmFormId,
				'data' => $data,
				'containerId' => $containerNodeId,
				'destroyEventName' => $destroyEventName,
				'actionRequestUrl' => $this->getComponent()->getPath() . '/ajax.php',
				'mess' => array(
					'errorAction' => Loc::getMessage('CRM_ADS_LEADADS_ERROR_ACTION'),
					'dlgBtnClose' => Loc::getMessage('CRM_ADS_LEADADS_CLOSE'),
					'dlgBtnCancel' => Loc::getMessage('CRM_ADS_LEADADS_APPLY'),
				)
			))?>);
		});

	});
</script>

<?$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
	'BUTTONS' => ['close' => $arParams['PATH_TO_WEB_FORM_LIST']]
]);?>