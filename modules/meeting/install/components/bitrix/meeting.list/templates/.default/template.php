<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if ($USER->IsAuthorized()):
	$this->SetViewTarget('pagetitle', 100);
?>
		<a href="<?=$arParams['MEETING_ADD_URL']?>" class="webform-small-button webform-small-button-blue webform-small-button-add">
			<span class="webform-small-button-icon"></span>
			<span class="webform-small-button-text"><?=GetMessage('ME_ADD')?></span>
		</a>
<?
	$this->EndViewTarget();
endif;

$this->SetViewTarget('sidebar');

$APPLICATION->IncludeComponent(
	"bitrix:intranet.user.selector.new", ".default", array(
		"MULTIPLE" => "N",
		"NAME" => "OWNER",
		"VALUE" => $arResult['FILTER']['OWNER_ID'],
		"POPUP" => "Y",
		"ON_CHANGE" => "BXOnFilterOwnerSelect",
		"SITE_ID" => SITE_ID,
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
		"SHOW_LOGIN" => 'Y',
		"SHOW_EXTRANET_USERS" => "NONE",
	), null, array("HIDE_ICONS" => "Y")
);
$APPLICATION->IncludeComponent(
	"bitrix:intranet.user.selector.new", ".default", array(
		"MULTIPLE" => "N",
		"NAME" => "MEMBER",
		"VALUE" => $arResult['FILTER']['MEMBER_ID'],
		"POPUP" => "Y",
		"ON_CHANGE" => "BXOnFilterMemberSelect",
		"SITE_ID" => SITE_ID,
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
		"SHOW_LOGIN" => 'Y',
		"SHOW_EXTRANET_USERS" => "NONE",
	), null, array("HIDE_ICONS" => "Y")
);
?>
<div class="sidebar-block">
	<b class="r2"></b>
	<b class="r1"></b>
	<b class="r0"></b>
	<div class="sidebar-block-inner">
		<form name="meeting_filter">
		<div class="filter-block-title"><?=GetMessage('ME_FILTER')?></div>
		<div class="menu-filter-block">
			<div class="filter-field filter-field-name">
				<label class="filter-field-title" for="meeting-field-name"><?=GetMessage('ME_FILTER_TITLE')?></label>
				<input type="text" name="FILTER[TITLE]" value="<?=htmlspecialcharsbx($arResult['FILTER']['TITLE'])?>" class="filter-textbox" id="meeting-field-name">
			</div>
			<div class="filter-field filter-field-stage">
				<label class="filter-field-title" for="meeting-field-stage"><?=GetMessage('ME_FILTER_CURRENT_STATE')?></label>
				<select name="FILTER[CURRENT_STATE]" class="filter-field-select" id="meeting-field-stage">
					<option value="0"><?=GetMessage('ME_FILTER_CURRENT_STATE_ALL')?></option>
<?
$ar = array(CMeeting::STATE_PREPARE, CMeeting::STATE_ACTION, CMeeting::STATE_CLOSED);
foreach ($ar as $p):
?>
					<option value="<?=$p?>"<?=$arResult['FILTER']['CURRENT_STATE'] == $p ? ' selected="selected"' : ''?>><?=GetMessage('ME_STATE_'.$p)?></option>
<?
endforeach;
?>
				</select>
			</div>
<script type="text/javascript">
window.meeting_last_fld = 'owner';
function BXFilterSelectOwner(el, fld)
{
	window.meeting_last_fld = fld;
	if (!window['BXFilterOwnerSelector_' + fld])
	{
		window['BXFilterOwnerSelector_' + fld] = BX.PopupWindowManager.create("filter-"+fld+"-popup", el, {
			offsetTop : 1,
			autoHide : true,
			content : BX(fld.toUpperCase() + "_selector_content")
		});
	}

	if (window['BXFilterOwnerSelector_' + fld].popupContainer.style.display != "block")
	{
		window['BXFilterOwnerSelector_' + fld].show();
	}
}

function BXOnFilterMemberSelect(users)
{
	window.meeting_last_fld = 'member';
	BXOnFilterOwnerSelect(users);
	window.meeting_last_fld = 'owner';
}

function BXOnFilterOwnerSelect(users)
{
	if (users)
	{
		var u = BX.util.array_values(users);
		if (u.length > 0)
		{
			BX('meeting_'+window.meeting_last_fld+'_name').innerHTML = '<span class="meeting-new-members-name"><input type="hidden" value="'+u[0].id+'" name="FILTER['+window.meeting_last_fld.toUpperCase()+'_ID]" /><a class="meeting-new-members-link" href="'+'<?=CUtil::JSEscape(COption::GetoptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/', SITE_ID))?>'.replace(/#id#|#user_id#/i, u[0].id) + '">'+BX.util.htmlspecialchars(u[0].name)+'</a><span onclick="O_'+window.meeting_last_fld.toUpperCase()+'.unselect(this.parentNode.firstChild.value);" class="meeting-del-icon"></span></span>';

			if (window['BXFilterOwnerSelector_'+window.meeting_last_fld])
				window['BXFilterOwnerSelector_'+window.meeting_last_fld].close();

			return;
		}
	}

	if (!!window.meeting_last_fld)
	{
		BX('meeting_'+window.meeting_last_fld+'_name').innerHTML = '';
	}
}

</script>
			<div class="filter-field filter-field-organizer">
				<label class="filter-field-title" for="meeting-field-organizer"><?=GetMessage('ME_FILTER_OWNER')?></label>
				<span id="meeting_owner_name"></span><a href="javascript:void(0)" class="webform-field-action-link" onclick="BXFilterSelectOwner(this, 'owner')"><?=GetMessage('ME_FILTER_OWNER_CHOOSE')?></a>
			</div>
			<div class="filter-field filter-field-member">
				<label class="filter-field-title" for="meeting-field-member"><?=GetMessage('ME_FILTER_MEMBER')?></label>
				<span id="meeting_member_name"></span><a href="javascript:void(0)" class="webform-field-action-link" onclick="BXFilterSelectOwner(this, 'member')"><?=GetMessage('ME_FILTER_OWNER_CHOOSE')?></a>
			</div>
<?
if (IsModuleInstalled('socialnetwork')):

	$APPLICATION->IncludeComponent('bitrix:socialnetwork.group.selector', '', array(
		'SELECTED' => $arResult['FILTER']['GROUP_ID'],
		'BIND_ELEMENT' => 'meeting_group_name',
		'ON_SELECT' => 'BXOnGroupChange'
	), null, array('HIDE_ICONS' => 'Y'));

?>
<script type="text/javascript">
function BXOnGroupChange(group)
{
	if (!!group && group.length > 0)
	{
		group = group[0];
		BX('meeting_group_name', true).innerHTML = '<span class="meeting-new-members-name"><a href="javascript:void(0)" class="meeting-new-members-link">' + BX.util.htmlspecialchars(group.title) + '<input type="hidden" name="FILTER[GROUP_ID]" value="'+group.id+'" /></a><span class="meeting-del-icon" onclick="BXOnGroupChange()"></span></span>';
	}
	else
	{
		BX('meeting_group_name', true).innerHTML = '';
	}
}
</script>
			<div class="filter-field filter-field-group">
				<label class="filter-field-title"><?=GetMessage('ME_FILTER_GROUP')?></label>
				<span id="meeting_group_name"></span><a href="javascript:void(0)" class="webform-field-action-link" onclick="groupsPopup.show()"><?=GetMessage('ME_FILTER_OWNER_CHOOSE')?></a>
			</div>
<?
endif;
if ($arResult['IS_HEAD']):
?>
			<div class="filter-field filter-field-meeting-checkbox">
				<input type="checkbox" class="meetings-filter-checkbox" name="FILTER[MY]" id="meeting-checkbox-sub" value="Y"<?=$arResult['FILTER']['MY']?' checked="checked"':''?> />
				<label class="filter-field-title" for="meeting-checkbox-sub"><?=GetMessage('ME_FILTER_HEAD')?></label>
			</div>
<?
endif;
?>
			<div class="filter-field-buttons">
				<input type="submit" value="<?=GetMessage('ME_FILTER_SUBMIT')?>" class="filter-submit" />&nbsp;&nbsp;<input type="button" name="del_filter_company_search" value="<?=GetMessage('ME_FILTER_CANCEL')?>" class="filter-submit" onclick="window.location.href='<?=$arParams['LIST_URL']?>'; return false;" />
			</div>
		</div>
	</div>
	<i class="r0"></i>
	<i class="r1"></i>
	<i class="r2"></i>
</div>
<?
$this->EndViewTarget();
?>
<script type="text/javascript">
function BXDeleteMeeting(id)
{
	BX.PopupMenu.currentItem.popupWindow.close();
	if (confirm('<?=CUtil::JSEScape(GetMessage('ME_DELETE_CONFIRM'))?>'))
	{
		var row = BX('meeting_row_' + id);
		BX.showWait(row)
		BX.ajax.get('<?=CUtil::JSEscape($arParams['MEETING_URL'])?>?DELETE=Y&<?=bitrix_sessid_get()?>'.replace('#MEETING_ID#', id), function()
		{
			BX.closeWait(row);
			row.parentNode.removeChild(row);
		});
	}
}
</script>
<div class="meeting-list">
	<div class="meeting-list-left-corner"></div>
	<div class="meeting-list-right-corner"></div>
	<table cellspacing="0" class="meeting-list-table">
		<colgroup>
			<col class="meeting-item-column">
			<col class="meeting-menu-column">
			<col class="meeting-date-column">
			<col class="meeting-stage-column">
			<col class="meeting-organizer-column">
			<col class="meeting-place-column">
		</colgroup>
		<tbody>
		<tr>
			<th colspan="2" class="meeting-item-column">
				<div class="meeting-head-cell"><span class="meeting-head-cell-title"><?=GetMessage('ME_TITLE');?></span></div>
			</th>
			<th class="meeting-date-column">
				<div class="meeting-head-cell"><span class="meeting-head-cell-title"><?=GetMessage('ME_DATE_START');?></span></div>
			</th>
			<th class="meeting-stage-column">
				<div class="meeting-head-cell"><span class="meeting-head-cell-title"><?=GetMessage('ME_CURRENT_STATE');?></span></div>
			</th>
			<th class="meeting-organizer-column">
				<div class="meeting-head-cell"><span class="meeting-head-cell-title"><?=GetMessage('ME_OWNER');?></span></div>
			</th>
			<th class="meeting-place-column">
				<div class="meeting-head-cell"><span class="meeting-head-cell-title"><?=GetMessage('ME_PLACE');?></span></div>
			</th>
		</tr>
<?
if (count($arResult['MEETINGS']) <= 0):
?>
		<tr>
			<td colspan="6" class="meeting-empty-table">
				<?=GetMessage('ME_ERR_NO_MEETINGS')?>. <a href="<?=$arParams['MEETING_ADD_URL']?>"><?=GetMessage('ME_ADD')?></a>
			</td>
		</tr>
<?
else:
	$arStateCSS = array(CMeeting::STATE_PREPARE => 'meeting-stage-not-started', CMeeting::STATE_ACTION => 'meeting-stage-goes', CMeeting::STATE_CLOSED => 'meeting-stage-completed');
	foreach ($arResult['MEETINGS'] as $arMeeting):
		$current_role = $arMeeting['USERS'][$USER->GetID()];
?>
	<script type="text/javascript">
meetingMenuPopup[<?=$arMeeting["ID"]?>] = [
	{text : "<?=GetMessage("ME_DETAIL")?>", title : "<?=GetMessage("ME_DETAIL_EX")?>", className : "menu-popup-item-view", href : "<?=CUtil::JSEscape($arMeeting['URL'])?>" }
	,{text : "<?=GetMessage("ME_COPY")?>", title : "<?=GetMessage("ME_COPY_EX")?>", className : "menu-popup-item-create", href : "<?=CUtil::JSEscape($arMeeting['URL_COPY'])?>" }
<?
		if ($current_role == CMeeting::ROLE_KEEPER || $current_role == CMeeting::ROLE_OWNER):
?>
	,{text : "<?=GetMessage("ME_EDIT")?>", title : "<?=GetMessage("ME_DETAIL_EX")?>", className : "menu-popup-item-edit", href : "<?=CUtil::JSEscape($arMeeting['URL_EDIT'])?>" }
<?
		endif;
		if ($current_role == CMeeting::ROLE_OWNER):
?>
	,{text : "<?=GetMessage("ME_DELETE")?>", title : "<?=GetMessage("ME_DELETE_EX")?>", className : "menu-popup-item-delete", onclick : function(){BXDeleteMeeting(<?=$arMeeting['ID']?>)}},
<?
		endif;
?>
]
	</script>
	<tr class="meeting-list-item <?=$arStateCSS[$arMeeting['CURRENT_STATE']]?>" id="meeting_row_<?=$arMeeting['ID']?>">
		<td class="meeting-item-column"><div class="meeting-column-wrap"><a href="<?=$arMeeting['URL']?>" class="meeting-title-link" title="<?=$arMeeting['TITLE']?>"><?=$arMeeting['TITLE']?></a></div></td>
		<td class="meeting-menu-column"><a href="javascript:void(0)" class="meeting-menu-button"  onclick="return ShowMenuPopup(<?php echo $arMeeting["ID"]?>, this);"><i class="meeting-menu-button-icon"></i></a></td>
		<td class="meeting-date-column"><span class="meeting-date-start"><?=$arMeeting['DATE_START'] && MakeTimeStamp($arMeeting['DATE_START'])>0 ? FormatDate($DB->DateFormatToPhp(FORMAT_DATE).((IsAmPmMode()) ? ' h:i a' : ' H:i'), MakeTimeStamp($arMeeting['DATE_START'])) : ''?>&nbsp;</span></td>
		<td class="meeting-stage-column"><span class="meeting-stage"><?=GetMessage('ME_STATE_'.$arMeeting['CURRENT_STATE'])?></span></td>
		<td class="meeting-organizer-column"><div class="meeting-column-wrap"><?
			$APPLICATION->IncludeComponent('bitrix:main.user.link', '', array(
				'ID' => $arMeeting['OWNER_ID'],
				'INLINE' => 'Y',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
			), null, array('HIDE_ICONS' => 'Y'));
		?></div></td>
		<td class="meeting-place-column"><div class="meeting-column-wrap"><span class="meeting-place" title="<?=$arMeeting['PLACE']?>"><?=$arMeeting['PLACE']?>&nbsp;</span></div></td>
	</tr>
<?
	endforeach;
endif;
?>
		</tbody>
	</table>
</div>
<div class="meeting-list-nav">
<?
	echo $arResult['NAV_STRING'];
?>
</div>
