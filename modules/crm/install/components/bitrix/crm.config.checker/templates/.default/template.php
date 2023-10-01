<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");

Extension::load([
	"ui.icons",
	"ui.switcher",
	"sidepanel",
	"ui.forms",
	"ui.buttons",
	"ui.fonts.opensans",
]);

$config = \CUserOptions::GetOption("crm", "config_checker", ["lastTime" => null, "show" => "Y"]);
$request = \Bitrix\Main\Context::getCurrent()->getRequest();
$isSlider = $request->getQuery("IFRAME") === "Y" && $request->getQuery("IFRAME_TYPE") === "SIDE_SLIDER";
if ($isSlider && !$arResult["IS_FINISHED"])
{
	$this->setViewTarget("inside_pagetitle");
	?>
	<div class="crm-wizard-switcher">
				<span data-switcher="<?=htmlspecialcharsbx(Json::encode([
					"id" => "iteratorSwitcher",
					"checked" => $config["show"] !== "N"
				]))?>" class="ui-switcher"></span>
	</div>
	<?
	$this->endViewTarget();
}

?>
<div class="crm-wizard-wrap">
		<div class="crm-wizard-block crm-wizard-block-desc <?=($arResult["CODE"] === "default" ? "crm-default-block-desc" : "")?>">
			<span class="crm-wizard-icon" style="background-image: url('<?=htmlspecialcharsbx($arResult["ICON"])?>')"></span>
			<div class="crm-wizard-section">
				<div class="crm-wizard-header">
					<?=htmlspecialcharsbx($arResult["TITLE"])?>
				</div>
				<div class="crm-wizard-desc">
					<span class="crm-wizard-desc-text">
						<?=htmlspecialcharsbx($arResult["DESCRIPTION"])?>
					</span>
				</div>
			</div>
		</div>
		<div class="crm-wizard-block">
			<div class="crm-wizard-subject">
				<span class="crm-wizard-subject-text"><?= Loc::getMessage("CMR_CONFIG_CHECKER_TITLE") ?></span>
			</div>
			<div class="crm-wizard-settings">
				<?
				$number = 1;
				foreach ($arResult["STEPS"] as &$step)
				{
					$step["NUMBER"] = $number++;
					$step["NODE_ID"] = "bx-configurator-step-" . $step["NUMBER"];
					?>
					<div class="crm-wizard-settings-block" id="<?=htmlspecialcharsbx($step["NODE_ID"])?>" data-bx-status="not-checked">
						<div class="crm-wizard-settings-block-inner">
							<div class="crm-wizard-settings-name-block">
								<span class="crm-wizard-settings-name"><?=htmlspecialcharsbx($step["TITLE"])?></span>
								<span class="crm-wizard-settings-detail"><?=htmlspecialcharsbx($step["DESCRIPTION"])?></span>
							</div>
							<div class="crm-wizard-settings-control">
								<button class="ui-btn ui-btn-sm ui-btn-link crm-wizard-settings-control-checked" data-bx-control="ok"><?= Loc::getMessage("CRM_CONFIG_CHECKER_DONE") ?></button>
								<button class="ui-btn ui-btn-sm" data-bx-control="in-progress"><?= Loc::getMessage("CRM_CONFIG_CHECKER_INPROCESS") ?></button>
								<button class="ui-btn ui-btn-sm" data-bx-control="not-checked"><?= Loc::getMessage("CRM_CONFIG_CHECKER_NOT_CHECKED") ?></button>
								<button class="ui-btn ui-btn-sm ui-btn-link" data-bx-control="not-actual"><?= Loc::getMessage("CRM_CONFIG_CHECKER_NOT_ACTUAL") ?></button>
								<?if (!empty($step["URL"])) { ?><button class="ui-btn ui-btn-sm ui-btn-primary" data-bx-control="not-correct" data-bx-url="<?=htmlspecialcharsbx($step["URL"])?>"><?= Loc::getMessage("CRM_CONFIG") ?></button><? } ?>
							</div>
						</div>
						<div class="crm-wizard-settings-block-hidden">
							<div class="crm-wizard-settings-block-hidden-inner" data-bx-block="info-block"></div>
						</div>
					</div>
					<?
				}
				?>
			</div>
		</div>
<?$APPLICATION->IncludeComponent("bitrix:ui.button.panel", "", [
	"BUTTONS" => (
		[
			[
				"ID" => "wizard_start",
				"TYPE" => "apply",
				"NAME" => "wizard_start",
				"VALUE" => "Y",
				"CAPTION" => Loc::getMessage("CRM_BUTTON_CHECK")
			],
			($isSlider ? [
				"ID" => "wizard_close",
				"TYPE" => "close",
				"NAME" => "wizard_close",
				"VALUE" => "Y"
			] : [] )
		]
	)
]);?>
<script>
		BX.message(<?=CUtil::phpToJsObject(Loc::loadLanguageFile(__FILE__))?>);
		void new BX.Crm.Iterator('<?=CUtil::JSEscape($arResult["ID"])?>',
			{
				buttons: {
					start: document.querySelector('#wizard_start'),
					close: document.querySelector('#wizard_close')
				},
				started: <?=($arResult["IS_STARTED"] ? "true" : "false")?>,
				finished: <?=($arResult["IS_FINISHED"] ? "true" : "false")?>,
				steps: <?=CUtil::phpToJsObject($arResult["STEPS"])?>,
				signedParameters: '<?= $this->getComponent()->getSignedParameters() ?>',
				componentName: '<?= $this->getComponent()->getName() ?>'
			}
		);
		setTimeout(function()
		{
			var switchIteratorButton = BX.UI.Switcher.getById("iteratorSwitcher");
			if (switchIteratorButton)
			{
				BX.addCustomEvent(switchIteratorButton, "toggled", function() {
					BX.userOptions.save("crm", "config_checker", "show", switchIteratorButton.isChecked() ? "Y" : "N");
				});
			}
		}, 500);
</script>
</div>
