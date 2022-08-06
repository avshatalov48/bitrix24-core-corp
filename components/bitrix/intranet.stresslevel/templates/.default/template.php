<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

$APPLICATION->SetTitle(Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_PAGETITLE'));

\Bitrix\Main\UI\Extension::load([
	"ui.forms",
	"ui.buttons",
	"ui.common",
	"ui.fonts.opensans"
]);

?>
<div class="intranet-stresslevel-instruction-wrapper">
	<div class="intranet-stresslevel-instruction">
		<div class="intranet-stresslevel-instruction-step">1</div>
		<div class="ui-text-2"><?=Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_STEP1')?></div>
		<div class="intranet-stresslevel-instruction-apps">
			<div class="intranet-stresslevel-instruction-apps-item intranet-stresslevel-instruction-apps-item-android"><a href="<?=Loc::getMessage("INTRANET_STRESSLEVEL_TEMPLATE_STEP1_GOOGLE_URL")?>" target="_blank"><?=Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_STEP1_GOOGLE')?></a></div>
			<div class="intranet-stresslevel-instruction-apps-item intranet-stresslevel-instruction-apps-item-ios"><a href="<?=Loc::getMessage("INTRANET_STRESSLEVEL_TEMPLATE_STEP1_APPLE_URL")?>" target="_blank"><?=Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_STEP1_APPLE')?></a></div>
		</div>
		<?
		if ($arResult['IS_CLOUD'])
		{
			?>
			<div class="intranet-stresslevel-instruction-apps-link" id="intranet-stresslevel-instruction-apps-link" for="intranet-stresslevel-instruction-apps-input-wrapper"><?=Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_STEP1_SENDSMS_HINT')?></div>
			<div class="intranet-stresslevel-instruction-apps-input-wrapper" id="intranet-stresslevel-instruction-apps-input-wrapper">
				<div class="intranet-stresslevel-instruction-apps-input">
					<div class="ui-ctl ui-ctl-textbox ui-ctl-inline">
						<input type="text" class="ui-ctl-element" id="intranet-stresslevel-instruction-input">
					</div>
					<span class="ui-btn ui-btn-primary" id="intranet-stresslevel-send-app-button"><?=Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_STEP1_SENDSMS_BUTTON')?></span>
				</div>
			</div>
			<?
		}
		?>
	</div>
	<div class="intranet-stresslevel-instruction">
		<div class="intranet-stresslevel-instruction-step">2</div>
		<div class="ui-text-2"><?=Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_STEP2')?></div>
		<img src="<?=$this->getFolder()?>/images/intranet-stresslevel-content-image-1.png" alt="">
	</div>
	<div class="intranet-stresslevel-instruction">
		<div class="intranet-stresslevel-instruction-step">3</div>
		<div class="ui-text-2"><?=Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_STEP3')?></div>
		<img src="<?=$this->getFolder()?>/images/intranet-stresslevel-content-image-2.png" alt="">
	</div>
</div>

<script>
	BX.ready(function () {
		new BX.Intranet.StressLevel.EmptyManager({});
	});
</script>
