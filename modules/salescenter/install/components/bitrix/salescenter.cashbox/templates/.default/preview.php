<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\UI\Extension;

Loc::loadMessages(__DIR__.'/template.php');

Extension::load(['ui.buttons', 'ui.icons', 'ui.common', 'ui.alerts',]);
?>

<div class="salescenter-cashbox-wrapper">
	<div id="salescenter-wrapper" class="salescenter-wrapper">
		<div id="salescenter-cashbox-info">
			<div style="padding: 15px; margin-bottom: 15px;" class="ui-bg-color-white">
				<div class="salescenter-main-header">

					<div class="salescenter-main-header-left-block">
						<div class="salescenter-logo-container">
							<div class="salescenter-<?=$arResult['handlerDescription']['code'];?>-icon ui-icon" style="width:97px;"><i></i></div>
						</div>
					</div>

					<div class="salescenter-main-header-right-block">

						<div class="salescenter-main-header-title-container">
							<div style="margin-bottom: 15px;" class="ui-title-3"><?=Loc::getMessage($arResult['handlerDescription']['title'])?></div>
							<div class="salescenter-main-header-feedback-container">
								<?Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->renderFeedbackButton();?>
							</div>
						</div>
						<hr class="ui-hr" style="margin-bottom: 15px;">
						<div class="ui-text-2" style="margin-bottom: 20px;"><?=Loc::getMessage($arResult['handlerDescription']['description'])?></div>
						<div class="salescenter-button-container">
							<a class="ui-link ui-link-dashed" onclick="BX.Salescenter.Manager.openHowToConfigCashBox(event);"><?=Loc::getMessage('SC_CASHBOX_LINK_CONNECT')?></a>
						</div>
						<div style="padding-top: 20px;padding-bottom: 20px;" class="salescenter-button-container">
							<button class="ui-btn ui-btn-md ui-btn-primary" onclick="location.href='<?=$arResult['addUrl'];?>';"><?=Loc::getMessage("SC_ADD_CASHBOX_BUTTOM")?></button>
						</div>

					</div>

				</div>
			</div>

			<?php
			if(is_array($arResult['errors']) && !empty($arResult['errors']))
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
			<div style="padding: 15px; margin-bottom: 15px;" class="ui-bg-color-white">
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
			<?php
			}
			?>
		</div>
	</div>
</div>