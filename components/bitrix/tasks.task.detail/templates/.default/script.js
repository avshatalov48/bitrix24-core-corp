var delegatePopup, delegateUser;

var tasksTaskDetailDefaultTemplateInit = function() {

	BX.bind(BX("task-comments-switcher", true), "click", ToggleSwitcher);
	BX.bind(BX("task-log-switcher", true), "click", ToggleSwitcher);
	BX.bind(BX("task-time-switcher", true), "click", ToggleSwitcher);

	if (BX("task-add-elapsed-time", true))
	{
		BX.bind(BX("task-add-elapsed-time", true), "click", AddElaplsedTime);
		BX.bind(BX("task-send-elapsed-time", true), "click", SendElaplsedTime);
		BX.bind(BX("task-cancel-elapsed-time", true), "click", CancelElaplsedTime);
	}

	if (BX("task-group-edit", true))
	{
		BX.bind(BX("task-group-edit", true), "click", tasksDetailsNS.changeTaskGroup);
	}
	if (BX("task-group-add", true))
	{
		BX.bind(BX("task-group-add", true), "click", tasksDetailsNS.changeTaskGroup);
	}
	if (BX("task-group-remove", true))
	{
		BX.bind(BX("task-group-remove", true), "click", tasksDetailsNS.clearGroup);
	}

	if (BX("task-elapsed-time-form"))
	{
		var elapsedInputs = BX("task-elapsed-time-form").getElementsByTagName("input");
		for (var i = 0; i < elapsedInputs.length; i++) {
			BX.bind(elapsedInputs[i], "keypress", function(e) {
				if(!e) e = window.event;
				if (e.keyCode == 13) {
					BX.submit(this.form);
					BX.PreventDefault(e);
				}
			});
		}
	}

	var subTaskBlock = BX('task-detail-subtasks-block');

	if (BX.type.isElementNode(subTaskBlock) && subTaskBlock.style.display === 'none')
	{
		BX.addCustomEvent(
			'onTaskListTaskAdd',
			function(){
				BX('task-detail-subtasks-block').style.display = '';
			}
		);
	}
};

var tasksDetailsNS = {
	taskGroupPopup : null,
	deleteFile : function (fileId, taskId, linkId, oRemoveBtn)
	{
		if ( ! confirm(BX.message("TASKS_DELETE_FILE_CONFIRM")) )
			return (false);

		BX.ajax.post(
			"/bitrix/components/bitrix/tasks.task.detail/ajax.php?action=remove_file",
			{
				fileId : fileId,
				taskId : taskId,
				sessid : BX.message("bitrix_sessid")
			},
			(function(linkId, oRemoveBtn){
				return function(datum){
					try
					{
						if (datum === 'Success')
						{
							BX(linkId).style.textDecoration = 'line-through';
							BX.remove(oRemoveBtn);
						}
					}
					catch (e)
					{
						// do nothing
					}
				} 
			})(linkId, oRemoveBtn)
		);
	},
	loadCommentsComponent : function(targetDomNode, forumId, taskId, 
		PATH_TO_USER_PROFILE, ITEM_DETAIL_COUNT, PATH_TO_FORUM_SMILE,
		SHOW_RATING, RATING_TYPE
	)
	{
		BX.ajax.post(
			tasksDetailPartsNS.detailsAjaxUrl + '&action=render_comments',
			{
				sessid               :  BX.message('bitrix_sessid'),
				forumId              :  forumId,
				taskId               :  taskId,
				PATH_TO_USER_PROFILE :  PATH_TO_USER_PROFILE,
				ITEM_DETAIL_COUNT    :  ITEM_DETAIL_COUNT,
				PATH_TO_FORUM_SMILE  :  PATH_TO_FORUM_SMILE,
				SHOW_RATING          :  SHOW_RATING,
				RATING_TYPE          :  RATING_TYPE,
				NAME_TEMPLATE        :  BX.message('TASKS_CONTEXT_NAME_TEMPLATE'),
				TASK_ID              :  taskId
			},
			function(data)
			{
				targetDomNode.innerHTML = data;
			}
		);
	},
	loadGroupSelectorViaAjax : function(params)
	{
		if (tasksDetailsNS.taskGroupPopup)
		{
			// the following code doesnt work due to some bug in tasksDetailsNS.taskGroupPopup
			/*
			var groupId = BX('task-detail-selected-group-id').value;

			try
			{
				var selected = tasksDetailsNS.taskGroupPopup.selected;
				for(var k = 0; k < selected.length; k++)
				{
					if(selected[k].id.toString() != groupId.toString())
					{
						tasksDetailsNS.taskGroupPopup.deselect(selected[k].id);
					}
				}
			}
			catch(e)
			{
			}
			*/

			if (params && params.onload)
				params.onload();

			return;
		}

		var backupText = BX("task-group-title").text;
		BX("task-group-title").text = BX.message('TASKS_GROUP_LOADING');

		BX.Tasks.lwPopup.__initSelectors([
			{
				requestedObject  : 'socialnetwork.group.selector',
				bindElement      : 'task-group-name',
				callbackOnSelect : function (arGroups, params)
				{
					tasksDetailsNS.onTaskGroupSelect(arGroups, params);
				},
				onLoadedViaAjax : (function(params, backupText){

					return function(jsObjectName)
					{
						var wait = function(delay, timeout)
						{
							if (typeof window[jsObjectName] === 'undefined') // still not initialized :(
							{
								if (timeout > 0)
									window.setTimeout(function() { wait(delay, timeout - delay); }, delay);
							}
							else
							{
								tasksDetailsNS.taskGroupPopup = window[jsObjectName];

								var groupId = BX('task-detail-selected-group-id').value;
								if (groupId > 0)
								{
									tasksDetailsNS.taskGroupPopup.select({
										id    : groupId,
										title : backupText
									});
								}

								if (params && params.onload)
									params.onload();
							}
						}

						// here we wait for group selector initialization :(
						wait(100, 15000);	// every 100ms, not more than 15000ms
					}
				})(params, backupText)
			}
		]);
	},
	changeTaskGroup : function(e)
	{
		if (!e) e = window.event;

		tasksDetailsNS.loadGroupSelectorViaAjax({
			onload : function(){
				tasksDetailsNS.taskGroupPopup.show();
			}
		});

		BX.PreventDefault(e);
	},
	clearGroup : function()
	{
		var groupId = BX('task-detail-selected-group-id').value;

		if(typeof groupId != 'undefined' && groupId.toString().length > 0)
		{
			tasksDetailsNS.loadGroupSelectorViaAjax({
				onload : (function(groupId, deleteIcon){
					return function(){

						BX.addClass(BX('task-group-selected'), 'hidden');
						BX.removeClass(BX('task-group-add'), 'hidden');

						tasksDetailsNS.taskGroupPopup.deselect(groupId);
						tasksDetailsNS.saveGroup(0);
					}
				})(groupId, this)
			});
		}
	},
	saveGroup : function(groupId)
	{
		var data = {
			mode : "group",
			sessid : BX.message("bitrix_sessid"),
			id : detailTaksID,
			path_to_user: BX.message("TASKS_PATH_TO_USER_PROFILE"),
			path_to_task: BX.message("TASKS_PATH_TO_TASK"),
			path_to_user_tasks_task: BX.message("PATH_TO_USER_TASKS_TASK"),
			groupId : groupId
		};

		BX('task-detail-selected-group-id').value = groupId;

		BX.ajax.post(ajaxUrl, data);
	},
	onTaskGroupSelect : function(groups, params)
	{
		try
		{
			if (
				(typeof(params) === 'object')
				&& (typeof(params.onInit) !== 'undefined')
				&& (params.onInit === true)
			)
			{
				return;
			}
		}
		catch (e)
		{
		}

		if (groups[0])
		{
			BX.html(BX('task-group-title'), BX.util.htmlspecialchars(groups[0].title));
			BX('task-detail-selected-group-id').value = groups[0].id;
			BX('task-group-title').href = BX.message('PATH_TO_GROUP').replace('#group_id#', parseInt(groups[0].id))

			BX.removeClass(BX('task-group-selected'), 'hidden');
			BX.addClass(BX('task-group-add'), 'hidden');

			tasksDetailsNS.saveGroup(groups[0].id);
		}
	},

	toggleFavorite: function(taskId, way)
	{
		taskId = parseInt(taskId);
		if(!taskId)
			return;

		way = !!way;

		BX.onCustomEvent(window.document, 'onTaskListTaskToggleFavorite', [{way: way}]);

		BX.ajax({
			url: ajaxUrl,
			method: 'POST',
			dataType: 'json',
			data: {
				mode : "favorite",
				sessid : BX.message("bitrix_sessid"),
				id : taskId,
				add : way ? '1' : '0'
			}
		});
	}
}

function ToggleSwitcher()
{
	if (BX.hasClass(this, "task-switcher-selected"))
		return false;

	var tabs = ["task-log", "task-time", "task-comments"];
	for (var i = 0; i < tabs.length; i++)
	{
		var block = BX(tabs[i] + "-block", true);
		var switcher = BX(tabs[i] + "-switcher", true);

		if (switcher === this)
		{
			BX.addClass(switcher, "task-switcher-selected");
			BX.addClass(block, tabs[i] + "-block-selected");
		}
		else
		{
			BX.removeClass(switcher, "task-switcher-selected");
			if (block)
			{
				BX.removeClass(block, tabs[i] + "-block-selected");
			}
		}
	}

	return false;
}

function GetNumericCase(number, nominative, genitiveCase, prepositional)
{
	number = parseInt(number, 10);
	if (isNaN(number))
		return prepositional;

	if (number < 0)
		number = 0 - number;

	number %= 100;
	if (number >= 5 && number <= 20)
		return prepositional;

	number %= 10;
	if (number == 1)
		return nominative;

	if (number >= 2 && number <= 4)
		return genitiveCase;

	return prepositional;
}

function ChangeTaskUsers(event)
{
	var id = this.id.replace(/-change/, "");
	BX.addClass(this.parentNode.parentNode, "task-detail-info-users-empty");
	BX(id + "-add", true).parentNode.style.display = "block";

	BX.PreventDefault(event);
}

function AddTaskUsers(event)
{
	var id = this.id.replace(/-add/, "");
	BX.removeClass(BX(id + "-change", true).parentNode.parentNode, "task-detail-info-users-empty");
	this.parentNode.style.display = "none";


	BX.PreventDefault(event);
}

/*=====================Templates Popup==========================*/

function ShowTemplatesPopup(bindElement)
{
	var popup = BX("task-popup-templates-popup-content", true);

	BX.PopupWindowManager.create("task-templates-popup" , bindElement, {
		autoHide : true,
		offsetTop : 1,
		//lightShadow : true,
		events : {
			onPopupClose : __onTemplatesPopupClose
		},
		content : popup
	}).show();

	BX.addClass(bindElement, "webform-button-active");

	return false;
}

function __onTemplatesPopupClose()
{
	BX.removeClass(this.bindElement, "webform-button-active");
}

function SetReport(id, flag)
{
	if ((flag && BX.hasClass(BX("task-detail-report-no"), 'selected')) || (!flag && BX.hasClass(BX("task-detail-report-yes"), 'selected')))
	{
		BX.toggleClass(BX("task-detail-report-yes"), 'selected');
		BX.toggleClass(BX("task-detail-report-no"), 'selected');

		var data = {
			mode : "report",
			sessid : BX.message("bitrix_sessid"),
			id : id,
			path_to_user: BX.message("TASKS_PATH_TO_USER_PROFILE"),
			path_to_task: BX.message("TASKS_PATH_TO_TASK"),
			path_to_user_tasks_task: BX.message("PATH_TO_USER_TASKS_TASK"),
			report : flag
		};
		BX.ajax.post(ajaxUrl, data);
	}
}

function SaveTags(tags)
{
	var tagsString = "";
	for (var i = 0, length = tags.length; i < length; i++)
	{
		if (i > 0)
			tagsString += ", ";
		tagsString += tags[i].name
	};

	var data = {
		mode : "tags",
		sessid : BX.message("bitrix_sessid"),
		id : detailTaksID,
		path_to_user: BX.message("TASKS_PATH_TO_USER_PROFILE"),
		path_to_task: BX.message("TASKS_PATH_TO_TASK"),
		path_to_user_tasks_task: BX.message("PATH_TO_USER_TASKS_TASK"),
		tags : tagsString
	};
	BX.ajax.post(ajaxUrl, data);
}

function GoToComment(link, toggle)
{
	if (toggle)
	{
		(BX.proxy(ToggleSwitcher, BX("task-comments-switcher", true)))();
	}
	setTimeout('window.location = "' + link + '"', 10);
}

var lastRow;

function MoveForm(toRow)
{
	var nextRow = BX.findNextSibling(toRow, {tag: "tr"});
	if (nextRow)
	{
		toRow.parentNode.insertBefore(BX("task-elapsed-time-form-row"), nextRow);
	}
	else
	{
		toRow.parentNode.appendChild(BX("task-elapsed-time-form-row"));
	}

	if (lastRow)
	{
		lastRow.style.display = "";
	}
	lastRow = toRow;
	lastRow.style.display = "none";
}

function ShowElapsedForm(id, action, hours, minutes, comment, seconds, date)
{
	var seconds = seconds || 0;

	BX("task-elapsed-time-form").elements["ELAPSED_ID"].value = id;
	BX("task-elapsed-time-form").elements["ACTION"].value = action;
	BX("task-elapsed-time-form").elements["CREATED_DATE"].value = typeof date == 'undefined' ? '' : date;
	BX("task-elapsed-time-form").elements["HOURS"].value = hours;
	BX("task-elapsed-time-form").elements["MINUTES"].value = minutes;
	BX("task-elapsed-time-form").elements["SECONDS"].value = seconds;
	BX("task-elapsed-time-form").elements["COMMENT_TEXT"].value = comment;

	if (seconds > 0)
		BX("task-elapsed-time-form").elements["SECONDS"].style.display = "";

	BX("task-elapsed-time-form-row").style.display = "";
	BX("task-time-comment-column").style.display = ""; // IE7 hack
	BX("task-elapsed-time-form").elements["HOURS"].focus();
	BX("task-elapsed-time-form").elements["HOURS"].select();
}

function AddElaplsedTime()
{
	MoveForm(BX("task-elapsed-time-button-row"));

	ShowElapsedForm("", "elapsed_add", "1", "00", "", "");
}

function EditElapsedTime(id, hours, minutes, comment, seconds, date)
{
	var seconds = seconds || 0;

	MoveForm(BX("elapsed-time-" + id));

	ShowElapsedForm(id, "elapsed_update", hours, minutes, comment, seconds, date)
}

function CancelElaplsedTime()
{
	if (lastRow)
	{
		lastRow.style.display = "";
	}
	BX("task-elapsed-time-form").elements["SECONDS"].style.display = "none";
	BX("task-elapsed-time-form-row").style.display = "none";
	BX("task-time-comment-column").style.display = "none"; // IE7 hack
	BX("task-elapsed-time-button-row").style.display = "";
}

function SendElaplsedTime()
{
	BX.submit(BX("task-elapsed-time-form"));
	BX.unbind(BX("task-send-elapsed-time", true), "click", SendElaplsedTime);
}

function ShowNewTaskMenu(button, id, menu)
{
	BX.PopupMenu.show(
		-1,
		button,
		menu,
		{offsetTop : -2, offsetLeft : -10}
	);

	return false;
}
