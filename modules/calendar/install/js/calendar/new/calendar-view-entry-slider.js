;(function(window) {

	function ViewSlider(calendar)
	{
		this.calendar = calendar;
		this.id = 'calendar_view_slider_' + Math.round(Math.random() * 1000000);
		this.sliderId = "calendar:view-entry-slider";
		this.zIndex = 3100;
		this.DOM = {};
	}

	ViewSlider.prototype = {
		show: function(params)
		{
			this.entry = params.entry;
			this.formType = params.formType || 'slider_main';

			this.calendar.util.doBxContextFix();

			BX.SidePanel.Instance.open(this.sliderId, {
				contentCallback: BX.delegate(this.createContent, this),
				events: {
					onDestroy: function() {
						this.xhr.abort();
					}.bind(this),
					onClose: BX.proxy(this.hide, this),
					onCloseComplete: BX.proxy(this.destroy, this)
				}
			});

			this.calendar.disableKeyHandler();

			BX.bind(document, "click", BX.proxy(this.calendar.util.applyHacksForPopupzIndex, this.calendar.util));
			this.opened = true;
		},

		hide: function (event)
		{
			if (event && event.getSliderPage && event.getSliderPage().getUrl() === this.sliderId)
			{
				if (this.denyClose)
				{
					event.denyAction();
				}
				else
				{
					BX.removeCustomEvent("SidePanel.Slider:onClose", BX.proxy(this.hide, this));
				}
			}
		},

		destroy: function (event)
		{
			if (event && event.getSliderPage && event.getSliderPage().getUrl() === this.sliderId)
			{
				BX.unbind(document, "click", BX.proxy(this.calendar.util.applyHacksForPopupzIndex, this.calendar.util));
				BX.removeCustomEvent("SidePanel.Slider:onCloseComplete", BX.proxy(this.destroy, this));
				BX.onCustomEvent('OnCalendarPlannerDoUninstall', [{plannerId: this.plannerId}]);
				BX.SidePanel.Instance.destroy(this.sliderId);
				this.calendar.enableKeyHandler();

				if (this.userListPopup)
					this.userListPopup.close();

				setTimeout(BX.delegate(function()
				{
					this.calendar.getView().deselectEntry();
				}, this), 300);

				this.opened = false;

				this.calendar.util.restoreBxContextFix();
			}
		},

		isOpened: function()
		{
			return this.opened;
		},

		close: function ()
		{
			BX.SidePanel.Instance.close();
		},

		createContent: function(slider)
		{
			top.BX.onCustomEvent(top, 'onCalendarBeforeCustomSliderCreate');
			var promise = new BX.Promise();

			this.xhr = BX.ajax.get(this.calendar.util.getActionUrl(), {
				action: 'get_view_slider',
				unique_id: this.id,
				form_type: this.formType,
				sessid: BX.bitrix_sessid(),
				bx_event_calendar_request: 'Y',
				entry_id: this.entry.id,
				date_from: this.entry.data['~CURRENT_DATE'] || this.entry.data.DATE_FROM,
				section_name: this.entry.getSectionName(),
				date_from_offset: this.entry.data.TZ_OFFSET_FROM,
				reqId: Math.round(Math.random() * 1000000)
			}, BX.delegate(function(html)
			{
				if (slider.isDestroyed())
				{
					promise.fulfill();
				}
				else
				{
					promise.fulfill(BX.util.trim(html));
					this.initControls();
				}
			}, this));

			return promise;
		},

		initControls: function ()
		{
			this.DOM.buttonSet = BX(this.id + '_buttonset');
			if (!this.DOM.buttonSet)
			{
				return;
			}

			this.initPlannerControl();
			this.initUserListControl();

			this.DOM.editButton = BX(this.id + '_but_edit');
			this.DOM.delButton = BX(this.id + '_but_del');

			if (BX(this.id + '_time_inner_wrap').offsetHeight > 50)
			{
				BX.addClass(BX(this.id + '_time_wrap'), 'calendar-slider-sidebar-head-long-time');
			}

			if (this.calendar.entryController.canDo(this.entry, 'edit'))
			{
				BX.bind(this.DOM.editButton, 'click', BX.delegate(function ()
				{
					BX.SidePanel.Instance.close(false, BX.delegate(function ()
					{
						this.calendar.entryController.editEntry({
							entry: this.entry
						});
					}, this));
				}, this));
			}
			else
			{
				BX.remove(this.DOM.editButton);
			}

			if (this.calendar.entryController.canDo(this.entry, 'delete'))
			{
				BX.bind(this.DOM.delButton, 'click', BX.delegate(function ()
				{
					this.calendar.entryController.deleteEntry(this.entry);
				}, this));
			}
			else
			{
				BX.remove(this.DOM.delButton);
			}

			BX.viewElementBind(
				this.id + '_' + this.entry.id + '_files_wrap',
				{
					showTitle: true
				},
				function(node)
				{
					return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer') || node.getAttribute('data-bx-image'));
				}
			);

			if (this.entry && this.entry.getCurrentStatus())
			{
				this.initAcceptMeetingControll();
			}

			var sidebarInner =  BX(this.id + '_sidebar_inner');
			if (sidebarInner)
			{
				var items = sidebarInner.querySelectorAll('.calendar-slider-sidebar-border-bottom');
				if (items.length >= 2)
				{
					BX.removeClass(items[items.length - 1], 'calendar-slider-sidebar-border-bottom');
				}
			}

			this.DOM.copyButton = BX(this.id + '_copy_url_btn');
			if (this.DOM.copyButton)
			{
				BX.bind(this.DOM.copyButton, 'click', BX.proxy(this.copyEventUrl, this));
			}
		},

		initPlannerControl: function()
		{
			this.plannerId = this.id + '_view_slider_planner';
			this.DOM.plannerWrap = BX(this.id + '_view_planner_wrap');
			setTimeout(BX.delegate(function()
			{
				if (this.DOM.plannerWrap)
				{
					BX.removeClass(this.DOM.plannerWrap, 'hidden');
				}
			}, this), 500);

			setTimeout(BX.delegate(function(){
				if (this.DOM.plannerWrap && this.DOM.plannerWrap.offsetWidth)
				{
					BX.onCustomEvent('OnCalendarPlannerDoResize', [
						{
							plannerId: this.plannerId,
							timeoutCheck: true,
							width: this.DOM.plannerWrap.offsetWidth
						}
					]);
				}
			}, this), 200);

			BX.bind(window, 'resize', BX.delegate(function(){
				if (this.DOM.plannerWrap && this.DOM.plannerWrap.offsetWidth)
				{
					BX.onCustomEvent('OnCalendarPlannerDoResize', [
						{
							plannerId: this.plannerId,
							timeoutCheck: true,
							width: this.DOM.plannerWrap.offsetWidth
						}
					]);
				}
			}, this));
		},

		initUserListControl: function()
		{
			var userList = {y : [], i: [], q: [], n: []};

			if (this.entry.isMeeting())
			{
				this.entry.getAttendees().forEach(function(user)
				{
					if (user.STATUS === 'H')
					{
						userList.y.push(user);
					}
					else if (userList[user.STATUS.toLowerCase()])
					{
						userList[user.STATUS.toLowerCase()].push(user);
					}
				}, this);
			}

			BX.bind(BX(this.id + '_attendees_y'), 'click', BX.delegate(function(){this.showUserListPopup(BX(this.id + '_attendees_y'), userList.y);}, this));
			BX.bind(BX(this.id + '_attendees_n'), 'click', BX.delegate(function(){this.showUserListPopup(BX(this.id + '_attendees_n'), userList.n);}, this));
			BX.bind(BX(this.id + '_attendees_q'), 'click', BX.delegate(function(){this.showUserListPopup(BX(this.id + '_attendees_q'), userList.q);}, this));
			BX.bind(BX(this.id + '_attendees_i'), 'click', BX.delegate(function(){this.showUserListPopup(BX(this.id + '_attendees_i'), userList.i);}, this));
		},

		showUserListPopup: function(node, userList)
		{
			if (this.userListPopup)
				this.userListPopup.close();

			if (!userList || !userList.length)
				return;

			this.DOM.userListPopupWrap = BX.create('DIV', {props: {className: 'calendar-user-list-popup-block'}});
			userList.forEach(function(user){
				var userWrap = this.DOM.userListPopupWrap.appendChild(BX.create('DIV', {props: {className: 'calendar-slider-sidebar-user-container calendar-slider-sidebar-user-card'}}));

				userWrap.appendChild(BX.create('DIV', {props: {className: 'calendar-slider-sidebar-user-block-avatar'}}))
					.appendChild(BX.create('DIV', {props: {className: 'calendar-slider-sidebar-user-block-item'}}))
					.appendChild(BX.create('IMG', {props: {width: 34, height: 34, src: user.AVATAR}}));

				userWrap.appendChild(
					BX.create("DIV", {props: {className: 'calendar-slider-sidebar-user-info'}}))
					.appendChild(BX.create("A", {
						props: {
							href: user.URL ? user.URL : '#',
							className: 'calendar-slider-sidebar-user-info-name'
						},
						text: user.DISPLAY_NAME
					}));
			}, this);

			this.userListPopup = BX.PopupWindowManager.create(this.calendar.id + "-user-list-popup",
				node,
				{
					autoHide: true,
					closeByEsc: true,
					offsetTop: 0,
					offsetLeft: 0,
					width: 220,
					resizable: false,
					lightShadow: true,
					content: this.DOM.userListPopupWrap,
					className: 'calendar-user-list-popup',
					zIndex: 4000
				});

			this.userListPopup.setAngle({offset: 36});
			this.userListPopup.show();
			BX.addCustomEvent(this.userListPopup, 'onPopupClose', BX.delegate(function()
			{
				this.userListPopup.destroy();
			}, this));
		},

		initAcceptMeetingControll: function ()
		{
			this.setStatus = new SetStatusButton({
				calendar: this.calendar,
				wrap: BX(this.id + '_status_buttonset'),
				currentStatus: BX(this.id + '_current_status').value || this.entry.getCurrentStatus(),
				changeStatusCallback: BX.delegate(function(value)
				{
					return this.calendar.entryController.setMeetingStatus(this.entry, value);
				}, this)
			});
		},

		copyEventUrl: function()
		{
			var url = this.calendar.util.getEventPath(this.entry);
			if(!BX.clipboard.copy(url))
			{
				return;
			}

			this.timeoutIds = this.timeoutIds || [];
			var popupParams = {
				content: BX.message('CALENDAR_TIP_TEMPLATE_LINK_COPIED'),
				darkMode: true,
				autoHide: true,
				zIndex: 1000,
				angle: true,
				offsetLeft: 20
			};
			var popup = new BX.PopupWindow(
				'calendar_clipboard_copy',
				this.DOM.copyButton,
				popupParams
			);
			popup.show();

			var timeoutId;
			while(timeoutId = this.timeoutIds.pop())
				clearTimeout(timeoutId);
			timeoutId = setTimeout(function(){
				popup.close();
			}, 1500);
			this.timeoutIds.push(timeoutId);
		}
	};

	function SetStatusButton(params)
	{
		this.calendar = params.calendar;
		this.wrap = params.wrap;
		this.id = this.calendar.id + '_set_status_button';
		this.status = params.currentStatus;
		this.changeStatusCallback = params.changeStatusCallback;
		this.zIndex = 3100;
		this.create();
		this.updateStatus();
	}

	SetStatusButton.prototype = {
		create: function ()
		{
			this.selectorButton = this.wrap.appendChild(BX.create("SPAN", {
				props: {className: "webform-small-button webform-small-button-transparent webform-small-button-dropdown"},
				events: {click: BX.proxy(this.showPopup, this)}
			}));
			this.selectorButtonText = this.selectorButton.appendChild(BX.create("SPAN", {
				props: {className: "webform-small-button-text"}
			}));
			this.selectorButtonIcon = this.selectorButton.appendChild(BX.create("SPAN", {
				props: {className: "webform-small-button-icon"}
			}));

			this.buttonY = this.wrap.appendChild(BX.create("SPAN", {
				props: {className: "webform-small-button webform-small-button-accept"},
				events: {click: BX.proxy(function(){this.setStatus('Y');}, this)},
				html: BX.message('EC_VIEW_DESIDE_BUT_Y')
			}));
			this.buttonI = this.wrap.appendChild(BX.create("SPAN", {
				props: {className: "webform-small-button webform-small-button-transparent"},
				style: {display: 'none'},
				events: {click: BX.proxy(function(){this.setStatus('I');}, this)},
				html: BX.message('EC_VIEW_DESIDE_BUT_I')
			}));
			this.buttonN = this.wrap.appendChild(BX.create("SPAN", {
				props: {className: "webform-small-button-link"},
				events: {click: BX.proxy(function(){this.setStatus('N');}, this)},
				html: BX.message('EC_VIEW_DESIDE_BUT_N')
			}));
		},

		updateStatus: function()
		{
			if (this.status === 'Q')
			{
				this.selectorButton.style.display = 'none';
				this.buttonY.style.display = '';
				this.buttonN.style.display = '';
			}
			else
			{
				this.selectorButton.style.display = '';
				this.selectorButtonText.innerHTML = BX.message('EC_VIEW_STATUS_BUT_' + this.status);

				this.buttonY.style.display = 'none';
				this.buttonI.style.display = 'none';
				this.buttonN.style.display = 'none';
			}
		},

		setStatus: function(value)
		{
			this.status = value;
			if (this.menuPopup)
			{
				this.menuPopup.close();
			}

			var res = true;
			if (BX.type.isFunction(this.changeStatusCallback))
			{
				res = this.changeStatusCallback(this.status);
			}

			if (res)
			{
				this.updateStatus();
			}
		},

		showPopup: function ()
		{
			if (this.menuPopup && this.menuPopup.popupWindow && this.menuPopup.popupWindow.isShown())
			{
				return this.menuPopup.close();
			}

			var menuItems;

			if (this.status === 'Y' || this.status === 'H')
			{
				menuItems = [
					{
						text: BX.message('EC_VIEW_DESIDE_BUT_N'),
						onclick: BX.proxy(function(){this.setStatus('N');}, this)
					}
				];
			}
			else if(this.status === 'N')
			{
				menuItems = [
					{
						text: BX.message('EC_VIEW_DESIDE_BUT_Y'),
						onclick: BX.proxy(function(){this.setStatus('Y');}, this)
					}
				];
			}
			else if(this.status === 'I')
			{
				menuItems =[
					{
						text: BX.message('EC_VIEW_DESIDE_BUT_Y'),
						onclick: BX.proxy(function(){this.setStatus('Y');}, this)
					},
					{
						text: BX.message('EC_VIEW_DESIDE_BUT_N'),
						onclick: BX.proxy(function(){this.setStatus('N');}, this)
					}
				];
			}

			this.menuPopup = BX.PopupMenu.create(
				this.id,
				this.selectorButtonIcon,
				menuItems,
				{
					closeByEsc : true,
					autoHide : true,
					zIndex: this.zIndex,
					offsetTop: 15,
					offsetLeft: 5,
					angle: true
				}
			);

			this.menuPopup.show();

			BX.addCustomEvent(this.menuPopup.popupWindow, 'onPopupClose', BX.delegate(function()
			{
				BX.PopupMenu.destroy(this.id);
				this.menuPopup = null;
			}, this));
		}
	};

	if (window.BXEventCalendar)
	{
		window.BXEventCalendar.ViewEntrySlider = ViewSlider;
	}
	else
	{
		BX.addCustomEvent(window, "onBXEventCalendarInit", function()
		{
			window.BXEventCalendar.ViewEntrySlider = ViewSlider;
		});
	}
})(window);