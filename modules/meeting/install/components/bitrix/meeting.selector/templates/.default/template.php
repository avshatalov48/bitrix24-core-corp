<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (strlen($arParams['CALLBACK_NAME']) <= 0)
	$arParams['CALLBACK_NAME'] = 'showMeetingSelector';
?>
<div class="meeting-other-popup-cont" id="meeting_selector" style="display: none;">
	<div class="finder-box finder-box-multiple">
		<table cellspacing="0" class="finder-box-layout">
			<tbody><tr>
				<td class="finder-box-left-column">
					<div class="finder-box-search"><input class="finder-box-search-textbox" onkeydown="meetingSearch(this)" id="findex_box_text" /></div>
					<div class="finder-box-tabs">
						<span class="finder-box-tab finder-box-tab-selected" onclick="switchTab('list')" id="meeting_selector_tab_list"><span class="finder-box-tab-left"></span><span class="finder-box-tab-text"><?=GetMessage('ME_MS_TAB_LAST')?></span><span class="finder-box-tab-right"></span></span><span class="finder-box-tab" onclick="switchTab('search'); BX('findex_box_text').focus()" id="meeting_selector_tab_search"><span class="finder-box-tab-left"></span><span class="finder-box-tab-text"><?=GetMessage('ME_MS_TAB_SEARCH')?></span><span class="finder-box-tab-right"></span></span>
					</div>
					<div class="popup-window-hr popup-window-buttons-hr"><i></i></div>
					<div class="finder-box-tabs-content" id="meeting_selector_list">
						<div id="meeting_selector_last" style="display: block;">
<?
foreach ($arResult['MEETINGS'] as $arMeeting):
?>
							<a class="finder-box-item finder-box-item-text" href="<?=htmlspecialcharsbx($arMeeting['URL'])?>"><span class="finder-box-item-date"><?=FormatDate($DB->DateFormatToPhp(FORMAT_DATE).((IsAmPmMode()) ? ' h:i a' : ' H:i'), MakeTimeStamp($arMeeting['DATE_START']))?></span> <?=$arMeeting['TITLE']?></a>
<?
endforeach;
?>
						</div>
						<div id="meeting_selector_search" style="display: none;"></div>
					</div>
				</td>
				<td class="finder-box-right-column">
					<div class="finder-box-selected-items" id="meeting_selector_agenda"></div>
				</td>
			</tr></tbody>
		</table>
	</div>
</div>
<script type="text/javascript">
var meetingSearchTimer, meetingSearchInput;
function meetingSearch(m)
{
	window.meetingSearchInput = m;
	if (meetingSearchTimer)
		clearTimeout(meetingSearchTimer);
	meetingSearchTimer = setTimeout(_meetingSearch, 500);
}

function _meetingSearch()
{
	if (meetingSearchInput.value.length > 0)
	{
		BX.ajax.loadJSON('<?=CUtil::JSEscape($APPLICATION->GetCurPageParam('mode=selector_search'))?>&FILTER[TITLE]=' + BX.util.urlencode(meetingSearchInput.value), __meetingSearch);
	}
}

function __meetingSearch(res)
{
	switchTab('search');
	BX.cleanNode(BX('meeting_selector_search', true));
	if (res.length > 0)
	{
		var s = '';
		for (var i=0; i<res.length; i++)
		{
			s += '<a class="finder-box-item finder-box-item-text" href="' + res[i].URL + '"><span class="finder-box-item-date">' + res[i].DATE_START + '</span> ' + res[i].TITLE + '</a>';
		}
		BX('meeting_selector_search', true).innerHTML = s;
	}
}

function switchTab(id)
{
	if (id == 'search')
	{
		BX.hide(BX('meeting_selector_last', true));
		BX.show(BX('meeting_selector_search', true));
		BX.addClass(BX('meeting_selector_tab_search', true), 'finder-box-tab-selected');
		BX.removeClass(BX('meeting_selector_tab_list', true), 'finder-box-tab-selected');
	}
	else
	{
		BX.hide(BX('meeting_selector_search', true));
		BX.show(BX('meeting_selector_last', true));
		BX.addClass(BX('meeting_selector_tab_list', true), 'finder-box-tab-selected');
		BX.removeClass(BX('meeting_selector_tab_search', true), 'finder-box-tab-selected');
	}
}

function <?=$arParams['CALLBACK_NAME']?>(el)
{
	if (!window.meeting_selector_wnd)
	{
		var q = BX('meeting_selector');
		q.parentNode.removeChild(q);
		q.style.display = 'block';
		window.meeting_selector_wnd = new BX.PopupWindow('meeting_selector', el, {
			autoHide: true,
			lightShadow: true,
			content: q,
			bindOptions: {forceBindPosition:true},
			buttons: [
				new BX.PopupWindowButton({
					text : '<?=CUtil::JSEscape(GetMessage('ME_MS_BTN_ADD'))?>',//BX.message('JS_CORE_TM_B_SAVE'),
					className : "popup-window-button-accept",
					events : {
						click : function() {
							if (window.addItems)
							{
								window.addItems();
								window.meeting_selector_wnd.close();
							}
						}
					}
				}),
				new BX.PopupWindowButtonLink({
					text : '<?=CUtil::JSEscape(GetMessage('ME_MS_BTN_CLOSE'))?>',
					className : "popup-window-button-link-cancel",
					events : {
						click : function() {window.meeting_selector_wnd.close()}
					}
				}),

			]
		});
	}
	else
	{
		window.meeting_selector_wnd.setBindElement(el);
	}

	window.meeting_selector_wnd.show();
}

BX.ready(function(){
	var currentSelected = null;

	BX.bindDelegate(BX('meeting_selector_list'), 'click', {tagName: 'A', className: 'finder-box-item'}, function(e) {
		if (!!currentSelected)
			BX.removeClass(currentSelected, 'finder-box-item-active');

		currentSelected = this;
		BX.addClass(this, 'finder-box-item-active');
		BX.ajax.insertToNode(this.href + '?AGENDA_EX=Y&POPUP=Y&sessid='+BX.bitrix_sessid(), BX('meeting_selector_agenda'));

		return BX.PreventDefault(e);
	});
});
</script>