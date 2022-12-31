if (typeof BX.BizProcMobile === 'undefined')
{
	BX.BizProcMobile = {};

	BX.BizProcMobile.doTask = function (parameters, callback, silent)
	{
		var preparePost = true;
		if (parameters instanceof FormData)
		{
			preparePost = false;
			parameters.append('sessid', BX.bitrix_sessid());
		}
		else
		{
			parameters['sessid'] = BX.bitrix_sessid();
		}

		app.showPopupLoader({text: '...'});
		var url = (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + 'mobile/?mobile_action=bp_do_task';

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: url,
			data: parameters,
			preparePost: preparePost,
			onsuccess: function (json)
			{
				app.hidePopupLoader();

				if (json.ERROR)
				{
					app.alert({text: json.ERROR, title: BX.message('MB_BP_DETAIL_ALERT')});
				}
				else
				{
					if (callback)
					{
						callback(json, parameters);
					}
					if (!silent)
					{
						BXMobileApp.onCustomEvent('bpDoTaskComplete', parameters, true);
					}
				}
			},
			onfailure: function (e)
			{
				console.error(e);
				app.hidePopupLoader();
			}
		});

		return false;
	};

	BX.BizProcMobile.openTaskPage = function (taskId, event)
	{
		if (
			typeof event != 'undefined'
			&& event != null
			&& event
			&& typeof event.target != 'undefined'
			&& event.target != null
		)
		{
			if (
				typeof event.target.tagName != 'undefined'
				&& event.target.tagName.toLowerCase() == 'a'
			)
			{
				return false;
			}

			var anchorNode = BX.findParent(event.target, {'tag': 'A'}, {
				'tag': 'div',
				'className': 'post-item-post-block'
			});
			if (anchorNode && !BX.hasClass(anchorNode, 'webform-small-button-blue'))
			{
				return false;
			}
		}
		app.loadPageBlank({
			url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + 'mobile/bp/detail.php?task_id=' + taskId,
			unique: true
		});

		BX.PreventDefault(event);

		return false;
	};

	BX.BizProcMobile.renderLogMessage = function(logElement, newContent, updateId)
	{
		if (!logElement)
			return false;
		if (newContent !== null)
		{
			var wrapper = logElement.parentNode;
			if (!wrapper)
				return false;
			wrapper.innerHTML = newContent;
			logElement = wrapper.querySelector('[data-role="mobile-log-bp-wf"]');
			if (!logElement)
				return false;
		}

		var tasks = JSON.parse(logElement.getAttribute('data-tasks')),
		userId = 0,
		statusWaiting = '0', //CBPTaskUserStatus::Waiting
		statusYes = '1', //CBPTaskUserStatus::Yes
		statusNo = '2', //CBPTaskUserStatus::No
		statusOk = '3', //CBPTaskUserStatus::Ok
		statusCancel = '4',	//CBPTaskUserStatus::Cancel
		userStatus = false,
		taskId = false;

		if (BX.message('USER_ID'))
			userId = BX.message('USER_ID');

		var getUserFromTask = function (task, userId)
		{
			for (var i = 0, l = task.USERS.length; i < l; ++i)
			{
				if (task.USERS[i]['USER_ID'] == userId)
					return task.USERS[i];
			}
			return null;
		};

		if (tasks.length)
		{
			for (var i = 0, l = tasks.length; i < l; ++i)
			{
				var task = tasks[i];
				var user = getUserFromTask(task, userId);
				if (user)
				{
					if (user.STATUS > statusWaiting)
						userStatus = user.STATUS;
					else
					{
						userStatus = false;
						taskId = task.ID;
						var btn = BX.findChild(logElement, {className: 'task_buttons_'+task.ID}, true);
						if (btn)
							btn.style.display = '';
						var taskBlock = BX.findChild(logElement, {className: 'task_block_'+task.ID}, true);

						if (taskBlock)
							taskBlock.style.display = '';
						break;
					}
				}
			}
		}
		if (userStatus !== false)
		{
			var userStatusCls = 'user_status_ok';
			if (userStatus == statusYes)
				userStatusCls = 'user_status_yes';
			else if (userStatus == statusNo || userStatus == statusCancel)
				userStatusCls = 'user_status_no';

			var userStatusBlock = BX.findChild(logElement, {className: userStatusCls}, true);
			if (userStatusBlock)
				userStatusBlock.style.display = '';
		}
		var statusBlock = BX.findChild(logElement, {className: 'wf_status'}, true);
		if (statusBlock)
			statusBlock.style.display = (userStatus || taskId)? 'none' : '';

		logElement.setAttribute('data-rendered', updateId);
	};

	BX.BizProcMobile.renderLogMessages = function(scope, workflowId, newLogContent, updateId)
	{
		var items = scope.querySelectorAll('[data-role="mobile-log-bp-wf"]');
		if (!updateId)
			updateId = 'Y';

		if (items)
		{
			for(var i=0; i<items.length; i++)
			{
				var rendered = items[i].getAttribute('data-rendered'),
					itemWorkflowId = items[i].getAttribute('data-workflow-id');

				if (rendered)
				{
					if (rendered === updateId.toString())
						continue;

					if (workflowId && workflowId !== itemWorkflowId)
						continue;
				}

				var itemContent = workflowId === itemWorkflowId ? newLogContent : null;
				BX.BizProcMobile.renderLogMessage(items[i], itemContent, updateId);
			}

			BX.onCustomEvent('MobileBizProc:onRenderLogMessages', []);
		}
	};

	BX.BizProcMobile.loadLogMessageCallback = function(json, parameters)
	{
		BX.ajax({
			'method': 'POST',
			'dataType': 'html',
			'url': '/mobile/?mobile_action=bp_livefeed_action',
			'data': {WORKFLOW_ID: parameters['WORKFLOW_ID']},
			'onsuccess': function (HTML)
			{
				parameters['NEW_LOG_CONTENT'] = HTML;
				parameters['UPDATE_ID'] = parseInt(Math.random()*10000);
				BX.BizProcMobile.renderLogMessages(document, parameters['WORKFLOW_ID'], HTML, parameters['UPDATE_ID']);
				BXMobileApp.onCustomEvent('bpDoTaskComplete', parameters, true);
			}
		});
	};

	BX.BizProcMobile.renderFacePhoto = function(scope, users)
	{
		var userId = BX.message('USER_ID'),
			displayedUser = users[0];

		if (userId && users.length > 1)
		{
			for (var i = 0, l = users.length; i < l; ++i)
			{
				var user = users[i];
				if (user['USER_ID'] == userId)
				{
					displayedUser = user;
					break;
				}
			}
		}
		if (displayedUser['PHOTO_SRC'])
		{
			scope.onload = null;
			scope.src = displayedUser['PHOTO_SRC'];
		}
	};

	BX.BizProcMobile.showDatePicker = function(scope, event)
	{
		var format = 'M/d/y H:m';
		var wrapper = scope.parentNode;
		var input = BX.findChild(wrapper, {tag: 'input'});
		var type = input.getAttribute('data-type') === 'date'? 'date' : 'datetime';
		var pickerParams = {
			type: type,
			format: format,
			callback: function(value)
			{
				var d = new Date(Date.parse(value));
				var siteFormat = type === 'date' ? BX.message('FORMAT_DATE') : BX.message('FORMAT_DATETIME');
				var formatted = BX.formatDate(d, siteFormat);

				input.value = formatted;
				scope.innerHTML = formatted;
			}
		};
		BXMobileApp.UI.DatePicker.setParams(pickerParams);
		BXMobileApp.UI.DatePicker.show();
		return BX.PreventDefault(event);
	};
}
