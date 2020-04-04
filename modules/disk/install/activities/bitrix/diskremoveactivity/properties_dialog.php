<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
CJSCore::Init('file_dialog');
?>
<tr>
	<td align="right" width="40%">
		<span class="adm-required-field"><?= GetMessage("BPDRMV_PD_SOURCE_ID") ?>:</span>
		<br/><?= GetMessage("BPDRMV_PD_SOURCE_ID_DESCR") ?>
	</td>
	<td width="60%">
		<?
		$objectId = 0;
		$objectName = GetMessage('BPDRMV_PD_LABEL_DISK_EMPTY');
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
			<a href="#" id="id_source_id_clear" onclick="return BPDRMV_removeDiskObject()" style="<?=$objectId?'':'display: none;'?>color: red; text-decoration: none; border-bottom: 1px dotted">x</a>
			<br/>
			<a href="#" onclick="return BPDRMV_showDiskFileDialog()" style="color: black; text-decoration: none; border-bottom: 1px dotted"><?=GetMessage('BPDRMV_PD_LABEL_DISK_CHOOSE_FILE')?></a>
			<a href="#" onclick="return BPDRMV_showDiskFolderDialog()" style="color: black; text-decoration: none; border-bottom: 1px dotted"><?=GetMessage('BPDRMV_PD_LABEL_DISK_CHOOSE_FOLDER')?></a>
		</div>
		<?=\CBPDocument::ShowParameterField('int', 'source_id_x', CBPDocument::IsExpression($arCurrentValues['source_id'])? $arCurrentValues['source_id'] : '', array('size' => 30))?>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><?= GetMessage("BPDRMV_PD_DELETED_BY") ?>:</td>
	<td width="60%">
		<?=\CBPDocument::ShowParameterField('user', 'deleted_by', $arCurrentValues['deleted_by'], array('rows' => 1, 'size' => 29))?>
	</td>
</tr>
<script>
	var BPDRMV_showDiskFolderDialog = function()
	{
		var urlSelect = '/bitrix/tools/disk/uf.php?action=selectFile&dialog2=Y&SITE_ID=' + BX.message('SITE_ID');
		var dialogName = 'BPDRMV';

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
									BX('id_source_id_value').value = selected[i].id;
									BX('id_source_id_name').innerHTML = selected[i].name;
									BX('id_source_id_clear').style.display = '';
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

	var BPDRMV_showDiskFileDialog = function()
	{
		var urlSelect = '/bitrix/tools/disk/uf.php?action=selectFile&dialog2=Y&SITE_ID=' + BX.message('SITE_ID');
		var dialogName = 'BPDRMV';

		BX.ajax.get(urlSelect, 'multiselect=N&dialogName='+dialogName,
			BX.delegate(function() {
				setTimeout(BX.delegate(function() {
					BX.DiskFileDialog.obCallback[dialogName] = {'saveButton' :function(tab, path, selected)
					{
						var i;
						for (i in selected)
						{
							if (selected.hasOwnProperty(i))
							{
								if (selected[i].type == 'file')
								{
									BX('id_source_id_value').value = (selected[i].id).toString().substr(1);
									BX('id_source_id_name').innerHTML = selected[i].name;
									BX('id_source_id_clear').style.display = '';
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

	var BPDRMV_removeDiskObject = function()
	{
		BX('id_source_id_value').value = 0;
		BX('id_source_id_name').innerHTML = '<?=GetMessageJs('BPDRMV_PD_LABEL_DISK_EMPTY')?>';
		BX('id_source_id_clear').style.display = 'none';
		return false;
	}
</script>