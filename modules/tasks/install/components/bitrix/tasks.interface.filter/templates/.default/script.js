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
					query: query ? query : false,
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
			groupLimit = settings.groupLimit;

		// change add-button href
		var setTaskAddHref = function(groupId)
		{
			BX(buttonAddId).setAttribute("href", pathTaskAdd + groupId);
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
					BX(selectorId + "_text").innerHTML = item.text;
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
						onclick: function ()
						{
							var selector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
								scope: BX.proxy_context,
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
							selector.open();
						}
					});
				}
				// create menu
				menu = BX.PopupMenu.create(
					selectorId,
					BX(selectorId),
					menuItems,
					{
						autoHide: true,
						closeByEsc: true
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

if (typeof BX.Tasks.SprintSelector === "undefined")
{
	BX.Tasks.SprintSelector = function(containerId, sprints, params)
	{
		if (!BX(containerId))
		{
			return;
		}

		var menuSprintItems = [];

		for (var i = 0, c = sprints.length; i < c; i++)
		{
			menuSprintItems.push({
				sprintId: sprints[i]["ID"],
				text: sprints[i]["START_TIME"]
							+ " &mdash; " +
						sprints[i]["FINISH_TIME"],
				className: (params.sprintId === parseInt(sprints[i]["ID"]))
							? "menu-popup-item-accept"
							: "menu-popup-item-none",
				onclick: function(e, menuItem)
				{
					BX.onCustomEvent(
						BX(containerId),
						"onTasksGroupSelectorChange",
						[
							{
								id: params.groupId,
								sprintId: menuItem.sprintId
							}
						]
					);
					// remove and set css-classes
					var menuItems = menuItem.menuWindow.getMenuItems();
					for (var i = 0, c = menuItems.length; i < c; i++)
					{
						BX.removeClass(
							menuItems[i].layout.item,
							"menu-popup-item-accept"
						);
					}
					BX.addClass(
						menuItem.layout.item,
						"menu-popup-item-accept"
					);
				}
			});
		}

		var menuSprint = new BX.PopupMenuWindow(
			"tasks_sprint_menu",
			BX(containerId),
			menuSprintItems,
			{
				autoHide : true
			}
		);

		BX.bind(
			BX(containerId),
			"click",
			function()
			{
				menuSprint.show();
			}
		);
	};
}
