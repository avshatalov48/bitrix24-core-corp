<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
CJSCore::Init('file_dialog');
?>
<tr>
	<td align="right" width="40%">
		<span class="adm-required-field"><?= GetMessage("BPDCM_PD_SOURCE_ID") ?>:</span>
		<br/><?= GetMessage("BPDCM_PD_SOURCE_ID_DESCR") ?>
	</td>
	<td width="60%">
		<?
		$objectId = 0;
		$objectName = GetMessage('BPDCM_PD_LABEL_DISK_EMPTY');
		if ($arCurrentValues['source_id'] && !CBPDocument::IsExpression($arCurrentValues['source_id']))
		{
			$object = \Bitrix\Disk\BaseObject::loadById($arCurrentValues['source_id']);
			if ($object)
			{
				$objectId = $object->getId();
				$objectName = $object->getName();
			}
		}
		?>
		<div style="padding: 3px;">
			<input type="hidden" name="source_id" id="id_source_id_value" value="<?=(int)$objectId?>"/>
			<span id="id_source_id_name" style="color: grey">
				<?=htmlspecialcharsbx($objectName)?>
			</span>
			<a href="#" id="id_source_id_clear" onclick="return BPDCM_removeDiskObject('source_id')" style="<?=$objectId?'':'display: none;'?>color: red; text-decoration: none; border-bottom: 1px dotted">x</a>
			<br/>
			<a href="#" onclick="return BPDCM_showDiskFileDialog('source_id')" style="color: black; text-decoration: none; border-bottom: 1px dotted"><?=GetMessage('BPDCM_PD_LABEL_DISK_CHOOSE_FILE')?></a>
			<a href="#" onclick="return BPDCM_showDiskFolderDialog('source_id')" style="color: black; text-decoration: none; border-bottom: 1px dotted"><?=GetMessage('BPDCM_PD_LABEL_DISK_CHOOSE_FOLDER')?></a>
		</div>
		<?=\CBPDocument::ShowParameterField('int', 'source_id_x', CBPDocument::IsExpression($arCurrentValues['source_id'])? $arCurrentValues['source_id'] : '', array('size' => 30))?>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPDCM_PD_OPERATION") ?>:</span></td>
	<td width="60%">
		<label>
			<input type="radio" name="operation" value="copy" <?if ($arCurrentValues['operation'] != 'move') echo 'checked'?>><?= GetMessage("BPDCM_PD_OPERATION_COPY") ?>
		</label>
		<br/>
		<label>
			<input type="radio" name="operation" value="move" <?if ($arCurrentValues['operation'] == 'move') echo 'checked'?>><?= GetMessage("BPDCM_PD_OPERATION_MOVE") ?>
		</label>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPDCM_PD_ENTITY") ?>:</span></td>
	<td width="60%">
		<select name="entity_type" onchange="BPDUA_changeEntityType(this.value);">
			<option value="user" <?if ($arCurrentValues['entity_type'] == 'user') echo 'selected'?>><?=GetMessage('BPDCM_PD_ENTITY_TYPE_USER')?></option>
			<? if (CModule::IncludeModule('socialnetwork')):?>
			<option value="sg" <?if ($arCurrentValues['entity_type'] == 'sg') echo 'selected'?>><?=GetMessage('BPDCM_PD_ENTITY_TYPE_SG')?></option>
			<?endif?>
			<option value="common" <?if ($arCurrentValues['entity_type'] == 'common') echo 'selected'?>><?=GetMessage('BPDCM_PD_ENTITY_TYPE_COMMON')?></option>
			<option value="folder" <?if ($arCurrentValues['entity_type'] == 'folder') echo 'selected'?>><?=GetMessage('BPDCM_PD_ENTITY_TYPE_FOLDER_1')?></option>
		</select>
	</td>
</tr>
<tr id="id_entity_id_user" <?if ($arCurrentValues['entity_type'] != 'user') echo 'style="display: none"'?>>
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPDCM_PD_ENTITY_ID_USER") ?>:</span></td>
	<td width="60%">
		<?
		$value = $arCurrentValues['entity_type'] == 'user' ? $arCurrentValues['entity_id'] : '';
		echo \CBPDocument::ShowParameterField('user', 'entity_id_user', $value, array('rows' => 1, 'cols' => 29))?>
	</td>
</tr>
<? if (CModule::IncludeModule('socialnetwork')):?>
<tr id="id_entity_id_sg" <?if ($arCurrentValues['entity_type'] != 'sg') echo 'style="display: none"'?>>
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPDCM_PD_ENTITY_ID_SG") ?>:</span></td>
	<td width="60%">
		<?
		$value = $arCurrentValues['entity_type'] == 'sg'? $arCurrentValues['entity_id'] : '';
		?>
		<select name="entity_id_sg">
			<option value=""><?= GetMessage("BPDCM_PD_LABEL_CHOOSE") ?></option>
			<?
			$iterator = CSocNetGroup::GetList(
					array('SITE_ID' => 'ASC', "NAME" => "ASC"),
					array("ACTIVE" => "Y"),
					false,
					false,
					array("ID", "NAME", "SITE_ID")
			);

			while($row = $iterator->fetch()):
				?>
				<option value="<?=htmlspecialcharsbx($row['ID'])?>" <?if ($row['ID'] == $value) echo 'selected'?>>[<?=htmlspecialcharsbx($row['SITE_ID'])?>] <?=htmlspecialcharsbx(\Bitrix\Main\Text\Emoji::decode($row['NAME']))?></option>
				<?
			endwhile;
			?>
		</select>
		<?
		echo \CBPDocument::ShowParameterField('int', 'entity_id_sg_x', CBPDocument::IsExpression($value) ? $value : '', array('size' => 30))?>
	</td>
</tr>
<?endif?>
<tr id="id_entity_id_common" <?if ($arCurrentValues['entity_type'] != 'common') echo 'style="display: none"'?>>
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPDCM_PD_ENTITY_ID_COMMON") ?>:</span></td>
	<td width="60%">
		<?
		$value = $arCurrentValues['entity_type'] == 'common'? $arCurrentValues['entity_id'] : '';
		?>
		<select name="entity_id_common">
			<option value=""><?= GetMessage("BPDCM_PD_LABEL_CHOOSE") ?></option>
			<?
			$iterator = \Bitrix\Disk\Storage::getList(array(
				'select' => array('NAME', 'ENTITY_ID', 'SITE_ID'),
				'filter' => array('=ENTITY_TYPE' => \Bitrix\Disk\ProxyType\Common::className()),
				'order' => array('SITE_ID' => 'ASC', 'NAME' => 'ASC')
			));

			while($row = $iterator->fetch()):
			?>
			<option value="<?=htmlspecialcharsbx($row['ENTITY_ID'])?>" <?if ($row['ENTITY_ID'] == $value) echo 'selected'?>>[<?=htmlspecialcharsbx($row['SITE_ID'])?>] <?=htmlspecialcharsbx($row['NAME'])?></option>
			<?
			endwhile;
			?>
		</select>
		<?
		echo \CBPDocument::ShowParameterField('string', 'entity_id_common_x', CBPDocument::IsExpression($value) ? $value : '', array('size' => 30))?>
	</td>
</tr>
<tr id="id_entity_id_folder" <?if ($arCurrentValues['entity_type'] != 'folder') echo 'style="display: none"'?>>
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPDCM_PD_ENTITY_ID_FOLDER") ?>:</span></td>
	<td width="60%">
		<?
		$value = $arCurrentValues['entity_type'] == 'folder'? $arCurrentValues['entity_id'] : '';

		$folderId = 0;
		$folderName = GetMessage('BPDCM_PD_LABEL_DISK_EMPTY');
		if ($value && !CBPDocument::IsExpression($value))
		{
			$folder = \Bitrix\Disk\Folder::loadById($value);
			if ($folder)
			{
				$folderId = $folder->getId();
				$folderName = $folder->getName();
			}
		}
		?>
		<div style="padding: 3px;">
			<input type="hidden" name="entity_id_folder" id="id_entity_id_folder_value" value="<?=(int)$folderId?>"/>
			<span id="id_entity_id_folder_name" style="color: grey">
				<?=htmlspecialcharsbx($folderName)?>
			</span>
			<a href="#" id="id_entity_id_folder_clear" onclick="return BPDCM_removeDiskObject('entity_id_folder')" style="<?=$folderId?'':'display: none;'?>color: red; text-decoration: none; border-bottom: 1px dotted">x</a>
			<br/>
			<a href="#" onclick="return BPDCM_showDiskFolderDialog('entity_id_folder')" style="color: black; text-decoration: none; border-bottom: 1px dotted"><?=GetMessage('BPDCM_PD_LABEL_DISK_CHOOSE_FOLDER')?></a>
		</div>
		<?
		echo \CBPDocument::ShowParameterField('int', 'entity_id_folder_x', CBPDocument::IsExpression($value) ? $value : '', array('size' => 30))
		?>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><?= GetMessage("BPDCM_PD_OPERATOR") ?>:</td>
	<td width="60%">
		<?=\CBPDocument::ShowParameterField('user', 'operator', $arCurrentValues['operator'], array('rows' => 1, 'size' => 29))?>
	</td>
</tr>
<script>
	var BPDUA_changeEntityType = function(type)
	{
		var i, s, types = ['user', 'sg', 'common', 'folder'];
		for (i=0,s=types.length; i<s; ++i)
		{
			BX('id_entity_id_'+types[i]).style.display = types[i] == type? '' : 'none';
		}
	};

	var BPDCM_showDiskFolderDialog = function(field)
	{
		var urlSelect = '/bitrix/tools/disk/uf.php?action=selectFile&dialog2=Y&SITE_ID=' + BX.message('SITE_ID');
		var dialogName = 'BPDCM';

		BX.ajax.get(urlSelect, 'wish=fakemove&dialogName='+dialogName,
			BX.delegate(function() {
				setTimeout(BX.delegate(function() {
					BX.DiskFileDialog.obElementBindPopup[dialogName].overlay = {
						backgroundColor: "#404040",
						opacity: ".1"
					};
					BX.DiskFileDialog.obCallback[dialogName] = {'saveButton' :function(tab, path, selected)
					{
						var i;
						for (i in selected)
						{
							if (selected.hasOwnProperty(i))
							{
								if (selected[i].type == 'folder')
								{
									BX('id_'+field+'_value').value = selected[i].id;
									BX('id_'+field+'_name').innerHTML = selected[i].name;
									BX('id_'+field+'_clear').style.display = '';
									break;
								}
							}
						}
					}};
					BX.DiskFileDialog.openDialog(dialogName);
				}, this), 10);
			}, this)
		);
		return false;
	};

	var BPDCM_showDiskFileDialog = function(field)
	{
		var urlSelect = '/bitrix/tools/disk/uf.php?action=selectFile&dialog2=Y&SITE_ID=' + BX.message('SITE_ID');
		var dialogName = 'BPDCM';

		BX.ajax.get(urlSelect, 'multiselect=N&dialogName='+dialogName,
			BX.delegate(function() {
				setTimeout(BX.delegate(function() {
					BX.DiskFileDialog.obElementBindPopup[dialogName].overlay = {
						backgroundColor: "#404040",
						opacity: ".1"
					};
					BX.DiskFileDialog.obCallback[dialogName] = {'saveButton' :function(tab, path, selected)
					{
						var i;
						for (i in selected)
						{
							if (selected.hasOwnProperty(i))
							{
								if (selected[i].type == 'file')
								{
									BX('id_'+field+'_value').value = (selected[i].id).toString().substr(1);
									BX('id_'+field+'_name').innerHTML = selected[i].name;
									BX('id_'+field+'_clear').style.display = '';
									break;
								}
							}
						}
					}};
					BX.DiskFileDialog.openDialog(dialogName);
				}, this), 10);
			}, this)
		);
		return false;
	};

	var BPDCM_removeDiskObject = function(field)
	{
		BX('id_'+field+'_value').value = 0;
		BX('id_'+field+'_name').innerHTML = '<?=GetMessageJs('BPDCM_PD_LABEL_DISK_EMPTY')?>';
		BX('id_'+field+'_clear').style.display = 'none';
		return false;
	}
</script>
