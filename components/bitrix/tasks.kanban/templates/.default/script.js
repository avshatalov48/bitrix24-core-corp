BX.namespace("Tasks.KanbanComponent");

BX.Tasks.KanbanComponent.enableCustomSort = function(event, item)
{
	if (BX.Tasks.KanbanComponent.openedCustomSort)
	{
		return;
	}

	BX.Tasks.KanbanComponent.sortMenuItem = item;

	item.params.forEach(function(paramsItem){
		paramsItem.params = BX.parseJSON(paramsItem.params);
		paramsItem.onclick = BX.Tasks.KanbanComponent.ClickSort;
		item.menuWindow.addMenuItem(paramsItem);
	});
	var sortDescItem = null;
	item.menuWindow.menuItems.forEach(function(menuItem){
		BX.removeClass(BX(menuItem.layout.item), "menu-popup-item-accept");
		if (
			menuItem.params
			&& typeof menuItem.params.order !== 'undefined'
			&&  menuItem.params.order === 'desc'
		)
		{
			sortDescItem = menuItem;
		}
	});
	BX.addClass(BX(item.layout.item), "menu-popup-item-accept");
	if (!sortDescItem)
	{
		return;
	}
	BX.addClass(BX(sortDescItem.layout.item), "menu-popup-item-accept");

	BX.ajax.runComponentAction('bitrix:tasks.kanban', 'setNewTaskOrder', {
		mode: 'class',
		data: {
			order: sortDescItem.params.order,
			params: ajaxParams
		},
	}).then(
		(response) => {
			const data = response.data;
			BX.onCustomEvent(this, 'onTaskSortChanged', [data]);
		},
	);

	BX.Tasks.KanbanComponent.openedCustomSort = true;
}

BX.Tasks.KanbanComponent.getMySortButton = function(event, item)
{
	if (typeof BX.Tasks.KanbanComponent.sortMenuItem !== 'undefined')
	{
		return BX.Tasks.KanbanComponent.sortMenuItem;
	}

	item.menuWindow.menuItems.forEach(function(menuItem){
		if (Array.isArray(menuItem.params))
		{
			BX.Tasks.KanbanComponent.sortMenuItem = menuItem;
		}
	});

	return BX.Tasks.KanbanComponent.sortMenuItem;
}

BX.Tasks.KanbanComponent.disableCustomSort = function(event, item)
{
	if (!BX.Tasks.KanbanComponent.openedCustomSort)
	{
		return;
	}
	var items = item.menuWindow.menuItems.slice(0);
	items.forEach(function(paramsItem){
		if (
			paramsItem.params
			&& typeof paramsItem.params.type !== 'undefined'
			&& paramsItem.params.type === 'sub'
		)
		{
			item.menuWindow.removeMenuItem(paramsItem.id);
		}
	});
	BX.Tasks.KanbanComponent.openedCustomSort = false;
}

BX.Tasks.KanbanComponent.ClickSort = function(event, item)
{
	var order = "desc";

	if (
		typeof item.params !== "undefined" &&
		typeof item.params.order !== "undefined"
	)
	{
		order = item.params.order;
	}

	if (BX.Tasks.KanbanComponent.openedCustomSort && order === 'actual')
	{
		BX.Tasks.KanbanComponent.disableCustomSort(event, item);
	}
	// refresh icons and save selected
	if (!BX.hasClass(BX(item.layout.item), "menu-popup-item-accept"))
	{
		var menuItems = item.menuWindow.menuItems;
		for (var i = 0, c = menuItems.length; i < c; i++)
		{
			BX.removeClass(BX(menuItems[i].layout.item), 'menu-popup-item-accept');
		}
		BX.addClass(BX(item.layout.item), "menu-popup-item-accept");
		if (order === 'asc' || order === 'desc')
		{
			var sortMenuItem = BX.Tasks.KanbanComponent.getMySortButton(event, item);
			sortMenuItem && BX.addClass(BX(sortMenuItem.layout.item), "menu-popup-item-accept");
		}

		BX.ajax.runComponentAction('bitrix:tasks.kanban', 'setNewTaskOrder', {
			mode: 'class',
			data: {
				order: order,
				params: ajaxParams,
			},
		}).then(
			(response) => {
				const data = response.data;
				BX.onCustomEvent(this, 'onTaskSortChanged', [data]);
			},
		);
	}
};

BX.Tasks.KanbanComponent.filterId = {};
BX.Tasks.KanbanComponent.defaultPresetId = {};

BX.Tasks.KanbanComponent.onReady = function()
{
	// sort-button is disabled
	BX.bind(BX("tasks-popupMenuOptions"), "click", BX.delegate(function()
	{
		if (BX.data(BX("tasks-popupMenuOptions"), "disabled") === true)
		{
			var tooltip = new BX.PopupWindow(
				"popupMenuOptionsDisabled",
				BX("tasks-popupMenuOptions"),
				{
					closeByEsc: true,
					angle: true,
					offsetLeft: 5,
					darkMode: true,
					autoHide: true,
					zIndex: 1000,
					content: BX.message("TASKS_KANBAN_DIABLE_SORT_TOOLTIP")
				}
			);
			tooltip.show();
		}
	}));

	BX.addCustomEvent('Tasks.TopMenu:onItem', function(roleId, url) {
		var filterManager = BX.Main.filterManager.getById(BX.Tasks.KanbanComponent.filterId);
		if (!filterManager)
		{
			alert('BX.Main.filterManager not initialised');
			return;
		}

		var fields = {
			preset_id: BX.Tasks.KanbanComponent.defaultPresetId,
			additional: {ROLEID: (roleId === 'view_all' ? 0 : roleId)}
		};
		var filterApi = filterManager.getApi();
		filterApi.setFilter(fields, {ROLE_TYPE: 'TASKS_ROLE_TYPE_' + (roleId === '' ? 'view_all' : roleId)});

		window.history.pushState(null, null, url);
	});

	BX.addCustomEvent('Tasks.Toolbar:onItem', function(event) {
		var data = event.getData();
		if (data.counter && data.counter.filter)
		{
			data.counter.filter.toggleByField({PROBLEM: data.counter.filterValue});
		}
	});
};

BX.addCustomEvent("SidePanel.Slider:onCloseByEsc", function(event) {
	var reg = /tasks\/task\/edit/;
	var str = event.getSlider().getUrl();
	if (reg.test(str) && !confirm(BX.message('TASKS_CLOSE_PAGE_CONFIRM')))
	{
		event.denyAction();
	}
});

BX.Tasks.KanbanComponent.TourGuideController = function(options)
{
	this.tours = options.tours;
	this.guide = null;

	this.initGuides(options);
}

BX.Tasks.KanbanComponent.TourGuideController.prototype = {
	initGuides: function(options)
	{
		var firstTimelineTaskCreation = this.tours.firstTimelineTaskCreation;
		var expiredTasksDeadlineChange = this.tours.expiredTasksDeadlineChange;

		if (options.viewMode === 'timeline' && firstTimelineTaskCreation.show)
		{
			this.guide = new BX.Tasks.KanbanComponent.TourGuideController.FirstTimelineTaskCreationTourGuide(options);
		}
		else if (expiredTasksDeadlineChange.show || expiredTasksDeadlineChange.backgroundCheck)
		{
			this.guide = new BX.Tasks.KanbanComponent.TourGuideController.ExpiredTasksDeadlineChangeTourGuide(options);
		}
	},

	getGuide: function()
	{
		return this.guide;
	}
};

BX.Tasks.KanbanComponent.TourGuideController.FirstTimelineTaskCreationTourGuide = function(options)
{
	this.viewMode = options.viewMode;
	this.tour = options.tours.firstTimelineTaskCreation;
	this.popupData = this.tour.popupData;

	this.ajaxActionPrefix = 'tasks.tourguide.firsttimelinetaskcreation.';

	this.start();
};

BX.Tasks.KanbanComponent.TourGuideController.FirstTimelineTaskCreationTourGuide.prototype = {
	start: function()
	{
		var eventCreate;
		var target;
		var lastItem = null;
		function setLastItem(item)
		{
			lastItem = item;
		}
		BX.addCustomEvent(Kanban, "Kanban.Grid:addItem", setLastItem);
		var setPopupParamFunction = function setPopupParam(node)
		{
			var popUp = this.guide.getPopup();
			var target = node;
			var targetWidth = target.offsetWidth;
			popUp.setMinWidth(targetWidth);
			popUp.setMaxWidth(targetWidth);
			popUp.setAngle({
				offset: (targetWidth / 2) - 11
			});
			this.guide.getPopup().getPopupContainer().style.left = BX.pos(target).left + "px";
		}.bind(this);

		this.guide = new BX.UI.Tour.Guide({
			steps: [
				{
					target: document.querySelectorAll(".main-kanban-column-add-item-button")[0],
					title: this.popupData[0].title,
					text: this.popupData[0].text,
					article: this.popupData[0].article,
					events: {
						onShow: function() {
							Kanban.getGridContainer().classList.add("main-kanban-aha");
							this.guide.getCurrentStep().getTarget().classList.add("--pulse");
							this.guide.getCurrentStep().getTarget().classList.add("--hover");
							setPopupParamFunction(this.guide.getCurrentStep().getTarget());
							eventCreate = function() {
								if(lastItem)
								{
									target = lastItem.getContainer();
									this.guide.getCurrentStep().setTarget(target);
									this.showNextStep();
								}
								BX.removeCustomEvent(Kanban, "Kanban.Grid:removeDraftItemByEsc", eventCreate);
								BX.removeCustomEvent(Kanban, "Kanban.Grid:closeDraftItem", eventCreate);

							}.bind(this);
							BX.addCustomEvent(Kanban, "Kanban.Grid:removeDraftItemByEsc", eventCreate);
							BX.addCustomEvent(Kanban, "Kanban.Grid:closeDraftItem", eventCreate);
						}.bind(this),
						onClose: function() {
							Kanban.getGridContainer().classList.remove("main-kanban-aha");
							this.guide.getCurrentStep().getTarget().classList.remove("--pulse");
							this.guide.getCurrentStep().getTarget().classList.remove("--hover");
						}.bind(this)
					}
				},
				{
					target: null,
					title: this.popupData[1].title,
					text: this.popupData[1].text,
					events: {
						onShow: function() {
							setPopupParamFunction(this.guide.getCurrentStep().getTarget());
							lastItem.animateAha();
							lastItem.getColumn().getContainer().classList.add("main-kanban-column-aha");
							var columns = Kanban.getColumns();
							BX.addCustomEvent(Kanban, "Kanban.Grid:onItemDragStart", function() {
								lastItem.getColumn().getContainer().classList.remove("main-kanban-column-aha");
								lastItem.unsetAnimateAha();
								Kanban.offAhaMode();
								this.guide.close();
								for (var i = 0; i < columns.length; i++)
								{
									if(	lastItem.getColumn() !== columns[i]
										&& columns[i].getType() !== "PERIOD1" )
									{
											columns[i].onAhaMode();
											columns[i].getContainer().classList.add("main-kanban-column-aha");
									}
								}
							}.bind(this));
							BX.addCustomEvent(Kanban, "Kanban.Grid:onItemDragStop", function(item) {
								for (var i = 0; i < columns.length; i++)
								{
									if( lastItem.getColumn() !== columns[i]
										&& columns[i].getType() !== "PERIOD1" )
									{
										columns[i].offAhaMode();
										columns[i].getContainer().classList.remove("main-kanban-column-aha");
									}
								}

								var prevColumn = item.getColumn();

								setTimeout(function() {
									if(prevColumn !== item.getColumn())
									{
										BX.ajax.runAction(this.ajaxActionPrefix + 'finish');
									}
								}.bind(this));
							}.bind(this));
						}.bind(this),
						onClose: function() {
							Kanban.offAhaMode();
							if(!lastItem)
							{
								return;
							}
							lastItem.unsetAnimateAha();
							lastItem.getColumn().getContainer().classList.remove("main-kanban-column-aha");
						}
					}
				}
			],
			onEvents: true
		});

		this.showNextStep();
	},

	markShowedStep: function(step)
	{
		BX.ajax.runAction(this.ajaxActionPrefix + 'markShowedStep', {
			analyticsLabel: {
				viewMode: this.viewMode,
				step: step
			}
		});
	},

	showNextStep: function()
	{
		setTimeout(function() {
			this.guide.showNextStep();
			this.markShowedStep(this.getCurrentStepIndex());
		}.bind(this), 500);
	},

	getCurrentStepIndex: function()
	{
		return this.guide.currentStepIndex;
	}
};

BX.Tasks.KanbanComponent.TourGuideController.ExpiredTasksDeadlineChangeTourGuide = function(options)
{
	this.userId = options.userId;
	this.viewMode = options.viewMode;
	this.tour = options.tours.expiredTasksDeadlineChange;
	this.popupData = this.tour.popupData
	this.counterToCheck = this.tour.counterToCheck;

	this.itemId = 0;
	this.calendarPopup = 0;
	this.isStopped = false;
	this.ajaxActionPrefix = 'tasks.tourguide.expiredtasksdeadlinechange.';

	if (this.tour.show)
	{
		this.start();
	}
	else if (this.tour.backgroundCheck)
	{
		this.isPullListening = true;
	}

	this.bindEvents();
}

BX.Tasks.KanbanComponent.TourGuideController.ExpiredTasksDeadlineChangeTourGuide.prototype = {
	bindEvents: function()
	{
		var eventHandlers = {
			user_counter: this.onUserCounter.bind(this),
		};
		BX.addCustomEvent('onPullEvent-tasks', function(command, params) {
			if (eventHandlers[command])
			{
				eventHandlers[command].apply(this, [params]);
			}
		}.bind(this));

		BX.addCustomEvent('UI.Tour.Guide:onPopupClose', function() {
			this.stop();
		}.bind(this));

		BX.addCustomEvent('UI.Tour.Guide:onFinish', function(event) {
			if (event.getData().guide === this.guide && this.getCurrentStepIndex() === 0)
			{
				BX.addCustomEvent(Kanban, 'Kanban.Grid:onRender', BX.proxy(this.onExpiredCounterKanbanReloaded, this));
			}
		}.bind(this));
	},

	onUserCounter: function(data)
	{
		if (!this.isPullListening || this.userId !== Number(data.userId))
		{
			return;
		}

		var newCounter = Number(data[0].view_role_originator.expired) + Number(data[0].view_role_responsible.expired);
		if (newCounter >= Number(this.counterToCheck))
		{
			this.isPullListening = false;

			BX.ajax.runAction(this.ajaxActionPrefix + 'proceed', {
				analyticsLabel: {
					viewMode: this.viewMode
				}
			}).then(function(result) {
				if (result.data)
				{
					this.start();
				}
			}.bind(this));
		}
	},

	start: function()
	{
		this.guide = new BX.UI.Tour.Guide({
			steps: [
				{
					target: document.querySelector('.tasks-counters--item-counter'),
					title: this.popupData[0].title,
					text: this.popupData[0].text
				}
			],
			onEvents: true
		});

		this.showNextStep();
	},

	markShowedStep: function(step)
	{
		BX.ajax.runAction(this.ajaxActionPrefix + 'markShowedStep', {
			analyticsLabel: {
				viewMode: this.viewMode,
				step: step
			}
		});
	},

	onExpiredCounterKanbanReloaded: function()
	{
		BX.removeCustomEvent(Kanban, 'Kanban.Grid:onRender', BX.proxy(this.onExpiredCounterKanbanReloaded, this));

		var selector = '.tasks-kanban-item-deadline.tasks-kanban-item-pointer';
		var target = Kanban.getRenderToContainer().querySelector(selector);

		if (!this.itemId && target)
		{
			this.itemId = Number(BX.data(target.closest('.main-kanban-item'), 'id'));
		}

		if (this.itemId > 0 && target)
		{
			this.guide.steps.push(
				new BX.UI.Tour.Step({
					target: target,
					title: this.popupData[1].title,
					text: this.popupData[1].text
				})
			);
			this.showNextStep();

			BX.addCustomEvent(Kanban, 'Tasks.Kanban.Item:deadlineChangeClick', BX.proxy(this.onDeadlineChangeClick, this));
		}
	},

	onDeadlineChangeClick: function(event)
	{
		var eventData = event.getData();

		if (this.isCorrectItem(eventData.itemId) && !this.getIsStopped())
		{
			this.setCalendarPopup(eventData.calendar.popup);
		}
	},

	setCalendarPopup: function(popup)
	{
		if (!this.calendarPopup && this.getCurrentStepIndex() === 1)
		{
			this.calendarPopup = popup;

			BX.addCustomEvent(this.calendarPopup, 'onPopupAfterClose', BX.proxy(this.onCalendarPopupClose, this));
		}
	},

	onCalendarPopupClose: function()
	{
		BX.removeCustomEvent(this.calendarPopup, 'onPopupAfterClose', BX.proxy(this.onCalendarPopupClose, this));

		setTimeout(function() {
			BX.removeCustomEvent(Kanban, 'Tasks.Kanban.Item:deadlineChanged', BX.proxy(this.onDeadlineChanged, this));
		}.bind(this), 500);

		BX.addCustomEvent(Kanban, 'Tasks.Kanban.Item:deadlineChanged', BX.proxy(this.onDeadlineChanged, this));
	},

	onDeadlineChanged: function(event)
	{
		var eventData = event.getData();
		var currentTime = new Date();

		if (eventData.value.getTime() > currentTime.getTime())
		{
			BX.addCustomEvent(Kanban, 'Kanban.Grid:onItemRemoved', BX.proxy(this.onItemRemoved, this));
		}
	},

	onItemRemoved: function(event)
	{
		var eventData = event.getData();
		var itemId = eventData.itemId;

		if (!this.isCorrectItem(itemId))
		{
			return;
		}

		BX.removeCustomEvent(Kanban, 'Kanban.Grid:onItemRemoved', BX.proxy(this.onItemRemoved, this));

		if (Kanban.getItem(itemId) === null)
		{
			this.guide.steps.push(
				new BX.UI.Tour.Step({
					title: this.popupData[2].title,
					text: this.popupData[2].text,
					buttons: [
						{
							text: this.popupData[2].buttons[0],
							event: function() {
								this.stop();
							}.bind(this)
						}
					]
				})
			);
			this.showNextStep();
		}
	},

	showNextStep: function()
	{
		if (this.isStopped)
		{
			return;
		}

		setTimeout(function() {
			this.guide.showNextStep();
			this.markShowedStep(this.getCurrentStepIndex());
		}.bind(this), 500);
	},

	getCurrentStepIndex: function()
	{
		return this.guide.currentStepIndex;
	},

	isCorrectItem: function(itemId)
	{
		return Number(this.itemId) === Number(itemId);
	},

	getIsStopped: function()
	{
		return this.isStopped;
	},

	stop: function()
	{
		this.isStopped = true;
		this.guide.close();
	}
};