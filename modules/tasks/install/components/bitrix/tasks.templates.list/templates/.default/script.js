var tasksMenuPopup = {};
var quickInfoData = {};
var loadedTasks = {};
var preOrder = {};

function DeleteTemplate(templateId)
{
	DeleteTask(templateId, {'mode': 'delete-subtree'});
}

/*=====================Menu Popup===============================*/

function ShowMenuPopup(taskId, bindElement)
{
	if (tasksMenuPopup[taskId])
	{
		BX.PopupMenu.show(taskId, bindElement, tasksMenuPopup[taskId], { events : { onPopupClose: __onMenuPopupClose} });
	}

	BX.addClass(bindElement, "task-menu-button-selected");

	return false;
}

function __onMenuPopupClose()
{
	BX.removeClass(this.bindElement, "task-menu-button-selected");
}

function ShowMenuPopupContext(taskId, event)
{
	var target = event.target || event.srcElement;
	if (target && target.tagName.toUpperCase() == "A")
	{
		return true;
	}

	if (tasksMenuPopup[taskId])
	{
		BX.PopupMenu.show("c" + taskId, event, tasksMenuPopup[taskId], { });
		BX.PopupMenu.getCurrentMenu().popupWindow.setBindElement(event);
		BX.PopupMenu.getCurrentMenu().popupWindow.adjustPosition();
	}

	BX.PreventDefault(event);
}

function SwitchTaskFilter(link)
{
	if (BX.hasClass(link, "task-filter-mode-selected"))
		return false;
	
	BX.toggleClass(link.parentNode.parentNode.parentNode, "task-filter-advanced-mode");

	var links = link.parentNode.getElementsByTagName("a");
	for (var i = 0; i < links.length; i++)
		BX.toggleClass(links[i], "task-filter-mode-selected");

	return false;
}

function SortTable(url, e)
{
	if(!e) e = window.event;
	window.location = url;
	BX.PreventDefault(e);
}

function ToggleSubtasks(currentRow, depthLevel, taskID)
{
	// delay function run, if tasksListNS not ready
	if (
		( ! tasksListNS )
		|| ( ! tasksListNS.isReady )
	)
	{
		window.setTimeout(
			(function(currentRow, depthLevel, taskID){
				return function(){
					ToggleSubtasks(currentRow, depthLevel, taskID);
				};
			})(currentRow, depthLevel, taskID),
			500
		);

		return;
	}

	if (loadedTasks[taskID])
	{
		var row = BX.findNextSibling(currentRow, {tagName : "tr"});

		while(row && Depth(row) > depthLevel)
		{
			if (BX.hasClass(currentRow, "task-list-item-opened")) //collapse children
			{
				BX.addClass(row, "task-list-item-hidden");
				BX.removeClass(row, "task-list-item-opened");
			}
			else if (BX.hasClass(row, "task-depth-" + (depthLevel + 1))) // expand children
			{
				BX.removeClass(row, "task-list-item-hidden");
			}
			row =  BX.findNextSibling(row, {tagName : "tr"});
		}

		BX.toggleClass(currentRow, "task-list-item-opened");
	}
	else
	{
		var data = {
			sessid : BX.message("bitrix_sessid"),
			id : taskID,
			depth : depthLevel,
			filter: tasksListNS.arFilter,
			order: preOrder,
			columnsOrder : tasksListNS.getColumnsOrder(),
			path_to_user: BX.message("TASKS_PATH_TO_USER_PROFILE"),
			path_to_task: BX.message("TASKS_PATH_TO_TASK"),
			path_to_templates_template: BX.message("TASKS_PATH_TO_TEMPLATES_TEMPLATE"),
			mode : "load"
		};
		loadedTasks[taskID] = true;

		BX.ajax({
			"method": "POST",
			"dataType": "html",
			"url": tasksListAjaxUrl,
			"data":  data,
			"processData" : false,
			"onsuccess": (function() {
				var func = function(data) {
					//converting html to dom nodes
					var tempDiv = document.createElement("div");
					tempDiv.innerHTML = "<table>" + data + "</table>";

					var arRows = tempDiv.firstChild.getElementsByTagName("TR");
					var arScripts = tempDiv.firstChild.getElementsByTagName("SCRIPT");
					for(var i = arRows.length - 1; i >= 0; i--)
					{
						if (!BX(arRows[i].id))
						{
							currentRow.parentNode.insertBefore(arRows[i], currentRow.nextSibling);

							if (
								(!BX.browser.IsIE())
								|| (!!document.documentMode && document.documentMode >= 10)
							)
							{
								var script = BX.create(
									"script", {
										props : {type : "text/javascript"},
										html: arScripts[i].innerHTML
									}
								)
							}
							else
							{
								var script = arScripts[i];
							}

							currentRow.parentNode.insertBefore(script, currentRow.nextSibling);
						}
					}

					ToggleSubtasks(currentRow, depthLevel, taskID);
				}

				return func;
			})()
		});
	}
}

function Depth(row)
{
	var regexp = /task-depth-([0-9]+)/;
	var matches = regexp.exec(row.className);
	if (matches)
	{
		return parseInt(matches[1]);
	}
	else
	{
		return 0;
	}
}