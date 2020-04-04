window.saveTimer = null;

if (!window.listItemParams)
{
	window.listItemParams = {
		isItem: {property: 'BXLISTITEM'},
		getData: function() {
			if (!this.BXLISTCOUNER)
				this.BXLISTCOUNER = BX.findChild(this, {className: 'meet-ag-block-title-num'}, true)

			return {
				counter: this.BXLISTCOUNER,
				children: this.nextSibling
			}
		},
		getCounterValue: function(num, prefix)
		{
			if (prefix)
			{
				prefix = prefix.substring(0, prefix.length - 6);
				if (prefix && prefix.charAt(prefix.length-1) != '.')
				{
					prefix += '.';
				}
			}

			return (prefix || '') + num + (prefix ? '' : '.') + '&nbsp;';
		}
	};
}

function getUserUrl(id)
{
	return window.bx_user_url_tpl.replace(/#user_id#|#id#/i, id);
}


function BXOnMembersListChange()
{
	window.arMembersList = arguments[0];

	if (!window.meeting_owner_data)
		window.meeting_owner_data = window.arMembersList[window.meeting_owner];
	else if (!window.arMembersList[window.meeting_owner])
		window.arMembersList[window.meeting_owner] = window.meeting_owner_data;

	BX.onCustomEvent('onMeetingChangeUsersList', []);
	BX.onCustomEvent('onMembersListChange', [BX.util.array_values(window.arMembersList)]);
}

function BXSelectMembers(el)
{
	if (!window.BXMembersSelector)
	{
		window.BXMembersSelector = BX.PopupWindowManager.create("members-popup", el, {
			offsetTop : 1,
			autoHide : true,
			content : BX("USERS_selector_content")
		});
	}

	if (window.BXMembersSelector.popupContainer.style.display != "block")
	{
		window.BXMembersSelector.show();
	}
}

function BXSelectKeepers(el)
{
	var a = BX.util.array_values(window.arMembersList);
	UpdateKeepersList(a);

	if (!window.BXKeeperSelector)
	{
		window.BXKeeperSelector = BX.PopupWindowManager.create("keepers-popup", el, {
			offsetTop : 1,
			autoHide : true,
			content : BX("keeper_selector_content")
		});
	}

	if (window.BXKeeperSelector.popupContainer.style.display != "block" && a.length > 1)
	{
		window.BXKeeperSelector.show();
	}
}

function meetingAction(id, params)
{
	var p = {r: Math.random(), MEETING_ID:id};
	if (params && params.state)
		p.STATE = params.state;

	p.sessid = BX.bitrix_sessid();
	BX.ajax.loadJSON('/bitrix/tools/ajax_meeting.php', p, meetingHandler);
}

function meetingHandler(params)
{
	switch (params.state)
	{
		case 'A':
			switchView('protocol');
		case 'C':
			BX('switcher').style.display = 'block';
		break;
		case 'P':
			BX('switcher').style.display = 'none';
			switchView('agenda');
		break;
	}

	BX('meeting_state_text').innerHTML = BX.message('MEETING_STATE_' + params.state);
	BX('meeting_toolbar').className = 'meeting-toolbar toolbar-' + params.state;
}

function switchView(type)
{
	window.current_view = type;

	if (type == 'agenda')
	{
		BX.addClass(BX('switch_agenda', true), 'meeting-tab-active');
		BX.removeClass(BX('switch_protocol', true), 'meeting-tab-active');
		BX.removeClass(BX('agenda_block'), 'meeting-detail-agenda-protocol-active');
	}
	else
	{
		BX.removeClass(BX('switch_agenda', true), 'meeting-tab-active');
		BX.addClass(BX('switch_protocol', true), 'meeting-tab-active');
		BX.addClass(BX('agenda_block'), 'meeting-detail-agenda-protocol-active');
	}

	updateIndexes();

	if (jsDD)
		jsDD.refreshDestArea();
}

var updTimer = null;
function updateIndexes()
{
	if (updTimer)
		clearTimeout(updTimer);
	updTimer = setTimeout("updateListNumbers()", 30);
}

function updateListNumbers()
{
	BX.listNumber(window.listItemParams);
}

function saveData(bTimeout)
{
	if (!!window.BXMEETINGCANEDIT)
	{
		if (window.saveTimer)
			clearTimeout(window.saveTimer);

		if (!bTimeout)
		{
			window.saveTimer = setTimeout("saveData(true)", 1000);
		}
		else
		{
			var f = document.forms.meeting_edit
			if (f.MEETING_ID.value > 0)
			{
				f.save_type.value = 'BGSAVE';
				BX.ajax.submit(f);
				setTimeout(function(){
					f.save_type.value = 'SUBMIT';
				}, 15);
			}
		}
	}
}

function replaceKeys(repl, link)
{
	var i,j,row,subrows,arFields = ['AGENDA', 'AGENDA_PARENT', 'AGENDA_ORIGINAL', 'AGENDA_TYPE', 'AGENDA_TITLE', 'AGENDA_TASK', 'AGENDA_DEADLINE', 'AGENDA_SORT', 'AGENDA_ITEM'];

	var ie7 = false;
	/*@cc_on
		 @if (@_jscript_version <= 5.7)
			ie7 = true;
		/*@end
	@*/

	for (i in repl)
	{
		if (!repl[i])
			continue;

		if (document.forms.meeting_edit['AGENDA['+i+']'])
		{
			row = BX('agenda_item_' + i);
			if (row)
			{
				row.BXINSTANCEKEY = row.BXINSTANCE.ID = repl[i][0];
				row.BXINSTANCE.ITEM_ID = repl[i][1];

				row.id = 'agenda_item_' + repl[i][0];
				row.nextSibling.id = 'agenda_blocks_' + repl[i][0];

				BX('agenda_item_comments_'+i).id = 'agenda_item_comments_'+repl[i][0];

				document.forms.meeting_edit['AGENDA['+i+']'].value = repl[i][0];
				document.forms.meeting_edit['AGENDA_ITEM['+i+']'].value = repl[i][1];

				for (j = 0; j < arFields.length; j++)
				{
					if (document.forms.meeting_edit[arFields[j]+'['+i+']'])
					{
						document.forms.meeting_edit[arFields[j]+'['+i+']'].name = arFields[j]+'['+repl[i][0]+']';
						if (ie7)
						{
							document.forms.meeting_edit[arFields[j]+'['+repl[i][0]+']'] = document.forms.meeting_edit[arFields[j]+'['+i+']']
						}
					}
				}
				document.forms.meeting_edit['AGENDA_RESPONSIBLE['+i+'][]'].name = 'AGENDA_RESPONSIBLE['+repl[i][0]+'][]';
				if (ie7)
				{
					document.forms.meeting_edit['AGENDA_RESPONSIBLE['+repl[i][0]+'][]'] = document.forms.meeting_edit['AGENDA_RESPONSIBLE['+i+'][]']
				}

				var link_href = link.replace('#ITEM_ID#', repl[i][1]),
					icons = BX.findChild(row, {tag: 'DIV', className: 'meeting-ag-info-icons'}, true),
					anchor = BX.findChild(row, {tag: 'A', className: 'meeting-ag-block-title-text'}, true),
					anchor_tasks = BX.findChild(row, {tag: 'A', className: 'meeting-ag-tasks-ic'}, true);

				if (icons) icons.style.display = 'block';
				if (anchor) anchor.href = link_href;
				if (anchor_tasks) anchor_tasks.href = link_href + '#tasks';

				subrows = BX.findChildren(row.nextSibling, listItemParams.isItem);

				if (subrows && subrows.length > 0)
				{
					for (j = 0; j < subrows.length; j++)
					{
						subrows[j].BXINSTANCE.INSTANCE_PARENT_ID = repl[i][0];
						document.forms.meeting_edit['AGENDA_PARENT['+subrows[j].BXINSTANCEKEY+']'].value = repl[i][0];
					}
				}
			}
		}
	}
}

function replaceTasks(tasks)
{
	var i,j,q,row;
	for (i in tasks)
	{
		row = BX('agenda_item_' + i);
		if (row)
		{
			row.BXINSTANCE.AGENDA_TASK_CHECKED = false;
			if (tasks[i])
			{
				row.BXINSTANCE.TASKS_COUNT[0]++;
				row.BXINSTANCE.TASKS_COUNT[1]++;
				row.BXINSTANCE.TASK_ID = tasks[i];
				row.BXINSTANCE.TASK_ACCESS = true;
			}
			else
			{
				row.BXINSTANCE.TASK_ID = null;
			}

			if (window.currently_edited_row != row)
			{
				viewRow(row, false)
			}
			else
			{
				BX.addClass(BX('meeting_make_task_'+i), 'meeting-has-task');
				BX('meeting_make_task_'+i).setAttribute('onclick', 'taskIFramePopup.tasksList=[]; taskIFramePopup.view('+tasks[i]+');');
			}

			q = document.forms.meeting_edit['AGENDA_TASK['+row.BXINSTANCEKEY+']'];
			if (q && q.length > 1)
			{
				for (j=0;j<q.length;j++)
				{
					if (q[j].value != 'Y')
					{
						q[j].parentNode.removeChild(q[j]);
					}
				}
			}
		}
	}
}

function meetingOnTaskDeleted(task_id)
{
	var r = BX('meeting_task_' + task_id);
	if (r)
	{
		row = BX.findParent(r, listItemParams.isItem);
		if (row)
		{
			row.BXINSTANCE.TASK_ID = null;
			row.BXINSTANCE.AGENDA_TASK_CHECKED = false;

			if (window.currently_edited_row != row)
			{
				viewRow(row, false);
			}
			else
			{
				BX.removeClass(BX('meeting_make_task_'+i), 'meeting-has-task');
			}
		}
	}
}
BX.addCustomEvent('onTaskDeleted', meetingOnTaskDeleted);