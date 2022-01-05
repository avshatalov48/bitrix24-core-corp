"use strict";

/**
 * @module timeman/timecontrol
 */

BX.namespace('BX.TimeControl');

(function()
{
	var TimeControl = function(params)
	{
		this.init(params);

		this.successCallback = function(){};
		this.failCallback = function(){};
		this.changeCalendarCheckboxCallback = function(){};
		this.sendReportCallback = null;
	};
	TimeControl.prototype =
	{
		init: function(params)
		{
			params = params || {};

			var placeholder = params.placeholder || null;
			var absenceTime = params.absenceTime || 0;
			var absenceStart = params.absenceStart || null;
			var absenceEnd = params.absenceEnd || null;
			var absenceType = params.absenceType || 'work';
			var absenceReason = params.absenceReason || '';
			var reportId = params.reportId || 0;
			var userAvatarUrl = params.userAvatarUrl || '';
			var userGender = params.userGender || 'M';
			var calendarCheckboxDefault = true;
			var calendarCheckboxEnable = typeof params.calendarCheckboxEnable == 'undefined'? true: params.calendarCheckboxEnable !== false;


			if (typeof params.calendarCheckboxDefault == 'undefined')
			{
				calendarCheckboxDefault = BX.localStorage.get('timeman_timecontrol_calendar_checkbox');
				calendarCheckboxDefault = calendarCheckboxDefault !== false;
			}
			else
			{
				calendarCheckboxDefault = params.calendarCheckboxDefault !== false;
			}

			this.userGender = userGender;
			this.showConfirmModal = typeof params.showConfirmModal == 'undefined'? false: params.showConfirmModal !== false;
			this.customParams = params.customParams || {};
			this.absenceStartText = absenceStart? this.getTimeFormat(new Date(absenceStart)): '';
			this.absenceEndText = absenceEnd? this.getTimeFormat(new Date(absenceEnd)): '';

			this.reportId = reportId;

			var node = this.render({
				popup: !placeholder,
				absenceTime: this.getAbsenceTime(absenceTime),
				absenceType: absenceType,
				absenceReason: absenceReason,
				userAvatar: this.getAvatarStyles(userAvatarUrl),
				userGender: userGender == 'F'? 'F': 'M',
				calendarCheckboxDefault: calendarCheckboxDefault,
			});

			if (!this.absenceStartText || !this.absenceEndText)
			{
				BX.remove(this.nodeSelectorTimeHelp);
			}
			if (!calendarCheckboxEnable)
			{
				this.nodeSelectorTimeHelp = BX.findChildByClassName(node, 'bx-timeman-timecontrol-message-user-commit-checkbox');
				BX.remove(this.nodeSelectorTimeHelp);
			}

			// type of absence
			var selectorClickCallback = (event) =>
			{
				var selector = BX.findParent(event.target, {className : "bx-timeman-timecontrol-selector"});

				var inputNode = BX.findChildByClassName(selector, 'bx-timeman-timecontrol-selector-radio-input');
				if (inputNode)
				{
					inputNode.checked = true;
					inputNode.focus();

					selectorTypeChangeCallback({target: inputNode});
				}

			};

			var selectorTypeChangeCallback = (event) =>
			{
				Array.from(event.target.parentNode.parentNode.parentNode.children).forEach(node => {
					node.classList.remove("bx-timeman-timecontrol-selector-selected");
				});
				event.target.parentNode.parentNode.classList.add("bx-timeman-timecontrol-selector-selected");

				var type = this.nodeSelectorTypePrivate.checked? 'private': 'work';
				if (type == 'private' || this.nodeTextarea.value.length > 0)
				{
					this.nodeSendButton.parentNode.classList.remove('bx-timeman-timecontrol-message-user-commit-button-disabled');
				}
				else
				{
					this.nodeSendButton.parentNode.classList.add('bx-timeman-timecontrol-message-user-commit-button-disabled');
				}
			};

			BX.bindDelegate(node, 'click', {className: 'bx-timeman-timecontrol-selector'}, event => selectorClickCallback(event));

			this.nodeSelectorTypeWork = BX.findChildByClassName(node, 'bx-timeman-timecontrol-type-work');
			BX.bind(this.nodeSelectorTypeWork, 'change', event => selectorTypeChangeCallback(event));

			this.nodeSelectorTypePrivate = BX.findChildByClassName(node, 'bx-timeman-timecontrol-type-private');
			BX.bind(this.nodeSelectorTypePrivate, 'change', event => selectorTypeChangeCallback(event));

			this.nodeCloseButton = BX.findChildByClassName(node, 'bx-timeman-timecontrol-title-close');
			if (this.nodeCloseButton)
			{
				BX.bind(this.nodeCloseButton, 'click', event => this.popupDialog.close());
			}

			this.nodeSelectorTimeHelp = BX.findChildByClassName(node, 'bx-timeman-timecontrol-time-help');
			if (this.absenceStartText && this.absenceEndText)
			{
				BX.bind(this.nodeSelectorTimeHelp, 'click', event => {
					var text = BX.message('JS_CORE_TC_ABSENCE_START').replace('#DATE#', '<b>'+this.absenceStartText+'</b>')+
								'<br>'+
								BX.message('JS_CORE_TC_ABSENCE_END').replace('#DATE#', '<b>'+this.absenceEndText+'</b>');

					this.tooltip(this.nodeSelectorTimeHelp, text, {
						offsetLeft: 8,
						offsetTop: 6,
						bindOptions: {position: 'bottom'}
					});
				});
			}

			// calendar
			if (calendarCheckboxEnable)
			{
				BX.bindDelegate(node, 'click', {className: 'bx-timeman-timecontrol-message-user-commit-checkbox'}, (event) =>
				{
					var selector = BX.findParent(event.target, {className : "bx-timeman-timecontrol-message-user-commit-checkbox"});
					var inputNode = BX.findChildByClassName(selector, 'bx-timeman-timecontrol-checkbox-input-calendar');

					if (event.target != inputNode)
					{
						inputNode.checked = !inputNode.checked;
						inputNode.focus();
					}

					if (inputNode.checked)
					{
						inputNode.parentNode.classList.add("bx-timeman-timecontrol-checkbox-input-checked");
					}
					else
					{
						inputNode.parentNode.classList.remove("bx-timeman-timecontrol-checkbox-input-checked");
					}

					BX.localStorage.set('timeman_timecontrol_calendar_checkbox', inputNode.checked !== false);

					this.changeCalendarCheckboxCallback(inputNode.checked);
				});

				this.nodeSendToCalendar = BX.findChildByClassName(node, 'bx-timeman-timecontrol-checkbox-input-calendar');
			}


			// textarea
			this.nodeTextarea = BX.findChildByClassName(node, 'bx-timeman-timecontrol-message-user-comment-textarea');
			BX.bind(this.nodeTextarea, 'keydown', event => {
				if (
					(event.metaKey == true || event.ctrlKey == true)
					&& (event.keyCode == 13 || event.keyCode == 32)
				)
				{
					this.sendForm();
				}
			});
			BX.bind(this.nodeTextarea, 'keyup', (event) => {
				if (this.sendFormBlock)
				{
					return true;
				}

				var type = this.nodeSelectorTypePrivate.checked? 'private': 'work';
				if (type == 'private' || this.nodeTextarea.value.length > 0)
				{
					this.nodeSendButton.parentNode.classList.remove('bx-timeman-timecontrol-message-user-commit-button-disabled');
				}
				else
				{
					this.nodeSendButton.parentNode.classList.add('bx-timeman-timecontrol-message-user-commit-button-disabled');
				}
			});

			// button
			this.nodeSendButton = BX.findChildByClassName(node, 'bx-timeman-timecontrol-message-user-commit-button-input');
			BX.bind(this.nodeSendButton, 'click', event => this.sendForm());

			if (placeholder)
			{
				placeholder.innerHTML = '';
				placeholder.appendChild(node);
			}
			else
			{
				this.openDialog(node);
			}

			this.nodeTextarea.focus();

			if (!window.onfocus)
			{
				window.onfocus = () => {
					setTimeout(() => this.nodeTextarea.focus(), 100);
				};
			}
		},

		openDialog: function(contentNode)
		{
			if (this.popupDialog != null)
			{
				this.popupDialog.destroy();
			}

			this.popupDialog = new BX.PopupWindow('bx-timeman-timecontrol-dialog', null, {
				zIndex: 1500,
				autoHide: false,
				closeByEsc: false,
				events : { onPopupClose : function() { this.destroy() }, onPopupDestroy : BX.delegate(function() { this.popupDialog = null; }, this)},
				content : contentNode
			});

			this.popupDialog.show();
		},

		sendForm: function(params)
		{
			var type = this.nodeSelectorTypePrivate.checked? 'private': 'work';
			var calendar = this.nodeSendToCalendar? this.nodeSendToCalendar.checked: null;
			var text = this.nodeTextarea.value;

			if (
				type == 'work' && text.length <= 0
				|| this.nodeSendButton.parentNode.classList.contains('bx-timeman-timecontrol-message-user-commit-button-disabled')
				|| this.sendFormBlock
			)
			{
				return false;
			}

			if (text.length <= 0)
			{
				text = BX.message('JS_CORE_TC_ABSENCE_'+(type.toUpperCase())+'_'+this.userGender);
			}
			if (text.length <= 0)
			{
				return false;
			}

			this.sendFormBlock = true;
			this.nodeSendButton.parentNode.classList.add('bx-timeman-timecontrol-message-user-commit-button-disabled');

			var promise = null;
			if (this.sendReportCallback)
			{
				promise = this.sendReportCallback({
					reportId: this.reportId,
					type: type,
					calendar: calendar === null? null: calendar? 'Y': 'N',
					text: text,
					customParams: this.customParams
				});
			}
			else
			{
				promise = BX.rest.callMethod('timeman.timecontrol.report.add', {
					REPORT_ID: this.reportId,
					TYPE: type,
					CALENDAR: calendar === null? null: calendar? 'Y': 'N',
					TEXT: text
				});
			}

			promise.then(() => {
				if (this.popupConfirm)
				{
					this.popupConfirm.close();
				}
				if (this.popupDialog)
				{
					this.popupDialog.close();
				}
				this.successCallback();
			}).catch(() => {
				this.showConfirm(BX.message('JS_CORE_TC_SEND_ERROR'), null, this.showConfirmModal);

				this.sendFormBlock = false;
				var type = this.nodeSelectorTypePrivate.checked? 'private': 'work';

				if (type == 'private' || this.nodeTextarea.value.length > 0)
				{
					this.nodeSendButton.parentNode.classList.remove('bx-timeman-timecontrol-message-user-commit-button-disabled');
				}
				else
				{
					this.nodeSendButton.parentNode.classList.add('bx-timeman-timecontrol-message-user-commit-button-disabled');
				}
				this.failCallback();
			});
		},

		setCustomRequest: function(callback)
		{
			this.sendReportCallback = callback;
		},

		success: function(callback)
		{
			this.successCallback = callback;
		},

		fail: function(callback)
		{
			this.failCallback = callback;
		},

		changeCalendarCheckbox: function(callback)
		{
			this.changeCalendarCheckboxCallback = callback;
		},

		getTimeFormat: function(date)
		{
			var ampm = BX.isAmPmMode(true);
			var timeFormat = (ampm === BX.AM_PM_LOWER? "g:i a" : (ampm === BX.AM_PM_UPPER? "g:i A" : "H:i"));
			var getTimeFormatNotToday = this.getTimeFormatNotToday? "today": '#TODAY#';

			var day = BX.date.format([
				["tomorrow", "tomorrow"],
				["-", BX.date.convertBitrixFormat(BX.message("FORMAT_DATE"))],
				["today", getTimeFormatNotToday],
				["yesterday", "yesterday"],
				["", BX.date.convertBitrixFormat(BX.message("FORMAT_DATE"))]
			], date.getTime()/1000);

			var time = BX.date.format([
				["tomorrow", timeFormat],
				["today", timeFormat],
				["yesterday", timeFormat],
				["", timeFormat],
			], date.getTime()/1000);

			if (day && day.indexOf('#TODAY#') != -1)
			{
				return time;
			}
			else
			{
				this.getTimeFormatNotToday = true;
				return BX.message("FD_DAY_AT_TIME").replace(/#DAY#/g, day).replace(/#TIME#/g, time);
			}
		},

		getAvatarStyles: function(url)
		{
			if (!url)
				return '';

			return `background-image: url('${url}'); background-color: transparent;`;
		},

		getAbsenceTime: function(seconds)
		{
			var full = seconds / 3600;
			var hours = Math.floor(full);
			var minutes = Math.floor((3600 * (full - hours)) / 60);

			var text = '';
			if (hours > 0)
			{
				text = this.getMessagePlural('JS_CORE_TC_HOURS_#FORM#', hours);
			}
			if (minutes > 0)
			{
				text = (text.length > 0? text+' ': '')+this.getMessagePlural('JS_CORE_TC_MINUTES_#FORM#', minutes);
			}
			if (hours <= 0 && minutes <= 0 || seconds == 0)
			{
				text = BX.message('JS_CORE_TC_MINUTES_ZERO');
			}

			return text;
		},

		getMessagePlural: function(messageId, number)
		{
			number = parseInt(number);
			if (number < 0)
			{
				number = number*-1;
			}

			var pluralForm = BX.Loc.getPluralForm(number);

			return BX.message((messageId.toString().replace('#FORM#', pluralForm.toString()))).replace('#NUMBER#', number);
		},

		tooltip: function(bind, text, params)
		{
			if (this.popupTooltip != null)
				this.popupTooltip.close();

			params = params || {};

			params.offsetLeft = params.offsetLeft || 0;
			params.offsetTop = params.offsetTop || 0;
			params.width = params.width || 0;
			params.angle = typeof(params.angle) == 'undefined'? true: params.angle;
			params.showOnce = typeof(params.showOnce) == 'undefined'? false: params.showOnce;
			params.bindOptions = typeof(params.bindOptions) == 'undefined'? {position: "top"}: params.bindOptions;

			var content = '';
			if (typeof(text) == 'object')
			{
				content = BX.create("div", { props : { className: "bx-timeman-timecontrol-tooltip", style : "padding-right: 5px;"+(params.width>0? "width: "+params.width+"px;": '') }, children: [text]})
			}
			else
			{
				content = BX.create("div", { props : { className: "bx-timeman-timecontrol-tooltip", style : "padding-right: 5px;"+(params.width>0? "width: "+params.width+"px;": '') }, html: text})
			}

			this.popupTooltip = new BX.PopupWindow('bx-timeman-timecontrol-tooltip', bind, {
				lightShadow: true,
				autoHide: true,
				darkMode: true,
				offsetLeft: params.offsetLeft,
				offsetTop: params.offsetTop,
				closeIcon : {},
				bindOptions: params.bindOptions,
				events : {
					onPopupClose : function() { this.destroy(); },
					onPopupDestroy : function() { this.popupTooltip = null; }.bind(this)
				},
				zIndex: 2000,
				content: content
			});
			if (params.angle)
			{
				this.popupTooltip.setAngle({offset:23, position: params.bindOptions.position == 'top'? 'bottom': 'top'});
			}
			this.popupTooltip.show();

			return true;
		},

		showConfirm: function(text, buttons, modal)
		{
			if (this.popupConfirm != null)
			{
				this.popupConfirm.destroy();
			}

			if (typeof text == "object")
			{
				text = '<div class="bx-timeman-timecontrol-confirm-title">'+text.title+'</div>'+text.message;
			}

			modal = modal !== false;

			if (!buttons || typeof buttons == "object" && buttons.length <= 0 || buttons === false)
			{
				buttons = [new BX.PopupWindowButton({
					text : BX.message('JS_CORE_TC_CONFIRM_CLOSE'),
					className : "popup-window-button-decline",
					events : { click : function(e) { this.popupWindow.close(); BX.PreventDefault(e) } }
				})];
			}

			this.popupConfirm = new BX.PopupWindow('bx-timeman-timecontrol-confirm', null, {
				zIndex: 2000,
				autoHide: buttons === false,
				buttons : buttons,
				closeByEsc: buttons === false,
				overlay : modal,
				events : { onPopupClose : function() { this.destroy() }, onPopupDestroy : BX.delegate(function() { this.popupConfirm = null }, this)},
				content : BX.create("div", { props : { className : (buttons === false? " bx-timeman-timecontrol-confirm-without-buttons": "bx-timeman-timecontrol-confirm") }, html: text})
			});
			this.popupConfirm.show();

			BX.bind(this.popupConfirm.popupContainer, "click", BX.PreventDefault);
			BX.bind(this.popupConfirm.contentContainer, "click", BX.PreventDefault);
			BX.bind(this.popupConfirm.overlay.element, "click", BX.PreventDefault);

			if(buttons === false)
			{
				setTimeout(() => this.close(), 2000);
			}
		},

		render: function(options)
		{
			var template = `<div class="bx-timeman-timecontrol">
				<div class="bx-timeman-timecontrol-title">
					<div class="bx-timeman-timecontrol-title-text">
						${BX.message('JS_CORE_TC_TITLE')}
					</div>
					<div class="${options.popup? 'bx-timeman-timecontrol-title-close': 'bx-timeman-timecontrol-title-domain'}">
						${options.popup? BX.message('JS_CORE_TC_DIALOG_CLOSE'): location.host}
					</div>
				</div>
				<div class="bx-timeman-timecontrol-box">
					<div class="bx-timeman-timecontrol-image"></div>
					<div class="bx-timeman-timecontrol-content">
						<div class="bx-timeman-timecontrol-message bx-timeman-timecontrol-message-marta">
							<div class="bx-timeman-timecontrol-message-avatar"></div>
							<div class="bx-timeman-timecontrol-message-box">
								<div class="bx-timeman-timecontrol-message-marta-1">
									${BX.message('JS_CORE_TC_MESSAGE_LINE_1').replace('#TIME#', '<b>'+options.absenceTime+'</b> <span class="bx-timeman-timecontrol-time-help">?</span>')}
								</div>
								<div class="bx-timeman-timecontrol-message-marta-2">
									${BX.message('JS_CORE_TC_MESSAGE_LINE_2')}
								</div>
							</div>
						</div>
						<div class="bx-timeman-timecontrol-message bx-timeman-timecontrol-message-user">
							<div class="bx-timeman-timecontrol-message-avatar" style="${options.userAvatar}"></div>
							<div class="bx-timeman-timecontrol-message-box">
								<div class="bx-timeman-timecontrol-message-user-type-selector">
									<div class="bx-timeman-timecontrol-selector ${options.absenceType == 'work'? 'bx-timeman-timecontrol-selector-selected': ''}">
										<div class="bx-timeman-timecontrol-selector-radio">
											<input class="bx-timeman-timecontrol-selector-radio-input bx-timeman-timecontrol-type-work" name="timeman-timecontrol-type" type="radio" value="work" tabidex="1000" ${options.absenceType == 'work'? 'checked="true"': ''}>
										</div>
										<div class="bx-timeman-timecontrol-selector-radio-text">${BX.message('JS_CORE_TC_ABSENCE_WORK_'+(options.userGender))}</div>
									</div>
									<div class="bx-timeman-timecontrol-selector ${options.absenceType == 'private'? 'bx-timeman-timecontrol-selector-selected': ''}">
										<div class="bx-timeman-timecontrol-selector-radio">
											<input class="bx-timeman-timecontrol-selector-radio-input bx-timeman-timecontrol-type-private" name="timeman-timecontrol-type" type="radio" value="private" ${options.absenceType == 'private'? 'checked="true"': ''}>
										</div>
										<div class="bx-timeman-timecontrol-selector-radio-text">${BX.message('JS_CORE_TC_ABSENCE_PRIVATE_'+(options.userGender))}</div>
									</div>
								</div>
								<div class="bx-timeman-timecontrol-message-user-comment">
									<textarea class="bx-timeman-timecontrol-message-user-comment-textarea" placeholder="${BX.message('JS_CORE_TC_TEXTAREA_HELP')}" tabidex="1002">${options.absenceReason}</textarea>
								</div>
								</div>
								<div class="bx-timeman-timecontrol-message-user-commit">
									<div class="bx-timeman-timecontrol-message-user-commit-button ${options.absenceReason || options.absenceType == 'private'? '': 'bx-timeman-timecontrol-message-user-commit-button-disabled'}">
										<button class="bx-timeman-timecontrol-button-input bx-timeman-timecontrol-message-user-commit-button-input" tabidex="1003">${BX.message('JS_CORE_TC_SEND_FORM')} (${BX.browser.IsMac()? "&#8984;+Enter": "Ctrl+Enter"})</button>
									</div>
									<div class="bx-timeman-timecontrol-message-user-commit-checkbox">
										<div class="bx-timeman-timecontrol-checkbox">
											<div class="bx-timeman-timecontrol-checkbox-input ${options.calendarCheckboxDefault? 'bx-timeman-timecontrol-checkbox-input-checked': ''}" tabidex="1004">
												<input class="bx-timeman-timecontrol-checkbox-input-input bx-timeman-timecontrol-checkbox-input-calendar" name="timeman-timecontrol-calendar" type="checkbox" value="Y" ${options.calendarCheckboxDefault? 'checked="Y"' :''}>
											</div>
											<div class="bx-timeman-timecontrol-checkbox-text">${BX.message('JS_CORE_TC_SAVE_TO_CALENDAR')}</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>`;

			return BX.create('div', {html: template});
		}
	};

	BX.TimeControl = TimeControl;
})();