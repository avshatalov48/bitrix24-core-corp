(function (window) {
	var __windows = {};

	BX.TaskReminders = {
		create : function(uniquePopupId, bindElement, reminders, deadline, params)
		{
			if (!__windows[uniquePopupId])
				__windows[uniquePopupId] = new TaskReminders(uniquePopupId, bindElement, reminders, deadline, params);
			return __windows[uniquePopupId];
		}
	};

	var TaskReminders = function(uniquePopupId, bindElement, reminders, deadline, params) {
		
		this.calendar = BX.calendar;

		this.unchangedReminders = [];
		this.reminders = [];
		
		this.params = params;
		
		this.restoreReminders = true;
		
		if (this.params && this.params.events)
		{
			for (var eventName in this.params.events)
				BX.addCustomEvent(this, eventName, this.params.events[eventName]);
		}

		for (var i = 0, length = reminders ? reminders.length : 0; i < length; i++)
			this.addReminder(reminders[i]);

		this.newDeadlineReminderTitle = BX.create("span", {
			html: "<strong>" + BX.message("TASKS_ABOUT_DEADLINE") + "</strong> <span class=\"task-reminder-deadline-date\"></span>"
		})

		this.newDeadlineReminderTextbox = BX.create("input", {
			props: {
				className: "task-reminder-day",
				type: "text"
			}
		});

		this.newDeadlineReminderPopup = BX.create("span", {
			props: {className: "task-reminder-list-popup"},
			children: [
				BX.create("a", {
					props: {className: "task-reminder-list"},
					text: BX.message("TASKS_REMIND_VIA_JABBER")
				}),
				BX.create("span", {})
			]
		});
		
		BX.bind(this.newDeadlineReminderPopup.firstChild, "click", BX.proxy(function (e) {
			if (!e) e = window.event;
			
			this.__showTransportMenu(this.newDeadlineReminderPopup, -2);
		}, this));

		this.newDeadlineReminderButton = BX.create("span", {
			props: {className: "task-reminder-popup-add"},
			events: {click : BX.proxy(this.__onAddDeadlineReminderClick, this)}
		});

		var arBefore = BX.message("TASKS_REMIND_BEFORE").split("#NUM#");
		
		this.newDeadlineReminderForm = BX.create("div", {
			props : {className: "task-reminder-toolbar-wrap"},
			children: [
				BX.create("span", {
					props: {className: "task-reminder-left-toolbar"}
				}),
				BX.create("span", {
					props: {className: "task-reminder-cont-toolbar"},
					children: [
						BX.create("span", {text: arBefore[0]}),
						this.newDeadlineReminderTextbox,
						BX.create("span", {text: arBefore[1]}),
						this.newDeadlineReminderPopup,
						this.newDeadlineReminderButton
					]
				}),
				BX.create("span", {
					props: {className: "task-reminder-right-toolbar"}
				})
			]
		});

		this.deadlineRemindersContainer = BX.create("div", {
			props : {className: "task-reminder-block task-reminder-block-deadline"},
			children: [
				this.newDeadlineReminderTitle,
				this.newDeadlineReminderForm
			]
		});

		this.newReminderTitle = BX.create("span", {
			children: [
				BX.create("strong", {
					text: BX.message("TASKS_BY_DATE")
				})
			]
		});

		var attrs = {};
		try
		{
			attrs = {
				'data-default-hour': this.params.defaultTime.hour.toString(),
				'data-default-minute': this.params.defaultTime.minute.toString()
			};
		}
		catch(e)
		{
		}

		this.newReminderTextbox = BX.create("input", {
			props: {
				id: "task-reminder-textbox",
				className: "task-reminder-day",
				type: "text"
			},
			attrs: attrs,
			events: {click : this.__showCalendar}
		});
		this.newReminderTextbox.readOnly = "readonly";

		this.newReminderPopup = BX.create("div", {
			props: {className: "task-reminder-list-popup"},
			children: [
				BX.create("a", {
					props: {className: "task-reminder-list"},
					text: BX.message("TASKS_REMIND_VIA_JABBER")
				}),
				BX.create("span", {})
			]
		});
		
		BX.bind(this.newReminderPopup.firstChild, "click",  BX.proxy(function (e) {
			if (!e) e = window.event;
			
			this.__showTransportMenu(this.newReminderPopup, -3);
		}, this));

		this.newReminderButton = BX.create("span", {
			props: {className: "task-reminder-popup-add"},
			events: {click : BX.proxy(this.__onAddReminderClick, this)}
		});

		this.newReminderForm = BX.create("div", {
			props : {className: "task-reminder-toolbar-wrap"},
			children: [
				BX.create("span", {
					props: {className: "task-reminder-left-toolbar"}
				}),
				BX.create("span", {
					props: {className: "task-reminder-cont-toolbar"},
					children: [
						this.newReminderTextbox,
						this.newReminderPopup,
						this.newReminderButton
					]
				}),
				BX.create("span", {
					props: {className: "task-reminder-right-toolbar"}
				})
			]
		});

		this.remindersContainer = BX.create("div", {
			props : {className: "task-reminder-block task-reminder-block-arbitrarily"},
			children: [
				this.newReminderTitle,
				this.newReminderForm
			]
		});

		this.content = BX.create("div", {
			props : {
				className: "task-reminder-popup",
				id: "task-reminder-popup-content"
			},
			children : [
				BX.create("span", {
					props: {className: "task-reminder-title"},
					text: BX.message("TASKS_REMINDER_TITLE")
				}),
				BX.create("div", {
					props : {className: "popup-window-hr"},
					children: [BX.create("i", {})]
				}),
				BX.create("div", {
					props : {className: "task-reminder-block-scrol"},
					children: [
						this.deadlineRemindersContainer,
						this.remindersContainer
					]
				})
			]
		});

		this.popupWindow = new BX.PopupWindow(uniquePopupId, bindElement, {
			content: "",
			closeIcon: {right: "22px", top: "10px"},
			offsetTop: 2,
			offsetLeft: -15,
			buttons: [
				new BX.PopupWindowButton({
					text : BX.message("TASKS_REMINDER_OK"),
					className : "popup-window-button-accept",
					events : {
						click : BX.proxy(this.__onOKButtonClick, this)
					}
				}),
				new BX.PopupWindowButtonLink({
					text : BX.message("TASKS_CANCEL"),
					className : "popup-window-button-link-cancel",
					events : {
						click : BX.proxy(this.__onCancelButtonClick, this)
					}
				})
			],
			events : {
				onPopupFirstShow : BX.proxy(
					function(popupWindow) {
						popupWindow.setContent(this.content);
					},
					this
				),

				onPopupShow : BX.proxy(
					function(popupWindow) {
						this.restoreReminders = true;
						this.unchangedReminders = [];
						for(var i = 0; i < this.reminders.length; i++)
							this.unchangedReminders.push(this.reminders[i]);

						this.redraw();
					},
					this
				),
				
				onPopupClose : BX.proxy(
					function(popupWindow) {
						if (this.restoreReminders)
						{
							this.reminders = [];
							for(var i = 0; i < this.unchangedReminders.length; i++)
								this.reminders.push(this.unchangedReminders[i]);

							BX.onCustomEvent(this, "onRemindersChange", [this.reminders]);
						}
						else
						{
							BX.onCustomEvent(this, "onRemindersSave", [this.reminders]);
						}
					},
					this
				)
			}
		});
		
		this.setDeadline(deadline ? BX.parseDate(deadline) : false);
	}

	TaskReminders.prototype.addReminder = function(reminder) {
		var tmpReminder = reminder;
		tmpReminder.date = typeof(tmpReminder.date) == "object" ? tmpReminder.date : BX.parseDate(tmpReminder.date);
		
		this.reminders.push(tmpReminder);
		
		BX.onCustomEvent(this, "onReminderAdd", [tmpReminder]);
		BX.onCustomEvent(this, "onRemindersChange", [this.reminders]);

		return tmpReminder;
	}

	TaskReminders.prototype.removeReminder = function(num) {
		
		var reminder = this.reminders[num];
		
		this.reminders.splice(num, 1);
		
		BX.onCustomEvent(this, "onReminderDelete", [num, reminder]);
		BX.onCustomEvent(this, "onRemindersChange", [this.reminders]);
	}

	TaskReminders.prototype.show = function() {
		this.popupWindow.show();
	}

	TaskReminders.prototype.close = function() {
		this.popupWindow.close();
	}
	
	TaskReminders.prototype.setDeadline = function(deadline) {
		if (!deadline) {
			this.deadline = null;
			BX.adjust(this.deadlineRemindersContainer, {
				style: {
					display: "none"
				}
			})
		} else {
			var tmpDeadline = typeof(deadline) == "object" ? deadline : BX.parseDate(deadline);

			if (tmpDeadline !== null) {
				this.deadline = tmpDeadline;
				var deadlineSpan = BX.findChild(this.newDeadlineReminderTitle, {tag: "span", className: "task-reminder-deadline-date"}, false);
				BX.adjust(deadlineSpan, {
					text: "(" + BX.date.format(BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')), this.deadline) + ")"
//					text: "(" + this.calendar.FormatDate(this.deadline, phpVars.FORMAT_DATE) + ")"
				});
				BX.adjust(this.deadlineRemindersContainer, {
					style: {
						display: "block"
					}
				})
			}
		}
		
		this.redraw();
		
		return this.deadline;
	}
	
	TaskReminders.prototype.redraw = function() {
		BX.cleanNode(this.deadlineRemindersContainer);
		BX.adjust(this.deadlineRemindersContainer , {
			children: [
				this.newDeadlineReminderTitle,
				this.newDeadlineReminderForm
			]
		});
		BX.cleanNode(this.remindersContainer);
		BX.adjust(this.remindersContainer , {
			children: [
				this.newReminderTitle,
				this.newReminderForm
			]
		});

		this.sortReminders();
		for(var i = 0; i < this.reminders.length; i++) {
			var reminderNode = BX.create("div" , {
				props : {className: "task-reminder-date-block-wrap"},
				children: [
					BX.create("span", {
						props: {className: "task-reminder-date-left"}
					}),
					BX.create("span", {
						props: {className: "task-reminder-date-cont"},
						children: [
							BX.create("span", {
								text: BX.date.format(BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')), this.reminders[i].date) +
									" " + (this.reminders[i].transport == "J" ? BX.message("TASKS_REMIND_VIA_JABBER") : BX.message("TASKS_REMIND_VIA_EMAIL"))
//								text: this.calendar.FormatDate(this.reminders[i].date, phpVars.FORMAT_DATE) + " " + (this.reminders[i].transport == "J" ? BX.message("TASKS_REMIND_VIA_JABBER") : BX.message("TASKS_REMIND_VIA_EMAIL"))
							}),
							BX.create("span", {
								props: {className: "task-reminder-remove"},
								events: {
									click: BX.proxy((function() {
										var num = i;
										return function() {
											this.removeReminder(num);
											this.redraw();
										}
									})(), this)
								}
							})
						]
					}),
					BX.create("span", {
						props: {className: "task-reminder-date-right"}
					})
				]
			});

			if (this.reminders[i].type == "D" && this.deadline) {
				this.deadlineRemindersContainer.insertBefore(reminderNode, this.newDeadlineReminderForm);
			} else {
				this.remindersContainer.insertBefore(reminderNode, this.newReminderForm);
			}
		}
	}
	
	TaskReminders.prototype.sortReminders = function() {
		this.reminders.sort(this.__sortReminders);
	};
	
	TaskReminders.prototype.__showCalendar = function(e) {
		if(!e) e = window.event;

		var node = BX("task-reminder-textbox");
		var cTime = BX.CJSTask.ui.getInputDateTimeValue(node);

		BX.calendar({
			node: node, 
			field: "task-reminder-textbox", 
			form: '', 
			bTime: true, 
			value: cTime,
			bHideTimebar: false
		});
	}

	TaskReminders.prototype.__sortReminders = function(a, b) {
		if (a.date < b.date)
			return -1;
		else if (a.date > b.date)
			return 1;

		return 0;
	};

	TaskReminders.prototype.__onAddDeadlineReminderClick = function(e) {
		if (!e) e = window.event;
		
		this.__addDeadlineReminder();
	}

	
	TaskReminders.prototype.__onAddReminderClick = function(e) {
		if (!e) e = window.event;
		
		this.__addReminder();
	}
	
	TaskReminders.prototype.__onOKButtonClick = function(e) {
		if (!e) e = window.event;
		
		this.restoreReminders = false;

		if (this.newReminderTextbox.value != "") {
			this.__addReminder();
		}
		
		if (this.newDeadlineReminderTextbox.value != "") {
			this.__addDeadlineReminder();
		}

		this.popupWindow.close()
	}
	
	TaskReminders.prototype.__addReminder = function () {
		var reminderDate = BX.parseDate(this.newReminderTextbox.value);
		if (reminderDate !== null)
		{
			var tmpCurDate = new Date();
			var tmpCurDayStart = new Date(
				tmpCurDate.getFullYear(), 
				tmpCurDate.getMonth(), 
				tmpCurDate.getDate(), 
				0, 
				0, 
				0, 
				0
			);

			if (reminderDate === null || reminderDate < tmpCurDayStart)
			{
				alert(BX.message('TASKS_DATE_MUST_BE_IN_FUTURE'));
				return null;
			}

			this.addReminder({date:reminderDate, transport: (this.newReminderPopup.firstChild.innerHTML == BX.message("TASKS_REMIND_VIA_EMAIL") ? "E" : "J"), type: "A"});
			this.redraw();
			this.newReminderTextbox.value = "";
			this.newReminderTextbox.focus();
		}
	}
	
	TaskReminders.prototype.__addDeadlineReminder = function () {
		var days = parseInt(this.newDeadlineReminderTextbox.value, 10);
		if (isNaN(days))
		{
			days = 0;
		}
		var time = this.deadline.getTime() - days * 24 * 60 * 60 * 1000;
		var date = new Date(time);

		if (!isNaN(date.getTime())) {
			this.addReminder({date: date, transport: (this.newDeadlineReminderPopup.firstChild.innerHTML == BX.message("TASKS_REMIND_VIA_EMAIL") ? "E" : "J"), type: "D"});
			this.redraw();
			this.newDeadlineReminderTextbox.value = "";
			this.newDeadlineReminderTextbox.focus();
		}
	}
	
	TaskReminders.prototype.__onCancelButtonClick = function(e) {
		if (!e) e = window.event;
		
		this.newReminderTextbox.value = "";
		this.newDeadlineReminderTextbox.value = "";
		
		this.popupWindow.close()
	}
	
	TaskReminders.prototype.__showTransportMenu = function(button, taskId) {
		var menu = [
			{
				text: BX.message("TASKS_REMIND_VIA_JABBER"),
				title: BX.message("TASKS_REMIND_VIA_JABBER_EX"),
				className: "menu-popup-no-icon",
				onclick: function() {
					BX.adjust(this.bindElement.firstChild, {html : BX.message("TASKS_REMIND_VIA_JABBER")});
					this.popupWindow.close();
				}
			},
			{
				text: BX.message("TASKS_REMIND_VIA_EMAIL"),
				title: BX.message("TASKS_REMIND_VIA_EMAIL_EX"),
				className: "menu-popup-no-icon",
				onclick: function() {
					BX.adjust(this.bindElement.firstChild, {html : BX.message("TASKS_REMIND_VIA_EMAIL")});
					this.popupWindow.close();
				}
			}
		];

		BX.PopupMenu.show(taskId, button, menu, {offsetTop : -5});
	}
})(window);