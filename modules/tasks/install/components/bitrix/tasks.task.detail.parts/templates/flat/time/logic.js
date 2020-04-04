BX.namespace("Tasks.Component");

(function() {

	if(typeof BX.Tasks.Component.TaskElapsedTime != 'undefined')
	{
		return;
	}

	BX.Tasks.Component.TaskElapsedTime = function(containerId, parameters)
	{
		this.container = BX(containerId);
		if (!this.container)
		{
			return;
		}

		this.parameters = parameters || {};
		this.messages = this.parameters.messages || {};
		this.template = this.parameters.template || "";
		this.nameTemplate = this.parameters.nameTemplate || "";
		this.pathToUserProfile = this.parameters.pathToUserProfile || "";
		this.ajaxPendingResponse = false;

		this.records = {};
		var records = BX.type.isArray(this.parameters.records) ? this.parameters.records : [];

		for (var i = 0; i < records.length; i++)
		{
			var record = records[i];
			var row = BX(record.rowId);

			this.addRecord(
				record.id,
				row,
				record.date,
				record.time,
				record.comment
			);
		}

		this.layout = {
			addLinkRow: BX.findChildByClassName(this.container, "task-time-add-link-row", true),
			addLink: BX.findChildByClassName(this.container, "task-dashed-link", true),
			sendButton: BX.findChildByClassName(this.container, "task-table-edit-ok", true),
			cancelButton: BX.findChildByClassName(this.container, "task-table-edit-remove", true),
			formRow: BX.findChildByClassName(this.container, "task-time-form-row", true),
			lastRow: null
		};

		this.form = {};
		var elements = this.layout.formRow.getElementsByTagName("input");
		for (i = 0; i < elements.length; i++)
		{
			var element = elements[i];
			var name = element.getAttribute("name");
			this.form[name] = element;

			BX.bind(element, "keypress", BX.proxy(this.catchEnterKey, this));
		}

		this.query = new BX.Tasks.Util.Query({url: "/bitrix/components/bitrix/tasks.task.list/ajax.php"});
		this.query.bindEvent("executed", BX.proxy(this.onQueryExecuted, this));

		BX.bind(this.layout.addLink, "click", BX.proxy(this.add, this));
		BX.bind(this.layout.sendButton, "click", BX.proxy(this.send, this));
		BX.bind(this.layout.cancelButton, "click", BX.proxy(this.cancel, this));

		BX.bind(this.form.date, "click", function() {
			BX.calendar({
				node: this,
				field: this,
				form: "",
				bTime: true,
				value: this.value,
				bHideTime: false
			});
		});

		BX.bind(this.container, "click", BX.proxy(this.captureClick, this));
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.add = function()
	{
		this.moveForm(this.layout.addLinkRow);
		this.setForm("", "", "1", "00", "00", "");
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.edit = function(id)
	{
		var record = this.getRecordById(id);
		if (record)
		{
			this.moveForm(record.row);
			this.setForm(record.id, record.date, record.hours, record.minutes, record.seconds, record.comment);
		}
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.getRecordById = function(id)
	{
		if (this.records[id])
		{
			return this.records[id];
		}

		return null;
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.getRecordByRow = function(row)
	{
		for (var recordId in this.records)
		{
			if (this.records.hasOwnProperty(recordId) && this.records[recordId].row === row)
			{
				return this.records[recordId];
			}
		}

		return null;
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.addRecord = function(id, row, date, time, comment)
	{
		if (!id || !BX.type.isDomNode(row))
		{
			return;
		}

		time = parseInt(time, 10);
		time = BX.type.isNumber(time) ? time : 0;
		var parsedTime = this.getTime(time);
		this.records[id] = {
			row: row,
			id: id,
			date: date,
			hours: parsedTime.hours,
			minutes: parsedTime.minutes,
			seconds: parsedTime.seconds,
			time: time,
			comment: comment
		};
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.deleteRecord = function(id)
	{
		delete this.records[id];
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.delete = function(id)
	{
		if (this.ajaxPendingResponse)
		{
			return;
		}

		this.query.deleteAll();
		this.query.add("task.elapsedtime.delete", { id: id }, { code: "task.elapsedtime.delete" });
		this.query.execute();
		this.ajaxPendingResponse = true;
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.send = function()
	{
		if (this.ajaxPendingResponse)
		{
			return;
		}

		var id = parseInt(this.form.id.value, 10);
		var hours = parseInt(this.form.hours.value, 10);
		var minutes = parseInt(this.form.minutes.value, 10);
		var seconds = parseInt(this.form.seconds.value, 10);
		hours = BX.type.isNumber(hours) ? hours : 0;
		minutes = BX.type.isNumber(minutes) ? minutes : 0;

		var data = {
			COMMENT_TEXT: BX.util.trim(this.form.comment.value),
			CREATED_DATE: this.form.date.value,
			SECONDS: hours * 3600 + minutes * 60 + seconds
		};

		this.enable(false);

		this.query.deleteAll();
		if (id > 0)
		{
			this.query.add(
				"task.elapsedtime.update",
				{
					id: id,
					data: data,
					parameters: {
						'RETURN_ENTITY': true
					}
				},
				{
					code: "task.elapsedtime.update"
				}
			);
		}
		else
		{
			data.TASK_ID = this.parameters.taskId;
			this.query.add(
				"task.elapsedtime.add",
				{
					data: data,
					parameters: {
						'RETURN_ENTITY': true
					}
				},
				{
					code: "task.elapsedtime.add"
				}
			);
		}

		this.query.execute();
		this.ajaxPendingResponse = true;
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.onQueryExecuted = function(response)
	{
		this.ajaxPendingResponse = false;

		if (!response.success || !response.data)
		{
			return false;
		}

		for (var operation in response.data)
		{
			if (!response.data.hasOwnProperty(operation))
			{
				continue;
			}

			if (!response.data[operation].SUCCESS)
			{
				return false;
			}

			if (operation === "task.elapsedtime.add" || operation === "task.elapsedtime.update")
			{
				this.cancel();
				this.enable();

				var data = response.data[operation]["RESULT"]["DATA"];
				var can = response.data[operation]["RESULT"]["CAN"];

				var rowClass = "";
				if (can["MODIFY"])
				{
					rowClass += " task-time-table-edit";
				}

				if (can["REMOVE"])
				{
					rowClass += " task-time-table-remove";
				}

				var source = data["SOURCE"];
				var sourceNote = "";
				if (source == 2)
				{
					sourceNote = this.messages.sourceManual;
					rowClass += " task-time-table-manually";
				}
				else if (source == 1)
				{
					sourceNote = this.messages.sourceUndefined;
					rowClass += " task-time-table-unknown";
				}

				var userName = BX.formatName({
						NAME: data["USER_NAME"],
						LAST_NAME: data["USER_LAST_NAME"],
						SECOND_NAME: data["USER_SECOND_NAME"],
						LOGIN: data["USER_LOGIN"]
					},
					this.nameTemplate,
					"Y"
				);

				var id = data["ID"];
				var date = BX.formatDate(BX.parseDate(data["CREATED_DATE"]), BX.message("FORMAT_DATETIME"));
				var comment = data["COMMENT_TEXT"];

				var time = data["SECONDS"];
				time = this.getTime(time);
				var pathToUserProfile = this.pathToUserProfile.replace("#user_id#", data["USER_ID"]);

				var template = BX.Tasks.Util.Template.compile(this.template);
				var rows = template.getNode({
					rowId: "",
					rowClass: rowClass,
					sourceNote: sourceNote,
					userName: userName,
					date: date,
					timeFormatted: BX.Tasks.Util.formatTimeAmount(time.time),
					comment: comment,
					pathToUserProfile: pathToUserProfile
				});

				var oldRecord = this.getRecordById(id);
				var target = this.layout.addLinkRow;
				if (oldRecord)
				{
					target = BX.findNextSibling(oldRecord.row);
					BX.remove(oldRecord.row);
				}

				this.addRecord(id, rows[0], date, time.time, comment);
				target.parentNode.insertBefore(rows[0], target);
			}
			else if (operation === "task.elapsedtime.delete")
			{
				var args = response.data[operation]["ARGUMENTS"] || {};
				var record = this.getRecordById(args.id);
				if (record)
				{
					record.row.parentNode.removeChild(record.row);
					this.deleteRecord(record.id);
				}
			}
		}

		var total = this.getSummary();
		BX.onCustomEvent("TaskElapsedTimeUpdated", [total.hours, total.minutes, this.records, total]);
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.getTime = function(time)
	{
		var sign = time >= 0 ? 1 : -1;
		var hours = Math.floor(sign * Math.floor(Math.abs(time) / 3600));
		var minutes = (sign * Math.floor(Math.abs(time) / 60)) % 60;
		return {
			hours: hours,
			minutes: minutes,
			seconds: time - hours*3600 - minutes*60,
			time: time
		}
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.getSummary = function()
	{
		var time = 0;
		for (var id in this.records)
		{
			if (this.records.hasOwnProperty(id))
			{
				time += this.records[id].time;
			}
		}

		return this.getTime(time);
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.showError = function()
	{
		var popup = new BX.PopupWindow("task-elapsed-time-error-popup", null, {
			lightShadow: true,
			overlay: true,
			buttons: [new BX.PopupWindowButton({
				text: BX.message("JS_CORE_WINDOW_CLOSE"),
				className: "",
				events: {
					click: function() {
						BX.reload();
						this.popupWindow.close();
					}
				}
			})]
		});

		var errors = [];
		for (var i = 0; i < arguments.length; i++)
		{
			var argument = arguments[i];
			if (BX.type.isArray(argument))
			{
				errors = BX.util.array_merge(errors, argument);
			}
			else if (BX.type.isString(argument))
			{
				errors.push(argument);
			}
		}

		var popupContent = "";
		for (i = 0; i < errors.length; i++)
		{
			popupContent += (typeof(errors[i].MESSAGE) !== "undefined" ? errors[i].MESSAGE : errors[i]) + "<br>";
		}

		popup.setContent("<div class='task-detail-error-popup' style='width: 300px; margin: 15px; color: red; word-wrap: break-word;'>" + popupContent + "</div>");
		popup.show();
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.cancel = function()
	{
		if (this.layout.lastRow)
		{
			this.layout.lastRow.style.display = "";
		}

		this.layout.formRow.style.display = "none";
		this.layout.addLinkRow.style.display = "";
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.setForm = function(id, date, hours, minutes, seconds, comment)
	{
		this.form.id.value = id;
		this.form.date.value = date;
		this.form.hours.value = hours;
		this.form.minutes.value = minutes;
		this.form.seconds.value = seconds;
		this.form.comment.value = comment;

		this.layout.formRow.style.display = "";
		this.form.hours.focus();
		this.form.hours.select();
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.moveForm = function(toRow)
	{
		var nextRow = BX.findNextSibling(toRow, { tag: "tr" });
		if (nextRow)
		{
			toRow.parentNode.insertBefore(this.layout.formRow, nextRow);
		}
		else
		{
			toRow.parentNode.appendChild(this.layout.formRow);
		}

		if (this.layout.lastRow)
		{
			this.layout.lastRow.style.display = "";
		}

		this.layout.lastRow = toRow;
		this.layout.lastRow.style.display = "none";
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.captureClick = function(event)
	{
		event = event || window.event;
		var target = event.target || event.srcElement;
		if (!BX.type.isDomNode(target))
		{
			return;
		}

		if (BX.hasClass(target, "task-table-edit") || BX.hasClass(target, "task-table-remove"))
		{
			var row = BX.findParent(target, { tagName: "tr" });
			var record = this.getRecordByRow(row);
			record = record || {};

			if (BX.hasClass(target, "task-table-edit"))
			{
				this.edit(record.id);
			}
			else if (BX.hasClass(target, "task-table-remove"))
			{
				this.delete(record.id);
			}

			BX.PreventDefault(event);
		}
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.catchEnterKey = function(event)
	{
		event = event || window.event;
		if (event.keyCode === 13)
		{
			this.send();
			BX.PreventDefault(event);
		}
	};

	BX.Tasks.Component.TaskElapsedTime.prototype.enable = function(flag)
	{
		var disabled = flag === false;
		for (var name in this.form)
		{
			if (this.form.hasOwnProperty(name))
			{
				this.form[name].disabled = disabled;
			}
		}
	};

}).call(this);