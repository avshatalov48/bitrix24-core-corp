<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */

\Bitrix\Main\UI\Extension::load(['ui.design-tokens']);
?>
<div class="bx-crm-widget-form-config-wrapper <?=($arResult['REMOVE_COPYRIGHT'] == 'Y' ? 'bx-crm-widget-form-copyright-disabled' : '')?>">
	<div id="bx24_form_container_<?=htmlspecialcharsbx($arResult['FORM_ID'])?>" class="bx-crm-widget-form-config-sidebar">
		<div class="bx-crm-widget-form-config-sidebar-inner">
			<div class="bx-crm-widget-form-config-sidebar-header">
				<span class="bx-crm-widget-form-config-sidebar-hamburger">
					<span class="bx-crm-widget-form-config-sidebar-hamburger-item"></span>
				</span>
				<span class="bx-crm-widget-form-config-sidebar-message">
					<span data-bx-crm-widget-caption="" class="bx-crm-widget-form-config-sidebar-message-item"><?=htmlspecialcharsbx($arResult['TITLE'])?></span>
				</span>
				<div class="bx-crm-widget-form-config-sidebar-rollup">
					<span class="bx-crm-widget-form-config-sidebar-rollup-item"></span>
				</div>
				<span class="bx-crm-widget-form-config-sidebar-close">
					<span class="bx-crm-widget-form-config-sidebar-close-item" onclick="BX.SiteButton.classes.remove(document.getElementById('bx24_form_container_<?=htmlspecialcharsbx($arResult['FORM_ID'])?>'), 'open-sidebar'); BX.SiteButton.onWidgetClose();"></span>
				</span>
			</div>

			<div class="bx-crm-widget-form-config-sidebar-info">

				<div id="bx24_form_inline_loader_container_<?=htmlspecialcharsbx($arResult['FORM_ID'])?>" class="">
					<?=$arResult['SCRIPT_LOADER']?>
				</div>

			</div>

			<div class="bx-crm-widget-form-config-sidebar-chat-container">
				<div class="bx-crm-widget-form-config-sidebar-chat-border"></div>
				<?if ($arResult['REMOVE_COPYRIGHT'] != 'Y'):?>
				<div class="bx-crm-widget-form-config-sidebar-logo">
					<a target="_blank" href="<?=htmlspecialcharsbx($arResult['REF_LINK'])?>">
						<span class="bx-crm-widget-form-config-sidebar-logo-text"><?=Loc::getMessage('CRM_BUTTON_WEBFORM_WIDGET_LOGO_TEXT')?></span>
						<span class="bx-crm-widget-form-config-sidebar-logo-bx"><?=Loc::getMessage('CRM_BUTTON_WEBFORM_WIDGET_LOGO_BITRIX')?></span>
						<span class="bx-crm-widget-form-config-sidebar-logo-24">24</span>
						<?if(!in_array(LANGUAGE_ID, array('ru', 'ua', 'kz', 'by'))):?>
							<span class="bx-crm-widget-form-config-sidebar-logo-text">, #1 Free CRM</span>
						<?endif;?>
					</a>
				</div>
				<?endif;?>
			</div>
		</div>
	</div>
</div>