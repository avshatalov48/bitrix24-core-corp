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

		BX.bind(
			BX(containerId).querySelector('.webform-small-button'),
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

							var selectorTextNode = BX(containerId).querySelector('.webform-small-button-text');
							selectorTextNode.textContent = selectedItem.customData.get('label');
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
(function (exports,ui_tour,main_core) {
	'use strict';

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _filterId = /*#__PURE__*/new WeakMap();
	var _filter = /*#__PURE__*/new WeakMap();
	var _init = /*#__PURE__*/new WeakSet();
	var _setFilter = /*#__PURE__*/new WeakSet();
	var _setGuide = /*#__PURE__*/new WeakSet();
	var _markViewed = /*#__PURE__*/new WeakSet();
	var _log = /*#__PURE__*/new WeakSet();
	var Preset = /*#__PURE__*/function () {
	  function Preset(options) {
	    babelHelpers.classCallCheck(this, Preset);
	    _classPrivateMethodInitSpec(this, _log);
	    _classPrivateMethodInitSpec(this, _markViewed);
	    _classPrivateMethodInitSpec(this, _setGuide);
	    _classPrivateMethodInitSpec(this, _setFilter);
	    _classPrivateMethodInitSpec(this, _init);
	    _classPrivateFieldInitSpec(this, _filterId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _filter, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _filterId, options.filterId);
	    _classPrivateMethodGet(this, _init, _init2).call(this);
	  }
	  babelHelpers.createClass(Preset, [{
	    key: "payAttention",
	    value: function payAttention() {
	      var _this = this;
	      setTimeout(function () {
	        _this.guide.showNextStep();
	        _classPrivateMethodGet(_this, _markViewed, _markViewed2).call(_this);
	      }, Preset.DELAY);
	    }
	  }]);
	  return Preset;
	}();
	function _init2() {
	  var _classPrivateMethodGe;
	  _classPrivateMethodGet(_classPrivateMethodGe = _classPrivateMethodGet(this, _setFilter, _setFilter2).call(this), _setGuide, _setGuide2).call(_classPrivateMethodGe);
	}
	function _setFilter2() {
	  babelHelpers.classPrivateFieldSet(this, _filter, BX.Main.filterManager.getById(babelHelpers.classPrivateFieldGet(this, _filterId)));
	  return this;
	}
	function _setGuide2() {
	  this.guide = new ui_tour.Guide({
	    simpleMode: true,
	    onEvents: true,
	    steps: [{
	      target: babelHelpers.classPrivateFieldGet(this, _filter).getPopupBindElement(),
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
	  var _this2 = this;
	  main_core.ajax.runComponentAction('bitrix:tasks.interface.filter', 'markPresetAhaMomentViewed', {
	    mode: 'class',
	    data: {}
	  })["catch"](function (error) {
	    _classPrivateMethodGet(_this2, _log, _log2).call(_this2, error);
	  });
	}
	function _log2(error) {
	  console.log(error);
	}
	babelHelpers.defineProperty(Preset, "DELAY", 1000);

	exports.Preset = Preset;

}((this.BX.Tasks = this.BX.Tasks || {}),BX.UI.Tour,BX));


//# sourceMappingURL=script.js.map