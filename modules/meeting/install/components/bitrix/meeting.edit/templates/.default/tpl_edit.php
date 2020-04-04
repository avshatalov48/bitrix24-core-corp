<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$bHasTitle = true;
if (strlen($arResult['MEETING']['TITLE']) <= 0)
{
	$arResult['MEETING']['TITLE'] = GetMessage('ME_TITLE_DEFAULT');
	$bHasTitle = false;
}

$tdef = htmlspecialcharsbx(CUtil::JSEscape(GetMessage('ME_TITLE_DEFAULT')));
if ($arResult['MEETING']['DATE_START'] && MakeTimeStamp($arResult['MEETING']['DATE_START'])>0)
{
	$date = MakeTimeStamp($arResult['MEETING']['DATE_START']);
	$date_date = FormatDateFromDB(ConvertTimeStamp($date, 'SHORT'), 'SHORT');
	$date_time = FormatDate((IsAmPmMode() ? 'h:i a' : 'H:i'), $date);
}
else
{
	$date = $date_date = $date_time = '';
}

$duration = intval($arResult['MEETING']['DURATION']);
$duration_coef = 60;
if ($duration % 3600 == 0)
	$duration_coef = 3600;
$duration = intval($duration/$duration_coef);

$keeper = 0;
foreach ($arResult['MEETING']['USERS'] as $USER_ID => $USER_ROLE)
{
	if ($USER_ROLE == CMeeting::ROLE_KEEPER)
	{
		$keeper = $USER_ID;
	}
}

$this->SetViewTarget('pagetitle', 100);
?>
	<a href="<?=$arParams['LIST_URL']?>" class="webform-small-button webform-small-button-blue webform-small-button-back">
		<span class="webform-small-button-icon"></span>
		<span class="webform-small-button-text"><?=GetMessage('ME_LIST_TITLE')?></span>
	</a>
<?
if ($arResult['MEETING']['ID']):
?>
	<a href="<?=$arParams['MEETING_URL']?>" class="webform-small-button webform-small-button-blue webform-small-button-back">
		<span class="webform-small-button-icon"></span>
		<span class="webform-small-button-text"><?=GetMessage('ME_VIEW_TITLE')?></span>
	</a>
<?
endif;
?>
<?
$this->EndViewTarget();

$arValue = $arResult['MEETING']['USERS'] ? array_keys($arResult['MEETING']['USERS']) : array($USER->GetID());

$APPLICATION->IncludeComponent(
	"bitrix:intranet.user.selector.new", ".default", array(
		"MULTIPLE" => "Y",
		"NAME" => "USERS",
		"VALUE" => $arValue,
		"POPUP" => "Y",
		"ON_CHANGE" => "BXOnMembersListChange",
		"SITE_ID" => SITE_ID,
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
		"SHOW_LOGIN" => 'Y',
		"SHOW_EXTRANET_USERS" => "NONE",
	), null, array("HIDE_ICONS" => "Y")
);
?>
<script type="text/javascript" bxrunfirst="true">
window.bx_user_url_tpl = '<?=CUtil::JSEscape(COption::GetOptionString('intranet', 'path_user', '', SITE_ID))?>';
window.arMembersList = [];

function BXChangeCoef(coef)
{
	var cur = BX('me_coef_' + coef), other = BX('me_coef_' + (coef == 60 ? 3600 : 60));
	BX.addClass(cur, 'meeting-new-dur-active');
	BX.removeClass(other, 'meeting-new-dur-active');
	other.parentNode.insertBefore(cur, other);
	document.forms.meeting_edit.DURATION_COEF.value = coef;
}

function UpdateMembersList(arUsers)
{
	BX.cleanNode(BX('meeting_members'));
	var h = '';

	for (var i = 0; i < arUsers.length; i++)
	{
		h += '<span class="meeting-new-members-name"><input type="hidden" name="USERS[]" value="'+arUsers[i].id+'" /><a href="'+getUserUrl(arUsers[i].id)+'" class="meeting-new-members-link">'+BX.util.htmlspecialchars(arUsers[i].name)+'</a>' + (arUsers[i].id == BX.message('USER_ID') ? '' : '<span class="meeting-del-icon" onclick="O_USERS.unselect(this.parentNode.firstChild.value);"></span>') + '</span>';
	}
	BX('meeting_members').innerHTML = h;
}

window.meeting_owner = <?=$arResult['MEETING']['OWNER_ID'] > 0 ? $arResult['MEETING']['OWNER_ID'] : $USER->GetID()?>;
window.meeting_owner_data = null;
window.meeting_keeper = <?=$keeper?>;
function UpdateKeepersList(arUsers)
{
	BX.cleanNode(BX('keeper_selector_content'));

	var h = '<div class="menu-popup-items">';
	var bKeeperEx = false;
	for (var i = 0; i < arUsers.length; i++)
	{
		if (arUsers[i].id == window.meeting_keeper)
			bKeeperEx = true;

		if (i > 0)
			h += '<div class="popup-window-hr"><i></i></div>';

		h += '<a href="javascript:void(0)" class="menu-popup-item' + (arUsers[i].id == window.meeting_keeper ? " meeting-menu-popup-item-current" : "") + '" onclick="SetKeeper(window.arMembersList['+arUsers[i].id+'])"><span class="menu-popup-item-left"></span><span class="menu-popup-item-text">'+BX.util.htmlspecialchars(arUsers[i].name)+'</span><span class="menu-popup-item-right"></span></a>';
	}

	h += '</div>'
	BX('keeper_selector_content').innerHTML = h;

	if (!bKeeperEx)
		SetKeeper();
	else
		SetKeeper(window.arMembersList[window.meeting_keeper]);
}

function SetKeeper(u)
{
	window.meeting_keeper = !!u ? u.id : 0;
	if (window.meeting_keeper > 0)
	{
		BX('meeting_keepers').innerHTML = '<span class="meeting-new-members-name"><input type="hidden" name="KEEPERS[]" value="'+u.id+'" /><a href="'+(getUserUrl(u.id))+'" class="meeting-new-members-link">'+BX.util.htmlspecialchars(u.name)+'</a>' + (u.id == '<?=$USER->GetID()?>' ? '' : '<span class="meeting-del-icon" onclick="SetKeeper();"></span>') + '</span>'
	}
	else
	{
		window.meeting_keeper = '<?=$USER->GetID()?>';
		SetKeeper(window.arMembersList[window.meeting_keeper]);
	}
	if (window.BXKeeperSelector)
	{
		window.BXKeeperSelector.close();
	}
}

BX.addCustomEvent('onMembersListChange', UpdateMembersList);
BX.addCustomEvent('onMembersListChange', UpdateKeepersList);
</script>
<div id="keeper_selector_content" class="menu-popup" style="display: none;"></div>
<div class="meetings-content">
	<form action="<?=POST_FORM_ACTION_URI?>" name="meeting_edit" method="POST" enctype="multipart/form-data">
		<?=bitrix_sessid_post()?>
		<input type="hidden" name="MEETING_ID" value="<?=$arResult['MEETING']['ID']?>" />
		<input type="hidden" name="edit" value="Y" />
<?
if ($arParams['COPY']):
?>
		<input type="hidden" name="PARENT_ID" value="<?=$arResult['MEETING']['PARENT_ID']?>" />
		<input type="hidden" name="COPY" value="Y" />
<?
endif;
?>

		<div class="webform-round-corners webform-main-fields">
			<div class="webform-corners-top"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>
			<div class="webform-content meeting-detail-title-label">
				<?=GetMessage('ME_TITLE')?>
				<div class="meeting-new-title-wrap">
					<input type="text" name="TITLE" onblur="if(this.value==''||this.value=='<?=$tdef?>') {this.value='<?=$tdef?>'; BX.removeClass(this,'meeting-new-title-active')}" onfocus="if(this.value=='<?=$tdef?>') this.value=''; BX.addClass(this, 'meeting-new-title-active')" value="<?=$arResult['MEETING']['TITLE']?>" class="meeting-new-title<?=$bHasTitle ? ' meeting-new-title-active' : ''?>" />
				</div>
				<span class="meeting-new-create-date">
					<div style="display: none;">
<?
$APPLICATION->IncludeComponent('bitrix:main.clock', '', array(
	'INPUT_NAME' => 'DATE_START_TIME',
	'INIT_TIME' => $date_time,
	'INPUT_ID' => 'meeting_time',
	'ZINDEX' => '300',
), null, array('HIDE_ICONS' => 'Y'));
?>
					</div>
					<label><?=GetMessage('ME_DATE')?></label><input type="text" class="meeting-new-create-d-date" name="DATE_START_DATE" value="<?=$date_date?>" onclick="BX.calendar({node: this, field: this, bTime: false});" onchange="checkMeetingRoom()" />
					<label><?=GetMessage('ME_TIME')?></label><span></span><input type="text" class="meeting-new-create-d-time" name="DATE_START_TIME" value="<?=$date_time?>" onclick="if(BX.isReady){bxShowClock_meeting_time();}" onchange="checkMeetingRoom()" />
					<label><?=GetMessage('ME_DURATION')?></label><input type="text" class="meeting-new-create-d-duration" name="DURATION" value="<?=$duration?>" onchange="checkMeetingRoom()" />
					<span class="meeting-new-duration-time"><span onclick="BXChangeCoef(60);  checkMeetingRoom();" class="meeting-dash-link meeting-new-dur-active" id="me_coef_60"><?=GetMessage('ME_DURATION_60')?></span><span onclick="BXChangeCoef(3600); checkMeetingRoom();" class="meeting-dash-link" id="me_coef_3600"><?=GetMessage('ME_DURATION_3600')?></span></span><input type="hidden" name="DURATION_COEF" value="<?=$duration_coef?>" />
<script type="text/javascript">
BX.ready(function() {
	BX.cleanNode(BX('meeting_time'), true); BX.cleanNode(BX('meeting_time_icon'), true);
	var input = document.forms.meeting_edit.DATE_START_TIME, icon = input.previousSibling, pos = BX.pos(input, true);

	input.id = 'meeting_time';
	icon.id = 'meeting_time_icon';
	icon.style.position = 'absolute';
	icon.style.top = pos.top + 'px';
	icon.style.left = pos.left + 'px';

<?
if ($duration_coef == 3600):
?>
	BXChangeCoef(3600);
<?
endif;
?>
});
</script>
					<span class="meeting-new-cr-date-lt"></span>
					<span class="meeting-new-cr-date-rt"></span>
					<span class="meeting-new-cr-date-lb"></span>
					<span class="meeting-new-cr-date-rb"></span>
				</span>
				<div class="meeting-new-members-block">
					<span class="meeting-new-members">
						<a href="javascript:void(0)" class="meeting-new-members-left meeting-dash-link" onclick="BXSelectMembers(this)"><?=GetMessage('ME_MEMBERS')?>:</a>
						<span class="meeting-new-members-right" id="meeting_members">

						</span>
					</span>
<?/*<span class="meeting-new-members"><span class="meeting-new-members-left meeting-dash-link"><?=GetMessage('ME_PLANNER')?></span></span>*/?>
					<span class="meeting-new-members">
						<a href="javascript:void(0)" class="meeting-new-members-left meeting-dash-link" onclick="BXSelectKeepers(this)"><?=GetMessage('ME_KEEPER')?>:</a>
						<span class="meeting-new-members-right" id="meeting_keepers"></span>
					</span>
				</div>
				<div class="meeting-new-meeting-plase">
					<span class="meeting-new-meeting-plase-text"><?=GetMessage('ME_PLACE')?></span>
					<input type="text" name="PLACE" value="<?=$arResult['MEETING']['PLACE']?>" onchange="onMeetingRoomChange()" />
					<input type="hidden" name="PLACE_ID" value="<?=htmlspecialcharsbx($arResult['MEETING']['PLACE_ID'])?>">
					<span id="meeting_room_flag" class="meeting-room-flag meeting-rm-free" style="display:<?=$arResult['MEETING']['PLACE_ID']==''?'none':'inline-block'?>"><span class="meeting-rm-icon"></span><span class="meeting-room-flag-text"><?=GetMessage('ME_MR_FREE')?></span></span>
<?
if (is_array($arResult['MEETING_ROOMS_LIST']) && count($arResult['MEETING_ROOMS_LIST']) > 0):
?>
<script type="text/javascript">
var meetingRooms = <?=CUtil::PhpToJsObject($arResult['MEETING_ROOMS_LIST'], false, true);?>,
	meetingRoomCheckTimeout = null;

function onMeetingRoomChange(roomId, roomName)
{
	document.forms.meeting_edit.PLACE_ID.value = roomId||'';

	if(typeof roomName != 'undefined')
		document.forms.meeting_edit.PLACE.value = roomName;

	if(!!meetingRoomCheckTimeout)
		clearTimeout(meetingRoomCheckTimeout);

	meetingRoomCheckTimeout = setTimeout(checkMeetingRoom, 300);
}

function onMeetingRoomFormSubmit(e)
{
	alert('<?=GetMessageJS('ME_MR_RESERVED_WARNING')?>');
	return BX.PreventDefault(e);
}

function checkMeetingRoom()
{
	if(!!document.forms.meeting_edit.PLACE_ID.value)
	{
		var queryData = {
			PLACE_ID: document.forms.meeting_edit.PLACE_ID.value,
			EVENT_ID: <?=intval($arResult['MEETING']['EVENT_ID'])?>,
			DATE_START_DATE: document.forms.meeting_edit.DATE_START_DATE.value,
			DATE_START_TIME: document.forms.meeting_edit.DATE_START_TIME.value,
			DURATION: parseInt(document.forms.meeting_edit.DURATION.value) * parseInt(document.forms.meeting_edit.DURATION_COEF.value)
		};

		BX.ajax.loadJSON('/bitrix/tools/ajax_meeting.php', queryData, function(res){
			if(res && res.result && res.result != 'error')
			{
				BX('meeting_room_flag').className = 'meeting-room-flag meeting-rm-'+(res.result == 'ok' ? 'free':'reserved');
				BX('meeting_room_flag', true).innerHTML = '<span class="meeting-rm-icon"></span><span class="meeting-room-flag-text">' + (res.result=='ok'?'<?=GetMessageJS('ME_MR_FREE')?>':'<?=GetMessageJS('ME_MR_RESERVED')?>') + '</span>'

				BX('meeting_room_flag', true).style.display = 'inline-block';

				if(res.result == 'ok')
				{
					BX.addClass(BX('meeting_save_button', true), 'webform-button-create');
					BX.unbind(document.forms.meeting_edit, 'submit', onMeetingRoomFormSubmit);
				}
				else
				{
					BX.removeClass(BX('meeting_save_button', true), 'webform-button-create');
					BX.bind(document.forms.meeting_edit, 'submit', onMeetingRoomFormSubmit);
				}
			}
			else
			{
				BX('meeting_room_flag', true).style.display = 'none';
				BX.addClass(BX('meeting_save_button', true), 'webform-button-create');
				BX.unbind(document.forms.meeting_edit, 'submit', onMeetingRoomFormSubmit);
			}
		});
	}
	else
	{
		BX('meeting_room_flag', true).style.display = 'none';
		BX.addClass(BX('meeting_save_button', true), 'webform-button-create');
		BX.unbind(document.forms.meeting_edit, 'submit', onMeetingRoomFormSubmit);
	}
}

BX.ready(function(){
	new BXInputPopup({
		values: meetingRooms,
		handler: function(roomData) {
			if(!!meetingRooms[roomData['ind']])
			onMeetingRoomChange(meetingRooms[roomData['ind']]['MEETING_ROOM_ID'], roomData['value']);
		},
		input: document.forms.meeting_edit.PLACE,
		className: 'meeting-rooms-popup'
	});
	checkMeetingRoom();
});
</script>
<?
else:
?>
<script type="text/javascript">window.onMeetingRoomChange=window.checkMeetingRoom=BX.DoNothing;</script>
<?
endif;
?>
				</div>
<?
if ($arResult['IS_NEW_CALENDAR']):
?>
				<div class="meeting-event-options-block">
					<div class="meeting-event-option">
						<input type="hidden" name="EVENT_NOTIFY" value="N" /><input type="checkbox" name="EVENT_NOTIFY" id="EVENT_NOTIFY" value="Y"<?=$arResult['MEETING']['EVENT']['MEETING']['NOTIFY'] ? ' checked="checked"' : ''?> /><label for="EVENT_NOTIFY"><?=GetMessage('ME_EVENT_NOTIFY');?></label>
					</div>
<?
	if ($arResult['MEETING']['ID'] > 0):
?>
					<div class="meeting-event-option">
						<input type="hidden" name="EVENT_REINVITE" value="N" /><input type="checkbox" name="EVENT_REINVITE" id="EVENT_REINVITE" value="Y" /><label for="EVENT_REINVITE"><?=GetMessage('ME_EVENT_REINVITE');?></label>
					</div>
<?
	endif;
?>
				</div>
<?
endif;
?>
			</div>
		</div>

		<div class="meeting-new-description">
			<div class="webform-content">
				<div class="webform-field-label"><?=GetMessage('ME_DESCRIPTION')?></div>
				<div id="meeting-new-add-description-form" class="meeting-new-add-description-form">
<?
$APPLICATION->IncludeComponent('bitrix:fileman.light_editor', '', array(
	'CONTENT' => $arResult['MEETING']['~DESCRIPTION'],
	'INPUT_NAME' => 'DESCRIPTION',
	'RESIZABLE' => 'Y',
	'AUTO_RESIZE' => 'Y',
	'HEIGHT' => '100px',
	'WIDTH' => '100%',
));

$arFilesExt = array();
if (count($arResult['MEETING']['FILES']) > 0)
{
	foreach ($arResult['MEETING']['FILES'] as $arFile)
	{
		if ($arFile['FILE_SRC'])
			$arFilesExt[$arFile['FILE_ID']] = $arFile['FILE_SRC'];
	}
}

$APPLICATION->IncludeComponent('bitrix:main.file.input', '', array(
	'INPUT_NAME' => 'FILES',
	'INPUT_NAME_UNSAVED' => 'FILES_TMP',
	'INPUT_VALUE' => array_keys($arResult['MEETING']['FILES']),
	'CONTROL_ID' => 'MEETING_DESCRIPTION',
	'MODULE_ID' => 'meeting'
));

if (IsModuleInstalled('socialnetwork')):

	$APPLICATION->IncludeComponent('bitrix:socialnetwork.group.selector', '', array(
		'SELECTED' => $arResult['MEETING']['GROUP_ID'],
		'BIND_ELEMENT' => 'ingroup_link',
		'ON_SELECT' => 'BXOnGroupChange'
	), null, array('HIDE_ICONS' => 'Y'))
?>
<script type="text/javascript">
function BXOnGroupChange(group)
{
	if (!!group && group.length > 0)
	{
		group = group[0];
		BX('ingroup_link', true).innerHTML = '<?=CUtil::JSEscape(GetMessage('ME_GROUP'))?>: ' + BX.util.htmlspecialchars(group.title)
		BX('ingroup_link', true).nextSibling.style.visibility = 'visible';
		document.forms.meeting_edit.GROUP_ID.value = group.id;
	}
	else
	{
		BX('ingroup_link', true).innerHTML = '<?=CUtil::JSEscape(GetMessage('ME_GROUP'))?>';
		BX('ingroup_link', true).nextSibling.style.visibility = 'hidden';
		document.forms.meeting_edit.GROUP_ID.value = '';
	}
}
</script>
					<span class="meeting-new-ingroup-wrap">
						<span class="meeting-dash-link meeting-new-ingroup" id="ingroup_link" onclick="groupsPopup.show()"><?=GetMessage('ME_GROUP')?></span><span class="meeting-del-icon" onclick="BXOnGroupChange()" style="visibility: <?=$arResult['MEETING']['GROUP_ID'] > 0 ? 'visible' : 'hidden'?>"></span><input type="hidden" name="GROUP_ID" value="<?=$arResult['MEETING']['GROUP_ID']?>" />
					</span>
<?
endif;
?>
				</div>
			</div>
		</div>
		<div class="meeting-new-agenda-wrap">
			<span class="meeting-new-agenda-title"><?=GetMessage('ME_AGENDA')?></span>
<?
if ($arResult['MEETING']['PARENT_ID']):
?>
			<div class="meeting-new-agenda-block">
				<div class="meeting-new-ag-bl-title" onclick="BX.toggle(BX('parent_agenda'))"><?=GetMessage('ME_AGENDA_EX')?></div>
				<div class="meeting-new-ag-bl-cont" id="parent_agenda"<?=$arParams['COPY'] ? '' : ' style="display:none;"'?>></div>
			</div>
<script type="text/javascript">
BX.ready(function() {
	BX.ajax.insertToNode(
		'<?=CUtil::JSEscape(str_replace('#MEETING_ID#', $arResult['MEETING']['PARENT_ID'], $arParams["MEETING_EDIT_URL_TPL"])).'?AGENDA_EX=Y&'.bitrix_sessid_get()?>',
		BX('parent_agenda')
	);
});
</script>
<?
endif;
?>
		</div>
<script type="text/javascript">
window.saveData = BX.DoNothing;
window.BXMEETINGCANEDIT = true;
</script>
<?
require($_SERVER['DOCUMENT_ROOT'].$this->GetFolder().'/agenda.php');
?>

		<div class="meeting-new-select-but-wrap">
			<a href="javascript:void(0)" onclick="BX.submit(document.forms.meeting_edit)" class="webform-button webform-button-create" id="meeting_save_button">
				<span class="webform-button-left"></span><span class="webform-button-text"><?=$arResult['MEETING']['ID'] > 0 ? GetMessage('ME_SAVE'): GetMessage('ME_CREATE')?></span><span class="webform-button-right"></span>
			</a><a href="<?=$arResult['MEETING']['ID'] > 0 ? $arParams['MEETING_URL'] : $arParams['LIST_URL']?>" class="webform-button-link webform-button-link-cancel"><?=GetMessage('ME_CANCEL')?></a>
		</div>
	</form>
<?
require($_SERVER['DOCUMENT_ROOT'].$this->GetFolder().'/comments.php');
?>
</div>
