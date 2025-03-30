<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\UI\Extension;

Loc::loadMessages(__DIR__.'/template.php');

Extension::load(['ui.buttons', 'ui.icons', 'ui.common', 'ui.alerts', 'ui.sidepanel-content', 'salescenter.manager']);
?>

<div id="salescenter-wrapper" class="salescenter-wrapper">
	<div id="salescenter-cashbox-info">
		<div class="ui-slider-section ui-slider-section-icon">
			<div class="ui-icon ui-slider-icon salescenter-<?=$arResult['handlerDescription']['code'];?>-icon">
				<i></i>
			</div>
			<div class="ui-slider-content-box">
				<div class="ui-slider-heading-4 salescenter-main-header-title-container">
					<?php
					$title = Loc::getMessage($arResult['handlerDescription']['title']);
					if (!$title)
					{
						$title = $arResult['handlerDescription']['title'];
					}
					?>
					<?= $title ?>
					<div class="salescenter-main-header-feedback-container">
						<?Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->renderFeedbackButton();?>
					</div>
				</div>
				<p class="ui-slider-paragraph"><?=Loc::getMessage($arResult['handlerDescription']['description'])?></p>
				<div class="salescenter-button-container">
					<?php if (mb_strtolower($arResult['handler']) === mb_strtolower('\Bitrix\Sale\Cashbox\CashboxCheckbox')): ?>
					<a class="ui-link ui-link-dashed" onclick="BX.Salescenter.Manager.openHowToConfigCheckboxCashBox(event);"><?=Loc::getMessage('SC_CASHBOX_CHECKBOX_LINK_CONNECT')?></a>
					<?php elseif (mb_strtolower($arResult['handler']) === mb_strtolower('\Bitrix\Sale\Cashbox\CashboxBusinessRu')): ?>
					<a class="ui-link ui-link-dashed" onclick="BX.Salescenter.Manager.openHowToConfigBusinessRuCashBox(event);"><?=Loc::getMessage('SC_CASHBOX_BUSINESSRU_LINK_CONNECT')?></a>
					<?php else:?>
					<a class="ui-link ui-link-dashed" onclick="BX.Salescenter.Manager.openHowToConfigCashBox(event);"><?=Loc::getMessage('SC_CASHBOX_LINK_CONNECT')?></a>
					<?php endif; ?>
				</div>
				<div style="padding-top: 20px;padding-bottom: 20px;" class="salescenter-button-container">
					<button class="ui-btn ui-btn-md ui-btn-primary" onclick="location.href='<?=$arResult['addUrl'];?>';"><?=Loc::getMessage("SC_ADD_CASHBOX_BUTTOM")?></button>
					<?php if (isset($arResult['connectionInfoUrl'])): ?>
					<button class="ui-btn ui-btn-md ui-btn-light-border" onclick="window.open('<?= $arResult['connectionInfoUrl'] ?>');"><?=Loc::getMessage("SC_CASHBOX_CONNECTION_INFORMATION")?></button>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<?php
		if(!empty($arResult['errors']) && is_array($arResult['errors']))
		{?>
			<div class="ui-alert ui-alert-danger">
				<span class="ui-alert-message" id="salescenter-cashbox-error"><?php
					echo implode('<br />', $arResult['errors']);
					?></span>
			</div>
			<?php
		}
		else
		{
			?>
			<div class="ui-slider-section">
			<?php if (mb_strtolower($arResult['handler']) === mb_strtolower('\Bitrix\Sale\Cashbox\CashboxCheckbox')): ?>
				<div class="ui-title-4"><?=Loc::getMessage("SC_CASHBOX_CHECKBOX_INSTRUCTION_TITLE")?></div>
				<hr class="ui-hr">
					<ul class="ui-list ui-color-medium ui-list-icon">
						<li><?=Loc::getMessage("SC_CASHBOX_CHECKBOX_INSTRUCTION_ITEM1")?></li>
						<li><?=Loc::getMessage("SC_CASHBOX_CHECKBOX_INSTRUCTION_ITEM2")?> <a href="" onclick="BX.Salescenter.Manager.openHowToSetupCheckboxCashBoxAndKeys(event);"><?=Loc::getMessage('SC_CASHBOX_CHECKBOX_INSTRUCTION_METHODS')?></a></li>
						<li><?=Loc::getMessage("SC_CASHBOX_CHECKBOX_INSTRUCTION_ITEM3")?></li>
					</ul>
					<p class="ui-color-medium"><?=Loc::getMessage("SC_CASHBOX_CHECKBOX_UKTZED_WARNING")?></p>
				</div>
			<?php elseif (
				mb_strtolower($arResult['handler']) === mb_strtolower('\Bitrix\Sale\Cashbox\CashboxBusinessRu')
				|| mb_strtolower($arResult['handler']) === mb_strtolower('\Bitrix\Sale\Cashbox\CashboxBusinessRuV5')
			): ?>
				<div class="ui-title-4"><?=Loc::getMessage("SC_CASHBOX_INSTRUCTION_TITLE")?></div>
				<hr class="ui-hr">
					<ul class="ui-list ui-color-medium ui-list-icon">
						<li><?=Loc::getMessage("SC_CASHBOX_BUSINESSRU_INSTRUCTION_ITEM1")?></li>
						<li><?=Loc::getMessage("SC_CASHBOX_BUSINESSRU_INSTRUCTION_ITEM2")?></li>
						<li><?=Loc::getMessage("SC_CASHBOX_BUSINESSRU_INSTRUCTION_ITEM3")?></li>
					</ul>
				</div>
			<?php else:?>
				<div class="ui-title-4"><?=Loc::getMessage("SC_CASHBOX_INSTRUCTION_TITLE")?></div>
				<hr class="ui-hr">
					<ul class="ui-list ui-color-medium ui-list-icon">
						<li><?=Loc::getMessage("SC_CASHBOX_INSTRUCTION_ITEM1")?></li>
						<li><?=Loc::getMessage("SC_CASHBOX_INSTRUCTION_ITEM2")?></li>
						<li><?=Loc::getMessage("SC_CASHBOX_INSTRUCTION_ITEM3")?></li>
						<li><?=Loc::getMessage("SC_CASHBOX_INSTRUCTION_ITEM4")?></li>
						<li><?=Loc::getMessage("SC_CASHBOX_INSTRUCTION_ITEM5")?></li>
					</ul>
				</div>
			<?php endif; ?>

			<?php
		}
		?>
	</div>
</div>
