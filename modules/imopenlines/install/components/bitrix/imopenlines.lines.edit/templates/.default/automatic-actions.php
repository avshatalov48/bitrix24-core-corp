<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

$sendWelcomeMessage = $arResult['CONFIG']['WELCOME_MESSAGE'] === 'Y';
$sendWelcomeEachSession = $arResult['CONFIG']['SEND_WELCOME_EACH_SESSION'] === 'Y';

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
	<div class="imopenlines-form-settings-block">
		<div class="imopenlines-control-checkbox-container">
			<label class="imopenlines-control-checkbox-label">
				<input type="checkbox"
					   id="imol_welcome_message"
					   name="CONFIG[WELCOME_MESSAGE]"
					   value="Y"
					   class="imopenlines-control-checkbox"
					   <?= $sendWelcomeMessage ? 'checked' : '' ?>>
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_WELCOME_MESSAGE')?>
			</label>
		</div>
		<div  <?= !$sendWelcomeMessage ? 'class="invisible"' : '' ?> id="imol_welcome_message_block">
			<div class="imopenlines-control-checkbox-container" id="imol_welcome_message_each_session_n">
				<label class="imopenlines-control-checkbox-label">
					<input type="radio"
						   id="imol_send_welcome_each_session_n"
						   name="CONFIG[SEND_WELCOME_EACH_SESSION]"
						   value="N"
						   class="imopenlines-control-checkbox"
						<?= !$sendWelcomeEachSession ? 'checked' : '' ?>>
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_WELCOME_MESSAGE_N_EACH_SESSION_NEW')?>
					<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_WELCOME_MESSAGE_N_EACH_SESSION_TIP'))?>"></span>
				</label>
			</div>
			<div class="imopenlines-control-checkbox-container" id="imol_welcome_message_each_session_y">
				<label class="imopenlines-control-checkbox-label">
					<input type="radio"
						   id="imol_send_welcome_each_session_y"
						   name="CONFIG[SEND_WELCOME_EACH_SESSION]"
						   value="Y"
						   class="imopenlines-control-checkbox"
						<?= $sendWelcomeEachSession ? 'checked' : '' ?>>
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_WELCOME_MESSAGE_Y_EACH_SESSION')?>
					<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_WELCOME_MESSAGE_Y_EACH_SESSION_TIP'))?>"></span>
				</label>
			</div>
			<div class="imopenlines-control-container" id="imol_action_welcome">
				<div class="imopenlines-control-subtitle"><?=Loc::getMessage('IMOL_CONFIG_EDIT_WELCOME_MESSAGE_NEW_TEXT')?></div>
				<div class="imopenlines-control-inner">
				<textarea type="text" class="imopenlines-control-input imopenlines-control-textarea"
						  name="CONFIG[WELCOME_MESSAGE_TEXT]"><?=htmlspecialcharsbx($arResult['CONFIG']['WELCOME_MESSAGE_TEXT'])?></textarea>
				</div>
			</div>
		</div>
	</div>

	<!--	Welcome form block	-->
	<?if (isset($arResult['CRM_INSTALLED'])):?>
	<div class="imopenlines-form-settings-block">
		<div class="imopenlines-control-checkbox-container">
			<label class="imopenlines-control-checkbox-label">
				<input type="checkbox"
					   class="imopenlines-control-checkbox"
					   id="imol_form_welcome"
					   name="CONFIG[USE_WELCOME_FORM]"
					   value="Y"
					   <? if ($arResult['CONFIG']['USE_WELCOME_FORM'] == 'Y') { ?>checked<? } ?>>
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_FORM_WELCOME_CHECKBOX')?>
				<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_FORM_WELCOME_TIP'))?>"></span>
			</label>
		</div>
		<div id="imol_form_welcome_block" <? if ($arResult['CONFIG']['USE_WELCOME_FORM'] !== 'Y') { ?>class="invisible" <? } ?>>
			<div class="imopenlines-control-container imopenlines-control-select">
				<div class="imopenlines-control-subtitle">
					<?=Loc::getMessage("IMOL_CONFIG_EDIT_FORM_WELCOME_SELECT")?>
				</div>
				<div class="imopenlines-control-inner">
					<select name="CONFIG[WELCOME_FORM_ID]" class="imopenlines-control-input">
						<?
						if (is_array($arResult["CRM_FORMS_LIST"]) && !empty($arResult["CRM_FORMS_LIST"]))
						{
							foreach($arResult["CRM_FORMS_LIST"] as $form)
							{
								?>
								<option value="<?=$form['ID']?>"<?=($arResult["CONFIG"]["WELCOME_FORM_ID"] == $form['ID']? ' selected="selected"' : '')?>>
									<?=htmlspecialcharsbx($form['NAME'])?>
								</option>
								<?
							}
						}
						?>
					</select>
				</div>
				<div>
					<a href="<?=$arResult['CRM_FORMS_CREATE_LINK']?>" target="_blank" class="imopenlines-form-welcome-create-link">
						<?=Loc::getMessage("IMOL_CONFIG_EDIT_FORM_WELCOME_CREATE")?>
					</a>
				</div>
			</div>
			<div class="imopenlines-control-container imopenlines-control-select">
				<div class="imopenlines-control-subtitle">
					<?=Loc::getMessage("IMOL_CONFIG_EDIT_FORM_WELCOME_DELAY_SELECT")?>
				</div>
				<div class="imopenlines-control-inner">
					<select name="CONFIG[WELCOME_FORM_DELAY]" id="imol_form_welcome_delay" class="imopenlines-control-input">
						<option value="N"<?=($arResult["CONFIG"]["WELCOME_FORM_DELAY"] === 'N'? ' selected="selected"' : '')?>>
							<?=Loc::getMessage("IMOL_CONFIG_EDIT_FORM_WELCOME_DELAY_SELECT_N")?>
						</option>
						<option value="Y"<?=($arResult["CONFIG"]["WELCOME_FORM_DELAY"] === 'Y'? ' selected="selected"' : '')?>>
							<?=Loc::getMessage("IMOL_CONFIG_EDIT_FORM_WELCOME_DELAY_SELECT_Y")?>
						</option>
					</select>
				</div>
				<div id="imol_form_welcome_delay_description">
					<div id="imol_form_no_delay_description" class="imopenlines-control-subtitle <? if ($arResult['CONFIG']['WELCOME_FORM_DELAY'] === 'Y') { ?>invisible<? } ?>">
						<?=Loc::getMessage("IMOL_CONFIG_EDIT_FORM_WELCOME_DELAY_DESCRIPTION_N")?>
					</div>
					<div id="imol_form_delay_description" class="imopenlines-control-subtitle <? if ($arResult['CONFIG']['WELCOME_FORM_DELAY'] === 'N') { ?>invisible<? } ?>">
						<?=Loc::getMessage("IMOL_CONFIG_EDIT_FORM_WELCOME_DELAY_DESCRIPTION_Y")?>
					</div>
				</div>
			</div>
			<div class="imopenlines-control-checkbox-container">
				<label class="imopenlines-control-checkbox-label">
					<input type="checkbox"
						   class="imopenlines-control-checkbox"
						   id="imol_form_welcome_ignore_responsible"
						   name="CONFIG[IGNORE_WELCOME_FORM_RESPONSIBLE]"
						   value="Y"
						   <?php if ($arResult['CONFIG']['IGNORE_WELCOME_FORM_RESPONSIBLE'] == 'Y'): ?>checked<?php endif; ?>>
					<?= Loc::getMessage('IMOL_CONFIG_EDIT_FORM_WELCOME_IGNORE_RESPONSIBLE') ?>
					<span data-hint-html data-hint="<?= htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_FORM_WELCOME_IGNORE_RESPONSIBLE_TIP')) ?>"></span>
				</label>
			</div>
		</div>
	</div>
	<?endif;?>

	<?if(isset($arResult['AUTOMATIC_MESSAGE'])):?>
	<div class="imopenlines-form-settings-block">
		<div class="imopenlines-control-checkbox-container">
			<label class="imopenlines-control-checkbox-label">
				<input type="checkbox"
					   id="imol_automatic_message"
					   name="AUTOMATIC_MESSAGE[ENABLE]"
					   value="Y"
					   class="imopenlines-control-checkbox"
					   <? if ($arResult['AUTOMATIC_MESSAGE']['ENABLE'] === 'Y') { ?>checked<? } ?>>
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_ENABLE')?>
			</label>
		</div>
		<div<? if ($arResult['AUTOMATIC_MESSAGE']['ENABLE'] !== 'Y') { ?> class="invisible"<? } ?> id="imol_action_automatic_message">
			<?foreach ($arResult['AUTOMATIC_MESSAGE']['TASK'] as $idConfigTask => $configTask):?>
				<div class="imopenlines-control-checkbox-container">
					<label class="imopenlines-control-checkbox-label">
						<input type="checkbox"
							   name="AUTOMATIC_MESSAGE[TASK][<?=$idConfigTask?>][ACTIVE]"
							   value="Y"
							   class="imopenlines-control-checkbox"
							   <? if (isset($configTask['ACTIVE']) && $configTask['ACTIVE']  === 'Y') { ?>checked<? } ?>>
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_ACTIVE')?>
					</label>
				</div>
				<div class="imopenlines-control-container imopenlines-control-select">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TIME')?>
					</div>
					<div class="imopenlines-control-inner">
						<select class="imopenlines-control-input" name="AUTOMATIC_MESSAGE[TASK][<?=$idConfigTask?>][TIME_TASK]">
							<?php
							$timeTask = (int)($configTask['TIME_TASK'] ?? 0);
							?>
							<option value="10800" <? if($timeTask === 10800) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TIME_3_H')?></option>
							<option value="25200" <? if($timeTask === 25200) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TIME_7_H')?></option>
							<option value="43200" <? if($timeTask === 43200) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TIME_12_H')?></option>
							<option value="172800" <? if($timeTask === 172800) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TIME_2_D')?></option>
							<option value="345600" <? if($timeTask === 345600) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TIME_4_D')?></option>
							<option value="518400" <? if($timeTask === 518400) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TIME_6_D')?></option>
							<option value="1209600" <? if($timeTask === 1209600) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TIME_2_W')?></option>
						</select>
					</div>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle"><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_TEXT')?></div>
					<div class="imopenlines-control-inner">
				<textarea type="text" class="imopenlines-control-input imopenlines-control-textarea"
						  name="AUTOMATIC_MESSAGE[TASK][<?=$idConfigTask?>][MESSAGE]"><?=htmlspecialcharsbx($configTask['MESSAGE'] ?? '')?></textarea>
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
							   value="<?=htmlspecialcharsbx($configTask['TEXT_BUTTON_CLOSE'] ?? '')?>"
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
							   value="<?=htmlspecialcharsbx($configTask['LONG_TEXT_BUTTON_CLOSE'] ?? '')?>"
							   placeholder="<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_CLOSE_TITLE')?>"
							   type="text">
					</div>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle"><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_AUTOMATIC_TEXT_TITLE')?></div>
					<div class="imopenlines-control-inner">
				<textarea type="text" class="imopenlines-control-input imopenlines-control-textarea"
						  name="AUTOMATIC_MESSAGE[TASK][<?=$idConfigTask?>][AUTOMATIC_TEXT_CLOSE]"><?=htmlspecialcharsbx($configTask['AUTOMATIC_TEXT_CLOSE'] ?? '')?></textarea>
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
							   value="<?=htmlspecialcharsbx($configTask['TEXT_BUTTON_CONTINUE'] ?? '')?>"
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
							   value="<?=htmlspecialcharsbx($configTask['LONG_TEXT_BUTTON_CONTINUE'] ?? '')?>"
							   placeholder="<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_CONTINUE_TITLE')?>"
							   type="text">
					</div>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle"><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_AUTOMATIC_TEXT_TITLE')?></div>
					<div class="imopenlines-control-inner">
				<textarea type="text" class="imopenlines-control-input imopenlines-control-textarea"
						  name="AUTOMATIC_MESSAGE[TASK][<?=$idConfigTask?>][AUTOMATIC_TEXT_CONTINUE]"><?=htmlspecialcharsbx($configTask['AUTOMATIC_TEXT_CONTINUE'] ?? '')?></textarea>
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
							   value="<?=htmlspecialcharsbx($configTask['TEXT_BUTTON_NEW'] ?? '')?>"
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
							   value="<?=htmlspecialcharsbx($configTask['LONG_TEXT_BUTTON_NEW'] ?? '')?>"
							   placeholder="<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_NEW_TITLE')?>"
							   type="text">
					</div>
				</div>
				<div class="imopenlines-control-container">
					<div class="imopenlines-control-subtitle"><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTOMATIC_MESSAGE_AUTOMATIC_TEXT_TITLE')?></div>
					<div class="imopenlines-control-inner">
				<textarea type="text" class="imopenlines-control-input imopenlines-control-textarea"
						  name="AUTOMATIC_MESSAGE[TASK][<?=$idConfigTask?>][AUTOMATIC_TEXT_NEW]"><?=htmlspecialcharsbx($configTask['AUTOMATIC_TEXT_NEW'] ?? '')?></textarea>
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
					<option value="60" <?if((int)$arResult['CONFIG']['NO_ANSWER_TIME'] === 60) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_1')?></option>
					<option value="180" <?if((int)$arResult['CONFIG']['NO_ANSWER_TIME'] === 180) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_3')?></option>
					<option value="300" <?if((int)$arResult['CONFIG']['NO_ANSWER_TIME'] === 300) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_5')?></option>
					<option value="600" <?if((int)$arResult['CONFIG']['NO_ANSWER_TIME'] === 600) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_10')?></option>
					<option value="900" <?if((int)$arResult['CONFIG']['NO_ANSWER_TIME'] === 900) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_15')?></option>
					<option value="1800" <?if((int)$arResult['CONFIG']['NO_ANSWER_TIME'] === 1800) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_30')?></option>

					<option value="3600" <?if((int)$arResult['CONFIG']['NO_ANSWER_TIME'] === 3600) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_60')?></option>
					<option value="7200" <?if((int)$arResult['CONFIG']['NO_ANSWER_TIME'] === 7200) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_120')?></option>
					<option value="10800" <?if((int)$arResult['CONFIG']['NO_ANSWER_TIME'] === 10800) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_180')?></option>
					<option value="21600" <?if((int)$arResult['CONFIG']['NO_ANSWER_TIME'] === 21600) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_360')?></option>
					<option value="28800" <?if((int)$arResult['CONFIG']['NO_ANSWER_TIME'] === 28800) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_480')?></option>
					<option value="43200" <?if((int)$arResult['CONFIG']['NO_ANSWER_TIME'] === 43200) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_720')?></option>
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
						<option value="<?=$value?>" <?if($arResult['CONFIG']['NO_ANSWER_RULE'] === $value) { ?>selected<? }?> <?if($value === 'disabled') { ?>disabled<? }?>>
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
						<option value="<?=$value?>" <?if($arResult['CONFIG']['NO_ANSWER_FORM_ID'] === $value) { ?>selected<? }?> <?if($value === 'disabled') { ?>disabled<? }?>>
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
								<?if($value === 'disabled') { ?>disabled<? }?>>
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
					<option value="0" <?if((int)$arResult['CONFIG']['FULL_CLOSE_TIME'] === 0) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_FULL_CLOSE_TIME_0')?></option>
					<option value="1" <?if((int)$arResult['CONFIG']['FULL_CLOSE_TIME'] === 1) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_FULL_CLOSE_TIME_1')?></option>
					<option value="2" <?if((int)$arResult['CONFIG']['FULL_CLOSE_TIME'] === 2) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_FULL_CLOSE_TIME_2')?></option>
					<option value="5" <?if((int)$arResult['CONFIG']['FULL_CLOSE_TIME'] === 5) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_FULL_CLOSE_TIME_5')?></option>
					<option value="10" <?if((int)$arResult['CONFIG']['FULL_CLOSE_TIME'] === 10 || !isset($arResult['CONFIG']['FULL_CLOSE_TIME'])) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_FULL_CLOSE_TIME_10')?></option>
					<option value="30" <?if((int)$arResult['CONFIG']['FULL_CLOSE_TIME'] === 30) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_FULL_CLOSE_TIME_30')?></option>
					<option value="60" <?if((int)$arResult['CONFIG']['FULL_CLOSE_TIME'] === 60) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_FULL_CLOSE_TIME_60')?></option>
					<option value="1440" <?if((int)$arResult['CONFIG']['FULL_CLOSE_TIME'] === 1440) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_FULL_CLOSE_TIME_1_D')?></option>
				</select>
			</div>
		</div>
		<div class="imopenlines-control-container imopenlines-control-select" id="imol_queue_time">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_TIME')?>
			</div>
			<div class="imopenlines-control-inner">
				<select class="imopenlines-control-input" name="CONFIG[AUTO_CLOSE_TIME]">
					<option value="3600" <?if((int)$arResult['CONFIG']['AUTO_CLOSE_TIME'] === 3600) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_TIME_1_H')?></option>
					<option value="14400" <?if((int)$arResult['CONFIG']['AUTO_CLOSE_TIME'] === 14400) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_TIME_4_H')?></option>
					<option value="28800" <?if((int)$arResult['CONFIG']['AUTO_CLOSE_TIME'] === 28800) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_TIME_8_H')?></option>
					<option value="86400" <?if((int)$arResult['CONFIG']['AUTO_CLOSE_TIME'] === 86400) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_TIME_1_D')?></option>
					<option value="172800" <?if((int)$arResult['CONFIG']['AUTO_CLOSE_TIME'] === 172800) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_TIME_2_D')?></option>
					<option value="604800" <?if((int)$arResult['CONFIG']['AUTO_CLOSE_TIME'] === 604800) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_TIME_1_W')?></option>
					<option value="2678400" <?if((int)$arResult['CONFIG']['AUTO_CLOSE_TIME'] === 2678400) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_AUTO_CLOSE_TIME_1_M')?></option>
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
						<option value="<?=$value?>" <?if($arResult['CONFIG']['AUTO_CLOSE_RULE'] === $value) { ?>selected<? }?> <?if($value === 'disabled') { ?>disabled<? }?>>
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
				<select name="CONFIG[QUICK_ANSWERS_IBLOCK_ID]" class="imopenlines-control-input"<?if($arResult['CAN_USE_QUICK_ANSWERS'] === false):?> disabled="disabled"<?endif;?>>
					<?
					//$arResult['CAN_USE_QUICK_ANSWERS']
					foreach($arResult['QUICK_ANSWERS_STORAGE_LIST'] as $id => $item)
					{
						?>
						<option value="<?=(int)$id ?>"<?if($id == $arResult['CONFIG']['QUICK_ANSWERS_IBLOCK_ID']){?> selected<?}?>>
							<?=htmlspecialcharsbx($item['NAME']);?>
						</option>
						<?
					}
					?>
				</select>
				<div class="ui-btn ui-btn-light-border" id="<?if($arResult['CAN_USE_QUICK_ANSWERS'] === true):?>imol_quick_answer_manage<?else:?>imol_quick_answer_manage_not_can_use<?endif;?>" data-url="<?=$arResult['QUICK_ANSWERS_MANAGE_URL']?>">
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
			<label class="imopenlines-control-checkbox-label" <? if ($arResult['CAN_WATCH_TYPING'] === false) { ?> style="color: #a9adb2;" <? } ?>>
				<input type="checkbox"
					   id="imol_watch_typing"
					   name="CONFIG[WATCH_TYPING]"
					   value="Y"
					   class="imopenlines-control-checkbox"
					   <? if ($arResult['CAN_WATCH_TYPING'] === false) { ?> disabled="disabled" <? } ?>
					   <? if ($arResult['CAN_WATCH_TYPING'] === true && $arResult['CONFIG']['WATCH_TYPING'] === 'Y') { ?>checked<? } ?>
				>
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_WATCH_TYPING')?>
			</label>
			<? if ($arResult['CAN_WATCH_TYPING'] === false):?>
				<div class="imopenlines-control-subtitle" id="imol_watch_typing_not_available">
					<a class="bx-helpdesk-link" onclick="top.BX.Helper.show('redirect=detail&code=12715116')">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_WATCH_TYPING_NOT_AVAILABLE')?>
					</a>
				</div>
			<? endif; ?>
			<div class="imopenlines-control-subtitle imopenlines-control-subtitle-answer">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_WATCH_TYPING_DESC')?>
			</div>
		</div>
	</div>
</div>
