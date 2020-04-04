;(function ()
{
	BX.namespace('BX.Timeman.Component.Schedule.List');
	BX.Timeman.Component.Schedule.List = function (options)
	{
		this.gridId = options.gridId;
		this.scheduleCreateBtn = document.querySelector('[data-role="timeman-add-schedule-btn"]');
		this.addEventHandlers();
	};
	BX.Timeman.Component.Schedule.List.prototype = {
		addEventHandlers: function ()
		{
			// todo delete this hack
			// it is here to prevent grid's title changing after filter apply
			BX.ajax.UpdatePageData = (function ()
			{
			});

			BX.bind(this.scheduleCreateBtn, 'click', BX.delegate(this.onScheduleCreateBtnClick, this));
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
			BX.bind(gridTable, 'click', BX.delegate(this.onGridTableClick, this));
		},
		onGridTableClick: function (e)
		{
			var target = e.target;
			if (target.dataset && target.dataset.role && target.dataset.role === 'name')
			{
				this.onScheduleNameClick(e)
			}
		},
		getRowCell: function (id, name)
		{
			return document.querySelector('.main-grid-row[data-id="' + id + '"]' +
				' [data-role="' + name + '"]');
		},
		onShowShiftPlanClick: function (event, id)
		{
			event.stopPropagation();
			event.preventDefault();
			if (id)
			{
				var urlSchEdit = BX.util.add_url_param("/bitrix/components/bitrix/timeman.schedule.shiftplan/slider.php", {SCHEDULE_ID: id});
				BX.SidePanel.Instance.open(urlSchEdit, {width: 1400});
			}
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
		onScheduleNameClick: function (event)
		{
			event.stopPropagation();
			event.preventDefault();
			var cell = BX.findParent(event.target, {className: 'main-grid-row'});
			var id = cell ? cell.dataset.id : null;
			if (id)
			{
				var urlSchEdit = BX.util.add_url_param("/bitrix/components/bitrix/timeman.schedule.edit/slider.php", {SCHEDULE_ID: id});
				BX.SidePanel.Instance.open(urlSchEdit, {width: 1200, cacheable: false});
			}
		},
		onScheduleCreateBtnClick: function (event)
		{
			event.stopPropagation();
			event.preventDefault();
			BX.SidePanel.Instance.open('/bitrix/components/bitrix/timeman.schedule.edit/slider.php', {
				width: 1200,
				cacheable: false
			});
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
			this.deleteSchedulePopup[id] = new BX.PopupWindow({
				id: 'tm-menu-confirm-delete-schedule-' + id,
				autoHide: true,
				closeByEsc: true,
				titleBar: BX.message('TM_SCHEDULE_DELETE_CONFIRM_TITLE'),
				content: BX.util.htmlspecialchars(BX.message('TM_SCHEDULE_DELETE_CONFIRM').replace('#SCHEDULE_NAME#', name)),
				buttons: [
					new BX.PopupWindowButtonLink({
						text: BX.message('TM_SCHEDULE_DELETE_CONFIRM_NO'),
						className: 'popup-window-button-link-cancel',
						events: {
							click: function (id)
							{
								this.deleteSchedulePopup[id].close();
							}.bind(this, id)
						}
					}),
					new BX.PopupWindowButton({
						text: BX.message('TM_SCHEDULE_DELETE_CONFIRM_YES'),
						className: 'popup-window-button-accept',
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
			this.deleteSchedulePopup[id].show();
		},
		processDelete: function(id)
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
		},
		onEditScheduleClick: function (event, id)
		{
			event.stopPropagation();
			event.preventDefault();
			if (id)
			{
				var urlSchEdit = BX.util.add_url_param("/bitrix/components/bitrix/timeman.schedule.edit/slider.php", {SCHEDULE_ID: id});
				BX.SidePanel.Instance.open(urlSchEdit, {width: 1200});
			}
		}
	};
})();