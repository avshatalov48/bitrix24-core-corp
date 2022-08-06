<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	"voximplant.common",
	"ui.buttons",
	"ui.design-tokens",
])
?>

<div class="voximplant-slider-pagetitle-wrap">
	<div class="voximplant-slider-pagetitle">
		<span><?= Loc::getMessage("VOX_CLOSING_DOCS_REQUEST_TITLE") ?></span>
	</div>
</div>

<div class="voximplant-container voximplant-options-popup">
	<form name="request-docs">
		<div class="voximplant-control-row">
			<div class="voximplant-control-subtitle"><?= Loc::getMessage("VOX_CLOSING_DOCS_PERIOD") ?></div>
			<select name="PERIOD" class="voximplant-control-select">
				<? foreach ($arResult["PERIODS"] as $date): ?>
					<?
						$month = $date->format("m");
						$year = $date->format("Y");
					?>
					<option value="<?=$year . "-" . $month?>">
						<?= Loc::getMessage("VOX_CLOSING_DOCS_MONTH_" . $month) . " " . $year?>
					</option>
				<? endforeach ?>
			</select>
		</div>
		<div class="voximplant-control-row">
			<div class="voximplant-control-subtitle"><?= Loc::getMessage("VOX_CLOSING_DOCS_YOUR_ADDRESS") ?></div>
			<span class="voximplant-control-input">
				<span class="voximplant-docsrequest-address-index">
					<input name="ADDRESS_INDEX" type="text" value="<?=htmlspecialcharsbx($arResult["INDEX"])?>" placeholder="<?= Loc::getMessage("VOX_CLOSING_DOCS_PLACEHOLDER_INDEX") ?>" maxlength="6" class="voximplant-docsrequest-address-input">
				</span>
				<span class="voximplant-docsrequest-address-address">
					<input name="ADDRESS" type="text" value="<?=htmlspecialcharsbx($arResult["ADDRESS"])?>" placeholder="<?= Loc::getMessage("VOX_CLOSING_DOCS_PLACEHOLDER_ADDRESS") ?>" class="voximplant-docsrequest-address-input">
				</span>
			</span>
		</div>
		<div class="voximplant-control-row">
			<div class="voximplant-control-subtitle"><?= Loc::getMessage("VOX_CLOSING_DOCS_EMAIL") ?></div>
			<input name="EMAIL" type="text" class="voximplant-control-input" value="<?=htmlspecialcharsbx($arResult["CURRENT_USER_EMAIL"])?>">
		</div>
	</form>
</div>
<div class="voximplant-button-panel">
	<button id="docs-request-submit" class="ui-btn ui-btn-success"><?= Loc::getMessage("VOX_CLOSING_DOCS_REQUEST") ?></button>
	<button id="docs-request-cancel" class="ui-btn ui-btn-link"><?= Loc::getMessage("VOX_CLOSING_DOCS_CANCEL") ?></button>
</div>

<script>
	BX.message({
		"VOX_CLOSING_DOCS_REQUEST_SENT": '<?= GetMessageJS("VOX_CLOSING_DOCS_REQUEST_SENT")?>'
	});

	new BX.Voximplant.DocsRequest({
		submitButton: BX("docs-request-submit"),
		cancelButton: BX("docs-request-cancel")
	});
</script>
