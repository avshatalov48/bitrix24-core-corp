;(function ()
{
	BX.namespace('BX.Timeman.Component.Schedule.List');
	BX.Timeman.Component.Schedule.List = function (options)
	{
		BX.Timeman.Component.BaseComponent.apply(this, arguments);
		this.gridId = options.gridId;
		this.addEventHandlers();
	};
	BX.Timeman.Component.Schedule.List.prototype = {
		__proto__: BX.Timeman.Component.BaseComponent.prototype,
		constructor: BX.Timeman.Component.Schedule.List,
		addEventHandlers: function ()
		{
			// todo delete this hack
			// it is here to prevent grid's title changing after filter apply
			BX.ajax.UpdatePageData = (function ()
			{
			});

			BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(function (event)
			{
				if (event.getEventId() === 'BX.Timeman.Schedule.Add::Success')
				{
					BX.Main.gridManager.reload(this.gridId);
				}
				else if (event.getEventId() === 'BX.Timeman.Schedule.Update::Success')
				{
					var scheduleData = event.getData()['schedule'];
					this.getRowCell(scheduleData.id, 'name').textContent = scheduleData.name;
					this.getRowCell(scheduleData.id, 'type').textContent = scheduleData.formattedType;
					this.getRowCell(scheduleData.id, 'period').textContent = scheduleData.formattedPeriod;
					this.getRowCell(scheduleData.id, 'user-count').textContent = scheduleData.userCount;
				}
			}.bind(this)));

			var gridTable = document.querySelector('[id^=' + this.gridId + ']');
			gridTable.classList.add('timeman-schedule-list');
		},
		getRowCell: function (id, name)
		{
			return document.querySelector('.main-grid-row[data-id="' + id + '"]' +
				' [data-role="' + name + '"]');
		},
		onDeleteSchedulesListClick: function ()
		{
			var deletingIds = this.getGridInstance().getRows().getSelectedIds();
			if (deletingIds.length <= 0)
			{
				return;
			}
			this.deleteButtonDisabled = true;
			BX.ajax.runAction(
				'timeman.schedule.deleteList',
				{
					data: {ids: deletingIds}
				}
			).then(
				function (response)
				{
					this.getGridInstance().removeSelected();
					this.deleteButtonDisabled = false;
				}.bind(this),
				function (response)
				{
					this.deleteButtonDisabled = false;
				}.bind(this));
		},
		getGridInstance: function ()
		{
			return this.gridId && BX.Main.gridManager.getById(this.gridId)
				? BX.Main.gridManager.getById(this.gridId).instance : null;
		},
		onDeleteScheduleClick: function (event, id, name)
		{
			if (!id)
			{
				return;
			}
			event.stopPropagation();
			event.preventDefault();
			if (this.deleteButtonDisabled)
			{
				return;
			}
			this.deleteButtonDisabled = true;

			if (!this.deleteSchedulePopup)
			{
				this.deleteSchedulePopup = {};
			}

			if (!this.deleteSchedulePopup.hasOwnProperty(id))
			{
				this.deleteSchedulePopup[id] = new BX.PopupWindow({
					id: 'tm-menu-confirm-delete-schedule-' + id,
					autoHide: true,
					closeByEsc: true,
					titleBar: BX.message('TM_SCHEDULE_DELETE_CONFIRM_TITLE'),
					content: BX.util.htmlspecialchars(BX.message('TM_SCHEDULE_DELETE_CONFIRM').replace('#SCHEDULE_NAME#', name)),
					buttons: [
						new BX.UI.Button({
							text: BX.message('TM_SCHEDULE_DELETE_CONFIRM_NO'),
							className: 'ui-btn ui-btn-danger',
							events: {
								click: function (id)
								{
									this.deleteButtonDisabled = false;
									this.deleteSchedulePopup[id].close();
								}.bind(this, id)
							}
						}),
						new BX.UI.Button({
							text: BX.message('TM_SCHEDULE_DELETE_CONFIRM_YES'),
							className: 'ui-btn ui-btn-success',
							events: {
								click: function (id)
								{
									this.deleteSchedulePopup[id].close();

									this.processDelete(id);
								}.bind(this, id)
							}
						})
					]
				});
			}

			this.deleteSchedulePopup[id].show();
		},
		processDelete: function (id)
		{
			BX.ajax.runAction(
				'timeman.schedule.delete',
				{
					data: {id: id}
				}
			).then(
				function (response)
				{
					this.deleteRowById(id);
					this.deleteButtonDisabled = false;
				}.bind(this),
				function (response)
				{
					this.deleteRowById(id);
					this.deleteButtonDisabled = false;
				}.bind(this));
		},
		deleteRowById: function (id)
		{
			if (this.getGridInstance() && this.getGridInstance().getRows().getById(id))
			{
				this.getGridInstance().getRows().getById(id).remove();
			}
		},
		reloadGrid: function ()
		{
			BX.Main.gridManager.reload(this.gridId);
		}
	};
})();