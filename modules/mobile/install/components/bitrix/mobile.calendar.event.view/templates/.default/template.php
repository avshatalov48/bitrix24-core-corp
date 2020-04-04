<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();

CUtil::InitJSCore(array('ajax', 'date'));
?>

<script>
var
	userId = parseInt(<?= $arResult['USER_ID']?>),
	eventId = parseInt('<?= $arResult['EVENT']['ID']?>'),
	version = parseInt('<?= $arResult['EVENT']['VERSION']?>');

app.pullDown({
	enable: true,
	pulltext: '<?= GetMessage('PULL_TEXT')?>',
	downtext: '<?= GetMessage('DOWN_TEXT')?>',
	loadtext: '<?= GetMessage('LOAD_TEXT')?>',
	callback:function(){document.location.reload();}
});

// onPull handler
BX.addCustomEvent("onPull-calendar", function(data)
{
	if (data.params.NEW == 'Y')
		app.removeTableCache('calendar_list');

	if (data.params.EVENT.ID == eventId)
	{
		app.removeTableCache('calendar_list');
		version = data.params.EVENT.VERSION;

		// Update
		window.mobileViewEventManager.PrepareEventOnPull(data.params.EVENT, data.params.ATTENDEES);
		window.mobileViewEventManager.UpdateForm();
	}
});

(function() {
	var BX = window.BX;

	function ViewEventManager(data)
	{
		this.Init(data);
	}

	ViewEventManager.prototype.Init = function(data)
	{
		var _this = this;
		this.bAmPm = <?= IsAmPmMode() ? 'true' : 'false'?>;
		this.userId = data.USER_ID;
		this.oEvent = data.EVENT;
		this.bDeleted = data.DELETED == 'Y';
		this.oAttendees = this.oEvent.IS_MEETING ? data.ATTENDEES : {};

		this.importance = {
			high: '<?= GetMessageJS('MBCAL_VIEWEV_IMP_HIGH')?>',
			normal: '<?= GetMessageJS('MBCAL_VIEWEV_IMP_NORMAL')?>',
			low: '<?= GetMessageJS('MBCAL_VIEWEV_IMP_LOW')?>'
		};
		this.acc = {
			absent: '<?= GetMessageJS('MBCAL_VIEWEV_ACC_ABSENT')?>',
			busy: '<?= GetMessageJS('MBCAL_VIEWEV_ACC_BUSY')?>',
			quest: '<?= GetMessageJS('MBCAL_VIEWEV_ACC_QUEST')?>',
			free: '<?= GetMessageJS('MBCAL_VIEWEV_ACC_FREE')?>'
		};
		this.reminders = {
			min: '<?= GetMessageJS('MBCAL_VIEWEV_REM_MIN')?>',
			hour: '<?= GetMessageJS('MBCAL_VIEWEV_REM_HOUR1')?>',
			hours: '<?= GetMessageJS('MBCAL_VIEWEV_REM_HOURS')?>',
			day: '<?= GetMessageJS('MBCAL_VIEWEV_REM_DAY')?>',
			days: '<?= GetMessageJS('MBCAL_VIEWEV_REM_DAYS')?>'
		};
		this.days = {
			MO: '<?= GetMessageJS('MBCAL_VIEWEV_MO')?>',
			TU: '<?= GetMessageJS('MBCAL_VIEWEV_TU')?>',
			WE: '<?= GetMessageJS('MBCAL_VIEWEV_WE')?>',
			TH: '<?= GetMessageJS('MBCAL_VIEWEV_TH')?>',
			FR: '<?= GetMessageJS('MBCAL_VIEWEV_FR')?>',
			SA: '<?= GetMessageJS('MBCAL_VIEWEV_SA')?>',
			SU: '<?= GetMessageJS('MBCAL_VIEWEV_SU')?>'
		};

		this.pForm = {
			// Base params
			cont: BX('mbcal-view-cont'),
			deleted: BX('mbcal-view-deleted'),
			name: BX('mbcal-view-name'),
			fromToCont: BX('mbcal-view-from-to'),
			description: BX('mbcal-view-desc'),
			descriptionCont: BX('mbcal-view-desc-cont'),
			location: BX('mbcal-view-location'),
			locationCont: BX('mbcal-view-location-cont'),
			importance: BX('mbcal-view-importance'),
			accessibility: BX('mbcal-view-acc'),
			remind: BX('mbcal-view-remind'),
			remindCont: BX('mbcal-view-remind-cont'),
			privateNotice: BX('mbcal-view-private'),
			repeatCont: BX('mbcal-view-repeat-cont'),
			repeat: BX('mbcal-view-repeat'),

			statusPanel: BX('mbcal-status-panel'),
			acceptBut: BX('mbcal-status-panel-accept'),
			declineBut: BX('mbcal-status-panel-decline'),
			statusPanelY: BX('mbcal-status-panel-y'),
			declineBut2: BX('mbcal-status-panel-decline2'),
			statusPanelN: BX('mbcal-status-panel-n'),
			acceptBut2: BX('mbcal-status-panel-accept2'),

			hostCont: BX('mbcal-view-host-cont'),
			host: BX('mbcal-view-host'),
			attCont: BX('mbcal-view-att-cont'),
			attContWrap: BX('mbcal-view-att-cont-wrap')
		};

		BXMobileApp.addCustomEvent("onCalendarEventRemoved", function(data)
		{
			if (data.event_id == _this.oEvent.ID)
				BX.addCustomEvent("onOpenPageAfter", function(){app.closeController({drop: true});});
		});

		BXMobileApp.addCustomEvent("onCalendarEventChanged", function(data)
		{
			if (data.event_id == _this.oEvent.ID)
			{
				document.location.reload();
			}
		});

		new FastButton(this.pForm.acceptBut, BX.proxy(this.Accept, this), false);
		new FastButton(this.pForm.acceptBut2, BX.proxy(this.Accept, this), false);
		new FastButton(this.pForm.declineBut, BX.proxy(this.Decline, this), false);
		new FastButton(this.pForm.declineBut2, BX.proxy(this.Decline, this), false);
		new FastButton(this.pForm.statusPanelY, function(){BX.toggleClass(_this.pForm.statusPanelY, 'close')}, false);
		new FastButton(this.pForm.statusPanelN, function(){BX.toggleClass(_this.pForm.statusPanelN, 'close')}, false);
	};

	ViewEventManager.prototype.PrepareEventOnPull = function(Event, Attendees)
	{
		this.oEvent = Event || {};
		this.oEvent.PRIVATE_EVENT = this.oEvent.PRIVATE_EVENT === true || this.oEvent.PRIVATE_EVENT === 'YES';
		this.oEvent.IS_MEETING = this.oEvent.IS_MEETING === true || this.oEvent.IS_MEETING === 'YES';

		var
			att,
			oAttendees = {
				count: 0,
				Y: [], // Accepted
				N: [], // Declined
				Q: [] // ?
			};

		if (this.oEvent.IS_MEETING)
		{
			for (var i in Attendees)
			{
				if (Attendees.hasOwnProperty(i))
				{
					att = Attendees[i];
					oAttendees[att.STATUS].push(att);
					oAttendees.count++;
				}
			}
		}

		this.oAttendees = oAttendees;
	};

	ViewEventManager.prototype.UpdateForm = function()
	{
		if (this.bDeleted == true)
		{
			this.pForm.cont.style.display = 'none';
			this.pForm.deleted.style.display = '';
			app.removeTableCache('calendar_list');
		}
		else
		{
			this.pForm.cont.style.display = '';
			this.pForm.deleted.style.display = 'none';

			// Basic info
			this.pForm.name.innerHTML = BX.util.htmlspecialchars(this.oEvent.NAME);
			BXMPage.getTitle().setText(this.oEvent.NAME);
			BXMPage.getTitle().show();
			// Description
			this.pForm.descriptionCont.style.display = this.oEvent['~DESCRIPTION'] == '' ? 'none' : '';
			var desc = BX.util.trim(this.oEvent['~DESCRIPTION']);
			if (desc != '')
			{
				var oDesc = BX.processHTML(desc);
				desc = oDesc.HTML;
				this.pForm.description.innerHTML = desc;

				setTimeout(function()
				{
					if (oDesc.SCRIPT && oDesc.SCRIPT.length > 0)
					{
						var sc, scriptsInt = '';
						for (var i = 0; i < oDesc.SCRIPT.length; i++)
						{
							sc = oDesc.SCRIPT[i];
							if (sc.isInternal)
								scriptsInt += ';' + sc.JS;
						}
						BX.evalGlobal(scriptsInt);
					}
				}, 100);
			}
			else
			{
				this.pForm.description.innerHTML = '';
			}

			// Location
			this.pForm.locationCont.style.display = this.oEvent.LOCATION == '' ? 'none' : '';
			this.pForm.location.innerHTML = BX.util.htmlspecialchars(this.oEvent.LOCATION);
			// Importance
			this.pForm.importance.innerHTML = this.importance[this.oEvent.IMPORTANCE];
			// Accessibility
			this.pForm.accessibility.innerHTML = this.acc[this.oEvent.ACCESSIBILITY];

			// Repeat
			this.pForm.repeatCont.style.display = this.oEvent.RRULE == '' ? 'none' : '';
			this.pForm.repeat.innerHTML = this.GetRepeatHtml();

			// Reminder
			this.pForm.remindCont.style.display = this.oEvent.REMIND == '' ? 'none' : '';

			if (this.oEvent.REMIND && this.oEvent.REMIND[0])
			{
				var
					mes = '',
					type = this.oEvent.REMIND[0]['type'],
					count = this.oEvent.REMIND[0]['count'];

				if(type == 'min')
					mes = this.reminders.min;
				else if(type == 'hour')
					mes = count == 1 ? this.reminders.hour : this.reminders.hours;
				else if(type == 'day')
					mes = count == 1 ? this.reminders.day : this.reminders.days;

				mes = mes.replace('#N#', count);
				this.pForm.remind.innerHTML = mes;
			}

			// From - to
			this.pForm.fromToCont.innerHTML = this.GetFromToHtml(this.oEvent.DATE_FROM, this.oEvent.DATE_TO, this.oEvent.DT_SKIP_TIME, this.oEvent.DT_LENGTH);

			// Private event
			this.pForm.privateNotice.style.display = this.oEvent.PRIVATE_EVENT ? '' : 'none';

			this.pForm.statusPanelY.style.display = 'none';
			this.pForm.statusPanelN.style.display = 'none';
			if (this.oEvent.IS_MEETING)
			{
				this.pForm.hostCont.style.display = '';
				this.pForm.host.innerHTML = BX.util.htmlspecialchars(this.oEvent.MEETING.HOST_NAME);

				if (this.oEvent.MEETING_HOST != this.userId)
				{
					if (this.oEvent.MEETING_STATUS == 'Y')
					{
						this.pForm.statusPanelY.style.display = '';
						this.pForm.statusPanel.style.display = 'none';
						this.pForm.statusPanelN.style.display = 'none';
					}
					else if (this.oEvent.MEETING_STATUS == 'N')
					{
						this.pForm.statusPanelY.style.display = 'none';
						this.pForm.statusPanel.style.display = 'none';
						this.pForm.statusPanelN.style.display = '';
					}
					else
					{
						this.pForm.statusPanel.style.display = '';
					}
				}
				else
				{
					this.pForm.statusPanel.style.display = 'none';
				}

				this.pForm.attCont.style.display = '';
				BX.cleanNode(this.pForm.attContWrap);
				this.DisplayAttendee(this.oAttendees.Y, this.pForm.attContWrap, 'cle-confirmed');
				this.DisplayAttendee(this.oAttendees.Q, this.pForm.attContWrap, 'cle-not-confirmed');
				this.DisplayAttendee(this.oAttendees.N, this.pForm.attContWrap, 'cle-refused');
			}
			else
			{
				this.pForm.hostCont.style.display = 'none';
				this.pForm.attCont.style.display = 'none';
				this.pForm.statusPanel.style.display = 'none';
			}
		}

		this.DisplayEditButton();
	};

	ViewEventManager.prototype.DisplayEditButton = function()
	{
		if (this.bDeleted || (this.oEvent.IS_MEETING && this.oEvent.MEETING_HOST != this.userId))
		{
			BX.MobileUI.bottomPanel.clear();
		}
		else
		{

			BX.MobileUI.bottomPanel.setButtons([
					{
						name:'<?= GetMessageJS('MB_CALENDAR_EDIT_BUT'); ?>',
						callback: function ()
						{
							BXMPager.loadPageBlank({
								url:'/mobile/calendar/edit_event.php?event_id=' + eventId + '&ver=' + version,
								bx24ModernStyle:true
							});
						}
					}
				]
			);
		}
	};

	ViewEventManager.prototype.GetFromToHtml = function(DATE_FROM, DATE_TO, DT_SKIP_TIME, DT_LENGTH)
	{
		var
			fromDate = BX.parseDate(DATE_FROM),
			toDate = BX.parseDate(DATE_TO),
			dayl = 86400,
			dateFormat = "<?= GetMessage('MB_CAL_EVENT_DATE_FORMAT')?>",
			timeFormat = this.bAmPm ? "<?= GetMessage('MB_CAL_EVENT_TIME_FORMAT_AMPM')?>" : "<?= GetMessage('MB_CAL_EVENT_TIME_FORMAT')?>",
			html;

		if (!fromDate || !toDate)
			return '';

		if (DT_SKIP_TIME == 'Y')
		{
			if (DT_LENGTH == dayl) // One full day event
			{
				html = BX.date.format([
					["today", "today"],
					["tommorow", "tommorow"],
					["yesterday", "yesterday"],
					["" , dateFormat]
				], fromDate);
				html += ', <?= GetMessageJS('MBCAL_VIEWEV_FULL_DAY')?>';
			}
			else // Event for several days
			{
				html = '<?= GetMessageJS('MBCAL_VIEWEV_DATE_FROM_TO')?>';
				html = html.replace('#DATE_FROM#', BX.date.format(dateFormat, fromDate));
				html = html.replace('#DATE_TO#', BX.date.format(dateFormat, toDate));
			}
		}
		else
		{
			if (fromDate.getDate() == toDate.getDate() && fromDate.getMonth() == toDate.getMonth() && fromDate.getFullYear() == toDate.getFullYear()) // Event during one day
			{
				html = BX.date.format([
					["today", "today"],
					["tommorow", "tommorow"],
					["yesterday", "yesterday"],
					["" , dateFormat]
				], fromDate);

				html += ', <?= GetMessageJS('MBCAL_VIEWEV_TIME_FROM_TO_TIME')?>';
				html = html.replace('#TIME_FROM#', BX.date.format(timeFormat, fromDate));
				html = html.replace('#TIME_TO#', BX.date.format(timeFormat, toDate));
			}
			else
			{
				html = '<?= GetMessageJS('MBCAL_VIEWEV_DATE_FROM_TO')?>';
				html = html.replace('#DATE_FROM#', BX.date.format(dateFormat + ' ' + timeFormat, fromDate));
				html = html.replace('#DATE_TO#', BX.date.format(dateFormat + ' ' + timeFormat, toDate));
			}
		}
		return html;
	};

	ViewEventManager.prototype.DisplayAttendee = function(list, pCont, className)
	{
		for (var i = 0; i < list.length; i++)
			pCont.appendChild(BX.create('LI', {props: {className: className}, text: list[i].DISPLAY_NAME}));
	};

	ViewEventManager.prototype.Decline = function()
	{
		this.SetMeetingStatus('N');
	};

	ViewEventManager.prototype.Accept = function()
	{
		// 1. Set status
		this.SetMeetingStatus('Y');

		// 2. Update attendees list
		var i, att, arAtt, keys = ['Q', 'N', 'Y'];

		for (var k = 0; k < keys.length; k++)
		{
			arAtt = this.oAttendees[keys[k]];
			for(i = 0; i < arAtt.length; i++)
			{
				if (arAtt[i].USER_ID == this.userId)
				{
					att = arAtt[i];
					this.oAttendees[keys[k]] = BX.util.deleteFromArray(arAtt, i);
					break;
				}
			}
			if (att)
				break;
		}

		if (att)
		{
			this.oAttendees.Y.push(att);
			BX.cleanNode(this.pForm.attContWrap);
			this.DisplayAttendee(this.oAttendees.Y, this.pForm.attContWrap, 'cle-confirmed');
			this.DisplayAttendee(this.oAttendees.Q, this.pForm.attContWrap, 'cle-not-confirmed');
			this.DisplayAttendee(this.oAttendees.N, this.pForm.attContWrap, 'cle-refused');
		}
	};

	ViewEventManager.prototype.SetMeetingStatus = function(status)
	{
		// TODO
		var _this = this;
		var url = '/mobile/calendar/view_event.php';
		var data = {
			user_id: userId,
			event_id: eventId,
			status: status, // Y | N
			app_calendar_action: 'change_meeting_status',
			sessid: BX.bitrix_sessid()
		};

		function onChangeStatus(result)
		{
			if (status == 'Y')
			{
				_this.pForm.statusPanelY.style.display = '';
				_this.pForm.statusPanelN.style.display = 'none';
				_this.pForm.statusPanel.style.display = 'none';
			}
			else
			{
				if (status == 'N')
				{
					_this.pForm.statusPanel.style.display = 'none';
					_this.pForm.statusPanelY.style.display = 'none';
					_this.pForm.statusPanelN.style.display = '';
				}

				setTimeout(function(){app.closeController({drop: true})}, 100);
			}
			app.removeTableCache('calendar_list');
		}

		BX.ajax.post(url, data, onChangeStatus);
	};

	ViewEventManager.prototype.GetRepeatHtml = function()
	{
		if (this.oEvent.RRULE == '')
			return '';

		var repeatHTML = '', interval =  this.oEvent.RRULE.INTERVAL;

		var
			date, month,
			fromDate = BX.parseDate(this.oEvent.DATE_FROM);
		if (!fromDate)
			return '';

		switch (this.oEvent.RRULE.FREQ)
		{
			case 'DAILY':
				repeatHTML += '<b><?= GetMessageJS('EC_JS_EVERY_M')?> ' + interval + '<?= GetMessageJS('EC_JS_DE_DOT')?><?= GetMessageJS('EC_JS__J')?> <?= GetMessageJS('EC_JS_DAY_P')?> </b>';
				break;
			case 'WEEKLY':
				repeatHTML += '<b><?= GetMessageJS('EC_JS_EVERY_F')?> ';
				if (interval > 1)
					repeatHTML += interval + '<?= GetMessageJS('EC_JS_DE_DOT')?><?= GetMessageJS('EC_JS__U')?> ';
				repeatHTML += '<?= GetMessageJS('EC_JS_WEEK_P')?>: ';
				var n = 0;
				for (var i in this.oEvent.RRULE.BYDAY)
				{
					if(this.oEvent.RRULE.BYDAY[i])
						repeatHTML += (n++ > 0 ? ', ' : '') + this.days[this.oEvent.RRULE.BYDAY[i]];
				}
				repeatHTML += '</b>';
				break;
			case 'MONTHLY':
				date = fromDate.getDate();
				repeatHTML += '<b><?= GetMessageJS('EC_JS_EVERY_M')?> ';
				if (interval > 1)
					repeatHTML += interval + '<?= GetMessageJS('EC_JS_DE_DOT')?><?= GetMessageJS('EC_JS__J')?> ';
				repeatHTML += '<?= GetMessageJS('EC_JS_MONTH_P')?>, <?= GetMessageJS('EC_JS_DE_AM')?>' + date + '<?= GetMessageJS('EC_JS_DE_DOT')?><?= GetMessageJS('EC_JS_DATE_P_')?></b>';
				break;
			case 'YEARLY':
				date = fromDate.getDate();
				month = fromDate.getMonth() + 1;
				repeatHTML += '<b><?= GetMessageJS('EC_JS_EVERY_N_')?> ';
				if (interval > 1)
					repeatHTML += interval + '<?= GetMessageJS('EC_JS_DE_DOT')?><?= GetMessageJS('EC_JS__J')?> ';
				repeatHTML += '<?= GetMessageJS('EC_JS_YEAR_P')?>, <?= GetMessageJS('EC_JS_DE_AM')?>' + date + '<?= GetMessageJS('EC_JS_DE_DOT')?><?= GetMessageJS('EC_JS_DATE_P_')?> <?= GetMessageJS('EC_JS_DE_DES')?>' + month + '<?= GetMessageJS('EC_JS_DE_DOT')?><?= GetMessageJS('EC_JS_MONTH_P_')?></b>';
				break;
		}

		var dateFormat = "<?= GetMessage('MB_CAL_EVENT_DATE_FORMAT')?>";
		repeatHTML += '<br> <?= GetMessageJS('EC_JS_FROM_')?> ' + BX.date.format(dateFormat, fromDate);

		var to;
		if (parseInt(this.oEvent.RRULE.UNTIL) == this.oEvent.RRULE.UNTIL)
		{
			to = new Date(this.oEvent.RRULE.UNTIL);
		}
		else
		{
			to = BX.parseDate(this.oEvent.RRULE.UNTIL);
		}

		if (to && (to.getMonth() != 0 || to.getFullYear() != 2038))
			repeatHTML += ' <?= GetMessageJS('EC_JS_TO_')?> ' + BX.date.format(dateFormat, to);

		return repeatHTML;
	};

	window.ViewEventManager = ViewEventManager;
})();

BX.ready(function(){
	window.mobileViewEventManager = new ViewEventManager(<?= CUtil::PhpToJSObject($arResult)?>);
	window.mobileViewEventManager.UpdateForm();
});

</script>
<div class="calendar-event-page">
	<div class="calendar-event-textar-wrap" id="mbcal-view-deleted">
		<div class="calendar-event-main-block-aqua">
			<div class="calendar-event-main-block-aqua-container">
				<div class="calendar-event-description-title"><?= GetMessage('MBCAL_VIEWEV_DELETED')?></div>
			</div>
		</div>
	</div>
	<div class="calendar-event-textar-wrap" id="mbcal-view-cont">
		<div class="calendar-event-main-block-aqua">
			<div class="calendar-event-main-block-aqua-container">
				<h2 class="calendar-event-title" id="mbcal-view-name"></h2>
				<div class="calendar-event-time" id="mbcal-view-from-to"></div>
				<div class="calendar-event-repeat" id="mbcal-view-repeat-cont"><?= GetMessage('MBCAL_REPEAT_TITLE')?>: <span id="mbcal-view-repeat"></span></div>
				<div class="calendar-event-reminder" id="mbcal-view-remind-cont"><?= GetMessage('MBCAL_VIEWEV_REMINDER')?>: <span id="mbcal-view-remind"></span></div>
			</div>
		</div>

		<div class="calendar-event-button" id="mbcal-status-panel">
			<a id="mbcal-status-panel-decline" href="" class="calendar denied-button" style="float: right;"><?= GetMessage('MB_CALENDAR_DECLINE_BUT');?></a>
			<a id="mbcal-status-panel-accept" href="" class="calendar accept-button"><?= GetMessage('MB_CALENDAR_ACCEPT_BUT');?></a>
			<div style="clear: both;"></div><br>
		</div>

		<div id="mbcal-status-panel-y" class="calendar-event-main-block-aqua close">
			<div class="calendar-event-main-block-aqua-container">
				<div class="calendar-addevent-row">
					<div class="calendar-addevent-row-container">
						<div class="calendar-addevent-addmembers" id="mbcal-edit-att-title"><?= GetMessage('MB_CALENDAR_ACCEPTED_TITLE')?></div>
						<div style="clear: both;"></div>
						<div class="calendar-addevent-arrow"></div>
					</div>

					<div class="calendar-status-decline-cont">
						<a id="mbcal-status-panel-decline2" href="" class="calendar denied-button"><?= GetMessage('MB_CALENDAR_DECLINE_BUT');?></a>
					</div>
				</div>
			</div>
		</div>

		<div id="mbcal-status-panel-n" class="calendar-event-main-block-aqua close">
			<div class="calendar-event-main-block-aqua-container">
				<div class="calendar-addevent-row">
					<div class="calendar-addevent-row-container">
						<div class="calendar-addevent-addmembers"><?= GetMessage('MB_CALENDAR_DECLINED_TITLE')?></div>
						<div style="clear: both;"></div>
						<div class="calendar-addevent-arrow"></div>
					</div>

					<div class="calendar-status-accept-cont">
						<a id="mbcal-status-panel-accept2" href="" class="calendar accept-button"><?= GetMessage('MB_CALENDAR_ACCEPT_BUT_2');?></a>
					</div>
				</div>
			</div>
		</div>

		<div class="calendar-event-main-block">
			<div class="calendar-event-description-title" style="font-weight: normal!important;"><?= GetMessage('MBCAL_VIEWEV_ADDITIONAL_INFO')?>:</div>
			<div class="calendar-event-description-title" id="mbcal-view-private"><?= GetMessage('MBCAL_VIEWEV_PRIVATE_NOTICE')?></div>
			<div class="calendar-event-description" id="mbcal-view-desc-cont">
				<h3><?= GetMessage('MBCAL_VIEWEV_DESC')?>:</h3>
				<p id="mbcal-view-desc"></p>
			</div>
			<div class="calendar-event-Importance">
				<span><?= GetMessage('MBCAL_VIEWEV_IMP')?>:</span>
				<strong id="mbcal-view-importance"></strong>
			</div>
			<div class="calendar-event-employment">
				<span><?= GetMessage('MBCAL_VIEWEV_ACC')?>:</span>
				<strong id="mbcal-view-acc"></strong>
			</div>
			<div class="calendar-event-location" id="mbcal-view-location-cont">
				<span><?= GetMessage('MBCAL_VIEWEV_LOCATION')?>:</span>
				<strong id="mbcal-view-location"></strong>
			</div>
			<div class="calendar-event-employment" id="mbcal-view-host-cont">
				<span><?= GetMessage('MBCAL_VIEWEV_HOST')?>:</span>
				<span id="mbcal-view-host"></span>
			</div>
			<div class="calendar-event-members" id="mbcal-view-att-cont">
				<h3><?= GetMessage('MBCAL_VIEWEV_ATTENDEES')?>:</h3>
				<ul id="mbcal-view-att-cont-wrap"></ul>
			</div>
		</div>
	</div>
</div>

