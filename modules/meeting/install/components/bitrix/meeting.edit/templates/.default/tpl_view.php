<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$arUsers = array('O' => array(), 'K' => array(), 'M' => array(), 'R' => array());

foreach ($arResult['MEETING']['USERS'] as $USER_ID => $USER_ROLE):
	if($arResult['MEETING']['USERS_EVENT'][$USER_ID] == 'N')
		$USER_ROLE = 'R';

	$arUsers[$USER_ROLE][] = $USER_ID;
endforeach;

$this->SetViewTarget('pagetitle', 100);
?>
<a href="<?=$arParams['LIST_URL']?>" class="webform-small-button webform-small-button-blue webform-small-button-back">
	<span class="webform-small-button-icon"></span>
	<span class="webform-small-button-text"><?=GetMessage('ME_LIST_TITLE')?></span>
</a>
<?
$this->EndViewTarget();
?>

<script type="text/javascript" bxrunfirst="true">
window.bx_user_url_tpl = '<?=CUtil::JSEscape(COption::GetOptionString('intranet', 'path_user', '', SITE_ID))?>';
window.arMembersList = [];
window.meeting_owner = <?=intval($arUsers['O'][0])?>;
window.meeting_owner_data = null;
window.meeting_keeper = <?=intval($arUsers['K'][0])?>;
window.arRefuseList = <?=CUtil::PhpToJsObject($arUsers['R'])?>;

function SetKeeper(u)
{
	window.meeting_keeper = !!u ? u.id : 0;

	UpdateAllUsersList(BX.util.array_values(window.arMembersList));

	if (window.BXKeeperSelector)
	{
		window.BXKeeperSelector.close();
	}
}

function UpdateKeepersList(arUsers)
{
	BX.cleanNode(BX('keeper_selector_content'));
	var h = '<div class="menu-popup-items">';

	if (!window.meeting_keeper)
		window.meeting_keeper = window.meeting_owner;

	var s = '';
	for (var i = 0; i < arUsers.length; i++)
	{
		if (BX.util.in_array(arUsers[i].id, window.arRefuseList))
			continue;

		if (s != '')
			s += '<div class="popup-window-hr"><i></i></div>';

		s += '<a href="javascript:void(0)" class="menu-popup-item' + (arUsers[i].id == window.meeting_keeper ? " meeting-menu-popup-item-current" : "") + '" onclick="SetKeeper(window.arMembersList['+arUsers[i].id+'])"><span class="menu-popup-item-left"></span></span><span class="menu-popup-item-text">'+BX.util.htmlspecialchars(arUsers[i].name)+'</span><span class="menu-popup-item-right"></span></a>';
	}

	h += s + '</div>';

	BX('keeper_selector_content').innerHTML = h;
}

function UpdateAllUsersList(arUsers)
{
	var list = BX('meeting_all_users');
	var h = {M:'',O:'',K:'',R:''};

	var inputs = BX('meeting_users_input');
	BX.cleanNode(inputs);

	if (!window.meeting_keeper)
		window.meeting_keeper = window.meeting_owner;

	for (var i = 0; i < arUsers.length; i++)
	{
		var s = arUsers[i].id == window.meeting_owner ? 'O' :
			(
				arUsers[i].id == window.meeting_keeper ? 'K' :
				(
					BX.util.in_array(arUsers[i].id, window.arRefuseList) ? 'R' : 'M'
				)
			);

		inputs.appendChild(BX.create('INPUT', {props: {type:'hidden',name:'USERS[]',value:arUsers[i].id}}));
		if (s == 'K')
			inputs.appendChild(BX.create('INPUT', {props: {type:'hidden',name:'KEEPERS[]',value:arUsers[i].id}}));

		var url = getUserUrl(arUsers[i].id),
			str = '<div class="meeting-detail-info-user"><a href="'+url+'" class="meeting-detail-info-user-avatar"'+(arUsers[i].photo ? ' style="background:url(\''+arUsers[i].photo+'\') no-repeat center center; background-size: cover;"' : '')+'></a><div class="meeting-detail-info-user-info"><div class="meeting-detail-info-user-name"><a href="'+url+'">'+BX.util.htmlspecialchars(arUsers[i].name)+'</a></div><div class="meeting-detail-info-user-position">'+BX.util.htmlspecialchars(arUsers[i].position)+'</div></div></div>';

		h[s] += str;

		if (s == 'O' && window.meeting_keeper == window.meeting_owner)
			h['K'] += str;
	}

	for (var s in h)
	{
		var c = BX('meeting_users_' + s);
		if (c)
			c.innerHTML = h[s];
	}

	saveData();
}

BX.addCustomEvent('onMembersListChange', UpdateAllUsersList);
BX.addCustomEvent('onMembersListChange', UpdateKeepersList);
</script>
<div class="meetings-content">
<div class="webform-round-corners webform-main-fields">
	<div class="webform-corners-top">
		<div class="webform-left-corner"></div>
		<div class="webform-right-corner"></div>
	</div>
	<div class="webform-content meeting-detail-title-label"><?=GetMessage('ME_DESCR_TITLE')?>
<?
if ($arResult['CAN_EDIT']):
?>
		<a href="<?=$arParams['MEETING_EDIT_URL']?>" class="meeting-edit-description"><?=GetMessage('ME_EDIT_TITLE')?></a>
<?
endif;
?>
	</div>
</div>
<div class="webform-round-corners webform-main-block webform-main-block-topless webform-main-block-bottomless">
	<div class="webform-content">
		<div class="meeting-detail-title"><?=$arResult['MEETING']['TITLE']?></div>
		<div class="meeting-detail-description"><?=$arResult['MEETING']['~DESCRIPTION']?></div>
		<div class="meeting-detail-files">
<?
if (count($arResult['MEETING']['FILES']) > 0):
?>
			<label class="meeting-detail-files-title"><?=GetMessage('ME_FILES')?>:</label>
			<div class="meeting-detail-files-list">
<?
	foreach ($arResult['MEETING']['FILES'] as $ix => $arFile):
?>
				<div class="meeting-detail-file"><span class="meeting-detail-file-number"><?=$ix+1?>.</span><span class="meeting-detail-file-info"><?if($arFile['FILE_SRC']):?><a href="#message<?=$arFile['FILE_SRC']?>" class="meeting-detail-file-comment"></a><?endif?><a class="meeting-detail-file-link" href="<?=$arFile['DOWNLOAD_URL']?>"><?=$arFile['ORIGINAL_NAME']?></a><span class="meeting-detail-file-size">(<?=$arFile['FILE_SIZE_FORMATTED']?>)</span></span></div>
<?
	endforeach;
?>
			</div>
<?endif;?>
		</div>
	</div>
</div>

<?
$this->SetViewTarget('sidebar', 100);
?>
<div class="meetings-content">
<?
$APPLICATION->IncludeComponent(
	"bitrix:intranet.user.selector.new", ".default", array(
		"MULTIPLE" => "Y",
		"NAME" => "USERS",
		"VALUE" => array_keys($arResult['MEETING']['USERS']),
		"POPUP" => "Y",
		"ON_CHANGE" => "BXOnMembersListChange",
		"SITE_ID" => SITE_ID,
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
		"SHOW_LOGIN" => 'Y',
		"SHOW_EXTRANET_USERS" => "NONE",
	), null, array("HIDE_ICONS" => "Y")
);
?>
<div id="keeper_selector_content" class="menu-popup" style="display: none;"></div>
<div class="sidebar-block">
	<b class="r2"></b>
	<b class="r1"></b>
	<b class="r0"></b>
	<div class="sidebar-block-inner">
		<div class="meeting-detail-info-users">
			<div class="meeting-detail-info-users-border"></div>
			<div class="meeting-detail-info-users-inner">
				<div class="meeting-detail-info-users-title"><span><?=GetMessage('ME_OWNER')?></span><?/*<a class="webform-field-action-link" href=""><?=GetMessage('ME_CHANGE')?></a>*/?></div>
				<div class="meeting-detail-info-users-list" id="meeting_users_O"></div>
			</div>
			<div class="meeting-detail-info-users-border"></div>
		</div>
		<table cellspacing="0" class="meeting-detail-info-layout">
			<tbody>
<?
if (strlen($arResult['MEETING']['PLACE']) > 0):
?>
				<tr>
					<td class="meeting-detail-left-column" valign="top"><?=GetMessage('ME_PLACE')?>:</td>
					<td class="meeting-detail-right-column"><?=$arResult['MEETING']['PLACE']?></td>
				</tr>
<?
endif;
if (strlen($arResult['MEETING']['DATE_START']) > 0 && MakeTimeStamp($arResult['MEETING']['DATE_START'])>0):
?>
				<tr>
					<td class="meeting-detail-left-column"><?=GetMessage('ME_DATE_START')?>:</td>
					<td class="meeting-detail-right-column"><?=FormatDate($DB->DateFormatToPhp(FORMAT_DATE).((IsAmPmMode()) ? ' h:i a' : ' H:i'), MakeTimeStamp($arResult['MEETING']['DATE_START']))?></td>
				</tr>
<?
endif;
?>
				<tr>
					<td class="meeting-detail-left-column"><?=GetMessage('ME_CURRENT_STATE')?>:</td>
					<td class="meeting-detail-right-column" id="meeting_state_text"><?=GetMessage('MEETING_STATE_'.$arResult['MEETING']['CURRENT_STATE'])?></td>
				</tr>
<?
if (strlen($arResult['MEETING']['GROUP_NAME']) > 0):
?>
				<tr>
					<td class="meeting-detail-left-column" valign="top"><?=GetMessage('ME_GROUP')?>:</td>
					<td class="meeting-detail-right-column"><a href="<?=$arResult['MEETING']['GROUP_URL']?>" class="meeting-detail-group-link"><?=$arResult['MEETING']['GROUP_NAME']?></a></td>
				</tr>
<?
endif;
?>			</tbody></table>

		<div class="meeting-detail-info-users" id="">
			<div class="meeting-detail-info-users-border"></div>
			<div class="meeting-detail-info-users-inner">
				<div class="meeting-detail-info-users-title"><span><?=GetMessage('ME_KEEPER')?></span>
<?
if ($arResult['CAN_EDIT']):
?>
				<a class="webform-field-action-link" href="javascript:void(0)" onclick="BXSelectKeepers(this)"><?=GetMessage('ME_CHANGE')?></a>
<?
endif;
?>
				</div><div class="meeting-detail-info-users-list" id="meeting_users_K"></div>
			</div>
			<div class="meeting-detail-info-users-border"></div>
		</div>

		<div class="meeting-detail-info-users meeting-detail-info-member">
			<div class="meeting-detail-info-users-border"></div>
			<div class="meeting-detail-info-users-inner">
				<div class="meeting-detail-info-users-title"><span><?=GetMessage('ME_MEMBERS')?></span>
<?
if ($arResult['CAN_EDIT']):
?>
				<a class="webform-field-action-link" href="javascript:void(0)" onclick="BXSelectMembers(this)"><?=GetMessage('ME_CHANGE')?></a>
<?
endif;
?>
				</div><div class="meeting-detail-info-users-list" id="meeting_users_M"></div>
			</div>
			<div class="meeting-detail-info-users-border"></div>
		</div>
<?
if (count($arUsers['R']) > 0):
?>
		<div class="meeting-detail-info-users meeting-refuse">
			<div class="meeting-detail-info-users-border"></div>
			<div class="meeting-detail-info-users-inner">
				<div onclick="BX.toggle(BX('meeting_users_R')); BX.toggleClass(this,'meeting-refuse-close')" class="meeting-detail-info-users-title meeting-refuse-close">
					<span class="meeting-refuse-title"><?=GetMessage('ME_REFUSED')?> (<?=count($arUsers['R'])?>)</span><span class="meeting-refuse-corner"></span>
				</div>
				<div style="display:none;" id="meeting_users_R"></div>
			</div>
			<div class="meeting-detail-info-users-border"></div>
		</div>
<?
endif;
?>
	</div>
	<i class="r0"></i>
	<i class="r1"></i>
	<i class="r2"></i>
</div>
</div>
<?
$this->EndViewTarget();
?>
<span class="meeting-new-agenda-title"><?=GetMessage('ME_AGENDA')?></span>
<form action="<?=POST_FORM_ACTION_URI?>" name="meeting_edit" method="POST" enctype="multipart/form-data">
<?=bitrix_sessid_post()?>
<input type="hidden" name="MEETING_ID" value="<?=$arParams['MEETING_ID']?>" />
<input type="hidden" name="edit" value="N" />
<?
require($_SERVER['DOCUMENT_ROOT'].$this->GetFolder().'/agenda.php');
?>
<input type="hidden" name="save_type" value="SUBMIT" />
<input type="hidden" name="save" value="Y" />
<span style="display: none" id="meeting_users_input"></span>
</form>
<?
if ($arResult['CAN_EDIT']):
?>
<script type="text/javascript">
window.BXMEETINGCANEDIT = true;
BX.ready(function(){
	BX('meeting_toolbar_layout').appendChild(BX('meeting_toolbar'));
	BX('meeting_toolbar_layout').appendChild(BX.create('DIV',{props:{className:'meeting-toolbar-layout-finish'}}));
});
</script>
<div id="meeting_toolbar" class="meeting-toolbar toolbar-<?=$arResult['MEETING']['CURRENT_STATE']?>">
	<a href="javascript:void(0)" class="meeting-agenda-bot-start" onclick="meetingAction('<?=$arParams['MEETING_ID']?>', {state: '<?=CMeeting::STATE_ACTION?>'})">
		<span class="meeting-agenda-bot-st-l"></span><span class="meeting-agenda-bot-st-text"><?=GetMessage('ME_ACTION')?></span><span class="meeting-agenda-bot-st-r"></span>
	</a>

	<a href="javascript:void(0)" class="webform-small-button webform-small-button-decline meeting-agenda-bot-stop" onclick="meetingAction('<?=$arParams['MEETING_ID']?>', {state: '<?=CMeeting::STATE_CLOSED?>'})">
		<span class="webform-small-button-left"></span><span class="webform-small-button-text"><?=GetMessage('ME_CLOSE')?></span><span class="webform-small-button-right"></span>
	</a>

	<div class="meeting-agenda-bot-return">
		<a href="<?=$arParams['MEETING_COPY_URL']?>" class="meeting-agenda-bot-link meeting-dash-link"><?=GetMessage('ME_COPY')?></a>
		&nbsp;
		<a onclick="meetingAction('<?=$arParams['MEETING_ID']?>', {state: '<?=CMeeting::STATE_PREPARE?>'})" href="javascript:void(0)" class="meeting-agenda-bot-link meeting-dash-link"><?=GetMessage('ME_PREPARE')?></a>
	</div>
</div>
<?
endif;

require($_SERVER['DOCUMENT_ROOT'].$this->GetFolder().'/comments.php');
?>
</div>