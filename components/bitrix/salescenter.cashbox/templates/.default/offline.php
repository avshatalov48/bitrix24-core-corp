<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\UI\Extension;

Loc::loadMessages(__DIR__.'/template.php');

Extension::load(['ui.buttons', 'ui.icons', 'ui.common', 'ui.alerts', 'salescenter.manager', 'ui.sidepanel-content']);

$APPLICATION->SetTitle(Loc::getMessage('SC_CASHBOX_OFFLINE_TITLE'));
?>

<?php $this->setViewTarget("inside_pagetitle_below", 100); ?>
<div class="salescenter-main-header-feedback-container">
	<?Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->renderFeedbackButton();?>
</div>
<?php $this->endViewTarget(); ?>

<div id="salescenter-wrapper" class="salescenter-wrapper">
	<div id="salescenter-cashbox-info">
		<div class="ui-slider-section ui-slider-section-icon">
			<div class="ui-icon ui-slider-icon salescenter-offline-icon">
				<i></i>
			</div>
			<div class="ui-slider-content-box">
				<div class="ui-slider-heading-3"><?=Loc::getMessage('SC_CASHBOX_OFFLINE_DESCRIPTION_TITLE')?></div>
				<p class="ui-slider-paragraph"><?=Loc::getMessage('SC_CASHBOX_OFFLINE_DESCRIPTION_DESC1')?></p>
				<p class="ui-slider-paragraph"><?=Loc::getMessage('SC_CASHBOX_OFFLINE_DESCRIPTION_DESC2')?></p>
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
			<div class="ui-slider-section">
				<div class="ui-slider-heading-4"><?=Loc::getMessage("SC_CASHBOX_OFFLINE_INSTRUCTIION_TITLE")?></div>
				<ol style="margin-bottom: 40px;" class="ui-slider-list">
					<li class="ui-slider-list-item"><?=Loc::getMessage("SC_CASHBOX_OFFLINE_INSTRUCTIION_ITEM1")?></li>
					<li class="ui-slider-list-item"><?=Loc::getMessage("SC_CASHBOX_OFFLINE_INSTRUCTIION_ITEM2")?></li>
					<li class="ui-slider-list-item"><?=Loc::getMessage("SC_CASHBOX_OFFLINE_INSTRUCTIION_ITEM3")?></li>
					<li class="ui-slider-list-item"><?=Loc::getMessage("SC_CASHBOX_OFFLINE_INSTRUCTIION_ITEM4")?></li>
					<li class="ui-slider-list-item"><?=Loc::getMessage("SC_CASHBOX_OFFLINE_INSTRUCTIION_ITEM5")?></li>
					<li class="ui-slider-list-item"><?=Loc::getMessage("SC_CASHBOX_OFFLINE_INSTRUCTIION_ITEM6")?></li>
				</ol>
				<a class="salescenter-offline-instruction-link ui-link ui-link-dashed" onclick="BX.Salescenter.Manager.openHowToUseOfflineCashBox(event);"><?=Loc::getMessage('SC_CASHBOX_OFFLINE_INSTRUCTIION_LINK')?></a>
			</div>
			<?php
		}
		?>
	</div>
</div>
