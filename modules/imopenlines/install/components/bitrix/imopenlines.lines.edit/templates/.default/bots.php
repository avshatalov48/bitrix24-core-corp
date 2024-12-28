<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'marketplace',
]);

?>
<div class="imopenlines-form-settings-section">
	<?if(!empty($arResult['ERROR'])):?>
		<div class="ui-alert ui-alert-danger">
			<span class="ui-alert-message">
			<?foreach ($arResult['ERROR'] as $error):?>
				<?= $error ?><br>
			<?endforeach;?>
			</span>
		</div>
	<?endif;?>
	<div class="imopenlines-form-settings-title imopenlines-form-settings-title-other">
		<?=Loc::getMessage('IMOL_CONFIG_EDIT_BOT_SETTINGS')?>
		<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage("IMOL_CONFIG_EDIT_BOT_JOIN_TIP_NEW_2"))?>"></span>
	</div>
	<div class="imopenlines-control-container">
		<div class="imopenlines-control-checkbox-container">
			<label class="imopenlines-control-checkbox-label">
				<input id="imol_welcome_bot"
					   type="checkbox"
					   class="imopenlines-control-checkbox"
					   name="CONFIG[WELCOME_BOT_ENABLE]"
					   value="Y"
					   <?if($arResult['CONFIG']['WELCOME_BOT_ENABLE'] === 'Y') { ?>checked<? }?>>
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_BOT_JOIN_NEW')?>
			</label>
		</div>
		<div id="imol_welcome_bot_block" <? if($arResult['CONFIG']['WELCOME_BOT_ENABLE'] !== 'Y') {?>class="invisible"<?}?>>
			<div class="imopenlines-control-container imopenlines-control-select">
				<div class="imopenlines-control-subtitle">
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_BOT_ID')?>
				</div>
				<div class="imopenlines-control-inner">
					<select name="CONFIG[WELCOME_BOT_ID]" id="WELCOME_BOT_ID" class="imopenlines-control-input">
						<?
						foreach ($arResult['BOT_LIST'] as $value => $name)
						{
							?>
							<option value="<?=$value?>" <?if($arResult['CONFIG']['WELCOME_BOT_ID'] == $value) { ?>selected<? }?> ><?=htmlspecialcharsbx($name)?></option>
							<?
						}
						?>
					</select>
				</div>
			</div>
			<div class="imopenlines-control-container imopenlines-control-select">
				<div class="imopenlines-control-subtitle">
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_WELCOME_BOT_JOIN')?>
				</div>
				<div class="imopenlines-control-inner">
					<select name="CONFIG[WELCOME_BOT_JOIN]" class="imopenlines-control-input">
						<option value="first"
								<?if($arResult['CONFIG']['WELCOME_BOT_JOIN'] === 'first') { ?>selected<? }?>>
							<?=Loc::getMessage('IMOL_CONFIG_EDIT_WELCOME_BOT_JOIN_FIRST')?>
						</option>
						<option value="always"
								<?if($arResult['CONFIG']['WELCOME_BOT_JOIN'] === 'always') { ?>selected<? }?>>
							<?=Loc::getMessage('IMOL_CONFIG_EDIT_WELCOME_BOT_JOIN_ALWAYS')?>
						</option>
					</select>
				</div>
			</div>
			<div class="imopenlines-control-container imopenlines-control-select">
				<div class="imopenlines-control-subtitle">
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_BOT_TIME_NEW')?>
					<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_BOT_TIME_TIP'))?>"></span>
				</div>
				<div class="imopenlines-control-inner">
					<select name="CONFIG[WELCOME_BOT_TIME]" class="imopenlines-control-input">
						<option value="60" <?if((int)$arResult['CONFIG']['WELCOME_BOT_TIME'] === 60) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_1')?></option>
						<option value="180" <?if((int)$arResult['CONFIG']['WELCOME_BOT_TIME'] === 180) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_3')?></option>
						<option value="300" <?if((int)$arResult['CONFIG']['WELCOME_BOT_TIME'] === 300) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_5')?></option>
						<option value="600" <?if((int)$arResult['CONFIG']['WELCOME_BOT_TIME'] === 600) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_10')?></option>
						<option value="900" <?if((int)$arResult['CONFIG']['WELCOME_BOT_TIME'] === 900) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_15')?></option>
						<option value="1800" <?if((int)$arResult['CONFIG']['WELCOME_BOT_TIME'] === 1800) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_30')?></option>
						<option value="0" <?if((int)$arResult['CONFIG']['WELCOME_BOT_TIME'] === 0) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_0')?></option>
					</select>
				</div>
			</div>
			<div class="imopenlines-control-container imopenlines-control-select">
				<div class="imopenlines-control-subtitle">
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_WELCOME_BOT_LEFT')?>
				</div>
				<div class="imopenlines-control-inner">
					<select name="CONFIG[WELCOME_BOT_LEFT]" class="imopenlines-control-input">
						<option value="queue" <?if($arResult['CONFIG']['WELCOME_BOT_LEFT'] === 'queue') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_WELCOME_BOT_LEFT_QUEUE_NEW')?></option>
						<option value="close" <?if($arResult['CONFIG']['WELCOME_BOT_LEFT'] === 'close') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_WELCOME_BOT_LEFT_CLOSE')?></option>
					</select>
				</div>
			</div>
		</div>
		<?
		if($arResult['CAN_INSTALL_APPLICATIONS'])
		{
			?>
			<div class="imopenlines-control-checkbox-container">
				<a href="#" class="imopenlines-control-link" id="imopenlines-bot-link">
					<?=Loc::getMessage('IMOL_CONFIG_ADD_BOT')?>
				</a>
			</div>
			<?
		}
		?>
	</div>
</div>

<script>
	BX.ready(function () {
		BX.bind(BX('imol_welcome_bot'), 'change', function(e){
			<?
			if(empty($arResult['BOT_LIST']))
			{
			?>
			BX('imol_welcome_bot').checked = false;
			alert('<?=GetMessageJS('IMOL_CONFIG_EDIT_BOT_EMPTY_NEW_2')?>');
			<?
			}
			else
			{
			?>
			BX.OpenLinesConfigEdit.toggleBotBlock('imol_welcome_bot_block');
			<?
			}
			?>
		});
		<?php if($arResult['CAN_INSTALL_APPLICATIONS']): ?>
				BX.bind(
					BX('imopenlines-bot-link'),
					'click',
					<?php if(\Bitrix\Main\Loader::includeModule('market')): ?>
						BX.OpenLinesConfigEdit.botmarketButtonAction
					<?php else: ?>
						BX.OpenLinesConfigEdit.botButtonAction
					<?php endif; ?>
				);
		<?php endif; ?>
	});
</script>
