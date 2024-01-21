<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	"ui.design-tokens",
	"ui.fonts.opensans",
	"ui.buttons",
	"ui.alerts",
	"ui.forms",
	"ui.icons",
	"ui.sidepanel-content",
]);

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

global $APPLICATION;
$APPLICATION->SetTitle(Loc::getMessage("CRM_1C_START_FACE_CARD_NAME"));

if (isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y")
{
	$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
	$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");
}

$jsMessages = [];
$jsMessagesCodes = [];

if($arResult['RESTRICTED_LICENCE'] === true)
{
	$jsMessagesCodes = array(
		'CRM_1C_START_FACE_CARD_B24_BLOCK_TITLE2',
		'CRM_1C_START_FACE_CARD_B24_BLOCK_TEXT2',
	);
}
elseif($arResult['LICENSE_ACCEPTED'] === false)
{
	$jsMessagesCodes = array(
		'CRM_1C_START_FACE_CARD_CONSENT_TITLE',
		'CRM_1C_START_FACE_CARD_CONSENT_AGREED',
	);
}

if(count($jsMessagesCodes)>0)
{
	foreach ($jsMessagesCodes as $code)
	{
		$jsMessages[$code] = Loc::getMessage($code);
	}
}
?>

<div class="crm-onec-wrapper">
	<div class="ui-slider-section ui-slider-section-icon ui-slider-section-column">
		<div class="ui-slider-content-box">
			<div class="ui-icon ui-slider-icon">
				<i style="background-image: url('<?=$templateFolder?>/images/1c-logo.svg')"></i>
			</div>
			<div class="ui-slider-content-box">
				<div class="ui-slider-heading-3"><?=Loc::getMessage("CRM_1C_START_FACE_CARD_INFO_TITLE")?></div>
				<div class="crm-onec-header-futures-container">
					<div class="crm-onec-header-futures-block">
						<span class="crm-onec-header-futures">
							<span class="crm-onec-header-futures-icon icon-1"></span>
							<span class="crm-onec-header-futures-text"><?=Loc::getMessage("CRM_1C_START_FACE_CARD_ADV_1")?></span>
						</span>
					</div>
					<div class="crm-onec-header-futures-block">
						<span class="crm-onec-header-futures">
							<span class="crm-onec-header-futures-icon icon-2"></span>
							<span class="crm-onec-header-futures-text"><?=Loc::getMessage("CRM_1C_START_FACE_CARD_ADV_2")?></span>
						</span>
					</div>
					<div class="crm-onec-header-futures-block">
						<span class="crm-onec-header-futures">
							<span class="crm-onec-header-futures-icon icon-3"></span>
							<span class="crm-onec-header-futures-text"><?=Loc::getMessage("CRM_1C_START_FACE_CARD_ADV_3")?></span>
						</span>
					</div>
				</div>
				<hr class="crm-onec-separator">
				<div class="crm-onec-install-description"><?=GetMessage("CRM_1C_START_FACE_CARD_INSTALL_INFO")?></div>
			</div>
		</div>
		<div class="ui-slider-content-box">
			<hr class="crm-onec-separator">
			<div id="b24-integration-active" class="crm-onec-button">
				<div class="ui-btn ui-btn-primary" id="b24-integration-active-button">
					<?=Loc::getMessage("CRM_1C_START_FACE_CARD_DO_START")?>
				</div>

				<?if(IsModuleInstalled("bitrix24")):?>
					<div class="ui-alert ui-alert-success ui-alert-inline onec-alert">
						<span class="ui-alert-message"><?=Loc::getMessage("CRM_1C_START_FACE_CARD_WARN_TEXT")?></span>
					</div>
				<?endif?>

				<div id="b24-integration-inner-active" class="b24-integration-wrap b24-integration-left-text-block">
					<hr style="margin: 30px 0; border: none; border-top: 2px dashed #8681818c !important;">
					<?
					$sid = $APPLICATION->IncludeComponent(
						'bitrix:app.layout',
						'',
						array(
							'ID' => $arResult['APP']['ID'],
							'CODE' => $arResult['APP']['CODE'],
							'INITIALIZE' => 'N',
							'SET_TITLE' => 'N',
							'PLACEMENT_OPTIONS' => array(
								'tab' => 'face'
							),
						),
						$this,
						array('HIDE_ICONS' => 'Y')
					);
					?>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
    BX.message(<?=\Bitrix\Main\Web\Json::encode($jsMessages)?>);

    <?if($arResult['LICENSE_ACCEPTED'] === false)
	{
	    ?>BX.message({"CRM_1C_START_FACE_CARD_CONSENT_AGREEMENT":'<?=CUtil::JSEscape($arResult['LICENSE_TEXT'])?>'});<?
    }?>

    window.ONEC_APP_INACTIVE = <?=$arResult['APP_INACTIVE']?'true':'false'?>;
    window.LICENCE_RESTRICTED = <?=$arResult['RESTRICTED_LICENCE']?'true':'false'?>;
    window.LICENCE_ACCEPTED = <?=$arResult['LICENSE_ACCEPTED']?'true':'false'?>;
    window.ONEC_APP_SID = '<?=CUtil::JSEscape($sid)?>';
    window.ONEC_AJAX_URL = '<?=CUtil::JSEscape($APPLICATION->GetCurPageParam('', \Bitrix\Main\HttpRequest::getSystemParameters()))?>';

	<?
	if($arResult['RESTRICTED_LICENCE'])
    {
		CBitrix24::initLicenseInfoPopupJS();
    }
	?>
	BX.CrmStart.OnecAppPaths = <?=CUtil::PhpToJSObject($arResult['PATH_TO_APPS'])?>;
    BXOneCStart();
</script>
