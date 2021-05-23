var responsiblePopup, accomplicesPopup, responsiblesPopup, prevTasksPopup, authorPopup;
var arAccomplices = [];
var arResponsibles = [];
var arPrevTasks = [];

var tasksEditNS = {
	checkWarningsId : null,
	checkWarnings : function()
	{
		if (this.checkWarningsId)
			window.clearTimeout(this.checkWarningsId);

		this.checkWarningsId = window.setTimeout(
			function()
			{
				if ( ! BX('task-edit-warnings-area') )
					return;

				var groupId = 0;
				var responsibleId = document.forms["task-edit-form"].elements["RESPONSIBLE_ID"].value;

				if (document.forms["task-edit-form"].elements["GROUP_ID"])
					groupId = document.forms["task-edit-form"].elements["GROUP_ID"].value;

				var data = {
					sessid : BX.message('bitrix_sessid'),
					TASK   : {
						RESPONSIBLE_ID : responsibleId,
						GROUP_ID       : groupId
					},
					action : 'getWarnings'
				};

				var oAjaxReply = BX.ajax({
					method   : 'POST',
					dataType : 'html',
					url      : '/bitrix/components/bitrix/tasks.task.edit/ajax.php',
					data     :  data,
					async    :  false
				});

				
				if (oAjaxReply.responseText.length)
				{
					BX('task-edit-warnings-area-message').innerHTML = oAjaxReply.responseText;
					BX('task-edit-warnings-area').style.display = 'block';
				}
				else
				{
					// clear warnings
					BX('task-edit-warnings-area').style.display = 'none';
					BX('task-edit-warnings-area-message').innerHTML = '';
				}
			}
		, 250);
	},
	onFilesChange : function()
	{
		var files = [];
		var filenameShort = '';

		if (this.files && this.files.length > 0) {
			files = this.files;
		} else {
			var filePath = this.value;
			var fileTitle = filePath.replace(/.*\\(.*)/, "$1");
			fileTitle = fileTitle.replace(/.*\/(.*)/, "$1");
			files = [
				{fileName : fileTitle}
			];
		}
		
		var uniqueID;
		
		do
		{
			uniqueID = Math.floor(Math.random() * 99999);
		}
		while(BX("iframe-" + uniqueID));
		
		var list = BX("webform-field-upload-list");
		var items = [];
		for (var i = 0; i < files.length; i++) {
			if (!files[i].fileName && files[i].name) {
				files[i].fileName = files[i].name;
			}

			filenameShort = files[i].fileName;

			if (filenameShort.length >= 95)
				filenameShort = filenameShort.substr(0, 91) + '...';

			var li = BX.create("li", {
				props : {className : "uploading",  id : "file-" + i + '-' + uniqueID},
				children : [
					BX.create("a", { 
						props : {href : "", target : "_blank", className : "upload-file-name", title: files[i].fileName},
						text : filenameShort,
						events : {click : function(e) {
							BX.PreventDefault(e);
						}}
					}),
					BX.create("i", { }),
					BX.create("a", {
						props : {href : "", className : "delete-file"},
						events : {click : function(e) {
							BX.PreventDefault(e);
						}}
					})
				]
			});
			
			list.appendChild(li);
			items.push(li);
		}
		
		var iframeName = "iframe-" + uniqueID;
		var iframe = BX.create("iframe", {
			props : {name : iframeName, id : iframeName},
			style : {display : "none"}
		});
		document.body.appendChild(iframe);

		var originalParent = this.parentNode;
		var form = BX.create("form", {
			props : {
				method : "post",
				action : "/bitrix/components/bitrix/tasks.task.edit/upload.php",
				enctype : "multipart/form-data",
				encoding : "multipart/form-data",
				target : iframeName
			},
			style : {display : "none"},
			children : [
				this,
				BX.create("input", {
					props : {
						type : "hidden",
						name : "sessid",
						value : BX.message("bitrix_sessid")
					}
				}),
				BX.create("input", {
					props : {
						type : "hidden",
						name : "uniqueID",
						value : uniqueID
					}
				}),
				BX.create("input", {
					props : {
						type : "hidden",
						name : "mode",
						value : "upload"
					}
				})
			]
		});
		document.body.appendChild(form);
		BX.submit(form);

		// This is workaround due to changes in main//core.js since main 11.5.9
		// http://jabber.bx/view.php?id=29990
		setTimeout(
			BX.delegate(
				function()
				{
					originalParent.appendChild(this);
					BX.cleanNode(form, true);
				}, 
				this
			),
			15
		);
	}
}


function changeDurationType(type)
{
	bindElement = BX("task-duration-" + type);

	if (!BX.hasClass(bindElement, "selected"))
	{
		BX("task-duration-type").value = type;
		BX.addClass(bindElement, "selected");
		bindElement.parentNode.appendChild(BX("task-duration-" + (type == "days" ? "hours" : "days")));
		BX.toggleClass(BX("task-duration-" + (type == "days" ? "hours" : "days")), "selected");
	}

	fillPlanDates();
}

function fillPlanDates() {
	if ((duration = parseInt(BX("task-duration").value, 10)) && duration > 0)
	{
		if (BX("task-duration-type").value == "days")
			duration *= 24;


		if (BX("task-start-date").value.length > 0)
		{
			//var startDate = BX("task-start-date", true).value.length > 10 ? BX("task-start-date", true).value + ":00" : BX("task-start-date", true).value;
			var startDate = BX("task-start-date", true).value;
			var endDate = new Date(BX.parseDate(startDate).getTime() + duration * 60 * 60 * 1000);
			var value = BX.date.format(BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')), endDate);
			//BX("task-end-date").value = value.substr(11, 8) == "00:00:00" ? value.substr(0, 10) : value.substr(0, 16);
			BX("task-end-date").value = value
				.replace(' 12:00:00 AM', ' ')
				.replace(/ 00:00:00$/, ' ')
				.replace(' 12:00 AM', ' ')
				.replace(/ 00:00$/, ' ');
			BX.removeClass(BX("task-end-date").parentNode.parentNode, "webform-field-textbox-empty");
		}
		else if(BX("task-end-date").value.length > 0)
		{
			//var endDate = BX("task-end-date", true).value.length > 10 ? BX("task-end-date", true).value + ":00" : BX("task-end-date", true).value;
			var endDate = BX("task-end-date", true).value;
			var startDate =  new Date(BX.parseDate(endDate).getTime() - duration * 60 * 60 * 1000);
			var value = BX.date.format(BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')), startDate);
			//BX("task-start-date").value = value.substr(11, 8) == "00:00:00" ? value.substr(0, 10) : value.substr(0, 16);
			BX("task-start-date").value = value
				.replace(' 12:00:00 AM', ' ')
				.replace(/ 00:00:00$/, ' ')
				.replace(' 12:00 AM', ' ')
				.replace(/ 00:00$/, ' ');
			BX.removeClass(BX("task-start-date").parentNode.parentNode, "webform-field-textbox-empty");
		}
	}
}

var taskManagerForm =
{
	funcOnChangeStartOrEndDate : function (field)
	{
		if (field.value.length > 0)
			BX.removeClass(field.parentNode.parentNode, "webform-field-textbox-empty");

		//var startDate = BX("task-start-date", true).value.length > 10 ? BX("task-start-date", true).value + ":00" : BX("task-start-date", true).value;
		var startDate = BX("task-start-date", true).value;
		startDate = BX.parseDate(startDate);
		//var endDate = BX("task-end-date", true).value.length > 10 ? BX("task-end-date", true).value + ":00" : BX("task-end-date", true).value;
		var endDate = BX("task-end-date", true).value;
		endDate = BX.parseDate(endDate);

		if (startDate && endDate)
		{
			var hours = Math.floor((endDate.getTime() - startDate.getTime()) / 1000 / 60 / 60);
			if (hours > 0) {
				if (hours % 24) {
					BX("task-duration").value =	hours;
					BX("task-duration-hours").click();
					changeDurationType("hours");
				} else {
					BX("task-duration").value = hours / 24;
					changeDurationType("days");
				}
			}
		}
	},

	init : function(params)
	{
		//Task title
		BX.bind(BX("task-title"), "focus", function() {
			if (this.value == BX.message("TASKS_DEFAULT_TITLE")) {
				this.value = "";
				BX.removeClass(this, "inactive");
			}
		});

		BX.bind(BX("task-title"), "blur", function() {
			if (this.value == "") {
				this.value = BX.message("TASKS_DEFAULT_TITLE");
				BX.addClass(this, "inactive");
			}
		});

		//if(BX.browser.IsChrome() || BX.browser.IsIE11() || BX.browser.IsIE())
		//{
			if(BX.type.isNotEmptyString(params.editorId))
			{
				setTimeout(function(){

					var input = BX('task-title');
					var editor = window.BXHtmlEditor.Get(params.editorId);

					if(BX.type.isElementNode(input) && typeof editor != 'undefined' && ('Focus' in editor))
					{
						editor.Focus(false);
						input.focus();
					}

				}, 1000);
			}
		//}
		//else
		//	BX.focus(BX("task-title"));

		var priorityLinks = document.getElementById("task-priority").getElementsByTagName("a");
		for (var i = 0; i < priorityLinks.length; i++)
			BX.bind(priorityLinks[i], "click", taskManagerForm._changePriority);

		BX.bind(BX("task-planning-dates-link"), "click", function(e) {

			if(!e) e = window.event;

			var div = BX("task-planning-dates");
			if (div.style.display == "none")
				div.style.display = "block";
			else
				div.style.display = "none";

			BX.PreventDefault(e);
		});
		
		BX.bind(BX("task-duration-hours"), "click", function(e) {

			if(!e) e = window.event;

			changeDurationType("hours");
			
			BX.PreventDefault(e);
		});

		BX.bind(BX("task-duration-days"), "click", function(e) {
			if(!e) e = window.event;

			changeDurationType("days");
			
			BX.PreventDefault(e);
		});
		
		BX.bind(BX("task-duration"), "change", function(e) {
			if(!e) e = window.event;
			
			fillPlanDates();
		});
		
		if (BX("webform-field-upload-list"))
		{
			var arFiles = BX("webform-field-upload-list").children;
			for(var i = 0; i < arFiles.length; i++)
			{
				BX.bind(arFiles[i].lastChild.previousSibling, "click", taskManagerForm._deleteFile);
			}

			BX.bind(BX("task-upload"), "change", tasksEditNS.onFilesChange)
		}

		BX.bind(BX("webform-field-additional-link"), "click", function() {
			BX.toggleClass(this, "selected");
			BX("webform-additional-fields-content").style.display = BX.hasClass(this, "selected") ? "block" : "none";

		});


		BX.bind(BX("task-previous-tasks-link"), "click", function(e) {

			if(!e) e = window.event;
			
			arPrevTasks = O_PREV_TASKS.arSelected;
			
			prevTasksPopup = BX.PopupWindowManager.create("prev-tasks-employee-popup", this, {
				autoHide : true,
				closeByEsc : true,
				content : BX("PREV_TASKS_selector_content"),
				buttons : [
					new BX.PopupWindowButton({
						text : BX.message("TASKS_SELECT"),
						className : "popup-window-button-accept",
						events : {click : function(e) {
							if(!e) e = window.event;

							var empIDs = [];
							var tasksList = BX.create("ol", {
								props : {
									className : "task-to-tasks-list",
									id : "task-previous-tasks-list"
								}
							});
							for(i = 0; i < arPrevTasks.length; i++)
							{
								if (arPrevTasks[i])
								{
									tasksList.appendChild(BX.create("li", {
										props : {
											className : "task-to-tasks-item"
										},
										children : [
											BX.create("a", {
												props : {
													className : "task-to-tasks-item-name",
													href : BX.message("TASKS_PATH_TO_TASK").replace("#task_id#", arPrevTasks[i].id).replace("#action#", "view"),
													title : arPrevTasks[i].name,
													target : "_blank"
												},
												text : arPrevTasks[i].name
											}),
											BX.create("span", {
												props : {
													className : "task-to-tasks-item-delete"
												},
												events : {
													click : (function () {
														var tid = arPrevTasks[i].id;
														return function(e) {
															if(!e) e = window.event;
															
															onPrevTasksUnselect(tid, this)
														}
													})()
												}
											})
										]
									}));
									empIDs.push(arPrevTasks[i].id);
								}
							}
							
							// hack to fix IE9 ordered list zero bug
							setTimeout(function() {
								BX.cleanNode(BX("task-previous-tasks-list-outer"));
								BX("task-previous-tasks-list-outer").appendChild(tasksList);
							}, 0);
							
							document.forms["task-edit-form"].elements["PREV_TASKS_IDS"].value = empIDs.join(",");
							
							this.popupWindow.close();
						}}
					}),

					new BX.PopupWindowButtonLink({
						text : BX.message("TASKS_CANCEL"),
						className : "popup-window-button-link-cancel",
						events : {click : function(e) {
							if(!e) e = window.event;

							this.popupWindow.close();

							BX.PreventDefault(e);
						}}
					})
				]
			});
			
			BX.addCustomEvent(prevTasksPopup, "onAfterPopupShow", function(e) {setTimeout("O_PREV_TASKS.searchInput.focus();", 100)});

			prevTasksPopup.show();

			this.value = "";
			BX.focus(this);

			BX.PreventDefault(e);
		});
		
		BX.bind(BX("task-supertask-link"), "click", function(e) {

			if(!e) e = window.event;

			parentTaskPopup = BX.PopupWindowManager.create("parent-task-employee-popup", this, {
				offsetTop : 1,
				autoHide : true,
				closeByEsc : true,
				content : BX("PARENT_TASK_selector_content"),
				buttons : [
							new BX.PopupWindowButton({
								text : BX.message("TASKS_CLOSE_POPUP"),
								className : "popup-window-button-accept",
								events : {click : function(e) {
									if(!e) e = window.event;

									this.popupWindow.close();
								}}
							})
						]
			});
			
			BX.addCustomEvent(parentTaskPopup, "onAfterPopupShow", function(e) {setTimeout("O_PARENT_TASK.searchInput.focus();", 100)});

			parentTaskPopup.show();
			
			this.value = "";
			BX.focus(this);

			BX.PreventDefault(e);
		});
		
		var dateTextboxes = [BX("task-repeating-interval-start-date"), BX("task-repeating-interval-end-date")];
		for (var i = 0; i < dateTextboxes.length; i++)
		{
			if (dateTextboxes[i])
			{
				BX.bind(dateTextboxes[i].nextSibling, "click", taskManagerForm._clearTextBox);
				BX.bind(dateTextboxes[i], "click", taskManagerForm._showCalendar);
			}
		}

		BX.bind(BX("task-deadline-date").nextSibling, "click", taskManagerForm._clearTextBox);
		BX.bind(
			BX("task-deadline-date"),
			"click",
			function(e){
				if(!e) e = window.event;

				taskManagerForm._showCalendar.call(
					this,
					e,
					function()
					{
						var field = BX("task-deadline-date");
						var defaultTime = BX.CJSTask.ui.extractDefaultTimeFromDataAttribute(field);

						field.value = BX.CJSTask.addTimeToDateTime(field.value, defaultTime);

						if (field.value.length > 0)
							BX.removeClass(field.parentNode.parentNode, "webform-field-textbox-empty");

						tasksRemindersPopUp.setDeadline(field.value);
					}
				);
			}
		);

		BX.bind(BX("task-start-date").nextSibling, "click", taskManagerForm._clearTextBox);
		BX.bind(BX("task-start-date"), "click", taskManagerForm._showCalendarWithCallback);
		BX.bind(BX("task-end-date").nextSibling, "click", taskManagerForm._clearTextBox);
		BX.bind(BX("task-end-date"), "click", taskManagerForm._showCalendarWithCallback);

		if (BX("task-repeating-checkbox"))
		{
			BX.bind(BX("task-repeating-checkbox"), "click", taskManagerForm._enableRepeating);
			
			var repeatLinks = BX("task-repeating-timespan").getElementsByTagName("a");
			for (var i = 0; i < repeatLinks.length; i++)
				BX.bind(repeatLinks[i], "click", taskManagerForm._changeRepeating);

			var repeatDaysLinks = BX("task-repeating-timespan-days").getElementsByTagName("a");
			for (var i = 0; i < repeatDaysLinks.length; i++)
				BX.bind(repeatDaysLinks[i], "click", taskManagerForm._changeRepeatingDay);
			
		}

		BX.bind(BX("task-submit-button"), "click", taskManagerForm._submitForm);
		if (BX("task-submit-back-to-form-button"))
		{
			BX.bind(BX("task-submit-back-to-form-button"), "click", taskManagerForm._submitFormAndBack)
		}
		if (BX("task-submit-and-create-new-when-back-to-form-button"))
		{
			BX.bind(
				BX("task-submit-and-create-new-when-back-to-form-button"), 
				"click", 
				taskManagerForm._submitFormAndBackForCreateNew
				);
		}
		
		BX.bind(BX("task-responsible-employee"), "focus", BX.proxy(ShowResponsibleSelector, BX("task-responsible-employee").parentNode));
		
		BX.bind(BX("task-responsible-employee").parentNode, "click", function(e) {
			if(!e) e = window.event;
			
			BX("task-responsible-employee").focus();
			
			BX.PreventDefault(e);
		});
		
		BX.bind(BX("task-sonet-group-selector"), "click", function(e) {
			if(!e) e = window.event;

			groupsPopup.show();

			BX.PreventDefault(e);
		});
		
		function ShowAuthorSelector(e) {

			if(!e) e = window.event;
			
			if (!authorPopup || authorPopup.popupContainer.style.display != "block")
			{
				authorPopup = BX.PopupWindowManager.create("author-employee-popup", this, {
					offsetTop : 1,
					autoHide : true,
					closeByEsc : true,
					content : BX("AUTHOR_selector_content")
				});
	
				BX.addCustomEvent(authorPopup, "onAfterPopupShow", function(e) {setTimeout("O_AUTHOR.searchInput.focus();", 100)});
				authorPopup.show();
				
				this.value = "";
				BX.focus(this);
			}

			BX.PreventDefault(e);
		}

		if (BX("task-author-employee"))
		{
			BX.bind(BX("task-author-employee"), "click", BX.proxy(ShowAuthorSelector, BX("task-author-employee").parentNode));
		}
		
		BX.bind(BX("task-assistants-link"), "click", function(e) {

			if(!e) e = window.event;
			
			arAccomplices = O_ACCOMPLICES.arSelected;
			
			accomplicesPopup = BX.PopupWindowManager.create("accomplices-employee-popup", this, {
				autoHide : true,
				closeByEsc : true,
				content : BX("ACCOMPLICES_selector_content"),
				buttons : [
					new BX.PopupWindowButton({
						text : BX.message("TASKS_SELECT"),
						className : "popup-window-button-accept",
						events : {click : function(e) {
							if(!e) e = window.event;

							var empIDs = [];
							BX.cleanNode(BX("task-assistants-list"));
							var bindLink = BX("task-assistants-link");
							for(i = 0; i < arAccomplices.length; i++)
							{
								if (arAccomplices[i])
								{
									BX("task-assistants-list").appendChild(BX.create("div", {
										props : {
											className : "task-assistant-item"
										},
										children : [
											BX.create("span", {
												props : {
													className : "task-assistant-link",
													href : BX.message("TASKS_PATH_TO_USER_PROFILE").replace("#user_id#", arAccomplices[i].id),
													target : "_blank",
													title : arAccomplices[i].name
												},
												text : arAccomplices[i].name
											})
										]
									}));
									empIDs.push(arAccomplices[i].id);
								}
							}
							if (empIDs.length > 0)
							{
								if(bindLink.innerHTML.substr(bindLink.innerHTML.length - 1) != ":")
								{
									bindLink.innerHTML = bindLink.innerHTML + ":";
								}
								
							}
							else
							{
								if(bindLink.innerHTML.substr(bindLink.innerHTML.length - 1) == ":")
								{
									bindLink.innerHTML = bindLink.innerHTML.substr(0, bindLink.innerHTML.length - 1);
								}
							}
							document.forms["task-edit-form"].elements["ACCOMPLICES_IDS"].value = empIDs.join(",");

							this.popupWindow.close();
						}}
					}),

					new BX.PopupWindowButtonLink({
						text : BX.message("TASKS_CANCEL"),
						className : "popup-window-button-link-cancel",
						events : {click : function(e) {
							if(!e) e = window.event;

							this.popupWindow.close();

							BX.PreventDefault(e);
						}}
					})
				]
			});
			
			BX.addCustomEvent(accomplicesPopup, "onAfterPopupShow", function(e) {setTimeout("O_ACCOMPLICES.searchInput.focus();", 100)});

			accomplicesPopup.show();

			this.value = "";
			BX.focus(this);

			BX.PreventDefault(e);
		});

		if (BX("task-responsibles-link"))
		{
			BX.bind(BX("task-responsibles-link"), "click", function(e) {

				if(!e) e = window.event;

				arResponsibles = O_RESPONSIBLES.arSelected;
				
				responsiblesPopup = BX.PopupWindowManager.create("responsibles-employee-popup", this, {
					autoHide : true,
					closeByEsc : true,
					content : BX("RESPONSIBLES_selector_content"),
					buttons : [
						new BX.PopupWindowButton({
							text : BX.message("TASKS_SELECT"),
							className : "popup-window-button-accept",
							events : {click : function(e) {
								if(!e) e = window.event;

								var empIDs = [];
								BX.cleanNode(BX("task-responsible-employees-list"));
								for(i = 0; i < arResponsibles.length; i++)
								{
									if (arResponsibles[i])
									{
										BX("task-responsible-employees-list").appendChild(BX.create("div", {
											props : {
												className : "task-responsible-employee-item"
											},
											children : [
												BX.create("a", {
													props : {
														className : "task-responsible-employee-link",
														href : BX.message("TASKS_PATH_TO_USER_PROFILE").replace("#user_id#", arResponsibles[i].id),
														target : "_blank",
														title : arResponsibles[i].name
													},
													text : arResponsibles[i].name
												})
											]
										}));
										empIDs.push(arResponsibles[i].id);
									}
								}
								document.forms["task-edit-form"].elements["RESPONSIBLES_IDS"].value = empIDs.join(",");

								this.popupWindow.close();
							}}
						}),

						new BX.PopupWindowButtonLink({
							text : BX.message("TASKS_CANCEL"),
							className : "popup-window-button-link-cancel",
							events : {click : function(e) {
								if(!e) e = window.event;

								this.popupWindow.close();

								BX.PreventDefault(e);
							}}
						})
					]
				});

				responsiblesPopup.show();

				this.value = "";
				BX.focus(this);

				BX.PreventDefault(e);
			});
		}
	},

	_activateCurrentItem : function(items, currentItem)
	{
		for (var i = 0; i < items.length; i++) {
			if (items[i] == currentItem)
				BX.addClass(items[i], "selected");
			else
				BX.removeClass(items[i], "selected");
		}
	},

	_changePriority : function(e)
	{
		if(!e) e = window.event;

		BX("task-priority-field").value = this.id.substr(this.id.lastIndexOf("-") + 1);
		taskManagerForm._activateCurrentItem(this.parentNode.children, this);
		BX.PreventDefault(e);
	},

	_enableRepeating : function(e)
	{
		if(!e) e = window.event;

		if (this.checked)
			BX.addClass(BX("task-repeating"), "selected");
		else
		{
			BX.removeClass(BX("task-repeating"), "selected");
			return;
		}
		
		var repeatLinks = document.getElementById("task-repeating-timespan").getElementsByTagName("a");
		for (var i = 0; i < repeatLinks.length; i++)
			if (BX.hasClass(repeatLinks[i], "selected"))
				return;

		//enable first timespan
		taskManagerForm._activateCurrentItem(repeatLinks[0].parentNode.children, repeatLinks[0]);
		var repeatingDetails = BX("task-repeating-timespan-details");
		taskManagerForm._activateCurrentItem(repeatingDetails.children[0].children, repeatingDetails.children[0].children[0]);
	},

	_changeRepeating : function(e)
	{
		if(!e) e = window.event;

		if (BX("task-repeating-checkbox").checked)
		{
			BX("task-repeat-period").value = this.id.substr(this.id.lastIndexOf("-") + 1);
			taskManagerForm._activateCurrentItem(this.parentNode.children, this);
			var repeatingDetails = BX("task-repeating-timespan-details");
			taskManagerForm._activateCurrentItem(repeatingDetails.children[0].children, BX.findChild(repeatingDetails.children[0], {tagName: "div", className : this.id}));

			if (this.id == "task-repeating-by-weekly")
			{
				var days = BX("task-repeating-timespan-days").children;
				var isAnyActivate = false;
				for (var i = 0; i < days.length; i++)
				{
					if (BX.hasClass(days[i], "selected"))
					{
						isAnyActivate = true;
						break;
					}
				}

				//enable monday
				if (!isAnyActivate)
					BX.addClass(days[0], "selected");

			}
		}
		BX.PreventDefault(e);
	},

	_changeRepeatingDay : function(e)
	{
		if(!e) e = window.event;
		BX.toggleClass(this, "selected");
		var aSelected = [];
		var repeatDaysLinks = BX("task-repeating-timespan-days").getElementsByTagName("a");
		for (var i = 0; i < repeatDaysLinks.length; i++)
		{
			if (BX.hasClass(repeatDaysLinks[i], "selected"))
			{
				aSelected.push(repeatDaysLinks[i].id.substr(repeatDaysLinks[i].id.lastIndexOf("-") + 1));
			}
		}
		BX("task-week-days").value = aSelected.join(",");
		
		BX.PreventDefault(e);
	},

	_clearTextBox : function(e)
	{
		if(!e) e = window.event;
		this.previousSibling.value="";
		if (this.previousSibling.id == "task-deadline-date") {
			tasksRemindersPopUp.setDeadline(false);
		}
		BX.addClass(this.parentNode.parentNode, "webform-field-textbox-empty");
		BX.PreventDefault(e);
	},
	
	_submitForm : function (e)
	{
		if(!e) e = window.event;
		
		if (BX("task-title").value == BX.message("TASKS_DEFAULT_TITLE")) {
			BX("task-title").value = "";
		}

		if (BX("task-upload"))
			BX("task-upload").disabled = true;

		BX.submit(BX("task-edit-form"));
		BX.unbind(BX("task-submit-button"), "click", taskManagerForm._submitForm);
		BX.PreventDefault(e);
	},
	
	_submitFormAndBack : function (e)
	{
		if(!e) e = window.event;
		
		BX("task-edit-form").elements["apply"].value = "save_and_back";
		
		BX("task-upload").disabled = true;

		taskManagerForm._submitForm();
		BX.unbind(BX("task-submit-back-to-form-button"), "click", taskManagerForm._submitFormAndBack);
		BX.PreventDefault(e);
	},

	_submitFormAndBackForCreateNew : function (e)
	{
		if(!e) e = window.event;
		
		BX("task-edit-form").elements["apply"].value = "save_and_create_new";
		
		taskManagerForm._submitForm(e);
		BX.unbind(BX("task-submit-and-create-new-when-back-to-form-button"), "click", taskManagerForm._submitFormAndBack);
		BX.PreventDefault(e);
	},

	_showCalendar : function(e, callback_after)
	{
		if(!e) e = window.event;

		var fieldName = this.name;
		var bTime = true;
		var formName = 'task-edit-form';

		var params = {
			node: this.parentNode, 
			form: formName, 
			field: fieldName,
			bTime: bTime, 
			value: BX.CJSTask.ui.getInputDateTimeValue(this),
			bHideTime: false
		};

		if (callback_after)
			params.callback_after = callback_after;

		var hz = BX.calendar(params);
	},
	
	_showCalendarWithCallback : function(e)
	{
		if(!e) e = window.event;

		var fieldName = this.name;
		var bTime = true;
		var formName = 'task-edit-form';
		var node = this;

		BX.calendar({
			node: this.parentNode, 
			form: formName, 
			field: fieldName,
			bTime: bTime, 
			value: BX.CJSTask.ui.getInputDateTimeValue(this),
			bHideTime: false,
			callback_after: function(value) { taskManagerForm.funcOnChangeStartOrEndDate(node, value); }
		});
	},
	
	_filesUploaded : function(files, uniqueID)
	{
		for(i = 0; i < files.length; i++)
		{
			var elem = BX("file-" + i + '-' + uniqueID);
			if (files[i].fileID)
			{
				BX.removeClass(elem, "uploading");
				BX.adjust(elem.firstChild, {props : {href : files[i].fileULR}});
				BX.unbindAll(elem.firstChild);
				BX.unbindAll(elem.lastChild);
				BX.bind(elem.lastChild, "click", taskManagerForm._deleteFile);
				elem.appendChild(BX.create("input", {
					props : {
						type : "hidden",
						name : "FILES[]",
						value : files[i].fileID
					}
				}));
			}
			else
			{
				BX.cleanNode(elem, true);
			}
		}
		BX.cleanNode(BX("iframe-" + uniqueID), true);
	},
	
	_deleteFile : function (e)
	{
		if(!e) e = window.event;
		
		if (confirm(BX.message("TASKS_DELETE_CONFIRM"))) {
			if (!BX.hasClass(this.parentNode, "saved"))
			{
				var data = {
					fileID : this.nextSibling.value,
					sessid : BX.message("bitrix_sessid"),
					mode : "delete"
				}
				var url = "/bitrix/components/bitrix/tasks.task.edit/upload.php";
				BX.ajax.post(url, data);
			}
			BX.remove(this.parentNode);

			if (BX.browser.IsIE())
			{
				BX("task-upload-container").innerHTML = '';
				BX("task-upload-container").appendChild(BX.create(
					"input",
					{
						props : {
							multiple: "multiple",
							id : "task-upload",
							size : 1,
							name : "task-attachments[]",
							type : "file"
						},
						events : {
							change : tasksEditNS.onFilesChange
						}
					}
				));
			}
		}

		BX.PreventDefault(e);
	}
}


function ShowResponsibleSelector(e)
{
	if(!e) e = window.event;
	
	if (!responsiblePopup || responsiblePopup.popupContainer.style.display != "block")
	{
		responsiblePopup = BX.PopupWindowManager.create("responsible-employee-popup", this, {
			offsetTop : 1,
			autoHide : true,
			closeByEsc : true,
			content : BX("RESPONSIBLE_selector_content")
		});

		responsiblePopup.show();
		
		BX.addCustomEvent(responsiblePopup, "onPopupClose", onResponsibleClose);

		this.value = "";
		BX.focus(this);
	}

	BX.PreventDefault(e);
}


function onResponsibleSelect(arUser)
{
	// Show warning if responsible person not in selected group
	var input = BX.findNextSibling(
		BX("task-sonet-group-selector"), 
		{tag: "input", name: "GROUP_ID"}
	);

	if (input && (input.value > 0) && (arUser.id > 0))
	{
		var inputWithGroupName = BX.findNextSibling(
				BX("task-sonet-group-selector"), 
				{tag: "input", className: "tasks-notclass-GROUP_NAME"}
			);

		var groupId   = input.value
		var groupName = inputWithGroupName.value;

		tasksEditNS.checkWarnings();
	}

	document.forms["task-edit-form"].elements["RESPONSIBLE_ID"].value = arUser.id;

	if (arUser.id == loggedInUser)
	{
		BX('task-control').parentNode.firstChild.disabled = true;
		BX('task-control').parentNode.firstChild.checked = false;
		BX.addClass(BX('task-control').parentNode, 'webform-field-checkbox-option-disabled');
	}
	else
	{
		BX('task-control').parentNode.firstChild.disabled = false;
		BX('task-control').parentNode.firstChild.checked = BX.message('TASKS_META_RECCOMEND_TASK_CONTROL') === 'Y' ? true : false;
		BX.removeClass(BX('task-control').parentNode, 'webform-field-checkbox-option-disabled');
	}

	if (arUser.id == currentUser)
	{
		BX("add-in-report").parentNode.firstChild.disabled = true;
		BX("add-in-report").parentNode.firstChild.checked = false;
		BX.addClass(BX("add-in-report").parentNode, "webform-field-checkbox-option-disabled");
		if (BX("add-to-timeman"))
		{
			BX("add-to-timeman").parentNode.firstChild.disabled = false;
			BX.removeClass(BX("add-to-timeman").parentNode, "webform-field-checkbox-option-disabled");
		}
	}
	else
	{
		if (arUser.sub)
		{
			BX("add-in-report").parentNode.firstChild.disabled = false;
			BX("add-in-report").parentNode.firstChild.checked = true;
			BX.removeClass(BX("add-in-report").parentNode, "webform-field-checkbox-option-disabled");
		}
		if (BX("add-to-timeman"))
		{
			BX("add-to-timeman").parentNode.firstChild.disabled = true;
			BX("add-to-timeman").parentNode.firstChild.checked = false;
			BX.addClass(BX("add-to-timeman").parentNode, "webform-field-checkbox-option-disabled");
		}
	}
	responsiblePopup.close();
}

function onResponsibleClose()
{
	var emp = O_RESPONSIBLE.arSelected.pop();
	if (emp)
	{
		O_RESPONSIBLE.arSelected.push(emp);
		O_RESPONSIBLE.searchInput.value = emp.name;
	}
}

function onAuthorSelect(arUser)
{
	// Field type may be a "span" or an "A"
	var oTmp = BX.findNextSibling(BX("task-author-employee"), {tagName: "a"});
	if (oTmp == null)
		oTmp = BX.findNextSibling(BX("task-author-employee"), {tagName: "span"});

	BX.remove(oTmp);

	BX("task-author-employee").parentNode.appendChild(BX.create("span", {
		props : {
			className : "task-director-link",
			href : BX.message("TASKS_PATH_TO_USER_PROFILE").replace("#user_id#", arUser.id),
			target : "_blank",
			title : arUser.name
		},
		text : arUser.name
	}));
	
	document.forms["task-edit-form"].elements["CREATED_BY"].value = arUser.id;
	
	if (arUser.id == loggedInUser)
	{
		BX('task-control').parentNode.firstChild.disabled = true;
		BX('task-control').parentNode.firstChild.checked = false;
		BX.addClass(BX('task-control').parentNode, 'webform-field-checkbox-option-disabled');
	}
	else
	{
		BX('task-control').parentNode.firstChild.disabled = false;
		BX('task-control').parentNode.firstChild.checked = BX.message('TASKS_META_RECCOMEND_TASK_CONTROL') === 'Y' ? true : false;
		BX.removeClass(BX('task-control').parentNode, 'webform-field-checkbox-option-disabled');
	}

	if ( ! (isAdmin || isPortalB24Admin) )
	{
		if (arUser.id != currentUser)
		{
			previousUser = document.forms["task-edit-form"].elements["RESPONSIBLE_ID"].value;
			previousUserName = BX("task-responsible-employee").value;
			prevUserSubordinate = ! BX("add-in-report").parentNode.firstChild.disabled;

			document.forms["task-edit-form"].elements["RESPONSIBLE_ID"].value = currentUser;
			BX("task-responsible-employee").value = currentUserName;

			BX.addClass(document.forms["task-edit-form"].elements["RESPONSIBLE_ID"].parentNode.parentNode, "webform-field-combobox-disabled");
			BX.unbindAll(BX("task-responsible-employee").parentNode);
			BX.unbindAll(BX("task-responsible-employee"));
			if (BX("duplicate-task"))
			{
				BX("duplicate-task").disabled = true;
				BX.addClass(BX("duplicate-task").parentNode, "webform-field-checkbox-option-disabled");
			}
			BX("task-responsible-employee").disabled = true;
			BX.bind(BX("task-responsible-employee").parentNode, "click", function(e) {
				if(!e) e = window.event;

				BX.PreventDefault(e);
			});

			if (arUser.sup)
			{
				BX("add-in-report").parentNode.firstChild.disabled = false;
				BX("add-in-report").parentNode.firstChild.checked = true;
				BX.removeClass(BX("add-in-report").parentNode, "webform-field-checkbox-option-disabled");
			}
			else
			{
				BX("add-in-report").parentNode.firstChild.disabled = true;
				BX("add-in-report").parentNode.firstChild.checked = false;
				BX.addClass(BX("add-in-report").parentNode, "webform-field-checkbox-option-disabled");
			}
		}
		else
		{
			if (prevUserSubordinate)
			{
				BX("add-in-report").parentNode.firstChild.disabled = false;
				BX("add-in-report").parentNode.firstChild.checked = true;
				BX.removeClass(BX("add-in-report").parentNode, "webform-field-checkbox-option-disabled");
			}
			else
			{
				BX("add-in-report").parentNode.firstChild.disabled = true;
				BX("add-in-report").parentNode.firstChild.checked = false;
				BX.addClass(BX("add-in-report").parentNode, "webform-field-checkbox-option-disabled");
			}

			document.forms["task-edit-form"].elements["RESPONSIBLE_ID"].value = previousUser;
			BX("task-responsible-employee").value = previousUserName;

			BX("duplicate-task").disabled = false;
			BX.removeClass(BX("duplicate-task").parentNode, "webform-field-checkbox-option-disabled");
			BX("task-responsible-employee").disabled = false;
			BX.removeClass(document.forms["task-edit-form"].elements["RESPONSIBLE_ID"].parentNode.parentNode, "webform-field-combobox-disabled");
			BX.bind(BX("task-responsible-employee"), "focus", BX.proxy(ShowResponsibleSelector, BX("task-responsible-employee").parentNode));
			BX.bind(BX("task-responsible-employee"), "keyup", BX.proxy(O_RESPONSIBLE.search, O_RESPONSIBLE));
			BX.bind(BX("task-responsible-employee"), "focus", BX.proxy(O_RESPONSIBLE._onFocus, O_RESPONSIBLE));
			BX.bind(BX("task-responsible-employee").parentNode, "click", function(e) {
				if(!e) e = window.event;

				BX("task-responsible-employee").focus();

				BX.PreventDefault(e);
			});
		}
	}
	
	authorPopup.close();
}

function onAccomplicesChange(arUsers)
{
	arAccomplices = arUsers;
}

function onPrevTasksChange(arTasks)
{
	arPrevTasks = arTasks;
}

function onPrevTasksUnselect(taskId, link)
{
	O_PREV_TASKS.unselect(taskId, BX("task-unselect-" + taskId));
	BX.remove(link.parentNode);
	
	var empIDs = [];
	for(i = 0; i < O_PREV_TASKS.arSelected.length; i++)
	{
		if (O_PREV_TASKS.arSelected[i])
		{
			empIDs.push(O_PREV_TASKS.arSelected[i].id);
		}
	}
	document.forms["task-edit-form"].elements["PREV_TASKS_IDS"].value = empIDs.join(",");
}

function onResponsiblesChange(arUsers)
{
	arResponsibles = arUsers;
}

function onParentTaskSelect(arTask)
{
	var empIDs = [];
	BX.cleanNode(BX("task-parent-tasks-list"));
	BX("task-parent-tasks-list").appendChild(BX.create("li", {
		props : {
			className : "task-to-tasks-item"
		},
		children : [
			BX.create("a", {
				props : {
					className : "task-to-tasks-item-name",
					href : BX.message("TASKS_PATH_TO_TASK").replace("#task_id#", arTask.id).replace("#action#", "view"),
					title : arTask.name,
					target : "_blank"
				},
				text : arTask.name
			}),
			BX.create("span", {
				props : {
					className : "task-to-tasks-item-delete"
				},
				events : {
					click : (function () {
						var tid = arTask.id;
						return function(e) {
							if(!e) e = window.event;
							
							onParentTasksRemove(tid, this)
						}
					})()
				}
			})
		]
	}));
	document.forms["task-edit-form"].elements["PARENT_ID"].value = arTask.id;
	
	parentTaskPopup.close();
}

function onParentTasksRemove(taskId, link)
{
	O_PARENT_TASK.unselect(taskId);
	BX.remove(link.parentNode);
	
	document.forms["task-edit-form"].elements["PARENT_ID"].value = "";
}


function CopyTask(checkbox)
{
	var responsibleLabel = BX("task-responsible-employee-label", true);
	var employeeBlock = BX("task-responsible-employee-block", true);
	var employeesBlock = BX("task-responsible-employees-block", true);
	var assistantsBlock = BX("task-assistants-block", true);
	var directorBlock = BX("task-director-employees-block", true);
	var isControlTaskAvailable = false;

	if (document.forms["task-edit-form"].elements["RESPONSIBLE_ID"].value == loggedInUser)
		isControlTaskAvailable = false;
	else
		isControlTaskAvailable = true;

	if (checkbox.checked)
	{
		isControlTaskAvailable = true;
		responsibleLabel.htmlFor = "";
		responsibleLabel.innerHTML = BX.message("TASKS_RESPONSIBLES");
		employeeBlock.style.display = "none";
		employeesBlock.style.display = "block";
		assistantsBlock.style.display = "none";

		if ( ! (isAdmin || isPortalB24Admin) )
			directorBlock.style.display = "none";
	}
	else
	{
		responsibleLabel.htmlFor = "task-responsible-employee";
		responsibleLabel.innerHTML = BX.message("TASKS_RESPONSIBLE");
		employeeBlock.style.display = "block";
		employeesBlock.style.display = "none";
		assistantsBlock.style.display = "block";

		if ( ! (isAdmin || isPortalB24Admin) )
			directorBlock.style.display = "block";
	}

	if ( ! isControlTaskAvailable )
	{
		BX('task-control').parentNode.firstChild.disabled = true;
		BX('task-control').parentNode.firstChild.checked = false;
		BX.addClass(BX('task-control').parentNode, 'webform-field-checkbox-option-disabled');
	}
	else
	{
		BX('task-control').parentNode.firstChild.disabled = false;
		BX('task-control').parentNode.firstChild.checked = BX.message('TASKS_META_RECCOMEND_TASK_CONTROL') === 'Y' ? true : false;
		BX.removeClass(BX('task-control').parentNode, 'webform-field-checkbox-option-disabled');
	}
}


function onGroupSelect(groups)
{
	if (groups[0])
	{
		BX.adjust(BX("task-sonet-group-selector"), {
			text: BX.message("TASKS_TASK_GROUP") + ": " + groups[0].title
		});
		var deleteIcon = BX.findNextSibling(BX("task-sonet-group-selector"), {tag: "span", className: "task-group-delete"});
		if (deleteIcon)
		{
			BX.adjust(deleteIcon, {
				events: {
					click: function(e) {
						if (!e) e = window.event;
						deleteGroup(groups[0].id);
					}
				}
			})
		}
		else
		{
			BX("task-sonet-group-selector").parentNode.appendChild(
				BX.create("span", {
					props: {className: "task-group-delete"},
					events: {
						click: function(e)
						{
							if (!e) e = window.event;
							deleteGroup(groups[0].id);
						}
					}
				})
			);
		}
		var input = BX.findNextSibling(BX("task-sonet-group-selector"), {tag: "input", className: "tasks-notclass-GROUP_ID"});
		if (input)
		{
			BX.adjust(input, {props: {value: groups[0].id}})

			var inputWithGroupName = BX.findNextSibling(
				BX("task-sonet-group-selector"),
				{tag: "input", className: "tasks-notclass-GROUP_NAME"}
			);

			BX.adjust(
				inputWithGroupName, 
				{props: {value: groups[0].title}}
			);
		}
		else
		{
			BX("task-sonet-group-selector").parentNode.appendChild(
				BX.create("input", {
					props: {
						name: "GROUP_ID",
						className: 'tasks-notclass-GROUP_ID',
						type: "hidden",
						value: groups[0].id
					}
				})
			);

			BX("task-sonet-group-selector").parentNode.appendChild(
				BX.create("input", {
					props: {
						name: "GROUP_NAME",
						className: 'tasks-notclass-GROUP_NAME',
						type: 'hidden',
						value: groups[0].title
					}
				})
			);
		}

		// Show warning if responsible person not in selected group
		tasksEditNS.checkWarnings();
	}
}


function deleteGroup(groupId)
{
	tasksEditNS.checkWarnings();

	BX.adjust(BX("task-sonet-group-selector"), {
		text: BX.message("TASKS_TASK_GROUP")
	});
	var deleteIcon = BX.findNextSibling(BX("task-sonet-group-selector"), {tag: "span", className: "task-group-delete"});
	if (deleteIcon)
	{
		BX.cleanNode(deleteIcon, true);
	}
	var input = BX.findNextSibling(BX("task-sonet-group-selector"), {tag: "input", className: "tasks-notclass-GROUP_ID"});
	if (input)
	{
		input.value = 0;
	}
	var input = BX.findNextSibling(BX("task-sonet-group-selector"), {tag: "input", className: "tasks-notclass-GROUP_NAME"});
	if (input)
	{
		input.value = '';
	}
	groupsPopup.deselect(groupId);
}

function onCancelClick(e, action)
{
	if (!e) e = window.event;
	
	if (window.top.BX.TasksIFrameInst && window.top.BX.TasksIFrameInst.isOpened())
	{
		if (document.referrer && action == "edit" && document.referrer.indexOf("IFRAME=Y") != -1)
		{
			var isViewMode = true;
			window.top.BX.TasksIFrameInst.isEditMode = !isViewMode;
			window.top.BX.TasksIFrameInst.load(document.referrer, isViewMode);
		}
		else
		{
			window.top.BX.TasksIFrameInst.close();
		}
		BX.PreventDefault(e);
	}
}