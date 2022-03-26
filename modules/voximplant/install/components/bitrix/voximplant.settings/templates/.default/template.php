<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

CJSCore::Init(['voximplant.common', 'ui.sidepanel-content']);

$bodyClass = $APPLICATION->getPageProperty('BodyClass');
$APPLICATION->setPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-all-paddings no-background');

foreach($arResult['INTERFACE_CHAT_OPTIONS'] as $action)
{
	$arResult['INTERFACE_CHAT_OPTIONS_FINAL'][$action] = GetMessage('VI_INTERFACE_CHAT_'.$action);
}
?>

<form method="post">
	<?= bitrix_sessid_post() ?>
	<div class="ui-slider-section">
		<div class="ui-slider-heading-4">
			<?=GetMessage('VI_NUMBERS_TITLE_2')?>
		</div>
		<div class="tel-set-item bx-vi-options">
			<div class="tel-set-item-desc"><?=GetMessage("VI_NUMBERS_CONFIG_BACKPHONE_TITLE")?></div>
			<div class="tel-set-item-select-wrap">
				<div class="tel-set-item-select-label"><?=GetMessage("VI_NUMBERS_CONFIG_BACKPHONE")?></div>
				<select class="tel-set-item-select" name="CURRENT_LINE" <?=(empty($arResult['LINES'])? 'class="tel-set-inp tel-set-inp-disabled" disabled="true"': 'class="tel-set-inp"')?>>
					<?foreach ($arResult['LINES'] as $k => $v): ?>
						<option value="<?=$k?>" <? if ($arResult['CURRENT_LINE'] == $k): ?> selected <? endif; ?>><?=$v?></option>
					<?endforeach;?>
				</select>
			</div>
		</div>
	</div>

	<? if(!$arResult['IS_REST_ONLY']): ?>
		<div class="ui-slider-section">
			<div class="ui-slider-heading-4">
				<?=GetMessage('VI_BACKUP_LINE_TITLE')?>
			</div>
			<div class="tel-set-item bx-vi-options">
				<div>
					<div class="tel-set-item-desc"><?=GetMessage("VI_BACKUP_NUMBER_LABEL")?>:</div>
					<div class="tel-set-item-select-wrap">
						<div class="tel-set-item-select-label"><?= Loc::getMessage("VI_BACKUP_NUMBER") ?></div>
						<input id="vi-backup-number" class="tel-set-inp" name="BACKUP_NUMBER" value="<?=htmlspecialcharsbx($arResult['BACKUP_NUMBER'])?>" size="20" maxlength="20">
					</div>
					<div class="tel-set-item-select-wrap">
						<div class="tel-set-item-select-label"><?=GetMessage("VI_BACKUP_LINE_LABEL")?>:</div>
						<select id="vi-backup-line" class="tel-set-item-select" name="BACKUP_LINE" class="tel-set-inp">
							<?foreach ($arResult['LINES'] as $k => $v):?>
								<option value="<?= $k ?>" <?= ($arResult["BACKUP_LINE"] == $k ? "selected" : "")?>><?= $v ?></option>
							<?endforeach;?>
						</select>
					</div>
				</div>
			</div>
		</div>
	<? endif ?>

	<div class="ui-slider-section">
		<div class="ui-slider-heading-4">
			<?=GetMessage('VI_INTERFACE_TITLE')?>
		</div>
		<div class="tel-set-item bx-vi-options">
			<div class="tel-set-item-select-wrap">
				<div class="tel-set-item-select-label"><?=GetMessage("VI_INTERFACE_CHAT_TITLE")?></div>
				<select name="INTERFACE_CHAT_ACTION" class="tel-set-item-select">
					<?foreach ($arResult['INTERFACE_CHAT_OPTIONS_FINAL'] as $k => $v): ?>
						<option value="<?=$k?>" <? if ($arResult['INTERFACE_CHAT_ACTION'] == $k): ?> selected <? endif; ?>><?=$v?></option>
					<?endforeach;?>
				</select>
			</div>
		</div>
	</div>

	<? if($arResult['LEAD_ENABLED']): ?>
		<div class="ui-slider-section">
			<div class="ui-slider-heading-4">
				<?=GetMessage('VI_CRM_INTEGRATION_TITLE')?>
			</div>
			<div class="tel-set-item bx-vi-options">
				<div class="tel-set-item-desc"><?=GetMessage("VI_CRM_INTEGRATION_WORKFLOW_EXECUTION_TITLE")?></div>
				<div class="tel-set-item-select-wrap">
					<select name="WORKFLOW_OPTION" class="tel-set-item-select">
						<?foreach ($arResult['WORKFLOW_OPTIONS'] as $k => $v): ?>
							<option value="<?=$k?>" <? if ($arResult['WORKFLOW_OPTION'] == $k): ?> selected <? endif; ?>><?=$v?></option>
						<?endforeach;?>
					</select>
				</div>
			</div>
		</div>
	<? endif ?>

	<? if(!$arResult['IS_REST_ONLY']): ?>
		<div class="ui-slider-section">
			<div class="ui-slider-heading-4">
				<?=GetMessage('VI_COMBINATIONS_TITLE')?>
			</div>
			<div class="tel-set-item bx-vi-options">
				<div class="tel-set-item-desc"><?=GetMessage("VI_COMBINATION_INTERCEPT_GROUP")?></div>
				<div class="tel-set-item-select-wrap">
					<div class="tel-set-item-select-label"><?= Loc::getMessage("VI_COMBINATIONS_TITLE")?></div>
					<input type="text"
						   name="COMBINATION_INTERCEPT_GROUP"
						   class="tel-set-inp"
						   size="5"
						   maxlength="5"
						   value="<?=htmlspecialcharsbx($arResult["COMBINATION_INTERCEPT_GROUP"])?>"
					>
				</div>
			</div>
		</div>
	<? endif ?>

	<?$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
		'BUTTONS' => [
			'save',
			'cancel' => '/telephony/',
		]
	]);?>
</form>

