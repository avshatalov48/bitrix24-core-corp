<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
\Bitrix\Main\UI\Extension::load(array("ui.fonts.opensans", "ui.buttons", "ui.alerts", "ui.forms"));

global $APPLICATION;
$APPLICATION->SetTitle(Loc::getMessage("CRM_1C_START_DOC_NAME"));

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */
?>

<div class="crm-onec-wrapper">
	<div class="crm-onec-section">
		<div class="crm-onec-header">
			<div class="crm-onec-header-left-block">
				<img class="crm-onec-logo" src="<?=$templateFolder?>/images/1c-logo.svg" alt="">
			</div>
			<div class="crm-onec-header-right-block">
				<div class="crm-onec-header-title"><?=Loc::getMessage("CRM_1C_START_DOC_ADV_TITLE")?></div>
				<div class="crm-onec-header-futures-container">
					<div class="crm-onec-header-futures-block">
						<span class="crm-onec-header-futures">
							<span class="crm-onec-header-futures-icon icon-1"></span>
							<span class="crm-onec-header-futures-text"><?=Loc::getMessage("CRM_1C_START_DOC_ADV_1")?></span>
						</span>
					</div>
					<div class="crm-onec-header-futures-block">
						<span class="crm-onec-header-futures">
							<span class="crm-onec-header-futures-icon icon-2"></span>
							<span class="crm-onec-header-futures-text"><?=Loc::getMessage("CRM_1C_START_DOC_ADV_2")?></span>
						</span>
					</div>
					<div class="crm-onec-header-futures-block">
						<span class="crm-onec-header-futures">
							<span class="crm-onec-header-futures-icon icon-3"></span>
							<span class="crm-onec-header-futures-text"><?=Loc::getMessage("CRM_1C_START_DOC_ADV_3")?></span>
						</span>
					</div>
				</div>
				<hr class="crm-onec-separator">
				<div class="crm-onec-install-description"><?=GetMessage("CRM_1C_START_DOC_INFO_TEXT")?></div>
			</div>
		</div>
		<hr class="crm-onec-separator">

		<div id="b24-integration-active" class="crm-onec-button">
			<div class="ui-btn ui-btn-primary" id="b24-integration-active-button">
				<?=GetMessage("CRM_1C_START_DOC_DO_START")?>
			</div>
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
							'tab' => 'base'
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

<script>
    window.ONEC_APP_INACTIVE = <?=$arResult['APP_INACTIVE']?'true':'false'?>;
    window.ONEC_APP_SID = '<?=CUtil::JSEscape($sid)?>';
    BXOneCStart("doc");
</script>


