;(function(window){
	window.ViewEventManager = function(config)
	{
		this.id = config.id;
		this.config = config;
		this.userId = BX.message('USER_ID');
		BX.ready(BX.proxy(this.Init, this));
	};

	window.ViewEventManager.prototype = {
		Init: function()
		{
			this.pFrom = BX('feed-event-view-from-' + this.id);
			var event = this.config.EVENT;
			if (event.DATE_FROM && event.DATE_TO)
			{
				event.dateFrom = BX.parseDate(event.DATE_FROM);
				event.dateTo = BX.parseDate(event.DATE_TO);
				event.DT_FROM_TS = event.dateFrom.getTime();
				event.DT_TO_TS = event.dateTo.getTime();
				if (event.DT_SKIP_TIME !== "Y")
				{
					event.DT_FROM_TS -= event['~USER_OFFSET_FROM'] * 1000;
					event.DT_TO_TS -= event['~USER_OFFSET_TO'] * 1000;
				}
				this.pFrom.innerHTML = this.GetFromHtml(event.DT_FROM_TS, event.DT_SKIP_TIME);
			}
			else // Copatibility with old records
			{
				this.pFrom.innerHTML = this.GetFromHtml(this.config.EVENT.DT_FROM_TS, this.config.EVENT.DT_SKIP_TIME);
			}

			// Invite controls
			var status = null;
			if (this.config.EVENT.IS_MEETING)
				status = this.config.attendees[this.userId].STATUS;
			this.ShowUserStatus(status);
		},

		ShowUserStatus: function(status)
		{
			var inviteCont = BX('feed-event-invite-controls-' + this.id);
			if (status)
			{
				inviteCont.className = 'calendar-invite-cont' + ' calendar-invite-cont-' + status.toLowerCase();
				if (status == 'Q')
				{
					BX('feed-event-accept-' + this.id).onclick = BX.proxy(this.Accept, this);
					BX('feed-event-decline-' + this.id).onclick = BX.proxy(this.Decline, this);
				}
			}
			else
			{
				inviteCont.style.display = 'none';
			}
		},

		ShowAttendeesRow: function(params)
		{
			this.pAttendeesCont = BX('feed-event-attendees-cont-' + this.id);
			this.pAttendeesWrap = BX('feed-event-attendees-wrap-' + this.id);

			var
				mess,
				html = '',
				accCount = parseInt(params.ACCEPTED_ATTENDEES_COUNT, 10),
				decCount = parseInt(params.DECLINED_ATTENDEES_COUNT, 10);

			this.pAttendeesCont.style.display = (accCount > 0 || decCount > 0) ? 'block' : 'none';

			if (accCount > 0)
			{
				mess = this.config.ECLFV_INVITE_ATTENDEES_ACC.replace('#ATTENDEES_NUM#', params.ACCEPTED_ATTENDEES_MESSAGE);
				html += '<span class="calendar-event-text">' + mess + '</span>';
				if (decCount > 0)
					html += ', ';
			}
			if (decCount > 0)
			{
				mess = this.config.ECLFV_INVITE_ATTENDEES_DEC.replace('#ATTENDEES_NUM#', params.DECLINED_ATTENDEES_MESSAGE);
				html += '<span class="calendar-event-text">' + mess + '</span>';
				if (decCount > 0)
					html += ', ';
			}
			this.pAttendeesWrap.innerHTML = html;
		},

		SetStatus: function(status)
		{
			var _this = this;

			BX.ajax.get(
				this.config.actionUrl,
				{
					mobile_action:"calendar_livefeed_view",
					event_feed_action: status,
					sessid: BX.bitrix_sessid(),
					event_id: this.config.eventId,
					ajax_params: this.config.AJAX_PARAMS
				},
				function(result)
				{
					setTimeout(function()
					{
						if (result.indexOf('#EVENT_FEED_RESULT_OK#') !== -1 && _this.config.EVENT.IS_MEETING)
						{
							_this.ShowUserStatus(status == 'accept' ? "Y" : "N");
							if (window.ViewEventManager.requestResult)
								_this.ShowAttendeesRow(window.ViewEventManager.requestResult);
						}
					}, 150);
				}
			);
		},

		Accept: function(e)
		{
			this.SetStatus('accept');
			return BX.PreventDefault(e);
		},

		Decline: function(e)
		{
			this.SetStatus('decline');
			return BX.PreventDefault(e);
		},

		GetFromHtml: function(DT_FROM_TS, DT_SKIP_TIME)
		{
			var
				fromDate = new Date(DT_FROM_TS),
				dateFormat = BX.date.convertBitrixFormat(BX.message('FORMAT_DATE')),
				timeFormat = BX.message('FORMAT_DATETIME'),
				timeFormat2 = BX.util.trim(timeFormat.replace(BX.message('FORMAT_DATE'), '')),
				html;

			if (timeFormat2 == dateFormat)
				timeFormat = "HH:MI";
			else
				timeFormat = timeFormat2.replace(/:SS/ig, '');
			timeFormat = BX.date.convertBitrixFormat(timeFormat);

			if (DT_SKIP_TIME == 'Y')
			{
				html = BX.date.format([
					["today", "today"],
					["tommorow", "tommorow"],
					["yesterday", "yesterday"],
					["" , dateFormat]
				], fromDate);
			}
			else
			{
				html = BX.date.format([
					["today", "today"],
					["tommorow", "tommorow"],
					["yesterday", "yesterday"],
					["" , dateFormat]
				], fromDate);

				html += ', ' + BX.date.format(timeFormat, fromDate);
			}

			return html;
		}
	};
})(window);