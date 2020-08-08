<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\UI\Extension;

Loc::loadMessages(__DIR__.'/template.php');

Extension::load(['ui.buttons', 'ui.icons', 'ui.common', 'ui.alerts', 'salescenter.manager']);

$APPLICATION->SetTitle(Loc::getMessage('SC_CASHBOX_OFFLINE_TITLE'));
?>

<?php $this->setViewTarget("inside_pagetitle_below", 100); ?>
<div class="salescenter-main-header-feedback-container">
	<?Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->renderFeedbackButton();?>
</div>
<?php $this->endViewTarget(); ?>

<div class="salescenter-cashbox-wrapper">
	<div id="salescenter-wrapper" class="salescenter-wrapper">
		<div id="salescenter-cashbox-info">
			<div style="padding: 15px; margin-bottom: 15px;" class="ui-bg-color-white">
				<div class="salescenter-main-header">
					<div class="salescenter-main-header-left-block">
						<div class="salescenter-logo-container">
							<div class="salescenter-offline-icon ui-icon">
								<i></i>
							</div>
						</div>
					</div>
					<div class="salescenter-main-header-right-block">
						<div class="salescenter-main-header-title-container">
							<div style="margin-bottom: 15px;" class="ui-title-3"><?=Loc::getMessage('SC_CASHBOX_OFFLINE_DESCRIPTION_TITLE')?></div>
						</div>
						<div class="ui-text-2" style="margin-bottom: 20px;"><?=Loc::getMessage('SC_CASHBOX_OFFLINE_DESCRIPTION_DESC1')?></div>
						<div class="ui-text-2" style="margin-bottom: 20px;"><?=Loc::getMessage('SC_CASHBOX_OFFLINE_DESCRIPTION_DESC2')?></div>
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
				<div style="padding: 15px 15px 45px 15px; margin-bottom: 15px;" class="ui-bg-color-white">
					<div class="ui-title-4"><?=Loc::getMessage("SC_CASHBOX_OFFLINE_INSTRUCTIION_TITLE")?></div>
					<hr class="ui-hr">
					<ol class="ui-color-medium salescenter-offline-instruction-list">
						<li class="salescenter-offline-instruction-item"><?=Loc::getMessage("SC_CASHBOX_OFFLINE_INSTRUCTIION_ITEM1")?></li>
						<li class="salescenter-offline-instruction-item"><?=Loc::getMessage("SC_CASHBOX_OFFLINE_INSTRUCTIION_ITEM2")?></li>
						<li class="salescenter-offline-instruction-item"><?=Loc::getMessage("SC_CASHBOX_OFFLINE_INSTRUCTIION_ITEM3")?></li>
						<li class="salescenter-offline-instruction-item"><?=Loc::getMessage("SC_CASHBOX_OFFLINE_INSTRUCTIION_ITEM4")?></li>
						<li class="salescenter-offline-instruction-item"><?=Loc::getMessage("SC_CASHBOX_OFFLINE_INSTRUCTIION_ITEM5")?></li>
						<li class="salescenter-offline-instruction-item"><?=Loc::getMessage("SC_CASHBOX_OFFLINE_INSTRUCTIION_ITEM6")?></li>
					</ol>
					<a class="salescenter-offline-instruction-link ui-link ui-link-dashed" onclick="BX.Salescenter.Manager.openHowToUseOfflineCashBox(event);"><?=Loc::getMessage('SC_CASHBOX_OFFLINE_INSTRUCTIION_LINK')?></a>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</div>