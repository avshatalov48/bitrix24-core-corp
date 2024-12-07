<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

/**
 * @var array $arParams
 * @var array $arResult
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global \CDatabase $DB
 * @var \CBitrixComponentTemplate $this
 * @var \CBitrixComponent $component
 */

use Bitrix\Imopenlines\Limit;
use Bitrix\Main\Localization\Loc;
use Bitrix\ImOpenLines\Config;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);
?>
<script>
	BX.ready(function(){
		BX.message({
			LM_ADD: '<?=GetMessageJS('IMOL_CONFIG_EDIT_LM_ADD')?>',
			'LM_QUEUE_USER_NAME': '<?=GetMessageJS('IMOL_CONFIG_QUEUE_USER_NAME_PLACEHOLDER')?>',
			'LM_QUEUE_USER_WORK_POSITION': '<?=GetMessageJS('IMOL_CONFIG_QUEUE_USER_WORK_POSITION_PLACEHOLDER')?>',
			'LM_HEAD_DEPARTMENT_EXCLUDED_QUEUE': '<?=GetMessageJS('IMOL_CONFIG_HEAD_DEPARTMENT_EXCLUDED_QUEUE')?>',
		});
		BX.OpenLinesConfigEdit.initQueue(
			{
				queueInputNode: BX('users_for_queue'),
				userInputNode: BX('users_for_queue_data'),
				defaultUserInputNode: BX('default_user_data'),
			},
			{
				queue: <?=CUtil::PhpToJSObject($arResult['QUEUE'])?>,
				defaultOperator: <?=CUtil::PhpToJSObject($arResult['CONFIG']['DEFAULT_OPERATOR_DATA'])?>,
			}
		);
	});
</script>
<div style="display: none">
	<script type="text/html" id="user_data_input_template">
		<?=getImolUserDataInputElement()?>
	</script>
	<script type="text/html" id="default_user_data_input_template">
		<?=getImolDefaultUserDataInputElement()?>
	</script>
</div>
<?
if ($arResult['CAN_EDIT'])
{
	CJSCore::Init(['avatar_editor']);
	?>
	<div style="display: none">
		<script type="text/html" id="user_data_avatar_template">
			<?=getImolUserDataAvatarTemplate()?>
		</script>
		<div id="imol_user_data_avatar_upload" class="imopenlines-user-photo-upload">
			<div class="imopenlines-user-photo-upload-container">
				<div class="imopenlines-user-photo-upload-item-container-title">
					<span><?=Loc::getMessage('IMOL_CONFIG_AVATAR_LOADED')?></span>
				</div>
				<div class="imopenlines-user-photo-upload-item-added-btn-container">
					<span data-imopenlines-user-photo-edit="" class="imopenlines-user-photo-upload-item-added-btn">
						<span class="imopenlines-user-photo-upload-item-added-btn-inner"></span>
						<span class="imopenlines-user-photo-upload-item-added-btn-inner-bg"></span>
					</span>
					<div class="imopenlines-user-photo-upload-item-added-completed-container">
						<div class="imopenlines-user-photo-upload-item-added-completed-slider-container">
							<div data-imopenlines-user-photo-edit-avatars="" class="imopenlines-user-photo-upload-item-added-completed-block">
							</div>
						</div>

					</div>

					<div style="clear: both;"></div>
				</div>
			</div>

			<div class="imopenlines-user-photo-uploaded-container">
				<div class="imopenlines-user-photo-uploaded-item-container-title">
					<span><?=Loc::getMessage('IMOL_CONFIG_AVATAR_PRESET')?></span>
				</div>
				<?foreach($arResult['HELLO']['ICONS'] as $icon):?>
					<span data-imopenlines-user-photo-edit-avatar-item=""
						  data-file-id=""
						  data-path="<?=htmlspecialcharsbx($icon['PATH'])?>"
						  class="imopenlines-user-photo-upload-item-container">
						<span style="background-image: url(<?=htmlspecialcharsbx($icon['PATH'])?>)"
							  class="imopenlines-user-photo-upload-item"></span>
						<span class="imopenlines-user-photo-upload-item-selected"></span>
					</span>
				<?endforeach;?>
				<div style="clear: both;"></div>
			</div>
		</div>
	</div>
	<?
}
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
		<a name="<?=$arResult['QUEUE']['blockIdQueueInput']?>"></a>
		<div class="imopenlines-form-settings-inner">
			<div class="imopenlines-control-subtitle">
				<?=Loc::getMessage('IMOL_CONFIG_EDIT_RESPONSIBLE_USERS_QUEUE')?>
				<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_DESC'))?>"></span>
			</div>
			<span id="<?=$arResult['QUEUE']['blockIdQueueInput']?>">
			</span>
			<div id="users_for_queue" style="height: 100%;"></div>
		</div>
		<div class="imopenlines-form-settings-inner">
			<div class="imopenlines-control-container imopenlines-control-select">
				<div class="imopenlines-control-subtitle">
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TYPE')?>
					<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TYPE_TIP_ALL_NEW'))?>"></span>
				</div>
				<div class="imopenlines-control-inner">
					<select name="CONFIG[QUEUE_TYPE]" id="imol_queue_type" class="imopenlines-control-input">
						<option value="evenly" <?if($arResult['CONFIG']['QUEUE_TYPE'] === Config::QUEUE_TYPE_EVENLY) { ?>selected<? }?>>
							<?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TYPE_EVENLY')?>
						</option>
						<option value="strictly" <?if($arResult['CONFIG']['QUEUE_TYPE'] === Config::QUEUE_TYPE_STRICTLY) { ?>selected<? }?>>
							<?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TYPE_STRICTLY')?>
						</option>
						<option value="all" <?if($arResult['CONFIG']['QUEUE_TYPE'] === Config::QUEUE_TYPE_ALL) { ?>selected<? }?>>
							<?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TYPE_ALL')?>
						</option>
					</select>
				</div>
			</div>
		</div>
	</div>
	<div class="imopenlines-form-settings-block">
		<div class="imopenlines-form-settings-inner">
			<div class="imopenlines-control-container imopenlines-control-select  <?if($arResult['VISIBLE']['QUEUE_TIME'] == false) { ?>invisible<? } ?>" id="imol_workers_time_block">
				<div class="imopenlines-control-subtitle" id="imol_queue_time_title">
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_NEW')?>
				</div>
				<div class="imopenlines-control-inner">
					<select class="imopenlines-control-input" name="CONFIG[QUEUE_TIME]">
						<option value="60" <?if((int)$arResult['CONFIG']['QUEUE_TIME'] === 60) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_1')?></option>
						<option value="180" <?if((int)$arResult['CONFIG']['QUEUE_TIME'] === 180) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_3')?></option>
						<option value="300" <?if((int)$arResult['CONFIG']['QUEUE_TIME'] === 300) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_5')?></option>
						<option value="600" <?if((int)$arResult['CONFIG']['QUEUE_TIME'] === 600) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_10')?></option>
						<option value="900" <?if((int)$arResult['CONFIG']['QUEUE_TIME'] === 900) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_15')?></option>
						<option value="1800" <?if((int)$arResult['CONFIG']['QUEUE_TIME'] === 1800) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_30')?></option>

						<option value="3600" <?if((int)$arResult['CONFIG']['QUEUE_TIME'] === 3600) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_60')?></option>
						<option value="7200" <?if((int)$arResult['CONFIG']['QUEUE_TIME'] === 7200) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_120')?></option>
						<option value="10800" <?if((int)$arResult['CONFIG']['QUEUE_TIME'] === 10800) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_180')?></option>
						<option value="21600" <?if((int)$arResult['CONFIG']['QUEUE_TIME'] === 21600) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_360')?></option>
						<option value="28800" <?if((int)$arResult['CONFIG']['QUEUE_TIME'] === 28800) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_480')?></option>
						<option value="43200" <?if((int)$arResult['CONFIG']['QUEUE_TIME'] === 43200) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_QUEUE_TIME_720')?></option>
					</select>
				</div>
			</div>
			<div id="imol_check_available_block" class="imopenlines-control-checkbox-container">
				<label class="imopenlines-control-checkbox-label">
					<input id="imol_check_available"
						   type="checkbox"
						   name="CONFIG[CHECK_AVAILABLE]"
						   value="Y"
						   class="imopenlines-control-checkbox"
						   <?if($arResult['CONFIG']['CHECK_AVAILABLE'] === 'Y') { ?>checked<? }?>>
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_CHECK_OPERATOR_AVAILABLE')?>
					<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_CHECK_OPERATOR_AVAILABLE_DESC'))?>"></span>
				</label>
			</div>


			<?php /*
			<div id="imol_check_online_block" class="imopenlines-control-checkbox-container<?if ($arResult['VISIBLE']['CHECK_ONLINE_BLOCK'] == false){?> invisible<?}?>">
				<label class="imopenlines-control-checkbox-label">
					<input id="imol_check_online"
						   type="checkbox"
						   name="CONFIG[CHECK_ONLINE]"
						   value="Y"
						   class="imopenlines-control-checkbox"
						   <?if($arResult['CONFIG']['CHECK_ONLINE'] === 'Y') { ?>checked<? }?>>
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_CHECK_OPERATOR_ONLINE')?>
				</label>
			</div>
			*/ ?>
			<div id="imol_limitation_max_chat_block" <? if ($arResult['VISIBLE']['LIMITATION_MAX_CHAT'] === false) { ?>class="invisible"<? } ?>>
				<div class="imopenlines-control-checkbox-container">
					<label class="imopenlines-control-checkbox-label">
						<input id="imol_limitation_max_chat"
							   type="checkbox"
							   name="CONFIG[LIMITATION_MAX_CHAT]"
							   value="Y"
							   class="imopenlines-control-checkbox"
							   <? if ($arResult['VISIBLE']['MAX_CHAT'] !== false) { ?>checked<? }?>>
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_LIMITATION_MAX_CHAT_TITLE_NEW');?>
						<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_LIMITATION_MAX_CHAT_DESC'))?>"></span>
					</label>
				</div>
				<div <? if ($arResult['VISIBLE']['MAX_CHAT'] === false) {?>class="invisible"<?}?> id="imol_max_chat">
					<div class="imopenlines-control-container imopenlines-control-select">
						<div class="imopenlines-control-subtitle">
							<?= Loc::getMessage('IMOL_CONFIG_EDIT_TYPE_MAX_CHAT_TITLE_NEW') ?>
							<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_TYPE_MAX_CHAT_TIP_NEW'))?>"></span>
						</div>
						<div class="imopenlines-control-inner">
							<select class="imopenlines-control-input" name="CONFIG[TYPE_MAX_CHAT]">
								<option value="answered_new" <?if($arResult['CONFIG']['TYPE_MAX_CHAT'] === 'answered_new' || empty($arResult['CONFIG']['TYPE_MAX_CHAT'])) { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_TYPE_MAX_CHAT_OPTION_ANSWERED_NEW')?></option>
								<option value="answered" <?if($arResult['CONFIG']['TYPE_MAX_CHAT'] === 'answered') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_TYPE_MAX_CHAT_OPTION_ANSWERED')?></option>
								<option value="closed" <?if($arResult['CONFIG']['TYPE_MAX_CHAT'] === 'closed') { ?>selected<? }?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_TYPE_MAX_CHAT_OPTION_CLOSED')?></option>
							</select>
						</div>
					</div>
					<div class="imopenlines-control-container">
						<div class="imopenlines-control-subtitle">
							<?= Loc::getMessage('IMOL_CONFIG_EDIT_MAX_CHAT_TITLE_NEW') ?>
						</div>
						<div class="imopenlines-control-inner width-80">
							<input type="text" name="CONFIG[MAX_CHAT]" class="imopenlines-control-input" value="<?=$arResult['CONFIG']['MAX_CHAT']?>">
						</div>
					</div>
				</div>
			</div>
			<?
			if(defined('IMOL_FDC'))
			{
				?>
				<div class="imopenlines-form-settings-inner">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_LANG_SESSION_PRIORITY')?>
						<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_LANG_SESSION_PRIORITY_TIP_NEW'))?>"></span>
					</div>
					<div class="imopenlines-control-inner width-80">
						<input type="number"
							   min="0"
							   max="86400"
							   class="imopenlines-control-input"
							   name="CONFIG[SESSION_PRIORITY]"
							   value="<?=htmlspecialcharsbx($arResult['CONFIG']['SESSION_PRIORITY'])?>">
					</div>
					<div class="imopenlines-control-subtitle"><?=Loc::getMessage('IMOL_CONFIG_EDIT_LANG_SESSION_PRIORITY_2')?></div>
				</div>
				<?
			}
			?>
		</div>
	</div>
</div>
<div class="imopenlines-form-settings-section">
	<div class="imopenlines-form-settings-block">
		<div class="imopenlines-form-settings-inner">
			<div class="imopenlines-control-container imopenlines-control-select">
				<div class="imopenlines-control-subtitle">
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_OPERATOR_DATA')?>
					<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_OPERATOR_DATA_TIP'))?>"></span>
				</div>
				<div class="imopenlines-control-inner">
					<select name="CONFIG[OPERATOR_DATA]" id="imol_operator_data" class="imopenlines-control-input">
						<option value="profile" <?if($arResult['CONFIG']['OPERATOR_DATA'] === 'profile') { ?>selected<? }?>>
							<?=Loc::getMessage('IMOL_CONFIG_EDIT_OPERATOR_DATA_PROFILE')?>
						</option>
						<option value="queue" <?if($arResult['CONFIG']['OPERATOR_DATA'] === 'queue') { ?>selected<? }?>>
							<?=Loc::getMessage('IMOL_CONFIG_EDIT_OPERATOR_DATA_QUEUE')?>
						</option>
						<option value="hide" <?if($arResult['CONFIG']['OPERATOR_DATA'] === 'hide') { ?>selected<? }?>>
							<?=Loc::getMessage('IMOL_CONFIG_EDIT_OPERATOR_DATA_HIDE')?>
						</option>
					</select>
				</div>
			</div>

			<div id="users_for_queue_data" <?if($arResult['CONFIG']['OPERATOR_DATA'] !== 'queue') { ?>class="invisible"<? }?>>
			</div>
			<div class="imopenlines-form-settings-user <?if($arResult['CONFIG']['OPERATOR_DATA'] !== 'hide') { ?>invisible<? }?>" id="default_user_data" data-id="default-user">
				<div class="imopenlines-form-settings-user-info">
					<span class="imopenlines-form-settings-user-name"><?=Loc::getMessage('IMOL_CONFIG_EDIT_DEFAULT_OPERATOR_DATA_TITLE')?></span>
					<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_DEFAULT_OPERATOR_DATA_TIP'))?>"></span>
				</div>
			</div>
		</div>
	</div>
	<div class="imopenlines-form-settings-block">
		<div class="imopenlines-form-settings-inner">
			<div class="imopenlines-control-checkbox-container">
				<label class="imopenlines-control-checkbox-label">
					<input type="checkbox"
						   name="CONFIG[CRM]"
						   value="Y"
						   id="imol_crm_checkbox"
						   class="imopenlines-control-checkbox"
						<? if ($arResult['CONFIG']['CRM'] === 'Y'  && $arResult['IS_CRM_INSTALLED'] === 'Y') { ?>
							checked
						<? } elseif($arResult['IS_CRM_INSTALLED'] !== 'Y') { ?>
							disabled
						<? } ?>>
					<?=Loc::getMessage('IMOL_CONFIG_EDIT_CRM')?>
				</label>
			</div>
			<?
			if ($arResult['IS_CRM_INSTALLED'] !== 'Y')
			{
				?>
				<div class="imopenlines-control-checkbox-container">
					<label class="imopenlines-control-checkbox-label">
						<?= Loc::getMessage('IMOL_CONFIG_EDIT_CRM_DISABLED') ?>
					</label>
				</div>
				<?
			}
			?>
			<?if(!empty($arResult['CRM'])):?>
			<div id="imol_crm_block" <?if (!($arResult['CONFIG']['CRM'] === 'Y'  && $arResult['IS_CRM_INSTALLED'] === 'Y')){?>class="invisible"<?}?>>
				<div class="imopenlines-control-checkbox-container">
					<label class="imopenlines-control-checkbox-label">
						<input type="checkbox"
							   class="imopenlines-control-checkbox"
							   name="CONFIG[CRM_CHAT_TRACKER]"
							   id="imol_crm_chat_tracker"
							   value="Y"
							   <?if($arResult['CONFIG']['CRM_CHAT_TRACKER'] === 'Y') { ?>checked<? }?>>
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_CRM_CHAT_TRACKER')?>
					</label>
				</div>

				<div class="imopenlines-control-container imopenlines-control-select">
					<div class="imopenlines-control-subtitle">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_CRM_CREATE')?>
						<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_CRM_CREATE_LEAD_DESC'))?>"></span>
					</div>
					<div class="imopenlines-control-inner">
						<select name="CONFIG[CRM_CREATE]" id="imol_crm_create" class="imopenlines-control-input">
							<?foreach ($arResult['CRM']['CRM_CREATE_ITEMS'] as $option):?>
								<option value="<?=$option['ID']?>" <?if($option['SELECT']): ?>selected<?endif;?>>
									<?=htmlspecialcharsbx($option['NAME'])?>
								</option>
							<?endforeach;?>
						</select>
					</div>
				</div>

				<?foreach ($arResult['CRM']['CRM_CREATE_ITEMS'] as $option):?>
					<?if(
							!empty($option['SECOND_ITEMS']) ||
							!empty($option['THIRD_NAME'])
					):?>
					<div<?if(!$option['SELECT']): ?> class="invisible"<?endif;?> id="imol_crm_create_second_<?=$option['ID']?>">
						<div class="imopenlines-control-container imopenlines-control-select">
							<?if(!empty($option['SECOND_ITEMS'])):?>
								<?if(!empty($option['SECOND_ITEMS_NAME'])):?>
								<div class="imopenlines-control-subtitle">
									<?=$option['SECOND_ITEMS_NAME']?>
								</div>
								<?endif;?>
							<div class="imopenlines-control-inner">
								<select name="CONFIG[CRM_CREATE_SECOND]" id="imol_crm_create_second_<?=$option['ID']?>" class="imopenlines-control-input">
									<?php
									foreach ($option['SECOND_ITEMS'] as $secondItems)
									{
										?>
										<option value="<?=$secondItems['ID']?>" <?if($secondItems['SELECT']):?>selected<?endif;?> >
											<?=htmlspecialcharsbx($secondItems['NAME'])?>
										</option>
										<?
									}
									?>
								</select>
							</div>
							<?endif;?>
						</div>
						<?if(!empty($option['THIRD_NAME'])):?>
							<div class="imopenlines-control-checkbox-container">
								<label class="imopenlines-control-checkbox-label">
									<input type="checkbox"
										   class="imopenlines-control-checkbox"
										   name="CONFIG[CRM_CREATE_THIRD]"
										   value="Y"
										   <?if($option['THIRD_SELECT']) { ?>checked<? }?>>
									<?=htmlspecialcharsbx($option['THIRD_NAME'])?>
								</label>
							</div>
						<?endif;?>
					</div>
					<?endif;?>
				<?endforeach;?>
				<div class="imopenlines-control-container imopenlines-control-select" id="imol_crm_source">
					<div id="imol_crm_source_title" class="imopenlines-control-subtitle<?if($arResult['CRM']['VISIBLE']['SOURCE_DEAL_TITLE']):?> invisible<?endif?>">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_CRM_SOURCE')?>
						<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_CRM_SOURCE_TIP_NEW'))?>"></span>
					</div>
					<div id="imol_crm_source_title_deal" class="imopenlines-control-subtitle<?if(!$arResult['CRM']['VISIBLE']['SOURCE_DEAL_TITLE']):?> invisible<?endif?>">
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_CRM_SOURCE_DEAL')?>
						<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_CRM_SOURCE_TIP_NEW'))?>"></span>
					</div>
					<div class="imopenlines-control-inner">
						<select name="CONFIG[CRM_SOURCE]" id="imol_crm_source_select" class="imopenlines-control-input">
							<?php
							foreach ($arResult['CRM_SOURCES'] as $value => $name)
							{
								?>
								<option value="<?=$value?>" <?if($arResult['CONFIG']['CRM_SOURCE'] === (string)$value) { ?>selected<? }?> >
									<?=htmlspecialcharsbx($name)?>
								</option>
								<?
							}
							?>
						</select>
					</div>
				</div>
				<div class="imopenlines-control-checkbox-container">
					<label class="imopenlines-control-checkbox-label">
						<input type="checkbox"
							   class="imopenlines-control-checkbox"
							   name="CONFIG[CRM_FORWARD]"
							   id="imol_crm_forward"
							   value="Y"
							   <?if($arResult['CONFIG']['CRM_FORWARD'] === 'Y') { ?>checked<? }?>>
						<?=Loc::getMessage('IMOL_CONFIG_EDIT_CRM_FORWARD_NEW')?>
						<span data-hint-html data-hint="<?=htmlspecialcharsbx(Loc::getMessage('IMOL_CONFIG_EDIT_CRM_FORWARD_TIP'))?>"></span>
					</label>
				</div>
				<div class="imopenlines-control-checkbox-container<?if(!$arResult['CRM']['VISIBLE']['CRM_TRANSFER_CHANGE']):?> invisible<?endif;?>"
					 id="imol_crm_source_rule">
					<label class="imopenlines-control-checkbox-label">
						<input type="checkbox"
							   class="imopenlines-control-checkbox"
							   name="CONFIG[CRM_TRANSFER_CHANGE]"
							   value="Y"
							   <?if($arResult['CONFIG']['CRM_TRANSFER_CHANGE'] === 'Y') { ?>checked<? }?>  >
						<span id="imol_crm_transfer_change_title"<?if($arResult['CRM']['VISIBLE']['CRM_TRANSFER_CHANGE'] !== Config::CRM_CREATE_LEAD):?> class="invisible"<?endif;?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_CRM_TRANSFER_CHANGE')?></span>
						<span id="imol_crm_transfer_change_title_deal"<?if($arResult['CRM']['VISIBLE']['CRM_TRANSFER_CHANGE'] !== Config::CRM_CREATE_DEAL):?> class="invisible"<?endif;?>><?=Loc::getMessage('IMOL_CONFIG_EDIT_CRM_TRANSFER_CHANGE_DEAL')?></span>
					</label>
				</div>
			</div>
			<?endif;?>
		</div>
	</div>
</div>
