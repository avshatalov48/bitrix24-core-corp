if (typeof(BX.CrmActivityTodo) === 'undefined')
{
	BX.CrmActivityTodo = function(settings)
	{
		this._ccontainer = settings.ccontainer || 'crm-activity-todo-items';
		this._citem = settings.citem || 'crm-activity-todo-item';
		this._clink = settings.clink || 'crm-activity-todo-link';
		this._ccheck = settings.ccheck || 'crm-activity-todo-check';
		this._cbuttoncancel = settings.cbuttoncancel || 'popup-window-button-link-cancel';
		this._ccheckprefix = settings.ccheckprefix || 'check';
		this._ajaxPath = settings.ajax_path || '/bitrix/components/bitrix/crm.activity.todo/ajax.php';
		this._ajaxPlannerPath = settings.ajax_planner_path || '/bitrix/components/bitrix/crm.activity.planner/ajax.php?site_id=' + BX.message('SITE_ID');
		this._dialogId = 'activity_todo_dialog';
		this._popup = null;
		this._activityId = 0;
		
		//bind click on activity title
		var activityLink = BX.findChild(BX(this._ccontainer), { class: this._clink, tag: 'a' }, true, true);
		if (activityLink)
		{
			for (i=0; i<activityLink.length; i++)
			{
				BX.bind(activityLink[i], 'click', BX.delegate(this._clickTitleHandler, this));
			}
		}
		//bind click on checkbox
		var activityCheck = BX.findChild(BX(this._ccontainer), { class: this._ccheck }, true, true);
		if (activityCheck)
		{
			for (i=0; i<activityCheck.length; i++)
			{
				BX.bind(activityCheck[i], 'click', BX.delegate(this._clickCheckHandler, this));
			}
		}
	};
	BX.CrmActivityTodo.prototype =
	{
		_getParent: function(proxy)
		{
			return BX.findParent(proxy, { class: this._citem });
		},
		_showPopup: function(title, events)
		{
			if (this._popup === null)
			{
				this._popup = new BX.PopupWindow(this._dialogId, window.body, {
					offsetLeft : 0,
					lightShadow : true,
					closeIcon : true,
					titleBar: {content: BX.create('span', {html: ''})},
					draggable: true,
					closeByEsc : true,
					contentColor: 'white',
					events: events,
					overlay: {
						backgroundColor: '#cdcdcd', opacity: '80'
					}
				});
			}
			this._popup.setContent('...');
			this._popup.setTitleBar(title);
			this._popup.show();
		},
		_loadActivity: function()
		{
			var _this = this;
			BX.ajax.post(this._ajaxPlannerPath, {
				sessid: BX.bitrix_sessid(),
				ajax_action: 'ACTIVITY_VIEW',
				activity_id: this._activityId
			}, function(data) {
				_this._popup.setContent(data);
				_this._popup.adjustPosition();
				var additionalSwitcher = _this._getNodeByRole(BX(_this._dialogId), 'additional-switcher');
				var additionalFields = _this._getNodeByRole(BX(_this._dialogId), 'additional-fields');
				var fieldCompleted = _this._getNodeByRole(BX(_this._dialogId), 'field-completed');
				if (additionalSwitcher && additionalFields)
				{
					BX.bind(additionalSwitcher, 'click', function() {
								BX.toggleClass(additionalFields, 'active')
							});
				}
				if (fieldCompleted)
				{
					BX.remove(BX.findParent(fieldCompleted, {tag: 'label'}));
					/*if (fieldCompleted.checked)
					{
						fieldCompleted.disabled = true;
					}
					else
					{
						BX.bind(fieldCompleted, 'click', function(){
							BX.fireEvent(BX(_this._ccheckprefix + _this._activityId), 'click');
							fieldCompleted.disabled = true;
						});
					}*/
				}
				
				_this._popup.setButtons([
					new BX.PopupWindowButtonLink({
						text : BX.message('CRM_ACTIVITY_TODO_CLOSE'),
						className : _this._cbuttoncancel,
						events : {
							click: function(){this.popupWindow.close();}
						}
					})
				]);
			});
		},
		_getNodeByRole: function(container, name)
		{
			return container.querySelector('[data-role="'+name+'"]');
		},
		_clickTitleHandler: function(e)
		{
			this._activityId = BX.data(this._getParent(BX.proxy_context), 'id');
			if (BX.data(this._getParent(BX.proxy_context), 'icon') === 'tasks' && typeof window['taskIFramePopup'] !== 'undefined')
			{
				window['taskIFramePopup'].view(BX.data(this._getParent(BX.proxy_context), 'associatedid'), window['tasksIFrameList']);
			}
			else if (BX.CrmActivityEditor && BX.CrmActivityEditor.items['kanban_activity_editor'])
			{
				// @TODO: preload activity? or loader?
				BX.CrmActivityEditor.items['kanban_activity_editor'].viewActivity(this._activityId);
			}
			else
			{
				this._showPopup(
								BX.message('CRM_ACTIVITY_TODO_VIEW_TITLE'),
								{
									onAfterPopupShow: BX.delegate(
											this._loadActivity, 
											this
										),
									onPopupClose: BX.delegate(
											function() {
												// clean popupId
												this._popup.destroy();
												this._popup = null;
											}, 
											this
										)
								});
			}
			BX.PreventDefault(e);
		},
		_clickCheckHandler: function(e)
		{
			if (BX.proxy_context.checked)
			{
				var context = BX.proxy_context;
				var parent = this._getParent(context);
				BX.ajax.loadJSON(this._ajaxPath, {
					action: 'complete',
					id: BX.data(parent, 'id'),
					ownerid: BX.data(parent, 'ownerid'),
					ownertypeid: BX.data(parent, 'ownertypeid'),
					completed: 1
				}, function(data) {
					if (0&&data.error)
					{
						alert(data.error);
						context.checked = false;
					}
					else
					{
						BX.onCustomEvent('onCrmActivityTodoChecked', [
							BX.data(parent, 'id'), 
							BX.data(parent, 'ownerid'), 
							BX.data(parent, 'ownertypeid'),
							parseInt(BX.data(parent, 'deadlined')) === 1
						]);
						context.disabled = true;
						BX.addClass(parent, 'crm-activity-todo-item-completed');
					}
				});
			}
		}
	};
	BX.CrmActivityTodo._self = null;
	BX.CrmActivityTodo.create = function(settings)
	{
		this._self = new BX.CrmActivityTodo(settings || {});
		return this._self;
	};
}