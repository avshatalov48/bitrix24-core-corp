<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$control_id = RandString(8);

if (count($arResult['MEETING']['AGENDA']) > 0):
	$arAgenda = array();
	foreach ($arResult['MEETING']['AGENDA'] as $key => $arItem)
	{
		if (!$arItem['INSTANCE_PARENT_ID'])
		{
			$arAgenda[] = $arItem;
			foreach ($arResult['MEETING']['AGENDA'] as $key1 => $arSubItem)
			{
				if ($arSubItem['INSTANCE_PARENT_ID'] == $arItem['ID'])
					$arAgenda[] = $arSubItem;
			}
		}
	}

?>
<script type="text/javascript">
var parentAgenda = {}, parentAgendaInstance = {}, parentAgendaRows = {};
<?
	foreach ($arResult['MEETING']['AGENDA'] as $item_id => $arItem):
		if (MakeTimeStamp($arItem['DEADLINE'])<=0)
			$arItem['DEADLINE'] = '';
?>
parentAgenda[<?=$arItem['ITEM_ID']?>] = <?=CUtil::PhpToJsObject($arItem, false, true)?>;
parentAgendaInstance[<?=$arItem['ID']?>] = '<?=$arItem['ITEM_ID']?>';
<?
	endforeach;
?>

function addItems()
{
	var items = BX.findChildren(BX('meeting_parent_items_<?=$control_id?>'), {tag: 'INPUT', property: {name: 'PARENT_ITEMS[]'}}, true);
	if (items.tagName) items = [items];
	if (items && items.length > 0)
	{
		for (var i=0, l=items.length; i<l; i++)
		{
			var item_id = items[i].value;

			if (!parentAgenda[item_id].OLD_ID)
			{
				parentAgenda[item_id].OLD_ID = parentAgenda[item_id].ID
				delete parentAgenda[item_id].ID
				delete parentAgenda[item_id].TASK_ID
				//parentAgenda[item_id].EDITABLE = false;
			}

			var row = parentAgendaRows[parentAgenda[item_id].OLD_ID];
			if (items[i].checked)
			{
				if (parentAgenda[item_id])
				{
					if (parentAgenda[item_id].INSTANCE_PARENT_ID === '0')
						parentAgenda[item_id].INSTANCE_PARENT_ID = 0;

					if (row && !!row.BXDELETED)
					{
						unDeleteRow(null, row);
					}

					if (!row)
					{
						parentAgenda[item_id].ORIGINAL_TYPE = '<?CMeetingInstance::TYPE_AGENDA?>';

						if (parentAgenda[item_id].INSTANCE_PARENT_ID && parentAgendaRows[parentAgenda[item_id].INSTANCE_PARENT_ID])
						{
							parentAgenda[item_id].INSTANCE_PARENT_ID = parentAgendaRows[parentAgenda[item_id].INSTANCE_PARENT_ID].BXINSTANCEKEY;
						}
						else if (!parentAgenda[item_id].INSTANCE_PARENT_ID_OLD)
						{
							parentAgenda[item_id].INSTANCE_PARENT_ID_OLD = parentAgenda[item_id].INSTANCE_PARENT_ID;
							parentAgenda[item_id].INSTANCE_PARENT_ID = 0;
						}

						if (!!parentAgenda[item_id].RESPONSIBLE && parentAgenda[item_id].RESPONSIBLE.length > 0)
						{
							if (!window.arMembersList[parentAgenda[item_id].RESPONSIBLE[0]])
							{
								parentAgenda[item_id].RESPONSIBLE = [];
							}
						}

						parentAgendaRows[parentAgenda[item_id].OLD_ID] = addRow(parentAgenda[item_id]);
					}
					else if (
						parentAgenda[item_id].INSTANCE_PARENT_ID_OLD
						&& !row.BXINSTANCE.INSTANCE_PARENT_ID
						&& parentAgendaRows[parentAgenda[item_id].INSTANCE_PARENT_ID_OLD]
					)
					{
						row.BXINSTANCE.INSTANCE_PARENT_ID =
						document.forms.meeting_edit['AGENDA_PARENT['+row.BXINSTANCEKEY+']'].value =
							parentAgendaRows[parentAgenda[item_id].INSTANCE_PARENT_ID_OLD].BXINSTANCEKEY;

						checkParent(row.BXINSTANCEKEY);
					}
				}
			}
			else if (row)
			{
				//deleteRow(null, parentAgendaRows[parentAgenda[item_id].OLD_ID]);
			}
		}

		saveData();
	}
}
</script>
<div class="meeting-new-checkbox-select-all" class="meeting-selectors">
	<span class="meeting-selector-item">
		<?if ($arResult['POPUP']):?><span class="meeting-sub-bl-checkbox-wrap" onclick="meeting_checkbox_checkall(this.firstChild)"><input type="checkbox" id="meeting-new-select-all-<?=$control_id?>" class="meeting-sub-bl-checkbox" onclick="meeting_checkbox_checkall(this)" /></span><?else:?><input type="checkbox" id="meeting-new-select-all-<?=$control_id?>" onclick="meeting_checkbox_checkall(this)" /><?endif;?><label for="meeting-new-select-all-<?=$control_id?>"><?=GetMessage('ME_SELECT_ALL')?></label>
	</span><span class="meeting-selector-item">
		<?if ($arResult['POPUP']):?><span class="meeting-sub-bl-checkbox-wrap" onclick="meeting_checkbox_checkall(this.firstChild, true)"><input type="checkbox" id="meeting-new-select-opened-<?=$control_id?>" class="meeting-sub-bl-checkbox" onclick="meeting_checkbox_checkall(this)" /></span><?else:?><input type="checkbox" id="meeting-new-select-opened-<?=$control_id?>" onclick="meeting_checkbox_checkall(this, true)" /><?endif;?><label for="meeting-new-select-opened-<?=$control_id?>"><?=GetMessage('ME_SELECT_OPENED')?></label>
	</span>
</div>
<div id="meeting_parent_items_<?=$control_id?>">
<?
	$ix = 1;
	$ix1 = 1;
	foreach ($arAgenda as $key => $arItem):
		$className = 'meeting-new-checkbox';

		$index = $ix.'.';

		if ($arItem['INSTANCE_PARENT_ID'])
		{
			$index = ($ix-1).'.'.($ix1++);
			$className .= ' meeting-new-sub-checkbox';
		}
		else
		{
			$ix++;
			$ix1 = 1;
		}

		if ($arItem['ORIGINAL_TYPE'] == CMeetingInstance::TYPE_TASK)
			$className .= ' meeting-new-checkbox-task';
		if ($arItem['INSTANCE_TYPE'] == CMeetingInstance::TYPE_AGENDA)
			$className .= ' meeting-new-checkbox-agenda';

?>
		<div class="<?=$className?>">
			<?if ($arResult['POPUP']):?><span class="meeting-sub-bl-checkbox-wrap"><input type="checkbox" class="meeting-sub-bl-checkbox" id="meeting-new-checkbox-<?=$key?>" name="PARENT_ITEMS[]" value="<?=$arItem['ITEM_ID']?>" /></span><?else:?><input type="checkbox" id="meeting-new-checkbox-<?=$key?>" name="PARENT_ITEMS[]" value="<?=$arItem['ITEM_ID']?>" /><?endif;?><label for="meeting-new-checkbox-<?=$key?>"><?=$index?> <?=$arItem['TITLE']?></label>
		</div>
<?
	endforeach;
?>
</div>
<a class="webform-small-button webform-small-button-accept" href="javascript:void(0)" onclick="addItems()">
	<span class="webform-small-button-left"></span><span class="webform-small-button-text"><?=GetMessage('ME_MOVE_QUESTIONS_FROM_AGENDA_EX_TO_THE_CURRENT_AGENDA')?></span><span class="webform-small-button-right"></span>
</a>
<script type="text/javascript">
function meeting_checkbox_checkall(el, bCheckClass)
{
	var i=0, ar = [BX('meeting-new-select-all-<?=$control_id?>'), BX('meeting-new-select-opened-<?=$control_id?>')];

	for (i = 0; i < ar.length; i++)
	{
		if (ar[i] != el && ar[i].checked)
<?if ($arResult['POPUP']):?>
			meeting_checkbox_click.apply(ar[i].parentNode);
<?else:?>
			ar[i].checked = false;
<?endif;?>
	}

<?if ($arResult['POPUP']):?>
	meeting_checkbox_click.apply(el.parentNode);
<?endif;?>

	var items = BX.findChildren(BX('meeting_parent_items_<?=$control_id?>'), {tag: 'INPUT', property: {name: 'PARENT_ITEMS[]'}}, true), c = el.checked;
	if (items && items.length > 0)
	{
		for (i=0; i<items.length; i++)
		{
			var old_checked = items[i].checked, new_checked = null;
			if (!c)
				new_checked = false;
			else
				new_checked = !bCheckClass || !BX.hasClass(items[i].parentNode<?if($arResult['POPUP']):?>.parentNode<?endif;?>, 'meeting-new-checkbox-agenda');

<?
if ($arResult['POPUP']):
?>
			if (old_checked != new_checked)
				meeting_checkbox_click.apply(items[i].parentNode);
<?
else:
?>
			items[i].checked = new_checked;
<?
endif;
?>
		}
	}
}
<?
if ($arResult['POPUP']):
?>
function meeting_checkbox_click(e)
{
	var checkbox = (this.tagName.toUpperCase() == 'LABEL' ? this.previousSibling : this).firstChild;
	if (checkbox)
	{
		checkbox.checked = !checkbox.checked;
		if (checkbox.checked)
			BX.addClass(this.parentNode, 'meeting-checkbox-checked');
		else
			BX.removeClass(this.parentNode, 'meeting-checkbox-checked');
	}

	if (e || window.event)
		return BX.PreventDefault(e);
}

function meeting_checkbox_check_node(el)
{
	return el.tagName.toUpperCase() == 'LABEL' || BX.hasClass(el, 'meeting-sub-bl-checkbox-wrap');
}

BX.ready(function() {
	BX.bindDelegate(BX('meeting_parent_items_<?=$control_id?>'), 'click', meeting_checkbox_check_node, meeting_checkbox_click);
});
;
<?
endif;
?>
</script>
<?
endif;
?>