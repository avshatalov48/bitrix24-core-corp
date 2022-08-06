<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
CUtil::InitJSCore();

\Bitrix\Main\UI\Extension::load(['ui.alerts', 'ui.design-tokens']);

$canWriteConfig = ($arResult['ROLE_PERMS']['CONFIG']['WRITE']['-'] == 'X');
?>
<form action="<?=POST_FORM_ACTION_URI?>" name="crmPermForm" method="POST">
	<input type="hidden" name="ROLE_ID" value="<?=$arResult['ROLE']['ID']?>"/>
	<?=bitrix_sessid_post()?>
	<?=GetMessage('CRM_PERMS_FILED_NAME')?>: <input name="NAME" value="<?=htmlspecialcharsbx($arResult['ROLE']['NAME'])?>" class="crmPermRoleName"/>
	<br/>
	<br/>
	<table width="100%" cellpadding="0" cellspacing="0" class="crmPermRoleTable" id="crmPermRoleTable" >
		<tr>
			<th><?=GetMessage('CRM_PERMS_HEAD_ENTITY')?></th>
			<th><?=GetMessage('CRM_PERMS_HEAD_READ')?></th>
			<th><?=GetMessage('CRM_PERMS_HEAD_ADD')?></th>
			<th><?=GetMessage('CRM_PERMS_HEAD_WRITE')?></th>
			<th><?=GetMessage('CRM_PERMS_HEAD_DELETE')?></th>
			<th><?=GetMessage('CRM_PERMS_HEAD_EXPORT')?></th>
			<th><?=GetMessage('CRM_PERMS_HEAD_IMPORT')?></th>
			<th><?=GetMessage('CRM_PERMS_HEAD_AUTOMATION')?></th>
		</tr>
		<? foreach ($arResult['ENTITY'] as $entityType => $entityName): ?>
		<tr>
			<td><? if (isset($arResult['ENTITY_FIELDS'][$entityType])): ?><a href="javascript:void(0)" class="crmPermRoleTreePlus" onclick="CrmPermRoleShowRow(this)"></a><?endif;?><?=$entityName?></td>
			<td>
				<? if (in_array('READ', $arResult['ENTITY_PERMS'][$entityType])): ?>
				<span id="divPermsBox<?=$entityType?>Read" class="divPermsBoxText" onclick="CrmPermRoleShowBox(this.id)"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['READ']['-']]?></span>
				<span id="divPermsBox<?=$entityType?>Read_Select" style="display:none">
					<select id="divPermsBox<?=$entityType?>Read_SelectBox" name="ROLE_PERMS[<?=$entityType?>][READ][-]">
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['READ']['-'] ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
				<? endif; ?>
			</td>
			<td>
				<? if (in_array('ADD', $arResult['ENTITY_PERMS'][$entityType])): ?>
				<span id="divPermsBox<?=$entityType?>Add" class="divPermsBoxText" onclick="CrmPermRoleShowBox(this.id)"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['ADD']['-']]?></span>
				<span id="divPermsBox<?=$entityType?>Add_Select" style="display:none">
					<select id="divPermsBox<?=$entityType?>Add_SelectBox" name="ROLE_PERMS[<?=$entityType?>][ADD][-]">
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['ADD']['-'] ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
				<? endif; ?>
			</td>
			<td>
				<? if (in_array('WRITE', $arResult['ENTITY_PERMS'][$entityType])):
				//TODO: remove this crutch
				if ($entityType === 'SALETARGET')
				{
					$arResult['ROLE_PERM'][$entityType] = array(
						BX_CRM_PERM_NONE => $arResult['ROLE_PERM'][$entityType][BX_CRM_PERM_NONE],
						BX_CRM_PERM_ALL => $arResult['ROLE_PERM'][$entityType][BX_CRM_PERM_ALL]
					);
				}
				?>
				<span id="divPermsBox<?=$entityType?>Write" class="divPermsBoxText" onclick="CrmPermRoleShowBox(this.id)"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['WRITE']['-']]?></span>
				<span id="divPermsBox<?=$entityType?>Write_Select" style="display:none">
					<select id="divPermsBox<?=$entityType?>Write_SelectBox" name="ROLE_PERMS[<?=$entityType?>][WRITE][-]">
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['WRITE']['-'] ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
				<? endif; ?>
			</td>
			<td>
				<? if (in_array('DELETE', $arResult['ENTITY_PERMS'][$entityType])): ?>
				<span id="divPermsBox<?=$entityType?>Delete" class="divPermsBoxText" onclick="CrmPermRoleShowBox(this.id)"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['DELETE']['-']]?></span>
				<span id="divPermsBox<?=$entityType?>Delete_Select" style="display:none">
					<select id="divPermsBox<?=$entityType?>Delete_SelectBox" name="ROLE_PERMS[<?=$entityType?>][DELETE][-]">
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['DELETE']['-'] ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
				<? endif; ?>
			</td>
			<td>
				<? if (in_array('EXPORT', $arResult['ENTITY_PERMS'][$entityType])): ?>
				<span id="divPermsBox<?=$entityType?>Export" class="divPermsBoxText" onclick="CrmPermRoleShowBox(this.id)"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['EXPORT']['-']]?></span>
				<span id="divPermsBox<?=$entityType?>Export_Select" style="display:none">
					<select id="divPermsBox<?=$entityType?>Export_SelectBox" name="ROLE_PERMS[<?=$entityType?>][EXPORT][-]">
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['EXPORT']['-'] ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
				<? endif; ?>
			</td>
			<td>
				<? if (in_array('IMPORT', $arResult['ENTITY_PERMS'][$entityType])): ?>
				<span id="divPermsBox<?=$entityType?>Import" class="divPermsBoxText" onclick="CrmPermRoleShowBox(this.id)"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['IMPORT']['-']]?></span>
				<span id="divPermsBox<?=$entityType?>Import_Select" style="display:none">
					<select id="divPermsBox<?=$entityType?>Import_SelectBox" name="ROLE_PERMS[<?=$entityType?>][IMPORT][-]">
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['IMPORT']['-'] ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
				<? endif; ?>
			</td>
			<td>
				<?if (in_array('AUTOMATION', $arResult['ENTITY_PERMS'][$entityType])):
					$curVal = $canWriteConfig ? BX_CRM_PERM_ALL : $arResult['ROLE_PERMS'][$entityType]['AUTOMATION']['-'];
				?>
					<span id="divPermsBox<?=$entityType?>Automation" data-role="automation-perm" class="divPermsBoxText <?if ($canWriteConfig):?>divPermsBoxTextDisabled<?endif?>" onclick="if (!BX.hasClass(this, 'divPermsBoxTextDisabled')){CrmPermRoleShowBox(this.id);}"><?=$arResult['ROLE_PERM']['AUTOMATION'][$curVal]?></span>
					<span id="divPermsBox<?=$entityType?>Automation_Select" style="display:none">
					<select id="divPermsBox<?=$entityType?>Automation_SelectBox" name="ROLE_PERMS[<?=$entityType?>][AUTOMATION][-]">
						<? foreach ($arResult['ROLE_PERM']['AUTOMATION'] as $rolePermAtr => $rolePermName): ?>
							<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $curVal ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
						<? endforeach; ?>
					</select>
				</span>
				<?endif;?>
			</td>
		</tr>
		<?	if (isset($arResult['ENTITY_FIELDS'][$entityType])):
				foreach ($arResult['ENTITY_FIELDS'][$entityType] as $fieldID => $arFieldValue):
					foreach ($arFieldValue as $fieldValueID => $fieldValue):
		?>
		<tr class="crmPermRoleFields" style="display:none">
			<td><?=$fieldValue?></td>
			<td>
					<?
						$sOrigPermAttr = '-';
						if (isset($arResult['~ROLE_PERMS'][$entityType]['READ'][$fieldID]) && array_key_exists($fieldValueID, $arResult['~ROLE_PERMS'][$entityType]['READ'][$fieldID]))
							$sOrigPermAttr = $arResult['~ROLE_PERMS'][$entityType]['READ'][$fieldID][$fieldValueID];
					?>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Read" class="divPermsBoxText <?=(!isset($arResult['~ROLE_PERMS'][$entityType]['READ'][$fieldID][$fieldValueID]) ? 'divPermsBoxTextGray' : '')?>" onclick="CrmPermRoleShowBox(this.id, 'divPermsBox<?=$entityType?>Read')"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['READ'][$fieldID][$fieldValueID]]?></span>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Read_Select" style="display:none">

					<select id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Read_SelectBox" name="ROLE_PERMS[<?=$entityType?>][READ][<?=$fieldID?>][<?=$fieldValueID?>]">
						<option value="-" <?=('-' == $sOrigPermAttr ? 'selected="selected"' : '')?> class="divPermsBoxOptionGray"><?=GetMessage('CRM_PERMS_PERM_INHERIT')?></option>
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName):?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $sOrigPermAttr ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
			</td>
			<td>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Add" class="divPermsBoxText <?=(!isset($arResult['~ROLE_PERMS'][$entityType]['ADD'][$fieldID][$fieldValueID]) ? 'divPermsBoxTextGray' : '')?>" onclick="CrmPermRoleShowBox(this.id, 'divPermsBox<?=$entityType?>Add')"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['ADD'][$fieldID][$fieldValueID]]?></span>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Add_Select" style="display:none">
					<?
						$sOrigPermAttr = '-';
						if (isset($arResult['~ROLE_PERMS'][$entityType]['ADD'][$fieldID]) && array_key_exists($fieldValueID, $arResult['~ROLE_PERMS'][$entityType]['ADD'][$fieldID]))
							$sOrigPermAttr =  $arResult['~ROLE_PERMS'][$entityType]['ADD'][$fieldID][$fieldValueID];
					?>
					<select id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Add_SelectBox" name="ROLE_PERMS[<?=$entityType?>][ADD][<?=$fieldID?>][<?=$fieldValueID?>]">
						<option value="-" <?=('-' == $sOrigPermAttr ? 'selected="selected"' : '')?> class="divPermsBoxOptionGray"><?=GetMessage('CRM_PERMS_PERM_INHERIT')?></option>
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $sOrigPermAttr ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
			</td>
			<td>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Write" class="divPermsBoxText <?=(!isset($arResult['~ROLE_PERMS'][$entityType]['WRITE'][$fieldID][$fieldValueID]) ? 'divPermsBoxTextGray' : '')?>" onclick="CrmPermRoleShowBox(this.id, 'divPermsBox<?=$entityType?>Write')"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['WRITE'][$fieldID][$fieldValueID]]?></span>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Write_Select" style="display:none">
					<?
						$sOrigPermAttr = '-';
						if (isset($arResult['~ROLE_PERMS'][$entityType]['WRITE'][$fieldID]) && array_key_exists($fieldValueID, $arResult['~ROLE_PERMS'][$entityType]['WRITE'][$fieldID]))
							$sOrigPermAttr =  $arResult['~ROLE_PERMS'][$entityType]['WRITE'][$fieldID][$fieldValueID];
					?>
					<select id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Write_SelectBox" name="ROLE_PERMS[<?=$entityType?>][WRITE][<?=$fieldID?>][<?=$fieldValueID?>]">
						<option value="-" <?=('-' == $sOrigPermAttr ? 'selected="selected"' : '')?> class="divPermsBoxOptionGray"><?=GetMessage('CRM_PERMS_PERM_INHERIT')?></option>
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $sOrigPermAttr ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
			</td>
			<td>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Delete" class="divPermsBoxText <?=(!isset($arResult['~ROLE_PERMS'][$entityType]['DELETE'][$fieldID][$fieldValueID]) ? 'divPermsBoxTextGray' : '')?>" onclick="CrmPermRoleShowBox(this.id, 'divPermsBox<?=$entityType?>Delete')"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['DELETE'][$fieldID][$fieldValueID]]?></span>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Delete_Select" style="display:none">
					<?
						$sOrigPermAttr = '-';
						if (isset($arResult['~ROLE_PERMS'][$entityType]['DELETE'][$fieldID]) && array_key_exists($fieldValueID, $arResult['~ROLE_PERMS'][$entityType]['DELETE'][$fieldID]))
							$sOrigPermAttr =  $arResult['~ROLE_PERMS'][$entityType]['DELETE'][$fieldID][$fieldValueID];
					?>
					<select id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Delete_SelectBox" name="ROLE_PERMS[<?=$entityType?>][DELETE][<?=$fieldID?>][<?=$fieldValueID?>]">
						<option value="-" <?=('-' == $sOrigPermAttr ? 'selected="selected"' : '')?> class="divPermsBoxOptionGray"><?=GetMessage('CRM_PERMS_PERM_INHERIT')?></option>
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $sOrigPermAttr ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
			</td>
			<td>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Export" class="divPermsBoxText <?=(!isset($arResult['~ROLE_PERMS'][$entityType]['EXPORT'][$fieldID][$fieldValueID]) ? 'divPermsBoxTextGray' : '')?>" onclick="CrmPermRoleShowBox(this.id, 'divPermsBox<?=$entityType?>Export')"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['EXPORT'][$fieldID][$fieldValueID]]?></span>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Export_Select" style="display:none">
					<?
						$sOrigPermAttr = '-';
						if (isset($arResult['~ROLE_PERMS'][$entityType]['EXPORT'][$fieldID]) && array_key_exists($fieldValueID, $arResult['~ROLE_PERMS'][$entityType]['EXPORT'][$fieldID]))
							$sOrigPermAttr =  $arResult['~ROLE_PERMS'][$entityType]['EXPORT'][$fieldID][$fieldValueID];
					?>
					<select id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Export_SelectBox" name="ROLE_PERMS[<?=$entityType?>][EXPORT][<?=$fieldID?>][<?=$fieldValueID?>]">
						<option value="-" <?=('-' == $sOrigPermAttr ? 'selected="selected"' : '')?> class="divPermsBoxOptionGray"><?=GetMessage('CRM_PERMS_PERM_INHERIT')?></option>
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $sOrigPermAttr ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
			</td>
			<td>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Import" class="divPermsBoxText <?=(!isset($arResult['~ROLE_PERMS'][$entityType]['IMPORT'][$fieldID][$fieldValueID]) ? 'divPermsBoxTextGray' : '')?>" onclick="CrmPermRoleShowBox(this.id, 'divPermsBox<?=$entityType?>Import')"><?=$arResult['ROLE_PERM'][$entityType][$arResult['ROLE_PERMS'][$entityType]['IMPORT'][$fieldID][$fieldValueID]]?></span>
				<span id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Import_Select" style="display:none">
					<?
						$sOrigPermAttr = '-';
						if (isset($arResult['~ROLE_PERMS'][$entityType]['IMPORT'][$fieldID]) && array_key_exists($fieldValueID, $arResult['~ROLE_PERMS'][$entityType]['IMPORT'][$fieldID]))
							$sOrigPermAttr =  $arResult['~ROLE_PERMS'][$entityType]['IMPORT'][$fieldID][$fieldValueID];
					?>
					<select id="divPermsBox<?=$entityType.$fieldID.$fieldValueID?>Import_SelectBox" name="ROLE_PERMS[<?=$entityType?>][IMPORT][<?=$fieldID?>][<?=$fieldValueID?>]">
						<option value="-" <?=('-' == $sOrigPermAttr ? 'selected="selected"' : '')?> class="divPermsBoxOptionGray"><?=GetMessage('CRM_PERMS_PERM_INHERIT')?></option>
					<? foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName): ?>
						<option value="<?=$rolePermAtr?>" <?=($rolePermAtr == $sOrigPermAttr ? 'selected="selected"' : '')?>><?=$rolePermName?></option>
					<? endforeach; ?>
					</select>
				</span>
			</td>
			<td></td>
		</tr>
		<?
					endforeach;
				endforeach;
			endif;
		endforeach;
		?>
		<tr class="ConfigEdit">
			<td colspan="8"><input name="ROLE_PERMS[CONFIG][WRITE][-]" <?=($canWriteConfig ? 'checked="checked"' : '')?> value="X" id="crmConfigEdit" type="checkbox" /><label for="crmConfigEdit"><?=GetMessage("CRM_PERMS_PERM_ADD")?></label></td>
		</tr>
	</table>
	<br/>
	<div id="crmPermButtonBoxPlace">
		<? if ($arResult['ROLE']['ID'] > 0): ?>
		<div style="float:right; padding-right: 10px;">
			<?if($arResult['IS_PERMITTED']):?>
			<a href="<?=$arResult['PATH_TO_ROLE_DELETE']?>" onclick="CrmRoleDelete('<?=CUtil::JSEscape(GetMessage('CRM_PERMS_DLG_TITLE'))?>', '<?=CUtil::JSEscape(GetMessage('CRM_PERMS_DLG_MESSAGE'))?>', '<?=CUtil::JSEscape(GetMessage('CRM_PERMS_DLG_BTN'))?>', '<?=CUtil::JSEscape($arResult['PATH_TO_ROLE_DELETE'])?>'); return false;" style="color:#E00000">
				<?=GetMessage('CRM_PERMS_ROLE_DELETE')?>
			</a>
			<?else:?>
				<a href="#" onclick="<?=htmlspecialcharsbx($arResult['LOCK_SCRIPT'])?>; return false;" style="color:#E00000">
					<?=GetMessage('CRM_PERMS_ROLE_DELETE')?>
				</a>
			<? endif;?>
		</div>
		<? endif;?>
		<div align="left">
		<?if($arResult['IS_PERMITTED']):?>
			<button type="submit" name="save"><?=GetMessage('CRM_PERMS_BUTTONS_SAVE');?></button>
			<button type="submit" name="apply"><?=GetMessage('CRM_PERMS_BUTTONS_APPLY');?></button>
		<?else:?>
			<button type="button" onclick="<?=htmlspecialcharsbx($arResult['LOCK_SCRIPT'])?>"><?=GetMessage('CRM_PERMS_BUTTONS_SAVE');?></button>
			<button type="button" onclick="<?=htmlspecialcharsbx($arResult['LOCK_SCRIPT'])?>"><?=GetMessage('CRM_PERMS_BUTTONS_APPLY');?></button>
		<?endif;?>
		</div>
	</div>
</form>
<?if(!$arResult['IS_PERMITTED']):?>
	<div class="ui-alert ui-alert-warning" style="margin: 15px 0 0 0;">
		<span class="ui-alert-message"><?=GetMessage('CRM_PERMS_RESTRICTION')?></span>
	</div>
<?endif;?>
<script>
	BX.ready(function()
	{
		var helpText = '<?=CUtil::JSEscape(htmlspecialcharsbx(GetMessage('CRM_PERMS_AUTOMATION_DISABLED_HELP')))?>';

		var HelpHint = {
			popupHint: null,

			bindToNode: function(node)
			{
				BX.bind(node, 'mouseover', BX.proxy(function() {
					if (BX.hasClass(BX.proxy_context, 'divPermsBoxTextDisabled'))
					{
						this.showHint(BX.proxy_context);
					}
				}, this));
				BX.bind(node, 'mouseout', BX.delegate(this.hideHint, this));
			},
			showHint: function(node)
			{
				this.popupHint = new BX.PopupWindow('crm-perms-help-tip', node, {
					lightShadow: true,
					autoHide: false,
					darkMode: true,
					offsetLeft: 0,
					offsetTop: 2,
					bindOptions: {position: "top"},
					zIndex: 1100,
					events : {
						onPopupClose : function() {this.destroy()}
					},
					content : BX.create("div", { attrs : { style : "padding-right: 5px; width: 250px;" }, html: helpText})
				});
				this.popupHint.setAngle({offset:32, position: 'bottom'});
				this.popupHint.show();

				return true;
			},
			hideHint: function()
			{
				if (this.popupHint)
					this.popupHint.close();
				this.popupHint = null;
			}
		};

		var configEditCheckbox = BX('crmConfigEdit');
		if (configEditCheckbox)
		{
			var automationControls = Array.prototype.slice.call(document.querySelectorAll('[data-role="automation-perm"]'));
			var atmReadPerm = '<?=BX_CRM_PERM_NONE?>';
			var atmReadPermText = '<?=CUtil::JSEscape($arResult['ROLE_PERM']['AUTOMATION'][BX_CRM_PERM_NONE])?>';
			var atmWritePerm = '<?=BX_CRM_PERM_ALL?>';
			var atmWritePermText = '<?=CUtil::JSEscape($arResult['ROLE_PERM']['AUTOMATION'][BX_CRM_PERM_ALL])?>';

			BX.bind(configEditCheckbox, 'change', function()
			{
				var checked = this.checked;

				automationControls.forEach(function(control)
				{
					var select = BX(control.id + '_SelectBox');

					select.value = checked ? atmWritePerm : atmReadPerm;
					control.textContent = checked ? atmWritePermText : atmReadPermText;

					BX[checked ? 'addClass' : 'removeClass'](control, 'divPermsBoxTextDisabled');
				});
			});

			automationControls.forEach(function(control)
			{
				HelpHint.bindToNode(control);
			});
		}
	});
</script>
