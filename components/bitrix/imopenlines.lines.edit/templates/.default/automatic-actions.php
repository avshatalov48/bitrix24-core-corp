<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc; ?>

<div class="imopenlines-form-settings-section">
	<div class="imopenlines-form-settings-block">
		<div class="imopenlines-control-checkbox-container">
			<label class="imopenlines-control-checkbox-label">
				<input type="checkbox"
					   id="imol_welcome_message"
					   name="CONFIG[WELCOME_MESSAGE]"
					   value="Y"
					   class="imopenlines-control-checkbox"
					   <? if ($arResult['CONFIG']['WELCOME_MESSAGE'] == 'Y') { ?>checked<? } ?>>
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_WELCOME_MESSAGE_NEW')?>
				<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_WELCOME_MESSAGE_NEW_TIP'))?>"></span>
			</label>
		</div>
		<div class="imopenlines-control-container <? if ($arResult['CONFIG']['WELCOME_MESSAGE'] != 'Y') { ?>invisible<? } ?>" id="imol_action_welcome">
			<div class="imopenlines-control-subtitle"><?=Loc::getMessage('IMOL_CONFIG_EDIT_WELCOME_MESSAGE_NEW_TEXT')?></div>
			<div class="imopenlines-control-inner">
				<textarea type="text" class="imopenlines-control-input imopenlines-control-textarea"
				name="CONFIG[WELCOME_MESSAGE_TEXT]"><?=htmlspecialcharsbx($arResult['CONFIG']['WELCOME_MESSAGE_TEXT'])?></textarea>
			</div>
		</div>
	</div>
	<?if(isset($arResult['AUTOMATIC_MESSAGE'])):?>
	<div class="imopenlines-form-settings-block">
		<div class="imopenlines-control-checkbox-container">
			<label class="imopenlines-control-checkbox-label">
				<input type="checkbox"
					   id="imol_automatic_message"
					   name="AUTOMATIC_MESSAGE[ENABLE]"
					   value="Y"
					   class="imopenlines-control-checkbox"
					   <? if ($arResult['AUTOMATIC_MESSAGE']['ENABLE'] == 'Y') { ?>checked<? } ?>>
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_ENABLE')?>
			</label>
		</div>
		<div<? if ($arResult['AUTOMATIC_MESSAGE']['ENABLE'] != 'Y') { ?> class="invisible"<? } ?> id="imol_action_automatic_message">
			<?foreach ($arResult['AUTOMATIC_MESSAGE']['TASK'] as $idConfigTask => $configTask):?>
				<div class="imopenlines-control-container imopenlines-control-select">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TIME')?>
					</div>
					<div class="imopenlines-control-inner">
						<select class="imopenlines-control-input" name="AUTOMATIC_MESSAGE[TASK][<?=$idConfigTask?>][TIME_TASK]">
							<option value="10800" <?if((int)$configTask['TIME_TASK'] === 10800) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TIME_3_H')?></option>
							<option value="25200" <?if((int)$configTask['TIME_TASK'] === 25200) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TIME_7_H')?></option>
							<option value="43200" <?if((int)$configTask['TIME_TASK'] === 43200) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TIME_12_H')?></option>
							<option value="172800" <?if((int)$configTask['TIME_TASK'] === 172800) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TIME_2_D')?></option>
							<option value="345600" <?if((int)$configTask['TIME_TASK'] === 345600) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TIME_4_D')?></option>
							<option value="518400" <?if((int)$configTask['TIME_TASK'] === 518400) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TIME_6_D')?></option>
							<option value="1209600" <?if((int)$configTask['TIME_TASK'] === 1209600) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TIME_2_W')?></option>
						</select>
					</div>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle"><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TEXT')?></div>
					<div class="imopenlines-control-inner">
				<textarea type="text" class="imopenlines-control-input imopenlines-control-textarea"
						  name="AUTOMATIC_MESSAGE[TASK][<?=$idConfigTask?>][MESSAGE]"><?=htmlspecialcharsbx($configTask['MESSAGE'])?></textarea>
					</div>
				</div>
				<div class="imopenlines-control-checkbox-container">
					<div class="imopenlines-control-checkbox-container"><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_CLOSE_TITLE')?></div>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_BUTTON_NAME')?>
					</div>
					<div class="imopenlines-control-inner">
						<input name="AUTOMATIC_MESSAGE[TASK][<?=$idConfigTask?>][TEXT_BUTTON_CLOSE]"
							   class="imopenlines-control-input"
							   value="<?=htmlspecialcharsbx($configTask['TEXT_BUTTON_CLOSE'])?>"
							   placeholder="<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_CLOSE_TITLE')?>"
							   type="text">
					</div>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_LONG_TEXT_NAME')?>
					</div>
					<div class="imopenlines-control-inner">
						<input name="AUTOMATIC_MESSAGE[TASK][<?=$idConfigTask?>][LONG_TEXT_BUTTON_CLOSE]"
							   class="imopenlines-control-input"
							   value="<?=htmlspecialcharsbx($configTask['LONG_TEXT_BUTTON_CLOSE'])?>"
							   placeholder="<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_CLOSE_TITLE')?>"
							   type="text">
					</div>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle"><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_AUTOMATIC_TEXT_TITLE')?></div>
					<div class="imopenlines-control-inner">
				<textarea type="text" class="imopenlines-control-input imopenlines-control-textarea"
						  name="AUTOMATIC_MESSAGE[TASK][<?=$idConfigTask?>][AUTOMATIC_TEXT_CLOSE]"><?=htmlspecialcharsbx($configTask['AUTOMATIC_TEXT_CLOSE'])?></textarea>
					</div>
				</div>

				<div class="imopenlines-control-checkbox-container">
					<div class="imopenlines-control-checkbox-container"><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_CONTINUE_TITLE')?></div>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_BUTTON_NAME')?>
					</div>
					<div class="imopenlines-control-inner">
						<input name="AUTOMATIC_MESSAGE[TASK][<?=$idConfigTask?>][TEXT_BUTTON_CONTINUE]"
							   class="imopenlines-control-input"
							   value="<?=htmlspecialcharsbx($configTask['TEXT_BUTTON_CONTINUE'])?>"
							   placeholder="<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_CONTINUE_TITLE')?>"
							   type="text">
					</div>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_LONG_TEXT_NAME')?>
					</div>
					<div class="imopenlines-control-inner">
						<input name="AUTOMATIC_MESSAGE[TASK][<?=$idConfigTask?>][LONG_TEXT_BUTTON_CONTINUE]"
							   class="imopenlines-control-input"
							   value="<?=htmlspecialcharsbx($configTask['LONG_TEXT_BUTTON_CONTINUE'])?>"
							   placeholder="<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_CONTINUE_TITLE')?>"
							   type="text">
					</div>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle"><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_AUTOMATIC_TEXT_TITLE')?></div>
					<div class="imopenlines-control-inner">
				<textarea type="text" class="imopenlines-control-input imopenlines-control-textarea"
						  name="AUTOMATIC_MESSAGE[TASK][<?=$idConfigTask?>][AUTOMATIC_TEXT_CONTINUE]"><?=htmlspecialcharsbx($configTask['AUTOMATIC_TEXT_CONTINUE'])?></textarea>
					</div>
				</div>

				<div class="imopenlines-control-checkbox-container">
					<div class="imopenlines-control-checkbox-container"><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_NEW_TITLE')?></div>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_BUTTON_NAME')?>
					</div>
					<div class="imopenlines-control-inner">
						<input name="AUTOMATIC_MESSAGE[TASK][<?=$idConfigTask?>][TEXT_BUTTON_NEW]"
							   class="imopenlines-control-input"
							   value="<?=htmlspecialcharsbx($configTask['TEXT_BUTTON_NEW'])?>"
							   placeholder="<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_NEW_TITLE')?>"
							   type="text">
					</div>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_LONG_TEXT_NAME')?>
					</div>
					<div class="imopenlines-control-inner">
						<input name="AUTOMATIC_MESSAGE[TASK][<?=$idConfigTask?>][LONG_TEXT_BUTTON_NEW]"
							   class="imopenlines-control-input"
							   value="<?=htmlspecialcharsbx($configTask['LONG_TEXT_BUTTON_NEW'])?>"
							   placeholder="<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_NEW_TITLE')?>"
							   type="text">
					</div>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle"><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_AUTOMATIC_TEXT_TITLE')?></div>
					<div class="imopenlines-control-inner">
				<textarea type="text" class="imopenlines-control-input imopenlines-control-textarea"
						  name="AUTOMATIC_MESSAGE[TASK][<?=$idConfigTask?>][AUTOMATIC_TEXT_NEW]"><?=htmlspecialcharsbx($configTask['AUTOMATIC_TEXT_NEW'])?></textarea>
					</div>
				</div>
			<?endforeach;?>
		</div>
	</div>
	<?endif;?>
	<div class="imopenlines-form-settings-block">
		<div class="imopenlines-control-container imopenlines-control-select">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_NA_TIME_NEW')?>
			</div>
			<div class="imopenlines-control-inner">
				<select class="imopenlines-control-input" name="CONFIG[NO_ANSWER_TIME]">
					<option value="60" <?if($arResult['CONFIG']['NO_ANSWER_TIME'] == '60') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_1')?></option>
					<option value="180" <?if($arResult['CONFIG']['NO_ANSWER_TIME'] == '180') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_3')?></option>
					<option value="300" <?if($arResult['CONFIG']['NO_ANSWER_TIME'] == '300') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_5')?></option>
					<option value="600" <?if($arResult['CONFIG']['NO_ANSWER_TIME'] == '600') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_10')?></option>
					<option value="900" <?if($arResult['CONFIG']['NO_ANSWER_TIME'] == '900') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_15')?></option>
					<option value="1800" <?if($arResult['CONFIG']['NO_ANSWER_TIME'] == '1800') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_30')?></option>

					<option value="3600" <?if($arResult['CONFIG']['NO_ANSWER_TIME'] == '3600') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_60')?></option>
					<option value="7200" <?if($arResult['CONFIG']['NO_ANSWER_TIME'] == '7200') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_120')?></option>
					<option value="10800" <?if($arResult['CONFIG']['NO_ANSWER_TIME'] == '10800') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_180')?></option>
					<option value="21600" <?if($arResult['CONFIG']['NO_ANSWER_TIME'] == '21600') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_360')?></option>
					<option value="28800" <?if($arResult['CONFIG']['NO_ANSWER_TIME'] == '28800') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_480')?></option>
					<option value="43200" <?if($arResult['CONFIG']['NO_ANSWER_TIME'] == '43200') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_720')?></option>
				</select>
			</div>
		</div>

		<div class="imopenlines-control-container imopenlines-control-select">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_NO_ANSWER_RULE')?>
				<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_NO_ANSWER_DESC_NEW'))?>"></span>
			</div>
			<div class="imopenlines-control-inner">
				<select name="CONFIG[NO_ANSWER_RULE]" id="imol_no_answer_rule" class="imopenlines-control-input">
					<?
					foreach ($arResult['NO_ANSWER_RULES'] as $value => $name)
					{
						?>
						<option value="<?=$value?>" <?if($arResult['CONFIG']['NO_ANSWER_RULE'] == $value) { ?>selected<? }?> <?if($value == 'disabled') { ?>disabled<? }?>>
							<?=$name?>
						</option>
						<?
					}
					?>
				</select>
			</div>
		</div>

		<div class="imopenlines-control-container imopenlines-control-select" id="imol_no_answer_rule_form_form">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_NO_ANSWER_FORM_ID')?>
				<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_NO_ANSWER_FORM_TEXT'))?>"></span>
			</div>
			<div class="imopenlines-control-inner">
				<select name="CONFIG[NO_ANSWER_FORM_ID]" class="imopenlines-control-input">
					<?
					foreach ($arResult['NO_ANSWER_RULES'] as $value => $name)
					{
						?>
						<option value="<?=$value?>" <?if($arResult['CONFIG']['NO_ANSWER_RULE'] == $value) { ?>selected<? }?> <?if($value == 'disabled') { ?>disabled<? }?>>
							<?=$name?>
						</option>
						<?
					}
					?>
				</select>
			</div>
		</div>
		<div class="imopenlines-control-container" id="imol_no_answer_rule_text">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_NO_ANSWER_TEXT')?>
			</div>
			<div class="imopenlines-control-inner">
				<textarea type="text"
						  name="CONFIG[NO_ANSWER_TEXT]"
						  class="imopenlines-control-input imopenlines-control-textarea"><?=htmlspecialcharsbx($arResult['CONFIG']['NO_ANSWER_TEXT'])?></textarea>
			</div>
		</div>
	</div>
	<div class="imopenlines-form-settings-block">
		<div class="imopenlines-control-container imopenlines-control-select">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_CLOSE_ACTION_NEW')?>
			</div>
			<div class="imopenlines-control-inner">
				<select name="CONFIG[CLOSE_RULE]" id="imol_action_close" class="imopenlines-control-input">
					<?
					foreach($arResult['CLOSE_RULES'] as $value=>$name)
					{
						?>
						<option value="<?=$value?>"
								<?if($arResult['CONFIG']['CLOSE_RULE'] == $value) { ?>selected<? }?>
								<?if($value == 'disabled') { ?>disabled<? }?>>
							<?=$name?>
						</option>
						<?
					}
					?>
				</select>
			</div>
		</div>
		<div class="imopenlines-control-container ui-control-select" id="imol_action_close_form">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_CLOSE_FORM_ID')?>
			</div>
			<div class="imopenlines-control-inner">
				<select class="imopenlines-control-input" name="CONFIG[CLOSE_FORM_ID]"></select>
			</div>
		</div>
		<div class="imopenlines-control-container imopenlines-control-block" id="imol_action_close_text">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_CLOSE_TEXT_NEW')?>
			</div>
			<div class="imopenlines-control-inner">
				<textarea class="imopenlines-control-input imopenlines-control-textarea"
						  name="CONFIG[CLOSE_TEXT]"><?=htmlspecialcharsbx($arResult['CONFIG']['CLOSE_TEXT'])?></textarea>
			</div>
		</div>
	</div>
	<div class="imopenlines-form-settings-block">
		<div class="imopenlines-control-container imopenlines-control-select">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_FULL_CLOSE_TIME_NEW')?>
				<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_FULL_CLOSE_TIME_DESC_NEW'))?>"></span>
			</div>
			<div class="imopenlines-control-inner">
				<select name="CONFIG[FULL_CLOSE_TIME]" class="imopenlines-control-input">
					<option value="0" <?if($arResult['CONFIG']['FULL_CLOSE_TIME'] == '0') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_FULL_CLOSE_TIME_0')?></option>
					<option value="1" <?if($arResult['CONFIG']['FULL_CLOSE_TIME'] == '1') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_FULL_CLOSE_TIME_1')?></option>
					<option value="2" <?if($arResult['CONFIG']['FULL_CLOSE_TIME'] == '2') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_FULL_CLOSE_TIME_2')?></option>
					<option value="5" <?if($arResult['CONFIG']['FULL_CLOSE_TIME'] == '5') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_FULL_CLOSE_TIME_5')?></option>
					<option value="10" <?if($arResult['CONFIG']['FULL_CLOSE_TIME'] == '10' || !isset($arResult['CONFIG']['FULL_CLOSE_TIME'])) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_FULL_CLOSE_TIME_10')?></option>
					<option value="30" <?if($arResult['CONFIG']['FULL_CLOSE_TIME'] == '30') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_FULL_CLOSE_TIME_30')?></option>
					<option value="60" <?if($arResult['CONFIG']['FULL_CLOSE_TIME'] == '60') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_FULL_CLOSE_TIME_60')?></option>
				</select>
			</div>
		</div>
		<div class="imopenlines-control-container imopenlines-control-select" id="imol_queue_time">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_TIME')?>
			</div>
			<div class="imopenlines-control-inner">
				<select class="imopenlines-control-input" name="CONFIG[AUTO_CLOSE_TIME]">
					<option value="3600" <?if($arResult['CONFIG']['AUTO_CLOSE_TIME'] == '3600') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_TIME_1_H')?></option>
					<option value="14400" <?if($arResult['CONFIG']['AUTO_CLOSE_TIME'] == '14400') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_TIME_4_H')?></option>
					<option value="28800" <?if($arResult['CONFIG']['AUTO_CLOSE_TIME'] == '28800') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_TIME_8_H')?></option>
					<option value="86400" <?if($arResult['CONFIG']['AUTO_CLOSE_TIME'] == '86400') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_TIME_1_D')?></option>
					<option value="172800" <?if($arResult['CONFIG']['AUTO_CLOSE_TIME'] == '172800') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_TIME_2_D')?></option>
					<option value="604800" <?if($arResult['CONFIG']['AUTO_CLOSE_TIME'] == '604800') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_TIME_1_W')?></option>
					<option value="2678400" <?if($arResult['CONFIG']['AUTO_CLOSE_TIME'] == '2678400') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_TIME_1_M')?></option>
				</select>
			</div>
		</div>
		<div class="imopenlines-control-container imopenlines-control-select">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_RULE')?>
			</div>
			<div class="imopenlines-control-inner">
				<select name="CONFIG[AUTO_CLOSE_RULE]" id="imol_action_auto_close" class="imopenlines-control-input">
					<?
					foreach($arResult['CLOSE_RULES'] as $value=>$name)
					{
						?>
						<option value="<?=$value?>" <?if($arResult['CONFIG']['AUTO_CLOSE_RULE'] == $value) { ?>selected<? }?> <?if($value == 'disabled') { ?>disabled<? }?>>
							<?=$name?>
						</option>
						<?
					}
					?>
				</select>
			</div>
		</div>
		<div class="imopenlines-control-container ui-control-select"
			 id="imol_action_auto_close_form">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_FORM_ID')?>
			</div>
			<div class="imopenlines-control-inner">
				<select class="imopenlines-control-input" name="CONFIG[AUTO_CLOSE_FORM_ID]"></select>
			</div>
		</div>
		<div class="imopenlines-control-container imopenlines-control-block"
			 id="imol_action_auto_close_text">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_TEXT_NEW')?>
			</div>
			<div class="imopenlines-control-inner">
				<textarea class="imopenlines-control-input imopenlines-control-textarea"
						  name="CONFIG[AUTO_CLOSE_TEXT]"><?=htmlspecialcharsbx($arResult['CONFIG']['AUTO_CLOSE_TEXT'])?></textarea>
			</div>
		</div>
	</div>
	<div class="imopenlines-form-settings-block">
		<div class="imopenlines-control-container imopenlines-control-select">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_QUICK_ANSWERS_STORAGE')?>
			</div>
			<div class="imopenlines-control-inner">
				<select name="CONFIG[QUICK_ANSWERS_IBLOCK_ID]" class="imopenlines-control-input">
					<?
					foreach($arResult['QUICK_ANSWERS_STORAGE_LIST'] as $id => $item)
					{
						?>
						<option value="<?=intval($id);?>"<?if($id == $arResult['CONFIG']['QUICK_ANSWERS_IBLOCK_ID']){?> selected<?}?>>
							<?=htmlspecialcharsbx($item['NAME']);?>
						</option>
						<?
					}
					?>
				</select>
				<div class="ui-btn ui-btn-light-border" id="imol_quick_answer_manage" data-url="<?=$arResult['QUICK_ANSWERS_MANAGE_URL']?>">
					<?
					$code = ($arResult['CONFIG']['QUICK_ANSWERS_IBLOCK_ID'] > 0 ? 'IMOL_CONFIG_QUICK_ANSWERS_CONFIG' : 'IMOL_CONFIG_QUICK_ANSWERS_CREATE');
					echo Loc::getMessage($code);
					?>
				</div>
			</div>
			<div class="imopenlines-control-subtitle imopenlines-control-subtitle-answer">
				<?=Loc::getMessage('IMOL_CONFIG_QUICK_ANSWERS_DESC_NEW')?>
			</div>
		</div>
		<div class="imopenlines-control-checkbox-container">
			<label class="imopenlines-control-checkbox-label">
				<input type="checkbox"
					   id="imol_watch_typing"
					   name="CONFIG[WATCH_TYPING]"
					   value="Y"
					   class="imopenlines-control-checkbox"
					   <? if ($arResult['CONFIG']['WATCH_TYPING'] == 'Y') { ?>checked<? } ?>>
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_WATCH_TYPING')?>
			</label>
			<div class="imopenlines-control-subtitle imopenlines-control-subtitle-answer">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_WATCH_TYPING_DESC')?>
			</div>
		</div>
	</div>
</div>
