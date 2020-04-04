<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
CJSCore::Init('file_dialog');
?>
<tr>
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPDAF_PD_ENTITY") ?>:</span></td>
	<td width="60%">
		<select name="entity_type" onchange="BPDAF_changeEntityType(this.value);">
			<option value="user" <?if ($arCurrentValues['entity_type'] == 'user') echo 'selected'?>><?=GetMessage('BPDAF_PD_ENTITY_TYPE_USER')?></option>
			<? if (CModule::IncludeModule('socialnetwork')):?>
			<option value="sg" <?if ($arCurrentValues['entity_type'] == 'sg') echo 'selected'?>><?=GetMessage('BPDAF_PD_ENTITY_TYPE_SG')?></option>
			<?endif?>
			<option value="common" <?if ($arCurrentValues['entity_type'] == 'common') echo 'selected'?>><?=GetMessage('BPDAF_PD_ENTITY_TYPE_COMMON')?></option>
			<option value="folder" <?if ($arCurrentValues['entity_type'] == 'folder') echo 'selected'?>><?=GetMessage('BPDAF_PD_ENTITY_TYPE_FOLDER')?></option>
		</select>
	</td>
</tr>
<tr id="id_entity_id_user" <?if ($arCurrentValues['entity_type'] != 'user') echo 'style="display: none"'?>>
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPDAF_PD_ENTITY_ID_USER") ?>:</span></td>
	<td width="60%">
		<?
		$value = $arCurrentValues['entity_type'] == 'user' ? $arCurrentValues['entity_id'] : '';
		echo \CBPDocument::ShowParameterField('user', 'entity_id_user', $value, array('rows' => 1, 'cols' => 29))?>
	</td>
</tr>
<? if (CModule::IncludeModule('socialnetwork')):?>
<tr id="id_entity_id_sg" <?if ($arCurrentValues['entity_type'] != 'sg') echo 'style="display: none"'?>>
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPDAF_PD_ENTITY_ID_SG") ?>:</span></td>
	<td width="60%">
		<?
		$value = $arCurrentValues['entity_type'] == 'sg'? $arCurrentValues['entity_id'] : '';
		?>
		<select name="entity_id_sg">
			<option value=""><?= GetMessage("BPDAF_PD_LABEL_CHOOSE") ?></option>
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
				<option value="<?=htmlspecialcharsbx($row['ID'])?>" <?if ($row['ID'] == $value) echo 'selected'?>>[<?=htmlspecialcharsbx($row['SITE_ID'])?>] <?=htmlspecialcharsbx($row['NAME'])?></option>
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
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPDAF_PD_ENTITY_ID_COMMON") ?>:</span></td>
	<td width="60%">
		<?
		$value = $arCurrentValues['entity_type'] == 'common'? $arCurrentValues['entity_id'] : '';
		?>
		<select name="entity_id_common">
			<option value=""><?= GetMessage("BPDAF_PD_LABEL_CHOOSE") ?></option>
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
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPDAF_PD_ENTITY_ID_FOLDER") ?>:</span></td>
	<td width="60%">
		<?
		$value = $arCurrentValues['entity_type'] == 'folder'? $arCurrentValues['entity_id'] : '';

		$folderId = 0;
		$folderName = GetMessage('BPDAF_PD_LABEL_DISK_EMPTY');
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
			<a href="#" id="id_entity_id_folder_clear" onclick="return BPDAF_removeDiskFolder()" style="<?=$folderId?'':'display: none;'?>color: red; text-decoration: none; border-bottom: 1px dotted">x</a>
			<br/>
			<a href="#" onclick="return BPDAF_showDiskDialog()" style="color: black; text-decoration: none; border-bottom: 1px dotted"><?=GetMessage('BPDAF_PD_LABEL_DISK_CHOOSE')?></a>
		</div>
		<?
		echo \CBPDocument::ShowParameterField('int', 'entity_id_folder_x', CBPDocument::IsExpression($value) ? $value : '', array('size' => 30))
		?>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPDAF_PD_FOLDER_NAME") ?>:</span></td>
	<td width="60%">
		<?=\CBPDocument::ShowParameterField('string', 'folder_name', $arCurrentValues['folder_name'], array('size' => 30))?>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><?= GetMessage("BPDAF_PD_FOLDER_AUTHOR") ?>:</td>
	<td width="60%">
		<?=\CBPDocument::ShowParameterField('user', 'created_by', $arCurrentValues['created_by'], array('rows' => 1, 'size' => 29))?>
	</td>
</tr>
<script>
	var BPDAF_changeEntityType = function(type)
	{
		var i, s, types = ['user', 'sg', 'common', 'folder'];
		for (i=0,s=types.length; i<s; ++i)
		{
			BX('id_entity_id_'+types[i]).style.display = types[i] == type? '' : 'none';
		}
	};

	var BPDAF_showDiskDialog = function()
	{
		var urlSelect = '/bitrix/tools/disk/uf.php?action=selectFile&dialog2=Y&SITE_ID=' + BX.message('SITE_ID');
		var dialogName = 'BPDAF';

		BX.ajax.get(urlSelect, 'wish=fakemove&dialogName='+dialogName,
			BX.delegate(function() {
				setTimeout(BX.delegate(function() {
					BX.DiskFileDialog.obCallback[dialogName] = {'saveButton' :function(tab, path, selected)
					{
						var i;
						for (i in selected)
						{
							if (selected.hasOwnProperty(i))
							{
								if (selected[i].type == 'folder')
								{
									BX('id_entity_id_folder_value').value = selected[i].id;
									BX('id_entity_id_folder_name').innerHTML = selected[i].name;
									BX('id_entity_id_folder_clear').style.display = 'inline-block';
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

	var BPDAF_removeDiskFolder = function()
	{
		BX('id_entity_id_folder_value').value = 0;
		BX('id_entity_id_folder_name').innerHTML = '<?=GetMessageJs('BPDAF_PD_LABEL_DISK_EMPTY')?>';
		BX('id_entity_id_folder_clear').style.display = 'none';
		return false;
	}
</script>