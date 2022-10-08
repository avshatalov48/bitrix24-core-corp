<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'date',
	'ui.progressbar',
	'ui.progressround',
	'ui.buttons',
	'sidepanel',
]);

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");
$APPLICATION->SetTitle(Loc::getMessage("CRM_ML_MODEL_LIST_SCORING_TITLE"));

\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();
\Bitrix\UI\Toolbar\Facade\Toolbar::addButton([
	"text" => Loc::getMessage("CRM_ML_MODEL_LIST_HELP"),
	"color" => \Bitrix\UI\Buttons\Color::LIGHT_BORDER,
	"click" => new \Bitrix\UI\Buttons\JsHandler(
		"BX.Crm.scoringModelList.showHelp"
	),
]);

if(!$arResult["SCORING_ENABLED"] && \Bitrix\Main\Loader::includeModule("bitrix24"))
{
	$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", []);
}

?>
<div class="crm-ml-entity-content">
	<div class="crm-ml-entity-content-img-block">
		<div class="crm-ml-entity-content-img"></div>
		<div class="crm-ml-entity-content-overlay" id="crm-ml-entity-content-overlay"></div>
		<div class="crm-ml-entity-progress" id="crm-ml-entity-progress"></div>
	</div>
	<div class="crm-ml-entity-content-text-block">
		<div class="crm-ml-entity-content-text"><?= Loc::getMessage("CRM_ML_MODEL_LIST_SCORING_TITLE")?></div>
		<div class="crm-ml-entity-content-decs-block">
			<div class="crm-ml-entity-content-desc"><?= Loc::getMessage("CRM_ML_MODEL_LIST_SCORING_DESCRIPTION_P1")?></div>
			<div class="crm-ml-entity-content-desc"><?= Loc::getMessage("CRM_ML_MODEL_LIST_SCORING_DESCRIPTION_P2")?></div>
		</div>
	</div>
	<div class="crm-ml-entity-content-text-block">
		<div class="crm-ml-entity-content-text"><?= Loc::getMessage("CRM_ML_MODEL_LIST_AVAILABLE_MODELS") ?></div>
		<div id="model-list" class="crm-ml-entity-content-decs-block"></div>
	</div>
</div>
<script>
	BX.message({
		"CRM_ML_MODEL_LIST_CONFIRMATION": '<?= GetMessageJS("CRM_ML_MODEL_LIST_CONFIRMATION")?>',
		"CRM_ML_MODEL_LIST_BUTTON_DISABLE": '<?= GetMessageJS("CRM_ML_MODEL_LIST_BUTTON_DISABLE")?>',
		"CRM_ML_MODEL_LIST_BUTTON_CANCEL": '<?= GetMessageJS("CRM_ML_MODEL_LIST_BUTTON_CANCEL")?>',
		"CRM_ML_MODEL_LIST_BUTTON_TRAIN_FREE_OF_CHARGE": '<?= GetMessageJS("CRM_ML_MODEL_LIST_BUTTON_TRAIN_FREE_OF_CHARGE")?>',
		"CRM_ML_MODEL_LIST_LEAD_SCORING_DISABLE": '<?= GetMessageJS("CRM_ML_MODEL_LIST_LEAD_SCORING_DISABLE")?>',
		"CRM_ML_MODEL_LIST_DEAL_SCORING_DISABLE": '<?= GetMessageJS("CRM_ML_MODEL_LIST_DEAL_SCORING_DISABLE")?>',
		"CRM_ML_MODEL_LIST_DISABLE_LEAD_SCORING": '<?= GetMessageJS("CRM_ML_MODEL_LIST_DISABLE_LEAD_SCORING")?>',
		"CRM_ML_MODEL_LIST_DISABLE_DEAL_SCORING": '<?= GetMessageJS("CRM_ML_MODEL_LIST_DISABLE_DEAL_SCORING")?>',
		"CRM_ML_MODEL_LIST_SCORING_TRAINING_IN_PROCESS": '<?= GetMessageJS("CRM_ML_MODEL_LIST_SCORING_TRAINING_IN_PROCESS")?>',
		"CRM_ML_MODEL_LIST_SCORING_REENABLE_WARNING": '<?= GetMessageJS("CRM_ML_MODEL_LIST_SCORING_REENABLE_WARNING")?>',
		"CRM_ML_MODEL_LIST_SCORING_ERROR_TOO_SOON_2": '<?= GetMessageJS("CRM_ML_MODEL_LIST_SCORING_ERROR_TOO_SOON_2")?>',
		"CRM_ML_MODEL_LIST_SCORING_ENOUGH_DATA": '<?= GetMessageJS("CRM_ML_MODEL_LIST_SCORING_ENOUGH_DATA")?>',
		"CRM_ML_MODEL_LIST_SCORING_NOT_ENOUGH_DATA": '<?= GetMessageJS("CRM_ML_MODEL_LIST_SCORING_NOT_ENOUGH_DATA")?>',
		"CRM_ML_MODEL_LIST_SCORING_MODEL_READY": '<?= GetMessageJS("CRM_ML_MODEL_LIST_SCORING_MODEL_READY")?>',
		"CRM_ML_MODEL_LIST_SCORING_MODEL_QUALITY": '<?= GetMessageJS("CRM_ML_MODEL_LIST_SCORING_MODEL_QUALITY")?>',
		"CRM_ML_MODEL_LIST_SCORING_MODEL_TRAINING_DATE": '<?= GetMessageJS("CRM_ML_MODEL_LIST_SCORING_MODEL_TRAINING_DATE")?>',
		"CRM_SCORING_LICENSE_TITLE": '<?= CUtil::JSEscape(\Bitrix\Crm\Ml\Scoring::getLicenseInfoTitle())?>',
		"CRM_SCORING_LICENSE_TEXT": '<?= CUtil::JSEscape(\Bitrix\Crm\Ml\Scoring::getLicenseInfoText())?>',
	});

	BX.Crm.scoringModelList = new BX.Crm.Scoring.ModelList({
		container: BX("model-list"),
		models: <?= Json::encode($arResult["MODELS"])?>,
		trainingList: <?= Json::encode($arResult["TRAINING_LIST"])?>,
		trainingErrors: <?= Json::encode($arResult["TRAINING_ERRORS"])?>,
		scoringEnabled: <?= $arResult["IS_SCORING_ENABLED"] ? 'true' : 'false'?>,
	});
</script>
