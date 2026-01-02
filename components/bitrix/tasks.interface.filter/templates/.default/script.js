/* eslint-disable */
if (typeof(BX.FilterEntitySelector) === "undefined")
{
	BX.FilterEntitySelector = function ()
	{
		this._id = "";
		this._settings = {};
		this._fieldId = "";
		this._control = null;
		this._selector = null;

		this._inputKeyPressHandler = BX.delegate(this.keypress, this);
	};

	BX.FilterEntitySelector.prototype =
		{
			initialize: function (id, settings)
			{
				this._id = id;
				this._settings = settings ? settings : {};
				this._fieldId = this.getSetting("fieldId", "");

				BX.addCustomEvent(window, "BX.Main.Filter:customEntityFocus", BX.delegate(this.onCustomEntitySelectorOpen, this));
				BX.addCustomEvent(window, "BX.Main.Filter:customEntityBlur", BX.delegate(this.onCustomEntitySelectorClose, this));

			},
			getId: function ()
			{
				return this._id;
			},
			getSetting: function (name, defaultval)
			{
				return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
			},
			keypress: function (e)
			{
				//e.target.value
			},
			open: function (field, query)
			{
				this._selector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
					scope: field,
					id: this.getId() + "-selector",
					mode: this.getSetting("mode"),
					query: false,
					useSearch: true,
					useAdd: false,
					parent: this,
					popupOffsetTop: 5,
					popupOffsetLeft: 40
				});
				this._selector.bindEvent("item-selected", BX.delegate(function (data)
				{
					this._control.setData(BX.util.htmlspecialcharsback(data.nameFormatted), data.id);
					if (!this.getSetting("multi"))
					{
						this._selector.close();
					}
				}, this));
				this._selector.open();
			},
			close: function ()
			{
				if (this._selector)
				{
					this._selector.close();
				}
			},
			onCustomEntitySelectorOpen: function (control)
			{
				this._control = control;

				//BX.bind(control.field, "keyup", this._inputKeyPressHandler);

				if (this._fieldId !== control.getId())
				{
					this._selector = null;
					this.close();
				}
				else
				{
					this._selector = control;
					this.open(control.field);
				}
			},
			onCustomEntitySelectorClose: function (control)
			{
				if (this._fieldId !== control.getId())
				{
					this.close();
					//BX.unbind(control.field, "keyup", this._inputKeyPressHandler);
				}
			}
		};
	BX.FilterEntitySelector.closeAll = function ()
	{
		for (var k in this.items)
		{
			if (this.items.hasOwnProperty(k))
			{
				this.items[k].close();
			}
		}
	};
	BX.FilterEntitySelector.items = {};
	BX.FilterEntitySelector.create = function(id, settings)
	{
		var self = new BX.FilterEntitySelector(id, settings);
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if (typeof(BX.TasksGroupsSelectorInit) === "undefined")
{
	BX.TasksGroupsSelectorInit = function (settings)
	{
		var menu = null,
			selectorId = settings.selectorId,
			buttonAddId = settings.buttonAddId,
			pathTaskAdd = settings.pathTaskAdd.indexOf("?") === -1
				? settings.pathTaskAdd + "?GROUP_ID="
				: settings.pathTaskAdd + "&GROUP_ID=",
			messages = settings.messages,
			groups = settings.groups,
			currentGroup = settings.currentGroup,
			groupLimit = settings.groupLimit,
			offsetLeft = settings.offsetLeft;

		// change add-button href
		var setTaskAddHref = function(groupId)
		{
			if (BX(buttonAddId))
			{
				BX(buttonAddId).setAttribute("href", pathTaskAdd + groupId);
			}
		};

		currentGroup.id = parseInt(currentGroup.id);
		currentGroup.text = BX.util.htmlspecialchars(currentGroup.text);

		setTaskAddHref(currentGroup.id);

		BX.bind(BX(selectorId), "click", function ()
		{
			if (menu === null)
			{
				var menuItems = [];

				var clickHandler = function (e, item)
				{
					//BX.addClass(item.layout.item, "menu-popup-item-accept");

					BX.onCustomEvent(window, 'BX.Kanban.ChangeGroup', [item.id, currentGroup.id]);
					BX.onCustomEvent(window, 'BX.Tasks.ChangeGroup', [item.id, currentGroup.id]);

					if (item.id !== currentGroup.id)
					{
						var currentMenuItems = menu.getMenuItems();
						// insert new group and remove current item or pre-last item
						menu.addMenuItem({
							id: currentGroup.id,
							text: currentGroup.text,
							onclick: BX.delegate(clickHandler, this)
						}, currentMenuItems.length > 0
							? currentMenuItems[0]["id"]
							: null);
						if (item.id !== "wo")
						{
							if (menu.getMenuItem(item.id))
							{
								menu.removeMenuItem(item.id);
							}
							else if (currentMenuItems.length >= groupLimit)
							{
								// without "select" and delimeter
								menu.removeMenuItem(currentMenuItems[currentMenuItems.length - 3].id);
							}
						}
					}
					menu.close();
					// set selected item in current
					currentGroup = {
						id: item.id,
						text: item.text
					};
					setTaskAddHref(item.id);
					if (BX(selectorId + "_text"))
					{
						BX(selectorId + "_text").innerHTML = item.text;
					}
					BX.onCustomEvent(this, "onTasksGroupSelectorChange", [currentGroup]);
				};

				// fill menu array
				for (var i = 0, c = groups.length; i < c; i++)
				{
					menuItems.push({
						id: parseInt(groups[i]["id"]),
						text: BX.util.htmlspecialchars(groups[i]["text"]),
						class: 'menu-popup-item-none',
						onclick: BX.delegate(clickHandler, this)
					});

				}
				// select new group
				if (groups.length > 0)
				{
					menuItems.push({delimiter: true});
					/*menuItems.push({
						id: "wo",
						text: messages.TASKS_BTN_GROUP_WO,
						onclick: BX.delegate(clickHandler, this)
					});*/
					menuItems.push({
						id: "new",
						text: messages.TASKS_BTN_GROUP_SELECT,
						onclick: function (event, item)
						{
							menu.getPopupWindow().setAutoHide(false);

							var selector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
								scope: item.getContainer(),
								id: "group-selector",
								mode: "group",
								query: false,
								useSearch: true,
								useAdd: false,
								parent: this,
								popupOffsetTop: 5,
								popupOffsetLeft: 40
							});
							selector.bindEvent("item-selected", function (data)
							{
								clickHandler(null, {
									id: data.id,
									text: data.nameFormatted.length > 50
										? data.nameFormatted.substring(0, 50) + "..."
										: data.nameFormatted
								});
								selector.close();
							});
							selector.bindEvent("close", function (data) {
								menu.getPopupWindow().setAutoHide(true);
							});
							selector.open();
						}
					});
				}
				// create menu
				if (!offsetLeft)
				{
					offsetLeft = 0;
				}
				menu = BX.PopupMenu.create(
					selectorId,
					BX(selectorId),
					menuItems,
					{
						autoHide: true,
						closeByEsc: true,
						offsetLeft: offsetLeft
					}
				);
			}
			menu.popupWindow.show();
		});
	};
}

if (typeof(BX.Tasks.SortManager) === "undefined")
{
	BX.Tasks.SortManager = {
		setSort: function (field, dir, gridId)
		{
			dir = dir || 'asc';

			if (BX.Main.gridManager != undefined)
			{
				var grid = BX.Main.gridManager.getById(gridId).instance;
				grid.sortByColumn({sort_by: field, sort_order: dir});

				if (field === "SORTING")
				{
					grid.getRows().enableDragAndDrop()
				}
				else
				{
					grid.getRows().disableDragAndDrop();
				}
			}
			else
			{
				BX.ajax.post(
					BX.util.add_url_param("/bitrix/components/bitrix/main.ui.grid/settings.ajax.php", {
						GRID_ID: gridId,
						action: "setSort"
					}),
					{
						by: field,
						order: dir
					},
					function(res)
					{
						try
						{
							res = JSON.parse(res);

							if (!res.error)
							{
								window.location.reload();
							}
						}
						catch(err)
						{

						}
					}
				);
			}
		}
	}
}

if (typeof BX.Tasks.SprintSelector === 'undefined')
{
	BX.Tasks.SprintSelector = function(containerId, params)
	{
		if (!BX(containerId))
		{
			return;
		}

		const buttonSelector = BX.UI.ButtonManager.createFromNode(BX(containerId));

		BX.bind(
			BX(containerId),
			"click",
			function()
			{
				var sprintsSelectorDialog = new BX.UI.EntitySelector.Dialog({
					targetNode: BX(containerId),
					width: 400,
					height: 300,
					multiple: false,
					dropdownMode: true,
					enableSearch: true,
					compactView: true,
					showAvatars: false,
					cacheable: false,
					preselectedItems: [['sprint-selector' , params.sprintId]],
					entities: [
						{
							id: 'sprint-selector',
							options: {
								groupId: params.groupId,
								onlyCompleted: true
							},
							dynamicLoad: true,
							dynamicSearch: true
						}
					],
					events: {
						'Item:onSelect': function(event) {
							var selectedItem = event.getData().item;

							params.sprintId = selectedItem.id;

							BX.onCustomEvent(
								BX(containerId),
								'onTasksGroupSelectorChange',
								[
									{
										id: params.groupId,
										sprintId: selectedItem.id,
										name: selectedItem.customData.get('name')
									}
								]
							);

							buttonSelector.setText(selectedItem.customData.get('label'));
						},
					},
				});

				sprintsSelectorDialog.show();
			}
		);
	};
}

if (typeof BX.Tasks.ProjectSelector === "undefined")
{
	BX.Tasks.ProjectSelector =
		{
			reloadProject: function(groupId)
			{
				var url = document.location.href;
				url = BX.util.add_url_param(url, {
					group_id: groupId
				});

				document.location.href = url;
			}
		}
}


this.BX = this.BX || {};
(function (exports,ui_tour,main_core,main_core_events,ui_buttons,ui_system_menu,pull_client) {
	'use strict';

	var _filterId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("filterId");
	var _filter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("filter");
	var _init = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _setFilter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setFilter");
	var _setGuide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setGuide");
	var _markViewed = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("markViewed");
	var _log = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("log");
	class Preset {
	  constructor(options) {
	    Object.defineProperty(this, _log, {
	      value: _log2
	    });
	    Object.defineProperty(this, _markViewed, {
	      value: _markViewed2
	    });
	    Object.defineProperty(this, _setGuide, {
	      value: _setGuide2
	    });
	    Object.defineProperty(this, _setFilter, {
	      value: _setFilter2
	    });
	    Object.defineProperty(this, _init, {
	      value: _init2
	    });
	    Object.defineProperty(this, _filterId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _filter, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _filterId)[_filterId] = options.filterId;
	    babelHelpers.classPrivateFieldLooseBase(this, _init)[_init]();
	  }
	  payAttention() {
	    setTimeout(() => {
	      this.guide.showNextStep();
	      babelHelpers.classPrivateFieldLooseBase(this, _markViewed)[_markViewed]();
	    }, Preset.DELAY);
	  }
	}
	function _init2() {
	  babelHelpers.classPrivateFieldLooseBase(babelHelpers.classPrivateFieldLooseBase(this, _setFilter)[_setFilter](), _setGuide)[_setGuide]();
	}
	function _setFilter2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter] = BX.Main.filterManager.getById(babelHelpers.classPrivateFieldLooseBase(this, _filterId)[_filterId]);
	  return this;
	}
	function _setGuide2() {
	  this.guide = new ui_tour.Guide({
	    simpleMode: true,
	    onEvents: true,
	    steps: [{
	      target: babelHelpers.classPrivateFieldLooseBase(this, _filter)[_filter].getPopupBindElement(),
	      title: main_core.Loc.getMessage('TASKS_INTERFACE_FILTER_PRESETS_MOVED_TITLE'),
	      text: main_core.Loc.getMessage('TASKS_INTERFACE_FILTER_PRESETS_MOVED_TEXT'),
	      position: 'bottom',
	      condition: {
	        top: true,
	        bottom: false,
	        color: 'primary'
	      }
	    }]
	  });
	  this.guide.getPopup().setWidth(420);
	  return this;
	}
	function _markViewed2() {
	  main_core.ajax.runComponentAction('bitrix:tasks.interface.filter', 'markPresetAhaMomentViewed', {
	    mode: 'class',
	    data: {}
	  }).catch(error => {
	    babelHelpers.classPrivateFieldLooseBase(this, _log)[_log](error);
	  });
	}
	function _log2(error) {
	  console.log(error);
	}
	Preset.DELAY = 1000;

	const defaultRole = 'view_all';
	var _groupId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("groupId");
	var _selectedRoleId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedRoleId");
	var _roles = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("roles");
	var _analytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytics");
	var _button = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("button");
	var _menu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menu");
	var _initButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initButton");
	var _bindEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindEvents");
	var _handleFilterBeforeApply = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleFilterBeforeApply");
	var _handlePull = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handlePull");
	var _getEmptyCounters = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEmptyCounters");
	var _update = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("update");
	var _menuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuItems");
	var _roleName = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("roleName");
	var _roleCounter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("roleCounter");
	var _sendAnalyticsClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsClick");
	var _sendAnalyticsApply = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsApply");
	var _getAnalyticsSender = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAnalyticsSender");
	class Roles {
	  constructor(params) {
	    Object.defineProperty(this, _getAnalyticsSender, {
	      value: _getAnalyticsSender2
	    });
	    Object.defineProperty(this, _sendAnalyticsApply, {
	      value: _sendAnalyticsApply2
	    });
	    Object.defineProperty(this, _sendAnalyticsClick, {
	      value: _sendAnalyticsClick2
	    });
	    Object.defineProperty(this, _roleCounter, {
	      get: _get_roleCounter,
	      set: void 0
	    });
	    Object.defineProperty(this, _roleName, {
	      get: _get_roleName,
	      set: void 0
	    });
	    Object.defineProperty(this, _menuItems, {
	      get: _get_menuItems,
	      set: void 0
	    });
	    Object.defineProperty(this, _update, {
	      value: _update2
	    });
	    Object.defineProperty(this, _getEmptyCounters, {
	      value: _getEmptyCounters2
	    });
	    Object.defineProperty(this, _bindEvents, {
	      value: _bindEvents2
	    });
	    Object.defineProperty(this, _initButton, {
	      value: _initButton2
	    });
	    Object.defineProperty(this, _groupId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _selectedRoleId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _roles, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _analytics, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _button, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _menu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _handleFilterBeforeApply, {
	      writable: true,
	      value: event => {
	        const previousRoleId = babelHelpers.classPrivateFieldLooseBase(this, _selectedRoleId)[_selectedRoleId];
	        babelHelpers.classPrivateFieldLooseBase(this, _selectedRoleId)[_selectedRoleId] = event.getData()[2].getFilterFieldsValues().ROLEID || defaultRole;
	        babelHelpers.classPrivateFieldLooseBase(this, _update)[_update]();
	        if (previousRoleId !== babelHelpers.classPrivateFieldLooseBase(this, _selectedRoleId)[_selectedRoleId]) {
	          void babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsApply)[_sendAnalyticsApply](babelHelpers.classPrivateFieldLooseBase(this, _selectedRoleId)[_selectedRoleId]);
	        }
	      }
	    });
	    Object.defineProperty(this, _handlePull, {
	      writable: true,
	      value: data => {
	        var _data$babelHelpers$cl;
	        Object.entries((_data$babelHelpers$cl = data[babelHelpers.classPrivateFieldLooseBase(this, _groupId)[_groupId]]) != null ? _data$babelHelpers$cl : babelHelpers.classPrivateFieldLooseBase(this, _getEmptyCounters)[_getEmptyCounters]()).forEach(([roleId, {
	          total
	        }]) => {
	          babelHelpers.classPrivateFieldLooseBase(this, _roles)[_roles][roleId].counter = total;
	        });
	        babelHelpers.classPrivateFieldLooseBase(this, _update)[_update]();
	      }
	    });
	    if (!params.button) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _groupId)[_groupId] = params.groupId || 0;
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedRoleId)[_selectedRoleId] = params.selectedRoleId || defaultRole;
	    babelHelpers.classPrivateFieldLooseBase(this, _roles)[_roles] = {
	      [defaultRole]: {
	        title: main_core.Loc.getMessage('TASKS_ALL_ROLES'),
	        counter: params.totalCounter
	      },
	      ...Object.fromEntries(Object.entries(params.items).map(([roleId, item]) => [roleId, {
	        title: item.TEXT,
	        counter: Number(item.COUNTER)
	      }]))
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics] = params.analytics;
	    babelHelpers.classPrivateFieldLooseBase(this, _initButton)[_initButton](params.button);
	    babelHelpers.classPrivateFieldLooseBase(this, _bindEvents)[_bindEvents]();
	  }
	}
	function _initButton2(button) {
	  babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu] = new ui_system_menu.Menu({
	    items: babelHelpers.classPrivateFieldLooseBase(this, _menuItems)[_menuItems],
	    offsetTop: 6
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _button)[_button] = ui_buttons.ButtonManager.createFromNode(button);
	  babelHelpers.classPrivateFieldLooseBase(this, _button)[_button].bindEvent('click', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].show(babelHelpers.classPrivateFieldLooseBase(this, _button)[_button].getContainer());
	    void babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsClick)[_sendAnalyticsClick]();
	  });
	}
	function _bindEvents2() {
	  main_core_events.EventEmitter.subscribe('BX.Main.Filter:beforeApply', babelHelpers.classPrivateFieldLooseBase(this, _handleFilterBeforeApply)[_handleFilterBeforeApply]);
	  pull_client.PULL.subscribe({
	    moduleId: 'tasks',
	    command: 'user_counter',
	    callback: babelHelpers.classPrivateFieldLooseBase(this, _handlePull)[_handlePull]
	  });
	}
	function _getEmptyCounters2() {
	  return Object.fromEntries(Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _roles)[_roles]).map(roleId => [roleId, {
	    total: 0
	  }]));
	}
	function _update2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _button)[_button].setText(babelHelpers.classPrivateFieldLooseBase(this, _roleName)[_roleName]);
	  babelHelpers.classPrivateFieldLooseBase(this, _button)[_button].setRightCounter(babelHelpers.classPrivateFieldLooseBase(this, _roleCounter)[_roleCounter] ? {
	    value: babelHelpers.classPrivateFieldLooseBase(this, _roleCounter)[_roleCounter]
	  } : null);
	  babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].updateItems(babelHelpers.classPrivateFieldLooseBase(this, _menuItems)[_menuItems]);
	}
	function _get_menuItems() {
	  const hasCounters = Object.values(babelHelpers.classPrivateFieldLooseBase(this, _roles)[_roles]).some(({
	    counter
	  }) => counter > 0);
	  return Object.entries(babelHelpers.classPrivateFieldLooseBase(this, _roles)[_roles]).map(([roleId, role]) => ({
	    title: role.title,
	    isSelected: roleId === babelHelpers.classPrivateFieldLooseBase(this, _selectedRoleId)[_selectedRoleId],
	    onClick: () => main_core_events.EventEmitter.emit('Tasks.TopMenu:onItem', new main_core_events.BaseEvent({
	      data: [roleId],
	      compatData: [roleId]
	    })),
	    ...(hasCounters ? {
	      counter: {
	        value: role.counter
	      }
	    } : {})
	  }));
	}
	function _get_roleName() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _roles)[_roles][babelHelpers.classPrivateFieldLooseBase(this, _selectedRoleId)[_selectedRoleId]].title;
	}
	function _get_roleCounter() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _roles)[_roles][defaultRole].counter;
	}
	async function _sendAnalyticsClick2() {
	  const analytics = await babelHelpers.classPrivateFieldLooseBase(this, _getAnalyticsSender)[_getAnalyticsSender]();
	  analytics.sendRoleClick(babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics]);
	}
	async function _sendAnalyticsApply2(role) {
	  const analytics = await babelHelpers.classPrivateFieldLooseBase(this, _getAnalyticsSender)[_getAnalyticsSender]();
	  analytics.sendRoleClickType(babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics], {
	    role,
	    isFilterEnabled: role !== defaultRole
	  });
	}
	async function _getAnalyticsSender2() {
	  return (await main_core.Runtime.loadExtension('tasks.v2.lib.analytics')).analytics;
	}

	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _initAddButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initAddButton");
	var _initRoles = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initRoles");
	var _showPresetTourGuide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showPresetTourGuide");
	class TasksInterfaceFilter {
	  constructor(params) {
	    Object.defineProperty(this, _showPresetTourGuide, {
	      value: _showPresetTourGuide2
	    });
	    Object.defineProperty(this, _initRoles, {
	      value: _initRoles2
	    });
	    Object.defineProperty(this, _initAddButton, {
	      value: _initAddButton2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = params;
	    babelHelpers.classPrivateFieldLooseBase(this, _initAddButton)[_initAddButton]();
	    babelHelpers.classPrivateFieldLooseBase(this, _initRoles)[_initRoles]();
	    babelHelpers.classPrivateFieldLooseBase(this, _showPresetTourGuide)[_showPresetTourGuide]();
	  }
	}
	function _initAddButton2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].isV2Form || !babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].createNode) {
	    return;
	  }
	  const button = ui_buttons.ButtonManager.createFromNode(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].createNode);
	  button.getMainButton().bindEvent('click', () => BX.Tasks.V2.Application.TaskCard.showCompactCard({
	    groupId: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].groupId,
	    analytics: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].analytics
	  }));
	}
	function _initRoles2() {
	  new Roles(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].roles);
	}
	function _showPresetTourGuide2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].showPresetTourGuide) {
	    return;
	  }
	  BX.Tasks.Preset.Aha = new BX.Tasks.Preset({
	    filterId: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].filterId
	  });
	  BX.Tasks.Preset.Aha.payAttention();
	}

	exports.Preset = Preset;
	exports.TasksInterfaceFilter = TasksInterfaceFilter;

}((this.BX.Tasks = this.BX.Tasks || {}),BX.UI.Tour,BX,BX.Event,BX.UI,BX.UI.System,BX));


//# sourceMappingURL=script.js.map