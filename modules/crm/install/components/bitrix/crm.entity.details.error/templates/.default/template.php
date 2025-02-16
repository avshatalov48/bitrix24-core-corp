<?php


if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityPopupComponent $component */

use \Bitrix\Crm\Component\EntityDetails\Error;

\Bitrix\Main\UI\Extension::load("sidepanel");
?>
<div class="crm-entity-details-page-background">
	<div class = "crm-entity-details-error-error-background">
		<?=$arResult['IMAGE']?>
		<div class="crm-entity-details-error-message-close-button">
			<p class="crm-entity-details-error-message-text"><?= $arResult['ERROR_MESSAGE']?></p>
			<button class="crm-entity-details-error-close-button" onclick="BX.SidePanel.Instance.close()"><?=GetMessage("CRM_ENTITY_DETAIL_CLOSE_BUTTON")?></button>
		</div>
	</div>
</div>
<script>
	BX.ready(function() {
		const elements = document.querySelectorAll('.crm-iframe-header, .ui-toolbar');
		elements.forEach(function(element) {
			element.remove();
		});
	});
</script>