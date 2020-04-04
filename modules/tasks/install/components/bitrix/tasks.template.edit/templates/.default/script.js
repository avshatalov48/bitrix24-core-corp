var responsiblePopup, accomplicesPopup, responsiblesPopup, prevTasksPopup, authorPopup;
var arAccomplices = [];
var arResponsibles = [];
var arPrevTasks = [];

var timePicker = BX.Tasks.Util.Widget.extend({
	sys: {
		code: 'timepicker'
	},
	options: {
		value: '',
		inputId: ''
	},
	methods: {
		construct: function()
		{
			this.callConstruct(BX.Tasks.Util.Widget);

			this.bindDelegateControl('display', 'click', BX.delegate(this.openClock, this));

			this.vars.formatDisplay = BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME').replace(BX.message('FORMAT_DATE'), '').replace(':SS', '').replace('/SS', '').trim());
			this.vars.formatValue = BX.date.convertBitrixFormat('HH:MI');

			this.setTime(this.parseTime(this.option('value'))); // 24-hour format of value here!!!!

			BX.bind(BX(this.option('inputId')), 'change', this.passCtx(this.onTimeChange));
		},

		openClock: function()
		{
			// see clock.js for details
			var cbName = 'bxShowClock_'+this.option('inputId');
			if(BX.type.isFunction(window[cbName]))
			{
				window[cbName].call(window);
			}
		},

		onTimeChange: function(node)
		{
			var time = this.parseTime(node.value);
			this.setTime(time);

			this.fireEvent('change', [time]);
		},

		setTime: function(time)
		{
			var ts = 3600*(time.h) + 60*(time.m);

			this.control('display').value = this.dateStampToString(ts, this.vars.formatDisplay);
			this.control('value').value = this.dateStampToString(ts, this.vars.formatValue);
		},

		parseTime: function(value)
		{
			var time = value.toString().trim();
			var h = 0;
			var m = 0;

			// there will be troubles if they will switch places of hour and minute in date format :-|
			// todo: make parseTime() behave current format normally, but beware of passing 24-hour time in construct() then
			var found = time.match(new RegExp('^(\\d{1,2})[^\\d]+(\\d{2})', 'i'));
			if(found)
			{
				h = found[1] ? parseInt(found[1]) : 0;
				m = found[2] ? parseInt(found[2]) : 0;
			}

			found = time.match(new RegExp('(am|pm)', 'i'));
			var hasAmPm = found && found[1];
			var pm = (hasAmPm && found[1].toLowerCase() == 'pm');

			if (!isNaN(h) && !isNaN(m) && (h >= 0 && h <= 23) && (m >= 0 && m <= 59))
			{
				if(hasAmPm)
				{
					if(pm) // pm
					{
						if(h != 12) // 12:00 pm (12) => 12:00 (24), but
						{
							h += 12; // 1:00 pm (12) => 13:00 (24)
						}
					}
					else // am
					{
						if(h == 12)
						{
							h = 0; // 12:00 am (12) => 00:00 (24)
						}
					}

				}

				return {h: h, m: m}
			}

			return false;
		},

		dateStampToString: function(stamp, format)
		{
			return BX.date.format(format, new Date(stamp * 1000), false, true);
		}
	}
});

var taskManagerForm =
{
	init : function(params) {

		this.locks = {};
		this.controls = {};

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

		if(BX.browser.IsChrome() || BX.browser.IsIE11() || BX.browser.IsIE())
		{
			if(BX.type.isNotEmptyString(params.editorId))
			{
				setTimeout(function(){

					var input = BX('task-title');
					var editor = window.BXHtmlEditor.Get(params.editorId);

					if(BX.type.isElementNode(input) && typeof editor != 'undefined' && ('Focus' in editor))
					{
						editor.Focus(false);
						BX.focus(input);
					}

				}, 500);
			}
		}
		else
			BX.focus(BX("task-title"));

		var priorityLinks = document.getElementById("task-priority").getElementsByTagName("a");
		for (var i = 0; i < priorityLinks.length; i++)
			BX.bind(priorityLinks[i], "click", taskManagerForm._changePriority);

		if(BX("task-upload")) // if legacy files are present
		{
			var arFiles = BX("webform-field-upload-list").children;
			for(var i = 0; i < arFiles.length; i++)
			{
				BX.bind(arFiles[i].lastChild.previousSibling, "click", taskManagerForm._deleteFile);
			}

			BX.bind(BX("task-upload"), "change", function()
			{
				var files = [];

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
					var li = BX.create("li", {
						props : {className : "uploading",  id : "file-" + i + '-' + uniqueID},
						children : [
							BX.create("a", {
								props : {href : "", target : "_blank", className : "upload-file-name"},
								text : files[i].fileName,
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
			});
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
				content : BX("PREV_TASKS_selector_content"),
				buttons : [
					new BX.PopupWindowButton({
						text : BX.message("TASKS_SELECT"),
						className : "popup-window-button-accept",
						events : { click : function(e) {
							if(!e) e = window.event;

							var empIDs = [];
							BX.cleanNode(BX("task-previous-tasks-list"));
							for(i = 0; i < arPrevTasks.length; i++)
							{
								if (arPrevTasks[i])
								{
									BX("task-previous-tasks-list").appendChild(BX.create("li", {
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

		BX.bind(BX("task-base-template-link"), "click", function(e) {

			if(!e) e = window.event;

			baseTemplatePopup = BX.PopupWindowManager.create("base-template-employee-popup", this, {
				offsetTop : 1,
				autoHide : true,
				content : BX("BASE_TEMPLATE_selector_content"),
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
			
			BX.addCustomEvent(baseTemplatePopup, "onAfterPopupShow", function(e) {setTimeout("O_BASE_TEMPLATE.searchInput.focus();", 100)});

			baseTemplatePopup.show();

			this.value = "";
			BX.focus(this);

			BX.PreventDefault(e);
		});

		var dateTimeTextboxes = [BX("task-deadline-date"), BX("task-start-date"), BX("task-end-date")];
		for (var i = 0; i < dateTimeTextboxes.length; i++)
		{
			if (dateTimeTextboxes[i])
			{
				BX.bind(dateTimeTextboxes[i].nextSibling, "click", taskManagerForm._clearTextBox);
				BX.bind(dateTimeTextboxes[i], "click", function(e) { taskManagerForm._showCalendarTime(e, this); });
			}
		}

		var dateTextboxes = [BX("task-repeating-interval-start-date"), BX("task-repeating-interval-end-date")];
		for (var i = 0; i < dateTextboxes.length; i++)
		{
			if (dateTextboxes[i])
			{
				BX.bind(dateTextboxes[i].nextSibling, "click", taskManagerForm._clearTextBox);
				BX.bind(dateTextboxes[i], "click", function(e) { taskManagerForm._showCalendarDate(e, this); });
			}
		}

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
			
			if(!taskManagerForm.locks.authorSelector)
			{
				if (!authorPopup || authorPopup.popupContainer.style.display != "block")
				{
					authorPopup = BX.PopupWindowManager.create("author-employee-popup", this, {
						offsetTop : 1,
						autoHide : true,
						content : BX("AUTHOR_selector_content")
					});
		
					BX.addCustomEvent(authorPopup, "onAfterPopupShow", function(e) {setTimeout("O_AUTHOR.searchInput.focus();", 100)});
					authorPopup.show();
					
					this.value = "";
					BX.focus(this);
				}
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

		this.controls.userCreateTemplateCb = BX("task-user-create-checkbox");

		if (this.controls.userCreateTemplateCb)
		{
			BX.bind(this.controls.userCreateTemplateCb, "change", function(){

				// you are not an admin, bye-bye
				if(!(isAdmin || isPortalB24Admin))
					return;

				var form =					document.forms["task-edit-form"];

				// RESPONSIBLE_ID
				var responsibleBlock =		BX('task-responsible-employee-block');
				var responsibleIdInput =	form.querySelector('[name="RESPONSIBLE_ID"]');
				var responsibleFakeInput =	BX('task-responsible-employee');

				if(this.checked)
				{
					// saving previous...
					BX.data(responsibleIdInput, 'previous-value', responsibleIdInput.value);
					BX.data(responsibleFakeInput, 'previous-value', responsibleFakeInput.value);

					// In case of system template in RESPONSIBLE_ID should be "new employee" (-1)
					responsibleIdInput.value = -1;
					responsibleFakeInput.value = BX.message('TASKS_TEMPLATE_RESPONSIBLE_ID_UNDEFINED');

					BX.addClass(form, 'state-user-create-template');
				}
				else
				{
					var previous = '';

					// In case of generic template in RESPONSIBLE_ID should be previously selected responsible
					previous = BX.data(responsibleIdInput, 'previous-value');
					if(BX.type.isString(previous))
						responsibleIdInput.value = previous;
					else if(BX.type.isArray(previous) && previous.length > 0) // core.js temporal fix
						responsibleIdInput.value = previous[0];

					previous = BX.data(responsibleFakeInput, 'previous-value');
					if(BX.type.isString(previous))
						responsibleFakeInput.value = previous;
					else if(BX.type.isArray(previous) && previous.length > 0) // core.js temporal fix
						responsibleFakeInput.value = previous[0];

					BX.removeClass(form, 'state-user-create-template');
				}

				taskManagerForm.resolver.resolve();
			});
		}

		this.resolver = new BX.UIResolver({
			areas: {
				'MULTITASKING': {
					rule: function(){

						var ucCb = taskManagerForm.controls.userCreateTemplateCb;

						// hide when base template chosen or template is for new user
						if(BX.hasClass(document.forms["task-edit-form"], 'state-base-template-choosen') || (ucCb && ucCb.checked))
							return false;

						// hide when creator is not the current user
						if(loggedInUser != document.forms["task-edit-form"].elements["CREATED_BY"].value)
							return false;

						return true;
					},
					toggler: toggleMultitasking
				},
				'BASE_TEMPLATE': {
					rule: function(){

						var ucCb = taskManagerForm.controls.userCreateTemplateCb;

						// hide when template is for new user
						if(ucCb && ucCb.checked)
							return false;

						//document.forms["task-edit-form"].elements["BASE_TEMPLATE_ID"]

						return true;
					},
					toggler: toggleBaseTemplate
				},
				'NEW_USER_TEMPLATE_TYPE': {
					rule: function(){

						// always off if template already exists
						if(templateId != 0)
							return false;

						// hide when base template chosen
						if(BX.hasClass(document.forms["task-edit-form"], 'state-base-template-choosen'))
							return false;

						// if creator is not the current user, visibility depends on if the current user is admin
						if(loggedInUser != document.forms["task-edit-form"].elements["CREATED_BY"].value)
							return isAdmin || isPortalB24Admin;

						return true;
					},
					toggler: toggleNewUserTemplateType
				},
				/*
				'CREATOR': {
					rule: function(){
						return (
							!BX("task-user-create-checkbox").checked
						);
					},
					toggler: toggleCreator
				},
				*/
				'RESPONSIBLE': {
					rule: function(){

						var ucCb = taskManagerForm.controls.userCreateTemplateCb;

						// always off when template is for new user
						if(ucCb && ucCb.checked)
							return false;

						// selector is on for admins
						if(isAdmin || isPortalB24Admin)
							return true;

						// you are not an admin, and you are not creator, so you cant change responsible then
						if(loggedInUser != document.forms["task-edit-form"].elements["CREATED_BY"].value)
							return false;

						return true;
					},
					toggler: toggleResponsible
				}
			}
		});
		this.resolver.resolve();

		this.time = new timePicker({
			scope: BX('template-replication-timepicker'),
			inputId: 'taskReplicationTimeFake',
			value: BX.message('REPLICATE_TIME')
		});
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
		BX.addClass(this.parentNode.parentNode, "webform-field-textbox-empty");
		BX.PreventDefault(e);
	},
	
	_submitForm : function (e)
	{
		if(!e) e = window.event;
		
		if (BX("task-title").value == BX.message("TASKS_DEFAULT_TITLE")) {
			BX("task-title").value = "";
		}

		BX.submit(BX("task-edit-form"));
		BX.PreventDefault(e);
	},
	
	_showCalendar : function(e, bTime, bindElem)
	{
		if(!e) e = window.event;
		var curDate = new Date();

		var curDayMiddleTime = new Date(
			curDate.getFullYear(),
			curDate.getMonth(),
			curDate.getDate(),
			bTime ? 12 : 0, 0, 0
		);

		var nodeId = bindElem.parentNode;

		if (!!bindElem.value)
			var selectedDate = bindElem.value;
		else
			var selectedDate = curDayMiddleTime;

		BX.calendar({
			node: nodeId, 
			form: 'task-edit-form', 
			field: bindElem.name, 
			bTime: bTime, 
			value: selectedDate, 
			bHideTime: !bTime,
			callback: function() {
				BX.removeClass(nodeId.parentNode.parentNode, "webform-field-textbox-empty");
			}
		});
	},
	
	_showCalendarTime : function(e, bindElem)
	{
		this._showCalendar(e, true, bindElem);
	},

	_showCalendarDate : function(e, bindElem)
	{
		this._showCalendar(e, false, bindElem);
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
			if (BX.hasClass(this.parentNode, "saved"))
			{
				BX("task-edit-form").appendChild(BX.create("input", {
					props : {
						type : "hidden",
						name : "FILES_TO_DELETE[]",
						value : this.nextSibling.value
					}
			    }));
			}
			else
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
	document.forms["task-edit-form"].elements["RESPONSIBLE_ID"].value = arUser.id;
	if (arUser.sub && arUser.id != currentUser)
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

	if ( ! (isAdmin || isPortalB24Admin) )
	{

		if (arUser.id != currentUser)
		{
			previousUser = document.forms["task-edit-form"].elements["RESPONSIBLE_ID"].value;
			previousUserName = BX("task-responsible-employee").value;
			
			document.forms["task-edit-form"].elements["RESPONSIBLE_ID"].value = currentUser;
			BX("task-responsible-employee").value = currentUserName;
			
			BX.addClass(document.forms["task-edit-form"].elements["RESPONSIBLE_ID"].parentNode.parentNode, "webform-field-combobox-disabled");
			BX.unbindAll(BX("task-responsible-employee").parentNode);
			BX.unbindAll(BX("task-responsible-employee"));
			BX("duplicate-task").disabled = true;
			BX.addClass(BX("duplicate-task").parentNode, "webform-field-checkbox-option-disabled");
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

	taskManagerForm.resolver.resolve();
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

function onBaseTemplateSelect(arTask)
{
	var empIDs = [];
	BX.cleanNode(BX("task-base-template-list"));
	BX("task-base-template-list").appendChild(BX.create("li", {
		props : {
			className : "task-to-tasks-item"
		},
		children : [
			BX.create("a", {
				props : {
					className : "task-to-tasks-item-name",
					href : BX.message("TASKS_PATH_TO_TEMPLATE").replace("#template_id#", arTask.id).replace("#action#", "view"),
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
							
							onBaseTemplateRemove(tid, this)
						}
					})()
				}
			})
		]
	}));
	document.forms["task-edit-form"].elements["BASE_TEMPLATE_ID"].value = arTask.id;

	BX.addClass(document.forms["task-edit-form"], 'state-base-template-choosen');

	taskManagerForm.resolver.resolve();

	baseTemplatePopup.close();
}

function toggleResponsible(way)
{
	var responsibleBlock =		BX('task-responsible-employee-block');
	var responsibleFakeInput =	BX('task-responsible-employee');

	if(way)
	{
		if(BX.type.isElementNode(responsibleBlock))
			BX.removeClass(responsibleBlock, 'webform-field-combobox-disabled');

		var permanentlyDisabled = BX.data(responsibleFakeInput, 'data-permanently-disabled');

		if(BX.type.isElementNode(responsibleFakeInput) && 'disabled' in responsibleFakeInput && permanentlyDisabled != '1')
			responsibleFakeInput.disabled = false;
	}
	else
	{
		if(BX.type.isElementNode(responsibleBlock))
			BX.addClass(responsibleBlock, 'webform-field-combobox-disabled');

		if(BX.type.isElementNode(responsibleFakeInput) && 'disabled')
			responsibleFakeInput.disabled = true;
	}
}

function toggleMultitasking(way)
{
	var dupTaskCb = 	BX('duplicate-task');
	var employee = 		BX("task-responsible-employee");

	if(BX.type.isElementNode(dupTaskCb) && BX.type.isElementNode(employee))
	{
		if(way)
		{
			BX.removeClass(dupTaskCb.parentNode, "webform-field-checkbox-option-disabled");
			employee.disabled = false;
			dupTaskCb.disabled = false;
		}
		else
		{
			if(BX.type.isElementNode(dupTaskCb) && dupTaskCb.checked == true)
			{
				BX.fireEvent(dupTaskCb, 'click');
			}

			BX.addClass(dupTaskCb.parentNode, "webform-field-checkbox-option-disabled");
			employee.disabled = true;
			dupTaskCb.disabled = true;
		}
	}
}

function toggleBaseTemplate(way)
{
	if(!way)
	{
		var link = BX('task-base-template-list').querySelector('.task-to-tasks-item-delete');

		if(BX.type.isElementNode(link))
		{
			BX.fireEvent(link, 'click');
		}
	}
}

function toggleNewUserTemplateType(way)
{
	var cb = taskManagerForm.controls.userCreateTemplateCb;

	if(BX.type.isElementNode(cb))
	{
		var cbBlock = cb.parentNode;

		if(way)
		{
			cb.disabled = false;
			BX.removeClass(cbBlock, 'webform-field-checkbox-option-disabled');
		}
		else
		{
			//BX.fireEvent(cb, 'change');

			//cb.checked = false;
			cb.disabled = true;
			BX.addClass(cbBlock, 'webform-field-checkbox-option-disabled');
		}
	}
}

function toggleCreator(way)
{
	var block = BX('task-director-employees-block');
	var link = BX('task-author-employee');

	taskManagerForm.locks.authorSelector = !way;

	if(BX.type.isElementNode(block))
	{
		if(way)
		{
			BX.addClass(link, 'webform-field-action-link');
			BX.removeClass(link, 'webform-field-action-link-disabled');
			BX.removeClass(link, 'disabled');
		}
		else
		{
			BX.removeClass(link, 'webform-field-action-link');
			BX.addClass(link, 'webform-field-action-link-disabled');
			BX.addClass(link, 'disabled');
		}
	}
}

function onBaseTemplateRemove(templateId, link)
{
	O_BASE_TEMPLATE.unselect(templateId);
	BX.remove(link.parentNode);

	document.forms["task-edit-form"].elements["BASE_TEMPLATE_ID"].value = "";

	BX.removeClass(document.forms["task-edit-form"], 'state-base-template-choosen');

	taskManagerForm.resolver.resolve();
}


function CopyTask(checkbox)
{
	var responsibleLabel = BX("task-responsible-employee-label", true);
	var employeeBlock = BX("task-responsible-employee-block", true);
	var employeesBlock = BX("task-responsible-employees-block", true);
	var assistantsBlock = BX("task-assistants-block", true);
	var directorBlock = BX("task-director-employees-block", true);

	if (checkbox.checked)
	{
		responsibleLabel.htmlFor = "";
		responsibleLabel.innerHTML = BX.message("TASKS_RESPONSIBLES");
		employeeBlock.style.display = "none";
		employeesBlock.style.display = "block";
		assistantsBlock.style.display = "none";
		directorBlock.style.display = "none";
	}
	else
	{
		responsibleLabel.htmlFor = "task-responsible-employee";
		responsibleLabel.innerHTML = BX.message("TASKS_RESPONSIBLE");
		employeeBlock.style.display = "block";
		employeesBlock.style.display = "none";
		assistantsBlock.style.display = "block";
		directorBlock.style.display = "block";
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
		var input = BX.findNextSibling(BX("task-sonet-group-selector"), {tag: "input", name: "GROUP_ID"});
		if (input)
		{
			BX.adjust(input, {props: {value: groups[0].id}})
		}
		else
		{
			BX("task-sonet-group-selector").parentNode.appendChild(
				BX.create("input", {
					props: {
						name: "GROUP_ID",
						type: "hidden",
						value: groups[0].id
					}
				})
			);
		}
	}
}

function deleteGroup(groupId)
{
	BX.adjust(BX("task-sonet-group-selector"), {
		text: BX.message("TASKS_TASK_GROUP")
	});
	var deleteIcon = BX.findNextSibling(BX("task-sonet-group-selector"), {tag: "span", className: "task-group-delete"});
	if (deleteIcon)
	{
		BX.cleanNode(deleteIcon, true);
	}
	var input = BX.findNextSibling(BX("task-sonet-group-selector"), {tag: "input", name: "GROUP_ID"});
	if (input)
	{
		BX.cleanNode(input, true);
	}
	groupsPopup.deselect(groupId);
}


BX.UIResolver = function(opts)
{
	if(typeof opts == 'undefined' || opts === null)
		opts = {};

	this.vars = {
		areas: typeof opts != 'undefined' && BX.type.isPlainObject(opts.areas) ? opts.areas : {},
		lock: false
	};
}
BX.UIResolver.prototype.resolve = function()
{
	if(this.vars.lock)
		return;

	this.vars.lock = true;

	for(var k in this.vars.areas)
	{
		this.vars.areas[k].toggler.apply(this, [
			this.vars.areas[k].rule.call(this)
		]);
	}

	this.vars.lock = false;
}