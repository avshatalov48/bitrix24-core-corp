<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

/** @var CBitrixComponentTemplate $this */

\Bitrix\Main\UI\Extension::load([
	"ui.design-tokens",
	"ui.fonts.opensans",
	"amcharts",
	"amcharts_serial",
	"crm_activity_planner",
	"date",
	"ui.buttons",
	"ui.hint",
	"ui.feedback.form",
]);

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background pagetitle-toolbar-field-view");

$settingsButton = new \Bitrix\UI\Buttons\SettingsButton([
	"classList" => ($arResult["MODEL"] && $arResult["MODEL"]->getState() === \Bitrix\Ml\Model::STATE_READY) ? [] : ["crm-ml-button-hidden"],
	"menu" => [
		"items" => [
			[
				"text" => $arResult["ITEM"]["ENTITY_TYPE_ID"] == CCrmOwnerType::Lead ? Loc::getMessage("CRM_ML_LEAD_SCORING_DISABLE") : Loc::getMessage("CRM_ML_DEAL_SCORING_DISABLE"),
				"onclick" => new \Bitrix\UI\Buttons\JsHandler(
					"BX.Crm.entityDetailView.onDisableScoringClick",
					"BX.Crm.entityDetailView"
				)
			],
		],
	],
]);
\Bitrix\UI\Toolbar\Facade\Toolbar::addButton($settingsButton);

\Bitrix\UI\Toolbar\Facade\Toolbar::addButton([
	"text" => Loc::getMessage("CRM_ML_HELP"),
	"color" => \Bitrix\UI\Buttons\Color::LIGHT_BORDER,
	"click" => new \Bitrix\UI\Buttons\JsHandler(
		"BX.Crm.entityDetailView.showHelp"
	),
]);

\Bitrix\UI\Toolbar\Facade\Toolbar::addButton([
	"text" => Loc::getMessage("CRM_ML_FEEDBACK"),
	"color" => \Bitrix\UI\Buttons\Color::LIGHT_BORDER,
	"click" => new \Bitrix\UI\Buttons\JsHandler(
		"BX.Crm.entityDetailView.showFeedbackForm",
		"BX.Crm.entityDetailView"
	),
]);

\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();

if(!$arResult["SCORING_ENABLED"] && \Bitrix\Main\Loader::includeModule("bitrix24"))
{
	$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", []);
}

?>
<div id="crm-ml-entity-detail"></div>

<script>
	BX.message({
		"CRM_ML_MODEL_TRAINING_DEALS": '<?= GetMessageJS("CRM_ML_MODEL_TRAINING_DEALS")?>',
		"CRM_ML_MODEL_TRAINING_LEADS": '<?= GetMessageJS("CRM_ML_MODEL_TRAINING_LEADS")?>',
		"CRM_ML_MODEL_FUTURE_DEAL_FORECAST": '<?= GetMessageJS("CRM_ML_MODEL_FUTURE_DEAL_FORECAST")?>',
		"CRM_ML_MODEL_FUTURE_LEAD_FORECAST": '<?= GetMessageJS("CRM_ML_MODEL_FUTURE_LEAD_FORECAST")?>',
		"CRM_ML_DEAL_SUCCESS_PROBABILITY": '<?= GetMessageJS("CRM_ML_DEAL_SUCCESS_PROBABILITY")?>',
		"CRM_ML_LEAD_SUCCESS_PROBABILITY": '<?= GetMessageJS("CRM_ML_LEAD_SUCCESS_PROBABILITY")?>',
		"CRM_ML_DEAL_FORECAST": '<?= GetMessageJS("CRM_ML_DEAL_FORECAST")?>',
		"CRM_ML_LEAD_FORECAST": '<?= GetMessageJS("CRM_ML_LEAD_FORECAST")?>',
		"CRM_ML_FORECAST_DYNAMICS": '<?= GetMessageJS("CRM_ML_FORECAST_DYNAMICS")?>',
		"CRM_ML_MODEL_MODEL_WILL_BE_TRAINED_AGAIN": '<?= GetMessageJS("CRM_ML_MODEL_MODEL_WILL_BE_TRAINED_AGAIN")?>',
		"CRM_ML_MODEL_MODEL_WILL_BE_TRAINED_IN_DAYS": '<?= GetMessageJS("CRM_ML_MODEL_MODEL_WILL_BE_TRAINED_IN_DAYS")?>',
		"CRM_ML_MODEL_SUCCESSFUL_DEALS_IN_TRAINING": '<?= GetMessageJS("CRM_ML_MODEL_SUCCESSFUL_DEALS_IN_TRAINING")?>',
		"CRM_ML_MODEL_SUCCESSFUL_LEADS_IN_TRAINING": '<?= GetMessageJS("CRM_ML_MODEL_SUCCESSFUL_LEADS_IN_TRAINING")?>',
		"CRM_ML_MODEL_FAILED_DEALS_IN_TRAINING": '<?= GetMessageJS("CRM_ML_MODEL_FAILED_DEALS_IN_TRAINING")?>',
		"CRM_ML_MODEL_FAILED_LEADS_IN_TRAINING": '<?= GetMessageJS("CRM_ML_MODEL_FAILED_LEADS_IN_TRAINING")?>',
		"CRM_ML_SCORE_BALLOON": '<?= GetMessageJS("CRM_ML_SCORE_BALLOON")?>',
		"CRM_ML_INFLUENCING_EVENT": '<?= GetMessageJS("CRM_ML_INFLUENCING_EVENT")?>',
		"CRM_ML_MODEL_QUALITY": '<?= GetMessageJS("CRM_ML_MODEL_QUALITY")?>',
		"CRM_ML_SUCCESS_PROBABILITY_LOW": '<?= GetMessageJS("CRM_ML_SUCCESS_PROBABILITY_LOW")?>',
		"CRM_ML_SUCCESS_PROBABILITY_MEDIUM": '<?= GetMessageJS("CRM_ML_SUCCESS_PROBABILITY_MEDIUM")?>',
		"CRM_ML_SUCCESS_PROBABILITY_HIGH": '<?= GetMessageJS("CRM_ML_SUCCESS_PROBABILITY_HIGH")?>',
		"CRM_ML_MODEL_QUALITY_LOW": '<?= GetMessageJS("CRM_ML_MODEL_QUALITY_LOW")?>',
		"CRM_ML_MODEL_QUALITY_MEDIUM": '<?= GetMessageJS("CRM_ML_MODEL_QUALITY_MEDIUM")?>',
		"CRM_ML_MODEL_QUALITY_HIGH": '<?= GetMessageJS("CRM_ML_MODEL_QUALITY_HIGH")?>',
		"CRM_ML_MODEL_NO_EVENTS_YET_DEAL": '<?= GetMessageJS("CRM_ML_MODEL_NO_EVENTS_YET_DEAL")?>',
		"CRM_ML_MODEL_NO_EVENTS_YET_LEAD": '<?= GetMessageJS("CRM_ML_MODEL_NO_EVENTS_YET_LEAD")?>',
		"CRM_ML_MODEL_EVENT_UPDATE_LEAD": '<?= GetMessageJS("CRM_ML_MODEL_EVENT_UPDATE_LEAD")?>',
		"CRM_ML_MODEL_EVENT_UPDATE_DEAL": '<?= GetMessageJS("CRM_ML_MODEL_EVENT_UPDATE_DEAL")?>',
		"CRM_ML_SCORING_DESCRIPTION": '<?= GetMessageJS("CRM_ML_SCORING_DESCRIPTION")?>',
		"CRM_ML_SCORING_NOT_ENOUGH_DATA": '<?= GetMessageJS("CRM_ML_SCORING_NOT_ENOUGH_DATA")?>',
		"CRM_ML_SCORING_ERROR_TOO_SOON_2": '<?= GetMessageJS("CRM_ML_SCORING_ERROR_TOO_SOON_2")?>',
		"CRM_ML_SCORING_CAN_START_TRAINING": '<?= GetMessageJS("CRM_ML_SCORING_CAN_START_TRAINING")?>',
		"CRM_ML_SCORING_TRAIN_FREE_OF_CHARGE": '<?= GetMessageJS("CRM_ML_SCORING_TRAIN_FREE_OF_CHARGE")?>',
		"CRM_ML_CONFIRMATION": '<?= GetMessageJS("CRM_ML_CONFIRMATION")?>',
		"CRM_ML_BUTTON_DISABLE": '<?= GetMessageJS("CRM_ML_BUTTON_DISABLE")?>',
		"CRM_ML_BUTTON_CANCEL": '<?= GetMessageJS("CRM_ML_BUTTON_CANCEL")?>',
		"CRM_ML_DISABLE_LEAD_SCORING": '<?= GetMessageJS("CRM_ML_DISABLE_LEAD_SCORING")?>',
		"CRM_ML_DISABLE_DEAL_SCORING": '<?= GetMessageJS("CRM_ML_DISABLE_DEAL_SCORING")?>',
		"CRM_ML_SCORING_REENABLE_WARNING": '<?= GetMessageJS("CRM_ML_SCORING_REENABLE_WARNING")?>',
		"CRM_ML_SCORING_DESCRIPTION_TITLE_2": '<?= GetMessageJS("CRM_ML_SCORING_DESCRIPTION_TITLE_2")?>',
		"CRM_ML_SCORING_DESCRIPTION_P1": '<?= GetMessageJS("CRM_ML_SCORING_DESCRIPTION_P1")?>',
		"CRM_ML_SCORING_DESCRIPTION_P2_2": '<?= GetMessageJS("CRM_ML_SCORING_DESCRIPTION_P2_2")?>',
		"CRM_SCORING_LICENSE_TITLE": '<?= \CUtil::JSEscape(\Bitrix\Crm\Ml\Scoring::getLicenseInfoTitle())?>',
		"CRM_SCORING_LICENSE_TEXT": '<?= \CUtil::JSEscape(\Bitrix\Crm\Ml\Scoring::getLicenseInfoText())?>',
		"CRM_ML_SCORING_PREDICTION_HINT": '<?= GetMessageJS("CRM_ML_SCORING_PREDICTION_HINT")?>',
		"CRM_ML_SCORING_MODEL_QUALITY_HINT": '<?= GetMessageJS("CRM_ML_SCORING_MODEL_QUALITY_HINT")?>',
	});

	BX.ready(function()
	{
		BX.Crm.entityDetailView = new BX.Crm.MlEntityDetail({
			node: BX("crm-ml-entity-detail"),
			settingsButtonId: "<?= $settingsButton->getUniqId()?>",
			model: <?= \Bitrix\Main\Web\Json::encode($arResult["MODEL"])?>,
			mlModelExists: <?= ($arResult["ML_MODEL_EXISTS"]) ? "true" : "false" ?>,
			canStartTraining: <?= ($arResult["CAN_START_TRAINING"]) ? "true" : "false" ?>,
			trainingError: <?= \Bitrix\Main\Web\Json::encode($arResult["TRAINING_ERROR"])?>,
			currentTraining: <?= \Bitrix\Main\Web\Json::encode($arResult["CURRENT_TRAINING"])?>,
			entity: <?= CUtil::PhpToJSObject($arResult["ITEM"])?>,
			predictionHistory: <?= \Bitrix\Main\Web\Json::encode($arResult["PREDICTION_HISTORY"])?>,
			associatedEvents: <?= \Bitrix\Main\Web\Json::encode($arResult["ASSOCIATED_EVENTS"])?>,
			trainingHistory: <?= \Bitrix\Main\Web\Json::encode($arResult["TRAINING_HISTORY"])?>,
			errors: <?= \Bitrix\Main\Web\Json::encode($arResult["ERRORS"])?>,
			scoringEnabled: <?= ($arResult["SCORING_ENABLED"]) ? "true" : "false" ?>,
			feedbackParams: <?= \Bitrix\Main\Web\Json::encode([
					'id' => $arResult["FEEDBACK_PARAMS"]["ID"],
					'portal' => $arResult["FEEDBACK_PARAMS"]["PORTAL"],
					'presets' => $arResult["FEEDBACK_PARAMS"]["PRESETS"],
					'form' => $arResult["FEEDBACK_PARAMS"]["FORM"],
					'title' => Loc::getMessage("CRM_ML_FEEDBACK"),
				])?>,
		});
		BX.Crm.entityDetailView.show();
	});
</script>
