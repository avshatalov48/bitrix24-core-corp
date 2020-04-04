BX.TaskQuickInfo = {

	popupSettings : {},
	popup : null,
	task : null,
	layout : {
		taskId : null,
		name : null,
		responsible : null,
		director : null,
		status : null,

		files : null,
		priority : null,
		dateCreated : null,
		dateDeadline : null,
		dateStart : null,
		dateEnd : null,
		dateCompleted : null,
		dateCompletedCaption : null,
		dateStarted : null,
		dateStartedCaption : null,

		details : null
	},

	timeoutId : null,
	bindElement : null,


	show : function(bindElement, task, settings)
	{
		this.task = task;
		this.bindElement = bindElement;
		if (settings && typeof(settings) == "object")
			this.popupSettings = settings;

		if (this.timeoutId)
			clearTimeout(this.timeoutId);
		this.timeoutId = setTimeout(BX.proxy(this._show, this), 1000);
	},

	_show : function()
	{
		if (!this.bindElement)
			return;

		if (this.popup == null)
			this.popup = this.__createPopup();

		this.popup.setBindElement(BX.type.isFunction(this.bindElement) ? this.bindElement() : this.bindElement);
		this.updatePopup(this.task);
		this.popup.show();

		//BX.unbindAll(this.popup.popupContainer);
		BX.bind(this.popup.popupContainer, "mouseover", BX.proxy(this.onPopupMouseOver, this));
		BX.bind(this.popup.popupContainer, "mouseout", BX.proxy(this.onPopupMouseOut, this));
	},

	updatePopup : function(task)
	{
		if (!this.popup)
			return;

		this.layout.taskId.innerHTML = task.id;
		this.layout.name.innerHTML = task.name;

		this.layout.responsible.innerHTML = task.responsible ? task.responsible : "";
		this.layout.responsible.href = this.__getUserProfileLink(task.responsibleId);
		this.layout.director.innerHTML = task.director ? task.director : "";
		this.layout.director.href = this.__getUserProfileLink(task.directorId);

		this.layout.status.className = "task-quick-info-field-value " + "task-quick-info-status-" + task.status;
		this.layout.status.innerHTML = BX.type.isNotEmptyString(task.status) ?
			BX.message("TASKS_STATUS_" + task.status.toUpperCase().replace("-", "_")) : "";

		var files = "";
		if (task.files && BX.type.isArray(task.files))
		{
			for (var i = 0; i < task.files.length; i++)
			{
				var file = task.files[i];
				if (file && file.name && file.url)
				{
					files += '<span class="task-quick-info-files-item"><a href="'
						+ file.url + '" target="_blank" class="task-quick-info-files-name">'
						+ BX.util.htmlspecialchars(file.name)
						+ '</a>';

					if (file.size)
						files += ' <span class="task-quick-info-files-size">(' + file.size + ')</span>';

					files += '</span>';
				}

			}
		}
		this.layout.files.innerHTML = files;
		this.layout.files.parentNode.style.display = files == "" ? "none" : "block";

		this.layout.priority.className = "task-quick-info-field-value " + "task-quick-info-priority-" + (task.priority == 2 ? 'high' : 'low');
		this.layout.priority.innerHTML = typeof(task.priority) != "undefined" ? (task.priority == 2 ? BX.message("TASKS_COMMON_YES") : BX.message("TASKS_COMMON_NO")).toLowerCase() : "";

		this.layout.dateCreated.innerHTML = this.formatDate(task.dateCreated);
		this.layout.dateStart.innerHTML = this.formatDate(task.dateStart);
		this.layout.dateEnd.innerHTML = this.formatDate(task.dateEnd);
		this.layout.dateDeadline.innerHTML = this.formatDate(task.dateDeadline);
		if (task.dateDeadline)
			BX.addClass(this.layout.dateDeadline, "task-quick-info-status-overdue");
		else
			BX.removeClass(this.layout.dateDeadline, "task-quick-info-status-overdue");

		if (task.dateStarted)
		{
			this.layout.dateStarted.innerHTML = this.formatDate(task.dateStarted);
			this.layout.dateStartedCaption.style.display = "block";
			this.layout.dateStarted.style.display = "block";
		}
		else
		{
			this.layout.dateStartedCaption.style.display = "none";
			this.layout.dateStarted.style.display = "none";
			BX.cleanNode(this.layout.dateStarted);
		}

		if (task.dateCompleted)
		{
			this.layout.dateCompleted.innerHTML = this.formatDate(task.dateCompleted);
			this.layout.dateCompletedCaption.style.display = "block";
			this.layout.dateCompleted.style.display = "block";
		}
		else
		{
			this.layout.dateCompletedCaption.style.display = "none";
			this.layout.dateCompleted.style.display = "none";
			BX.cleanNode(this.layout.dateCompleted);
		}

		this.layout.details.href = BX.type.isNotEmptyString(task.url) ? task.url : "";
	},

	__getUserProfileLink : function(userId)
	{
		if (this.popupSettings.userProfileUrl && BX.type.isNumber(userId) && userId > 0)
			return this.popupSettings.userProfileUrl.replace(/#user_id#/ig, userId);
		else
			return "";
	},

	__createPopup : function()
	{
		this.popupSettings.lightShadow = this.popupSettings.lightShadow ? this.popupSettings.lightShadow : true;
		this.popupSettings.autoHide = this.popupSettings.lightShadow ? this.popupSettings.lightShadow : true;
		this.popupSettings.angle = this.popupSettings.angle ? this.popupSettings.angle : true;

		this.popupSettings.content = BX.create("div", { props: { className: "task-quick-info" }, children : [
			BX.create("div", { props : { className: "task-quick-info-box task-quick-info-box-title" }, children: [
				BX.create("div", { props : { className: "task-quick-info-title-label"},
					children: [
						BX.create("span", { html: BX.message("TASKS_TASK_TITLE_LABEL") }),
						(this.layout.taskId = BX.create("span")),
						BX.create("span", { html: ':' })
					]}),
				(this.layout.name = BX.create("div", { props : { className: "task-quick-info-title" }}))
			]}),
			BX.create("div", { props : { className: "task-quick-info-box" }, children: [
				BX.create("table", { props : { className: "task-quick-info-layout", cellSpacing: 0 }, children : [
					BX.create("tbody", { children : [
						BX.create("tr", {  children : [
							BX.create("td", { props : { className: "task-quick-info-left-column" }, children : [
								BX.create("span", { props : { className: "task-quick-info-fields" }, children : [
									BX.create("span", { props : { className: "task-quick-info-field-name" }, html : BX.message("TASKS_RESPONSIBLE") + ":" }),
									BX.create("span", { props : { className: "task-quick-info-field-name" }, html : BX.message("TASKS_DIRECTOR") + ":" })
								]}),
								BX.create("span", { props : { className: "task-quick-info-values" }, children : [
									BX.create("span", { props : { className: "task-quick-info-field-value" }, children: [
										(this.layout.responsible = BX.create("a", { props : { className: "task-quick-info-user-link", href: "" }}))
									]}),
									BX.create("span", { props : { className: "task-quick-info-field-value" }, children: [
										(this.layout.director = BX.create("a", { props : { className: "task-quick-info-user-link", href: "" }}))
									]})
								]})
							]}),
							BX.create("td", { props : { className: "task-quick-info-right-column" }, children : [
								BX.create("span", { props : { className: "task-quick-info-fields" }, children : [
									BX.create("span", { props : { className: "task-quick-info-field-name" }, html : BX.message("TASKS_STATUS") + ":" }),
									BX.create("span", { props : { className: "task-quick-info-field-name" }, html : BX.message("TASKS_PRIORITY_V2") + ":" })
								]}),
								BX.create("span", { props : { className: "task-quick-info-values" }, children : [
									(this.layout.status = BX.create("span", { props : { className: "task-quick-info-field-value" }})),
									(this.layout.priority = BX.create("span", { props : { className: "task-quick-info-field-value" }}))
								]})
							]})
						]})
					]})
				]})
			]}),
			BX.create("div", { props : { className: "task-quick-info-box task-quick-info-box-files" }, children: [
				BX.create("div", { props : { className: "task-quick-info-files-label" }, html : BX.message("TASKS_FILES") + ":" }),
				(this.layout.files = BX.create("div", { props : { className: "task-quick-info-files-items" }}))
			]}),
			BX.create("div", { props : { className: "task-quick-info-box" }, children: [
				BX.create("table", { props : { className: "task-quick-info-layout", cellSpacing: 0 }, children : [
					BX.create("tbody", { children : [
						BX.create("tr", {  children : [
							BX.create("td", { props : { className: "task-quick-info-left-column" }, children : [
								BX.create("span", { props : { className: "task-quick-info-fields" }, children : [
									BX.create("span", { props : { className: "task-quick-info-field-name" }, html : BX.message("TASKS_DATE_CREATED") + ":" }),
									BX.create("span", { props : { className: "task-quick-info-field-name" }, html : BX.message("TASKS_DATE_START") + ":" }),
									(this.layout.dateStartedCaption = BX.create("span", { props:{ className:"task-quick-info-field-name" }, html:BX.message("TASKS_DATE_STARTED") + ":" }))
								]}),
								BX.create("span", { props : { className: "task-quick-info-values" }, children : [
									(this.layout.dateCreated = BX.create("span", { props:{ className:"task-quick-info-field-value" }})),
									(this.layout.dateStart = BX.create("span", { props:{ className:"task-quick-info-field-value" }})),
									(this.layout.dateStarted = BX.create("span", { props:{ className:"task-quick-info-field-value" }}))
								]})
							]}),
							BX.create("td", { props : { className: "task-quick-info-right-column" }, children : [
								BX.create("span", { props : { className: "task-quick-info-fields" }, children : [
									BX.create("span", { props: { className:"task-quick-info-field-name" }, html: BX.message("TASKS_DATE_DEADLINE") + ":" }),
									BX.create("span", { props : { className: "task-quick-info-field-name" }, html: BX.message("TASKS_DATE_END") + ":" }),
									(this.layout.dateCompletedCaption = BX.create("span", { props:{ className:"task-quick-info-field-name" }, html:BX.message("TASKS_DATE_COMPLETED") + ":" }))
								]}),
								BX.create("span", { props : { className: "task-quick-info-values" }, children : [
									(this.layout.dateDeadline = BX.create("span", { props:{ className:"task-quick-info-field-value" }})),
									(this.layout.dateEnd = BX.create("span", { props:{ className:"task-quick-info-field-value" }})),
									(this.layout.dateCompleted = BX.create("span", { props:{ className:"task-quick-info-field-value" }}))
								]})
							]})
						]})
					]})
				]})
			]}),
			BX.create("div", { props : { className: "task-quick-info-box-bottom" }, children: [
				(this.layout.details = BX.create("a", {
					props: { className: "task-quick-info-detail-link", href: "" },
					//attrs: { "target" : "_blank" },
					html: BX.message("TASKS_QUICK_INFO_DETAILS"),
					events: {
						click:BX.proxy(this.onDetailClick, this)
					}
				}))
			]})
		]});

		var popup = new BX.PopupWindow("task-quick-info-popup", null, this.popupSettings);
		BX.addCustomEvent(popup, "onPopupClose", BX.proxy(this.onPopupClose, this));
		return popup;
	},

	hide : function()
	{
		if (this.popup && this.popup.isShown())
		{
			if (this.timeoutId)
				clearTimeout(this.timeoutId);
			this.timeoutId = setTimeout(BX.proxy(this._hide, this), 300);
		}
		else
			this._hide();
	},

	_hide : function()
	{
		if (this.timeoutId)
			clearTimeout(this.timeoutId);

		this.bindElement = null;
		if (this.popup)
			this.popup.close();
	},

	onDetailClick : function(event)
	{
		event = event || window.event;
		if (this.popupSettings.onDetailClick && BX.type.isFunction(this.popupSettings.onDetailClick))
		{
			this.popupSettings.onDetailClick(event, this.popup, this);
			BX.PreventDefault(event);
		}
	},

	onPopupClose : function()
	{
		BX.unbindAll(this.popup.popupContainer);
	},

	onPopupMouseOver : function()
	{
		if (this.timeoutId)
			clearTimeout(this.timeoutId);
	},

	onPopupMouseOut : function()
	{
		if (this.timeoutId)
			clearTimeout(this.timeoutId);
		this.timeoutId = setTimeout(BX.proxy(this._hide, this), 300);
	},

	formatDate : function(date)
	{
		if (!date)
			return BX.message("TASKS_QUICK_INFO_EMPTY_DATE");

		var isUTC = this.popupSettings.dateInUTC ? !!this.popupSettings.dateInUTC : false;
		var year = isUTC ? date.getUTCFullYear().toString() : date.getFullYear().toString();
		var month = isUTC ? (date.getUTCMonth()+1).toString() : (date.getMonth()+1).toString();
		var day = isUTC ? date.getUTCDate().toString() : date.getDate().toString();
		var hours = isUTC ? date.getUTCHours() : date.getHours();
		var minutes = isUTC ? date.getUTCMinutes() : date.getMinutes();
		var seconds = isUTC ? date.getUTCSeconds() : date.getSeconds();

		hours = hours.toString();
		var minutes = isUTC ? date.getUTCMinutes().toString() : date.getMinutes().toString();
//	   	var format = (this.popupSettings.dateFormat ? this.popupSettings.dateFormat : BX.message('FORMAT_DATETIME'))
		var format = BX.message('FORMAT_DATETIME')
			.replace(/YYYY/g, "<span class=\"task-quick-info-date-year\">" + year.toString() + "</span>")
			.replace(/MMMM/g, BX.util.str_pad_left(month.toString(), 2, "0"))
			.replace(/MM/g, BX.util.str_pad_left(month.toString(), 2, "0"))
			.replace(/MI/g, BX.util.str_pad_left(minutes.toString(), 2, "0"))
			.replace(/M/g, BX.util.str_pad_left(month.toString(), 2, "0"))
			.replace(/DD/g, BX.util.str_pad_left(day.toString(), 2, "0"))
			.replace(/GG/g, BX.util.str_pad_left(hours.toString(), 2, '0'))
			.replace(/HH/g, BX.util.str_pad_left(hours.toString(), 2, '0'))
			.replace(/SS/g, BX.util.str_pad_left(seconds.toString(), 2, "0"));

		if (BX.isAmPmMode())
		{
			var amPm = 'am';
			if (hours > 12)
			{
				hours = hours - 12;
				amPm = 'pm';
			}
			else if (hours == 12)
			{
				amPm = 'pm';
			}

			format = format.replace(/TT/g, amPm.toUpperCase())
				.replace(/T/g, amPm)
				.replace(/G/g, BX.util.str_pad_left(hours.toString(), 2, '0'))
				.replace(/H/g, BX.util.str_pad_left(hours.toString(), 2, '0'));
		}

//		if ((hours == 0 || (BX.isAmPmMode() && amPm == 'am' && hours == 12)) && minutes == 0)
		return format;
//		else
//	   		return format + "&nbsp;&nbsp;" + BX.util.str_pad_left(hours, 2, "0") + ":" + BX.util.str_pad_left(minutes, 2, "0") + (amPm != undefined ? amPm : '');
	}

};