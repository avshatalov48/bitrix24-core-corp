;(function ()
{
	BX.namespace('BX.Timeman.Component.Worktime');
	BX.Timeman.Component.Worktime.Stats = function (options)
	{
		this.isSlider = options.isSlider;
		this.gridId = options.gridId;
		options.container = this.container = document.querySelector(this.isSlider ? 'body' : '#content-table');
		BX.Timeman.Component.BaseComponent.apply(this, arguments);
		this.editSchedulesPopupToggle = this.selectOneByRole('worktime-grid-config-btn');
		this.gridOptionsToggle = this.selectOneByRole('grid-options');
		this.gridSettings = options.gridSettings;
		this.schedulesData = options.schedulesData;
		this.canDeleteSchedule = options.canDeleteSchedule;
		this.canUpdateSchedule = options.canUpdateSchedule;
		this.shiftedSchedules = options.shiftedSchedules;
		this.shiftplansBtn = this.selectOneByRole('shift-plans-btn');

		this.gridOptions = options.gridOptions;
		this.showViolationsCommonName = options.showViolationsCommonName;
		this.showViolationsPersonalName = options.showViolationsPersonalName;
		this.showStatsColumnsName = options.showStatsColumnsName;
		this.showStartFinishName = options.showStartFinishName;
		this.saveSelectedOption(this.showStartFinishName);
		this.saveSelectedOption(this.showStatsColumnsName);
		this.saveSelectedOption(this.showViolationsPersonalName);
		this.saveSelectedOption(this.showViolationsCommonName);

		this.applyGridOptions();
		this.addEventHandlers();
	};
	BX.Timeman.Component.Worktime.Stats.prototype = {
		__proto__: BX.Timeman.Component.BaseComponent.prototype,
		constructor: BX.Timeman.Component.Worktime.Stats,
		addEventHandlers: function ()
		{
			this.onGridUpdated();
			BX.bind(this.shiftplansBtn, 'click', BX.delegate(this.onShiftPlansClick, this));
			BX.bind(this.gridOptionsToggle, 'click', BX.delegate(this.onGridOptionsToggleClick, this));
			BX.bind(this.editSchedulesPopupToggle, 'click', BX.delegate(this.onEditSchedulesToggleClick, this));
			BX.addCustomEvent('Grid::updated', this.onGridUpdated.bind(this));
			BX.addCustomEvent('Grid::beforeRequest', BX.delegate(function (grid, event)
				{
					if (event.url === '')
					{
						event.url = window.location.toString();
						return;
					}

					var currentStatsColumn = window.location.href.match(new RegExp(this.showStatsColumnsName + '=([YN])'));
					if (currentStatsColumn !== null
						&& (currentStatsColumn[1] === 'Y' || currentStatsColumn[1] === 'N')
						&& event.url.match(new RegExp(this.showStatsColumnsName + '=([YN])')) !== null)
					{
						event.url = event.url.replace(
							this.showStatsColumnsName + '=' + '=([YN])',
							this.showStatsColumnsName + '=' + currentStatsColumn[1]
						);
					}
					var currentStartFinish = window.location.href.match(new RegExp(this.showStartFinishName + '=([YN])'));
					if (currentStartFinish !== null
						&& (currentStartFinish[1] === 'Y' || currentStartFinish[1] === 'N')
						&& event.url.match(new RegExp(this.showStartFinishName + '=([YN])')) !== null)
					{
						event.url = event.url.replace(
							this.showStartFinishName + '=' + '=([YN])',
							this.showStartFinishName + '=' + currentStartFinish[1]
						);
					}
				}.bind(this))
			);
			BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(function (event)
			{
				if (event.getEventId() === 'BX.Timeman.Schedule.Update::Success')
				{
					var scheduleUpdatedData = event.getData()['schedule'];
					if (!scheduleUpdatedData)
					{
						return;
					}
					for (var i = 0; i < this.schedulesData.length; i++)
					{
						if (parseInt(this.schedulesData[i].id) === parseInt(scheduleUpdatedData.id))
						{
							if (this.schedulesPopup && this.schedulesPopup.getMenuItem(scheduleUpdatedData.id.toString())
								&& this.schedulesPopup.getMenuItem(scheduleUpdatedData.id.toString()).setText
								&& typeof this.schedulesPopup.getMenuItem(scheduleUpdatedData.id.toString()).setText === "function")
							{
								this.schedulesPopup.getMenuItem(scheduleUpdatedData.id.toString()).setText(BX.util.htmlspecialchars(scheduleUpdatedData.name));
							}
							this.schedulesData[i].name = scheduleUpdatedData.name;
							break;
						}
					}
				}
				else if (event.getEventId() === 'BX.Timeman.Record.Approve::Success')
				{
					var record = event.getData()['record'];
					if (!record || !this.selectOneByRole('shift-records-container'))
					{
						return;
					}
					var approvedRecordCell = this.selectOneByRole('shift-records-container').querySelector('[data-role="worktime-record-cell"][data-id="' + record.id + '"]');
					if (!approvedRecordCell)
					{
						return;
					}

					var shiftBlock = document.createElement('div');
					shiftBlock.innerHTML = record.recordCellHtml;
					var parentNode = approvedRecordCell.querySelector('[data-shift-block="true"]').parentNode;
					if (parentNode && shiftBlock.querySelector('[data-shift-block="true"]'))
					{
						parentNode.replaceChild(shiftBlock.querySelector('[data-shift-block="true"]'), approvedRecordCell.querySelector('[data-shift-block="true"]'));
					}
					this.initHints();
					this.applyGridOptions();
				}
				else if (event.getEventId() === 'BX.Timeman.Schedule.Add::Success')
				{
					var scheduleData = event.getData()['schedule'];
					if (scheduleData)
					{
						if (this.schedulesPopup)
						{
							this.schedulesPopup.addMenuItem(this.createScheduleMenuItem(scheduleData));
						}
						if (!this.schedulesData)
						{
							this.schedulesData = [];
						}
						this.schedulesData.push({id: scheduleData['id'].toString(), name: scheduleData['name']});
					}
				}
			}.bind(this)));
		},
		setCookie: function (name, value, options)
		{
			options = options || {path: window.location.pathname};
			var expires = options.expires;
			if (typeof (expires) == "number" && expires)
			{
				var currentDate = new Date();
				currentDate.setTime(currentDate.getTime() + expires * 1000);
				expires = options.expires = currentDate;
			}
			if (expires && expires.toUTCString)
			{
				options.expires = expires.toUTCString();
			}
			value = encodeURIComponent(value);
			var updatedCookie = name + "=" + value;
			for (var propertyName in options)
			{
				if (!options.hasOwnProperty(propertyName))
				{
					continue;
				}
				updatedCookie += "; " + propertyName;
				var propertyValue = options[propertyName];
				if (propertyValue !== true)
				{
					updatedCookie += "=" + propertyValue;
				}
			}
			document.cookie = updatedCookie;
			return true;
		},
		onShiftPlansClick: function (event)
		{
			if (!this.shiftPlansPopup)
			{
				var items = [];
				for (var i = 0; i < this.shiftedSchedules.length; i++)
				{
					var schedule = this.shiftedSchedules[i];
					items.push({
						text: BX.util.htmlspecialchars(schedule.NAME),
						id: BX.util.htmlspecialchars(schedule.ID),
						onclick: function (schedule)
						{
							if (this.shiftPlansPopup)
							{
								this.shiftPlansPopup.close();
							}
							var urlSchEdit = BX.util.add_url_param("/bitrix/components/bitrix/timeman.schedule.shiftplan/slider.php", {SCHEDULE_ID: schedule.ID});
							BX.SidePanel.Instance.open(urlSchEdit, {width: 1400});
						}.bind(this, schedule)
					});
				}
				this.shiftPlansPopup = new BX.PopupMenuWindow({
					id: 'tmWorktimeStatsShiftedSchedulesPopup',
					bindElement: event.currentTarget,
					items: items
				});
			}

			this.shiftPlansPopup.show();
		},
		onGridUpdated: function ()
		{
			this.initHints();
			this.applyGridOptions();
		},
		initHints: function ()
		{
			BX.UI.Hint.init(this.container);
		},
		createScheduleMenuItem: function (schedule)
		{
			var items = [];
			var scheduleId = schedule.id.toString();
			var actionText = BX.message('TM_SCHEDULE_LIST_ACTION_READ');
			if (this.canUpdateSchedule)
			{
				actionText = BX.message('TM_SCHEDULE_LIST_ACTION_EDIT');
			}
			items.push({
				text: BX.util.htmlspecialchars(actionText),
				onclick: function (scheduleId)
				{
					this.onEditScheduleClick(scheduleId);
				}.bind(this, scheduleId)
			});
			if (this.canDeleteSchedule)
			{
				items.push({
					text: BX.util.htmlspecialchars(BX.message('TM_SCHEDULE_LIST_ACTION_DELETE')),
					onclick: function (schedule)
					{
						this.onDeleteScheduleClick(schedule);
					}.bind(this, schedule)
				});
			}

			return {
				text: BX.util.htmlspecialchars(schedule.name),
				id: BX.util.htmlspecialchars(scheduleId),
				onclick: function (scheduleId)
				{
					this.onEditScheduleClick(scheduleId);
				}.bind(this, scheduleId),
				items: items
			};
		},
		buildEditSchedulesPopup: function (event)
		{
			var items = [];
			for (var i = 0; i < this.schedulesData.length; i++)
			{
				var schedule = this.schedulesData[i];
				items.push(this.createScheduleMenuItem(schedule));
			}

			return BX.PopupMenu.create({
				items: items,
				maxHeight: 450,
				id: 'tmWorktimeGridOptionsEditSchedulesMenu',
				bindElement: event.currentTarget,
				angle: true,
				closeByEsc: true,
				autoHide: true
			});
		},
		onEditScheduleClick: function (scheduleId)
		{
			this.schedulesPopup.close();
			var url = BX.util.add_url_param('/bitrix/components/bitrix/timeman.schedule.edit/slider.php', {SCHEDULE_ID: scheduleId});
			window.top.BX.SidePanel.Instance.open(url, {width: 1200, cacheable: false});
		},
		onDeleteScheduleClick: function (schedule)
		{
			this.schedulesPopup.close();

			if (!this.deleteSchedulePopup)
			{
				this.deleteSchedulePopup = {};
			}
			this.deleteSchedulePopup[schedule.id] = new BX.PopupWindow({
				id: 'tm-menu-confirm-delete-schedule-' + BX.util.htmlspecialchars(schedule.id),
				autoHide: true,
				draggable: true,
				closeByEsc: true,
				titleBar: BX.message('TM_SCHEDULE_DELETE_CONFIRM_TITLE'),
				content: BX.util.htmlspecialchars(BX.message('TM_SCHEDULE_DELETE_CONFIRM').replace('#SCHEDULE_NAME#', (schedule.name))),
				buttons: [
					new BX.PopupWindowButtonLink({
						text: BX.message('TM_SCHEDULE_DELETE_CONFIRM_NO'),
						className: 'ui-btn ui-btn-danger',
						events: {
							click: function (schedule)
							{
								this.deleteSchedulePopup[schedule.id].close();
							}.bind(this, schedule)
						}
					}),
					new BX.PopupWindowButton({
						text: BX.message('TM_SCHEDULE_DELETE_CONFIRM_YES'),
						className: 'ui-btn ui-btn-success',
						events: {
							click: function (schedule)
							{
								this.deleteSchedulePopup[schedule.id].close();

								BX.ajax.runAction(
									'timeman.schedule.delete',
									{
										data: {id: schedule.id}
									}
								).then(
									function (schedule)
									{
										this.onAfterScheduleDelete(schedule);
										if (!this.schedulesPopup)
										{
											return;
										}
										this.schedulesPopup.removeMenuItem(schedule.id.toString());
										for (var i = 0; i < this.schedulesData.length; i++)
										{
											if (this.schedulesData[i].id == schedule.id.toString())
											{
												this.schedulesData.splice(i, 1);
												break;
											}
										}
									}.bind(this, schedule),
									function (response)
									{
									}.bind(this));
							}.bind(this, schedule)
						}
					})
				]
			});
			this.deleteSchedulePopup[schedule.id].show();
		},
		onAfterScheduleDelete: function (schedule)
		{
			if (!schedule || !schedule.id)
			{
				return;
			}
			for (var i = 0; i < this.shiftedSchedules.length; i++)
			{
				if (parseInt(this.shiftedSchedules[i].ID) === parseInt(schedule.id))
				{
					this.shiftedSchedules.splice(i, 1);
				}
			}
			for (var i = 0; i < this.schedulesData.length; i++)
			{
				if (parseInt(this.schedulesData[i].id) === parseInt(schedule.id))
				{
					this.schedulesData.splice(i, 1);
				}
			}
			if (this.shiftedSchedules.length === 0)
			{
				this.hideElement(this.shiftplansBtn);
			}
			if (this.schedulesData.length === 0)
			{
				this.hideElement(this.editSchedulesPopupToggle);
			}
		},
		onEditSchedulesToggleClick: function (event)
		{
			event.stopPropagation();
			event.preventDefault();
			if (!this.schedulesData.length)
			{
				return;
			}
			if (!this.schedulesPopup)
			{
				this.schedulesPopup = this.buildEditSchedulesPopup(event);
			}

			this.schedulesPopup.show();
		},
		onGridOptionsToggleClick: function (event)
		{
			this._settingsPopup = this.buildSettingsPopup(event);
			this._settingsPopup.show();
		},
		toggleSelectedItemInMenuPopup: function (selectedItem)
		{
			if (this._settingsPopup && this._settingsPopup.layout)
			{
				var selected = this.getMenuItemSelected(selectedItem.id);
				if (selected)
				{
					selected.classList.toggle('menu-popup-item-take');
					selected.classList.toggle('menu-popup-no-icon');
				}
			}
		},
		getMenuItemSelected: function (selectedItemId)
		{
			var item = this._settingsPopup.layout.menuContainer.querySelector('.js-id-' + selectedItemId);
			if (!item)
			{
				var menuItems = this._settingsPopup.getMenuItems();
				for (var i = 0; i < menuItems.length; i++)
				{
					if (this._settingsPopup.getMenuItem(menuItems[i].id)
						&& this._settingsPopup.getMenuItem(menuItems[i].id).getSubMenu()
						&& this._settingsPopup.getMenuItem(menuItems[i].id).getSubMenu().getMenuItem(selectedItemId)
						&& this._settingsPopup.getMenuItem(menuItems[i].id).getSubMenu().getMenuItem(selectedItemId).menuWindow)
					{
						return this._settingsPopup.getMenuItem(menuItems[i].id).getSubMenu().getMenuItem(selectedItemId).menuWindow.layout.menuContainer.querySelector('.js-id-' + selectedItemId);
					}
				}
			}
		},
		applyGridOptions: function ()
		{
			var startFinishBlocks = this.selectAllByRole('start-end');
			for (var i = 0; i < startFinishBlocks.length; i++)
			{
				if (this.getGridOptions()[this.showStartFinishName])
				{
					this.showElement(startFinishBlocks[i])
				}
				else
				{
					this.hideElement(startFinishBlocks[i])
				}
			}
			var blocks = this.selectAllByRole('violation-icon');
			for (var i = 0; i < blocks.length; i++)
			{
				if (
					(blocks[i].dataset.type === 'common' && this.gridOptions[this.showViolationsCommonName])
					||
					(blocks[i].dataset.type === 'personal' && this.gridOptions[this.showViolationsPersonalName])
				)
				{
					this.showElement(blocks[i]);
				}
				else
				{
					this.hideElement(blocks[i]);
				}
			}
			var percentageStats = this.selectAllByRole('violation-percentage-stat');
			for (var j = 0; j < percentageStats.length; j++)
			{
				if (
					(this.gridOptions[this.showViolationsPersonalName] && percentageStats[j].dataset.type === 'personal')
					||
					(this.gridOptions[this.showViolationsCommonName] && percentageStats[j].dataset.type === 'common')
				)
				{
					this.showElement(percentageStats[j]);
				}
				else
				{
					this.hideElement(percentageStats[j]);
				}
			}
			var recordCells = this.selectAllByRole('worktime-record-cell');
			var isIndividual = this.gridOptions[this.showViolationsPersonalName] ? 'Y' : 'N';
			for (var rcindex = 0; rcindex < recordCells.length; rcindex++)
			{
				recordCells[rcindex].href = this.addParamsToUrl(recordCells[rcindex].dataset.individualParam, isIndividual, recordCells[rcindex].href);
			}
		},
		_onGridOptionsMenuItemClick: function (selectedItem)
		{
			this.toggleSelectedItemInMenuPopup(selectedItem);
			this.gridOptions[selectedItem.id] = !this.gridOptions[selectedItem.id];
			this.saveSelectedOption(selectedItem.id);
			this.updateNavigationHref(this.selectOneByRole('tm-navigation-today'));
			var navsArrows = this.selectAllByRole('navigation-period');
			for (var i = 0; i < navsArrows.length; i++)
			{
				this.updateNavigationHref(navsArrows[i]);
			}

			if (selectedItem.id === this.showStatsColumnsName)
			{
				this.reloadGrid();
			}
			else
			{
				this.applyGridOptions()
			}
		},
		updateNavigationHref: function (item)
		{
			if (!item)
			{
				return;
			}
			var newStartValue = null;
			if (window.location.href.match(new RegExp(this.showStartFinishName + '=([YN])')) !== null)
			{
				newStartValue = window.location.href.match(new RegExp(this.showStartFinishName + '=([YN])'))[1];
			}
			var statsValue = null;
			if (window.location.href.match(new RegExp(this.showStatsColumnsName + '=([YN])')) !== null)
			{
				statsValue = window.location.href.match(new RegExp(this.showStatsColumnsName + '=([YN])'))[1];
			}
			item.href = item.href.replace(
				this.showStartFinishName + '=' + (newStartValue === 'Y' ? 'N' : 'Y'),
				this.showStartFinishName + '=' + newStartValue
			);
			item.href = item.href.replace(
				this.sst + '=' + (statsValue === 'Y' ? 'N' : 'Y'),
				this.showStatsColumnsName + '=' + statsValue
			);
		},
		getGridOptions: function (name)
		{
			return name ? this.gridOptions[name] : this.gridOptions;
		},
		saveSelectedOption: function (name)
		{
			var value = this.gridOptions[name];
			var url = this.addParamsToUrl(name, (value ? 'Y' : 'N'));
			if (url !== location.href)
			{
				window.history.replaceState({}, '', url);
			}
			this.saveLocalGridOptions(name, value);
		},
		addParamsToUrl: function (name, value, url)
		{
			var newParam = name + '=' + value;
			if (!url)
			{
				url = location.href;
			}
			if (url.includes(name))
			{
				url = url.replace(
					new RegExp(name + '=[^&]*(&*)', 'gi'),
					newParam + '$1'
				);
			}
			else
			{
				url = url.includes('?') ? (url + '&' + newParam) : (url + '?' + newParam);
			}
			return url;
		},
		getCookie: function (name)
		{
			var matches = document.cookie.match(new RegExp(
				"(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
			));

			return matches ? decodeURIComponent(matches[1]) : null;
		},
		saveLocalGridOptions: function (name, value)
		{
			var oldValue = this.getCookie(name);
			if (oldValue === value)
			{
				return;
			}
			this.setCookie(name, value);
		},
		buildSettingsMenuItem: function (settings)
		{
			var className = this.getGridOptions(settings.id) ? 'menu-popup-item-take' : 'menu-popup-no-icon';
			return {
				text: BX.util.htmlspecialchars(settings.name),
				id: BX.util.htmlspecialchars(settings.id),
				className: className + ' js-id-' + BX.util.htmlspecialchars(settings.id),
				onclick: function (settings)
				{
					this._settingsPopup.close();
					this._onGridOptionsMenuItemClick.bind(this, settings)();
					if (this.getGridOptions(this.showViolationsPersonalName) && this.getGridOptions(this.showViolationsCommonName))
					{
						var offId = settings.id === this.showViolationsPersonalName ? this.showViolationsCommonName : this.showViolationsPersonalName;
						for (var i = 0; i < this.gridSettings.length; i++)
						{
							if (this.gridSettings[i].items && this.gridSettings[i].items.length)
							{
								for (var j = 0; j < this.gridSettings[i].items.length; j++)
								{
									if (this.gridSettings[i].items[j].id === offId)
									{
										this._onGridOptionsMenuItemClick.bind(this, this.gridSettings[i].items[j])();
										break;
									}
								}
							}
						}
					}
				}.bind(this, settings)
			};
		},
		buildSettingsPopup: function (event)
		{
			return BX.PopupMenu.create({
				items: this.buildItems(),
				id: 'tmWorktimeGridOptionsSettings' + BX.util.getRandomString(20),
				bindElement: event.currentTarget,
				offsetLeft: -60,
				angle: false,
				closeByEsc: true,
				autoHide: true
			});
		},
		buildItems: function ()
		{
			var items = [];
			for (var i = 0; i < this.gridSettings.length; i++)
			{
				var settings = this.gridSettings[i];
				var options = {};
				if (!settings.items)
				{
					options = this.buildSettingsMenuItem(settings);
				}
				else
				{
					options = {
						text: BX.util.htmlspecialchars(settings.name),
						id: BX.util.htmlspecialchars(settings.id),
						className: 'menu-popup-no-icon' + ' js-id-' + BX.util.htmlspecialchars(settings.id),
						items: []
					};
					for (var j = 0; j < settings.items.length; j++)
					{
						options.items.push(this.buildSettingsMenuItem(settings.items[j]));
					}
				}
				items.push(options);
			}
			return items;
		},
		reloadGrid: function ()
		{
			BX.Main.gridManager.reload(this.gridId, location.href);
		}
	};
})();