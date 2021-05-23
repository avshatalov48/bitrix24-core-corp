;(function ()
{
	BX.namespace('BX.Timeman.Component.Schedule.ShiftPlan');
	BX.Timeman.Component.Schedule.ShiftPlan.Table = function (options)
	{
		BX.Timeman.Component.BaseComponent.apply(this, [{containerSelector: '[data-role="shift-records-container"]'}]);
		this.isSlider = options.isSlider;
		this.scheduleId = options.scheduleId;
		this.gridId = options.gridId;
		this.useEmployeesTimezoneName = 'useEmployeesTimezone';
		this.errorCodeOverlappingPlans = options.errorCodeOverlappingPlans;
		this.addEventHandlers();
	};
	BX.Timeman.Component.Schedule.ShiftPlan.Table.prototype = {
		__proto__: BX.Timeman.Component.BaseComponent.prototype,
		constructor: BX.Timeman.Component.Schedule.ShiftPlan.Table,
		addEventHandlers: function ()
		{
			this.addEventHandlersInsideGrid();
			BX.addCustomEvent('Grid::updated', this.addEventHandlersInsideGrid.bind(this));
			document.querySelector('[data-role="shift-records-container"]').addEventListener('TimemanWorktimeGridCellHtmlUpdated', function (e)
			{
				if (!e.detail.dayCellNodes)
				{
					return;
				}
				for (var i = 0; i < e.detail.dayCellNodes.length; i++)
				{
					var timeCells = e.detail.dayCellNodes[i].querySelectorAll('[data-shift-block="true"]');
					if (timeCells.length === 0)
					{
						continue;
					}
					for (var timeIndex = 0; timeIndex < timeCells.length; timeIndex++)
					{
						BX.bind(this.selectOneByRole('shiftplan-menu-toggle', timeCells[timeIndex]), 'click', BX.delegate(this.onShiftplanMenuToggleClick, this));
						BX.bind(this.selectOneByRole('add-shiftplan-btn', timeCells[timeIndex]), 'click', BX.delegate(this.onAddShiftPlanClick, this));
					}
				}
			}.bind(this), false);
		},
		addEventHandlersInsideGrid: function ()
		{
			var addShiftplanBtns = this.selectAllByRole('add-shiftplan-btn');
			for (var i = 0; i < addShiftplanBtns.length; i++)
			{
				BX.bind(addShiftplanBtns[i], 'click', BX.delegate(this.onAddShiftPlanClick, this));
			}

			var shiftplanMenuToggles = this.selectAllByRole('shiftplan-menu-toggle');
			for (var i = 0; i < shiftplanMenuToggles.length; i++)
			{
				BX.bind(shiftplanMenuToggles[i], 'click', BX.delegate(this.onShiftplanMenuToggleClick, this));
			}

			var deleteUserBtns = this.selectAllByRole('delete-user-btn');
			for (var i = 0; i < deleteUserBtns.length; i++)
			{
				BX.bind(deleteUserBtns[i], 'click', BX.delegate(this.onDeleteUserClick, this));
			}
		},
		useEmployeesTimezone: function ()
		{
			return this.getCookie(this.useEmployeesTimezoneName) === 'Y';
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
					new BX.PopupWindowButton({
						text: BX.message('TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM_NO'),
						className: 'ui-btn ui-btn-danger',
						events: {
							click: function ()
							{
								this.popupDeleteUser.close();
							}.bind(this)
						}
					}),
					new BX.PopupWindowButton({
						text: BX.message('TM_SCHEDULE_PLAN_DELETE_USER_CONFIRM_YES'),
						className: 'ui-btn ui-btn-success',
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
		onShiftplanMenuToggleClick: function (event)
		{
			event.stopPropagation();
			event.preventDefault();
			this.planMenuPopup = this.buildPlanMenuPopup(event);
			if (this.planMenuPopup)
			{
				this.planMenuPopup.show();
			}
		},
		buildPlanMenuPopup: function (event)
		{
			var items = this.buildPlanMenuItems(event);

			if (items.length > 0)
			{
				var id = 'tmShiftPlanMenu';
				for (var i = 0; i < items.length; i++)
				{
					id = id + items[i].id;
				}
				return BX.PopupMenu.create({
					items: items,
					maxHeight: 450,
					id: id,
					bindElement: event.currentTarget,
					angle: true,
					closeByEsc: true,
					autoHide: true
				});
			}
			return null;
		},
		buildPlanMenuItems: function (event)
		{
			var dataset = event.currentTarget.dataset;
			var items = [];
			if (dataset.itemDelete === '1')
			{
				items.push({
					id: 'deletePlan' + BX.util.getRandomString(20),
					text: BX.util.htmlspecialchars(BX.message('TM_SHIFT_PLAN_MENU_DELETE_SHIFT_TITLE')),
					onclick: function (form)
					{
						this.planMenuPopup.close();
						BX.ajax.runAction(
							'timeman.shiftplan.delete',
							{
								data: this.createFormDataForShiftPlan(form)
							}
						).then(
							function (form, response)
							{
								this.onSuccessShiftPlanDeleted(response.data.shiftPlan);
							}.bind(this, form),
							function (response)
							{
							}.bind(this));
					}.bind(this, event.currentTarget)
				});
			}
			if (dataset.itemAdd === '1')
			{
				items.push({
					id: 'addPlan' + BX.util.getRandomString(20),
					text: BX.util.htmlspecialchars(BX.message('TM_SHIFT_PLAN_MENU_ADD_SHIFT_TITLE')),
					onclick: function (btn)
					{
						this.planMenuPopup.close();
						this.onAddShiftPlanClick(btn);
					}.bind(this, event.currentTarget)
				});
			}

			return items;
		},
		createFormDataForShiftPlan: function (formWrapper)
		{
			var formData = new FormData();
			var inputs = formWrapper.querySelectorAll('input[type="hidden"]');
			for (var i = 0; i < inputs.length; i++)
			{
				formData.append(inputs[i].name, inputs[i].value);
			}
			formData.append('useEmployeesTimezone', this.useEmployeesTimezone() ? 'Y' : 'N');
			var absenceBlock = this.selectOneByRole('absence', BX.findParent(formWrapper, {'tag': 'td'}));
			if (absenceBlock && absenceBlock.dataset)
			{
				formData.append('drawAbsenceTitle', absenceBlock.dataset.title);
			}
			return formData;
		},
		onAddShiftPlanClick: function (event, force)
		{
			var formWrapper = event;
			if (event.stopPropagation)
			{
				event.stopPropagation();
				event.preventDefault();
				formWrapper = event.currentTarget;
			}
			if (formWrapper.isDisabled)
			{
				return;
			}
			formWrapper.isDisabled = true;
			var formData = this.createFormDataForShiftPlan(formWrapper);
			if (force === true)
			{
				formData.append('createShiftPlanForced', 'Y');
			}
			BX.ajax.runAction(
				'timeman.shiftplan.add',
				{
					data: formData
				}
			).then(
				function (response)
				{
					formWrapper.isDisabled = false;
					this.onSuccessShiftPlanAdded(response.data.shiftPlan);
				}.bind(this),
				function (response)
				{
					if (response.errors && response.errors.length > 0
						&& response.errors[0].code === this.errorCodeOverlappingPlans)
					{
						BX.UI.Dialogs.MessageBox.show({
							message: BX.util.htmlspecialchars(response.errors[0].message),
							modal: true,
							buttons: BX.UI.Dialogs.MessageBoxButtons.YES_NO,
							popupOptions: {
								autoHide: true
							},
							onYes: function (formWrapper, messageBox)
							{
								messageBox.close();
								this.onAddShiftPlanClick(formWrapper, true);
							}.bind(this, formWrapper),
							onNo: function (messageBox)
							{
								messageBox.close();
							}
						});
					}
					formWrapper.isDisabled = false;
				}.bind(this));
		},
		onSuccessShiftPlanDeleted: function (shiftPlan)
		{
			this.dispatchCellHtmlRedraw(shiftPlan);
		},
		onSuccessShiftPlanAdded: function (shiftPlan)
		{
			this.dispatchCellHtmlRedraw(shiftPlan);
		},
		dispatchCellHtmlRedraw: function (shiftPlan)
		{
			if (!this.getEventContainer())
			{
				return;
			}
			var event = new CustomEvent('TimemanWorktimeGridCellHtmlRedraw', {
				detail: {
					html: [shiftPlan.cellHtml]
				}
			});
			this.getEventContainer().dispatchEvent(event);
		},
		getEventContainer: function ()
		{
			return document.querySelector('[data-role="shift-records-container"]');
		}
	};
})();