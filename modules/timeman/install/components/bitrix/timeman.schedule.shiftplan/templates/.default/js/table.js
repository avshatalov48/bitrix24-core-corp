;(function ()
{
	BX.namespace('BX.Timeman.Component.Schedule.ShiftPlan');
	BX.Timeman.Component.Schedule.ShiftPlan.Table = function (options)
	{
		this.isSlider = options.isSlider;
		this.scheduleId = options.scheduleId;
		this.gridId = options.gridId;
		this.container = options.container.querySelector('[data-role="shift-plans-container"]');

		this.addEventHandlers();
	};
	BX.Timeman.Component.Schedule.ShiftPlan.Table.prototype = {
		addEventHandlers: function ()
		{
			this.addEventHandlersInsideGrid();
			BX.addCustomEvent('Grid::updated', this.addEventHandlersInsideGrid.bind(this));
		},
		addEventHandlersInsideGrid: function ()
		{
			var addShiftplanBtns = this.selectAllByRole('add-shiftplan-btn');
			for (var i = 0; i < addShiftplanBtns.length; i++)
			{
				BX.bind(addShiftplanBtns[i], 'click', BX.delegate(this.onAddShiftPlanClick, this));
			}

			var deleteShiftplanBtns = this.selectAllByRole('delete-shiftplan-btn');
			for (var i = 0; i < deleteShiftplanBtns.length; i++)
			{
				BX.bind(deleteShiftplanBtns[i], 'click', BX.delegate(this.onDeleteShiftPlanClick, this));
			}

			var deleteUserBtns = this.selectAllByRole('delete-user-btn');
			for (var i = 0; i < deleteUserBtns.length; i++)
			{
				BX.bind(deleteUserBtns[i], 'click', BX.delegate(this.onDeleteUserClick, this));
			}
		},
		onDeleteUserClick: function (event)
		{
			if (this.popupDeleteUser)
			{
				this.popupDeleteUser.close();
			}
			var userId = event.currentTarget.dataset.userId;
			this.popupDeleteUser = new BX.PopupWindow({
				id: 'tm-shiftplan-delete-schedule-user-' + event.currentTarget.dataset.userId,
				autoHide: true,
				draggable: true,
				bindOptions: {forceBindPosition: false},
				closeByEsc: true,
				closeIcon: {top: '10px', right: '15px'},
				zIndex: 0,
				titleBar: BX.message('TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM_TITLE'),
				content: BX.message('TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM').replace('#USER_NAME#', event.currentTarget.dataset.userName),
				buttons: [
					new BX.PopupWindowButtonLink({
						text: BX.message('TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM_NO'),
						className: 'popup-window-button-link-cancel',
						events: {
							click: function ()
							{
								this.popupDeleteUser.close();
							}.bind(this)
						}
					}),
					new BX.PopupWindowButton({
						text: BX.message('TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM_YES'),
						className: 'popup-window-button-accept',
						events: {
							click: function (userId)
							{
								this.popupDeleteUser.close();
								BX.Main.gridManager.getInstanceById(this.gridId).tableFade();
								BX.ajax.runAction('timeman.schedule.deleteUser', {
									data: {
										id: this.scheduleId,
										userId: userId
									}
								}).then(
									function (response)
									{
										this.reloadGrid();
									}.bind(this),
									function (response)
									{

									}.bind(this));
							}.bind(this, userId)
						}
					})
				]
			});
			this.popupDeleteUser.show();
		},
		reloadGrid: function ()
		{
			BX.Main.gridManager.reload(this.gridId);
		},
		onDeleteShiftPlanClick: function (event)
		{
			event.stopPropagation();
			event.preventDefault();
			var form = event.currentTarget;
			BX.ajax.runAction(
				'timeman.shiftplan.delete',
				{
					data: this.createFormDataForShiftPlan(form)
				}
			).then(
				function (response)
				{
					this.onSuccessShiftPlanDeleted.bind(this)(response.data.shiftPlan, form);
				}.bind(this),
				function (response)
				{
				}.bind(this));
		},
		onSuccessShiftPlanDeleted: function (shiftPlan, form)
		{
			if (!shiftPlan.shift)
			{
				return;
			}
			var addBtn = document.createElement('div');
			addBtn.innerHTML = shiftPlan.shiftCellHtml.trim();
			addBtn = addBtn.firstChild;

			BX.bind(addBtn, 'click', BX.delegate(this.onAddShiftPlanClick, this));

			var formParent = BX.findParent(form, {'attr': 'data-shift-block'});
			formParent.parentNode.replaceChild(addBtn, formParent);
			formParent.remove();
			this.showElement(addBtn);
		},
		createFormDataForShiftPlan: function (formWrapper)
		{
			var formData = new FormData();
			var inputs = formWrapper.querySelectorAll('input[type="hidden"]');
			for (var i = 0; i < inputs.length; i++)
			{
				formData.append(inputs[i].name, inputs[i].value);
			}
			return formData;
		},
		onAddShiftPlanClick: function (event)
		{
			event.stopPropagation();
			event.preventDefault();
			var formWrapper = event.currentTarget;
			if (formWrapper.isDisabled)
			{
				return;
			}
			formWrapper.isDisabled = true;
			formWrapper.style.opacity = 0;
			BX.ajax.runAction(
				'timeman.shiftplan.add',
				{
					data: this.createFormDataForShiftPlan(formWrapper)
				}
			).then(
				function (response)
				{
					formWrapper.style.opacity = 1;
					event.target.isDisabled = false;
					this.onSuccessShiftPlanAdded.bind(this)(response.data.shiftPlan, formWrapper);
				}.bind(this),
				function (response)
				{
					event.target.isDisabled = false;
					formWrapper.style.opacity = 1;
				}.bind(this));
		},
		onSuccessShiftPlanAdded: function (shiftPlan, formParent)
		{
			var shiftBlock = document.createElement('div');
			shiftBlock.innerHTML = shiftPlan.shiftCellHtml;
			shiftBlock = shiftBlock.firstChild;

			BX.bind(this.selectOneByRole('delete-shiftplan-btn', shiftBlock), 'click', BX.delegate(this.onDeleteShiftPlanClick, this));

			formParent.parentNode.replaceChild(shiftBlock, formParent);
			formParent.remove();
		},
		selectOneByRole: function (role, container)
		{
			return container ?
				container.querySelector('[data-role="' + role + '"')
				: this.container.querySelector('[data-role="' + role + '"');
		},
		selectAllByRole: function (role)
		{
			return this.container.querySelectorAll('[data-role="' + role + '"');
		},
		showElement: function (element)
		{
			if (element)
			{
				element.classList.remove('timeman-hide');
			}
		},
		hideElement: function (element)
		{
			if (element)
			{
				element.classList.add('timeman-hide');
			}
		},
	};

})();