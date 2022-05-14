<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? \Bitrix\Main\UI\Extension::load("ui.fonts.opensans"); ?>

<div class="intranet-license-restriction-container">
	<div class="intranet-license-restriction-title">
		<div class="intranet-license-restriction-title-item"><?=GetMessage("LICENSE_RESTR_TITLE")?></div>
	</div>
	<div class="intranet-license-restriction-logo"></div>
	<div class="intranet-license-restriction-desc">
		<div class="intranet-license-restriction-desc-item"><?=GetMessage("LICENSE_RESTR_TEXT1_1")?></div>
	</div>
</div>
<div class="intranet-license-restriction-container">
	<div class="intranet-license-restriction-info">
		<?=GetMessage("LICENSE_RESTR_TEXT2", array("#NUM#" => $arResult["NUM_AVAILABLE_USERS"]))?>
	</div>
	<div class="intranet-license-restriction-info"><?=GetMessage("LICENSE_RESTR_TEXT3_1", array("#NUM#" => $arResult["NUM_ALL_USERS"]))?></div>
</div>
<div class="intranet-license-restriction-warning">
	<div class="intranet-license-restriction-warning-text"><?=GetMessage("LICENSE_RESTR_TEXT4")?></div>
</div>