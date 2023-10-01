BX.namespace("BX.Crm");

if(typeof(BX.HtmlHelper) === "undefined")
{
	BX.HtmlHelper = function(){};
	BX.HtmlHelper.setupSelectOptions = function(select, settings)
	{
		while (select.options.length > 0)
		{
			select.remove(0);
		}

		var currentGroup = null;
		var currentGroupName = "";

		for(var i = 0; i < settings.length; i++)
		{
			var setting = settings[i];

			var groupName = BX.type.isNotEmptyString(setting["group"]) ? setting["group"] : "";
			if(groupName !== "" && groupName !== currentGroupName)
			{
				currentGroupName = groupName;
				currentGroup = document.createElement("OPTGROUP");
				currentGroup.label = groupName;
				select.appendChild(currentGroup);
			}

			var value = BX.type.isNotEmptyString(setting['value']) ? setting['value'] : '';
			var text = BX.type.isNotEmptyString(setting['text']) ? setting['text'] : setting['value'];

			var option = new Option(text, value, false, false);

			var attrs = BX.type.isPlainObject(setting['attrs']) ? setting['attrs'] : null;
			if(attrs)
			{
				for(var k in attrs)
				{
					if(!attrs.hasOwnProperty(k))
					{
						continue;
					}

					option.setAttribute("data-" + k, attrs[k]);
				}
			}

			if(currentGroup)
			{
				currentGroup.appendChild(option);
			}
			else
			{
				if(!BX.browser.IsIE())
				{
					select.add(option, null);
				}
				else
				{
					try
					{
						// for IE earlier than version 8
						select.add(option, select.options[null]);
					}
					catch (e)
					{
						select.add(option, null);
					}
				}
			}
		}
	};
}

if(typeof(BX.CrmUserSearchPopup) === "undefined")
{
	BX.CrmUserSearchPopup = function()
	{
		this._id = '';
		this._search_input = null;
		this._data_input = null;
		this._componentName = '';
		this._componentContainer = null;
		this._componentObj = null;
		this._serviceContainer = null;
		this._zIndex = 0;
		this._dlg = null;
		this._dlgDisplayed = false;
		this._currentUser = {};

		this._searchKeyHandler = BX.delegate(this._handleSearchKey, this);
		this._searchFocusHandler = BX.delegate(this._handleSearchFocus, this);
		this._externalClickHandler = BX.delegate(this._handleExternalClick, this);
		this._clearButtonClickHandler = BX.delegate(this._hadleClearButtonClick, this);

		this._userSelectorInitCounter = 0;
	};

	BX.CrmUserSearchPopup.prototype =
	{
		//initialize: function(id, search_input, data_input, componentName, user, serviceContainer, zIndex)
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : ('crm_user_search_popup_' + Math.random());

			if(!settings)
			{
				settings = {};
			}

			if(!BX.type.isElementNode(settings['searchInput']))
			{
				throw  "BX.CrmUserSearchPopup: 'search_input' is not defined!";
			}
			this._search_input = settings['searchInput'];

			this._clearButton = BX.findPreviousSibling(this._search_input, { className: "crm-filter-name-clean" });

			if(!BX.type.isElementNode(settings['dataInput']))
			{
				throw  "BX.CrmUserSearchPopup: 'data_input' is not defined!";
			}
			this._data_input = settings['dataInput'];

			if(!BX.type.isNotEmptyString(settings['componentName']))
			{
				throw  "BX.CrmUserSearchPopup: 'componentName' is not defined!";
			}

			this._currentUser = settings['user'] ? settings['user'] : {};
			this._componentName = settings['componentName'];

			this._componentContainer = BX(this._componentName + '_selector_content');

			this._initializeUserSelector();
			this._adjustUser();

			this._serviceContainer = settings['serviceContainer'] ? settings['serviceContainer'] : document.body;
			this.setZIndex(settings['zIndex']);
		},
		_initializeUserSelector: function()
		{
			var objName = 'O_' + this._componentName;
			if(!window[objName])
			{
				if(this._userSelectorInitCounter === 10)
				{
					throw "BX.CrmUserSearchPopup: Could not find '"+ objName +"' user selector!";
				}

				this._userSelectorInitCounter++;
				window.setTimeout(BX.delegate(this._initializeUserSelector, this), 200);
				return;
			}

			this._componentObj = window[objName];
			this._componentObj.onSelect = BX.delegate(this._handleUserSelect, this);
			this._componentObj.searchInput = this._search_input;

			if(this._currentUser && this._currentUser['id'] > 0)
			{
				this._componentObj.setSelected([ this._currentUser ]);
			}

			BX.bind(this._search_input, 'keyup', this._searchKeyHandler);
			BX.bind(this._search_input, 'focus', this._searchFocusHandler);

			if(BX.type.isElementNode(this._clearButton))
			{
				BX.bind(this._clearButton, 'click', this._clearButtonClickHandler);
			}

			BX.bind(document, 'click', this._externalClickHandler);
		},
		open: function()
		{
			this._componentContainer.style.display = '';
			this._dlg = new BX.PopupWindow(
				this._id,
				this._search_input,
				{
					autoHide: false,
					draggable: false,
					closeByEsc: true,
					offsetLeft: 0,
					offsetTop: 0,
					zIndex: this._zIndex,
					bindOptions: { forceBindPosition: true },
					content : this._componentContainer,
					events:
					{
						onPopupShow: BX.delegate(
							function()
							{
								this._dlgDisplayed = true;
							},
							this
						),
						onPopupClose: BX.delegate(
							function()
							{
								this._dlgDisplayed = false;
								this._componentContainer.parentNode.removeChild(this._componentContainer);
								this._serviceContainer.appendChild(this._componentContainer);
								this._componentContainer.style.display = 'none';
								this._dlg.destroy();
							},
							this
						),
						onPopupDestroy: BX.delegate(
							function()
							{
								this._dlg = null;
							},
							this
						)
					}
				}
			);

			this._dlg.show();
		},
		_adjustUser: function()
		{
			//var container = BX.findParent(this._search_input, { className: 'webform-field-textbox' });
			if(parseInt(this._currentUser['id']) > 0)
			{
				this._data_input.value = this._currentUser['id'];
				this._search_input.value = this._currentUser['name'] ? this._currentUser.name : this._currentUser['id'];
				//BX.removeClass(container, 'webform-field-textbox-empty');
			}
			else
			{
				this._data_input.value = this._search_input.value = '';
				//BX.addClass(container, 'webform-field-textbox-empty');
			}
		},
		getZIndex: function()
		{
			return this._zIndex;
		},
		setZIndex: function(zIndex)
		{
			if(typeof(zIndex) === 'undefined' || zIndex === null)
			{
				zIndex = 0;
			}

			var i = parseInt(zIndex);
			this._zIndex = !isNaN(i) ? i : 0;
		},
		close: function()
		{
			if(this._dlg)
			{
				this._dlg.close();
			}
		},
		select: function(user)
		{
			this._currentUser = user;
			this._adjustUser();
			if(this._componentObj)
			{
				this._componentObj.setSelected([ user ]);
			}
		},
		_onBeforeDelete: function()
		{
			if(BX.type.isElementNode(this._search_input))
			{
				BX.unbind(this._search_input, 'keyup', this._searchKeyHandler);
				BX.unbind(this._search_input, 'focus', this._searchFocusHandler);
			}

			if(BX.type.isElementNode(this._clearButton))
			{
				BX.bind(this._clearButton, 'click', this._clearButtonClickHandler);
			}

			BX.unbind(document, 'click', this._externalClickHandler);

			if(this._componentContainer)
			{
				this._componentContainer.parentNode.removeChild(this._componentContainer);
				this._serviceContainer.appendChild(this._componentContainer);
				this._componentContainer.style.display = 'none';
				this._componentContainer = null;
			}
		},
		_hadleClearButtonClick: function(e)
		{
			this._data_input.value = this._search_input.value = '';
		},
		_handleExternalClick: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			if(!this._dlgDisplayed)
			{
				return;
			}

			var target = null;
			if(e)
			{
				if(e.target)
				{
					target = e.target;
				}
				else if(e.srcElement)
				{
					target = e.srcElement;
				}
			}

			if(target !== this._search_input &&
				!BX.findParent(target, { attribute:{ id: this._componentName + '_selector_content' } }))
			{
				this._adjustUser();
				this.close();
			}
		},
		_handleSearchKey: function(e)
		{
			if(!this._dlg || !this._dlgDisplayed)
			{
				this.open();
			}

			this._componentObj.search();
		},
		_handleSearchFocus: function(e)
		{
			if(!this._dlg || !this._dlgDisplayed)
			{
				this.open();
			}

			this._componentObj._onFocus(e);
		},
		_handleUserSelect: function(user)
		{
			this._currentUser = user;
			this._adjustUser();
			this.close();
		}
	};

	BX.CrmUserSearchPopup.items = {};

	BX.CrmUserSearchPopup.create = function(id, settings, delay)
	{
		if(isNaN(delay))
		{
			delay = 0;
		}

		if(delay > 0)
		{
			window.setTimeout(
				function(){ BX.CrmUserSearchPopup.create(id, settings, 0); },
				delay
			);
			return null;
		}

		var self = new BX.CrmUserSearchPopup();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};

	BX.CrmUserSearchPopup.createIfNotExists = function(id, settings)
	{
		var self = this.items[id];
		if(typeof(self) !== 'undefined')
		{
			self.initialize(id, settings);
		}
		else
		{
			self = new BX.CrmUserSearchPopup();
			self.initialize(id, settings);
			this.items[id] = self;
		}
		return self;
	};

	BX.CrmUserSearchPopup.deletePopup = function(id)
	{
		var item = this.items[id];
		if(typeof(item) === 'undefined')
		{
			return false;
		}

		item._onBeforeDelete();
		delete this.items[id];
		return true;
	}
}

if(typeof(BX.CrmNotifier) === "undefined")
{
	BX.CrmNotifier = function()
	{
		this._sender = null;
		this._listeners = [];
	};

	BX.CrmNotifier.prototype =
	{
		initialize: function(sender)
		{
			this._sender = sender;
		},
		addListener: function(listener)
		{
			if(!BX.type.isFunction(listener))
			{
				return;
			}

			for(var i = 0; i < this._listeners.length; i++)
			{
				if(this._listeners[i] === listener)
				{
					return;
				}
			}

			this._listeners.push(listener);
		},
		removeListener: function(listener)
		{
			if(!BX.type.isFunction(listener))
			{
				return;
			}

			for(var i = 0; i < this._listeners.length; i++)
			{
				if(this._listeners[i] === listener)
				{
					this._listeners.splice(i, 1);
					return;
				}
			}
		},
		resetListeners: function()
		{
			this._listeners = [];
		},
		notify: function(params)
		{
			//Make copy of listeners to process addListener/removeListener while notification under way.
			var ary = [];
			for(var i = 0; i < this._listeners.length; i++)
			{
				ary.push(this._listeners[i]);
			}

			if(!BX.type.isArray(params))
			{
				params = [];
			}

			params.splice(0, 0, this._sender);

			for(var j = 0; j < ary.length; j++)
			{
				try
				{
					ary[j].apply(this._sender, params);
				}
				catch(ex)
				{
				}
			}
		},
		getListenerCount: function()
		{
			return this._listeners.length;
		}
	};

	BX.CrmNotifier.create = function(sender)
	{
		var self = new BX.CrmNotifier();
		self.initialize(sender);
		return self;
	}
}

//region BX.CmrSelectorMenuItem
if(typeof(BX.CmrSelectorMenuItem) === "undefined")
{
	BX.CmrSelectorMenuItem = function()
	{
		this._parent = null;
		this._settings = {};
		this._onSelectNotifier = null;
	};
	BX.CmrSelectorMenuItem.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings;
			this._onSelectNotifier = BX.CrmNotifier.create(this);
			var events = this.getSetting("events");
			if(events && events['select'])
			{
				this._onSelectNotifier.addListener(events['select']);
			}
		},
		getSetting: function(name, defaultval)
		{
			var s = this._settings;
			return typeof(s[name]) != "undefined" ? s[name] : defaultval;
		},
		getValue: function()
		{
			return this.getSetting("value", "");
		},
		getText: function()
		{
			var text = this.getSetting("text");
			return BX.type.isNotEmptyString(text) ? text : this.getValue();
		},
		isEnabled: function()
		{
			return this.getSetting("enabled", true);
		},
		isDefault: function()
		{
			return this.getSetting("default", false);
		},
		createMenuItem: function(encode)
		{
			if(BX.prop.getBoolean(this._settings, "delimiter", false))
			{
				return { delimiter: true };
			}

			encode = !!encode;
			var text = this.getText();
			if(!!encode)
			{
				text = BX.util.htmlspecialchars(text);
			}

			return({
				text: text,
				onclick: BX.delegate(this._onClick, this),
				className: this.getSetting('className', '')
			});
		},
		addOnSelectListener: function(listener)
		{
			this._onSelectNotifier.addListener(listener);
		},
		removeOnSelectListener: function(listener)
		{
			this._onSelectNotifier.removeListener(listener);
		},
		_onClick: function()
		{
			this._onSelectNotifier.notify();
		}
	};
	BX.CmrSelectorMenuItem.create = function(settings)
	{
		var self = new BX.CmrSelectorMenuItem();
		self.initialize(settings);
		return self;
	};
}
//endregion
//region BX.CmrSelectorMenu
if(typeof(BX.CmrSelectorMenu) === "undefined")
{
	BX.CmrSelectorMenu = function()
	{
		this._id = "";
		this._settings = {};
		this._items = [];
		this._encodeItems = true;
		this._onSelectNotifier = null;
		this._popup = null;
		this._isOpened = false;
		this._itemSelectHandler = BX.delegate(this.onItemSelect, this);
	};
	BX.CmrSelectorMenu.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : ("crm_selector_menu_" + Math.random().toString().substring(2));
			this._settings = settings ? settings : {};

			this._encodeItems = !!this.getSetting("encodeItems", true);
			var itemData = this.getSetting("items");
			itemData = BX.type.isArray(itemData) ? itemData : [];
			this._items = [];
			for(var i = 0; i < itemData.length; i++)
			{
				var item = BX.CmrSelectorMenuItem.create(itemData[i]);
				item.addOnSelectListener(this._itemSelectHandler);
				this._items.push(item);
			}

			this._onSelectNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getItems: function()
		{
			return this._items;
		},
		setupItems: function(data)
		{
			this._items = [];
			for(var i = 0; i < data.length; i++)
			{
				var item = BX.CmrSelectorMenuItem.create(data[i]);
				item.addOnSelectListener(this._itemSelectHandler);
				this._items.push(item);
			}
		},
		isOpened: function()
		{
			return this._isOpened;
		},
		open: function(anchor)
		{
			if(this._isOpened)
			{
				return;
			}

			var menuItems = [];
			for(var i = 0; i < this._items.length; i++)
			{
				var item = this._items[i];
				if(item.isEnabled())
				{
					menuItems.push(item.createMenuItem(this._encodeItems));
				}
			}

			BX.PopupMenu.show(
				this._id,
				anchor,
				menuItems,
				{
					"offsetTop": 0,
					"offsetLeft": 0,
					"events":
					{
						"onPopupShow": BX.delegate(this.onPopupShow, this),
						"onPopupClose": BX.delegate(this.onPopupClose, this),
						"onPopupDestroy": BX.delegate(this.onPopupDestroy, this)
					}
				}
			);
			this._popup = BX.PopupMenu.currentItem;
		},
		close: function()
		{
			if (this._popup && this._popup.popupWindow)
			{
				this._popup.popupWindow.close();
			}
		},
		addOnSelectListener: function(listener)
		{
			this._onSelectNotifier.addListener(listener);
		},
		removeOnSelectListener: function(listener)
		{
			this._onSelectNotifier.removeListener(listener);
		},
		onItemSelect: function(item)
		{
			this.close();
			this._onSelectNotifier.notify([item]);
		},
		onPopupShow: function()
		{
			this._isOpened = true;
		},
		onPopupClose: function()
		{
			if(this._popup)
			{
				if(this._popup.popupWindow)
				{
					this._popup.popupWindow.destroy();
				}
			}
		},
		onPopupDestroy: function()
		{
			this._isOpened = false;
			this._popup = null;

			if(typeof(BX.PopupMenu.Data[this._id]) !== "undefined")
			{
				delete(BX.PopupMenu.Data[this._id]);
			}
		}
	};
	BX.CmrSelectorMenu.create = function(id, settings)
	{
		var self = new BX.CmrSelectorMenu();
		self.initialize(id, settings);
		return self;
	};
}
//endregion

if(typeof(BX.CrmSelector) === "undefined")
{
	BX.CrmSelector = function()
	{
		this._id = "";
		this._selectedValue = "";
		this._settings = {};
		this._outerWrapper = this._wrapper = this._container = this._view = null;
		this._items = [];
		this._encodeItems = true;
		this._onSelectNotifier = null;
		this._popup = null;
		this._isPopupShown = false;
	};

	BX.CrmSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : ("crm_selector_" + Math.random().toString().substring(2));
			this._settings = settings ? settings : {};
			this._container = this.getSetting("container", null);
			this._selectedValue = this.getSetting("selectedValue", "");

			this._encodeItems = !!this.getSetting("encodeItems", true);
			var itemData = this.getSetting("items");
			itemData = BX.type.isArray(itemData) ? itemData : [];
			this._items = [];
			for(var i = 0; i < itemData.length; i++)
			{
				var item = BX.CmrSelectorMenuItem.create(itemData[i]);
				item.addOnSelectListener(BX.delegate(this._onItemSelect, this));
				this._items.push(item);
			}

			this._onSelectNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			var s = this._settings;
			return typeof(s[name]) != "undefined" ? s[name] : defaultval;
		},
		isEnabled: function()
		{
			return this.getSetting('enabled', true);
		},
		layout: function(container)
		{
			if(BX.type.isDomNode(container))
			{
				this._container = container;
			}
			else if(this._container)
			{
				container = this._container;
			}

			if(!container)
			{
				return;
			}

			var isEnabled = this.isEnabled();

			var layout = this.getSetting('layout');
			if(!layout)
			{
				layout = {};
			}

			var outerWrapper = this._outerWrapper = BX.create(
				"DIV",
				{
					"attrs":
					{
						"className": "crm-selector-container",
						"id": this._id
					}
				}
			);

			if(layout['position'] === 'first')
			{
				container.insertBefore(outerWrapper, BX.firstChild(container));
			}
			else if(layout['insertBefore'])
			{
				container.insertBefore(outerWrapper, BX.findChild(container, layout['insertBefore']));
			}
			else
			{
				container.appendChild(outerWrapper);
			}

			var offset = BX.type.isPlainObject(layout['offset']) ? layout['offset'] : {};
			if(BX.type.isNotEmptyString(offset['left']))
			{
				outerWrapper.style.marginLeft = offset['left'];
			}
			if(BX.type.isNotEmptyString(offset['right']))
			{
				outerWrapper.style.marginRight = offset['right'];
			}

			var title = this.getSetting("title", "");
			if(BX.type.isNotEmptyString(title))
			{
				outerWrapper.appendChild(
					BX.create(
						"SPAN",
						{
							"attrs":
							{
								"className": "crm-selector-title"
							},
							"text": title + ':'
						}
					)
				);
			}

			var wrapper = this._wrapper = BX.create(
				"DIV",
				{
					"attrs":
					{
						"className": "crm-selector-wrapper"
					}
				}
			);
			outerWrapper.appendChild(wrapper);

			var onClickHandler = BX.delegate(this._onClick, this);

			var innerWrapper = BX.create(
				"DIV",
				{
					"attrs":
					{
						"className": "crm-selector-inner-wrapper"
					}
				}
			);
			if(isEnabled)
			{
				BX.bind(innerWrapper, "click", onClickHandler);
			}
			wrapper.appendChild(innerWrapper);

			var selectItem = this._findItemByValue(this._selectedValue);
			if(!selectItem)
			{
				selectItem = this.getDefaultItem();
			}

			var text = selectItem ? selectItem.getText() : "";
			if(this._encodeItems)
			{
				text = BX.util.htmlspecialchars(text);
			}

			var view = this._view = BX.create(
				"SPAN",
				{
					"attrs":
					{
						"className": "crm-selector-view"
					},
					"html": text
				}
			);
			innerWrapper.appendChild(view);

			if(isEnabled)
			{
				innerWrapper.appendChild(
					BX.create(
						"A",
						{
							"attrs":
							{
								"className": "crm-selector-arrow"
							},
							"events":
							{
								"click": onClickHandler
							},
							"html": "&nbsp;"
						}
					)
				);
			}
		},
		clearLayout: function()
		{
			if(!this._outerWrapper)
			{
				return;
			}

			BX.remove(this._outerWrapper);
			this._outerWrapper = null;
		},
		getItems: function()
		{
			return this._items;
		},
		selectValue: function(value)
		{
			this.selectItem(this._findItemByValue(value));
		},
		selectItem: function(item)
		{
			if(!item)
			{
				return;
			}

			this._selectedValue = item.getValue();
			if(this._view)
			{
				var text = item.getText();
				if(this._encodeItems)
				{
					text = BX.util.htmlspecialchars(text);
				}
				this._view.innerHTML = text;
			}
		},
		getSelectedValue: function()
		{
			return this._selectedValue;
		},
		getSelectedItem: function()
		{
			return this._findItemByValue(this._selectedValue);
		},
		getDefaultItem: function()
		{
			var items = this.getItems();
			for(var i = 0; i < items.length; i++)
			{
				var item = items[i];
				if(item.isDefault())
				{
					return item;
				}
			}

			return null;
		},
		showPopup: function()
		{
			if(this._isPopupShown)
			{
				return;
			}

			var menuItems = [];
			for(var i = 0; i < this._items.length; i++)
			{
				var item = this._items[i];
				if(item.isEnabled())
				{
					menuItems.push(item.createMenuItem(this._encodeItems));
				}
			}

			BX.PopupMenu.show(
				this._id,
				this._wrapper,
				menuItems,
				{
					"offsetTop": 0,
					"offsetLeft": 0,
					"events":
					{
						"onPopupShow": BX.delegate(this._onPopupShow, this),
						"onPopupClose": BX.delegate(this._onPopupClose, this),
						"onPopupDestroy": BX.delegate(this._onPopupDestroy, this)
					}
				}
			);
			this._popup = BX.PopupMenu.currentItem;
		},
		addOnSelectListener: function(listener)
		{
			this._onSelectNotifier.addListener(listener);
		},
		removeOnSelectListener: function(listener)
		{
			this._onSelectNotifier.removeListener(listener);
		},
		_findItemByValue: function(value)
		{
			var items = this.getItems();
			for(var i = 0; i < items.length; i++)
			{
				var item = items[i];
				if(value === item.getValue())
				{
					return item;
				}
			}

			return null;
		},
		_onClick: function(e)
		{
			e = e ? e : window.event;
			BX.PreventDefault(e);
			if(this.isEnabled())
			{
				this.showPopup();
			}
		},
		_onItemSelect: function (item)
		{
			this.selectItem(item);

			if (this._popup)
			{
				if (this._popup.popupWindow)
				{
					this._popup.popupWindow.close();
				}
			}

			this._onSelectNotifier.notify([item]);
		},
		_onPopupShow: function()
		{
			this._isPopupShown = true;
		},
		_onPopupClose: function()
		{
			if(this._popup)
			{
				if(this._popup.popupWindow)
				{
					this._popup.popupWindow.destroy();
				}
			}
		},
		_onPopupDestroy: function()
		{
			this._isPopupShown = false;
			this._popup = null;

			if(typeof(BX.PopupMenu.Data[this._id]) !== "undefined")
			{
				delete(BX.PopupMenu.Data[this._id]);
			}
		}
	};

	BX.CrmSelector.create = function(id, settings)
	{
		var self = new BX.CrmSelector();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};

	BX.CrmSelector.deleteItem = function(id)
	{
	if(this.items[id])
	{
		this.items[id].clearLayout();
		delete this.items[id];
	}
};

	BX.CrmSelector.items = {};
}

if(typeof(BX.CrmInterfaceFormUtil) === "undefined")
{
	BX.CrmInterfaceFormUtil = function(){};
	BX.CrmInterfaceFormUtil.disableThemeSelection = function(formId)
	{
		var form = window["bxForm_" + formId];
		var menu = form ? form.settingsMenu : null;
		if(!menu)
		{
			return;
		}

		for(var i = 0; i < menu.length; i++)
		{
			if(menu[i] && menu[i].ICONCLASS === "form-themes")
			{
				menu.splice(i, 1);
				break;
			}
		}

		if(menu.length === 0)
		{
			var btn = BX.findChild(BX("form_" + formId), { "tag":"A", "class": "bx-context-button bx-form-menu" }, true);
			if(btn)
			{
				btn.style.display = "none";
			}
		}
	};

	BX.CrmInterfaceFormUtil.showFormRow = function(show, element)
	{
		var row = BX.findParent(element, {'tag': 'TR'});
		if(row)
		{
			row.style.display = !!show ? '' : 'none';
		}
	}
}

if(typeof(BX.CrmParamBag) === "undefined")
{
	BX.CrmParamBag = function()
	{
		this._params = {};
	};

	BX.CrmParamBag.prototype =
	{
		initialize: function(params)
		{
			this._params = params ? params : {};
		},
		getParam: function(name, defaultvalue)
		{
			var p = this._params;
			return typeof(p[name]) != "undefined" ? p[name] : defaultvalue;
		},
		getIntParam: function(name, defaultvalue)
		{
			if(typeof(defaultvalue) === "undefined")
			{
				defaultvalue = 0;
			}
			var p = this._params;
			return typeof(p[name]) != "undefined" ? parseInt(p[name]) : defaultvalue;
		},
		getBooleanParam: function(name, defaultvalue)
		{
			if(typeof(defaultvalue) === "undefined")
			{
				defaultvalue = 0;
			}
			var p = this._params;
			return typeof(p[name]) != "undefined" ? !!p[name] : defaultvalue;
		},
		setParam: function(name, value)
		{
			this._params[name] = value;
		},
		clear: function()
		{
			this._params = {};
		},
		merge: function(params)
		{
			this._params = BX.util.objectMerge(this._params, params);
		}
	};

	BX.CrmParamBag.create = function(params)
	{
		var self = new BX.CrmParamBag();
		self.initialize(params);
		return self;
	}
}

if(typeof(BX.CrmSubscriber) === "undefined")
{
	BX.CrmSubscriber = function()
	{
		this._id = "";
		this._element = null;
		this._eventName = "";
		this._callback = null;
		this._settings = null;
		this._handler = BX.delegate(this._onElementEvent, this);
	};

	BX.CrmSubscriber.prototype =
	{
		initialize: function(id, element, eventName, callback, settings)
		{
			this._id = id;
			this._element = element;
			this._eventName = eventName;
			this._callback = callback;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
		},
		getSetting: function(name, defaultvalue)
		{
			return this._settings.getParam(name, defaultvalue);
		},
		setSetting: function(name, value)
		{
			return this._settings.setParam(name, value);
		},
		getId: function()
		{
			return this._id;
		},
		getElement: function()
		{
			return this._element;
		},
		getEventName: function()
		{
			return this._eventName;
		},
		getCallback: function()
		{
			return this._callback;
		},
		subscribe: function()
		{
			BX.bind(this.getElement(), this.getEventName(), this._handler);
		},
		unsubscribe: function()
		{
			BX.unbind(this.getElement(), this.getEventName(), this._handler);
		},
		_onElementEvent: function(e)
		{
			var callback = this.getCallback();
			if(BX.type.isFunction(callback))
			{
				callback(this, { "event": e });
			}

			return this.getSetting("preventDefault", false) ? BX.PreventDefault(e) : true;
		}
	};

	BX.CrmSubscriber.items = {};
	BX.CrmSubscriber.create = function(id, element, eventName, callback, settings)
	{
		var self = new BX.CrmSubscriber();
		self.initialize(id, element, eventName, callback, settings);
		this.items[id] = self;
		return self;
	};

	BX.CrmSubscriber.subscribe = function(id, element, eventName, callback, settings)
	{
		var self = this.create(id, element, eventName, callback, settings);
		self.subscribe();
		return self;
	}
}

if(typeof(BX.CrmMultiFieldViewer) === "undefined")
{
	BX.CrmMultiFieldViewer = function()
	{
		this._id = '';
		this._shown = false;
		this._layout = '';
		this._typeName = '';
	};

	BX.CrmMultiFieldViewer.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._layout = this.getSetting('layout', 'grid').toLowerCase();
			this._typeName = this.getSetting('typeName', '');

		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		show: function()
		{
			if(this._shown)
			{
				return;
			}

			var tab = BX.create('TABLE');

			tab.cellSpacing = '0';
			tab.cellPadding = '0';
			tab.border = '0';


			var className = 'bx-crm-grid-multi-field-viewer';
			var enableSip = false;
			var items = this.getSetting('items', []);
			for(var i = 0; i < items.length; i++)
			{
				var item = items[i];

				var r = tab.insertRow(-1);
				var valueCell = r.insertCell(-1);

				var itemHtml = item['value'];
				var itemClassName = "crm-client-contacts-block-text";
				if(this._typeName === "PHONE" && BX.type.isNotEmptyString(item['sipCallHtml']))
				{
					if(!enableSip)
					{
						enableSip = true;
					}
					itemHtml += item['sipCallHtml'];
				}
				valueCell.appendChild(BX.create('SPAN', { attrs: { className: itemClassName }, html: itemHtml }));
				var typeCell = r.insertCell(-1);
				typeCell.appendChild(
					BX.create(
						'SPAN',
						{
							attrs: { className: 'crm-multi-field-value-type' },
							text: BX.type.isNotEmptyString(item['type']) ? item['type'] : ''
						}
					)
				);
			}

			if(enableSip)
			{
				className += ' bx-crm-grid-multi-field-viewer-tel-sip';
			}

			tab.className = className;

			var dlg = BX.CrmMultiFieldViewer.dialogs[this._id] ? BX.CrmMultiFieldViewer.dialogs[this._id] : null;
			if(!dlg)
			{
				var anchor = this.getSetting('anchor');
				if(!BX.type.isElementNode(anchor))
				{
					anchor = BX(this.getSetting('anchorId', ''));
				}

				var topmost = !!this.getSetting('topmost', false);
				dlg = new BX.PopupWindow(
					this._id,
					anchor,
					{
						autoHide: true,
						draggable: false,
						offsetLeft: 0,
						offsetTop: 0,
						bindOptions: { forceBindPosition: true },
						closeByEsc: true,
						zIndex: topmost ? -10 : -14,
						className: 'crm-item-popup-num-block',
						events:
						{
							onPopupShow: BX.delegate(
								function()
								{
									this._shown = true;
								},
								this
							),
							onPopupClose: BX.delegate(
								function()
								{
									this._shown = false;
									BX.CrmMultiFieldViewer.dialogs[this._id].destroy();
								},
								this
							),
							onPopupDestroy: BX.delegate(
								function()
								{
									delete(BX.CrmMultiFieldViewer.dialogs[this._id]);
								},
								this
							)
						},
						content: tab
					}
				);
				BX.CrmMultiFieldViewer.dialogs[this._id] = dlg;
			}

			dlg.show();
		},
		close: function()
		{
			if(this._shown && typeof(BX.CrmMultiFieldViewer.dialogs[this._id]) !== 'undefined')
			{
				BX.CrmMultiFieldViewer.dialogs[this._id].close();
			}
		}
	};
	BX.CrmMultiFieldViewer.items = {};
	BX.CrmMultiFieldViewer.create = function(id, settings)
	{
		var self = new BX.CrmMultiFieldViewer();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
	BX.CrmMultiFieldViewer.ensureCreated = function(id, settings)
	{
		return this.items[id] ? this.items[id] : this.create(id, settings);
	};
	BX.CrmMultiFieldViewer.dialogs = {};
}

if(typeof(BX.CrmSipManager) === "undefined")
{
	BX.CrmSipManager = function()
	{
		this._id = "";
		this._settings = null;
		this._serviceUrls = {};
		this._recipientInfos = {};
	};

	BX.CrmSipManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultvalue)
		{
			return this._settings.getParam(name, defaultvalue);
		},
		setSetting: function(name, value)
		{
			return this._settings.setParam(name, value);
		},
		openPreCallDialog: function(recipient, params, anchor, callback)
		{
			if(!recipient || typeof(recipient) !== "object")
			{
				return;
			}

			if(!params || typeof(params) !== "object")
			{
				params = {};
			}

			var entityType = BX.type.isNotEmptyString(params["ENTITY_TYPE"]) ? params["ENTITY_TYPE"] : "";
			var entityId = BX.type.isNotEmptyString(params["ENTITY_ID"]) ? params["ENTITY_ID"] : "";
			var dlgId = entityType + '_' + entityId.toString();

			var dlg = BX.CrmPreCallDialog.create(dlgId,
				BX.CrmParamBag.create(
					{
						recipient: recipient,
						params: params,
						anchor: anchor,
						closeCallback: callback
					}
				)
			);
			dlg.show();
		},
		setServiceUrl: function(entityTypeName, serviceUrl)
		{
			if(BX.type.isNotEmptyString(entityTypeName) && BX.type.isNotEmptyString(serviceUrl))
			{
				this._serviceUrls[entityTypeName] = serviceUrl;
			}
		},
		getServiceUrl: function(entityTypeName)
		{
			return BX.type.isNotEmptyString(entityTypeName)
				&& this._serviceUrls.hasOwnProperty(entityTypeName)
				? this._serviceUrls[entityTypeName] : "";
		},
		makeCall: function(recipient, params)
		{
			var number = BX.type.isNotEmptyString(recipient["number"]) ? recipient["number"] : "";
			if(number == "")
			{
				return;
			}

			var entityTypeName = BX.type.isNotEmptyString(params["ENTITY_TYPE"]) ? params["ENTITY_TYPE"] : "";
			var entityId = BX.type.isNotEmptyString(params["ENTITY_ID"]) ? parseInt(params["ENTITY_ID"]) : 0;
			if(!(entityTypeName !== "" && entityId > 0))
			{
				entityTypeName = BX.type.isNotEmptyString(recipient["entityTypeName"]) ? recipient["entityTypeName"] : "";
				if(entityTypeName !== "")
				{
					entityTypeName = "CRM_" + entityTypeName.toUpperCase();
				}
				params["ENTITY_TYPE"] = entityTypeName;
				params["ENTITY_ID"] = typeof(recipient["entityId"]) !== "undefined" ? parseInt(recipient["entityId"]) : 0;
			}

			var handlers = [];
			BX.onCustomEvent(
				window,
				'CRM_SIP_MANAGER_MAKE_CALL',
				[this, recipient, params, handlers]
			);

			if(BX.type.isArray(handlers) && handlers.length > 0)
			{
				for(var i = 0; i < handlers.length; i++)
				{
					var handler = handlers[i];
					if(BX.type.isFunction(handler))
					{
						try
						{
							handler(recipient, params);
						}
						catch(ex)
						{
						}
					}
				}
			}
			else if(typeof(top.BXIM) !== "undefined")
			{
				top.BXIM.phoneTo(number, params);
			}
		},
		startCall: function(recipient, params, enablePreCallDialog, anchor)
		{
			enablePreCallDialog = !!enablePreCallDialog;
			if(enablePreCallDialog)
			{
				var enableInfoLoading = typeof(recipient["enableInfoLoading"]) ? recipient["enableInfoLoading"] : false;
				if(enableInfoLoading)
				{
					var entityType = BX.type.isNotEmptyString(params["ENTITY_TYPE"]) ? params["ENTITY_TYPE"] : "";
					var entityId = "";
					if(BX.type.isNotEmptyString(params["ENTITY_ID"]) || BX.type.isNumber(params["ENTITY_ID"]))
					{
						entityId = params["ENTITY_ID"];
					}

					var key = entityType + '_' + entityId.toString();
					if(this._recipientInfos.hasOwnProperty(key))
					{
						var info = this._recipientInfos[key];
						recipient["title"] = BX.type.isNotEmptyString(info["title"]) ? info["title"] : "";
						recipient["legend"] = BX.type.isNotEmptyString(info["legend"]) ? info["legend"] : "";
						recipient["imageUrl"] = BX.type.isNotEmptyString(info["imageUrl"]) ? info["imageUrl"] : "";
						recipient["showUrl"] = BX.type.isNotEmptyString(info["showUrl"]) ? info["showUrl"] : "";
					}
					else
					{
						var serviceUrl = this.getServiceUrl(
							BX.type.isNotEmptyString(params["ENTITY_TYPE"]) ? params["ENTITY_TYPE"] : ""
						);

						if(serviceUrl !== "")
						{
							var loader = BX.CrmSipRecipientInfoLoader.create(
								BX.CrmParamBag.create(
									{
										serviceUrl: serviceUrl,
										recipient: recipient,
										params: params,
										anchor: anchor,
										callback: BX.delegate(this._onRecipientInfoLoad, this)
									}
								)
							);
							loader.process();
							return;
						}
					}
				}

				this.openPreCallDialog(recipient, params, anchor, BX.delegate(this._onPreCallDialogClose, this));
			}
			else
			{
				this.makeCall(recipient, params);
			}
		},
		getMessage: function(name)
		{
			return BX.CrmSipManager.messages && BX.CrmSipManager.messages.hasOwnProperty(name) ? BX.CrmSipManager.messages[name] : "";
		},
		_onPreCallDialogClose: function(dlg, recipient, params, settings)
		{
			if(!params || typeof(params) !== "object")
			{
				params = {};
			}
			this.makeCall(recipient, params);
		},
		_onRecipientInfoLoad: function(loader, recipient, params, anchor, info)
		{
			var entityType = BX.type.isNotEmptyString(params["ENTITY_TYPE"]) ? params["ENTITY_TYPE"] : "";
			var entityId = BX.type.isNotEmptyString(params["ENTITY_ID"]) ? params["ENTITY_ID"] : "";
			var key = entityType + '_' + entityId.toString();
			this._recipientInfos[key] = info;

			recipient["title"] = BX.type.isNotEmptyString(info["title"]) ? info["title"] : "";
			recipient["legend"] = BX.type.isNotEmptyString(info["legend"]) ? info["legend"] : "";
			recipient["imageUrl"] = BX.type.isNotEmptyString(info["imageUrl"]) ? info["imageUrl"] : "";
			recipient["showUrl"] = BX.type.isNotEmptyString(info["showUrl"]) ? info["showUrl"] : "";

			this.openPreCallDialog(recipient, params, anchor, BX.delegate(this._onPreCallDialogClose, this));
		}
	};

	BX.CrmSipManager.items = {};
	BX.CrmSipManager.create = function(id, settings)
	{
		var self = new BX.CrmSipManager();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
	BX.CrmSipManager.current = null;
	BX.CrmSipManager.getCurrent = function()
	{
		if(!this._current)
		{
			this._current = this.create("_CURRENT", null);
		}

		return this._current;
	};
	BX.CrmSipManager.startCall = function(recipient, params, enablePreCallDialog, anchor)
	{
		this.getCurrent().startCall(recipient, params, enablePreCallDialog, anchor);
	};
	BX.CrmSipManager.resolveSipEntityTypeName = function(typeName)
	{
		return BX.type.isNotEmptyString(typeName) ? ("CRM_" + typeName.toUpperCase()) : "";
	};
	BX.CrmSipManager.ensureInitialized = function(params)
	{
		var serviceUrls = BX.type.isPlainObject(params["serviceUrls"]) ? params["serviceUrls"] : null;
		if(serviceUrls)
		{
			for(var typeName in serviceUrls)
			{
				if(!serviceUrls.hasOwnProperty(typeName))
				{
					continue;
				}
				BX.CrmSipManager.getCurrent().setServiceUrl(typeName, serviceUrls[typeName]);
			}
		}


		var messages = BX.type.isPlainObject(params["messages"]) ? params["messages"] : null;
		if(messages)
		{
			BX.CrmSipManager.messages = messages;
		}
	};
}

if(typeof(BX.CrmSipRecipientInfoLoader) === "undefined")
{
	BX.CrmSipRecipientInfoLoader = function()
	{
		this._settings = null;
		this._serviceUrl = null;
		this._recipient = null;
		this._params = null;
		this._anchor = null;
		this._callBack = null;
	};

	BX.CrmSipRecipientInfoLoader.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : BX.CrmParamBag.create(null);

			this._serviceUrl = this.getSetting("serviceUrl", "");

			this._recipient = this.getSetting("recipient");
			if(!this._recipient)
			{
				this._recipient = {};
			}

			this._params = this.getSetting("params");
			if(!this._params)
			{
				this._params = {};
			}

			this._anchor = this.getSetting("anchor", null);

			this._callBack = this.getSetting("callback");
			if(!BX.type.isFunction(this._callBack))
			{
				this._callBack = null;
			}
		},
		getSetting: function(name, defaultvalue)
		{
			return this._settings.getParam(name, defaultvalue);
		},
		setSetting: function(name, value)
		{
			return this._settings.setParam(name, value);
		},
		process: function()
		{
			var params = this._params;
			var entityTypeName = BX.type.isNotEmptyString(params["ENTITY_TYPE"]) ? params["ENTITY_TYPE"] : "";
			var entityId = typeof(params["ENTITY_ID"]) !== "undefined" ? parseInt(params["ENTITY_ID"]) : 0;
			var serviceUrl = this._serviceUrl;
			var callBack = this._callBack;

			if(entityTypeName  === "" || entityId <= 0 || serviceUrl === "")
			{
				if(BX.type.isFunction(this._callBack))
				{
					callBack(this, this._recipient, this._params, this._anchor, {});
				}
				return;
			}

			BX.ajax(
				{
					url: serviceUrl,
					method: "POST",
					dataType: "json",
					data:
					{
						"MODE" : "GET_ENTITY_SIP_INFO",
						"ENITY_TYPE" : entityTypeName,
						"ENITY_ID" : entityId
					},
					onsuccess: BX.delegate(this._onSuccess, this)
					//onfailure: function(data){}
				}
			);
		},
		_onSuccess: function(result)
		{
			var callBack = this._callBack;
			if(!BX.type.isFunction(callBack))
			{
				return;
			}

			var data = typeof(result["DATA"]) !== "undefined" ? result["DATA"] : {};
			var title = BX.type.isNotEmptyString(data["TITLE"]) ? data["TITLE"] : "";
			var legend = BX.type.isNotEmptyString(data["LEGEND"]) ? data["LEGEND"] : "";
			var imageUrl = BX.type.isNotEmptyString(data["IMAGE_URL"]) ? data["IMAGE_URL"] : "";
			var showUrl = BX.type.isNotEmptyString(data["SHOW_URL"]) ? data["SHOW_URL"] : "";

			try
			{
				callBack(
					this,
					this._recipient,
					this._params,
					this._anchor,
					{ title: title, legend: legend, showUrl: showUrl, imageUrl: imageUrl }
				);
			}
			catch(ex)
			{
			}
		}
	};

	BX.CrmSipRecipientInfoLoader.create = function(settings)
	{
		var self = new BX.CrmSipRecipientInfoLoader();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.CrmPreCallDialog) === "undefined")
{
	BX.CrmPreCallDialog = function()
	{
		this._id = "";
		this._settings = null;
		this._recipient = null;
		this._params = null;
		this._anchor = null;
		this._dlg = null;
		this._isShown = false;
		this._makeCallButton = null;
		this._closeCallBack = null;
		this._onMakeCallButtonClickHandler = BX.delegate(this._onMakeCallButtonClick, this);
	};

	BX.CrmPreCallDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);

			this._recipient = this.getSetting("recipient");
			if(!this._recipient)
			{
				this._recipient = {};
			}

			this._params = this.getSetting("params");
			if(!this._params)
			{
				this._params = {};
			}

			this._anchor = this.getSetting("anchor", null);

			this._closeCallBack = this.getSetting("closeCallback");
			if(!BX.type.isFunction(this._closeCallBack))
			{
				this._closeCallBack = null;
			}
		},
		getSetting: function(name, defaultvalue)
		{
			return this._settings.getParam(name, defaultvalue);
		},
		setSetting: function(name, value)
		{
			return this._settings.setParam(name, value);
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			return BX.CrmSipManager.messages && BX.CrmSipManager.messages.hasOwnProperty(name) ? BX.CrmSipManager.messages[name] : "";
		},
		show: function()
		{
			if(this._isShown)
			{
				return;
			}

			this._dlg = BX.PopupWindowManager.create(
				this._id.toLowerCase() + "-pre-call",
				this._anchor,
				{
					content: this._preparePreCallDialogContent(),
					closeIcon: true,
					closeByEsc: true,
					lightShadow: true,
					angle:{ offset: 5 },
					zIndex: 200, //For balloons
					events:
					{
						onPopupClose: BX.delegate(this._onDialogClose, this)
					}
				}
			);

			if(!this._dlg.isShown())
			{
				this._dlg.show();
			}
			this._isShown = this._dlg.isShown();
		},
		close: function()
		{
			if(!this._isShown)
			{
				return;
			}

			if(this._dlg)
			{
				this._dlg.close();
				this._isShown = this._dlg.isShown();
			}
			else
			{
				this._isShown = false;
			}
		},
		_preparePreCallDialogContent: function()
		{
			var recipient = this._recipient;

			var container = BX.create(
				"DIV",
				{ attrs: { className: "crm-tel-popup" } }
			);

			var userWrapper = BX.create(
				"DIV",
				{ attrs: { className: "crm-tel-popup-user" } }
			);
			container.appendChild(userWrapper);

			var userAvatar = BX.create(
				"DIV",
				{ attrs: { className: "crm-tel-avatar" } }
			);
			var imageUrl = BX.type.isNotEmptyString(recipient["imageUrl"]) ? recipient["imageUrl"] : "";
			if(imageUrl !== "")
			{
				userAvatar.style.background = "url(" + imageUrl + ") no-repeat 3px 3px";
			}

			userWrapper.appendChild(userAvatar);
			userWrapper.appendChild(
				BX.create("DIV", { attrs: { className: "crm-tel-user-alignment" } })
			);

			var title = BX.type.isNotEmptyString(recipient["title"]) ? recipient["title"] : this.getMessage("unknownRecipient");
			var legend = BX.type.isNotEmptyString(recipient["legend"]) ? recipient["legend"] : "";
			var showUrl = BX.type.isNotEmptyString(recipient["showUrl"]) ? recipient["showUrl"] : "#";
			userWrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-tel-user-data" },
						children:
						[
							BX.create("A",
								{
									attrs: { className: "crm-tel-user-name", target: "_blank", href: showUrl },
									text: title
								}
							),
							BX.create("DIV",
								{
									attrs: { className: "crm-tel-user-organ" },
									text: legend
								}
							)
						]
					}
				)
			);

			var number = BX.type.isNotEmptyString(recipient["number"]) ? recipient["number"] : "-";
			var chkBxId = this._id.toLowerCase() + "_enable_recordind";

			var settingsWrapper = BX.create(
				"DIV",
				{
					attrs: { className: "crm-tel-popup-num-block" },
					children:
					[
						BX.create("DIV",
							{
								attrs: { className: "crm-tel-popup-num" },
								text: number
							}
						)
					]
				}
			);
			container.appendChild(settingsWrapper);

			var buttonWrapper = BX.create(
				"DIV",
				{ attrs: { className: "crm-tel-popup-footer" } }
			);
			container.appendChild(buttonWrapper);

			this._makeCallButton = BX.create("SPAN",
				{
					attrs: { className: "crm-tel-popup-call-btn" },
					text: this.getMessage("makeCall")
				}
			);
			BX.bind(this._makeCallButton, "click", this._onMakeCallButtonClickHandler);
			buttonWrapper.appendChild(this._makeCallButton);

			return container;
		},
		_onMakeCallButtonClick: function(e)
		{
			if(!this._isShown)
			{
				return;
			}

			if(this._dlg)
			{
				this._dlg.close();
			}
			this._isShown = this._dlg ? this._dlg.isShown() : false;

			BX.unbind(this._makeCallButton, "click", this._onMakeCallButtonClickHandler);

			if(this._closeCallBack)
			{
				try
				{
					this._closeCallBack(this, this._recipient, this._params, {});
				}
				catch(ex)
				{
				}
			}
		},
		_onDialogClose: function(e)
		{
			if(this._dlg)
			{
				this._dlg.destroy();
				this._dlg = null;
			}

			this._isShown = false;
		}
	};

	BX.CrmPreCallDialog.create = function(id, settings)
	{
		var self = new BX.CrmPreCallDialog();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmBizprocDispatcher) === "undefined")
{
	BX.CrmBizprocDispatcher = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._wrapper = null;
		this._serviceUrl = "";
		this._entityTypeName = "";
		this._entityId = 0;
		this._formId = "";
		this._tabId = "tab_bizproc";
		this._currentPage = "";
		this._formManager = null;

		this._isRequestRunning = false;
		this._isLoaded = false;

		this._waiter = null;
		this._scrollHandler = BX.delegate(this._onWindowScroll, this);
		this._formManagerHandler = BX.delegate(this._onFormManagerCreate, this);
	};

	BX.CrmBizprocDispatcher.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_bp_disp_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._container = BX(this.getSetting("containerID", ""));
			if(!this._container)
			{
				throw "BX.CrmBizprocDispatcher. Could not find container.";
			}
			this._wrapper = BX.findParent(this._container, { "tagName": "DIV", "className": "bx-edit-tab-inner" });

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.CrmBizprocDispatcher. Could not find service url.";
			}

			this._entityTypeName = this.getSetting("entityTypeName", "");
			if(!BX.type.isNotEmptyString(this._entityTypeName))
			{
				throw "BX.CrmBizprocDispatcher. Could not find entity type name.";
			}

			this._entityId = parseInt(this.getSetting("entityID", 0));
			if(!BX.type.isNumber(this._entityId) || this._entityId <= 0)
			{
				throw "BX.CrmBizprocDispatcher. Could not find entity id.";
			}

			this._formId = this.getSetting("formID", "");
			if(!BX.type.isNotEmptyString(this._formId))
			{
				throw "BX.CrmBizprocDispatcher. Could not find form id.";
			}

			var formManager = window["bxForm_" + this._formId];
			if(formManager)
			{
				this.setFormManager(formManager);
			}
			else
			{
				BX.addCustomEvent(window, "CrmInterfaceFormCreated", this._formManagerHandler);
			}

			this._currentPage = this.getSetting("currentPage", "");
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getContainerRect: function()
		{
			var r = this._container.getBoundingClientRect();
			return(
				{
					top: r.top, bottom: r.bottom, left: r.left, right: r.right,
					width: typeof(r.width) !== "undefined" ? r.width : (r.right - r.left),
					height: typeof(r.height) !== "undefined" ? r.height : (r.bottom - r.top)
				}
			);
		},
		isContanerInClientRect: function()
		{
			return this.getContainerRect().top <= document.documentElement.clientHeight;
		},
		setFormManager: function(formManager)
		{
			if(this._formManager === formManager)
			{
				return;
			}

			this._formManager = formManager;
			if(!this._formManager)
			{
				return;
			}

			if(this._formManager.GetActiveTabId() !== this._tabId)
			{
				BX.addCustomEvent(window, 'BX_CRM_INTERFACE_FORM_TAB_SELECTED', BX.delegate(this._onFormTabSelect, this));
			}
			else
			{
				if(this.isContanerInClientRect())
				{
					this.loadIndex();
				}
				else
				{
					BX.bind(window, "scroll", this._scrollHandler);
				}
			}
		},
		loadIndex: function()
		{
			if(this._isLoaded)
			{
				return;
			}

			if(this._currentPage === "index")
			{
				return;
			}

			var result = this._startRequest(
				"INDEX",
				{
					"FORM_ID": this.getSetting("formID", ""),
					"PATH_TO_ENTITY_SHOW": this.getSetting("pathToEntityShow", "")
				}
			);

			if(result)
			{
				this._currentPage = "index";
			}
		},
		_startRequest: function(action, params)
		{
			if(this._isRequestRunning)
			{
				return false;
			}

			this._isRequestRunning = true;
			this._waiter = BX.showWait(this._container);
			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "html",
					data:
					{
						"ACTION" : action,
						"ENTITY_TYPE_NAME": this._entityTypeName,
						"ENTITY_ID": this._entityId,
						"PARAMS": params
					},
					onsuccess: BX.delegate(this._onRequestSuccess, this),
					onfailure: BX.delegate(this._onRequestFailure, this)
				}
			);

			return true;
		},
		_onRequestSuccess: function(data)
		{
			this._isRequestRunning = false;

			if(this._waiter)
			{
				BX.closeWait(this._container, this._waiter);
				this._waiter = null;
			}

			this._container.innerHTML = data;
			this._isLoaded = true;
		},
		_onRequestFailure: function(data)
		{
			this._isRequestRunning = false;

			if(this._waiter)
			{
				BX.closeWait(this._container, this._waiter);
				this._waiter = null;
			}
			this._isLoaded = true;
		},
		_onFormManagerCreate: function(formManager)
		{
			if(formManager["name"] === this._formId)
			{
				BX.removeCustomEvent(window, "CrmInterfaceFormCreated", this._formManagerHandler);
				this.setFormManager(formManager);
			}
		},
		_onFormTabSelect: function(sender, formId, tabId, tabContainer)
		{
			if(this._formId === formId && (tabId === this._tabId || this._wrapper === tabContainer))
			{
				this.loadIndex();
			}
		},
		_onWindowScroll: function(e)
		{
			if(!this._isLoaded && !this._isRequestRunning && this.isContanerInClientRect())
			{
				BX.unbind(window, "scroll", this._scrollHandler);
				this.loadIndex();
			}
		}
	};

	BX.CrmBizprocDispatcher.items = {};
	BX.CrmBizprocDispatcher.create = function(id, settings)
	{
		var self = new BX.CrmBizprocDispatcher();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.CrmEntityTreeDispatcher) === 'undefined')
{
	BX.CrmEntityTreeDispatcher = function()
	{
		this._id = '';
		this._settings = {};
		this._container = null;
		this._subContainer = null;
		this._wrapper = null;
		this._serviceUrl = '';
		this._entityTypeName = '';
		this._entityId = 0;
		this._formId = '';
		this._tabId = 'tab_tree';
		this._formManager = null;

		this._isRequestRunning = false;
		this._isLoaded = false;

		this._waiter = null;
		this._scrollHandler = BX.delegate(this._onWindowScroll, this);
		this._formManagerHandler = BX.delegate(this._onFormManagerCreate, this);
	};

	BX.CrmEntityTreeDispatcher.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : 'crm_tree_disp_' + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._container = BX(this.getSetting('containerID', ''));
			if(!this._container)
			{
				throw 'BX.CrmEntityTreeDispatcher. Could not find container.';
			}
			this._wrapper = BX.findParent(this._container, { 'tagName': 'DIV', 'className': 'bx-edit-tab-inner' });

			this._serviceUrl = this.getSetting('serviceUrl', '');
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw 'BX.CrmEntityTreeDispatcher. Could not find service url.';
			}

			this._entityTypeName = this.getSetting('entityTypeName', '');
			if(!BX.type.isNotEmptyString(this._entityTypeName))
			{
				throw 'BX.CrmEntityTreeDispatcher. Could not find entity type name.';
			}

			this._entityId = parseInt(this.getSetting('entityID', 0));
			if(!BX.type.isNumber(this._entityId) || this._entityId <= 0)
			{
				throw 'BX.CrmEntityTreeDispatcher. Could not find entity id.';
			}

			this._formId = this.getSetting('formID', '');
			if(!BX.type.isNotEmptyString(this._formId))
			{
				throw 'BX.CrmEntityTreeDispatcher. Could not find form id.';
			}

			var formManager = window['bxForm_' + this._formId];
			if(formManager)
			{
				this.setFormManager(formManager);
				if (settings.selected === true)
				{
					formManager.SelectTab(this._tabId);
				}
			}
			else
			{
				BX.addCustomEvent(window, 'CrmInterfaceFormCreated', this._formManagerHandler);
			}

			this._moreButtonClickHandler = BX.delegate(this._handleMoreButtonClickHandler, this);
			this._entityButtonClickHandler = BX.delegate(this._handleEntityButtonClickHandler, this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getContainerRect: function()
		{
			var r = this._container.getBoundingClientRect();
			return(
				{
					top: r.top, bottom: r.bottom, left: r.left, right: r.right,
					width: typeof(r.width) !== 'undefined' ? r.width : (r.right - r.left),
					height: typeof(r.height) !== 'undefined' ? r.height : (r.bottom - r.top)
				}
			);
		},
		isContanerInClientRect: function()
		{
			return this.getContainerRect().top <= document.documentElement.clientHeight;
		},
		setFormManager: function(formManager)
		{
			if(this._formManager === formManager)
			{
				return;
			}

			this._formManager = formManager;
			if(!this._formManager)
			{
				return;
			}

			if(this._formManager.GetActiveTabId() !== this._tabId)
			{
				BX.addCustomEvent(window, 'BX_CRM_INTERFACE_FORM_TAB_SELECTED', BX.delegate(this._onFormTabSelect, this));
			}
			else
			{
				if(this.isContanerInClientRect())
				{
					this.loadIndex();
				}
				else
				{
					BX.bind(window, 'scroll', this._scrollHandler);
				}
			}
		},
		_startRequest: function(addParams)
		{
			if(this._isRequestRunning)
			{
				return false;
			}

			var params = {
				FORM_ID: this.getSetting('formID', ''),
				PATH_TO_LEAD_SHOW: this.getSetting('pathToLeadShow', ''),
				PATH_TO_CONTACT_SHOW: this.getSetting('pathToContactShow', ''),
				PATH_TO_COMPANY_SHOW: this.getSetting('pathToCompanyShow', ''),
				PATH_TO_DEAL_SHOW: this.getSetting('pathToDealShow', ''),
				PATH_TO_QUOTE_SHOW: this.getSetting('pathToQuoteShow', ''),
				PATH_TO_INVOICE_SHOW: this.getSetting('pathToInvoiceShow', ''),
				PATH_TO_USER_PROFILE: this.getSetting('pathToUserProfile', '')
			};

			params = BX.mergeEx(params, addParams);

			this._isRequestRunning = true;
			this._waiter = BX.showWait(this._container);
			BX.ajax(
				{
					url: this._serviceUrl,
					method: 'POST',
					dataType: 'html',
					data:
					{
						ADDITIONAL_PARAMS : 'active_tab=' + this._tabId,
						ENTITY_TYPE_NAME: params.ENTITY_TYPE_NAME ? params.ENTITY_TYPE_NAME : this._entityTypeName,
						ENTITY_ID: params.ENTITY_ID ? params.ENTITY_ID : this._entityId,
						PARAMS: params
					},
					onsuccess: BX.delegate(this._onRequestSuccess, this),
					onfailure: BX.delegate(this._onRequestFailure, this)
				}
			);

			return true;
		},
		_onRequestSuccess: function(data)
		{
			this._isRequestRunning = false;

			if(this._waiter)
			{
				BX.closeWait(this._container, this._waiter);
				this._waiter = null;
			}

			if (this._subContainer !== null)
			{
				BX.insertAfter(BX.create('DIV', {html: data}), this._subContainer);
			}
			else
			{
				this._container.innerHTML = data;
			}

			this._isLoaded = true;

			var _this = this;
			var moreButton = BX.findChild(this._container, {class: 'crm-entity-more'}, true, true);
			var entityButton = false;//BX.findChild(this._container, {class: 'crm-tree-link'}, true, true);
			if (moreButton)
			{
				for(var i = 0; i < moreButton.length; i++)
				{
					BX.bind(moreButton[i], 'click', this._moreButtonClickHandler);
				}
			}
			if (entityButton)
			{
				for(var i = 0; i < entityButton.length; i++)
				{
					BX.bind(entityButton[i], 'click', this._entityButtonClickHandler);
				}
			}
		},
		_onRequestFailure: function(data)
		{
			this._isRequestRunning = false;

			if(this._waiter)
			{
				BX.closeWait(this._container, this._waiter);
				this._waiter = null;
			}
			this._isLoaded = true;
		},
		_handleMoreButtonClickHandler: function()
		{
			var target = BX.proxy_context;

			this._subContainer = BX.findParent(target);

			BX.remove(target);

			var page = parseInt(BX.data(target, 'page')) + 1;
			BX.data(target, 'page', page);

			this._startRequest({
				BLOCK: BX.data(target, 'block'),
				BLOCK_PAGE: page
			});
		},
		_handleEntityButtonClickHandler: function(e)
		{
			var target = BX.proxy_context;
			this._subContainer = null;
			this._startRequest({
				ENTITY_ID: BX.data(target, 'id'),
				ENTITY_TYPE_NAME: BX.data(target, 'type')
			});
			e.preventDefault();
		},
		_onFormManagerCreate: function(formManager)
		{
			if(formManager['name'] === this._formId)
			{
				BX.removeCustomEvent(window, 'CrmInterfaceFormCreated', this._formManagerHandler);
				this.setFormManager(formManager);
			}
		},
		_onFormTabSelect: function(sender, formId, tabId, tabContainer)
		{
			if(this._formId === formId && (tabId === this._tabId || this._wrapper === tabContainer))
			{
				this._startRequest();
			}
		},
		_onWindowScroll: function(e)
		{
			if(!this._isLoaded && !this._isRequestRunning && this.isContanerInClientRect())
			{
				BX.unbind(window, 'scroll', this._scrollHandler);
				this._startRequest();
			}
		}
	};
	BX.CrmEntityTreeDispatcher.items = {};
	BX.CrmEntityTreeDispatcher.create = function(id, settings)
	{
		var self = new BX.CrmEntityTreeDispatcher();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.CrmLongRunningProcessState) === "undefined")
{
	BX.CrmLongRunningProcessState =
	{
		intermediate: 0,
		running: 1,
		completed: 2,
		stoped: 3,
		error: 4
	};
}

if(typeof(BX.CrmLongRunningProcessDialog) === "undefined")
{
	BX.CrmLongRunningProcessDialog = function()
	{
		this._id = "";
		this._settings = {};
		this._serviceUrl = "";
		this._controller = "";
		this._method = "POST";
		this._params = {};
		this._option = {};
		this._initialOptions = {};
		this._dlg = null;
		this._buttons = {};
		this._summary = null;
		this._progressUI = null;
		this._progressbar = null;
		this._initialOptionsBlock = null;
		this._isSummaryHtml = false;
		this._isShown = false;
		this._state = BX.CrmLongRunningProcessState.intermediate;
		this._cancelRequest = false;
		this._requestIsRunning = false;
		this._networkErrorCount = 0;
		this._requestHandler = null;
	};
	BX.CrmLongRunningProcessDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_long_run_proc_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._method = this.getSetting("method", "POST");
			this._controller = this.getSetting("controller", "");
			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._controller) && !BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.CrmLongRunningProcess. Could not find service url or ajax controller.";
			}

			this._action = this.getSetting("action", "");
			if(!BX.type.isNotEmptyString(this._action))
			{
				throw "BX.CrmLongRunningProcess. Could not find action.";
			}

			this._params = this.getSetting("params");
			if(!this._params)
			{
				this._params = {};
			}

			this._initialOptions = this.getSetting("initialOptions");
			if(!this._initialOptions)
			{
				this._initialOptions = {};
			}

			this._isSummaryHtml = !!(this.getSetting("isSummaryHtml", false));

			if(typeof(BX.UI) != "undefined" && typeof(BX.UI.ProgressBar) != "undefined")
			{
				this._progressUI = new BX.UI.ProgressBar({
					statusType: BX.UI.ProgressBar.Status.COUNTER,
					size: BX.UI.ProgressBar.Size.LARGE,
					fill: true
				});
			}

			this._requestHandler = this.getSetting("requestHandler", null);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getMessage: function(name)
		{
			return BX.CrmLongRunningProcessDialog.messages && BX.CrmLongRunningProcessDialog.messages.hasOwnProperty(name) ? BX.CrmLongRunningProcessDialog.messages[name] : "";
		},
		getState: function()
		{
			return this._state;
		},
		getServiceUrl: function()
		{
			return this._serviceUrl;
		},
		getAction: function()
		{
			return this._action;
		},
		setAction: function(action)
		{
			this._action = action;
		},
		getParams: function()
		{
			return this._params;
		},
		show: function()
		{
			if(this._isShown)
			{
				return;
			}

			this._dlg = BX.PopupWindowManager.create(
				this._id.toLowerCase(),
				this._anchor,
				{
					className: "bx-crm-dialog-wrap bx-crm-dialog-long-run-proc",
					autoHide: false,
					bindOptions: { forceBindPosition: false },
					buttons: this._prepareDialogButtons(),
					//className: "",
					closeByEsc: false,
					closeIcon: false,
					content: this._prepareDialogContent(),
					draggable: true,
					events: { onPopupClose: BX.delegate(this._onDialogClose, this) },
					offsetLeft: 0,
					offsetTop: 0,
					titleBar: this.getSetting("title", ""),
					overlay: true
				}
			);
			if(!this._dlg.isShown())
			{
				this._dlg.show();
			}
			this._isShown = this._dlg.isShown();
		},
		close: function()
		{
			if(!this._isShown)
			{
				return;
			}

			if(this._dlg)
			{
				this._dlg.close();
			}
			this._isShown = false;
		},
		start: function()
		{
			if(
				this._state === BX.CrmLongRunningProcessState.intermediate ||
				this._state === BX.CrmLongRunningProcessState.stoped ||
				this._state === BX.CrmLongRunningProcessState.completed
			)
			{
				this._startRequest();
			}
		},
		stop: function()
		{
			if(this._state === BX.CrmLongRunningProcessState.running)
			{
				this._stopRequest();
			}
		},
		_prepareDialogContent: function()
		{
			var summary = this.getSetting("summary", "");
			var summaryData = {
				attrs: { className: "bx-crm-dialog-long-run-proc-summary" }
			};
			if (this._isSummaryHtml)
			{
				summaryData["html"] = summary;
			}
			else
			{
				summaryData["text"] = summary;
			}
			this._summary = BX.create(
				"DIV",
				summaryData
			);

			if(this._progressUI)
			{
				this._progressbar = BX.create(
					"DIV",
					{
						attrs: {className: "bx-crm-dialog-long-run-proc-progressbar"},
						style: {display: "none"},
						children: [this._progressUI.getContainer()]
					}
				);
			}

			var option, optionName, optionBlock, optionId, numberOfOptions = 0;
			for (optionName in this._initialOptions)
			{
				if (this._initialOptions.hasOwnProperty(optionName))
				{
					option = this._initialOptions[optionName];
					if (BX.type.isPlainObject(option)
						&& option.hasOwnProperty("name")
						&& option.hasOwnProperty("type")
						&& option.hasOwnProperty("title")
						&& option.hasOwnProperty("value"))
					{
						optionBlock = null;
						switch (option["type"])
						{
							case "checkbox":
								optionId = this._id + "_opt_" + optionName;
								var checkboxAttrs = {
									id: optionId,
									type: option["type"],
									name: optionName
								};
								if (option["value"] === 'Y')
									checkboxAttrs["checked"] = "checked";
								optionBlock = BX.create(
									"DIV",
									{
										children: [
											BX.create(
												"SPAN",
												{
													children: [
														BX.create("INPUT", {attrs: checkboxAttrs}),
														BX.create(
															"LABEL",
															{
																attrs: { for: optionId },
																text: option["title"]
															}
														)
													]
												}
											)
										]
									}
								);
								checkboxAttrs = null;
								break;
						}
						if (optionBlock !== null)
						{
							if (this._initialOptionsBlock === null)
							{
								this._initialOptionsBlock = BX.create(
									"DIV", { attrs: { className: "bx-crm-dialog-long-run-proc-options" } }
								);
							}
							this._initialOptionsBlock.appendChild(optionBlock);
							numberOfOptions++;
						}
					}
				}
			}

			var summaryElements = [this._summary];

			if(this._progressbar)
				summaryElements.push(this._progressbar);

			if (this._initialOptionsBlock)
				summaryElements.push(this._initialOptionsBlock);

			return BX.create(
				"DIV",
				{
					attrs: { className: "bx-crm-dialog-long-run-proc-popup" },
					children: summaryElements
				}
			);
		},
		_prepareDialogButtons: function()
		{
			this._buttons = {};

			var startButtonText = this.getMessage("startButton");
			this._buttons["start"] = new BX.PopupWindowButton(
				{
					text: startButtonText !== "" ? startButtonText : "Start",
					className: "popup-window-button-accept",
					events:
					{
						click : BX.delegate(this._handleStartButtonClick, this)
					}
				}
			);

			var stopButtonText = this.getMessage("stopButton");
			this._buttons["stop"] = new BX.PopupWindowButton(
				{
					text: stopButtonText !== "" ? stopButtonText : "Stop",
					className: "popup-window-button-disable",
					events:
					{
						click : BX.delegate(this._handleStopButtonClick, this)
					}
				}
			);

			var closeButtonText = this.getMessage("closeButton");
			this._buttons["close"] = new BX.PopupWindowButtonLink(
				{
					text: closeButtonText !== "" ? closeButtonText : "Close",
					className: "popup-window-button-link-cancel",
					events:
					{
						click : BX.delegate(this._handleCloseButtonClick, this)
					}
				}
			);

			return [ this._buttons["start"], this._buttons["stop"], this._buttons["close"] ];
		},
		_onDialogClose: function(e)
		{
			if(this._dlg)
			{
				this._dlg.destroy();
				this._dlg = null;
			}

			this._setState(BX.CrmLongRunningProcessState.intermediate);
			this._buttons = {};
			this._summary = null;

			this._isShown = false;

			BX.onCustomEvent(this, 'ON_CLOSE', [this]);
		},
		_handleStartButtonClick: function()
		{
			var btn = typeof(this._buttons["start"]) !== "undefined" ? this._buttons["start"] : null;
			if(btn)
			{
				var wasDisabled = BX.data(btn.buttonNode, 'disabled');
				if (wasDisabled === true)
				{
					return;
				}
			}

			this.start();
		},
		_handleStopButtonClick: function()
		{
			var btn = typeof(this._buttons["stop"]) !== "undefined" ? this._buttons["stop"] : null;
			if(btn)
			{
				var wasDisabled = BX.data(btn.buttonNode, 'disabled');
				if (wasDisabled === true)
				{
					return;
				}
			}

			this.stop();
		},
		_handleCloseButtonClick: function()
		{
			if(this._state !== BX.CrmLongRunningProcessState.running)
			{
				this._dlg.close();
			}
		},
		_lockButton: function(bid, lock)
		{
			var btn = typeof(this._buttons[bid]) !== "undefined" ? this._buttons[bid] : null;
			if(!btn)
			{
				return;
			}

			if(!!lock)
			{
				BX.removeClass(btn.buttonNode, "popup-window-button-accept");
				BX.addClass(btn.buttonNode, "popup-window-button-disable");
				btn.buttonNode.disabled = true;
				BX.data(btn.buttonNode, 'disabled', true);
			}
			else
			{
				BX.removeClass(btn.buttonNode, "popup-window-button-disable");
				BX.addClass(btn.buttonNode, "popup-window-button-accept");
				btn.buttonNode.disabled = false;
				BX.data(btn.buttonNode, 'disabled', false);
			}
		},
		_showButton: function(bid, show)
		{
			var btn = typeof(this._buttons[bid]) !== "undefined" ? this._buttons[bid] : null;
			if(btn)
			{
				btn.buttonNode.style.display = !!show ? "" : "none";
			}
		},
		/**
		 * @param {string} content
		 * @param {bool} isHtml
		 * @private
		 */
		_setSummary: function(content, isHtml)
		{
			if (this._initialOptionsBlock)
			{
				BX.remove(this._initialOptionsBlock);
				this._initialOptionsBlock = null;
			}
			isHtml = !!isHtml;
			if(this._summary)
			{
				if (isHtml)
					this._summary.innerHTML = content;
				else
					this._summary.innerHTML = BX.util.htmlspecialchars(content);
			}
		},
		_setProgressBar: function(totalItems, processedItems)
		{
			if(this._progressUI)
			{
				if (BX.type.isNumber(processedItems) && BX.type.isNumber(totalItems) && totalItems > 0)
				{
					BX.show(this._progressbar);

					this._progressUI.setMaxValue(totalItems);
					this._progressUI.update(processedItems);
				}
				else
				{
					BX.hide(this._progressbar);
				}
			}
		},
		_setState: function(state)
		{
			if(this._state === state)
			{
				return;
			}

			this._state = state;
			if(state === BX.CrmLongRunningProcessState.intermediate || state === BX.CrmLongRunningProcessState.stoped)
			{
				this._lockButton("start", false);
				this._lockButton("stop", true);
				this._showButton("close", true);
			}
			else if(state === BX.CrmLongRunningProcessState.running)
			{
				this._lockButton("start", true);
				this._lockButton("stop", false);
				this._showButton("close", false);
			}
			else if(state === BX.CrmLongRunningProcessState.completed || state === BX.CrmLongRunningProcessState.error)
			{
				this._lockButton("start", true);
				this._lockButton("stop", true);
				this._showButton("close", true);
			}

			if(this._progressUI)
			{
				if(state === BX.CrmLongRunningProcessState.completed)
				{
					//this._progressUI.setColor(BX.UI.ProgressBar.Color.SUCCESS);
					BX.hide(this._progressbar);
				}
				if(state === BX.CrmLongRunningProcessState.error)
				{
					this._progressUI.setColor(BX.UI.ProgressBar.Color.DANGER);
				}
			}

			BX.onCustomEvent(this, 'ON_STATE_CHANGE', [this]);
		},
		_startRequest: function()
		{
			if(this._requestIsRunning)
			{
				return;
			}
			this._requestIsRunning = true;

			this._setState(BX.CrmLongRunningProcessState.running);

			var isAjaxControllerMode = BX.type.isNotEmptyString(this._controller);

			var actionData;
			if (isAjaxControllerMode)
			{
				actionData = BX.clone(this._params);
			}
			else
			{
				actionData = {
					"ACTION" : this._action,
					"PARAMS": this._params
				};
			}

			if (this._initialOptionsBlock)
			{
				this._option = {};
				var initialOptions = {};
				var numberOfOptions = 0;
				var option, optionName, optionId, optionElement, optionValue, optionValueIsSet;
				for (optionName in this._initialOptions)
				{
					if (this._initialOptions.hasOwnProperty(optionName))
					{
						option = this._initialOptions[optionName];
						if (BX.type.isPlainObject(option)
							&& option.hasOwnProperty("name")
							&& option.hasOwnProperty("type")
							&& option.hasOwnProperty("title")
							&& option.hasOwnProperty("value"))
						{
							optionValueIsSet = false;
							switch (option["type"])
							{
								case "checkbox":
									optionId = this._id + "_opt_" + optionName;
									optionElement = BX(optionId);
									if (optionElement)
									{
										optionValue = (optionElement.checked) ? "Y" : "N";
										optionValueIsSet = true;
									}
									break;
							}
							if (optionValueIsSet)
							{
								initialOptions[optionName] = optionValue;
								numberOfOptions++;
							}
						}
					}
				}
				if (numberOfOptions > 0)
				{
					this._option = initialOptions;
					actionData["INITIAL_OPTIONS"] = initialOptions;
				}
			}
			else if (BX.type.isNotEmptyObject(this._option))
			{
				actionData["INITIAL_OPTIONS"] = this._option;
			}

			if(isAjaxControllerMode)
			{
				BX.ajax.runAction
				(
					this._controller + '.' + this._action,
					{
						data: actionData,
						method: this._method
					}
				)
				.then(
					BX.delegate(this._onRequestSuccess, this),
					BX.delegate(this._onRequestFailure, this)
				);
			}
			else
			{
				BX.ajax(
					{
						url: this._serviceUrl,
						method: this._method,
						dataType: "json",
						data: actionData,
						onsuccess: BX.delegate(this._onRequestSuccess, this),
						onfailure: BX.delegate(this._onRequestFailure, this)
					}
				);
			}
		},
		_stopRequest: function()
		{
			if(this._cancelRequest)
			{
				return;
			}
			this._cancelRequest = true;
			this._requestIsRunning = false;

			this._setState(BX.CrmLongRunningProcessState.stoped);

			var isAjaxControllerMode = BX.type.isNotEmptyString(this._controller);

			var actionData;
			if (isAjaxControllerMode)
			{
				actionData = BX.clone(this._params);

				BX.ajax.runAction
				(
					this._controller + '.cancel',
					{
						data: actionData,
						method: this._method
					}
				)
				.then(
					BX.delegate(this._onRequestSuccess, this),
					BX.delegate(this._onRequestFailure, this)
				);
			}
		},
		/**
		 * @param {Object} result
		 * @private
		 */
		_onRequestSuccess: function(result)
		{
			this._requestIsRunning = false;

			if(!result)
			{
				this._setSummary(this.getMessage("requestError"));
				this._setState(BX.CrmLongRunningProcessState.error);
				return;
			}

			var isAjaxControllerMode = BX.type.isNotEmptyString(this._controller);

			if (isAjaxControllerMode)
			{
				if(BX.type.isArray(result["errors"]) && result["errors"].length > 0)
				{
					var lastError = result["errors"][result["errors"].length - 1];
					this._setState(BX.CrmLongRunningProcessState.error);
					this._setSummary(lastError.message);
					return;
				}
			}
			else if(BX.type.isNotEmptyString(result["ERROR"]))
			{
				this._setState(BX.CrmLongRunningProcessState.error);
				this._setSummary(result["ERROR"]);
				return;
			}

			if (isAjaxControllerMode)
			{
				result = result["data"];
			}

			if(typeof(this._requestHandler) == 'function')
			{
				this._requestHandler.call(this, result);
			}

			this._networkErrorCount = 0;

			var status = BX.type.isNotEmptyString(result["STATUS"]) ? result["STATUS"] : "";
			var summary = BX.type.isNotEmptyString(result["SUMMARY"]) ? result["SUMMARY"] : "";
			var isHtmlSummary = false;
			if (!BX.type.isNotEmptyString(summary))
			{
				summary = BX.type.isNotEmptyString(result["SUMMARY_HTML"]) ? result["SUMMARY_HTML"] : "";
				isHtmlSummary = true;
			}
			if(status === "PROGRESS")
			{
				var processedItems = BX.type.isNumber(result["PROCESSED_ITEMS"]) ? result["PROCESSED_ITEMS"] : 0;
				var totalItems = BX.type.isNumber(result["TOTAL_ITEMS"]) ? result["TOTAL_ITEMS"] : 0;
				if (totalItems > 0)
				{
					this._setProgressBar(totalItems, processedItems);
				}

				if(summary !== "")
				{
					this._setSummary(summary, isHtmlSummary);
				}

				if(this._cancelRequest)
				{
					this._setState(BX.CrmLongRunningProcessState.stoped);
					this._cancelRequest = false;
				}
				else
				{
					var nextAction = BX.type.isNotEmptyString(result["NEXT_ACTION"]) ? result["NEXT_ACTION"] : "";
					if (nextAction !== "")
					{
						this._action = nextAction;
					}

					window.setTimeout(
						BX.delegate(this._startRequest, this),
						200
					);
				}
				return;
			}

			if(status === "NOT_REQUIRED" || status === "COMPLETED")
			{
				this._setState(BX.CrmLongRunningProcessState.completed);
				if(summary !== "")
				{
					this._setSummary(summary, isHtmlSummary);
				}
			}
			else
			{
				this._setSummary(this.getMessage("requestError"));
				this._setState(BX.CrmLongRunningProcessState.error);
			}

			if(this._cancelRequest)
			{
				this._cancelRequest = false;
			}
		},
		/**
		 * @param {Object} result
		 * @private
		 */
		_onRequestFailure: function(result)
		{
			this._requestIsRunning = false;

			var isAjaxControllerMode = BX.type.isNotEmptyString(this._controller);
			if (isAjaxControllerMode)
			{
				if(BX.type.isArray(result["errors"]) && result["errors"].length > 0)
				{
					var lastError = result["errors"][result["errors"].length - 1];

					if (lastError.code === "NETWORK_ERROR")
					{
						this._networkErrorCount ++;
						// Let's give it more chance to complete
						if (this._networkErrorCount <= 2)
						{
							window.setTimeout(
								BX.delegate(this._startRequest, this),
								15000
							);
							return;
						}
					}

					this._setSummary(lastError.message);
				}
				else
				{
					this._setSummary(this.getMessage("requestError"));
				}
			}
			else
			{
				this._setSummary(this.getMessage("requestError"));
			}

			this._setState(BX.CrmLongRunningProcessState.error);
		}
	};
	if(typeof(BX.CrmLongRunningProcessDialog.messages) == "undefined")
	{
		BX.CrmLongRunningProcessDialog.messages = {};
	}
	BX.CrmLongRunningProcessDialog.items = {};
	BX.CrmLongRunningProcessDialog.create = function(id, settings)
	{
		var self = new BX.CrmLongRunningProcessDialog();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
if(typeof(BX.CrmEntityType) === "undefined")
{
	BX.CrmEntityType = function()
	{
	};

	BX.CrmEntityType.dynamicTypeStart = 128;
	BX.CrmEntityType.dynamicTypeEnd = 192;
	BX.CrmEntityType.dynamicTypeNamePrefix = "DYNAMIC_";
	BX.CrmEntityType.dynamicTypeAbbreviationPrefix = "T";
	BX.CrmEntityType.enumeration =
	{
		undefined: 0,
		lead: 1,
		deal: 2,
		contact: 3,
		company: 4,
		invoice: 5,
		activity: 6,
		quote: 7,
		wait: 11,
		dealrecurring: 13,
		order: 14,
		ordershipment: 16,
		orderpayment: 17,
		smartinvoice: 31,
		storeDocument: 33,
		shipmentDocument: 34,
		smartdocument: 36,
		agentcontract: 38,
		document: 12
	};
	BX.CrmEntityType.names =
	{
		undefined: "",
		lead: "LEAD",
		deal: "DEAL",
		contact: "CONTACT",
		company: "COMPANY",
		invoice: "INVOICE",
		activity: "ACTIVITY",
		quote: "QUOTE",
		wait: "WAIT",
		dealrecurring: "DEAL_RECURRING",
		order: "ORDER",
		ordershipment: "ORDER_SHIPMENT",
		orderpayment: "ORDER_PAYMENT",
		ordercheck: "ORDER_CHECK",
		smartinvoice: "SMART_INVOICE",
		dynamic: "DYNAMIC",
		smartdocument: "SMART_DOCUMENT",
		agentcontract: "AGENT_CONTRACT"
	};
	BX.CrmEntityType.abbreviations =
	{
		undefined: "",
		lead: "L",
		deal: "D",
		contact: "C",
		company: "CO",
		invoice: "I",
		quote: "Q",
		order: "O",
		ordershipment: "OS",
		orderpayment: "OP",
		smartinvoice: "SI",
		smartdocument: "DO",
		agentcontract: "AC",
	};
	BX.CrmEntityType.isDefined = function(typeId)
	{
		if(!BX.type.isNumber(typeId))
		{
			typeId = parseInt(typeId);
			if(isNaN(typeId))
			{
				typeId = 0;
			}
		}

		var entityTypeIds = Object.values(this.enumeration);
		var isStaticType = (entityTypeIds.indexOf(typeId) !== -1) && (typeId !== this.enumeration.undefined);
		var isDynamicType = this.isDynamicTypeByTypeId(typeId);

		return isStaticType || isDynamicType;
	};
	BX.CrmEntityType.resolveName = function(typeId)
	{
		if(!BX.type.isNumber(typeId))
		{
			typeId = parseInt(typeId);
			if(isNaN(typeId))
			{
				typeId = 0;
			}
		}

		if(typeId === BX.CrmEntityType.enumeration.lead)
		{
			return BX.CrmEntityType.names.lead;
		}
		else if(typeId === BX.CrmEntityType.enumeration.deal)
		{
			return BX.CrmEntityType.names.deal;
		}
		else if(typeId === BX.CrmEntityType.enumeration.dealrecurring)
		{
			return BX.CrmEntityType.names.dealrecurring;
		}
		else if(typeId === BX.CrmEntityType.enumeration.contact)
		{
			return BX.CrmEntityType.names.contact;
		}
		else if(typeId === BX.CrmEntityType.enumeration.company)
		{
			return BX.CrmEntityType.names.company;
		}
		else if(typeId === BX.CrmEntityType.enumeration.invoice)
		{
			return BX.CrmEntityType.names.invoice;
		}
		else if(typeId === BX.CrmEntityType.enumeration.activity)
		{
			return BX.CrmEntityType.names.activity;
		}
		else if(typeId === BX.CrmEntityType.enumeration.quote)
		{
			return BX.CrmEntityType.names.quote;
		}
		else if(typeId === BX.CrmEntityType.enumeration.wait)
		{
			return BX.CrmEntityType.names.wait;
		}
		else if(typeId === BX.CrmEntityType.enumeration.order)
		{
			return BX.CrmEntityType.names.order;
		}
		else if(typeId === BX.CrmEntityType.enumeration.ordershipment)
		{
			return BX.CrmEntityType.names.ordershipment;
		}
		else if(typeId === BX.CrmEntityType.enumeration.orderpayment)
		{
			return BX.CrmEntityType.names.orderpayment;
		}
		else if(typeId === BX.CrmEntityType.enumeration.smartinvoice)
		{
			return BX.CrmEntityType.names.smartinvoice;
		}
		else if(typeId === BX.CrmEntityType.enumeration.smartdocument)
		{
			return BX.CrmEntityType.names.smartdocument;
		}
		else if(typeId === BX.CrmEntityType.enumeration.agentcontract)
		{
			return BX.CrmEntityType.names.agentcontract;
		}
		else if (BX.CrmEntityType.isDynamicTypeByTypeId(typeId))
		{
			return BX.CrmEntityType.getDynamicTypeName(typeId);
		}
		else
		{
			return "";
		}
	};
	BX.CrmEntityType.resolveId = function(name)
	{
		name = name.toUpperCase();
		if(name === BX.CrmEntityType.names.lead)
		{
			return this.enumeration.lead;
		}
		else if(name === BX.CrmEntityType.names.deal)
		{
			return this.enumeration.deal;
		}
		else if(name === BX.CrmEntityType.names.dealrecurring)
		{
			return this.enumeration.dealrecurring;
		}
		else if(name === BX.CrmEntityType.names.contact)
		{
			return this.enumeration.contact;
		}
		else if(name === BX.CrmEntityType.names.company)
		{
			return this.enumeration.company;
		}
		else if(name === BX.CrmEntityType.names.invoice)
		{
			return this.enumeration.invoice;
		}
		else if(name === BX.CrmEntityType.names.activity)
		{
			return this.enumeration.activity;
		}
		else if(name === BX.CrmEntityType.names.quote)
		{
			return this.enumeration.quote;
		}
		else if(name === BX.CrmEntityType.names.order)
		{
			return this.enumeration.order;
		}
		else if(name === BX.CrmEntityType.names.ordershipment)
		{
			return this.enumeration.ordershipment;
		}
		else if(name === BX.CrmEntityType.names.orderpayment)
		{
			return this.enumeration.orderpayment;
		}
		else if(name === BX.CrmEntityType.names.wait)
		{
			return this.enumeration.wait;
		}
		else if(name === BX.CrmEntityType.names.smartinvoice)
		{
			return this.enumeration.smartinvoice;
		}
		else if(name === BX.CrmEntityType.names.smartdocument)
		{
			return this.enumeration.smartdocument;
		}
		else if(name === BX.CrmEntityType.names.agentcontract)
		{
			return this.enumeration.agentcontract;
		}
		else if (BX.CrmEntityType.isDynamicTypeByName(name))
		{
			return this.getTypeIdFromDynamicTypeName(name);
		}
		else
		{
			return this.enumeration.undefined;
		}
	};
	BX.CrmEntityType.resolveAbbreviation = function(name)
	{
		name = name.toUpperCase();
		if(name === BX.CrmEntityType.names.lead)
		{
			return this.abbreviations.lead;
		}
		else if(name === BX.CrmEntityType.names.deal)
		{
			return this.abbreviations.deal;
		}
		else if(name === BX.CrmEntityType.names.contact)
		{
			return this.abbreviations.contact;
		}
		else if(name === BX.CrmEntityType.names.company)
		{
			return this.abbreviations.company;
		}
		else if(name === BX.CrmEntityType.names.invoice)
		{
			return this.abbreviations.invoice;
		}
		else if(name === BX.CrmEntityType.names.order)
		{
			return this.abbreviations.order;
		}
		else if(name === BX.CrmEntityType.names.ordershipment)
		{
			return this.abbreviations.ordershipment;
		}
		else if(name === BX.CrmEntityType.names.orderpayment)
		{
			return this.abbreviations.orderpayment;
		}
		else if (name === BX.CrmEntityType.names.smartinvoice)
		{
			return this.abbreviations.smartinvoice;
		}
		else if (BX.CrmEntityType.isDynamicTypeByName(name))
		{
			var typeId = this.getTypeIdFromDynamicTypeName(name);
			return this.getDynamicTypeAbbreviation(typeId);
		}
		else
		{
			return this.abbreviations.undefined;
		}
	};

	/**
	 * @param {number} typeId
	 * @return {boolean}
	 */
	BX.CrmEntityType.isDynamicTypeByTypeId = function(typeId)
	{
		typeId = Number(typeId);

		return (
			typeId >= this.dynamicTypeStart && typeId < this.dynamicTypeEnd
		)
	};

	BX.CrmEntityType.isUseFactoryBasedApproach = function(typeId)
	{
		typeId = Number(typeId);

		return (
			typeId === this.enumeration.quote
			|| typeId === this.enumeration.smartinvoice
			|| typeId === this.enumeration.smartdocument
			|| this.isDynamicTypeByTypeId(typeId)
		);
	};

	/**
	 * @param {string} name
	 * @return {boolean}
	 */
	BX.CrmEntityType.isDynamicTypeByName = function(name)
	{
		name = String(name);
		var prefixMatches = (name.indexOf(this.dynamicTypeNamePrefix) === 0);
		var typeIdIsValid = this.isDynamicTypeByTypeId(this.getTypeIdFromDynamicTypeName(name));

		return (prefixMatches && typeIdIsValid);
	};

	BX.CrmEntityType.isUseDynamicTypeBasedApproach = function(typeId)
	{
		typeId = Number(typeId);

		return (
			typeId === BX.CrmEntityType.enumeration.smartinvoice
			|| typeId === BX.CrmEntityType.enumeration.smartdocument
			|| BX.CrmEntityType.isDynamicTypeByTypeId(typeId)
		);
	}

	BX.CrmEntityType.isUseDynamicTypeBasedApproachByName = function(name)
	{
		name = String(name);
		if (name === BX.CrmEntityType.names.smartinvoice)
		{
			return true;
		}
		if (name === BX.CrmEntityType.names.smartdocument)
		{
			return true;
		}

		return BX.CrmEntityType.isDynamicTypeByName(name);
	}

	BX.CrmEntityType.isUseFactoryBasedApproachByName = function(name)
	{
		name = String(name);
		if (name === BX.CrmEntityType.names.quote)
		{
			return true;
		}

		return BX.CrmEntityType.isUseDynamicTypeBasedApproachByName(name);
	}

	/**
	 * @param {number} typeId
	 * @return {string}
	 */
	BX.CrmEntityType.getDynamicTypeName = function(typeId)
	{
		return this.dynamicTypeNamePrefix + typeId;
	};

	/**
	 * @param {number} typeId
	 * @return {string}
	 */
	BX.CrmEntityType.getDynamicTypeAbbreviation = function(typeId)
	{
		return this.dynamicTypeAbbreviationPrefix + this.normalizeTypeIdForAbbreviation(typeId);
	}

	/**
	 * @private
	 * @param {number} typeId
	 * @return {string}
	 */
	BX.CrmEntityType.normalizeTypeIdForAbbreviation = function(typeId)
	{
		typeId = Number(typeId);
		// In dynamic type abbreviation typeId is being converted to Hex
		// in order to fit the resulting abbreviation into 3 symbols. It's a limitation of several CRM tables
		return typeId.toString(16);
	}

	/**
	 * @private
	 * @param {string} name
	 * @return {number}
	 */
	BX.CrmEntityType.getTypeIdFromDynamicTypeName = function(name)
	{
		name = String(name);
		var typeId = name.replace(this.dynamicTypeNamePrefix, '');
		return parseInt(typeId);
	};

	BX.CrmEntityType.verifyName = function(name)
	{
		if(!BX.type.isNotEmptyString(name))
		{
			return "";
		}

		name = name.toUpperCase();
		return (this.resolveId(name) !== this.enumeration.undefined ? name : "");
	};

	BX.CrmEntityType.setCaptions = function(captions)
	{
		if(BX.type.isPlainObject(captions))
		{
			this.captions = captions;
		}
	};

	BX.CrmEntityType.getCaption = function(typeId)
	{
		var name = this.resolveName(typeId);
		return (this.captions.hasOwnProperty(name) ? this.captions[name] : name);
	};

	BX.CrmEntityType.getCaptionByName = function(name)
	{
		if(!BX.type.isNotEmptyString(name))
		{
			return "";
		}

		name = name.toUpperCase();
		return (this.captions.hasOwnProperty(name) ? this.captions[name] : name);
	};

	BX.CrmEntityType.setNotFoundMessages = function(messages)
	{
		if(BX.type.isPlainObject(messages))
		{
			this.notFoundMessages = messages;
		}
	};

	BX.CrmEntityType.getNotFoundMessage = function(typeId)
	{
		var name = this.resolveName(typeId);

		var message = null;
		if (this.notFoundMessages.hasOwnProperty(name))
		{
			message = this.notFoundMessages[name];
		}
		if (!message && this.notFoundMessages.hasOwnProperty(BX.CrmEntityType.names.dynamic))
		{
			message = this.notFoundMessages[BX.CrmEntityType.names.dynamic];
		}
		if (!message)
		{
			message = name;
		}

		return message;
	};

	BX.CrmEntityType.getNotFoundMessageByName = function(name)
	{
		if(!BX.type.isNotEmptyString(name))
		{
			return "";
		}

		name = name.toUpperCase();
		return (this.notFoundMessages.hasOwnProperty(name) ? this.notFoundMessages[name] : name);
	};

	BX.CrmEntityType.prepareEntityKey = function(entityTypeName, entityId)
	{
		var abbr = this.resolveAbbreviation(entityTypeName);
		return abbr !== "" ? (abbr + "_" + entityId.toString()) : "";
	};

	if(typeof(BX.CrmEntityType.captions) === "undefined")
	{
		BX.CrmEntityType.captions = {};
	}
	if(typeof(BX.CrmEntityType.categoryCaptions) === "undefined")
	{
		BX.CrmEntityType.categoryCaptions = {};
	}
}
if(typeof(BX.CrmDuplicateManager) === "undefined")
{
	BX.CrmDuplicateManager = function()
	{
		this._id = "";
		this._settings = {};
		this._entityTypeName = "";
		this._processDialogs = {};
	};
	BX.CrmDuplicateManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_dp_mgr_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._entityTypeName = this.getSetting("entityTypeName", "");
			if(!BX.type.isNotEmptyString(this._entityTypeName))
			{
				throw "BX.CrmDuplicateManager. Could not find entity type name.";
			}

			this._entityTypeName = this._entityTypeName.toUpperCase();
		},
		getId: function()
		{
			return this._id;
		},
		getEntityTypeName: function()
		{
			return this._entityTypeName;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getMessage: function(name)
		{
			return BX.CrmDuplicateManager.messages && BX.CrmDuplicateManager.messages.hasOwnProperty(name) ? BX.CrmDuplicateManager.messages[name] : "";
		},
		rebuildIndex: function()
		{
			var serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(serviceUrl))
			{
				throw "BX.CrmDuplicateManager. Could not find service url.";
			}

			var entityTypeNameC = this._entityTypeName.toLowerCase().replace(/(?:^)\S/, function(c){ return c.toUpperCase(); });
			var key = "rebuild" + entityTypeNameC + "Index";

			var processDlg = null;
			if(typeof(this._processDialogs[key]) !== "undefined")
			{
				processDlg = this._processDialogs[key];
			}
			else
			{
				processDlg = BX.CrmLongRunningProcessDialog.create(
					key,
					{
						serviceUrl: serviceUrl,
						action:"REBUILD_DUPLICATE_INDEX",
						params:{ "ENTITY_TYPE_NAME": this._entityTypeName },
						title: this.getMessage(key + "DlgTitle"),
						summary: this.getMessage(key + "DlgSummary")
					}
				);

				this._processDialogs[key] = processDlg;
				BX.addCustomEvent(processDlg, 'ON_STATE_CHANGE', BX.delegate(this._onProcessStateChange, this));
			}
			processDlg.show();
		},
		_onProcessStateChange: function(sender)
		{
			var key = sender.getId();
			if(typeof(this._processDialogs[key]) !== "undefined")
			{
				var processDlg = this._processDialogs[key];
				if(processDlg.getState() === BX.CrmLongRunningProcessState.completed)
				{
					//ON_LEAD_INDEX_REBUILD_COMPLETE, ON_COMPANY_INDEX_REBUILD_COMPLETE, ON_CONTACT_INDEX_REBUILD_COMPLETE
					BX.onCustomEvent(this, "ON_" + this._entityTypeName + "_INDEX_REBUILD_COMPLETE", [this]);
				}
			}
		}
	};
	if(typeof(BX.CrmDuplicateManager.messages) == "undefined")
	{
		BX.CrmDuplicateManager.messages = {};
	}
	BX.CrmDuplicateManager.items = {};
	BX.CrmDuplicateManager.create = function(id, settings)
	{
		var self = new BX.CrmDuplicateManager();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
if(typeof(BX.CrmDupController) === "undefined")
{
	BX.CrmDupController = function()
	{
		this._id = "";
		this._settings = {};
		this._entityTypeName = "";
		this._entityId = 0;
		this._enable = true;
		this._groups = {};
		this._requestIsRunning = false;
		this._request = null;
		this._searchData = {};
		this._searchSummary = null;
		this._warningDialog = null;
		this._submits = [];
		this._lastSummaryGroupId = "";
		this._lastSummaryFieldId = "";
		this._lastSubmit = null;
		this._submitClickHandler = BX.delegate(this._onSubmitClick, this);
		this._beforeFormSubmitHandler = BX.delegate(this._onBeforeFormSubmit, this);
		this._startDestroy = false;
	};
	BX.CrmDupController.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_dp_ctrl_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.CrmDupController. Could not find service url.";
			}

			this._bind();

			this._entityTypeName = this.getSetting("entityTypeName", "");
			this._entityId = this.getSetting("entityId", 0);
			this._ignoredItems = BX.prop.getArray(this._settings, 'ignoredItems', []);
			var groups = this.getSetting("groups", null);
			var group = null;
			if(groups)
			{
				for(var key in groups)
				{
					if(!groups.hasOwnProperty(key))
					{
						continue;
					}

					group = groups[key];
					var type = BX.type.isNotEmptyString(group["groupType"]) ? group["groupType"] : "";
					var ctrl = null;
					try
					{
						if(type === "single")
						{
							ctrl = BX.CrmDupCtrlSingleField.create(key, group);
						}
						else if(type === "fullName")
						{
							ctrl = BX.CrmDupCtrlFullName.create(key, group);
						}
						else if(type === "communication")
						{
							ctrl = BX.CrmDupCtrlCommunication.create(key, group);
						}
					}
					catch(ex)
					{
					}

					if(ctrl)
					{
						this.addGroup(ctrl);
					}
				}
			}

			this._afterInitialize();

			this.initialSearch();
		},
		initialSearch: function(customGroups)
		{
			var groupParams = [];
			var groups = (BX.type.isPlainObject(customGroups)) ? customGroups : this._groups;
			for(var groupId in groups)
			{
				if(!groups.hasOwnProperty(groupId))
				{
					continue;
				}

				var group = groups[groupId];
				var params = group.prepareSearchParams();
				if(!params)
				{
					continue;
				}

				params["GROUP_ID"] = groupId;
				params["HASH_CODE"] = group.getSearchHashCode();
				params["FIELD_ID"] = group.getDefaultSearchSummaryFieldId();

				groupParams.push(params);
			}

			if(groupParams.length > 0)
			{
				this._search({ "GROUPS": groupParams });
			}
		},
		destroy: function()
		{
			this._startDestroy = true;
			this._unbind();
			for(var key in this._groups)
			{
				if(this._groups.hasOwnProperty(key))
					this.deleteGroup(key);
			}
		},
		_afterInitialize: function()
		{
			BX.onCustomEvent("CrmDupControllerAfterInitialize", [this]);
		},
		getId: function()
		{
			return this._id;
		},
		getEntityTypeName: function()
		{
			return this._entityTypeName;
		},
		isEnabled: function()
		{
			return this._enable;
		},
		enable: function(enable)
		{
			this._enable = !!enable;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		registerGroup: function(groupId, settings)
		{
			var type = BX.type.isNotEmptyString(settings["groupType"]) ? settings["groupType"] : "";
			var ctrl = null;
			try
			{
				if(type === "single")
				{
					ctrl = BX.CrmDupCtrlSingleField.create(groupId, settings);
				}
				else if(type === "fullName")
				{
					ctrl = BX.CrmDupCtrlFullName.create(groupId, settings);
				}
				else if(type === "communication")
				{
					ctrl = BX.CrmDupCtrlCommunication.create(groupId, settings);
				}
			}
			catch(ex)
			{
			}

			if(ctrl)
			{
				this.addGroup(ctrl);
			}

			return ctrl;
		},
		unregisterGroup: function(groupId)
		{
			if(!this._groups.hasOwnProperty(groupId))
			{
				return;
			}

			//release this._groups
			delete[this._groups[groupId]];
		},
		addGroup: function(group)
		{
			this._groups[group.getId()] = group;
			group.setController(this);
			return group;
		},
		getGroup: function(groupId)
		{
			return this._groups.hasOwnProperty(groupId) ? this._groups[groupId] : null;
		},
		deleteGroup: function(groupId)
		{
			var result = false;

			if (BX.type.isNotEmptyString(groupId) && this._groups.hasOwnProperty(groupId))
			{
				this._groups[groupId].clearFields();
				if(typeof(this._searchData[groupId]) !== "undefined")
				{
					delete this._searchData[groupId];
					this._refreshSearchSummary(groupId, "");
				}
				delete this._groups[groupId];
				result = true;
			}

			return result;
		},
		getDuplicateData: function()
		{
			return this._searchData;
		},
		hasDuplicates: function()
		{
			for(var key in this._searchData)
			{
				if(!this._searchData.hasOwnProperty(key))
				{
					continue;
				}

				var data = this._searchData[key];
				if(data.hasOwnProperty("items") && data["items"].length > 0)
				{
					return true;
				}
			}
			return false;
		},
		processGroupChange: function(group, field)
		{
			var groupId =  group.getId();

			var params = group.prepareSearchParams();
			if(!params)
			{
				if(typeof(this._searchData[groupId]) !== "undefined" && field)
				{
					delete this._searchData[groupId];
					this._refreshSearchSummary(groupId, field.getId());
				}
				return;
			}

			var hashCode = group.getSearchHashCode();
			if(hashCode !== this._getGroupSearchHashCode(groupId))
			{
				params["GROUP_ID"] = groupId;
				if(field)
				{
					params["FIELD_ID"] = field.getId();
				}

				params["HASH_CODE"] = hashCode;
				this._search({ "GROUPS": [ params ] });
			}
		},
		processGroupsChange: function(changedFieldsByGroup)
		{
			if (BX.type.isPlainObject(changedFieldsByGroup))
			{
				var searchParams = [];
				for (var groupId in changedFieldsByGroup)
				{
					if (changedFieldsByGroup.hasOwnProperty(groupId))
					{
						var group = changedFieldsByGroup[groupId]["group"];
						var groupFields = changedFieldsByGroup[groupId]["fields"];

						var params = group.prepareSearchParams();
						if(params)
						{
							for (var i = 0; i < groupFields.length; i++)
							{
								var hashCode = group.getSearchHashCode();
								if(hashCode !== this._getGroupSearchHashCode(groupId))
								{
									params["GROUP_ID"] = groupId;
									params["FIELD_ID"] = groupFields[i].getId();
									params["HASH_CODE"] = hashCode;
									searchParams.push(params);
								}
							}
						}
						else
						{
							for (var i = 0; i < groupFields.length; i++)
							{
								if (typeof(this._searchData[groupId]) !== "undefined")
								{
									delete this._searchData[groupId];
									this._refreshSearchSummary(groupId, groupFields[i].getId());
								}
							}
						}
					}
				}
				if (searchParams.length > 0)
				{
					this._search({ "GROUPS": searchParams });
				}
			}
		},
		_bind: function()
		{
			var submits = this.getSetting("submits", []);
			if(BX.type.isArray(submits))
			{
				for(var i = 0; i < submits.length; i++)
				{
					var submit = BX(submits[i]);
					if(BX.type.isElementNode(submit))
					{
						this._submits.push(submit);
						BX.bind(submit, "click", this._submitClickHandler);
					}
				}
			}
		},
		_unbind: function()
		{
			for(var i = 0; i < this._submits.length; i++)
			{
				BX.unbind(this._submits[i], "click", this._submitClickHandler);
			}
		},
		_search: function(params)
		{
			if(this._requestIsRunning)
			{
				this._stopSearchRequest();
			}
			params["ENTITY_TYPE_NAME"] = this._entityTypeName;
			params["ENTITY_ID"] = this._entityId;
			if (this._ignoredItems && this._ignoredItems.length)
			{
				params["IGNORED_ITEMS"] = this._ignoredItems;
			}

			this._startSearchRequest(params);
		},
		_startSearchRequest: function(params)
		{
			if(this._requestIsRunning)
			{
				return;
			}

			BX.showWait();
			this._requestIsRunning = true;
			this._request = BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
						{
							"ACTION" : "FIND_DUPLICATES",
							"PARAMS": params,
							"sessid": BX.bitrix_sessid()
						},
					onsuccess: BX.delegate(this._onSearchRequestSuccsess, this),
					onfailure: BX.delegate(this._onSearchRequestFailure, this)
				}
			);
		},
		_stopSearchRequest: function()
		{
			if(!this._requestIsRunning)
			{
				return;
			}
			this._requestIsRunning = false;
			if(this._request)
			{
				this._request.abort();
				this._request = null;
			}

			BX.closeWait();
		},
		_onSearchRequestSuccsess: function(result)
		{
			BX.closeWait();
			if (this._startDestroy)
				return;
			this._requestIsRunning = false;

			if(!result)
			{
				//var error = getMessage("generalError");
				//Show error
				return;
			}

			if(BX.type.isNotEmptyString(result["ERROR"]))
			{
				//var error = result["ERROR"];
				//Show error
				return;
			}

			var lastGroupId = "";
			var lastFieldId = "";
			var groupResults = BX.type.isArray(result["GROUP_RESULTS"]) ? result["GROUP_RESULTS"] : [];
			for(var i = 0; i < groupResults.length; i++)
			{
				var groupResult = groupResults[i];
				var groupId = typeof(groupResult["GROUP_ID"]) !== "undefined" ? groupResult["GROUP_ID"] : "";
				if(!BX.type.isNotEmptyString(groupId))
				{
					return;
				}

				var group = this.getGroup(groupId);
				if(!group)
				{
					return;
				}

				if(typeof(this._searchData[groupId]) === "undefined")
				{
					this._searchData[groupId] = {};
				}

				var items = BX.type.isArray(groupResult["DUPLICATES"]) ? groupResult["DUPLICATES"] : [];
				if(items.length > 0)
				{
					this._searchData[groupId]["items"] = BX.type.isArray(groupResult["DUPLICATES"]) ? groupResult["DUPLICATES"] : [];

					this._searchData[groupId]["totalText"] =
						BX.type.isNotEmptyString(groupResult["ENTITY_TOTAL_TEXT"]) ? groupResult["ENTITY_TOTAL_TEXT"] : "";

					var hash = 0;
					if(typeof(groupResult["HASH_CODE"]) !== "undefined")
					{
						hash = parseInt(groupResult["HASH_CODE"]);
						if(isNaN(hash))
						{
							hash = 0;
						}
					}
					this._searchData[groupId]["hash"] = hash;

					if(BX.type.isNotEmptyString(groupResult["FIELD_ID"]))
					{
						lastGroupId = groupId;
						lastFieldId = groupResult["FIELD_ID"];
					}
				}
				else
				{
					delete this._searchData[groupId];
				}
			}
			this._refreshSearchSummary(lastGroupId, lastFieldId);
		},
		_refreshSearchSummary: function(groupId, fieldId)
		{
			if(!BX.type.isNotEmptyString(groupId))
			{
				groupId = "";
			}

			if(!BX.type.isNotEmptyString(fieldId))
			{
				fieldId = "";
			}

			if(this.hasDuplicates())
			{
				var anchorField = null;
				if(groupId === "" || fieldId === "")
				{
					groupId = this._lastSummaryGroupId;
					fieldId = this._lastSummaryFieldId;
				}
				if(groupId !== "" && fieldId !== "")
				{
					var group = this.getGroup(groupId);
					if(group)
					{
						anchorField = group.getField(fieldId);
					}

					this._lastSummaryGroupId = groupId;
					this._lastSummaryFieldId = fieldId;
				}

				if (this._isSearchSummaryShown())
				{
					this._replaceShownSearchSummary(anchorField);
				}
				else
				{
					this._showSearchSummary(anchorField);
				}
			}
			else
			{
				this._closeSearchSummary();
			}
		},
		_onSearchRequestFailure: function(result)
		{
			BX.closeWait();
			this._requestIsRunning = false;
			//var error = getMessage("generalError");
			//Show error
		},
		_onBeforeFormSubmit: function(sender, eventArgs)
		{
			if(BX.prop.get(BX.prop.getObject(eventArgs, "options", {}), "originator", null) === this)
			{
				return;
			}

			if(this.hasDuplicates())
			{
				eventArgs["cancel"] = true;
				window.setTimeout(BX.delegate(this._openWarningDialog, this), 100);
			}
		},
		_onSubmitClick: function(e)
		{
			if(!this.hasDuplicates())
			{
				return true;
			}

			var submit = null;
			if(e)
			{
				if(e.target)
				{
					submit = e.target;
				}
				else if(e.srcElement)
				{
					submit = e.srcElement;
				}
			}

			if(BX.type.isElementNode(submit))
			{
				this._lastSubmit = submit;
			}

			window.setTimeout(BX.delegate(this._openWarningDialog, this), 100);
			return BX.PreventDefault(e);
		},
		_openWarningDialog: function()
		{
			if(!this.hasDuplicates())
			{
				this._unbind();
				this._submitForm();
			}
			else
			{
				this._warningDialog = BX.CrmDuplicateWarningDialog.create(
					this._id + "_warn",
					{
						"controller": this,
						"onClose": BX.delegate(this._onWarningDialogClose, this),
						"onCancel": BX.delegate(this._onWarningDialogCancel, this),
						"onAccept": BX.delegate(this._onWarningDialogAccept, this)
					}
				);
				this._warningDialog.show();
			}
		},
		_getGroupSearchData: function(groupId)
		{
			return this._searchData.hasOwnProperty(groupId) ? this._searchData[groupId] : null;
		},
		_getGroupSearchHashCode: function(groupId)
		{
			var data = this._getGroupSearchData(groupId);
			return (data && data.hasOwnProperty("hash")) ? data["hash"] : 0;
		},
		_showSearchSummary: function(anchorField)
		{
			let anchor = null;
			if(anchorField)
			{
				anchor = anchorField ? anchorField.getElementTitle() : null;
				if(!anchor)
				{
					anchor = anchorField.getElement();
				}
			}

			const form = this.getSetting("form", null);
			if (form && BX.Type.isFunction(form["getElementNode"]))
			{
				const formElement = form.getElementNode();
				this._searchSummary = BX.Crm.Duplicate.SummaryList.create(
					this._id + "_summary",
					{
						"controller": this,
						"anchor": anchor,
						"wrapper": formElement,
						"clientSearchBox": this.getSetting("clientSearchBox", null),
						"enableEntitySelect": this.getSetting("enableEntitySelect", false)
					}
				);
			}
			else
			{
				this._searchSummary = BX.CrmDuplicateSummaryPopup.create(
					this._id + "_summary",
					{
						"controller": this,
						"anchor": anchor,
						"position": this.getSetting("searchSummaryPosition", "bottom")
					}
				);
			}
			this._searchSummary.show();
		},

		_replaceShownSearchSummary: function(anchorField)
		{
			if (BX.Type.isFunction(this._searchSummary["subscribe"]))
			{
				this._searchSummary.subscribe('close', () => {
					this._showSearchSummary(anchorField);
				});
				this._closeSearchSummary();
			}
			else
			{
				this._showSearchSummary(anchorField);
			}
		},

		_isSearchSummaryShown: function()
		{
			return this._searchSummary && this._searchSummary.isShown();
		},
		_closeSearchSummary: function()
		{
			if(this._searchSummary)
			{
				this._searchSummary.close();
				this._searchSummary = null;
			}
		},
		_onWarningDialogClose: function(dlg)
		{
			if(this._warningDialog === dlg)
			{
				this._warningDialog = null;
			}
		},
		_onWarningDialogCancel: function(dlg)
		{
			if(this._warningDialog === dlg)
			{
				this._warningDialog.close();
			}
		},
		_onWarningDialogAccept: function(dlg)
		{
			if(this._warningDialog === dlg)
			{
				this._warningDialog.close();
				this._unbind();
				this._submitForm();
			}
		},
		_submitForm: function()
		{
			if(BX.type.isElementNode(this._lastSubmit))
			{
				if(this._lastSubmit.disabled)
				{
					this._lastSubmit.disabled = false;
				}
				this._lastSubmit.click();
			}
			else
			{
				var form = this.getSetting("form", null);
				if(form instanceof BX.Crm.Form || form instanceof BX.UI.AjaxForm)
				{
					form.submit({ originator: this });
				}
				else
				{
					form = BX(form);
					if(BX.type.isElementNode(form))
					{
						form.submit();
					}
				}
			}
		}
	};
	BX.CrmDupController.items = {};
	BX.CrmDupController.getItem = function(id)
	{
		return this.items.hasOwnProperty(id) ? this.items[id] : null;
	};
	BX.CrmDupController.create = function(id, settings)
	{
		var self = new BX.CrmDupController();
		self.initialize(id, settings);
		BX.CrmDupController.items[id] = self;
		return self;
	};
	BX.CrmDupController.delete = function(id)
	{
		BX.onCustomEvent("CrmDupControllerDelete", [this]);

		if (BX.CrmDupController.items.hasOwnProperty(id))
		{
			BX.CrmDupController.items[id].destroy();
			delete BX.CrmDupController.items[id];
		}
	};
}
if(typeof(BX.CrmDupCtrlField) === "undefined")
{
	BX.CrmDupCtrlField = function()
	{
		this._id = "";
		this._group = null;
		this._element = null;
		this._elementTitle = null;
		this._value = "";
		this._hasFosus = false;
		this._elementTimeoutId = 0;
		this._elementTimeoutHandler = BX.delegate(this._onElementTimeout, this);
		this._elementKeyUpHandler = BX.delegate(this._onElementKeyUp, this);
		this._elementFocusHandler = BX.delegate(this._onElementFocus, this);
		this._elementBlurHandler = BX.delegate(this._onElementBlur, this);
		this._initialized = false;
	};
	BX.CrmDupCtrlField.prototype =
	{
		initialize: function(id, element, elementTitle)
		{
			if(!BX.type.isNotEmptyString(id))
			{
				throw "BX.CrmDupCtrlField. Invalid parameter 'id': is not defined.";
			}
			this._id = id;

			if(!BX.type.isElementNode(element))
			{
				throw "BX.CrmDupCtrlField. Invalid parameter 'element': is not defined.";
			}
			this._element = element;
			this._value = element.value;

			BX.bind(this._element, "keyup", this._elementKeyUpHandler);
			BX.bind(this._element, "focus", this._elementFocusHandler);
			BX.bind(this._element, "blur", this._elementBlurHandler);

			if(BX.type.isElementNode(elementTitle))
			{
				this._elementTitle = elementTitle;
			}

			this._initialized = true;
		},
		release: function()
		{
			BX.unbind(this._element, "keyup", this._elementKeyUpHandler);
			BX.unbind(this._element, "focus", this._elementFocusHandler);
			BX.unbind(this._element, "blur", this._elementBlurHandler);
			this._element = null;

			this._initialized = false;
		},
		getId: function()
		{
			return this._id;
		},
		getGroup: function()
		{
			return this._group;
		},
		setGroup: function(group)
		{
			this._group = group;
		},
		hasFocus: function()
		{
			return this._hasFosus;
		},
		getElement: function()
		{
			return this._element;
		},
		getElementTitle: function()
		{
			return this._elementTitle;
		},
		getValue: function()
		{
			return this._element.value;
		},
		_onElementKeyUp: function(e)
		{
			var c = e.keyCode;
			if(c === 13 || c === 27 || (c >=37 && c <= 40) || (c >=112 && c <= 123))
			{
				return;
			}

			if(this._value === this._element.value)
			{
				return;
			}
			this._value = this._element.value;

			if(this._elementTimeoutId > 0)
			{
				window.clearTimeout(this._elementTimeoutId);
				this._elementTimeoutId = 0;
			}
			this._elementTimeoutId = window.setTimeout(this._elementTimeoutHandler, 1500);

			if(!this._hasFosus)
			{
				this._hasFosus = true;
			}
		},
		_onElementFocus: function(e)
		{
			this._hasFosus = true;
			if(this._group)
			{
				this._group.processFieldFocusGain(this);
			}
		},
		_onElementBlur: function(e)
		{
			if(this._elementTimeoutId > 0)
			{
				window.clearTimeout(this._elementTimeoutId);
				this._elementTimeoutId = 0;
			}

			this._hasFosus = false;
			if(this._group)
			{
				this._group.processFieldFocusLoss(this);
			}
		},
		_onElementTimeout: function()
		{
			if(this._elementTimeoutId <= 0)
			{
				return;
			}

			this._elementTimeoutId = 0;
			if(this._group)
			{
				this._group.processFieldDelay(this);
			}
		}
	};
	BX.CrmDupCtrlField.create = function(id, element, elementTitle)
	{
		var self = new BX.CrmDupCtrlField();
		self.initialize(id, element, elementTitle);
		return self;
	}
}
if(typeof(BX.CrmDupCtrlRequisiteField) === "undefined")
{
	BX.CrmDupCtrlRequisiteField = function()
	{
		this._params = {
			formId: "",
			requisitePseudoId: "",
			presetId: 0,
			countryId: 0,
			fieldName: ""
		};

		BX.CrmDupCtrlRequisiteField.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmDupCtrlRequisiteField, BX.CrmDupCtrlField);
	BX.CrmDupCtrlRequisiteField.prototype.initialize = function(id, element, elementTitle, params)
	{
		this.setParams(params);
		BX.CrmDupCtrlRequisiteField.superclass.initialize.apply(this, [id, element, elementTitle]);
	};
	BX.CrmDupCtrlRequisiteField.prototype.setParams = function(params)
	{
		if (BX.type.isPlainObject(params))
		{
			if (params.hasOwnProperty("formId") && BX.type.isNotEmptyString(params["formId"]))
				this._params["formId"] = params["formId"];
			if (params.hasOwnProperty("requisitePseudoId") && BX.type.isNotEmptyString(params["requisitePseudoId"]))
				this._params["requisitePseudoId"] = params["requisitePseudoId"];
			if (params.hasOwnProperty("presetId"))
				this._params["presetId"] = parseInt(params["presetId"]);
			if (params.hasOwnProperty("countryId"))
				this._params["countryId"] = parseInt(params["countryId"]);
			if (params.hasOwnProperty("fieldName") && BX.type.isNotEmptyString(params["fieldName"]))
				this._params["fieldName"] = params["fieldName"];
		}

		if(!BX.type.isNotEmptyString(this._params["formId"])
			|| !BX.type.isNotEmptyString(this._params["requisitePseudoId"]) || this._params["presetId"] <= 0
			|| this._params["countryId"] <= 0 || !BX.type.isNotEmptyString(this._params["fieldName"]))
		{
			throw "BX.CrmDupCtrlRequisiteField. Invalid parameters.";
		}
	};
	BX.CrmDupCtrlRequisiteField.prototype.getParams = function()
	{
		return this._params;
	};
	BX.CrmDupCtrlRequisiteField.create = function(id, element, elementTitle, params)
	{
		var self = new BX.CrmDupCtrlRequisiteField();
		self.initialize(id, element, elementTitle, params);
		return self;
	}
}
if(typeof(BX.CrmDupCtrlBankDetailField) === "undefined")
{
	BX.CrmDupCtrlBankDetailField = function()
	{
		this._params = {
			formId: "",
			requisitePseudoId: "",
			presetId: 0,
			bankDetailPseudoId: "",
			countryId: 0,
			fieldName: ""
		};

		BX.CrmDupCtrlBankDetailField.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmDupCtrlBankDetailField, BX.CrmDupCtrlField);
	BX.CrmDupCtrlBankDetailField.prototype.initialize = function(id, element, elementTitle, params)
	{
		this.setParams(params);
		BX.CrmDupCtrlBankDetailField.superclass.initialize.apply(this, [id, element, elementTitle]);
	};
	BX.CrmDupCtrlBankDetailField.prototype.setParams = function(params)
	{
		if (BX.type.isPlainObject(params))
		{
			if (params.hasOwnProperty("formId") && BX.type.isNotEmptyString(params["formId"]))
				this._params["formId"] = params["formId"];
			if (params.hasOwnProperty("requisitePseudoId") && BX.type.isNotEmptyString(params["requisitePseudoId"]))
				this._params["requisitePseudoId"] = params["requisitePseudoId"];
			if (params.hasOwnProperty("presetId"))
				this._params["presetId"] = parseInt(params["presetId"]);
			if (params.hasOwnProperty("bankDetailPseudoId") && BX.type.isNotEmptyString(params["bankDetailPseudoId"]))
				this._params["bankDetailPseudoId"] = params["bankDetailPseudoId"];
			if (params.hasOwnProperty("countryId"))
				this._params["countryId"] = parseInt(params["countryId"]);
			if (params.hasOwnProperty("fieldName") && BX.type.isNotEmptyString(params["fieldName"]))
				this._params["fieldName"] = params["fieldName"];
		}

		if(!BX.type.isNotEmptyString(this._params["formId"])
			|| !BX.type.isNotEmptyString(this._params["requisitePseudoId"]) || this._params["presetId"] <= 0
			|| this._params["countryId"] <= 0 || !BX.type.isNotEmptyString(this._params["fieldName"]))
		{
			throw "BX.CrmDupCtrlBankDetailField. Invalid parameters.";
		}
	};
	BX.CrmDupCtrlBankDetailField.prototype.getParams = function()
	{
		return this._params;
	};
	BX.CrmDupCtrlBankDetailField.create = function(id, element, elementTitle, params)
	{
		var self = new BX.CrmDupCtrlBankDetailField();
		self.initialize(id, element, elementTitle, params);
		return self;
	}
}
if(typeof(BX.CrmDupCtrlFieldGroup) === "undefined")
{
	BX.CrmDupCtrlFieldGroup = function()
	{
		this._id = "";
		this._settings = {};
		this._controller = null;
		this._fields = {};
	};
	BX.CrmDupCtrlFieldGroup.prototype =
	{
		initialize: function(id, settings)
		{
			if(!BX.type.isNotEmptyString(id))
			{
				throw "BX.CrmDupCtrlFieldGroup. Invalid parameter 'id': is not defined.";
			}
			this._id = id;

			this._settings = settings ? settings : {};
			this._afterInitialize();
		},
		_afterInitialize: function()
		{
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getController: function()
		{
			return this._controller;
		},
		setController: function(controller)
		{
			this._controller = controller;
		},
		addField: function(field)
		{
			this._fields[field.getId()] = field;
			field.setGroup(this);
			return field;
		},
		removeField: function(field)
		{
			var fieldId = field.getId();
			if(this._fields.hasOwnProperty(fieldId))
			{
				delete this._fields[fieldId];
			}
		},
		getField: function(fieldId)
		{
			return this._fields.hasOwnProperty(fieldId) ? this._fields[fieldId] : null;
		},
		getFirstField: function()
		{
			var keys = Object.keys(this._fields);
			return keys.length > 0 ? this._fields[keys[0]] : null;
		},
		getFieldValues: function()
		{
			var result = [];
			for(var key in this._fields)
			{
				if(this._fields.hasOwnProperty(key))
				{
					var value = BX.util.trim(this._fields[key].getValue());
					if(value !== "")
					{
						result.push(value);
					}
				}
			}
			return result;
		},
		getFieldCount: function()
		{
			return Object.keys(this._fields).length;
		},
		clearFields: function()
		{
			for(var key in this._fields)
			{
				if(this._fields.hasOwnProperty(key))
				{
					this._fields[key].release();
				}
			}
			this._fields = {};
		},
		registerField: function(settings)
		{
			var fieldId = BX.prop.getString(settings, "id", "");
			if(fieldId === "")
			{
				return null;
			}

			var field = this.getField(fieldId);
			if(field)
			{
				return field;
			}

			var element = BX.prop.getElementNode(settings, "element", null);
			if(!element)
			{
				return null;
			}

			return this.addField(BX.CrmDupCtrlField.create(fieldId, element, null));
		},
		unregisterField: function(settings)
		{
			var fieldId = BX.prop.getString(settings, "id", "");
			if(fieldId === "")
			{
				return;
			}

			var field = this.getField(fieldId);
			if(field)
			{
				this.removeField(field);
				field.release();
			}
		},
		getSummaryTitle: function()
		{
			return this.getSetting("groupSummaryTitle", "");
		},
		prepareSearchParams: function()
		{
			return null;
		},
		getSearchHashCode: function()
		{
			return 0;
		},
		getDefaultSearchSummaryFieldId: function()
		{
			return "";
		},
		processFieldDelay: function(field)
		{
		},
		processFieldFocusGain: function(field)
		{
		},
		processFieldFocusLoss: function(field)
		{
		}
	};
}
if(typeof(BX.CrmDupCtrlSingleField) === "undefined")
{
	BX.CrmDupCtrlSingleField = function()
	{
		BX.CrmDupCtrlSingleField.superclass.constructor.apply(this);
		this._paramName = "";
		this._field = null;
	};
	BX.extend(BX.CrmDupCtrlSingleField, BX.CrmDupCtrlFieldGroup);
	BX.CrmDupCtrlSingleField.prototype._afterInitialize = function()
	{
		this._paramName = this.getSetting("parameterName", "");
		if(!BX.type.isNotEmptyString(this._paramName))
		{
			throw "BX.CrmDupCtrlSingleField. Could not find parameter name.";
		}

		var element = BX(this.getSetting("element", null));
		if(BX.type.isDomNode(element))
		{
			this._field = this.addField(BX.CrmDupCtrlField.create(this._paramName, element, BX(this.getSetting("elementCaption", null))));
		}
	};
	BX.CrmDupCtrlSingleField.prototype.registerField = function(settings)
	{
		var fieldId = BX.prop.getString(settings, "id", "");
		if(fieldId !== this._paramName)
		{
			return null;
		}

		var element = BX.prop.getElementNode(settings, "element", null);
		if(!element)
		{
			return null;
		}

		if(!this._field)
		{
			this._field = this.addField(BX.CrmDupCtrlField.create(this._paramName, element, null));
		}
		return this._field;
	};
	BX.CrmDupCtrlSingleField.prototype.getValue = function()
	{
		return this._field ? BX.util.trim(this._field.getValue()) : "";
	};
	BX.CrmDupCtrlSingleField.prototype.prepareSearchParams = function()
	{
		var value = this.getValue();
		if(value === "")
		{
			return null;
		}

		var result = {};
		result[this._paramName] = value;
		return result;
	};
	BX.CrmDupCtrlSingleField.prototype.getSearchHashCode = function()
	{
		var value = this.getValue();
		if(value === "")
		{
			return 0;
		}
		return BX.util.hashCode(value);
	};
	BX.CrmDupCtrlSingleField.prototype.getDefaultSearchSummaryFieldId = function()
	{
		return this._field ? this._field.getId() : ""
	};
	BX.CrmDupCtrlSingleField.prototype.processFieldDelay = function(field)
	{
		this._fireChangeEvent(field);
	};
	BX.CrmDupCtrlSingleField.prototype.processFieldFocusLoss = function(field)
	{
		this._fireChangeEvent(field);
	};
	BX.CrmDupCtrlSingleField.prototype._fireChangeEvent = function(field)
	{
		if(this._controller)
		{
			this._controller.processGroupChange(this, field);
		}
	};
	BX.CrmDupCtrlSingleField.create = function(id, settings)
	{
		var self = new BX.CrmDupCtrlSingleField();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDupCtrlFullName) === "undefined")
{
	BX.CrmDupCtrlFullName = function()
	{
		BX.CrmDupCtrlFullName.superclass.constructor.apply(this);
		this._nameField = null;
		this._secondNameField = null;
		this._lastNameField = null;
	};

	BX.extend(BX.CrmDupCtrlFullName, BX.CrmDupCtrlFieldGroup);
	BX.CrmDupCtrlFullName.prototype._afterInitialize = function()
	{
		var element = BX(this.getSetting("name", null));
		if(BX.type.isDomNode(element))
		{
			this._nameField = this.addField(BX.CrmDupCtrlField.create("NAME", element, BX(this.getSetting("nameCaption", null))));
		}
		element = BX(this.getSetting("secondName", null));
		if(BX.type.isDomNode(element))
		{
			this._secondNameField = this.addField(BX.CrmDupCtrlField.create("SECOND_NAME", element, BX(this.getSetting("secondNameCaption", null))));
		}
		element = BX(this.getSetting("lastName", null));
		if(BX.type.isDomNode(element))
		{
			this._lastNameField = this.addField(BX.CrmDupCtrlField.create("LAST_NAME", element, BX(this.getSetting("lastNameCaption", null))));
		}
	};
	BX.CrmDupCtrlFullName.prototype.registerField = function(settings)
	{
		var fieldId = BX.prop.getString(settings, "id", "");
		if(fieldId === "")
		{
			return null;
		}

		var field = this.getField(fieldId);
		if(field)
		{
			return field;
		}

		var element = BX.prop.getElementNode(settings, "element", null);
		if(!element)
		{
			return null;
		}

		field = this.addField(BX.CrmDupCtrlField.create(fieldId, element, null));
		if(fieldId === "NAME")
		{
			this._nameField = field;
		}
		else if(fieldId === "SECOND_NAME")
		{
			this._secondNameField = field;
		}
		else if(fieldId === "LAST_NAME")
		{
			this._lastNameField = field;
		}

		return field;
	};
	BX.CrmDupCtrlFullName.prototype.getName = function()
	{
		return this._nameField ? BX.util.trim(this._nameField.getValue()) : "";
	};
	BX.CrmDupCtrlFullName.prototype.getSecondName = function()
	{
		return this._secondNameField ? BX.util.trim(this._secondNameField.getValue()) : "";
	};
	BX.CrmDupCtrlFullName.prototype.getLastName = function()
	{
		return this._lastNameField ? BX.util.trim(this._lastNameField.getValue()) : "";
	};
	BX.CrmDupCtrlFullName.prototype.prepareSearchParams = function()
	{
		var lastName = this.getLastName();
		if(lastName === "")
		{
			return null;
		}

		var result = { "LAST_NAME": lastName };
		var name = this.getName();
		if(name !== "")
		{
			result["NAME"] = name;
		}
		var secondName = this.getSecondName();
		if(secondName !== "")
		{
			result["SECOND_NAME"] = secondName;
		}

		return result;
	};
	BX.CrmDupCtrlFullName.prototype.getSearchHashCode = function()
	{
		var lastName = this.getLastName();
		if(lastName === "")
		{
			return 0;
		}

		var key = lastName.toLowerCase();
		var name = this.getName();
		if(name !== "")
		{
			key += "$" + name.toLowerCase();
		}

		var secondName = this.getSecondName();
		if(secondName !== "")
		{
			key += "$" + secondName.toLowerCase();
		}

		return BX.util.hashCode(key);
	};
	BX.CrmDupCtrlFullName.prototype.getDefaultSearchSummaryFieldId = function()
	{
		return this._lastNameField ? this._lastNameField.getId() : ""
	};
	BX.CrmDupCtrlFullName.prototype.processFieldDelay = function(field)
	{
		this._fireChangeEvent(field);
	};
	BX.CrmDupCtrlFullName.prototype.processFieldFocusLoss = function(field)
	{
		this._fireChangeEvent(field);
	};
	BX.CrmDupCtrlFullName.prototype._fireChangeEvent = function(field)
	{
		if(this._controller)
		{
			this._controller.processGroupChange(this, field);
		}
	};
	BX.CrmDupCtrlFullName.create = function(id, settings)
	{
		var self = new BX.CrmDupCtrlFullName();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDupCtrlCommunication) === "undefined")
{
	BX.CrmDupCtrlCommunication = function()
	{
		this._communicationType = "";
		this._container = null;
		this._editorCreateItemHandler = BX.delegate(this.onCommunicaionEditorItemCreate, this);
		this._editorDeleteItemHandler = BX.delegate(this.onCommunicaionEditorItemDelete, this);

		BX.CrmDupCtrlCommunication.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmDupCtrlCommunication, BX.CrmDupCtrlFieldGroup);
	BX.CrmDupCtrlCommunication.prototype._afterInitialize = function()
	{
		this._communicationType = this.getSetting("communicationType", "");
		if(!BX.type.isNotEmptyString(this._communicationType))
		{
			throw "BX.CrmDupCtrlCommunication. Could not find communication type.";
		}

		this._editorId = this.getSetting("editorId", "");
		this._container = this.getSetting("container", null);
		if(BX.type.isNotEmptyString(this._container))
		{
			this._container = BX(this._container);
		}
		if(!BX.type.isElementNode(this._container))
		{
			this._container = BX(this._editorId);
		}

		BX.addCustomEvent(window, "CrmFieldMultiEditorItemCreated", this._editorCreateItemHandler);
		BX.addCustomEvent(window, "CrmFieldMultiEditorItemDeleted", this._editorDeleteItemHandler);

		this._initializeFields();
	};
	BX.CrmDupCtrlCommunication.prototype._initializeFields = function()
	{
		this.clearFields();

		if(!this._container)
		{
			return;
		}

		var caption = BX(this.getSetting("editorCaption", null));
		var inputs = BX.findChildren(this._container, { tagName: "input", className: "bx-crm-edit-input" }, true);
		var length = inputs.length;
		for(var i = 0; i < length; i++)
		{
			this.addField(BX.CrmDupCtrlField.create("VALUE_" + (i + 1).toString(), inputs[i], caption));
		}
	};
	BX.CrmDupCtrlCommunication.prototype.prepareFieldId = function(index)
	{
		return ("VALUE_" + (index + 1).toString());
	};
	BX.CrmDupCtrlCommunication.prototype.registerField = function(settings)
	{
		var fieldId = BX.prop.getString(settings, "id", "");
		if(fieldId === "")
		{
			fieldId = this.prepareFieldId(this.getFieldCount());
		}

		var element = BX.prop.getElementNode(settings, "element", null);
		if(!element)
		{
			return null;
		}

		return this.addField(BX.CrmDupCtrlField.create(fieldId, element, null));
	};
	BX.CrmDupCtrlCommunication.prototype.prepareSearchParams = function()
	{
		var rawValues = this.getFieldValues();
		var length = rawValues.length;
		if(length === 0)
		{
			return null;
		}

		var result = {};
		if(this._communicationType !== "PHONE")
		{
			result[this._communicationType] = rawValues;
			return result;
		}

		var values = [];
		for(var i = 0; i < length; i++)
		{
			var value = rawValues[i];
			if(value.length >= 5)
			{
				values.push(value);
			}
		}

		if(values.length === 0)
		{
			return null;
		}

		result["PHONE"] = values;
		return result;
	};
	BX.CrmDupCtrlCommunication.prototype.getSearchHashCode = function()
	{
		var values = this.getFieldValues();
		return (values.length > 0 ? BX.util.hashCode(values.join("$")) : 0);
	};
	BX.CrmDupCtrlCommunication.prototype.getDefaultSearchSummaryFieldId = function()
	{
		var field = this.getFirstField();
		return field ? field.getId() : "";
	};
	BX.CrmDupCtrlCommunication.prototype.processFieldDelay = function(field)
	{
		if(this._controller)
		{
			this._controller.processGroupChange(this, field);
		}
	};
	BX.CrmDupCtrlCommunication.prototype.processFieldFocusLoss = function(field)
	{
		if(this._controller)
		{
			this._controller.processGroupChange(this, field);
		}
	};
	BX.CrmDupCtrlCommunication.prototype.onCommunicaionEditorItemCreate = function(sender, editorId)
	{
		if(this._editorId !== editorId)
		{
			return;
		}

		this._initializeFields();

		//if(this._controller)
		//{
		//	this._controller.processGroupChange(this, field);
		//}
	};
	BX.CrmDupCtrlCommunication.prototype.onCommunicaionEditorItemDelete = function(sender, editorId)
	{
		if(this._editorId !== editorId)
		{
			return;
		}

		this._initializeFields();

		if(this._controller)
		{
			var qty = this.getFieldCount();
			this._controller.processGroupChange(
				this,
				qty > 0 ? this.getField(this.prepareFieldId(qty - 1)) : null
			);
		}
	};
	BX.CrmDupCtrlCommunication.create = function(id, settings)
	{
		var self = new BX.CrmDupCtrlCommunication();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDupCtrlRequisite) === "undefined")
{
	BX.CrmDupCtrlRequisite = function()
	{
		this._fieldIndex = [];
		this._firstField = null;
		this._lastField = null;

		BX.CrmDupCtrlRequisite.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmDupCtrlRequisite, BX.CrmDupCtrlFieldGroup);
	BX.CrmDupCtrlRequisite.prototype.addField = function(field)
	{
		var fieldId = field.getId();
		this._fields[fieldId] = field;
		field.setGroup(this);

		if (!this._firstField)
			this._firstField = field;
		this._lastField = field;

		this._fieldIndex.push(fieldId);

		return field;
	};
	BX.CrmDupCtrlRequisite.prototype.removeField = function(fieldId)
	{
		var result = false;
		if (this._fields.hasOwnProperty(fieldId))
		{
			this._fields[fieldId].release();
			delete this._fields[fieldId];

			var index = this._fieldIndex.indexOf(fieldId);
			var length = this._fieldIndex.length;
			if (index >= 0)
			{
				if (index === 0)
					this._firstField = (length > 1) ? this._fields[this._fieldIndex[index + 1]] : null;
				if (index === (length - 1))
					this._lastField = (length > 1) ? this._fields[this._fieldIndex[index - 1]] : null;
				this._fieldIndex.splice(index, 1);
			}

			result = true;
		}

		return result;
	};
	BX.CrmDupCtrlRequisite.prototype.prepareRequisiteList = function()
	{
		var requisiteIndex = {};
		var requisiteList = [];
		var index = 0;
		for(var key in this._fields)
		{
			if(this._fields.hasOwnProperty(key))
			{
				var value = BX.util.trim(this._fields[key].getValue());
				if(value !== "")
				{
					var params = this._fields[key].getParams();

					var requisiteFields;
					if (requisiteIndex.hasOwnProperty(params["requisitePseudoId"]))
					{
						requisiteFields = requisiteList[requisiteIndex[params["requisitePseudoId"]]];
					}
					else
					{
						requisiteIndex[params["requisitePseudoId"]] = index;
						requisiteList[index++] = requisiteFields = {};
					}
					requisiteFields["ID"] = params["requisitePseudoId"];
					requisiteFields["PRESET_ID"] = params["presetId"];
					requisiteFields["PRESET_COUNTRY_ID"] = params["countryId"];
					requisiteFields[params["fieldName"]] = value;
					requisiteFields = null;
				}
			}
		}

		return requisiteList;
	};
	BX.CrmDupCtrlRequisite.prototype.prepareSearchParams = function()
	{
		var result = null;

		var requisiteList = this.prepareRequisiteList();
		if (requisiteList.length > 0)
		{
			result = {};
			result[this._id] = requisiteList;
		}

		return result;
	};
	BX.CrmDupCtrlRequisite.prototype.getSearchHashCode = function()
	{
		var requisiteList = this.prepareRequisiteList();

		var values = [];
		for (var i = 0; i < requisiteList.length; i++)
		{
			var valueString = "";
			for (var fieldName in requisiteList[i])
			{
				if (requisiteList[i].hasOwnProperty(fieldName))
				{
					var delimiter = (valueString === "") ? "" : "|";
					valueString += delimiter + requisiteList[i][fieldName];
				}
			}
			values.push(valueString);
		}

		return (values.length > 0 ? BX.util.hashCode(values.join("$")) : 0);
	};
	BX.CrmDupCtrlRequisite.prototype.getDefaultSearchSummaryFieldId = function()
	{
		return this._firstField ? this._firstField.getId() : ""
	};
	BX.CrmDupCtrlRequisite.prototype.processFieldDelay = function(field)
	{
		if(this._controller)
		{
			this._controller.processGroupChange(this, field);
		}
	};
	BX.CrmDupCtrlRequisite.prototype.processFieldFocusLoss = function(field)
	{
		if(this._controller)
		{
			this._controller.processGroupChange(this, field);
		}
	};
	BX.CrmDupCtrlRequisite.create = function(id, settings)
	{
		var self = new BX.CrmDupCtrlRequisite();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDupCtrlBankDetail) === "undefined")
{
	BX.CrmDupCtrlBankDetail = function()
	{
		this._fieldIndex = [];
		this._firstField = null;
		this._lastField = null;

		BX.CrmDupCtrlBankDetail.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmDupCtrlBankDetail, BX.CrmDupCtrlFieldGroup);
	BX.CrmDupCtrlBankDetail.prototype.addField = function(field)
	{
		var fieldId = field.getId();
		this._fields[fieldId] = field;
		field.setGroup(this);

		if (!this._firstField)
			this._firstField = field;
		this._lastField = field;

		this._fieldIndex.push(fieldId);

		return field;
	};
	BX.CrmDupCtrlBankDetail.prototype.removeField = function(fieldId)
	{
		var result = false;
		if (this._fields.hasOwnProperty(fieldId))
		{
			this._fields[fieldId].release();
			delete this._fields[fieldId];

			var index = this._fieldIndex.indexOf(fieldId);
			var length = this._fieldIndex.length;
			if (index >= 0)
			{
				if (index === 0)
					this._firstField = (length > 1) ? this._fields[this._fieldIndex[index + 1]] : null;
				if (index === (length - 1))
					this._lastField = (length > 1) ? this._fields[this._fieldIndex[index - 1]] : null;
				this._fieldIndex.splice(index, 1);
			}

			result = true;
		}

		return result;
	};
	BX.CrmDupCtrlBankDetail.prototype.prepareBankDetailList = function()
	{
		var bankDetailIndex = {};
		var bankDetailList = [];
		var index = 0;
		for(var key in this._fields)
		{
			if(this._fields.hasOwnProperty(key))
			{
				var value = BX.util.trim(this._fields[key].getValue());
				if(value !== "")
				{
					var params = this._fields[key].getParams();

					var bankDetailFields;
					if (bankDetailIndex.hasOwnProperty(params["bankDetailPseudoId"]))
					{
						bankDetailFields = bankDetailList[bankDetailIndex[params["bankDetailPseudoId"]]];
					}
					else
					{
						bankDetailIndex[params["bankDetailPseudoId"]] = index;
						bankDetailList[index++] = bankDetailFields = {};
					}
					bankDetailFields["ID"] = params["bankDetailPseudoId"];
					bankDetailFields["REQUISITE_ID"] = params["requisitePseudoId"];
					bankDetailFields["PRESET_ID"] = params["presetId"];
					bankDetailFields["PRESET_COUNTRY_ID"] = params["countryId"];
					bankDetailFields[params["fieldName"]] = value;
					bankDetailFields = null;
				}
			}
		}

		return bankDetailList;
	};
	BX.CrmDupCtrlBankDetail.prototype.prepareSearchParams = function()
	{
		var result = null;

		var bankDetailList = this.prepareBankDetailList();
		if (bankDetailList.length > 0)
		{
			result = {};
			result[this._id] = bankDetailList;
		}

		return result;
	};
	BX.CrmDupCtrlBankDetail.prototype.getSearchHashCode = function()
	{
		var bankDetailList = this.prepareBankDetailList();

		var values = [];
		for (var i = 0; i < bankDetailList.length; i++)
		{
			var valueString = "";
			for (var fieldName in bankDetailList[i])
			{
				if (bankDetailList[i].hasOwnProperty(fieldName))
				{
					var delimiter = (valueString === "") ? "" : "|";
					valueString += delimiter + bankDetailList[i][fieldName];
				}
			}
			values.push(valueString);
		}

		return (values.length > 0 ? BX.util.hashCode(values.join("$")) : 0);
	};
	BX.CrmDupCtrlBankDetail.prototype.getDefaultSearchSummaryFieldId = function()
	{
		return this._firstField ? this._firstField.getId() : ""
	};
	BX.CrmDupCtrlBankDetail.prototype.processFieldDelay = function(field)
	{
		if(this._controller)
		{
			this._controller.processGroupChange(this, field);
		}
	};
	BX.CrmDupCtrlBankDetail.prototype.processFieldFocusLoss = function(field)
	{
		if(this._controller)
		{
			this._controller.processGroupChange(this, field);
		}
	};
	BX.CrmDupCtrlBankDetail.create = function(id, settings)
	{
		var self = new BX.CrmDupCtrlBankDetail();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDupControllerRequisite) === "undefined")
{
	BX.CrmDupControllerRequisite = function()
	{
		this._id = "";
		this._settings = {};
		this._dupControllerId = "";
		this._dupController = null;
		this._groups = {};
		this._formFieldMap = {};
		this._requisiteEditFormCreateHandler = BX.delegate(this.onRequisiteEditFormCreate, this);
		this._requisiteEditFormRemoveHandler = BX.delegate(this.onRequisiteEditFormRemove, this);
		this._dupControllerDeleteHandler = BX.delegate(this.onDupControllerDelete, this);
		this._requisitePopupCloseHandler = BX.delegate(this.onRequisitePopupClose, this);
		this._dupControllerAfterInitializeHandler = BX.delegate(this.onDupControllerAfterInitialize, this);
		this._requisiteEditFormGetParamsCallback = BX.delegate(this.onRequisiteEditFormParams, this);
		this._dupControllerRequisiteFindHandler = BX.delegate(this.onDupControllerRequisiteFind, this);
		this._requisitePopupSaveLockHandler = BX.delegate(this.onRequisitePopupSaveLock, this);
		this._warningDialog = null;
	};
	BX.CrmDupControllerRequisite.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_dp_ctrl_rq_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._dupControllerId = this.getSetting("dupControllerId", "");
			if (BX.type.isNotEmptyString(this._dupControllerId)
				&& typeof(BX.CrmDupController.items[this._dupControllerId]) === "object"
				&& BX.CrmDupController.items[this._dupControllerId] !== null)
			{
				this._dupController = BX.CrmDupController.items[this._dupControllerId];
			}

			this._bind();
		},
		destroy: function()
		{
			this._unbind();
			for(var key in this._groups)
			{
				if(this._groups.hasOwnProperty(key))
					this.deleteGroup(key);
			}
			this._groups = {};
			this.setDupController(null);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		_bind: function()
		{
			if (this._dupController)
			{
				BX.addCustomEvent("CrmRequisiteEditFormCreate", this._requisiteEditFormCreateHandler);
				BX.addCustomEvent("CrmFormSettingManagerSectionRemove", this._requisiteEditFormRemoveHandler);
				BX.addCustomEvent("CrmDupControllerDelete", this._dupControllerDeleteHandler);
				BX.addCustomEvent("CrmRequisitePopupFormManagerClosePopup", this._requisitePopupCloseHandler);
			}
			else
			{
				BX.addCustomEvent("CrmDupControllerAfterInitialize", this._dupControllerAfterInitializeHandler);
			}
			BX.addCustomEvent("CrmDupControllerRequisiteFind", this._dupControllerRequisiteFindHandler);
			BX.addCustomEvent(this, "CrmRequisitePopupFormManagerSaveLock", this._requisitePopupSaveLockHandler);
		},
		_unbind: function()
		{
			BX.removeCustomEvent("CrmRequisiteEditFormCreate", this._requisiteEditFormCreateHandler);
			BX.removeCustomEvent("CrmFormSettingManagerSectionRemove", this._requisiteEditFormRemoveHandler);
			BX.removeCustomEvent("CrmDupControllerDelete", this._dupControllerDeleteHandler);
			BX.removeCustomEvent("CrmRequisitePopupFormManagerClosePopup", this._requisitePopupCloseHandler);
			BX.removeCustomEvent("CrmDupControllerAfterInitialize", this._dupControllerAfterInitializeHandler);
			BX.removeCustomEvent("CrmDupControllerRequisiteFind", this._dupControllerRequisiteFindHandler);
			BX.removeCustomEvent(this, "CrmRequisitePopupFormManagerSaveLock", this._requisitePopupSaveLockHandler);
		},
		_openWarningDialog: function()
		{
			this._warningDialog = BX.CrmDuplicateWarningDialog.create(
				this._id + "_warn",
				{
					"controller": this._dupController,
					"onClose": BX.delegate(this._onWarningDialogClose, this),
					"onCancel": BX.delegate(this._onWarningDialogCancel, this),
					"onAccept": BX.delegate(this._onWarningDialogAccept, this)
				}
			);
			this._warningDialog.show();
		},
		_onWarningDialogClose: function(dlg)
		{
			if(this._warningDialog === dlg)
			{
				this._warningDialog = null;
			}
		},
		_onWarningDialogCancel: function(dlg)
		{
			if(this._warningDialog === dlg)
			{
				this._warningDialog.close();
				BX.onCustomEvent("CrmRequisitePopupFormManagerDoSave", [this, false]);
			}
		},
		_onWarningDialogAccept: function(dlg)
		{
			if(this._warningDialog === dlg)
			{
				this._warningDialog.close();
				BX.onCustomEvent("CrmRequisitePopupFormManagerDoSave", [this, true]);
			}
		},
		onDupControllerAfterInitialize: function(dupController)
		{
			if (dupController instanceof BX.CrmDupController)
			{
				if (this._dupControllerId === dupController.getId())
				{
					BX.removeCustomEvent("CrmDupControllerAfterInitialize", this._dupControllerAfterInitializeHandler);
					this.setDupController(dupController);
					BX.onCustomEvent("CrmRequisiteEditFormGetParams", [this._requisiteEditFormGetParamsCallback]);
				}
			}
		},
		setDupController: function(dupController)
		{
			var oldDupController = this._dupController;
			this._dupController = dupController;
			if (!oldDupController && this._dupController)
			{
				BX.addCustomEvent("CrmRequisiteEditFormCreate", this._requisiteEditFormCreateHandler);
				BX.addCustomEvent("CrmFormSettingManagerSectionRemove", this._requisiteEditFormRemoveHandler);
				BX.addCustomEvent("CrmDupControllerDelete", this._dupControllerDeleteHandler);
				BX.addCustomEvent("CrmRequisitePopupFormManagerClosePopup", this._requisitePopupCloseHandler);
			}
			else if (!this._dupController)
			{
				BX.removeCustomEvent("CrmRequisiteEditFormCreate", this._requisiteEditFormCreateHandler);
				BX.removeCustomEvent("CrmFormSettingManagerSectionRemove", this._requisiteEditFormRemoveHandler);
				BX.removeCustomEvent("CrmDupControllerDelete", this._dupControllerDeleteHandler);
				BX.removeCustomEvent("CrmRequisitePopupFormManagerClosePopup", this._requisitePopupCloseHandler);
			}
		},
		onRequisiteEditFormParams: function(params)
		{

			this.onRequisiteEditFormCreate(params);
		},
		onDupControllerRequisiteFind: function(requisitePopupFormManager, result)
		{
			if (requisitePopupFormManager !== null && typeof(requisitePopupFormManager) === "object")
			{
				var formId = requisitePopupFormManager.getFormId();
				formId = formId.replace(/[^a-z0-9_]/ig, "");
				if (BX.type.isNotEmptyString(formId) && this._formFieldMap.hasOwnProperty(formId)
					&& BX.type.isArray(result))
				{
					result.push(this);
				}
			}
		},
		onRequisitePopupSaveLock: function()
		{
			if (!(this._dupController && this._dupController.hasDuplicates()))
			{
				BX.onCustomEvent("CrmRequisitePopupFormManagerDoSave", [this, true]);
			}
			else
			{
				window.setTimeout(BX.delegate(this._openWarningDialog, this), 100);
			}
		},
		onRequisiteEditFormCreate: function(params)
		{
			var i = 0;
			var formId = "";
			var container = null;
			var containerId = "";
			var countryId = 0;
			var fieldNameTemplate = "";
			var requisitePseudoId = "";
			var presetId = 0;
			var enableFieldMasquerading = false;
			var fieldSelectorTemplate = "";

			if (BX.type.isPlainObject(params))
			{
				if (BX.type.isNotEmptyString(params["formId"]))
				{
					formId = params["formId"];
					formId = formId.replace(/[^a-z0-9_]/ig, "");
				}
				if (BX.type.isNotEmptyString(params["containerId"]))
				{
					containerId = params["containerId"];
					container = BX(containerId);
				}
				if (BX.type.isNotEmptyString(params["countryId"]) || BX.type.isNumber(params["countryId"]))
					countryId = parseInt(params["countryId"]);
				if (params["enableFieldMasquerading"] === true)
					enableFieldMasquerading = true;
				if (BX.type.isNotEmptyString(params["fieldNameTemplate"]))
				{
					fieldNameTemplate = params["fieldNameTemplate"];
					fieldSelectorTemplate = fieldNameTemplate.replace(/\[/g, "\\5b ").replace(/]/g, "\\5d ");
				}
			}
			if (BX.type.isNotEmptyString(formId))
			{
				var matches = formId.match(/^([a-z0-9_]+)_(n?\d+)_PID(\d+)$/i);
				if (BX.type.isArray(matches) && matches.length === 4)
				{
					requisitePseudoId = matches[2];
					presetId = parseInt(matches[3]);
				}
			}

			if (BX.type.isNotEmptyString(formId) && BX.type.isDomNode(container) && countryId > 0
				&& BX.type.isNotEmptyString(requisitePseudoId) && presetId > 0)
			{
				var dupFieldsMap = this.getSetting("dupFieldsMap", {});
				var fieldsSelector = [], fieldSelector, elements, fieldName;
				var dupFieldCountryId, dupFields, selectorIndex;
				var useFieldTemplate = (enableFieldMasquerading && BX.type.isNotEmptyString(fieldSelectorTemplate));

				for (dupFieldCountryId in dupFieldsMap)
				{
					if (!dupFieldsMap.hasOwnProperty(dupFieldCountryId))
						continue;

					if (dupFieldsMap.hasOwnProperty(dupFieldCountryId)
						&& countryId === parseInt(dupFieldCountryId)
						&& BX.type.isArray(dupFieldsMap[dupFieldCountryId]))
					{
						dupFields = dupFieldsMap[dupFieldCountryId];
						selectorIndex = 0;
						for (i = 0; i < dupFields.length; i++)
						{
							fieldSelector = useFieldTemplate ?
								fieldSelectorTemplate.replace("#FIELD_NAME#", dupFields[i]) : dupFields[i];
							fieldsSelector[selectorIndex++] = "[name=" + fieldSelector + "]";
						}
					}
				}

				fieldsSelector = fieldsSelector.join(",");

				elements = container.querySelectorAll(fieldsSelector);
				for (i = 0; i < elements.length; i++)
				{
					fieldName = this.getFieldNameByElement(
						elements[i],
						(useFieldTemplate) ? fieldNameTemplate : ""
					);

					if (BX.type.isNotEmptyString(fieldName))
					{
						this.registerRequisiteField(
							formId, requisitePseudoId, presetId, countryId, fieldName, elements[i]
						);
					}
				}
			}
		},
		onRequisiteEditFormRemove: function(formSettingManager)
		{
			var formId = "";

			if (formSettingManager !== null && typeof(formSettingManager) === "object")
			{
				formId = formSettingManager.getFormId();
				if (BX.type.isNotEmptyString(formId))
				{
					formId = formId.replace(/[^a-z0-9_]/ig, "");
					this.unregisterRequisiteFieldsByFormId(formId);
				}
			}
		},
		onDupControllerDelete: function(dupController)
		{
			if (this._dupController && this._dupController === dupController)
				BX.CrmDupControllerRequisite.delete(this._id);
		},
		onRequisitePopupClose: function(requisitePopupFormManager)
		{
			var formId = "";

			if (requisitePopupFormManager !== null && typeof(requisitePopupFormManager) === "object")
			{
				formId = requisitePopupFormManager.getFormId();
				if (BX.type.isNotEmptyString(formId))
				{
					formId = formId.replace(/[^a-z0-9_]/ig, "");
					if (BX.type.isNotEmptyString(formId) && this._formFieldMap.hasOwnProperty(formId))
					{
						if (this._warningDialog !== null && typeof(this._warningDialog) === "object")
						{
							this._warningDialog.close();
						}
						var dupController = null;
						if (this._dupController)
							dupController = this._dupController;
						BX.CrmDupControllerRequisite.delete(this._id);
						if (dupController)
							BX.CrmDupController.delete(dupController.getId())
					}
				}
			}
		},
		getFieldNameByElement: function(element, fieldNameTemplate)
		{
			var fieldName = "";

			if (BX.type.isElementNode(element))
			{
				fieldName = element.getAttribute("name");
				if (BX.type.isNotEmptyString(fieldNameTemplate) && BX.type.isNotEmptyString(fieldName))
				{
					var marker, postfix;
					var pos;
					marker = "#FIELD_NAME#";
					pos = fieldNameTemplate.indexOf(marker);
					if (pos >= 0)
					{
						if (pos < fieldName.length)
						{
							fieldName = fieldName.substr(pos);
						}

						pos = pos + marker.length;
						if (pos < fieldNameTemplate.length)
						{
							postfix = fieldNameTemplate.substr(pos);
							pos = fieldName.lastIndexOf(postfix);
							if (pos >= 0)
							{
								fieldName = fieldName.substr(0, pos);
							}
						}
					}
				}
			}

			return fieldName;
		},
		registerRequisiteField: function(formId, requisitePseudoId, presetId, countryId, fieldName, element)
		{
			var groupId = fieldName + "|" + countryId.toString();

			var group = this.getGroup(groupId);
			if (!group)
			{
				var dupFieldsDescr = this.getSetting("dupFieldsDescriptions", {});
				var title = groupId;
				if (dupFieldsDescr[fieldName] && dupFieldsDescr[fieldName][countryId])
				{
					title = dupFieldsDescr[fieldName][countryId];
				}
				group = this.addGroup(
					groupId,
					{
						"controller": this,
						"countryId": countryId,
						"fieldName": fieldName,
						"groupSummaryTitle": this.getSetting("groupSummaryTitle", "") + " \"" + title + "\""
					}
				);
			}

			if (group)
			{
				var fieldId = element.getAttribute("name");

				if (!this._formFieldMap.hasOwnProperty(formId))
					this._formFieldMap[formId] = {};
				if (!this._formFieldMap[formId].hasOwnProperty(groupId))
					this._formFieldMap[formId][groupId] = {};

				this._formFieldMap[formId][groupId][fieldId] =
					group.addField(
						BX.CrmDupCtrlRequisiteField.create(
							fieldId,
							element,
							null,
							{
								formId: formId,
								requisitePseudoId: requisitePseudoId,
								presetId: presetId,
								countryId: countryId,
								fieldName: fieldName
							}
						)
					);
			}
		},
		unregisterRequisiteFieldsByFormId: function(formId)
		{
			if (BX.type.isNotEmptyString(formId) && this._formFieldMap.hasOwnProperty(formId))
			{
				var groupId;
				var groups = this._formFieldMap[formId];
				var changedFieldsByGroup = {};

				delete this._formFieldMap[formId];

				for (groupId in groups)
				{
					if (groups.hasOwnProperty(groupId))
					{
						var fieldId;
						var fields = groups[groupId];
						for (fieldId in fields)
						{
							if (fields.hasOwnProperty(fieldId))
							{
								if (this.unregisterRequisiteField(groupId, fieldId))
								{
									if (!changedFieldsByGroup.hasOwnProperty(groupId))
										changedFieldsByGroup[groupId] = {
											group: this.getGroup(groupId),
											fields: []
										};
									changedFieldsByGroup[groupId]["fields"].push(fields[fieldId]);
								}
							}
						}
					}
				}

				if (this._dupController)
					this._dupController.processGroupsChange(changedFieldsByGroup);
			}
		},
		unregisterRequisiteField: function(groupId, fieldId)
		{
			var group = this.getGroup(groupId);
			if (group)
			{
				return group.removeField(fieldId);
			}

			return false;
		},
		addGroup: function(groupId, groupSettings)
		{
			var result = null;

			if (BX.type.isNotEmptyString(groupId))
			{
				var group = BX.CrmDupCtrlRequisite.create(groupId, groupSettings);
				if (group)
				{
					this._groups[groupId] = group;
					if (this._dupController)
						this._dupController.addGroup(group);
					result = group;
				}
			}

			return result;
		},
		deleteGroup: function(groupId)
		{
			var result = false;

			if (BX.type.isNotEmptyString(groupId) && this._groups.hasOwnProperty(groupId))
			{
				if (this._dupController)
					this._dupController.deleteGroup(groupId);
				else
					this._groups[groupId].clearFields();
				delete this._groups[groupId];
				result = true;
			}

			return result;
		},
		getGroup: function(groupId)
		{
			if (BX.type.isNotEmptyString(groupId) && this._groups.hasOwnProperty(groupId))
			{
				return this._groups[groupId];
			}

			return null;
		}
	};
	BX.CrmDupControllerRequisite.items = {};
	BX.CrmDupControllerRequisite.getItem = function(id)
	{
		return this.items.hasOwnProperty(id) ? this.items[id] : null;
	};
	BX.CrmDupControllerRequisite.create = function(id, settings)
	{
		var self = new BX.CrmDupControllerRequisite();
		self.initialize(id, settings);
		BX.CrmDupControllerRequisite.items[id] = self;
		return self;
	};
	BX.CrmDupControllerRequisite.delete = function(id)
	{
		if (BX.CrmDupControllerRequisite.items.hasOwnProperty(id))
		{
			var self = BX.CrmDupControllerRequisite.items[id];
			self.destroy();
			delete BX.CrmDupControllerRequisite.items[id];
		}
	};
}
if(typeof(BX.CrmDupControllerBankDetail) === "undefined")
{
	BX.CrmDupControllerBankDetail = function()
	{
		this._id = "";
		this._settings = {};
		this._dupControllerId = "";
		this._dupController = null;
		this._groups = {};
		this._formFieldMap = {};
		this._bankDetailBlockCreateHandler = BX.delegate(this.onBankDetailBlockCreate, this);
		this._requisiteEditFormRemoveHandler = BX.delegate(this.onRequisiteEditFormRemove, this);
		this._dupControllerDeleteHandler = BX.delegate(this.onDupControllerDelete, this);
		this._requisitePopupCloseHandler = BX.delegate(this.onRequisitePopupClose, this);
		this._bankDetailBlockRemoveHandler = BX.delegate(this.onBankDetailBlockRemove, this);
		this._dupControllerAfterInitializeHandler = BX.delegate(this.onDupControllerAfterInitialize, this);
		this._bankDetailBlockGetParamsCallback = BX.delegate(this.onBankDetailBlockParams, this);
	};
	BX.CrmDupControllerBankDetail.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_dp_ctrl_bd_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._dupControllerId = this.getSetting("dupControllerId", "");
			var dupController = BX.CrmDupController.getItem(this._dupControllerId);
			if (BX.type.isNotEmptyString(this._dupControllerId)
				&& typeof(dupController) === "object" && dupController !== null)
			{
				this._dupController = dupController;
			}

			this._bind();
		},
		destroy: function()
		{
			this._unbind();
			for(var key in this._groups)
			{
				if(this._groups.hasOwnProperty(key))
					this.deleteGroup(key);
			}
			this._groups = {};
			this.setDupController(null);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		_bind: function()
		{
			if (this._dupController)
			{
				BX.addCustomEvent("CrmFormBankDetailBlockCreate", this._bankDetailBlockCreateHandler);
				BX.addCustomEvent("CrmFormSettingManagerSectionRemove", this._requisiteEditFormRemoveHandler);
				BX.addCustomEvent("CrmDupControllerDelete", this._dupControllerDeleteHandler);
				BX.addCustomEvent("CrmRequisitePopupFormManagerClosePopup", this._requisitePopupCloseHandler);
				BX.addCustomEvent("CrmFormBankDetailBlockRemove", this._bankDetailBlockRemoveHandler);
			}
			else
			{
				BX.addCustomEvent("CrmDupControllerAfterInitialize", this._dupControllerAfterInitializeHandler);
			}
		},
		_unbind: function()
		{
			BX.removeCustomEvent("CrmFormBankDetailBlockCreate", this._bankDetailBlockCreateHandler);
			BX.removeCustomEvent("CrmFormSettingManagerSectionRemove", this._requisiteEditFormRemoveHandler);
			BX.removeCustomEvent("CrmDupControllerDelete", this._dupControllerDeleteHandler);
			BX.removeCustomEvent("CrmRequisitePopupFormManagerClosePopup", this._requisitePopupCloseHandler);
			BX.removeCustomEvent("CrmFormBankDetailBlockRemove", this._bankDetailBlockRemoveHandler);
			BX.removeCustomEvent("CrmDupControllerAfterInitialize", this._dupControllerAfterInitializeHandler);
		},
		onDupControllerAfterInitialize: function(dupController)
		{
			if (dupController instanceof BX.CrmDupController)
			{
				if (this._dupControllerId === dupController.getId())
				{
					BX.removeCustomEvent("CrmDupControllerAfterInitialize", this._dupControllerAfterInitializeHandler);
					this.setDupController(dupController);
					BX.onCustomEvent("CrmRequisiteBankDetailBlockGetParams", [this._bankDetailBlockGetParamsCallback]);
				}
			}
		},
		setDupController: function(dupController)
		{
			var oldDupController = this._dupController;
			this._dupController = dupController;
			if (!oldDupController && this._dupController)
			{
				BX.addCustomEvent("CrmFormBankDetailBlockCreate", this._bankDetailBlockCreateHandler);
				BX.addCustomEvent("CrmFormSettingManagerSectionRemove", this._requisiteEditFormRemoveHandler);
				BX.addCustomEvent("CrmDupControllerDelete", this._dupControllerDeleteHandler);
				BX.addCustomEvent("CrmRequisitePopupFormManagerClosePopup", this._requisitePopupCloseHandler);
				BX.addCustomEvent("CrmFormBankDetailBlockRemove", this._bankDetailBlockRemoveHandler);
			}
			else if (!this._dupController)
			{
				BX.removeCustomEvent("CrmFormBankDetailBlockCreate", this._bankDetailBlockCreateHandler);
				BX.removeCustomEvent("CrmFormSettingManagerSectionRemove", this._requisiteEditFormRemoveHandler);
				BX.removeCustomEvent("CrmDupControllerDelete", this._dupControllerDeleteHandler);
				BX.removeCustomEvent("CrmRequisitePopupFormManagerClosePopup", this._requisitePopupCloseHandler);
				BX.removeCustomEvent("CrmFormBankDetailBlockRemove", this._bankDetailBlockRemoveHandler);
			}
		},
		onBankDetailBlockParams: function(params)
		{
			this.onBankDetailBlockCreate(params);
		},
		onBankDetailBlockCreate: function(params)
		{
			var i = 0;
			var formId = "";
			var container = null;
			var containerId = "";
			var bankDetailPseudoId = "";
			var countryId = 0;
			var fieldNameTemplate = "";
			var requisitePseudoId = "";
			var presetId = 0;
			var enableFieldMasquerading = false;
			var fieldSelectorTemplate = "";

			if (BX.type.isPlainObject(params))
			{
				if (BX.type.isNotEmptyString(params["formId"]))
				{
					formId = params["formId"];
					formId = formId.replace(/[^a-z0-9_]/ig, "");
				}
				if (BX.type.isNotEmptyString(params["containerId"]))
				{
					containerId = params["containerId"];
					container = BX(containerId);
				}


				if (params.hasOwnProperty("bankDetailPseudoId"))
				{
					if (BX.type.isNumber(params["bankDetailPseudoId"]))
						bankDetailPseudoId = params["bankDetailPseudoId"].toString();
					else if (BX.type.isNotEmptyString(params["bankDetailPseudoId"]))
						bankDetailPseudoId = params["bankDetailPseudoId"];
				}

				if (BX.type.isNotEmptyString(params["countryId"]) || BX.type.isNumber(params["countryId"]))
					countryId = parseInt(params["countryId"]);
				if (params["enableFieldMasquerading"] === true)
					enableFieldMasquerading = true;
				if (BX.type.isNotEmptyString(params["fieldNameTemplate"]))
				{
					fieldNameTemplate = params["fieldNameTemplate"];
					fieldSelectorTemplate = fieldNameTemplate.replace(/\[/g, "\\5b ").replace(/]/g, "\\5d ");
				}
			}
			if (BX.type.isNotEmptyString(formId))
			{
				var matches = formId.match(/^([a-z0-9_]+)_(n?\d+)_PID(\d+)$/i);
				if (BX.type.isArray(matches) && matches.length === 4)
				{
					requisitePseudoId = matches[2];
					presetId = parseInt(matches[3]);
				}
			}

			if (BX.type.isNotEmptyString(formId) && BX.type.isDomNode(container)
				&& BX.type.isNotEmptyString(bankDetailPseudoId) && countryId > 0
				&& BX.type.isNotEmptyString(requisitePseudoId) && presetId > 0)
			{
				var dupFieldsMap = this.getSetting("dupFieldsMap", {});
				var fieldsSelector = [], fieldSelector, elements, fieldName;
				var dupFieldCountryId, dupFields, selectorIndex;
				var useFieldTemplate = (enableFieldMasquerading && BX.type.isNotEmptyString(fieldSelectorTemplate));

				for (dupFieldCountryId in dupFieldsMap)
				{
					if (!dupFieldsMap.hasOwnProperty(dupFieldCountryId))
						continue;

					if (dupFieldsMap.hasOwnProperty(dupFieldCountryId)
						&& countryId === parseInt(dupFieldCountryId)
						&& BX.type.isArray(dupFieldsMap[dupFieldCountryId]))
					{
						dupFields = dupFieldsMap[dupFieldCountryId];
						selectorIndex = 0;
						for (i = 0; i < dupFields.length; i++)
						{
							fieldSelector = useFieldTemplate ?
								fieldSelectorTemplate.replace("#FIELD_NAME#", dupFields[i]) : dupFields[i];
							fieldsSelector[selectorIndex++] = "[name=" + fieldSelector + "]";
						}
					}
				}

				fieldsSelector = fieldsSelector.join(",");

				elements = container.querySelectorAll(fieldsSelector);
				for (i = 0; i < elements.length; i++)
				{
					fieldName = this.getFieldNameByElement(
						elements[i],
						(useFieldTemplate) ? fieldNameTemplate : ""
					);

					if (BX.type.isNotEmptyString(fieldName))
					{
						this.registerBankDetailField(
							formId, requisitePseudoId, presetId, bankDetailPseudoId, countryId, fieldName, elements[i]
						);
					}
				}
			}
		},
		onRequisiteEditFormRemove: function(formSettingManager)
		{
			var formId = "";

			if (formSettingManager !== null && typeof(formSettingManager) === "object")
			{
				formId = formSettingManager.getFormId();
				if (BX.type.isNotEmptyString(formId))
				{
					formId = formId.replace(/[^a-z0-9_]/ig, "");
					this.unregisterBankDetailFieldsByFormId(formId);
				}
			}
		},
		onDupControllerDelete: function(dupController)
		{
			if (this._dupController && this._dupController === dupController)
				BX.CrmDupControllerBankDetail.delete(this._id);
		},
		onRequisitePopupClose: function(requisitePopupFormManager)
		{
			var formId = "";

			if (requisitePopupFormManager !== null && typeof(requisitePopupFormManager) === "object")
			{
				formId = requisitePopupFormManager.getFormId();
				if (BX.type.isNotEmptyString(formId))
				{
					formId = formId.replace(/[^a-z0-9_]/ig, "");
					if (BX.type.isNotEmptyString(formId) && this._formFieldMap.hasOwnProperty(formId))
					{
						var dupController = null;
						if (this._dupController)
							dupController = this._dupController;
						BX.CrmDupControllerBankDetail.delete(this._id);
						if (dupController)
							BX.CrmDupController.delete(dupController.getId())
					}
				}
			}
		},
		onBankDetailBlockRemove: function(bankDetailBlock)
		{
			var formId = "";

			if (bankDetailBlock !== null && typeof(bankDetailBlock) === "object")
			{
				formId = bankDetailBlock.getFormId();
				if (BX.type.isNotEmptyString(formId))
				{
					formId = formId.replace(/[^a-z0-9_]/ig, "");
					var bankDetailPseudoId = bankDetailBlock.getPseudoId();
					if (BX.type.isNumber(bankDetailPseudoId))
						bankDetailPseudoId = bankDetailPseudoId.toString();
					if (BX.type.isNotEmptyString(bankDetailPseudoId))
						this.unregisterBankDetailFieldsByBankDetailId(formId, bankDetailPseudoId);
				}
			}
		},
		getFieldNameByElement: function(element, fieldNameTemplate)
		{
			var fieldName = "";

			if (BX.type.isElementNode(element))
			{
				fieldName = element.getAttribute("name");
				if (BX.type.isNotEmptyString(fieldNameTemplate) && BX.type.isNotEmptyString(fieldName))
				{
					var marker, postfix;
					var pos;
					marker = "#FIELD_NAME#";
					pos = fieldNameTemplate.indexOf(marker);
					if (pos >= 0)
					{
						if (pos < fieldName.length)
						{
							fieldName = fieldName.substr(pos);
						}

						pos = pos + marker.length;
						if (pos < fieldNameTemplate.length)
						{
							postfix = fieldNameTemplate.substr(pos);
							pos = fieldName.lastIndexOf(postfix);
							if (pos >= 0)
							{
								fieldName = fieldName.substr(0, pos);
							}
						}
					}
				}
			}

			return fieldName;
		},
		registerBankDetailField: function(formId, requisitePseudoId, presetId,
		                                  bankDetailPseudoId, countryId, fieldName, element)
		{
			var groupId = fieldName + "|" + countryId.toString();

			var group = this.getGroup(groupId);
			if (!group)
			{
				var dupFieldsDescr = this.getSetting("dupFieldsDescriptions", {});
				var title = groupId;
				if (dupFieldsDescr[fieldName] && dupFieldsDescr[fieldName][countryId])
				{
					title = dupFieldsDescr[fieldName][countryId];
				}
				group = this.addGroup(
					groupId,
					{
						"controller": this,
						"countryId": countryId,
						"fieldName": fieldName,
						"groupSummaryTitle": this.getSetting("groupSummaryTitle", "") + " \"" + title + "\""
					}
				);
			}

			if (group)
			{
				var fieldId = element.getAttribute("name");

				if (!this._formFieldMap.hasOwnProperty(formId))
					this._formFieldMap[formId] = {};
				if (!this._formFieldMap[formId].hasOwnProperty(groupId))
					this._formFieldMap[formId][groupId] = {};
				if (!this._formFieldMap[formId][groupId].hasOwnProperty(bankDetailPseudoId))
					this._formFieldMap[formId][groupId][bankDetailPseudoId] = {};

				this._formFieldMap[formId][groupId][bankDetailPseudoId][fieldId] =
					group.addField(
						BX.CrmDupCtrlBankDetailField.create(
							fieldId,
							element,
							null,
							{
								formId: formId,
								requisitePseudoId: requisitePseudoId,
								presetId: presetId,
								bankDetailPseudoId: bankDetailPseudoId,
								countryId: countryId,
								fieldName: fieldName
							}
						)
					);
			}
		},
		unregisterBankDetailFieldsByFormId: function(formId)
		{
			if (BX.type.isNotEmptyString(formId) && this._formFieldMap.hasOwnProperty(formId))
			{
				var groupId;
				var groups = this._formFieldMap[formId];
				var changedFieldsByGroup = {};

				delete this._formFieldMap[formId];

				for (groupId in groups)
				{
					if (groups.hasOwnProperty(groupId))
					{
						var bankDetailPseudoId;
						var bankDetailAreas = groups[groupId];
						for (bankDetailPseudoId in bankDetailAreas)
						{
							if (bankDetailAreas.hasOwnProperty(bankDetailPseudoId))
							{
								var fieldId;
								var fields = bankDetailAreas[bankDetailPseudoId];
								for (fieldId in fields)
								{
									if (fields.hasOwnProperty(fieldId))
									{
										if (this.unregisterBankDetailField(groupId, fieldId))
										{
											if (!changedFieldsByGroup.hasOwnProperty(groupId))
												changedFieldsByGroup[groupId] = {
													group: this.getGroup(groupId),
													fields: []
												};
											changedFieldsByGroup[groupId]["fields"].push(fields[fieldId]);
										}
									}
								}
							}
						}
					}
				}

				if (this._dupController)
					this._dupController.processGroupsChange(changedFieldsByGroup);
			}
		},
		unregisterBankDetailFieldsByBankDetailId: function(formId, bankDetailPseudoId)
		{
			if (BX.type.isNotEmptyString(formId) && this._formFieldMap.hasOwnProperty(formId)
				&& BX.type.isNotEmptyString(bankDetailPseudoId))
			{
				var groupId;
				var groups = this._formFieldMap[formId];
				var changedFieldsByGroup = {};

				for (groupId in groups)
				{
					if (groups.hasOwnProperty(groupId))
					{
						var bankDetailIndex;
						var bankDetailAreas = groups[groupId];
						for (bankDetailIndex in bankDetailAreas)
						{
							if (bankDetailIndex === bankDetailPseudoId
								&& bankDetailAreas.hasOwnProperty(bankDetailIndex))
							{
								var fieldId;
								var fields = bankDetailAreas[bankDetailIndex];
								for (fieldId in fields)
								{
									if (fields.hasOwnProperty(fieldId))
									{
										if (this.unregisterBankDetailField(groupId, fieldId))
										{
											if (!changedFieldsByGroup.hasOwnProperty(groupId))
												changedFieldsByGroup[groupId] = {
													group: this.getGroup(groupId),
													fields: []
												};
											changedFieldsByGroup[groupId]["fields"].push(fields[fieldId]);
										}
									}
								}

								delete this._formFieldMap[formId][groupId][bankDetailPseudoId];
								var property, group = this._formFieldMap[formId][groupId];
								var groupIsEmpty = true;
								for (property in group)
								{
									if (group.hasOwnProperty(property))
									{
										groupIsEmpty = false;
										break;
									}
								}
								if (groupIsEmpty)
									delete this._formFieldMap[formId][groupId];
								property = group = groupIsEmpty = null;
							}
						}
					}
				}

				if (this._dupController)
					this._dupController.processGroupsChange(changedFieldsByGroup);
			}
		},
		unregisterBankDetailField: function(groupId, fieldId)
		{
			var group = this.getGroup(groupId);
			if (group)
			{
				return group.removeField(fieldId);
			}

			return false;
		},
		addGroup: function(groupId, groupSettings)
		{
			var result = null;

			if (BX.type.isNotEmptyString(groupId))
			{
				var group = BX.CrmDupCtrlBankDetail.create(groupId, groupSettings);
				if (group)
				{
					this._groups[groupId] = group;
					if (this._dupController)
						this._dupController.addGroup(group);
					result = group;
				}
			}

			return result;
		},
		deleteGroup: function(groupId)
		{
			var result = false;

			if (BX.type.isNotEmptyString(groupId) && this._groups.hasOwnProperty(groupId))
			{
				if (this._dupController)
					this._dupController.deleteGroup(groupId);
				else
					this._groups[groupId].clearFields();
				delete this._groups[groupId];
				result = true;
			}

			return result;
		},
		getGroup: function(groupId)
		{
			if (BX.type.isNotEmptyString(groupId) && this._groups.hasOwnProperty(groupId))
			{
				return this._groups[groupId];
			}

			return null;
		}
	};
	BX.CrmDupControllerBankDetail.items = {};
	BX.CrmDupControllerBankDetail.getItem = function(id)
	{
		return this.items.hasOwnProperty(id) ? this.items[id] : null;
	};
	BX.CrmDupControllerBankDetail.create = function(id, settings)
	{
		var self = new BX.CrmDupControllerBankDetail();
		self.initialize(id, settings);
		BX.CrmDupControllerBankDetail.items[id] = self;
		return self;
	};
	BX.CrmDupControllerBankDetail.delete = function(id)
	{
		if (BX.CrmDupControllerBankDetail.items.hasOwnProperty(id))
		{
			var self = BX.CrmDupControllerBankDetail.items[id];
			self.destroy();
			delete BX.CrmDupControllerBankDetail.items[id];
		}
	};
}
if(typeof(BX.CrmDuplicateSummaryItem) === "undefined")
{
	BX.CrmDuplicateSummaryItem = function()
	{
		this._id = "";
		this._settings = {};
		this._groupId = "";
		this._controller = null;
		this._container = null;
	};
	BX.CrmDuplicateSummaryItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._controller = this.getSetting("controller", null);
			if(!this._controller)
			{
				throw "BX.CrmDuplicateListPopup. Parameter 'controller' is not found.";
			}

			this._container = this.getSetting("container", null);
			if(!this._controller)
			{
				throw "BX.CrmDuplicateSummaryItem. Parameter 'container' is not found.";
			}

			this._link = this.getSetting("link", null);
			if(!this._link)
			{
				throw "BX.CrmDuplicateSummaryItem. Parameter 'link' is not found.";
			}
			BX.bind(this._link, "click", BX.delegate(this._onLinkClick, this));

			this._groupId = this.getSetting("groupId", null);
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		_onLinkClick: function(e)
		{
			if(this._groupId !== "")
			{
				var popup = BX.CrmDuplicateListPopup.create(
					this._id,
					{
						controller: this._controller,
						groupId: this._groupId
					}
				);
				popup.show();
			}
		}
	};
	BX.CrmDuplicateSummaryItem.create = function(id, settings)
	{
		var self = new BX.CrmDuplicateSummaryItem();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDuplicateSummaryPopup) === "undefined")
{
	BX.CrmDuplicateSummaryPopup = function()
	{
		this._id = "";
		this._settings = {};
		this._controller = null;
		this._items = {};
		this._popup = null;
	};
	BX.CrmDuplicateSummaryPopup.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._controller = this.getSetting("controller", null);
			if(!this._controller)
			{
				throw "BX.CrmDuplicateSummaryPopup. Parameter 'controller' is not found.";
			}
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getId: function()
		{
			return this._id;
		},
		show: function()
		{
			if(this.isShown())
			{
				return;
			}

			var id = this.getId();
			if(BX.CrmDuplicateSummaryPopup.windows[id])
			{
				BX.CrmDuplicateSummaryPopup.windows[id].destroy();
			}

			var anchor = this.getSetting("anchor", null);
			var position = this.getSetting("position", "");
			if(position === "")
			{
				position = "left";
			}

			var anglePosition = "right";
			var offsetLeft = 0;
			var offsetTop = 0;
			if(position === "top")
			{
				anglePosition = "bottom";
			}
			else if(position === "bottom")
			{
				anglePosition = "top";
				offsetLeft = 40;
			}
			else if(position === "right")
			{
				anglePosition = "left";
			}

			this._popup = new BX.PopupWindow(
				id,
				anchor,
				{
					autoHide: false,
					draggable: true,
					closeByEsc: false,
					closeIcon :
					{
						marginRight: "-4px",
						marginTop: "-4px"
					},
					zIndex: 1,
					events:
					{
						onPopupClose: BX.delegate(this._onPopupClose, this),
						onPopupDestroy: BX.delegate(this._onPopupDestroy, this)
					},
					content: this._prepareContent(),
					className : "crm-tip-popup",
					angle: { position: anglePosition },
					offsetLeft: offsetLeft,
					offsetTop: offsetTop,
					lightShadow : true
				}
			);

			BX.CrmDuplicateSummaryPopup.windows[id] = this._popup;
			this._popup.show();

			var anchorPos, anglePos, offsetX, offsetY;
			if(position === "left")
			{
				anchorPos = BX.pos(anchor);
				anglePos = BX.pos(this._popup.angle.element);

				offsetX = this._popup.popupContainer.offsetWidth + anglePos.width + 5;
				offsetY = anchorPos.height + (anglePos.height + this._popup.angle.element.offsetTop) / 2;
				this._popup.move(-offsetX, -offsetY);
			}
			else if(position === "right")
			{
				anchorPos = BX.pos(anchor);
				anglePos = BX.pos(this._popup.angle.element);

				offsetX = anchorPos.width + anglePos.width;
				offsetY = anchorPos.height;
				this._popup.move(offsetX, -offsetY);
			}
		},
		close: function()
		{
			if(!(this._popup && this._popup.isShown()))
			{
				return;
			}

			this._popup.close();
		},
		isShown: function()
		{
			return this._popup && this._popup.isShown();
		},
		getMessage: function(name)
		{
			return BX.CrmDuplicateSummaryPopup.messages && BX.CrmDuplicateSummaryPopup.messages.hasOwnProperty(name) ? BX.CrmDuplicateSummaryPopup.messages[name] : "";
		},
		_prepareContent: function()
		{
			this._items = {};
			var infos = {};
			var data = this._controller.getDuplicateData();
			var groupId;
			for(groupId in data)
			{
				if(!data.hasOwnProperty(groupId))
				{
					continue;
				}

				var groupData = data[groupId];
				if(BX.type.isNotEmptyString(groupData["totalText"]))
				{
					infos[groupId] = { total: groupData["totalText"] };
				}
			}

			//crm-tip-popup-cont
			var wrapper = BX.create(
				"DIV",
				{
					attrs: { className: "crm-tip-popup-cont" }
				}
			);

			var titleIsAdded = false;
			for(groupId in infos)
			{
				if(!infos.hasOwnProperty(groupId))
				{
					continue;
				}

				var group = this._controller.getGroup(groupId);
				if(!group)
				{
					continue;
				}

				var itemLink = BX.create(
					"SPAN",
					{
						attrs: { className: "ui-link ui-link-dotted" },
						text: infos[groupId]["total"]
					}
				);

				var itemContainer =
					BX.create("DIV",
						{
							attrs: { className: "crm-tip-popup-item" }
						}
					);

				if(!titleIsAdded)
				{
					itemContainer.appendChild(
						BX.create("SPAN",
							{
								text: this.getMessage("title") + " "
							}
						)
					);
					titleIsAdded = true;
				}

				itemContainer.appendChild(itemLink);
				itemContainer.appendChild(
					BX.create("SPAN",
						{
							text: " " + group.getSummaryTitle()
						}
					)
				);
				wrapper.appendChild(itemContainer);

				this._items[groupId] = BX.CrmDuplicateSummaryItem.create(
					groupId,
					{
						controller: this._controller,
						container: itemContainer,
						link: itemLink,
						groupId: groupId
					}
				);
			}
			return wrapper;
		},
		_onPopupClose: function()
		{
			if(this._popup)
			{
				this._popup.destroy();
			}
		},
		_onPopupDestroy: function()
		{
			if(this._popup)
			{
				this._popup = null;
			}
		}
	};
	BX.CrmDuplicateSummaryPopup.windows = {};
	if(typeof(BX.CrmDuplicateSummaryPopup.messages) === "undefined")
	{
		BX.CrmDuplicateSummaryPopup.messages = {};
	}
	BX.CrmDuplicateSummaryPopup.create = function(id, settings)
	{
		var self = new BX.CrmDuplicateSummaryPopup();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDuplicateWarningDialog) === "undefined")
{
	BX.CrmDuplicateWarningDialog = function()
	{
		this._id = "";
		this._settings = {};
		this._controller = null;
		this._popup = null;
		this._contentWrapper = null;
	};
	BX.CrmDuplicateWarningDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._controller = this.getSetting("controller", null);
			if(!this._controller)
			{
				throw "BX.CrmDuplicateWarningDialog. Parameter 'controller' is not found.";
			}
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getId: function()
		{
			return this._id;
		},
		show: function()
		{
			if(this.isShown())
			{
				return;
			}

			var id = this.getId();
			if(BX.CrmDuplicateWarningDialog.windows[id])
			{
				BX.CrmDuplicateWarningDialog.windows[id].destroy();
			}

			var anchor = this.getSetting("anchor", null);
			this._popup = new BX.PopupWindow(
				id,
				anchor,
				{
					autoHide: false,
					draggable: true,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					closeIcon :
					{
						marginRight:"4px",
						marginTop:"9px"
					},
					zIndex: 3,
					titleBar: this.getMessage("title"),
					events:
					{
						onPopupShow: BX.delegate(this._onPopupShow, this),
						onPopupClose: BX.delegate(this._onPopupClose, this),
						onPopupDestroy: BX.delegate(this._onPopupDestroy, this)
					},
					content: this._prepareContent(),
					className : "crm-tip-popup",
					lightShadow : true,
					buttons: [
						new BX.PopupWindowButton(
							{
								text : this.getMessage("acceptButtonTitle"),
								className : "popup-window-button-create",
								events:
								{
									click: BX.delegate(this._onAcceptButtonClick, this)
								}
							}
						),
						new BX.PopupWindowButtonLink(
							{
								text : this.getMessage("cancelButtonTitle"),
								className : "webform-button-link-cancel",
								events:
								{
									click: BX.delegate(this._onCancelButtonClick, this)
								}
							}
						)
					]
				}
			);

			BX.CrmDuplicateWarningDialog.windows[id] = this._popup;
			this._popup.show();
			this._contentWrapper.tabIndex = "1";
			this._contentWrapper.focus();
		},
		close: function()
		{
			if(!(this._popup && this._popup.isShown()))
			{
				return;
			}

			this._popup.close();
		},
		isShown: function()
		{
			return this._popup && this._popup.isShown();
		},
		getMessage: function(name)
		{
			return BX.CrmDuplicateWarningDialog.messages && BX.CrmDuplicateWarningDialog.messages.hasOwnProperty(name) ? BX.CrmDuplicateWarningDialog.messages[name] : "";
		},
		_prepareContent: function()
		{
			this._contentWrapper = BX.CrmDuplicateRenderer.prepareListContent(this._controller.getDuplicateData());
			return this._contentWrapper;
		},
		_onCancelButtonClick: function()
		{
			var handler = this.getSetting("onCancel", null);
			if(BX.type.isFunction(handler))
			{
				handler(this);
			}
		},
		_onAcceptButtonClick: function()
		{
			var handler = this.getSetting("onAccept", null);
			if(BX.type.isFunction(handler))
			{
				handler(this);
			}
		},
		_onPopupShow: function()
		{
			if(!this._contentWrapper)
			{
				return;
			}

			BX.bind(this._contentWrapper, "keyup", BX.delegate(this._onKeyUp, this))
		},
		_onPopupClose: function()
		{
			var handler = this.getSetting("onClose", null);
			if(BX.type.isFunction(handler))
			{
				handler(this);
			}

			if(this._popup)
			{
				this._popup.destroy();
			}
		},
		_onPopupDestroy: function()
		{
			if(this._popup)
			{
				this._popup = null;
			}
		},
		_onKeyUp: function(e)
		{
			var c = e.keyCode;
			if(c === 13)
			{
				var handler = this.getSetting("onAccept", null);
				if(BX.type.isFunction(handler))
				{
					handler(this);
				}
			}
		}
	};
	BX.CrmDuplicateWarningDialog.windows = {};
	if(typeof(BX.CrmDuplicateWarningDialog.messages) === "undefined")
	{
		BX.CrmDuplicateWarningDialog.messages = {};
	}
	BX.CrmDuplicateWarningDialog.create = function(id, settings)
	{
		var self = new BX.CrmDuplicateWarningDialog();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDuplicateListPopup) === "undefined")
{
	BX.CrmDuplicateListPopup = function()
	{
		this._id = "";
		this._settings = {};
		this._controller = null;
		this._groupId = "";
		this._popup = null;
		this._contentWrapper = null;
	};
	BX.CrmDuplicateListPopup.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._controller = this.getSetting("controller", null);
			if(!this._controller)
			{
				throw "BX.CrmDuplicateListPopup. Parameter 'controller' is not found.";
			}

			this._groupId = this.getSetting("groupId", null);
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getId: function()
		{
			return this._id;
		},
		show: function()
		{
			if(this.isShown())
			{
				return;
			}

			var id = this.getId();
			if(BX.CrmDuplicateListPopup.windows[id])
			{
				BX.CrmDuplicateListPopup.windows[id].destroy();
			}

			var anchor = this.getSetting("anchor", null);
			this._popup = new BX.PopupWindow(
				id,
				anchor,
				{
					autoHide: true,
					draggable: false,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					closeIcon :
					{
						marginRight:"-4px",
						marginTop:"-4px"
					},
					zIndex: 2,
					events:
					{
						onPopupShow: BX.delegate(this._onPopupShow, this),
						onPopupClose: BX.delegate(this._onPopupClose, this),
						onPopupDestroy: BX.delegate(this._onPopupDestroy, this)
					},
					content: this._prepareContent(),
					lightShadow : true,
					className : "crm-tip-popup"
				}
			);

			BX.CrmDuplicateListPopup.windows[id] = this._popup;
			this._popup.show();
		},
		close: function()
		{
			if(!(this._popup && this._popup.isShown()))
			{
				return;
			}

			this._popup.close();
		},
		isShown: function()
		{
			return this._popup && this._popup.isShown();
		},
		getMessage: function(name)
		{
			return BX.CrmDuplicateListPopup.messages && BX.CrmDuplicateListPopup.messages.hasOwnProperty(name) ? BX.CrmDuplicateListPopup.messages[name] : "";
		},
		_prepareContent: function()
		{
			this._contentWrapper = BX.CrmDuplicateRenderer.prepareListContent(
				this._controller.getDuplicateData(),
				{
					groupId: this._groupId,
					classes: [ "crm-cont-info-popup-light" ]
				}
			);
			return this._contentWrapper;
		},
		_onPopupShow: function()
		{
		},
		_onPopupClose: function()
		{
			var handler = this.getSetting("onClose", null);
			if(BX.type.isFunction(handler))
			{
				handler(this);
			}

			if(this._popup)
			{
				this._popup.destroy();
			}
		},
		_onPopupDestroy: function()
		{
			if(this._popup)
			{
				this._popup = null;
			}
		}
	};
	BX.CrmDuplicateListPopup.windows = {};
	if(typeof(BX.CrmDuplicateListPopup.messages) == "undefined")
	{
		BX.CrmDuplicateListPopup.messages = {};
	}
	BX.CrmDuplicateListPopup.create = function(id, settings)
	{
		var self = new BX.CrmDuplicateListPopup();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDuplicateRenderer) === "undefined")
{
	BX.CrmDuplicateRenderer = function()
	{
	};
	BX.CrmDuplicateRenderer._onCommunicationBlockClick = function(e)
	{
		var element = null;
		if(e)
		{
			if(e.target)
			{
				element = e.target;
			}
			else if(e.srcElement)
			{
				element = e.srcElement;
			}
		}

		if(BX.type.isElementNode(element))
		{
			if(BX.hasClass(element, "crm-info-popup-block-main"))
			{
				BX.removeClass(element, "crm-info-popup-block-main");
			}

			var wrapper = BX.findParent(element, { className:"crm-info-popup-block" });
			if(BX.type.isElementNode(wrapper) && !BX.hasClass(wrapper, "crm-info-popup-block-open"))
			{
				BX.addClass(wrapper, "crm-info-popup-block-open");
			}

			BX.unbind(element, "click", BX.CrmDuplicateRenderer._onCommunicationBlockClickHandler);
		}
	};
	BX.CrmDuplicateRenderer._onCommunicationBlockClickHandler = BX.delegate(BX.CrmDuplicateRenderer._onCommunicationBlockClick, BX.CrmDuplicateRenderer);
	BX.CrmDuplicateRenderer._prepareCommunications = function(comms)
	{
		if(!BX.type.isArray(comms) || comms.length === 0)
		{
			return null;
		}

		var qty = comms.length;
		if(qty === 1)
		{
			return BX.util.htmlspecialchars(comms[0]);
		}

		var wrapper = BX.create(
			"DIV",
			{
				attrs: { className: "crm-info-popup-block" }
			}
		);

		var first = BX.create(
			"DIV",
			{
				attrs: { className: "crm-info-popup-block-main" },
				text: comms[0]
			}
		);

		wrapper.appendChild(first);
		BX.bind(first, "click", this._onCommunicationBlockClickHandler);

		var innerWrapper = BX.create(
			"DIV",
			{
				attrs: { className: "crm-info-popup-block-inner" }
			}
		);

		for(var i = 1; i < qty; i++)
		{
			innerWrapper.appendChild(
				BX.create(
					"DIV",
					{
						text: comms[i]
					}
				)
			);
		}
		wrapper.appendChild(innerWrapper);
		return wrapper;
	};
	BX.CrmDuplicateRenderer.prepareListContent = function(data, params)
	{
		if(!params)
		{
			params = {};
		}
		var targetGroupId = BX.type.isNotEmptyString(params["groupId"]) ? params["groupId"] : "";

		var infoByType = {};
		for(var groupId in data)
		{
			if(!data.hasOwnProperty(groupId))
			{
				continue;
			}

			if(targetGroupId !== "" && targetGroupId !== groupId)
			{
				continue;
			}

			var groupData = data[groupId];
			var items = BX.type.isArray(groupData["items"]) ? groupData["items"] : [];
			var itemQty = items.length;
			for(var i = 0; i < itemQty; i++)
			{
				var item = items[i];
				var entities = BX.type.isArray(item["ENTITIES"]) ? item["ENTITIES"] : [];
				var entityQty = entities.length;
				for(var j = 0; j < entityQty; j++)
				{
					var entity = entities[j];
					var entityTypeID = BX.type.isNotEmptyString(entity["ENTITY_TYPE_ID"]) ? parseInt(entity["ENTITY_TYPE_ID"]) : 0;
					if(!BX.CrmEntityType.isDefined(entityTypeID))
					{
						continue;
					}

					var entityTypeName = BX.CrmEntityType.resolveName(entityTypeID);
					if(typeof(infoByType[entityTypeName]) === "undefined")
					{
						infoByType[entityTypeName] = [entity];
					}
					else
					{
						var entityID = BX.type.isNotEmptyString(entity["ENTITY_ID"]) ? parseInt(entity["ENTITY_ID"]) : 0;
						var isExists = false;
						for(var n = 0; n < infoByType[entityTypeName].length; n++)
						{
							var curEntity = infoByType[entityTypeName][n];
							var curEntityID = BX.type.isNotEmptyString(curEntity["ENTITY_ID"]) ? parseInt(curEntity["ENTITY_ID"]) : 0;
							if(curEntityID === entityID)
							{
								isExists = true;
								break;
							}
						}

						if(!isExists)
						{
							infoByType[entityTypeName].push(entity);
						}
					}
				}
			}
		}

		var wrapper = BX.create(
			"DIV",
			{
				attrs: { className: "crm-cont-info-popup"}
			}
		);

		var wrapperClasses = typeof(params["classes"]) !== "undefined" ? params["classes"] : null;
		if(BX.type.isArray(wrapperClasses))
		{
			for(var m = 0; m < wrapperClasses.length; m++)
			{
				BX.addClass(wrapper, wrapperClasses[m]);
			}
		}

		var table = BX.create(
			"TABLE",
			{
				attrs: { className: "crm-cont-info-table" }
			}
		);
		wrapper.appendChild(table);

		var hasNotCompleted = false;
		var hasCompleted = false;

		for(var key in infoByType)
		{
			if(!infoByType.hasOwnProperty(key))
			{
				continue;
			}

			var ttleRow = table.insertRow(-1);
			ttleRow.className = "crm-cont-info-table-title";
			var ttlCell = ttleRow.insertCell(-1);
			ttlCell.colspan = 4;
			ttlCell.innerHTML = BX.util.htmlspecialchars(BX.CrmEntityType.categoryCaptions[key]);

			var infos = infoByType[key];
			var infoQty = infos.length;
			for(var k = 0; k < infoQty; k++)
			{
				var info = infos[k];
				var infoRow = table.insertRow(-1);
				var captionRow = infoRow.insertCell(-1);

				if(BX.type.isNotEmptyString(info["URL"]))
				{
					captionRow.appendChild(
						BX.create(
							"A",
							{
								attrs: { href: info["URL"], target: "_blank" },
								text: BX.type.isNotEmptyString(info["TITLE"]) ? info["TITLE"] : "[Untitled]"
							}
						)
					);
				}
				else
				{
					captionRow.innerHTML = BX.type.isNotEmptyString(info["TITLE"])
						? BX.util.htmlspecialchars(info["TITLE"]) : "[Untitled]";
				}

				//Emails
				var hasEmails = false;
				var emailCell = infoRow.insertCell(-1);
				var emails = BX.type.isArray(info["EMAIL"]) ? this._prepareCommunications(info["EMAIL"]) : null;
				if(BX.type.isElementNode(emails))
				{
					emailCell.appendChild(emails);
					hasEmails = true;
				}
				else if(BX.type.isNotEmptyString(emails))
				{
					emailCell.innerHTML = emails;
					hasEmails = true;
				}
				else if(!hasNotCompleted)
				{
					hasNotCompleted = true;
				}

				//Phones
				var hasPhones = false;
				var phoneCell = infoRow.insertCell(-1);
				phoneCell.className = "crm-cont-info-table-tel";
				var phones = BX.type.isArray(info["PHONE"]) ? this._prepareCommunications(info["PHONE"]) : null;
				if(BX.type.isElementNode(phones))
				{
					phoneCell.appendChild(phones);
					hasPhones = true;
				}
				else if(BX.type.isNotEmptyString(phones))
				{
					phoneCell.innerHTML = phones;
					hasPhones = true;
				}
				else if(!hasNotCompleted)
				{
					hasNotCompleted = true;
				}

				if(hasEmails && hasPhones && !hasCompleted)
				{
					hasCompleted = true;
				}

				var responsibleCell = infoRow.insertCell(-1);
				var responsibleID = BX.type.isNotEmptyString(info["RESPONSIBLE_ID"]) ? parseInt(info["RESPONSIBLE_ID"]) : 0;
				if(responsibleID > 0)
				{
					var userWrapper = BX.create(
						"DIV",
						{
							attrs: { className: "crm-info-popup-user" }
						}
					);
					responsibleCell.appendChild(userWrapper);
					userWrapper.className = "crm-info-popup-user";
					userWrapper.setAttribute("data-userid", responsibleID.toString());
					userWrapper.setAttribute("bx-tooltip-user-id", responsibleID.toString());

					var styles = {};
					if(BX.type.isNotEmptyString(info["RESPONSIBLE_PHOTO_URL"]))
					{
						styles["background"] = "url(" + info["RESPONSIBLE_PHOTO_URL"] + ") repeat scroll center center";
					}

					userWrapper.appendChild(
						BX.create(
							"SPAN",
							{
								attrs: { className: "crm-info-popup-user-img" },
								style: styles
							}
						)
					);

					userWrapper.appendChild(
						BX.create(
							"A",
							{
								attrs:
								{
									target: "_blank",
									href: BX.type.isNotEmptyString(info["RESPONSIBLE_URL"]) ? info["RESPONSIBLE_URL"] : "#",
									className: "crm-info-popup-user-name"
								},
								text: BX.type.isNotEmptyString(info["RESPONSIBLE_FULL_NAME"]) ? info["RESPONSIBLE_FULL_NAME"] : ("[" + responsibleID + "]")
							}
						)
					);
				}
			}
		}

		if(!hasCompleted)
		{
			BX.addClass(table, "crm-cont-info-table-empty");
		}
		return wrapper;
	}
}

if(typeof(BX.NotificationPopup) === "undefined")
{
	BX.NotificationPopup = function()
	{
		this._id = "";
		this._settings = {};
		this._popup = null;
		this._contentWrapper = null;
		this._title = "";
		this._timeout = 3000;
		this._timeoutId = null;
		this._messages = [];
	};
	BX.NotificationPopup.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._messages = this.getSetting("messages", null);
			if(!BX.type.isArray(this._messages) || this._messages.length === 0)
			{
				throw "BX.NotificationPopup. Parameter 'messages' is not defined or empty.";
			}

			var timeout = parseInt(this.getSetting("timeout", 3000));
			if(isNaN(timeout) || timeout <= 0)
			{
				timeout = 3000;
			}
			this._timeout = timeout;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getId: function()
		{
			return this._id;
		},
		show: function()
		{
			if(this.isShown())
			{
				return;
			}

			var id = this.getId();
			if(BX.NotificationPopup.windows[id])
			{
				BX.NotificationPopup.windows[id].destroy();
			}

			this._popup = new BX.PopupWindow(
				id,
				null,
				{
					autoHide: true,
					draggable: false,
					zIndex: 10200,
					className: "bx-notification-popup",
					closeByEsc: true,
					events:
					{
						onPopupClose: BX.delegate(this.onPopupClose, this),
						onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
					},
					content: this.prepareContent()
				}
			);

			BX.NotificationPopup.windows[id] = this._popup;
			this._popup.show();

			this._timeoutId = setTimeout(BX.delegate(this.close, this), this._timeout);

			BX.bind(this._contentWrapper, "mouseover", BX.delegate(this._onMouseOver, this));
			BX.bind(this._contentWrapper, "mouseout", BX.delegate(this._onMouseOut, this));
		},
		_onMouseOver: function(e)
		{
			if(this._timeoutId !== null)
			{
				clearTimeout(this._timeoutId);
			}
		},
		_onMouseOut: function(e)
		{
			this._timeoutId = setTimeout(BX.delegate(this.close, this), this._timeout);
		},
		close: function()
		{
			if(!(this._popup && this._popup.isShown()))
			{
				return;
			}

			this._popup.close();
		},
		isShown: function()
		{
			return this._popup && this._popup.isShown();
		},
		prepareContent: function()
		{
			this._contentWrapper = BX.create("DIV", { attrs: { className: "bx-notification" } });

			var align = this.getSetting("align", "");
			if(align === "justify")
			{
				BX.addClass(this._contentWrapper, "bx-notification-content-justify");
			}

			this._contentWrapper.appendChild(BX.create("SPAN", { attrs: { className: "bx-notification-aligner" } }));
			for(var i = 0; i < this._messages.length; i++)
			{
				this._contentWrapper.appendChild(
					BX.create("SPAN", { props: { className: "bx-notification-text" }, text: this._messages[i] })
				);
			}
			this._contentWrapper.appendChild(BX.create("DIV", { props: { className: "bx-notification-footer" } }));
			return this._contentWrapper;
		},
		onPopupClose: function()
		{
			if(this._popup)
			{
				this._popup.destroy();
			}
		},
		onPopupDestroy: function()
		{
			if(this._popup)
			{
				this._popup = null;
			}

			if(this._contentWrapper)
			{
				this._contentWrapper = null;
			}
		}
	};
	BX.NotificationPopup.windows = {};
	BX.NotificationPopup.create = function(id, settings)
	{
		var self = new BX.NotificationPopup();
		self.initialize(id, settings);
		return self;
	};
	BX.NotificationPopup.show = function(id, settings)
	{
		this.create(id, settings).show();
	}
}

if(typeof(BX.CrmInterfaceMode) === "undefined")
{
	BX.CrmInterfaceMode = { edit: 1, view: 2 };
}

if(typeof(BX.GridAjaxLoader) === "undefined")
{
	BX.GridAjaxLoader = function()
	{
		this._id = "";
		this._settings = {};
		this._url = "";
		this._method = "";
		this._data = {};
		this._dataType = "html";
		this._ajaxId = "";
		this._ajaxInsertHandler = BX.delegate(this._onAjaxInsert, this);
	};

	BX.GridAjaxLoader.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._url = this.getSetting("url", "");
			this._method = this.getSetting("method", "GET");
			this._data = this.getSetting("data", {});
			this._dataType = this.getSetting("dataType", "html");
			this._ajaxId = this.getSetting("ajaxId", "");
			this._urlAjaxIdRegex = /bxajaxid\s*=\s*([a-z0-9]+)/i;
			//Page number expression : first param is url-parameter name and second param is page number.
			this._urlPageNumRegexes =
				[
					/(PAGEN_[0-9]+)\s*=\s*([0-9]+)/i, //Standard page navigation
					/(page)\s*=\s*(-?[0-9]+)/i //Optimized CRM page navigation
				];

			BX.addCustomEvent(window, "onAjaxInsertToNode", this._ajaxInsertHandler);
		},
		release: function()
		{
			BX.removeCustomEvent(window, "onAjaxInsertToNode", this._ajaxInsertHandler);
		},
		getSetting: function(name, defaultvalue)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultvalue;
		},
		getId: function()
		{
			return this._id;
		},
		reload: function(url, callback)
		{
			if(!BX.type.isNotEmptyString(url))
			{
				url = this._url;
			}
			url = BX.util.add_url_param(url, { "bxajaxid": this._ajaxId });

			var cfg = { url: url, dataType: this._dataType };
			if(this._method === "POST")
			{
				cfg["method"] = "POST";
				cfg["data"] = this._data;
			}
			else
			{
				cfg["method"] = "GET";
			}

			if(BX.type.isFunction(callback))
			{
				cfg["onsuccess"] = callback;
			}

			BX.ajax(cfg);
		},
		loadPage: function(pageParam, pageNumber)
		{
			var urlParams = { "bxajaxid": this._ajaxId };
			urlParams[pageParam] = pageNumber;
			var cfg =
				{
					url: BX.util.add_url_param(this._url, urlParams),
					dataType: this._dataType
				};

			if(this._method === "POST")
			{
				cfg["method"] = "POST";
				cfg["data"] = this._data;
			}
			else
			{
				cfg["method"] = "GET";
			}

			cfg["onsuccess"] = BX.delegate(this._onPageLoadSuccess, this);
			BX.ajax(cfg);
		},
		setupFormAction: function(form, url)
		{
			if(!BX.type.isNotEmptyString(url))
			{
				url = this._url;
			}

			url = BX.util.add_url_param(url, { "bxajaxid": this._ajaxId });
			form.action = url;
		},
		setupForm: function(form, url)
		{
			this.setupFormAction(form, url);
			BX.util.addObjectToForm(this._data, form);
		},
		_onAjaxInsert: function(params)
		{
			if(typeof(params.eventArgs) === "undefined")
			{
				return;
			}

			var m = this._urlAjaxIdRegex.exec(params.url);
			if(BX.type.isArray(m) && m.length > 1 && m[1] === this._ajaxId)
			{
				var l = this._urlPageNumRegexes.length;
				for(var i = 0; i < l; i++)
				{
					m = this._urlPageNumRegexes[i].exec(params.url);
					if(!(BX.type.isArray(m) && m.length > 2))
					{
						continue;
					}

					this.loadPage(m[1], m[2]);

					params.eventArgs.cancel = true;
					return;
				}
			}
		},
		_onPageLoadSuccess: function(data)
		{
			var node = BX('comp_' + this._ajaxId);
			if(node)
			{
				node.innerHTML = data;
			}
		}
	};

	BX.GridAjaxLoader.items = {};
	BX.GridAjaxLoader.create = function(id, settings)
	{
		var self = new BX.GridAjaxLoader();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
	BX.GridAjaxLoader.remove = function(id)
	{
		if(typeof(this.items[id]) === "undefined")
		{
			return;
		}

		this.items[id].release();
		delete this.items[id];
	};
}

if(typeof(BX.AddressFormatSelector) === "undefined")
{
	BX.AddressFormatSelector = function()
	{
		this._id = "";
		this._settings = {};
		this._controlPrefix = "";
		this._descrContainer = null;
		this._typeInfos = {};
	};

	BX.AddressFormatSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._controlPrefix = this.getSetting("controlPrefix");
			this._typeInfos = this.getSetting("typeInfos", {});
			for(var key in this._typeInfos)
			{
				if(!this._typeInfos.hasOwnProperty(key))
				{
					continue;
				}

				var element = BX(this._controlPrefix + key.toLowerCase());
				if(element)
				{
					BX.bind(element, "change", BX.delegate(this._onControlChange, this));
				}
			}
			this._descrContainer = BX(this.getSetting("descrContainerId"));
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultvalue)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultvalue;
		},
		_onControlChange: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			var target = BX.getEventTarget(e);
			if(target && BX.type.isNotEmptyString(this._typeInfos[target.value]) && this._descrContainer)
			{
				this._descrContainer.innerHTML = this._typeInfos[target.value];
			}
		}
	};

	BX.AddressFormatSelector.create = function(id, settings)
	{
		var self = new BX.AddressFormatSelector();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmLongRunningProcessManager) === "undefined")
{
	BX.CrmLongRunningProcessManager = function()
	{
		this._id = "";
		this._settings = {};
		this._serviceUrl = "";
		this._actionName = "";
		this._dialog = null;
	};
	BX.CrmLongRunningProcessManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_lrp_mgr_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.CrmLongRunningProcessManager. Could not find 'serviceUrl' parameter in settings.";
			}

			this._actionName = this.getSetting("actionName", "");
			if(!BX.type.isNotEmptyString(this._actionName))
			{
				throw "BX.CrmLongRunningProcessManager. Could not find 'actionName' parameter in settings.";
			}
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getMessage: function(name)
		{
			var m = BX.CrmLongRunningProcessManager.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getServiceUrl: function()
		{
			return this._serviceUrl;
		},
		getActionName: function()
		{
			return this._actionName;
		},
		run: function()
		{
			if(!this._dialog)
			{
				var title = this.getSetting("dialogTitle", this.getMessage("dialogTitle"));
				var summary = this.getSetting("dialogSummary", this.getMessage("dialogSummary"));
				this._dialog = BX.CrmLongRunningProcessDialog.create(
					this.getId(),
					{
						serviceUrl: this.getServiceUrl(),
						action: this.getActionName(),
						title: title,
						summary: summary
					}
				);
			}

			BX.addCustomEvent(this._dialog, "ON_STATE_CHANGE", BX.delegate(this._onProcessStateChange, this));
			this._dialog.show();
		},
		_onProcessStateChange: function(sender)
		{
			if(sender === this._dialog)
			{
				if(this._dialog.getState() === BX.CrmLongRunningProcessState.completed)
				{
					BX.onCustomEvent(this, "ON_LONG_RUNNING_PROCESS_COMPLETE", [this]);
				}
			}
		}
	};
	if(typeof(BX.CrmLongRunningProcessManager.messages) == "undefined")
	{
		BX.CrmLongRunningProcessManager.messages = {};
	}
	BX.CrmLongRunningProcessManager.items = {};
	BX.CrmLongRunningProcessManager.create = function(id, settings)
	{
		var self = new BX.CrmLongRunningProcessManager();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.CrmLongRunningProcessPanel) === "undefined")
{
	BX.CrmLongRunningProcessPanel = function()
	{
		this._id = "";
		this._settings = {};
		this._prefix = "";
		this._hasLayout = false;
		this._active = false;
		this._container = null;
		this._wrapper = null;
		this._link = null;
		this._manager = null;
		this._clickHandler = BX.delegate(this.onClick, this);
		this._processCompleteHandler = BX.delegate(this.onProcessComplete, this);
	};

	BX.CrmLongRunningProcessPanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._container = BX(this.getSetting("containerId"));
			if(!this._container)
			{
				throw "CrmLongRunningProcessPanel: Could not find container.";
			}

			this._active = !!this.getSetting("active", false);
			this._prefix = this.getSetting("prefix");
			this._message = this.getSetting("message");

			this._manager = BX.CrmLongRunningProcessManager.create(this._id, this.getSetting("manager"));
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._wrapper = BX.create("DIV", { props: { className: "crm-view-message" } });
			this._container.appendChild(this._wrapper);

			if(!this._active)
			{
				this._wrapper.style.display = "none";
			}

			var linkId = (this._prefix !== "" ? this._prefix : this._id) + "_link";
			var html = this._message.replace(/#ID#/gi, linkId).replace(/#URL#/gi, "#");
			this._wrapper.appendChild(BX.create("SPAN", { html: html }));

			this._link = BX(linkId);
			if(this._link)
			{
				BX.bind(this._link, "click", this._clickHandler);
			}

			this._hasLayout = true;
		},
		cleanLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._link)
			{
				BX.unbind(this._link, "click", this._clickHandler);
				this._link = null;
			}

			BX.cleanNode(this._wrapper, true);
			this._wrapper = null;

			this._hasLayout = false;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		isActive: function()
		{
			return this._active;
		},
		setActive: function(active)
		{
			active = !!active;
			if(this._active === active)
			{
				return;
			}

			this._active = active;
			this._wrapper.style.display = active ? "" : "none";
		},
		onClick: function(e)
		{
			BX.addCustomEvent(this._manager, "ON_LONG_RUNNING_PROCESS_COMPLETE", this._processCompleteHandler);
			this._manager.run();
			return BX.PreventDefault(e);
		},
		onProcessComplete: function(mgr)
		{
			if(mgr !== this._manager)
			{
				return;
			}

			BX.removeCustomEvent(this._manager, "ON_LONG_RUNNING_PROCESS_COMPLETE", this._processCompleteHandler);
			this.setActive(false);
		}
	};

	BX.CrmLongRunningProcessPanel.create = function(id, settings)
	{
		var self = new BX.CrmLongRunningProcessPanel();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.InterfaceFilterFieldInfoProvider) === "undefined")
{
	BX.InterfaceFilterFieldInfoProvider = function()
	{
		this._id = "";
		this._settings = {};
		this._infos = null;
		this._setFildsHandler = BX.delegate(this.onSetFilterFields, this);
		this._getFildsHandler = BX.delegate(this.onGetFilterFields, this);
	};

	BX.InterfaceFilterFieldInfoProvider.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._infos = this.getSetting("infos", null);

			BX.onCustomEvent(window, "InterfaceFilterFieldInfoProviderCreate", [this]);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != "undefined" ? this._settings[name] : defaultval;
		},
		registerFilter: function(filter)
		{
			BX.addCustomEvent(filter, "AFTER_SET_FILTER_FIELDS", this._setFildsHandler);
			BX.addCustomEvent(filter, "AFTER_GET_FILTER_FIELDS", this._getFildsHandler);
		},
		getFieldInfos: function()
		{
			return this._infos;
		},
		onSetFilterFields: function(sender, form, fields)
		{
			var infos = this._infos;
			if(!BX.type.isArray(infos))
			{
				return;
			}

			var isSettingsContext = form.name.indexOf('flt_settings') === 0;

			var count = infos.length;
			var paramName = '';
			for(var i = 0; i < count; i++)
			{
				var info = infos[i];
				var id = BX.type.isNotEmptyString(info['id']) ? info['id'] : '';
				var type = BX.type.isNotEmptyString(info['typeName']) ? info['typeName'].toUpperCase() : '';
				var params = info['params'] ? info['params'] : {};

				if(type === 'USER')
				{
					var data = params['data'] ? params['data'] : {};
					this.setElementByFilter(
						data[isSettingsContext ? 'settingsElementId' : 'elementId'],
						data['paramName'],
						fields
					);

					var search = params['search'] ? params['search'] : {};
					this.setElementByFilter(
						search[isSettingsContext ? 'settingsElementId' : 'elementId'],
						search['paramName'],
						fields
					);
				}
			}
		},
		onGetFilterFields: function(sender, form, fields)
		{
			var infos = this._infos;
			if(!BX.type.isArray(infos))
			{
				return;
			}

			var isSettingsContext = form.name.indexOf('flt_settings') === 0;
			var count = infos.length;
			for(var i = 0; i < count; i++)
			{
				var info = infos[i];
				var id = BX.type.isNotEmptyString(info['id']) ? info['id'] : '';
				var type = BX.type.isNotEmptyString(info['typeName']) ? info['typeName'].toUpperCase() : '';
				var params = info['params'] ? info['params'] : {};

				if(type === 'USER')
				{
					var data = params['data'] ? params['data'] : {};
					this.setFilterByElement(
						data[isSettingsContext ? 'settingsElementId' : 'elementId'],
						data['paramName'],
						fields
					);

					var search = params['search'] ? params['search'] : {};
					this.setFilterByElement(
						search[isSettingsContext ? 'settingsElementId' : 'elementId'],
						search['paramName'],
						fields
					);
				}
			}
		},
		setElementByFilter: function(elementId, paramName, filter)
		{
			var element = BX.type.isNotEmptyString(elementId) ? BX(elementId) : null;
			if(BX.type.isElementNode(element))
			{
				element.value = BX.type.isNotEmptyString(paramName) && filter[paramName] ? filter[paramName] : '';
			}
		},
		setFilterByElement: function(elementId, paramName, filter)
		{
			var element = BX.type.isNotEmptyString(elementId) ? BX(elementId) : null;
			if(BX.type.isElementNode(element) && BX.type.isNotEmptyString(paramName))
			{
				filter[paramName] = element.value;
			}
		}
	};
	BX.InterfaceFilterFieldInfoProvider.items = {};
	BX.InterfaceFilterFieldInfoProvider.create = function(id, settings)
	{
		var self = new BX.InterfaceFilterFieldInfoProvider();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.CrmConversionSchemeSelector) === "undefined")
{
	BX.CrmConversionSchemeSelector = function()
	{
		this._id = "";
		this._settings = {};
		this._entityId = 0;
		this._scheme = "";

		this._isMenuShown = false;
		this._menuId = "";
		this._container = null;
		this._containerClickHandler = BX.delegate(this.onContainerClick, this);
		this._label = null;
		this._button = null;
		this._buttonClickHandler = BX.delegate(this.onButtonClick, this);
		this._menuIiemClickHandler = BX.delegate(this.onMenuItemClick, this);
		this._menuCloseHandler = BX.delegate(this.onMenuClose, this);
		this._hint = null;
	};
	BX.CrmConversionSchemeSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._entityId = parseInt(this.getSetting("entityId", 0));
			if(!BX.type.isNumber(this._entityId))
			{
				throw "BX.CrmConversionSchemeSelector: entity id is not found!";
			}

			this._scheme = this.getSetting("scheme", "");

			this._container = BX(this.getSetting("containerId"));
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.CrmConversionSchemeSelector: container element is not found!";
			}
			BX.bind(this._container, "click", this._containerClickHandler);

			this._menuId = 'crm_menu_popup_' + this._id.toLowerCase();
			this._button = BX(this.getSetting("buttonId"));
			if(!BX.type.isElementNode(this._button))
			{
				throw "BX.CrmConversionSchemeSelector: button element is not found!";
			}
			BX.bind(this._button, "click", this._buttonClickHandler);

			var labelId = this.getSetting("labelId", "");
			if(BX.type.isNotEmptyString(labelId))
			{
				this._label = BX(labelId);
			}

			if(this.getSetting("enableHint", false))
			{
				this.createHint(this.getSetting("hintMessages", null));
			}

			this.doInitialize();

			BX.addCustomEvent(
				window,
				"BX.CrmEntityConverter:applyPermissions",
				BX.delegate(this.applyPermissions, this)
			);
		},
		doInitialize: function()
		{
		},
		release: function()
		{
			this.closeMenu();

			BX.unbind(this._container, "click", this._containerClickHandler);
			BX.unbind(this._button, "click", this._buttonClickHandler);
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != "undefined" ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getEntityTypeName: function()
		{
			return BX.CrmEntityType.names.undefined;
		},
		getScheme: function()
		{
			return this._scheme;
		},
		setScheme: function(scheme, params)
		{
			this._scheme = scheme;
			this.processSchemeChange(params);
		},
		processSchemeChange: function(params)
		{
			if(this._label)
			{
				this._label.innerHTML = BX.util.htmlspecialchars(this.getSchemeDescription(this._scheme));
			}

			if(BX.prop.getBoolean(params, "convert", true))
			{
				window.setTimeout(BX.delegate(this.convert, this), 250);
			}
		},
		getSchemeDescription: function(scheme)
		{
			return "[" + scheme + "]";
		},
		applyPermissions: function(entityTypeName)
		{
			if(entityTypeName !== this.getEntityTypeName())
			{
				return;
			}

			var items = this.prepareItems();
			if(items.length === 0)
			{
				return;
			}

			for(var i = 0, length = items.length; i < length; i++)
			{
				if(this._scheme === items[i]["value"])
				{
					return;
				}
			}
			this.setScheme(items[0]["value"], { convert: false });
		},
		showMenu: function()
		{
			if(this._isMenuShown)
			{
				return;
			}

			var menuItems = [];
			var items = this.prepareItems();
			for(var i = 0; i < items.length; i++)
			{
				var item = items[i];

				menuItems.push(
					{
						text: item["text"],
						value: item["value"],
						href: "#",
						className: "crm-convert-item",
						onclick: this._menuIiemClickHandler
					}
				);
			}

			if(typeof(BX.PopupMenu.Data[this._menuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._menuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._menuId];
			}

			var anchor = this._button;
			var anchorPos = BX.pos(anchor);

			BX.PopupMenu.show(
				this._menuId,
				anchor,
				menuItems,
				{
					autoHide: true,
					offsetLeft: (anchorPos["width"] / 2),
					angle: { position: "top", offset: 0 },
					events: { onPopupClose: this._menuCloseHandler }
				}
			);

			this._isMenuShown = true;
		},
		closeMenu: function()
		{
			if(!this._isMenuShown)
			{
				return;
			}

			BX.PopupMenu.destroy(this._menuId);
			this._isMenuShown = false;
		},
		prepareItems: function()
		{
			return [];
		},
		prepareConfig: function()
		{
		},
		processContainerClick: function()
		{
			this.convert();
		},
		processMenuItemClick: function(item)
		{
			this.setScheme(item["value"]);
			this.closeMenu();
		},
		createHint: function(messages)
		{
			if(!messages)
			{
				return;
			}

			this._hint = BX.PopupWindowManager.create(this._id + "_hint",
				this._container,
				{
					offsetTop : -8,
					autoHide : true,
					closeByEsc : false,
					angle: { position: "bottom", offset: 42 },
					events: { onPopupClose : BX.delegate(this.onHintClose, this) },
					content : BX.create("DIV",
						{
							attrs: { className: "crm-conv-selector-popup-contents" },
							children:
							[
								BX.create("SPAN",
									{ attrs: { className: "crm-popup-title" }, text: messages["title"]  }
								),
								BX.create("P", { text: messages["content"] }),
								BX.create("P",
									{
										children:
										[
											BX.create("A",
												{
													props: { href: "#" },
													text: messages["disabling"],
													events: { "click": BX.delegate(this.onDisableHint, this)  }
												}
											)
										]
									}
								)
							]
						}
					)
				}
			);
			this._hint.show();
		},
		onDisableHint: function(e)
		{
			if(this._hint)
			{
				this._hint.close();
				BX.userOptions.save(
					"crm.interface.toobar",
					"conv_scheme_selector",
					"enable_" + this.getId().toLowerCase() + "_hint",
					"N",
					false
				);
			}
			return BX.PreventDefault(e);
		},
		onHintClose: function()
		{
			if(this._hint)
			{
				this._hint.destroy();
				this._hint = null;
			}
		},
		onButtonClick: function(e)
		{
			this.showMenu();
		},
		onContainerClick: function(e)
		{
			this.processContainerClick();
		},
		onMenuItemClick: function(e, item)
		{
			this.processMenuItemClick(item);
			return BX.PreventDefault(e);
		},
		onMenuClose: function()
		{
			this._isMenuShown = false;
		},
		convert: function()
		{
		}
	};
}

if(typeof(BX.CrmEntityConversionScheme) === "undefined")
{
	BX.CrmEntityConversionScheme = function()
	{
	};
	BX.CrmEntityConversionScheme.mergeConfigs = function(source, target)
	{
		this.mergeEntityConfigs(BX.CrmEntityType.names.deal, source, target);
		this.mergeEntityConfigs(BX.CrmEntityType.names.contact, source, target);
		this.mergeEntityConfigs(BX.CrmEntityType.names.company, source, target);
		this.mergeEntityConfigs(BX.CrmEntityType.names.invoice, source, target);
		this.mergeEntityConfigs(BX.CrmEntityType.names.quote, source, target);
	};
	BX.CrmEntityConversionScheme.mergeEntityConfigs = function(entityTypeName, source, target)
	{
		var key = entityTypeName.toLowerCase();
		if(typeof(source[key]) === "undefined")
		{
			return;
		}

		if(typeof(target[key]) === "undefined")
		{
			target[key] = {};
		}

		if(BX.type.isNotEmptyString(source[key]["active"]))
		{
			target[key]["active"] = source[key]["active"];
		}
		if(BX.type.isNotEmptyString(source[key]["enableSync"]))
		{
			target[key]["enableSync"] = source[key]["enableSync"];
		}
		if(BX.type.isPlainObject(source[key]["initData"]))
		{
			target[key]["initData"] = source[key]["initData"];
		}
	};

	BX.CrmEntityConversionScheme.removeEntityConfigs = function(entityTypeName, config)
	{
		var key = entityTypeName.toLowerCase();
		if(typeof(config[key]) !== "undefined")
		{
			delete config[key];
		}
	};
}

if(typeof(BX.CrmLeadConversionScheme) === "undefined")
{
	BX.CrmLeadConversionScheme =
	{
		undefined: "",
		dealcontactcompany: "DEAL_CONTACT_COMPANY",
		dealcontact: "DEAL_CONTACT",
		dealcompany: "DEAL_COMPANY",
		deal: "DEAL",
		contactcompany: "CONTACT_COMPANY",
		contact: "CONTACT",
		company: "COMPANY",

		getListItems: function(ids)
		{
			var results = [];
			for(var i = 0; i < ids.length; i++)
			{
				var id = ids[i];
				results.push({ value: id, text: this.getDescription(id) });
			}

			return results;
		},
		getDescription: function(id)
		{
			var m = this.messages;
			return m.hasOwnProperty(id) ? m[id] : id;
		},
		fromConfig: function(config)
		{
			var scheme = this.undefined;
			var isDealActive = this.isEntityActive(config, "deal");
			var isContactActive = this.isEntityActive(config, "contact");
			var isCompanyActive = this.isEntityActive(config, "company");

			if(isDealActive)
			{
				if(isContactActive && isCompanyActive)
				{
					scheme = this.dealcontactcompany;
				}
				else if(isContactActive)
				{
					scheme = this.dealcontact;
				}
				else if(isCompanyActive)
				{
					scheme = this.dealcompany;
				}
				else
				{
					scheme = this.deal;
				}
			}
			else if(isContactActive && isCompanyActive)
			{
				scheme = this.contactcompany;
			}
			else if(isContactActive)
			{
				scheme = this.contact;
			}
			else if(isCompanyActive)
			{
				scheme = this.company;
			}

			return scheme;
		},
		toConfig: function(scheme, config)
		{
			this.markEntityAsActive(
				config,
				BX.CrmEntityType.names.deal,
				scheme === this.dealcontactcompany || scheme === this.dealcontact || scheme === this.dealcompany || scheme === this.deal
			);

			this.markEntityAsActive(
				config,
				BX.CrmEntityType.names.contact,
				scheme === this.dealcontactcompany || scheme === this.dealcontact || scheme === this.contactcompany || scheme === this.contact
			);

			this.markEntityAsActive(
				config,
				BX.CrmEntityType.names.company,
				scheme === this.dealcontactcompany || scheme === this.dealcompany || scheme === this.contactcompany || scheme === this.company
			);
		},
		createConfig: function(scheme)
		{
			var config = {};
			this.toConfig(scheme, config);
			return config;
		},
		isEntityActive: function(config, entityTypeName)
		{
			var key = entityTypeName.toLowerCase();
			var params = typeof(config[key]) !== "undefined" ? config[key] : {};
			return BX.type.isNotEmptyString(params["active"]) && params["active"] === "Y"
		},
		markEntityAsActive: function(config, entityTypeName, active)
		{
			var key = entityTypeName.toLowerCase();
			if(typeof(config[key]) === "undefined")
			{
				config[key] = {};
			}
			config[key]["active"] = active ? "Y" : "N";
		},
		mergeConfigs: function(source, target)
		{
			BX.CrmEntityConversionScheme.mergeEntityConfigs(BX.CrmEntityType.names.deal, source, target);
			BX.CrmEntityConversionScheme.mergeEntityConfigs(BX.CrmEntityType.names.contact, source, target);
			BX.CrmEntityConversionScheme.mergeEntityConfigs(BX.CrmEntityType.names.company, source, target);

			BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.invoice, target);
			BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.quote, target);
		}
	};

	if(typeof(BX.CrmLeadConversionScheme.messages) === "undefined")
	{
		BX.CrmLeadConversionScheme.messages = {};
	}
}

if(typeof(BX.CrmDealConversionScheme) === "undefined")
{
	BX.CrmDealConversionScheme =
	{
		undefined: "",
		invoice: "INVOICE",
		quote: "QUOTE",
		getListItems: function(ids)
		{
			var results = [];
			for(var i = 0; i < ids.length; i++)
			{
				var id = ids[i];
				results.push({ value: id, text: this.getDescription(id) });
			}

			return results;
		},
		getDescription: function(id)
		{
			var m = this.messages;
			return m.hasOwnProperty(id) ? m[id] : id;
		},
		fromConfig: function(config)
		{
			var scheme = this.undefined;
			if(this.isEntityActive(config, "invoice"))
			{
				scheme = this.invoice;
			}
			else if(this.isEntityActive(config, "quote"))
			{
				scheme = this.quote;
			}
			return scheme;
		},
		toConfig: function(scheme, config)
		{
			this.markEntityAsActive(config, "invoice", scheme === this.invoice);
			this.markEntityAsActive(config, "quote", scheme === this.quote);
		},
		createConfig: function(scheme)
		{
			var config = {};
			this.toConfig(scheme, config);
			return config;
		},
		isEntityActive: function(config, entityTypeName)
		{
			var params = typeof(config[entityTypeName]) !== "undefined" ? config[entityTypeName] : {};
			return BX.type.isNotEmptyString(params["active"]) && params["active"] === "Y"
		},
		markEntityAsActive: function(config, entityTypeName, active)
		{
			if(typeof(config[entityTypeName]) === "undefined")
			{
				config[entityTypeName] = {};
			}
			config[entityTypeName]["active"] = active ? "Y" : "N";
		},
		mergeConfigs: function(source, target)
		{
			BX.CrmEntityConversionScheme.mergeEntityConfigs(BX.CrmEntityType.names.invoice, source, target);
			BX.CrmEntityConversionScheme.mergeEntityConfigs(BX.CrmEntityType.names.quote, source, target);

			BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.deal, target);
			BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.contact, target);
			BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.company, target);
		}
	};
	if(typeof(BX.CrmDealConversionScheme.messages) === "undefined")
	{
		BX.CrmDealConversionScheme.messages = {};
	}
}

if(typeof(BX.CrmQuoteConversionScheme) === "undefined")
{
	BX.CrmQuoteConversionScheme =
	{
		undefined: "",
		deal: "DEAL",
		invoice: "INVOICE",
		getListItems: function(ids)
		{
			var results = [];
			for(var i = 0; i < ids.length; i++)
			{
				var id = ids[i];
				results.push({ value: id, text: this.getDescription(id) });
			}

			return results;
		},
		getDescription: function(id)
		{
			var m = this.messages;
			return m.hasOwnProperty(id) ? m[id] : id;
		},
		fromConfig: function(config)
		{
			var scheme = this.undefined;
			if(this.isEntityActive(config, "deal"))
			{
				scheme = this.deal;
			}
			else if(this.isEntityActive(config, "invoice"))
			{
				scheme = this.invoice;
			}
			return scheme;
		},
		toConfig: function(scheme, config)
		{
			this.markEntityAsActive(config, "deal", scheme === this.deal);
			this.markEntityAsActive(config, "invoice", scheme === this.invoice);
		},
		createConfig: function(scheme)
		{
			var config = {};
			this.toConfig(scheme, config);
			return config;
		},
		isEntityActive: function(config, entityTypeName)
		{
			var params = typeof(config[entityTypeName]) !== "undefined" ? config[entityTypeName] : {};
			return BX.type.isNotEmptyString(params["active"]) && params["active"] === "Y"
		},
		markEntityAsActive: function(config, entityTypeName, active)
		{
			if(typeof(config[entityTypeName]) === "undefined")
			{
				config[entityTypeName] = {};
			}
			config[entityTypeName]["active"] = active ? "Y" : "N";
		},
		mergeConfigs: function(source, target)
		{
			BX.CrmEntityConversionScheme.mergeEntityConfigs(BX.CrmEntityType.names.deal, source, target);
			BX.CrmEntityConversionScheme.mergeEntityConfigs(BX.CrmEntityType.names.invoice, source, target);

			BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.quote, target);
			BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.contact, target);
			BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.company, target);
		}
	};
	if(typeof(BX.CrmQuoteConversionScheme.messages) === "undefined")
	{
		BX.CrmQuoteConversionScheme.messages = {};
	}
}

if(typeof(BX.CrmOrderConversionScheme) === "undefined")
{
	BX.CrmOrderConversionScheme =
		{
			undefined: "",
			deal: "DEAL",
			invoice: "INVOICE",
			getListItems: function(ids)
			{
				var results = [];
				for(var i = 0; i < ids.length; i++)
				{
					var id = ids[i];
					results.push({ value: id, text: this.getDescription(id) });
				}

				return results;
			},
			getDescription: function(id)
			{
				var m = this.messages;
				return m.hasOwnProperty(id) ? m[id] : id;
			},
			fromConfig: function(config)
			{
				var scheme = this.undefined;
				if(this.isEntityActive(config, "deal"))
				{
					scheme = this.deal;
				}
				else if(this.isEntityActive(config, "invoice"))
				{
					scheme = this.invoice;
				}
				return scheme;
			},
			toConfig: function(scheme, config)
			{
				this.markEntityAsActive(config, "deal", scheme === this.deal);
				this.markEntityAsActive(config, "invoice", scheme === this.invoice);
			},
			createConfig: function(scheme)
			{
				var config = {};
				this.toConfig(scheme, config);
				return config;
			},
			isEntityActive: function(config, entityTypeName)
			{
				var params = typeof(config[entityTypeName]) !== "undefined" ? config[entityTypeName] : {};
				return BX.type.isNotEmptyString(params["active"]) && params["active"] === "Y"
			},
			markEntityAsActive: function(config, entityTypeName, active)
			{
				if(typeof(config[entityTypeName]) === "undefined")
				{
					config[entityTypeName] = {};
				}
				config[entityTypeName]["active"] = active ? "Y" : "N";
			},
			mergeConfigs: function(source, target)
			{
				BX.CrmEntityConversionScheme.mergeEntityConfigs(BX.CrmEntityType.names.deal, source, target);
				BX.CrmEntityConversionScheme.mergeEntityConfigs(BX.CrmEntityType.names.invoice, source, target);

				BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.quote, target);
				BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.contact, target);
				BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.company, target);
			}
		};
	if(typeof(BX.CrmOrderConversionScheme.messages) === "undefined")
	{
		BX.CrmOrderConversionScheme.messages = {};
	}
}

if(typeof(BX.CrmEntityConverterMode) === "undefined")
{
	BX.CrmEntityConverterMode =
	{
		intermediate: 0,
		schemeSetup: 1,
		syncSetup: 2,
		request: 3
	}
}

if(typeof(BX.CrmEntityConverter) === "undefined")
{
	BX.CrmEntityConverter = function()
	{
		this._id = "";
		this._settings = {};
		this._config = {};
		this._contextData = null;
		this._mode = BX.CrmEntityConverterMode.intermediate;
		this._entityId = 0;
		this._originUrl = "";
		this._syncEditor = null;
		this._syncEditorClosingListener = BX.delegate(this.onSyncEditorClose, this);
		this._enableSync = false;
		this._enablePageRefresh = true;
		this._enableRedirectToShowPage = true;
		this._requestIsRunning = false;
		this._dealCategorySelectDialog = null;
		this._entityEditorDialog = null;
		this._dealCategorySelectListener = BX.delegate(this.onDealCategorySelect, this);
		this._entityEditorDialogListener = BX.delegate(this.onEntityEditorDialogClose, this);
	};
	BX.CrmEntityConverter.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._config = this.getSetting("config", {});
			this._serviceUrl = this.getSetting("serviceUrl", "");

			this._enablePageRefresh = this.getSetting("enablePageRefresh", true);
			this._enableRedirectToShowPage = this.getSetting("enableRedirectToShowPage", true);
			this._enableSlider = this.getSetting("enableSlider", false);
		},
		getSetting: function(name, defaultval)
		{
			return typeof(this._settings[name]) !== "undefined" ? this._settings[name] : defaultval;
		},
		setSetting: function(name, val)
		{
			this._settings[name] = val;
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			return name;
		},
		getEntityTypeId: function()
		{
			return BX.CrmEntityType.enumeration.undefined;
		},
		getEntityId: function()
		{
			return this._entityId;
		},
		getConfig: function()
		{
			return this._config;
		},
		getProgressManager: function()
		{
			return null;
		},
		setupSynchronization: function(fieldNames)
		{
			this._mode = BX.CrmEntityConverterMode.syncSetup;
			if(this._syncEditor)
			{
				this._syncEditor.setConfig(this._config);
				this._syncEditor.setFieldNames(fieldNames);
			}
			else
			{
				this._syncEditor = BX.CrmEntityFieldSynchronizationEditor.create(
					this._id + "_config",
					{
						converter: this,
						config: this._config,
						title: this.getMessage("dialogTitle"),
						fieldNames: fieldNames,
						legend: this.getMessage("syncEditorLegend"),
						fieldListTitle: this.getMessage("syncEditorFieldListTitle"),
						entityListTitle: this.getMessage("syncEditorEntityListTitle"),
						continueButton: this.getMessage("continueButton"),
						cancelButton: this.getMessage("cancelButton")
					}
				);
				this._syncEditor.addClosingListener(this._syncEditorClosingListener);
			}
			this._syncEditor.show();
		},
		createSynchronizationEditor: function(id, config, fieldNames)
		{
			return BX.CrmEntityFieldSynchronizationEditor.create(
				id,
				{
					converter: this,
					config: config,
					title: this.getMessage("dialogTitle"),
					fieldNames: fieldNames,
					legend: this.getMessage("syncEditorLegend"),
					fieldListTitle: this.getMessage("syncEditorFieldListTitle"),
					entityListTitle: this.getMessage("syncEditorEntityListTitle"),
					continueButton: this.getMessage("continueButton"),
					cancelButton: this.getMessage("cancelButton")
				}
			);
		},
		convert: function(entityId, config, originUrl, contextData)
		{
			if(!BX.type.isPlainObject(config))
			{
				return;
			}

			this._entityId = entityId;
			this._contextData = BX.type.isPlainObject(contextData) ? contextData : null;
			this._originUrl = originUrl;

			this.registerConfig(config);

			if(!BX.CrmLeadConversionScheme.isEntityActive(this._config, BX.CrmEntityType.names.deal))
			{
				this.startRequest();
			}
			else
			{
				var categoryCount = BX.CrmDealCategory.infos.length;
				if(categoryCount < 2)
				{
					if(categoryCount > 0)
					{
						if(!BX.type.isPlainObject(this._config["deal"]["initData"]))
						{
							this._config["deal"]["initData"] = {};
						}

						this._config["deal"]["initData"]["categoryId"] = BX.prop.getInteger(BX.CrmDealCategory.infos[0], "id", 0);
					}
					this.startRequest();
				}
				else
				{
					var categoryId = BX.type.isPlainObject(this._config["deal"]["initData"]) ?
						this._config["deal"]["initData"]["categoryId"] : 0;
					if(!this._dealCategorySelectDialog)
					{
						this._dealCategorySelectDialog = BX.CrmDealCategorySelectDialog.create(
							this._id, { value: categoryId }
						);
						this._dealCategorySelectDialog.addCloseListener(this._dealCategorySelectListener);
					}
					this._dealCategorySelectDialog.open();
				}
			}
		},
		registerConfig: function(config)
		{
			BX.CrmEntityConversionScheme.mergeConfigs(config, this._config);
		},
		onDealCategorySelect: function(sender, args)
		{
			if(!(BX.type.isBoolean(args["isCanceled"]) && args["isCanceled"] === false))
			{
				return;
			}

			if(!BX.type.isPlainObject(this._config["deal"]["initData"]))
			{
				this._config["deal"]["initData"] = {};
			}
			this._config["deal"]["initData"]["categoryId"] = sender.getValue();
			this.startRequest();
		},
		onSyncEditorClose: function(sender, args)
		{
			this._mode = BX.CrmEntityConverterMode.intermediate;

			if(!(BX.type.isBoolean(args["isCanceled"]) && args["isCanceled"] === false))
			{
				return;
			}

			this._enableSync = true;
			this._config = this._syncEditor.getConfig();

			this.startRequest();
		},
		singRequestUrl: function(url)
		{
			var params = { action: "convert" };
			for(var key in this._config)
			{
				if(this._config.hasOwnProperty(key))
				{
					params[key] = BX.prop.getString(this._config[key], "active", "N");
				}
			}
			return BX.util.add_url_param(url, params);
		},
		startRequest: function()
		{
			if(this._requestIsRunning)
			{
				return;
			}
			this._requestIsRunning = true;

			BX.ajax(
				{
					url: this.singRequestUrl(this._serviceUrl),
					method: "POST",
					dataType: "json",
					data:
						{
							"MODE": "CONVERT",
							"ENTITY_ID": this._entityId,
							"ENABLE_SYNCHRONIZATION": this._enableSync ? "Y" : "N",
							"ENABLE_REDIRECT_TO_SHOW": this._enableRedirectToShowPage ? "Y" : "N",
							"ENABLE_SLIDER": this._enableSlider ? "Y" : "N",
							"CONFIG": this._config,
							"CONTEXT": this._contextData,
							"ORIGIN_URL": this._originUrl
						},
					onsuccess: BX.delegate(this.onRequestSuccess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
			this._mode = BX.CrmEntityConverterMode.request;
		},
		onRequestSuccess: function(result)
		{
			this._requestIsRunning = false;
			this._mode = BX.CrmEntityConverterMode.intermediate;

			if(BX.type.isPlainObject(result["ERROR"]))
			{
				this.showError(result["ERROR"]);
				return;
			}

			var data;
			if(BX.type.isPlainObject(result["REQUIRED_ACTION"]))
			{
				var action = result["REQUIRED_ACTION"];
				var name = BX.type.isNotEmptyString(action["NAME"]) ? action["NAME"] : "";
				data = BX.type.isPlainObject(action["DATA"]) ? action["DATA"] : {};
				if(name === "SYNCHRONIZE")
				{
					if(BX.type.isPlainObject(data["CONFIG"]))
					{
						this._config = data["CONFIG"];
					}

					this.setupSynchronization(BX.type.isArray(data["FIELD_NAMES"]) ? data["FIELD_NAMES"] : []);
				}
				if(name === "CORRECT")
				{
					var checkErrors = BX.prop.getObject(data, "CHECK_ERRORS", null);
					if(checkErrors)
					{
						var manager = this.getProgressManager();
						this.openEntityEditorDialog(
							{
								title: manager ? manager.getMessage("checkErrorTitle") : null,
								helpData: { text: manager.getMessage("checkErrorHelp"), code: manager.getMessage("checkErrorHelpArticleCode") },
								fieldNames: Object.keys(checkErrors),
								initData: BX.prop.getObject(data, "EDITOR_INIT_DATA", null),
								context: BX.prop.getObject(data, "CONTEXT", null)
							}
						);
						return;
					}
				}
				return;
			}

			data = BX.prop.getObject(result, "DATA", null);
			if(!data)
			{
				return;
			}

			var redirectUrl = BX.prop.getString(data, "URL", "");
			var isRedirected = false;
			if(BX.prop.getString(data, "IS_FINISHED", "") === "Y")
			{
				this._contextData = null;

				//region Fire Events
				var entityTypeId = this.getEntityTypeId();
				var eventArgs =
					{
						entityTypeId: entityTypeId,
						entityTypeName: BX.CrmEntityType.resolveName(entityTypeId),
						entityId: this._entityId,
						redirectUrl: redirectUrl,
						isRedirected: false
					};

				var current = BX.Crm.Page.getTopSlider();
				if(current)
				{
					eventArgs["sliderUrl"] = current.getUrl();
				}

				BX.onCustomEvent(window, "Crm.EntityConverter.Converted", [ this, eventArgs ]);
				BX.localStorage.set("onCrmEntityConvert", eventArgs, 10);

				isRedirected = eventArgs["isRedirected"];
				//endregion
			}

			if(redirectUrl !== "" && !isRedirected)
			{
				window.setTimeout(
					function(){ BX.Crm.Page.open(redirectUrl); },
					0
				);
			}
			else if(this._enablePageRefresh && !(isRedirected && window.top === window))
			{
				window.setTimeout(
					function(){ window.location.reload(); },
					0
				);
			}
		},
		onRequestFailure: function(result)
		{
			BX.closeWait();
			this._requestIsRunning = false;
			this._mode = BX.CrmEntityConverterMode.intermediate;
		},
		openEntityEditorDialog: function(params)
		{
			BX.Crm.PartialEditorDialog.close("entity-converter-editor");

			this._entityEditorDialog = BX.Crm.PartialEditorDialog.create(
				"entity-converter-editor",
				{
					title: BX.prop.getString(params, "title", "Please fill in all required fields"),
					entityTypeId: this.getEntityTypeId(),
					entityId: this.getEntityId(),
					fieldNames: BX.prop.getArray(params, "fieldNames", []),
					helpData: BX.prop.getObject(params, "helpData", null),
					context: BX.prop.getObject(params, "context", null)
				}
			);

			window.setTimeout(
				function()
				{
					this._entityEditorDialog.open();
					BX.addCustomEvent(window, "Crm.PartialEditorDialog.Close", this._entityEditorDialogListener);
				}.bind(this),
				150
			);
		},
		onEntityEditorDialogClose: function(sender, eventParams)
		{
			if(!(this.getEntityTypeId() === BX.prop.getInteger(eventParams, "entityTypeId", 0)
				&& this.getEntityId() === BX.prop.getInteger(eventParams, "entityId", 0))
			)
			{
				return;
			}

			this._entityEditorDialog = null;
			BX.removeCustomEvent(window, "Crm.PartialEditorDialog.Close", this._entityEditorDialogListener);

			if(!BX.prop.getBoolean(eventParams, "isCancelled", true))
			{
				this.startRequest();
			}
		},
		showError: function(error)
		{
			if(BX.type.isPlainObject(error))
			{
				alert(BX.type.isNotEmptyString(error["MESSAGE"]) ? error["MESSAGE"] : this.getMessage("generalError"));
			}
		}
	};
	BX.CrmEntityConverter.create = function(id, settings)
	{
		var self = new BX.CrmEntityConverter();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmLeadConverter) === "undefined")
{
	BX.CrmLeadConverter = function()
	{
		BX.CrmLeadConverter.superclass.constructor.apply(this);
		this._entitySelectorId = "lead_converter";
		this._entitySelectHandler = BX.delegate(this.onEntitySelect, this);
		this._entitySelectCallback = null;
	};
	BX.extend(BX.CrmLeadConverter, BX.CrmEntityConverter);
	BX.CrmLeadConverter.prototype.getProgressManager = function()
	{
		return BX.CrmLeadStatusManager.current;
	};
	BX.CrmLeadConverter.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.lead;
	};
	BX.CrmLeadConverter.prototype.registerConfig = function(config)
	{
		BX.CrmLeadConversionScheme.mergeConfigs(config, this._config);
	};
	BX.CrmLeadConverter.prototype.getMessage = function(name)
	{
		var m = BX.CrmLeadConverter.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmLeadConverter.messages) === "undefined")
	{
		BX.CrmLeadConverter.messages = {};
	}
	BX.CrmLeadConverter.prototype.openEntitySelector = function(callback)
	{
		this._entitySelectCallback = BX.type.isFunction(callback) ? callback : null;

		var selectorId = this._entitySelectorId;
		if(typeof(obCrm[selectorId]) === "undefined")
		{
			obCrm[selectorId] = new CRM(
				selectorId,
				null,
				null,
				selectorId,
				[],
				false,
				true,
				[ "contact", "company" ],
				{
					"contact": this.getMessage("contact"),
					"company": this.getMessage("company"),
					"ok": this.getMessage("selectButton"),
					"cancel": BX.message("JS_CORE_WINDOW_CANCEL"),
					"close": BX.message("JS_CORE_WINDOW_CLOSE"),
					"wait": BX.message("JS_CORE_LOADING"),
					"noresult": this.getMessage("noresult"),
					"search" : this.getMessage("search"),
					"last" : this.getMessage("last")
				},
				true
			);
			obCrm[selectorId].Init();
			obCrm[selectorId].AddOnSaveListener(this._entitySelectHandler);
		}

		obCrm[selectorId].Open(
			{
				closeIcon: { top: "10px", right: "15px" },
				closeByEsc: true,
				titleBar: this.getMessage("entitySelectorTitle")
			}
		);
	};
	BX.CrmLeadConverter.prototype.onEntitySelect = function(settings)
	{
		var selectorId = this._entitySelectorId;
		obCrm[selectorId].RemoveOnSaveListener(this._entitySelectHandler);
		obCrm[selectorId].Clear();
		delete obCrm[selectorId];

		if(!this._entitySelectCallback)
		{
			return;
		}

		var type;
		var data = null;
		for(type in settings)
		{
			if(settings.hasOwnProperty(type)
				&& BX.type.isPlainObject(settings[type])
				&& BX.type.isPlainObject(settings[type][0]))
			{
				var setting = settings[type][0];
				var entityId = typeof(setting["id"]) ? parseInt(setting["id"]) : 0;
				if(entityId > 0)
				{
					if(data === null)
					{
						data = {};
					}
					data[type] = entityId;
				}
			}
		}

		if(data === null)
		{
			this._entitySelectCallback({ config: null, data: null });
		}
		else
		{
			var config = { deal: { active: "N" }, contact: { active: "N" }, company: { active: "N" } };
			for(type in data)
			{
				if(data.hasOwnProperty(type) && typeof(config[type]) !== "undefined")
				{
					config[type]["active"] = "Y";
				}
			}
			this._entitySelectCallback({ config: config, data: data });
		}
	};
	BX.CrmLeadConverter.create = function(id, settings)
	{
		var self = new BX.CrmLeadConverter();
		self.initialize(id, settings);
		return self;
	};
	BX.CrmLeadConverter.current = null;
	if(typeof(BX.CrmLeadConverter.settings === "undefined"))
	{
		BX.CrmLeadConverter.settings = {};
	}
	if(typeof(BX.CrmLeadConverter.permissions === "undefined"))
	{
		BX.CrmLeadConverter.permissions = { contact: false, company: false, deal: false };
	}
	BX.CrmLeadConverter.getCurrent = function()
	{
		if(!this.current)
		{
			this.current = BX.CrmLeadConverter.create("current", this.settings);
		}
		return this.current;
	};
}

if(typeof(BX.CrmDealConverter) === "undefined")
{
	BX.CrmDealConverter = function()
	{
		BX.CrmDealConverter.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmDealConverter, BX.CrmEntityConverter);
	BX.CrmDealConverter.prototype.getProgressManager = function()
	{
		return BX.CrmDealStageManager.current;
	};
	BX.CrmDealConverter.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.deal;
	};
	BX.CrmDealConverter.prototype.registerConfig = function(config)
	{
		BX.CrmDealConversionScheme.mergeConfigs(config, this._config);
	};
	BX.CrmDealConverter.prototype.getMessage = function(name)
	{
		var m = BX.CrmDealConverter.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmDealConverter.messages) === "undefined")
	{
		BX.CrmDealConverter.messages = {};
	}
	BX.CrmDealConverter.create = function(id, settings)
	{
		var self = new BX.CrmDealConverter();
		self.initialize(id, settings);
		return self;
	};
	BX.CrmDealConverter.current = null;
	if(typeof(BX.CrmDealConverter.settings === "undefined"))
	{
		BX.CrmDealConverter.settings = {};
	}
	if(typeof(BX.CrmDealConverter.permissions === "undefined"))
	{
		BX.CrmDealConverter.permissions = { invoice: false, quote: false };
	}
	BX.CrmDealConverter.getCurrent = function()
	{
		if(!this.current)
		{
			this.current = BX.CrmDealConverter.create("current", this.settings);
		}
		return this.current;
	};
}

if(typeof(BX.CrmQuoteConverter) === "undefined")
{
	BX.CrmQuoteConverter = function()
	{
		BX.CrmQuoteConverter.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmQuoteConverter, BX.CrmEntityConverter);
	BX.CrmQuoteConverter.prototype.getProgressManager = function()
	{
		return BX.CrmQuoteStatusManager.current;
	};
	BX.CrmQuoteConverter.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.quote;
	};
	BX.CrmQuoteConverter.prototype.registerConfig = function(config)
	{
		BX.CrmQuoteConversionScheme.mergeConfigs(config, this._config);
	};
	BX.CrmQuoteConverter.prototype.getMessage = function(name)
	{
		var m = BX.CrmQuoteConverter.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmQuoteConverter.messages) === "undefined")
	{
		BX.CrmQuoteConverter.messages = {};
	}
	BX.CrmQuoteConverter.create = function(id, settings)
	{
		var self = new BX.CrmQuoteConverter();
		self.initialize(id, settings);
		return self;
	};
	BX.CrmQuoteConverter.current = null;
	if(typeof(BX.CrmQuoteConverter.settings === "undefined"))
	{
		BX.CrmQuoteConverter.settings = {};
	}
	if(typeof(BX.CrmQuoteConverter.permissions === "undefined"))
	{
		BX.CrmQuoteConverter.permissions = { invoice: false, quote: false };
	}
	BX.CrmQuoteConverter.getCurrent = function()
	{
		if(!this.current)
		{
			this.current = BX.CrmQuoteConverter.create("current", this.settings);
		}
		return this.current;
	};
}

if(typeof(BX.CrmOrderConverter) === "undefined")
{
	BX.CrmOrderConverter = function()
	{
		BX.CrmOrderConverter.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmOrderConverter, BX.CrmEntityConverter);
	BX.CrmOrderConverter.prototype.getProgressManager = function()
	{
		return BX.CrmOrderStatusManager.current;
	};
	BX.CrmOrderConverter.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.order;
	};
	BX.CrmOrderConverter.prototype.registerConfig = function(config)
	{
		BX.CrmOrderConversionScheme.mergeConfigs(config, this._config);
	};
	BX.CrmOrderConverter.prototype.getMessage = function(name)
	{
		var m = BX.CrmOrderConverter.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmOrderConverter.messages) === "undefined")
	{
		BX.CrmOrderConverter.messages = {};
	}
	BX.CrmOrderConverter.create = function(id, settings)
	{
		var self = new BX.CrmOrderConverter();
		self.initialize(id, settings);
		return self;
	};
	BX.CrmOrderConverter.current = null;
	if(typeof(BX.CrmOrderConverter.settings === "undefined"))
	{
		BX.CrmOrderConverter.settings = {};
	}
	if(typeof(BX.CrmOrderConverter.permissions === "undefined"))
	{
		BX.CrmOrderConverter.permissions = { invoice: false, quote: false };
	}
	BX.CrmOrderConverter.getCurrent = function()
	{
		if(!this.current)
		{
			this.current = BX.CrmOrderConverter.create("current", this.settings);
		}
		return this.current;
	};
}

if(typeof(BX.CrmEntityFieldSynchronizationEditor) === "undefined")
{
	BX.CrmEntityFieldSynchronizationEditor = function()
	{
		this._id = "";
		this._settings = {};
		this._converter = null;
		this._config = {};
		this._fieldNames = [];
		this._closingNotifier = null;
		this._contentWrapper = null;
		this._fieldWrapper = null;
		this._foldButton = null;
		this._foldButtonClickHandler = BX.delegate(this.onFoldButtonClick, this);
		this._checkBoxes = {};
		this._resizer = null;
		this._popup = null;
	};
	BX.CrmEntityFieldSynchronizationEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._converter = this.getSetting("converter");

			this._config = this.getSetting("config", {});
			this._fieldNames = this.getSetting("fieldNames", []);
			this._closingNotifier = BX.CrmNotifier.create(this);
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getId: function()
		{
			return this._id;
		},
		getConfig: function()
		{
			return this._config;
		},
		setConfig: function(config)
		{
			this._config = config;
		},
		getFieldNames: function()
		{
			return this._fieldNames;
		},
		setFieldNames: function(fieldNames)
		{
			this._fieldNames = fieldNames;
		},
		show: function()
		{
			if(this.isShown())
			{
				return;
			}

			var id = this.getId();
			if(BX.CrmEntityFieldSynchronizationEditor.windows[id])
			{
				BX.CrmEntityFieldSynchronizationEditor.windows[id].destroy();
				delete BX.CrmEntityFieldSynchronizationEditor.windows[id];
			}

			var anchor = this.getSetting("anchor", null);
			this._popup = new BX.PopupWindow(
				id,
				anchor,
				{
					autoHide: false,
					draggable: true,
					zIndex: 100,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					closeIcon :
					{
						marginRight:"-2px",
						marginTop:"3px"
					},
					events:
					{
						onPopupShow: BX.delegate(this.onPopupShow, this),
						onPopupClose: BX.delegate(this.onPopupClose, this),
						onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
					},
					titleBar: this.getSetting("title"),
					content: this.prepareContent(),
					buttons: this.prepareButtons(),
					lightShadow : true,
					className : "crm-tip-popup"
				}
			);

			BX.CrmEntityFieldSynchronizationEditor.windows[id] = this._popup;
			this._popup.show();
		},
		close: function()
		{
			if(!(this._popup && this._popup.isShown()))
			{
				return;
			}

			this._popup.close();
		},
		isShown: function()
		{
			return this._popup && this._popup.isShown();
		},
		addClosingListener: function(listener)
		{
			this._closingNotifier.addListener(listener);
		},
		removeClosingListener: function(listener)
		{
			this._closingNotifier.removeListener(listener);
		},
		getMessage: function(name)
		{
			var m = BX.CrmEntityFieldSynchronizationEditor.messages;
			return m.hasOwnProperty(name) ? m.messages[name] : name;
		},
		prepareButtons: function()
		{
			return(
				[
					new BX.PopupWindowButton(
						{
							text: this.getSetting("continueButton"),
							className: "popup-window-button-accept",
							events: { click: BX.delegate(this.onContinueBtnClick, this) }
						}
					),
					new BX.PopupWindowButtonLink(
						{
							text: this.getSetting("cancelButton"),
							className: "popup-window-button-link-cancel",
							events: { click: BX.delegate(this.onCancelBtnClick, this) }
						}
					)
				]
			);
		},
		prepareContent: function()
		{
			this._contentWrapper = BX.create("DIV", { attrs: { className: "crm-popup-setting-fields" } });

			var fieldList = BX.create("UL", { attrs: { className: "crm-p-s-f-items-list" } });
			for(var i = 0; i < this._fieldNames.length; i++)
			{
				fieldList.appendChild(
					BX.create("LI", { attrs: { className: "crm-p-s-f-item" }, text: this._fieldNames[i] })
				);
			}

			var fieldWrapper = this._fieldWrapper = BX.create("DIV", { attrs: { className: "crm-p-s-f-block-wrap crm-p-s-f-block-hide" } });
			this._contentWrapper.appendChild(fieldWrapper);

			var fieldContainer = BX.create("DIV",
				{
					attrs: { className: "crm-p-s-f-top-block" },
					children:
					[
						BX.create("DIV",
							{
								attrs: { className: "crm-p-s-f-title" },
								text: this.getSetting("fieldListTitle") + ":"
							}
						),
						fieldList
					]
				}
			);

			var foldButton = this._foldButton = BX.create("DIV", { attrs: { className: "crm-p-s-f-open-btn" } });
			if(fieldList.children.length > 6)
			{
				BX.bind(foldButton, "click", this._foldButtonClickHandler);
			}
			else
			{
				fieldWrapper.classList.toggle('crm-p-s-f-block-open');
			}

			var innerFieldWrapper = BX.create("DIV",
				{
					attrs: { className: "crm-p-s-f-block-hide-inner" },
					children:
					[
						BX.create("DIV", { attrs: { className: "crm-p-s-f-text" }, text: this.getSetting("legend") }),
						fieldContainer,
						foldButton
					]
				}
			);

			fieldWrapper.appendChild(innerFieldWrapper);
			this._resizer = BX.AnimatedResize.create(innerFieldWrapper, fieldWrapper);

			var entityWrapper = BX.create("DIV", { attrs: { className: "crm-p-s-f-block-wrap" } });
			this._contentWrapper.appendChild(entityWrapper);
			entityWrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-p-s-f-title" },
						text: this.getSetting("entityListTitle") + ":"
					}
				)
			);

			var id = this.getId();
			this._checkBoxes = {};
			var entityList = BX.create("UL", { attrs: { className: "crm-p-s-f-checkbox-items-list" } });
			for(var entityTypeName in this._config)
			{
				if(!this._config.hasOwnProperty(entityTypeName))
				{
					continue;
				}

				var entityConfig = this._config[entityTypeName];
				var enableSync = BX.type.isNotEmptyString(entityConfig["enableSync"]) && entityConfig["enableSync"] === "Y";
				if(!enableSync)
				{
					continue;
				}

				var inputId = id + "_" + entityTypeName;
				var checkbox = BX.create("INPUT", { props: { id: inputId, type: "checkbox", checked: true } });
				this._checkBoxes[entityTypeName] = checkbox;

				var entityTitle = entityConfig.title || BX.CrmEntityType.getCaptionByName(entityTypeName);

				var label = BX.create("LABEL",
					{
						props: { htmlFor: inputId },
						text: entityTitle
					}
				);

				entityList.appendChild(
					BX.create("LI",
						{ attrs: { className: "crm-p-s-f-checkbox-item" }, children: [ checkbox, label ] }
					)
				);
			}
			entityWrapper.appendChild(entityList);
			return this._contentWrapper;
		},
		saveConfig: function()
		{
			for(var entityTypeName in this._checkBoxes)
			{
				if(this._checkBoxes.hasOwnProperty(entityTypeName) && this._config.hasOwnProperty(entityTypeName))
				{
					this._config[entityTypeName]["enableSync"] = this._checkBoxes[entityTypeName].checked ? "Y" : "N";
				}
			}
		},
		onFoldButtonClick: function()
		{
			this._fieldWrapper.classList.toggle("crm-p-s-f-block-open");
			this._resizer.run();
		},
		onContinueBtnClick: function()
		{
			this.saveConfig();
			this._closingNotifier.notify([{ isCanceled: false }]);
			this.close();
		},
		onCancelBtnClick: function()
		{
			this._closingNotifier.notify([{ isCanceled: true }]);
			this.close();
		},
		onPopupShow: function()
		{
		},
		onPopupClose: function()
		{
			if(this._popup)
			{
				this._contentWrapper = null;
				this._popup.destroy();
			}
		},
		onPopupDestroy: function()
		{
			if(!this._popup)
			{
				return;
			}

			this._fieldWrapper = null;
			this._foldButton = null;
			this._contentWrapper = null;
			this._checkBoxes = {};
			this._resizer = null;
			this._popup = null;
			delete BX.CrmEntityFieldSynchronizationEditor.windows[this.getId()];
		}
	};
	BX.CrmEntityFieldSynchronizationEditor.windows = {};
	if(typeof(BX.CrmEntityFieldSynchronizationEditor.messages) == "undefined")
	{
		BX.CrmEntityFieldSynchronizationEditor.messages = {};
	}
	BX.CrmEntityFieldSynchronizationEditor.create = function(id, settings)
	{
		var self = new BX.CrmEntityFieldSynchronizationEditor();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.AnimatedResize) === "undefined")
{
	BX.AnimatedResize = function()
	{
		this._innerBlock = null;
		this._mainBlock = null;
		this._isOpen = false;
	};

	BX.AnimatedResize.prototype =
	{
		initialize: function(innerBlock, mainBlock)
		{
			this._innerBlock = innerBlock;
			this._mainBlock = mainBlock;
		},
		run: function()
		{
			this._isOpen = this._mainBlock.offsetHeight == this._innerBlock.offsetHeight;
			this.ease(this._isOpen
				? { start : this._innerBlock.offsetHeight, finish : 0 }
				: { start: this._mainBlock.offsetHeight, finish: this._innerBlock.offsetHeight }
			);
			this._isOpen = !this._isOpen;
		},
		step: function(state)
		{
			this._mainBlock.style.height = state.height + "px";
		},
		complete: function()
		{
			if(this._isOpen)
			{
				this._mainBlock.style.height = "auto";
			}
		},
		ease: function (params)
		{
			(new BX.easing(
				{
					duration : 300,
					start : { height : params["start"] },
					finish : { height : params["finish"] },
					transition : BX.easing.makeEaseOut(BX.easing.transitions.circ),
					step : BX.delegate(this.step, this),
					complete :BX.delegate(this.complete, this)
				}
			)).animate();
		}
	};
	BX.AnimatedResize.create = function(innerBlock, mainBlock)
	{
		var self = new BX.AnimatedResize();
		self.initialize(innerBlock, mainBlock);
		return self;
	}
}

if(typeof(BX.CrmLeadConversionType) === "undefined")
{
	BX.CrmLeadConversionType =
	{
		undefined: 0,
		general: 1,
		returningCustomer: 2,
		supplement: 3,
		configs: {},
		getConfig: function(typeId)
		{
			return BX.prop.getObject(this.configs, typeId, null);
		}
	};
}

if(typeof(BX.CrmLeadConversionSchemeSelector) === "undefined")
{
	BX.CrmLeadConversionSchemeSelector = function()
	{
		BX.CrmLeadConversionSchemeSelector.superclass.constructor.apply(this);
		this._converter = null;
	};
	BX.extend(BX.CrmLeadConversionSchemeSelector, BX.CrmConversionSchemeSelector);
	BX.CrmLeadConversionSchemeSelector.prototype.getEntityTypeName = function()
	{
		return BX.CrmEntityType.names.lead;
	};
	BX.CrmLeadConversionSchemeSelector.prototype.getConverter = function()
	{
		if(!this._converter)
		{
			var typeId = BX.prop.getInteger(this._settings, "typeId", BX.CrmLeadConversionType.general);
			var config = BX.CrmLeadConversionType.getConfig(typeId);
			if(!config)
			{
				config = BX.prop.getObject(BX.CrmLeadConverter.settings, "config", null);
			}

			var serviceUrl = BX.prop.getString(BX.CrmLeadConverter.settings, "serviceUrl");
			this._converter = BX.CrmLeadConverter.create(this._id, { serviceUrl: serviceUrl, config: config });
		}
		return this._converter;
	};
	BX.CrmLeadConversionSchemeSelector.prototype.prepareItems = function()
	{
		var isDealPermitted = BX.CrmLeadConverter.permissions["deal"];
		var isContactPermitted = BX.CrmLeadConverter.permissions["contact"];
		var isCompanyPermitted = BX.CrmLeadConverter.permissions["company"];

		var enableDeal = isDealPermitted;
		var enableContact = isContactPermitted;
		var enableCompany = isCompanyPermitted;

		var typeId = BX.prop.getInteger(this._settings, "typeId", BX.CrmLeadConversionType.undefined);
		if(typeId === BX.CrmLeadConversionType.returningCustomer || typeId === BX.CrmLeadConversionType.supplement)
		{
			enableContact = enableCompany = false;
		}

		var schemes = [];
		if(enableDeal)
		{
			if(enableContact && enableCompany)
			{
				schemes.push(BX.CrmLeadConversionScheme.dealcontactcompany);
			}
			if(enableContact)
			{
				schemes.push(BX.CrmLeadConversionScheme.dealcontact);
			}
			if(enableCompany)
			{
				schemes.push(BX.CrmLeadConversionScheme.dealcompany);
			}

			schemes.push(BX.CrmLeadConversionScheme.deal);
		}
		if(enableContact && enableCompany)
		{
			schemes.push(BX.CrmLeadConversionScheme.contactcompany);
		}
		if(enableContact)
		{
			schemes.push(BX.CrmLeadConversionScheme.contact);
		}
		if(enableCompany)
		{
			schemes.push(BX.CrmLeadConversionScheme.company);
		}

		var items = BX.CrmLeadConversionScheme.getListItems(schemes);
		if(typeId !== BX.CrmLeadConversionType.returningCustomer &&
			(isContactPermitted || isCompanyPermitted)
		)
		{
			items.push(
				{
					value: "CUSTOM",
					text: this.getConverter().getMessage("openEntitySelector")
				}
			);
		}

		return items;
	};
	BX.CrmLeadConversionSchemeSelector.prototype.prepareConfig = function()
	{
		return BX.CrmLeadConversionScheme.createConfig(this._scheme);
	};
	BX.CrmLeadConversionSchemeSelector.prototype.getSchemeDescription = function(scheme)
	{
		return BX.CrmLeadConversionScheme.getDescription(scheme);
	};
	BX.CrmLeadConversionSchemeSelector.prototype.processMenuItemClick = function(item)
	{
		var value = item["value"];
		if(value === "CUSTOM")
		{
			this.getConverter().openEntitySelector(BX.delegate(this.onEntitySelect, this));
		}
		else
		{
			this.setScheme(value);
		}
		this.closeMenu();
	};
	BX.CrmLeadConversionSchemeSelector.prototype.onEntitySelect = function(result)
	{
		if(!BX.type.isPlainObject(result))
		{
			return;
		}

		this.getConverter().convert(
			this._entityId,
			result["config"],
			this.getSetting("originUrl"),
			result["data"]
		);
	};
	BX.CrmLeadConversionSchemeSelector.prototype.convert = function()
	{
		this.getConverter().convert(
			this._entityId,
			this.prepareConfig(),
			this.getSetting("originUrl")
		);
	};
	BX.CrmLeadConversionSchemeSelector.create = function(id, settings)
	{
		var self = new BX.CrmLeadConversionSchemeSelector();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmDealConversionSchemeSelector) === "undefined")
{
	BX.CrmDealConversionSchemeSelector = function()
	{
		BX.CrmDealConversionSchemeSelector.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmDealConversionSchemeSelector, BX.CrmConversionSchemeSelector);
	BX.CrmDealConversionSchemeSelector.prototype.getEntityTypeName = function()
	{
		return BX.CrmEntityType.names.deal;
	};
	BX.CrmDealConversionSchemeSelector.prototype.prepareItems = function()
	{
		var schemes = [];
		if(BX.CrmDealConverter.permissions["invoice"])
		{
			schemes.push(BX.CrmDealConversionScheme.invoice);
		}
		if(BX.CrmDealConverter.permissions["quote"])
		{
			schemes.push(BX.CrmDealConversionScheme.quote);
		}
		return BX.CrmDealConversionScheme.getListItems(schemes);
	};
	BX.CrmDealConversionSchemeSelector.prototype.prepareConfig = function()
	{
		return BX.CrmDealConversionScheme.createConfig(this._scheme);
	};
	BX.CrmDealConversionSchemeSelector.prototype.getSchemeDescription = function(scheme)
	{
		return BX.CrmDealConversionScheme.getDescription(scheme);
	};
	BX.CrmDealConversionSchemeSelector.prototype.convert = function()
	{
		BX.CrmDealConverter.getCurrent().convert(
			this._entityId,
			this.prepareConfig(),
			this.getSetting("originUrl", "")
		);
	};
	BX.CrmDealConversionSchemeSelector.create = function(id, settings)
	{
		var self = new BX.CrmDealConversionSchemeSelector();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmQuoteConversionSchemeSelector) === "undefined")
{
	BX.CrmQuoteConversionSchemeSelector = function()
	{
		BX.CrmQuoteConversionSchemeSelector.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmQuoteConversionSchemeSelector, BX.CrmConversionSchemeSelector);
	BX.CrmQuoteConversionSchemeSelector.prototype.getEntityTypeName = function()
	{
		return BX.CrmEntityType.names.quote;
	};
	BX.CrmQuoteConversionSchemeSelector.prototype.prepareItems = function()
	{
		var schemes = [];
		if(BX.CrmQuoteConverter.permissions["deal"])
		{
			schemes.push(BX.CrmQuoteConversionScheme.deal);
		}
		if(BX.CrmQuoteConverter.permissions["invoice"])
		{
			schemes.push(BX.CrmQuoteConversionScheme.invoice);
		}
		return BX.CrmQuoteConversionScheme.getListItems(schemes);
	};
	BX.CrmQuoteConversionSchemeSelector.prototype.prepareConfig = function()
	{
		return BX.CrmQuoteConversionScheme.createConfig(this._scheme);
	};
	BX.CrmQuoteConversionSchemeSelector.prototype.getSchemeDescription = function(scheme)
	{
		return BX.CrmQuoteConversionScheme.getDescription(scheme);
	};
	BX.CrmQuoteConversionSchemeSelector.prototype.convert = function()
	{
		BX.CrmQuoteConverter.getCurrent().convert(
			this._entityId,
			this.prepareConfig(),
			this.getSetting("originUrl", "")
		);
	};
	BX.CrmQuoteConversionSchemeSelector.items = {};
	BX.CrmQuoteConversionSchemeSelector.create = function(id, settings)
	{
		var self = new BX.CrmQuoteConversionSchemeSelector();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

//region BX.CrmRequisitePresetListLoader
BX.CrmRequisitePresetListLoader = function()
{
	this._id = "";
	this._settings = {};
	this._entityTypeName = "";
	this._serviceUrl = "";
	this._callback = null;
	this._isRequestRunning = false;
	this._waiter = null;
	this._resultData = null;
};
BX.CrmRequisitePresetListLoader.prototype =
{
	initialize: function(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : "crm_rq_prest_loader" + Math.random().toString().substring(2);
		this._settings = settings ? settings : {};

		this._entityTypeName = this.getSetting("entityTypeName", "");
		if(!BX.type.isNotEmptyString(this._entityTypeName))
		{
			throw "BX.CrmRequisitePresetListLoader. Could not find 'entityTypeName' parameter.";
		}

		this._entityTypeName = this._entityTypeName.toUpperCase();

		this._serviceUrl = this.getSetting("serviceUrl", "");
		if(!BX.type.isNotEmptyString(this._serviceUrl))
		{
			throw "BX.CrmRequisitePresetListLoader. Could not find 'serviceUrl' parameter.";
		}

		var callback = this.getSetting("callback");
		if(BX.type.isFunction(callback))
		{
			this._callback = callback;
		}
	},
	getId: function()
	{
		return this._id;
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	getResultData: function()
	{
		return this._resultData;
	},
	start: function()
	{
		if(this._isRequestRunning)
		{
			return false;
		}

		this._isRequestRunning = true;
		this._waiter = BX.showWait();
		BX.ajax(
			{
				url: this._serviceUrl,
				method: "POST",
				dataType: "json",
				data: { "ENTITY_TYPE_NAME": this._entityTypeName, "ACTION" : "GET_REQUISITE_PRESETS" },
				onsuccess: BX.delegate(this.onRequestSuccess, this),
				onfailure: BX.delegate(this.onRequestFailure, this)
			}
		);

		return true;
	},
	onRequestSuccess: function(data)
	{
		this._isRequestRunning = false;

		if(this._waiter)
		{
			BX.closeWait(null, this._waiter);
			this._waiter = null;
		}

		var result = BX.type.isPlainObject(data["RESULT"]) ? data["RESULT"] : {};
		this._resultData = BX.type.isArray(result["ITEMS"]) ? result["ITEMS"] : [];
		if(this._callback)
		{
			this._callback(this, { isSuccessed: true, resultData: this._resultData });
		}
	},
	onRequestFailure: function(data)
	{
		this._isRequestRunning = false;

		if(this._waiter)
		{
			BX.closeWait(null, this._waiter);
			this._waiter = null;
		}

		this._resultData = [];
		if(this._callback)
		{
			this._callback(this, { isSuccessed: false, resultData: this._resultData });
		}
	}
};
BX.CrmRequisitePresetListLoader.create = function(id, settings)
{
	var self = new BX.CrmRequisitePresetListLoader();
	self.initialize(id, settings);
	return self;
};
//endregion
//region BX.CrmRequisitePresetSelectDialog
BX.CrmRequisitePresetSelectDialog = function()
{
	this._id = "";
	this._settings = {};
	this._popup = null;
	this._contentWrapper = null;
	this._list = null;
	this._selector = null;
	this._callback = null;
};
BX.CrmRequisitePresetSelectDialog.prototype =
{
	initialize: function(id, settings)
	{
		this._id = id;
		this._settings = settings ? settings : {};

		this._list = this.getSetting("list");
		if(!BX.type.isArray(this._list))
		{
			throw "BX.CrmRequisitePresetSelectDialog. Could not find 'list' parameter.";
		}

		var callback = this.getSetting("callback");
		if(BX.type.isFunction(callback))
		{
			this._callback = callback;
		}
	},
	getId: function()
	{
		return this._id;
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	getMessage:function(name)
	{
		var m = BX.CrmRequisitePresetSelectDialog.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	},
	show: function()
	{
		if(this.isShown())
		{
			return;
		}

		var id = this.getId();
		if(BX.CrmRequisitePresetSelectDialog.windows[id])
		{
			BX.CrmRequisitePresetSelectDialog.windows[id].destroy();
		}

		this._popup = new BX.PopupWindow(
			id,
			this.getSetting("anchor", null),
			{
				autoHide: false,
				draggable: true,
				bindOptions: { forceBindPosition: false },
				closeByEsc: true,
				closeIcon: { top: "10px", right: "15px" },
				zIndex: 0,
				titleBar: this.getMessage("title"),
				content: this.prepareContent(),
				className : "crm-tip-popup",
				lightShadow : true,
				buttons:
				[
					new BX.PopupWindowButton(
						{
							text : BX.message("JS_CORE_WINDOW_CONTINUE"),
							className : "popup-window-button-accept",
							events: { click: BX.delegate(this.onAcceptButtonClick, this) }
						}
					),
					new BX.PopupWindowButtonLink(
						{
							text : BX.message("JS_CORE_WINDOW_CANCEL"),
							className : "popup-window-button-link-cancel",
							events: { click: BX.delegate(this.onCancelButtonClick, this) }
						}
					)
				],
				events:
				{
					onPopupShow: BX.delegate(this.onPopupShow, this),
					onPopupClose: BX.delegate(this.onPopupClose, this),
					onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
				}
			}
		);
		(BX.CrmRequisitePresetSelectDialog.windows[id] = this._popup).show();
	},
	close: function()
	{
		if(!(this._popup && this._popup.isShown()))
		{
			return;
		}

		this._popup.close();
	},
	isShown: function()
	{
		return this._popup && this._popup.isShown();
	},
	getSelectedValue: function()
	{
		return this._selector ? this._selector.value : "";
	},
	prepareContent: function()
	{
		var wrapper = this._contentWrapper = BX.create("DIV", { attrs: { className: "bx-requisite-dialog" } });
		var container = BX.create("DIV", { attrs: { className: "container-item" } });
		wrapper.appendChild(container);

		var selector = this._selector = BX.create('SELECT', {});
		var options = [];
		for(var i = 0; i < this._list.length; i++)
		{
			var item = this._list[i];
			options.push({ "value": item["ID"], "text": item["NAME"] });
		}
		BX.HtmlHelper.setupSelectOptions(selector, options);
		container.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "field-container field-container-left" },
					children:
					[
						BX.create("LABEL",
							{
								attrs: { className: "field-container-title" },
								text: this.getMessage("presetField") + ":"
							}
						),
						BX.create("SPAN", { attrs: { className: "select-container" }, children: [ selector ] })
					]
				}
			)
		);
		return this._contentWrapper;
	},
	onCancelButtonClick: function()
	{
		if(this._callback)
		{
			this._callback(this, { isAccepted: false, selectedValue: this.getSelectedValue() });
		}
	},
	onAcceptButtonClick: function()
	{
		if(this._callback)
		{
			this._callback(this, { isAccepted: true, selectedValue: this.getSelectedValue() });
		}
	},
	onPopupShow: function()
	{
	},
	onPopupClose: function()
	{
		if(this._popup)
		{
			this._popup.destroy();
		}

		if(this._callback)
		{
			this._callback(this, { isAccepted: false, selectedValue: this.getSelectedValue() });
		}
	},
	onPopupDestroy: function()
	{
		if(this._popup)
		{
			this._popup = null;
		}
	}
};
if(typeof(BX.CrmRequisitePresetSelectDialog.messages) === "undefined")
{
	BX.CrmRequisitePresetSelectDialog.messages = {};
}
BX.CrmRequisitePresetSelectDialog.windows = {};
BX.CrmRequisitePresetSelectDialog.create = function(id, settings)
{
	var self = new BX.CrmRequisitePresetSelectDialog();
	self.initialize(id, settings);
	return self;
};
//endregion
//region BX.CrmRequisiteConverter
BX.CrmRequisiteConverter = function()
{
	this._id = "";
	this._settings = {};
	this._entityTypeName = "";
	this._serviceUrl = "";
	this._presetId = 0;
	this._presetList = null;

	this._presetListLoader = null;
	this._presetListLoadHandler = BX.delegate(this.onPresetListLoad, this);

	this._presetSelector = null;
	this._presetSelectHandler = BX.delegate(this.onPresetSelect, this);

	this._processDialog = null;
	this._processStateChangeHandler = BX.delegate(this.onProcessStateChange, this);
};
BX.CrmRequisiteConverter.prototype =
{
	initialize: function(id, settings)
	{
		this._id = id;
		this._settings = settings ? settings : {};

		this._entityTypeName = this.getSetting("entityTypeName", "");
		if(!BX.type.isNotEmptyString(this._entityTypeName))
		{
			throw "BX.CrmRequisiteConverter. Could not find 'entityTypeName' parameter.";
		}
		this._entityTypeName = this._entityTypeName.toUpperCase();

		this._serviceUrl = this.getSetting("serviceUrl", "");
		if(!BX.type.isNotEmptyString(this._serviceUrl))
		{
			throw "BX.CrmRequisiteConverter. Could not find 'serviceUrl' parameter.";
		}
	},
	getId: function()
	{
		return this._id;
	},
	getSetting: function (name, defaultval)
	{
		return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	},
	getMessage:function(name)
	{
		var m = BX.CrmRequisiteConverter.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	},
	convert: function()
	{
		if(this._presetId > 0)
		{
			this.openProcessDialog();
		}
		else
		{
			if(this._presetList === null)
			{
				this.openPresetListLoader();
			}
			else
			{
				this.openPresetSelector();
			}
		}
	},
	skip: function()
	{
		BX.ajax(
			{
				url: this._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
				{
					"ACTION" : "SKIP_CONVERT_REQUISITES",
					"PARAMS": {}
				},
				onsuccess: BX.delegate(this._onRequestSuccess, this),
				onfailure: BX.delegate(this._onRequestFailure, this)
			}
		);
	},
	openPresetListLoader: function()
	{
		if(!this._presetListLoader)
		{
			this._presetListLoader = BX.CrmRequisitePresetListLoader.create(
				this._id,
				{
					entityTypeName: this._entityTypeName,
					serviceUrl: this._serviceUrl,
					callback: this._presetListLoadHandler
				}
			);
		}
		this._presetListLoader.start();
	},
	onPresetListLoad: function(sender, params)
	{
		this._presetList = params["isSuccessed"] ? params["resultData"] : [];
		this.openPresetSelector();
	},
	openPresetSelector: function()
	{
		if(!this._presetSelector)
		{
			this._presetSelector = BX.CrmRequisitePresetSelectDialog.create(
				this._id,
				{
					list: this._presetList,
					callback: this._presetSelectHandler
				}
			);
		}
		this._presetSelector.show();
	},
	onPresetSelect: function(sender, params)
	{
		if(this._presetSelector)
		{
			if(params["isAccepted"])
			{
				this._presetId = parseInt(params["selectedValue"]);
				this.openProcessDialog();
			}
			this._presetSelector.close();
		}
	},
	openProcessDialog: function()
	{
		if(!this._processDialog)
		{
			var entityTypeNameC = this._entityTypeName.toLowerCase().replace(/(?:^)\S/, function(c){ return c.toUpperCase(); });
			var key = "convert" + entityTypeNameC + "Requisites";

			this._processDialog = BX.CrmLongRunningProcessDialog.create(
				key,
				{
					serviceUrl: this._serviceUrl,
					action: "CONVERT_REQUISITES",
					params:
					{
						"ENTITY_TYPE_NAME": this._entityTypeName,
						"PRESET_ID": this._presetId
					},
					title: this.getMessage("processDialogTitle"),
					summary: this.getMessage("processDialogSummary")
				}
			);

			BX.addCustomEvent(this._processDialog, "ON_STATE_CHANGE", this._processStateChangeHandler);
		}

		this._processDialog.show();
	},
	closeProcessDialog: function()
	{
		if(this._processDialog)
		{
			this._processDialog.close();
			this._processDialog = null;
		}
	},
	onProcessStateChange: function(sender)
	{
		if(sender.getState() === BX.CrmLongRunningProcessState.completed)
		{
			//ON_CONTACT_REQUISITE_TRANFER_COMPLETE, ON_COMPANY_REQUISITE_TRANFER_COMPLETE
			BX.onCustomEvent(this, "ON_" + this._entityTypeName + "_REQUISITE_TRANFER_COMPLETE", [this]);
		}
	}
};
if(typeof(BX.CrmRequisiteConverter.messages) === "undefined")
{
	BX.CrmRequisiteConverter.messages = {};
}
BX.CrmRequisiteConverter.create = function(id, settings)
{
	var self = new BX.CrmRequisiteConverter();
	self.initialize(id, settings);
	return self;
};
//endregion
//region BX.CrmPSRequisiteConverter
BX.CrmPSRequisiteConverter = function()
{
	this._id = "";
	this._settings = {};
	this._serviceUrl = "";

	this._processDialog = null;
	this._processStateChangeHandler = BX.delegate(this.onProcessStateChange, this);
};
BX.CrmPSRequisiteConverter.prototype =
{
	initialize: function(id, settings)
	{
		this._id = id;
		this._settings = settings ? settings : {};

		this._serviceUrl = this.getSetting("serviceUrl", "");
		if(!BX.type.isNotEmptyString(this._serviceUrl))
		{
			throw "BX.CrmPSRequisiteConverter. Could not find 'serviceUrl' parameter.";
		}
	},
	getId: function()
	{
		return this._id;
	},
	getSetting: function (name, defaultval)
	{
		return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	},
	getMessage:function(name)
	{
		var m = BX.CrmPSRequisiteConverter.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	},
	convert: function()
	{
		this.openProcessDialog();
	},
	skip: function()
	{
		BX.ajax(
			{
				url: this._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
				{
					"ACTION" : "SKIP_CONVERT_PS_REQUISITES",
					"PARAMS": {}
				},
				onsuccess: BX.delegate(this._onRequestSuccess, this),
				onfailure: BX.delegate(this._onRequestFailure, this)
			}
		);
	},
	openProcessDialog: function()
	{
		if(!this._processDialog)
		{
			this._processDialog = BX.CrmLongRunningProcessDialog.create(
				"convertPSRequisites",
				{
					serviceUrl: this._serviceUrl,
					action: "CONVERT_PS_REQUISITES",
					params:
					{
						"ENTITY_TYPE_NAME": this._entityTypeName,
						"PRESET_ID": this._presetId
					},
					title: this.getMessage("processDialogTitle"),
					summary: this.getMessage("processDialogSummary")
				}
			);

			BX.addCustomEvent(this._processDialog, "ON_STATE_CHANGE", this._processStateChangeHandler);
		}

		this._processDialog.show();
	},
	closeProcessDialog: function()
	{
		if(this._processDialog)
		{
			this._processDialog.close();
			this._processDialog = null;
		}
	},
	onProcessStateChange: function(sender)
	{
		if(sender.getState() === BX.CrmLongRunningProcessState.completed)
		{
			BX.onCustomEvent(this, "ON_PS_REQUISITE_TRANFER_COMPLETE", [this]);
		}
	}
};
if(typeof(BX.CrmPSRequisiteConverter.messages) === "undefined")
{
	BX.CrmPSRequisiteConverter.messages = {};
}
BX.CrmPSRequisiteConverter.create = function(id, settings)
{
	var self = new BX.CrmPSRequisiteConverter();
	self.initialize(id, settings);
	return self;
};
//endregion
//region BX.CrmDealCategory
if(typeof(BX.CrmDealCategory) === "undefined")
{
	BX.CrmDealCategory = function()
	{
	};

	BX.CrmDealCategory.getDefaultValue = function()
	{
		return "0";
	};
	BX.CrmDealCategory.getListItems = function(infos)
	{
		if(!BX.type.isArray(infos))
		{
			infos = BX.CrmDealCategory.infos;
		}

		var results = [];
		for(var i = 0, l = infos.length; i < l; i++)
		{
			var info = infos[i];
			results.push({ value: info["id"], text: info["name"] });
		}
		return results;
	};
	BX.CrmDealCategory.getCount = function()
	{
		return BX.CrmDealCategory.infos.length;
	};
	if(typeof(BX.CrmDealCategory.infos) === "undefined")
	{
		BX.CrmDealCategory.infos = [];
	}
}
//endregion
//region BX.CrmDealCategorySelector
if(typeof(BX.CrmDealCategorySelector) === "undefined")
{
	BX.CrmDealCategorySelector = function()
	{
		this._id = "";
		this._settings = {};
		this._selectorMenu = null;
		this._menuItemSelectHandler = BX.delegate(this.onMenuItemSelect, this);
		this._canCreateCategory = false;
		this._createUrl = "";
		this._categoryListUrl = "";
		this._categoryCreateUrl = "";
	};

	BX.CrmDealCategorySelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._canCreateCategory = !!this.getSetting("canCreateCategory", false);
			this._createUrl = this.getSetting("createUrl", "");
			this._categoryListUrl = this.getSetting("categoryListUrl", "");
			this._categoryCreateUrl = this.getSetting("categoryCreateUrl", "");
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var m = BX.CrmDealCategorySelector.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		redirectToCreateUrl: function(categoryId)
		{
			if(this._createUrl === "")
			{
				return;
			}

			BX.Crm.Page.open(BX.util.add_url_param(this._createUrl, { "category_id": categoryId }));
		},
		openMenu: function(anchor)
		{
			if(!this.getSelectorMenu().isOpened())
			{
				this.getSelectorMenu().open(anchor);
			}
		},
		getSelectorMenu: function()
		{
			if(!this._selectorMenu)
			{
				var items = BX.CrmDealCategory.getListItems();
				if(this._canCreateCategory)
				{
					items.push({ text: this.getMessage("create"), value: "new" });
				}
				this._selectorMenu = BX.CmrSelectorMenu.create(this._id, { items: items });
				this._selectorMenu.addOnSelectListener(this._menuItemSelectHandler);
			}

			return this._selectorMenu;
		},
		onMenuItemSelect: function(sender, selectedItem)
		{
			var selectedValue = selectedItem.getValue();
			if(this._selectorMenu.isOpened())
			{
				this._selectorMenu.close();
			}

			if(selectedValue === "new")
			{
				window.location = this._categoryCreateUrl;
			}
			else
			{
				this.redirectToCreateUrl(parseInt(selectedValue));
			}
		}
	};

	if(typeof(BX.CrmDealCategorySelector.messages) === "undefined")
	{
		BX.CrmDealCategorySelector.messages = {};
	}
	BX.CrmDealCategorySelector.items = {};
	BX.CrmDealCategorySelector.create = function(id, settings)
	{
		var self = new BX.CrmDealCategorySelector();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
//endregion
//region BX.CrmDealCategorySelectDialog
if(typeof(BX.CrmDealCategorySelectDialog) === "undefined")
{
	BX.CrmDealCategorySelectDialog = function()
	{
		this._id = "";
		this._settings = {};
		this._popup = null;
		this._selector = null;
		this._value = "";
		this._isOpened = false;
		this._closeNotifier = null;
	};
	BX.CrmDealCategorySelectDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._value = parseInt(this.getSetting("value", 0));
			if(isNaN(this._value))
			{
				this._value = 0;
			}
			this._closeNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var m = BX.CrmDealCategorySelectDialog.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		isOpened: function()
		{
			return this._isOpened;
		},
		open: function()
		{
			if(this._isOpened)
			{
				return;
			}

			this._popup = new BX.PopupWindow(
				this._id,
				null,
				{
					autoHide: false,
					draggable: true,
					offsetLeft: 0,
					offsetTop: 0,
					bindOptions: { forceBindPosition: true },
					closeByEsc: true,
					closeIcon: { top: "10px", right: "15px" },
					titleBar: this.getMessage("title"),
					content: this.prepareContent(),
					events:
					{
						onPopupShow: BX.delegate(this.onPopupShow, this),
						onPopupClose: BX.delegate(this.onPopupClose, this),
						onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
					},
					buttons: this.prepareButtons()
				}
			);
			this._popup.show();
		},
		close: function()
		{
			if (this._popup)
			{
				this._popup.close();
			}
		},
		addCloseListener: function(listener)
		{
			this._closeNotifier.addListener(listener);
		},
		removeCloseListener: function(listener)
		{
			this._closeNotifier.removeListener(listener);
		},
		getFilteredCategories: function()
		{
			var categoryIds = BX.prop.getArray(this._settings, "categoryIds", []);
			if(categoryIds.length === 0)
			{
				return BX.CrmDealCategory.infos;
			}

			var results = BX.CrmDealCategory.infos.filter(
				function(info)
				{
					for(var i = 0, length = categoryIds.length; i < length; i++)
					{
						if(info["id"] == categoryIds[i])
						{
							return true;
						}
					}

					return false;
				}
			);
			return results;
		},
		prepareContent: function()
		{
			var table = BX.create("TABLE",
				{
					attrs:
					{
						className: "bx-crm-deal-category-selector-dialog",
						cellspacing: "2"
					}
				}
			);
			var r, c;
			r = table.insertRow(-1);
			c = r.insertCell(-1);
			c.appendChild(BX.create("LABEL", { text: this.getMessage("field") + ":" }));
			c = r.insertCell(-1);
			this._selector = BX.create("SELECT", {});

			var items = BX.CrmDealCategory.getListItems(this.getFilteredCategories());
			BX.HtmlHelper.setupSelectOptions(this._selector, items);
			if(items.length > 0)
			{
				this._selector.value = this._value >= 0 ? this._value : items[0].value;
			}

			c.appendChild(this._selector);

			return table;
		},
		prepareButtons: function()
		{
			return(
				[
					new BX.PopupWindowButton(
						{
							text: this.getMessage("saveButton"),
							className: "popup-window-button-accept",
							events: { click: BX.delegate(this.processSave, this) }
						}
					),
					new BX.PopupWindowButtonLink(
						{
							text: this.getMessage("cancelButton"),
							className: "popup-window-button-link-cancel",
							events: { click: BX.delegate(this.processCancel, this) }
						}
					)
				]);
		},
		getValue: function()
		{
			return this._value;
		},
		setValue: function(value)
		{
			value = parseInt(value);
			if(isNaN(value))
			{
				value = 0;
			}
			this._value = value;
		},
		processSave: function()
		{
			this._value = parseInt(this._selector.value);
			if(isNaN(this._value))
			{
				this._value = 0;
			}

			this._closeNotifier.notify([{ isCanceled: false }]);
			this.close();
		},
		processCancel: function()
		{
			this._closeNotifier.notify([{ isCanceled: true }]);
			this.close();
		},
		onPopupShow: function()
		{
			this._isOpened = true;
		},
		onPopupClose: function()
		{
			if(this._popup)
			{
				this._popup.destroy();
			}
		},
		onPopupDestroy: function()
		{
			this._isOpened = false;
			this._popup = null;
		}
	};

	if(typeof(BX.CrmDealCategorySelectDialog.messages) === "undefined")
	{
		BX.CrmDealCategorySelectDialog.messages = {};
	}
	BX.CrmDealCategorySelectDialog.create = function(id, settings)
	{
		var self = new BX.CrmDealCategorySelectDialog();
		self.initialize(id, settings);
		return self;
	};
}
//endregion

if(typeof(BX.CrmEntityManager) === "undefined")
{
	BX.CrmEntityManager = function()
	{
		this._id = "";
		this._settings = {};
	};

	BX.CrmEntityManager.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = id;
				this._settings = settings ? settings : {};
			},
			getEntityCreateUrl: function(entityTypeName)
			{
				return BX.prop.getString(
					BX.CrmEntityManager.entityCreateUrls,
					entityTypeName,
					""
				);
			},
			innerCreateEntity: function(entityType, options)
			{
				var url = this.getEntityCreateUrl(entityType);
				if(url === "")
				{
					throw "BX.CrmEntityManager.innerCreateEntity: Could not find create URL for type " + entityType;
				}

				var urlParams = BX.prop.getObject(options, "urlParams", null);
				if(urlParams)
				{
					url = BX.util.add_url_param(url, urlParams);
				}
				return BX.Crm.Page.open(url, { openInNewWindow: true });
			},
			createEntity: function(entityType, options)
			{
				if(BX.type.isNumber(entityType) || BX.CrmEntityType.verifyName(entityType) === "")
				{
					entityType = BX.CrmEntityType.resolveName(entityType);
				}

				if(!BX.type.isPlainObject(options))
				{
					options = {};
				}

				var promise = new BX.Promise();
				if(entityType === BX.CrmEntityType.names.deal && BX.CrmDealCategory.getCount() > 1)
				{
					var dialog = BX.CrmDealCategorySelectDialog.create(this._id, { value: 0 });
					dialog.addCloseListener(
						function(sender, args)
						{
							if(!(BX.type.isBoolean(args["isCanceled"]) && args["isCanceled"] === false))
							{
								promise.reject({ isCanceled: true });
							}
							else
							{
								var value =  sender.getValue();
								if(value >= 0)
								{
									options["urlParams"] = BX.mergeEx(
										BX.prop.getObject(options, "urlParams", {}),
										{ category_id: value }
									);
								}
								promise.fulfill({ wnd: this.innerCreateEntity(BX.CrmEntityType.names.deal, options) });
							}
						}.bind(this)
					);
					dialog.open();
				}
				else
				{
					window.setTimeout(
						function(){ promise.fulfill({ wnd: this.innerCreateEntity(entityType, options) }); }.bind(this),
						0
					);
				}
				return promise;
			}
		};

	BX.CrmEntityManager.entityCreateUrls = {};
	BX.CrmEntityManager.current = null;

	BX.CrmEntityManager.getCurrent = function()
	{
		if(!this._current)
		{
			this._current = this.create("current", {});
		}
		return this._current;
	};
	BX.CrmEntityManager.createEntity = function(entityType, options)
	{
		return this.getCurrent().createEntity(entityType, options);
	};
	BX.CrmEntityManager.create = function(id, settings)
	{
		var self = new BX.CrmEntityManager();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHtmlLoader) === "undefined")
{
	BX.CrmHtmlLoader = function()
	{
		this._id = "";
		this._settings = {};
		this._params = {};
		this._serviceUrl = "";
		this._requestIsRunning = false;
		this._button = null;
		this._wrapper = null;
		this._buttonClickHandler = BX.delegate(this.onButtonClick, this);
	};
	BX.CrmHtmlLoader.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._serviceUrl = this.getSetting("serviceUrl");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.CrmHtmlLoader: service url not found!";
			}

			this._action = this.getSetting("action");
			if(!BX.type.isNotEmptyString(this._action))
			{
				throw "BX.CrmHtmlLoader: action not found!";
			}

			this._params = this.getSetting("params", {});

			this._button = BX(this.getSetting("button"));
			if(!BX.type.isElementNode(this._button))
			{
				throw "BX.CrmHtmlLoader: button element not found!";
			}
			BX.bind(this._button, "click", this._buttonClickHandler);

			this._wrapper = BX(this.getSetting("wrapper"));
			if(!BX.type.isElementNode(this._wrapper))
			{
				throw "BX.CrmHtmlLoader: wrapper element not found!";
			}

		},
		release: function()
		{
			BX.unbind(this._button, "click", this._buttonClickHandler);
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		onButtonClick: function(e)
		{
			this.startRequest();
			return BX.PreventDefault(e);
		},
		startRequest: function()
		{
			if(this._requestIsRunning)
			{
				return;
			}
			this._requestIsRunning = true;
			BX.showWait();

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION": this._action,
						"PARAMS": this._params
					},
					onsuccess: BX.delegate(this.onRequestSuccess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
		},
		onRequestSuccess: function(result)
		{
			BX.closeWait();
			this._requestIsRunning = false;

			if(BX.type.isPlainObject(result["ERROR"]))
			{
				this.showError(result["ERROR"]);
				return;
			}

			if(BX.type.isPlainObject(result["DATA"]))
			{
				var data = result["DATA"];
				if(BX.type.isNotEmptyString(data["HTML"]))
				{
					this._wrapper.innerHTML = data["HTML"];
				}
				else if(BX.type.isNotEmptyString(data["TEXT"]))
				{
					this._wrapper.innerHTML = BX.util.htmlspecialchars(data["TEXT"]);
				}
			}
		},
		onRequestFailure: function(result)
		{
			BX.closeWait();
			this._requestIsRunning = false;
		},
		showError: function(error)
		{
			if(BX.type.isPlainObject(error) && BX.type.isNotEmptyString(error["MESSAGE"]))
			{
				alert(error["MESSAGE"]);
			}
		}
	};
	BX.CrmHtmlLoader.create = function(id, settings)
	{
		var self = new BX.CrmHtmlLoader();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDataLoader) === "undefined")
{
	BX.CrmDataLoader = function()
	{
		this._id = "";
		this._settings = {};
		this._params = {};
		this._serviceUrl = "";
		this._requestIsRunning = false;
		this._notifier = null;
		this._result = null;
	};
	BX.CrmDataLoader.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._serviceUrl = this.getSetting("serviceUrl");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.CrmDataLoader: service url not found!";
			}

			this._action = this.getSetting("action");
			if(!BX.type.isNotEmptyString(this._action))
			{
				throw "BX.CrmDataLoader: action not found!";
			}

			this._params = this.getSetting("params", {});

			this._notifier = BX.CrmNotifier.create(this);
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getResult: function()
		{
			return this._result;
		},
		isRequestRunning: function()
		{
			return this._requestIsRunning;
		},
		addCallBack: function(callback)
		{
			if(!BX.type.isFunction(callback))
			{
				return;
			}

			for(var i = 0; this._callbacks.length; i++)
			{
				if(this._callbacks[i] === callback)
				{
					return;
				}
			}

			this._callbacks.push(callback);
		},
		load: function(callback)
		{
			if(!BX.type.isFunction(callback))
			{
				callback = null;
			}

			if(this._result === null)
			{
				this._notifier.addListener(callback);
				this.startRequest();
			}
			else if(callback !== null)
			{
				callback(this._result);
			}
		},
		startRequest: function()
		{
			if(this._requestIsRunning)
			{
				return;
			}

			this._requestIsRunning = true;
			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data: { "ACTION": this._action, "PARAMS": this._params },
					onsuccess: BX.delegate(this.onRequestSuccess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
		},
		onRequestSuccess: function(result)
		{
			BX.closeWait();
			this._requestIsRunning = false;

			this._result = BX.type.isPlainObject(result) ? result : {};

			this._notifier.notify([ this._result ]);
			this._notifier.resetListeners();
		},
		onRequestFailure: function(result)
		{
			BX.closeWait();
			this._requestIsRunning = false;

			this._result = BX.type.isPlainObject(result) ? result : {};
			this._notifier.notify([ this._result ]);
			this._notifier.resetListeners();
		}
	};
	BX.CrmDataLoader.create = function(id, settings)
	{
		var self = new BX.CrmDataLoader();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmRemoteAction))
{
	BX.CrmRemoteAction = function()
	{
		this._id = "";
		this._settings = {};
		this._serviceUrl = "";
		this._redirectUrl = "";
	};
	BX.CrmRemoteAction.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.CrmRemoteAction: service url not found!";
			}

			this._redirectUrl = this.getSetting("redirectUrl", "");
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		execute: function(redirectUrl)
		{
			if(BX.type.isNotEmptyString(redirectUrl))
			{
				this._redirectUrl = redirectUrl;
			}

			BX.ajax(
				{
					method: "POST",
					dataType: "html",
					url: this._serviceUrl,
					data: this.getSetting("data", {}),
					onsuccess: BX.delegate(this.onActionSuccess, this)
				}
			);
		},
		onActionSuccess: function(data)
		{
			if(BX.type.isNotEmptyString(this._redirectUrl))
			{
				document.location.href = this._redirectUrl;
			}
		}
	};
	BX.CrmRemoteAction.items = {};
	BX.CrmRemoteAction.create = function(id, settings)
	{
		var self = new BX.CrmRemoteAction();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}

if(typeof(BX.CrmDeletionConfirmDialog) === "undefined")
{
	BX.CrmDeletionConfirmDialog = function()
	{
		this._id = "";
		this._settings = {};
		this._name = "";
		this._path = "";
		this._messages = {};
		this._dlg = null;
		this._closeNotifier = null;
	};
	BX.CrmDeletionConfirmDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._name = this.getSetting("name", "");
			this._path = this.getSetting("path", "");
			if(!BX.type.isNotEmptyString(this._path))
			{
				throw "BX.CrmDeletionConfirmDialog: Could not find parameter 'path'.";
			}

			this._messages = this.getSetting("messages", {});
			this._closeNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			return this._messages.hasOwnProperty(name) ? this._messages[name] : name;
		},
		open: function()
		{
			this._dlg = new BX.CDialog(
				{
					title: this.getMessage("title"),
					head: "",
					content: this.getMessage("confirm").replace(/#NAME#/gi, this._name),
					resizable: false,
					draggable: true,
					height: 70,
					width: 300
				}
			);

			this._dlg.SetButtons(
				[
					{
						title: this.getMessage("deleteButton"),
						id: "delete",
						action: BX.delegate(this.onAction, this)
					},
					BX.CDialog.btnClose
				]
			);
			this._dlg.Show();
		},
		close: function()
		{
			if(this._dlg)
			{
				this._dlg.Close();
			}
		},
		onAction: function()
		{
			this.close();
			window.location.href = this._path;
		}
	};
	BX.CrmDeletionConfirmDialog.create = function(id, settings)
	{
		var self = new BX.CrmDeletionConfirmDialog();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.FilterUserSelector) === "undefined")
{
	BX.FilterUserSelector = function()
	{
		this._id = "";
		this._settings = {};
		this._fieldId = "";
		this._control = null;

		this._currentUser = null;
		this._componentName = null;
		this._componentObj = null;
		this._componentContainer = null;
		this._serviceContainer = null;

		this._zIndex = 1100;
		this._isDialogDisplayed = false;
		this._dialog = null;

		this._inputKeyPressHandler = BX.delegate(this.onInputKeyPress, this);
		//this._externalClickHandler = BX.delegate(this.onExternalClick, this);
	};

	BX.FilterUserSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._fieldId = this.getSetting("fieldId", "");
			this._componentName = this.getSetting("componentName", "");
			this._componentContainer = BX(this._componentName + "_selector_content");

			this._serviceContainer = this.getSetting("serviceContainer", null);
			if(!BX.type.isDomNode(this._serviceContainer))
			{
				this._serviceContainer = document.body;
			}

			BX.addCustomEvent(window, "BX.Main.Filter:customEntityFocus", BX.delegate(this.onCustomEntitySelectorOpen, this));
			BX.addCustomEvent(window, "BX.Main.Filter:customEntityBlur", BX.delegate(this.onCustomEntitySelectorClose, this));
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name)  ? this._settings[name] : defaultval;
		},
		getSearchInput: function()
		{
			return this._control ? this._control.getLabelNode() : null;
		},
		isOpened: function()
		{
			return this._isDialogDisplayed;
		},
		open: function()
		{
			if(this._componentObj === null)
			{
				var objName = "O_" + this._componentName;
				if(!window[objName])
				{
					throw "BX.FilterUserSelector: Could not find '"+ objName +"' user selector.";
				}
				this._componentObj = window[objName];
			}

			var searchInput = this.getSearchInput();
			if(this._componentObj.searchInput)
			{
				BX.unbind(this._componentObj.searchInput, "keyup", BX.proxy(this._componentObj.search, this._componentObj));
			}
			this._componentObj.searchInput = searchInput;
			BX.bind(this._componentObj.searchInput, "keyup", BX.proxy(this._componentObj.search, this._componentObj));
			this._componentObj.onSelect = BX.delegate(this.onSelect, this);
			BX.bind(searchInput, "keyup", this._inputKeyPressHandler);
			//BX.bind(document, "click", this._externalClickHandler);

			if(this._currentUser)
			{
				this._componentObj.setSelected([ this._currentUser ]);
			}
			else
			{
				var selected = this._componentObj.getSelected();
				if(selected)
				{
					for(var key in selected)
					{
						if(selected.hasOwnProperty(key))
						{
							this._componentObj.unselect(key);
						}
					}
				}
				//this._componentObj.displayTab("last");
			}

			if(this._dialog === null)
			{
				this._componentContainer.style.display = "";
				this._dialog = new BX.PopupWindow(
					this._id,
					this.getSearchInput(),
					{
						autoHide: false,
						draggable: false,
						closeByEsc: true,
						offsetLeft: 0,
						offsetTop: 0,
						zIndex: this._zIndex,
						bindOptions: { forceBindPosition: true },
						content : this._componentContainer,
						events:
							{
								onPopupShow: BX.delegate(this.onDialogShow, this),
								onPopupClose: BX.delegate(this.onDialogClose, this),
								onPopupDestroy: BX.delegate(this.onDialogDestroy, this)
							}
					}
				);
			}

			this._dialog.show();
			this._componentObj._onFocus();

			if(this._control)
			{
				this._control.setPopupContainer(this._componentContainer);
			}
		},
		close: function()
		{
			var searchInput = this.getSearchInput();
			if(searchInput)
			{
				BX.unbind(searchInput, "keyup", this._inputKeyPressHandler);
			}

			if(this._dialog)
			{
				this._dialog.close();
			}

			if(this._control)
			{
				this._control.setPopupContainer(null);
			}

		},
		closeSiblings: function()
		{
			var siblings = BX.FilterUserSelector.items;
			for(var k in siblings)
			{
				if(siblings.hasOwnProperty(k) && siblings[k] !== this)
				{
					siblings[k].close();
				}
			}
		},
		onCustomEntitySelectorOpen: function(control)
		{
			var fieldId = control.getId();
			if(this._fieldId !== fieldId)
			{
				this._control = null;
				this.close();
			}
			else
			{
				this._control = control;
				if(this._control)
				{
					var current = this._control.getCurrentValues();
					this._currentUser = { "id": current["value"] };
				}
				this.closeSiblings();
				this.open();
			}
		},
		onCustomEntitySelectorClose: function(control)
		{
			if(this._fieldId === control.getId())
			{
				this._control = null;
				this.close();
			}
		},
		onDialogShow: function()
		{
			this._isDialogDisplayed = true;
		},
		onDialogClose: function()
		{
			this._componentContainer.parentNode.removeChild(this._componentContainer);
			this._serviceContainer.appendChild(this._componentContainer);
			this._componentContainer.style.display = "none";

			this._dialog.destroy();
			this._isDialogDisplayed = false;
		},
		onDialogDestroy: function()
		{
			this._dialog = null;
		},
		onInputKeyPress: function(e)
		{
			if(!this._dialog || !this._isDialogDisplayed)
			{
				this.open();
			}

			if(this._componentObj)
			{
				this._componentObj.search();
			}
		},
		/*
		 onExternalClick: function(e)
		 {
		 if(!e)
		 {
		 e = window.event;
		 }

		 if(!this._isDialogDisplayed)
		 {
		 return;
		 }

		 if(BX.getEventTarget(e) !== this.getSearchInput())
		 {
		 this.close();
		 }
		 },
		 */
		onSelect: function(user)
		{
			this._currentUser = user;
			if(this._control)
			{
				//CRUTCH: Intranet User Selector already setup input value.
				var node = this._control.getLabelNode();
				node.value = "";
				this._control.setData(user["name"], user["id"]);
			}
			this.close();
		}
	};
	BX.FilterUserSelector.closeAll = function()
	{
		for(var k in this.items)
		{
			if(this.items.hasOwnProperty(k))
			{
				this.items[k].close();
			}
		}
	};
	BX.FilterUserSelector.items = {};
	BX.FilterUserSelector.create = function(id, settings)
	{
		var self = new BX.FilterUserSelector(id, settings);
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}

if(typeof(BX.CrmUIFilterEntitySelector) === "undefined")
{
	BX.CrmUIFilterEntitySelector = function()
	{
		this._id = "";
		this._settings = {};
		this._fieldId = "";
		this._control = null;
		this._entitySelector = null;
		this._filterOpenHandler = BX.delegate(this.onCustomEntitySelectorOpen, this);
		this._filterCloseHandler = BX.delegate(this.onCustomEntitySelectorClose, this);
	};

	BX.CrmUIFilterEntitySelector.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = id;
				this._settings = settings ? settings : {};
				this._fieldId = this.getSetting("fieldId", "");

				BX.addCustomEvent(window, "BX.Main.Filter:customEntityFocus", this._filterOpenHandler);
				BX.addCustomEvent(window, "BX.Main.Filter:customEntityBlur", this._filterCloseHandler);
			},
			release: function ()
			{
				BX.removeCustomEvent(window, "BX.Main.Filter:customEntityFocus", this._filterOpenHandler);
				BX.removeCustomEvent(window, "BX.Main.Filter:customEntityBlur", this._filterCloseHandler);
			},
			getId: function()
			{
				return this._id;
			},
			getSetting: function (name, defaultval)
			{
				return this._settings.hasOwnProperty(name)  ? this._settings[name] : defaultval;
			},
			getSearchInput: function()
			{
				return this._control ? this._control.getLabelNode() : null;
			},
			onCustomEntitySelectorOpen: function(control)
			{
				var fieldId = control.getId();
				if(this._fieldId !== fieldId)
				{
					this._control = null;
					this.close();
				}
				else
				{
					this._control = control;
					/*if(this._control)
					{
						var current = this._control.getCurrentValues();
						this._currentValues = current["value"];
					}*/
					this.closeSiblings();
					this.open();
				}
			},
			onCustomEntitySelectorClose: function(control)
			{
				if(this._fieldId === control.getId())
				{
					this._control = null;
					this.close();
				}
			},
			onSelect: function(sender, data)
			{
				if(!this._control)
				{
					return;
				}

				var labels = [];
				var values = {};
				for(var typeName in data)
				{
					if(!data.hasOwnProperty(typeName))
					{
						continue;
					}

					var infos = data[typeName];
					for(var i = 0, l = infos.length; i < l; i++)
					{
						var info = infos[i];
						labels.push(info["title"]);
						if(typeof(values[typeName]) === "undefined")
						{
							values[typeName] = [];
						}

						values[typeName].push(info["entityId"]);
					}
				}
				//this._currentValues = values;
				this._control.setData(labels.join(", "), JSON.stringify(values));
			},
			open: function()
			{
				if(!this._entitySelector)
				{
					this._entitySelector = BX.CrmEntitySelector.create(
						this._id,
						{
							entityTypeNames: this.getSetting("entityTypeNames", []),
							isMultiple: this.getSetting("isMultiple", false),
							title: this.getSetting("title", "")
						}
					);

					BX.addCustomEvent(this._entitySelector, "BX.CrmEntitySelector:select", BX.delegate(this.onSelect, this));
				}

				this._entitySelector.open(this.getSearchInput());
				if(this._control)
				{
					this._control.setPopupContainer(this._entitySelector.getPopup()["contentContainer"]);
				}
			},
			close: function()
			{
				if(this._entitySelector)
				{
					this._entitySelector.close();

					if(this._control)
					{
						this._control.setPopupContainer(null);
					}
				}
			},
			closeSiblings: function()
			{
				var siblings = BX.CrmUIFilterEntitySelector.items;
				for(var k in siblings)
				{
					if(siblings.hasOwnProperty(k) && siblings[k] !== this)
					{
						siblings[k].close();
					}
				}
			}
		};

	BX.CrmUIFilterEntitySelector.items = {};
	BX.CrmUIFilterEntitySelector.remove = function(id)
	{
		var item = BX.prop.get(this.items, id, null);
		if(item)
		{
			item.release();
			delete this.items[id];
		}
	};
	BX.CrmUIFilterEntitySelector.create = function(id, settings)
	{
		var self = new BX.CrmUIFilterEntitySelector(id, settings);
		self.initialize(id, settings);
		BX.CrmUIFilterEntitySelector.items[self.getId()] = self;
		return self;
	}
}

if(typeof(BX.CrmEntitySelector) === "undefined")
{
	BX.CrmEntitySelector = function()
	{
		this._id = "";
		this._settings = {};
		this._entityTypeNames = [];
		this._isMultiple = false;
		this._entityInfos = null;
		this._entitySelectHandler = BX.delegate(this.onEntitySelect, this);
	};
	BX.CrmEntitySelector.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = id;
				this._settings = settings ? settings : {};
				this._entityTypeNames = this.getSetting("entityTypeNames", []);
				this._isMultiple = this.getSetting("isMultiple", false);
				this._entityInfos = [];
			},
			getId: function()
			{
				return this._id;
			},
			getSetting: function (name, defaultval)
			{
				return this._settings.hasOwnProperty(name)  ? this._settings[name] : defaultval;
			},
			getMessage: function(name)
			{
				var msg = BX.CrmEntitySelector.messages;
				return msg.hasOwnProperty(name) ? msg[name] : name;
			},
			isOpened: function()
			{
				return ((obCrm[this._id].popup instanceof BX.PopupWindow) && obCrm[this._id].popup.isShown());
			},
			open: function(anchor)
			{
				if(typeof(obCrm[this._id]) === "undefined")
				{
					var entityTypes = [];
					for(var i = 0, l = this._entityTypeNames.length; i < l; i++)
					{
						entityTypes.push(this._entityTypeNames[i].toLowerCase());
					}

					obCrm[this._id] = new CRM(
						this._id,
						null,
						null,
						this._id,
						this._entityInfos,
						false,
						this._isMultiple,
						entityTypes,
						{
							"contact": BX.CrmEntityType.getCaptionByName(BX.CrmEntityType.names.contact),
							"company": BX.CrmEntityType.getCaptionByName(BX.CrmEntityType.names.company),
							"invoice": BX.CrmEntityType.getCaptionByName(BX.CrmEntityType.names.invoice),
							"quote": BX.CrmEntityType.getCaptionByName(BX.CrmEntityType.names.quote),
							"lead": BX.CrmEntityType.getCaptionByName(BX.CrmEntityType.names.lead),
							"deal": BX.CrmEntityType.getCaptionByName(BX.CrmEntityType.names.deal),
							"ok": this.getMessage("selectButton"),
							"cancel": BX.message("JS_CORE_WINDOW_CANCEL"),
							"close": BX.message("JS_CORE_WINDOW_CLOSE"),
							"wait": BX.message("JS_CORE_LOADING"),
							"noresult": this.getMessage("noresult"),
							"search" : this.getMessage("search"),
							"last" : this.getMessage("last")
						},
						true
					);
					obCrm[this._id].Init();
					obCrm[this._id].AddOnSaveListener(this._entitySelectHandler);
				}

				if(!((obCrm[this._id].popup instanceof BX.PopupWindow) && obCrm[this._id].popup.isShown()))
				{
					if(!BX.type.isDomNode(anchor))
					{
						anchor = BX.prop.getElementNode(this._settings, "anchor", null);
					}
					obCrm[this._id].Open(
						{
							closeIcon: { top: "10px", right: "15px" },
							closeByEsc: true,
							autoHide: false,
							gainFocus: false,
							anchor: anchor,
							titleBar: this.getSetting("title", "")
						}
					);
				}
			},
			close: function()
			{
				if(typeof(obCrm[this._id]) !== "undefined")
				{
					obCrm[this._id].RemoveOnSaveListener(this._entitySelectHandler);
					obCrm[this._id].Clear();
					delete obCrm[this._id];
				}

			},
			getPopup: function()
			{
				return typeof(obCrm[this._id]) !== "undefined" ? obCrm[this._id].popup : null;
			},
			onEntitySelect: function(settings)
			{
				this.close();

				var data = {};
				this._entityInfos = [];
				for(var type in settings)
				{
					if(!settings.hasOwnProperty(type))
					{
						continue;
					}

					var entityInfos = settings[type];
					if(!BX.type.isPlainObject(entityInfos))
					{
						continue;
					}

					var typeName = type.toUpperCase();
					for(var key in entityInfos)
					{
						if(!entityInfos.hasOwnProperty(key))
						{
							continue;
						}

						var entityInfo = entityInfos[key];
						this._entityInfos.push(
							{
								"id": entityInfo["id"],
								"type": entityInfo["type"],
								"title": entityInfo["title"],
								"desc": entityInfo["desc"],
								"url": entityInfo["url"],
								"image": entityInfo["image"],
								"selected": "Y"
							}
						);

						var entityId = BX.type.isNotEmptyString(entityInfo["id"]) ? parseInt(entityInfo["id"]) : 0;
						if(entityId > 0)
						{
							if(typeof(data[typeName]) === "undefined")
							{
								data[typeName] = [];
							}

							data[typeName].push(
								{
									entityTypeName: typeName,
									entityId: entityId,
									title: BX.type.isNotEmptyString(entityInfo["title"]) ? entityInfo["title"] : ("[" + entityId + "]")
								}
							);
						}
					}
				}

				BX.onCustomEvent(this, "BX.CrmEntitySelector:select", [this, data]);
			}
		};

	if(typeof(BX.CrmEntitySelector.messages) === "undefined")
	{
		BX.CrmEntitySelector.messages =
			{
			};
	}
	BX.CrmEntitySelector.closeAll = function()
	{
		for(var k in this.items)
		{
			if(this.items.hasOwnProperty(k))
			{
				this.items[k].close();
			}
		}
	};
	BX.CrmEntitySelector.items = {};
	BX.CrmEntitySelector.create = function(id, settings)
	{
		var self = new BX.CrmEntitySelector(id, settings);
		self.initialize(id, settings);
		BX.CrmEntitySelector.items[self.getId()] = self;
		return self;
	}
}

if(typeof(BX.CrmSearchContentManager) === "undefined")
{
	BX.CrmSearchContentManager = function()
	{
		this._id = "";
		this._settings = {};
		this._entityTypeName = "";
		this._processDialogs = {};
	};
	BX.CrmSearchContentManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_search_content_mgr_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._entityTypeName = this.getSetting("entityTypeName", "");
			if(!BX.type.isNotEmptyString(this._entityTypeName))
			{
				throw "BX.CrmSearchContentManager. Could not find entity type name.";
			}

			this._entityTypeName = this._entityTypeName.toUpperCase();
		},
		getId: function()
		{
			return this._id;
		},
		getEntityTypeName: function()
		{
			return this._entityTypeName;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getMessage: function(name)
		{
			var m = BX.CrmSearchContentManager.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		rebuildIndex: function()
		{
			var serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(serviceUrl))
			{
				throw "BX.CrmSearchContentManager. Could not find service url.";
			}

			var entityTypeNameC = this._entityTypeName.toLowerCase().replace(/(?:^)\S/, function(c){ return c.toUpperCase(); });
			var key = "rebuild" + entityTypeNameC;

			var processDlg = null;
			if(typeof(this._processDialogs[key]) !== "undefined")
			{
				processDlg = this._processDialogs[key];
			}
			else
			{
				processDlg = BX.CrmLongRunningProcessDialog.create(
					key,
					{
						serviceUrl: serviceUrl,
						action:"REBUILD_SEARCH_CONTENT",
						params:{ "ENTITY_TYPE_NAME": this._entityTypeName },
						title: this.getMessage(key + "DlgTitle"),
						summary: this.getMessage(key + "DlgSummary")
					}
				);

				this._processDialogs[key] = processDlg;
				BX.addCustomEvent(processDlg, 'ON_STATE_CHANGE', BX.delegate(this._onProcessStateChange, this));
			}
			processDlg.show();
		},
		_onProcessStateChange: function(sender)
		{
			var key = sender.getId();
			if(typeof(this._processDialogs[key]) !== "undefined")
			{
				var processDlg = this._processDialogs[key];
				if(processDlg.getState() === BX.CrmLongRunningProcessState.completed)
				{
					//ON_CONTACT_SEARCH_CONTENT_REBUILD_COMPLETE
					BX.onCustomEvent(this, "ON_" + this._entityTypeName + "_SEARCH_CONTENT_REBUILD_COMPLETE", [this]);
				}
			}
		}
	};
	if(typeof(BX.CrmSearchContentManager.messages) === "undefined")
	{
		BX.CrmSearchContentManager.messages = {};
	}
	BX.CrmSearchContentManager.items = {};
	BX.CrmSearchContentManager.create = function(id, settings)
	{
		var self = new BX.CrmSearchContentManager();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

BX.Crm.Page =
{
	sliders:
	{
		lead: { condition: new RegExp("/crm/lead/details/[0-9]+/", "i") },
		leadMerge: { condition: new RegExp("/crm/lead/merge/", "i"), options: { customLeftBoundary: 0 } },
		leadDedupeList: { condition: new RegExp("/crm/lead/dedupelist/", "i"), stopParameters: ["page", "IFRAME"] },
		leadAutomation: { condition: new RegExp("/crm/lead/automation/[0-9]+/", "i"), stopParameters: ['grid_action', 'page'], options: { customLeftBoundary: 0, loader: 'bizproc:automation-loader' }},
		contact: { condition: new RegExp("/crm/contact/details/[0-9]+/", "i") },
		contactMerge: { condition: new RegExp("/crm/contact/merge/", "i"), options: { customLeftBoundary: 0 } },
		contactDedupeList: { condition: new RegExp("/crm/contact/dedupelist/", "i"), stopParameters: ["page", "IFRAME"] },
		company: { condition: new RegExp("/crm/company/details/[0-9]+/", "i") },
		companyMerge: { condition: new RegExp("/crm/company/merge/", "i"), options: { customLeftBoundary: 0 } },
		companyDedupeList: { condition: new RegExp("/crm/company/dedupelist/", "i"), stopParameters: ["page", "IFRAME"] },
		deal: { condition: new RegExp("/crm/deal/details/[0-9]+/", "i") },
		dealMerge: { condition: new RegExp("/crm/deal/merge/", "i"), options: { customLeftBoundary: 0 } },
		dealAutomation: { condition: new RegExp("/crm/deal/automation/[0-9]+/", "i"), stopParameters: ['grid_action', 'page'], options: { customLeftBoundary: 0, loader: 'bizproc:automation-loader' } },
		quote: { condition: new RegExp("/crm/type/7/details/[0-9]+/", "i") },
		order: { condition: new RegExp("/shop/orders/details/[0-9]+/", "i") },
		orderSalescenter: { condition: new RegExp("/saleshub/orders/order/", "i") }, //
		orderShipment: { condition: new RegExp("/shop/orders/shipment/details/[0-9]+/", "i") },
		orderPayment: { condition: new RegExp("/shop/orders/payment/details/[0-9]+/", "i") },
		orderAutomation: { condition: new RegExp("/shop/orders/automation/[0-9]+/", "i"), stopParameters: ['grid_action', 'page'], options: { customLeftBoundary: 0, loader: 'bizproc:automation-loader' } },
		factoryBased: { condition: new RegExp("/type/[0-9]+/details/[0-9]+/", "i") },
		dynamicAutomation: { condition: new RegExp("/crm/type/[0-9]+/automation/[0-9]+/", "i"), stopParameters: ['grid_action', 'page'], options: { customLeftBoundary: 0, loader: 'bizproc:automation-loader' } },
		activity: { condition: new RegExp("/bitrix/components/bitrix/crm.activity.planner/slider.php", "i"), options: { allowChangeHistory: false, width: 1080 }},
	},
	items: [],
	initialized: false,
	initialize: function()
	{
		if(this.initialized)
		{
			return;
		}

		if(!(BX.SidePanel && BX.SidePanel.Instance))
		{
			return;
		}

		if(window === window.top)
		{
			var rules = [];
			for(var key in this.sliders)
			{
				if(!this.sliders.hasOwnProperty(key))
				{
					continue;
				}

				var slider = this.sliders[key];
				var options = BX.prop.getObject(slider, "options", {});
				if(!options.hasOwnProperty("cacheable"))
				{
					options["cacheable"] = false;
				}
				rules.push(
					{
						condition: [ slider.condition ],
						stopParameters: BX.prop.getArray(slider, "stopParameters", []),
						loader: "crm-entity-details-loader",
						options: options
					}
				);
			}

			BX.SidePanel.Instance.bindAnchors({ rules: rules });
		}

		this.initialized = true;
	},
	getItem: function(url)
	{
		for(var i = 0, length = this.items.length; i < length; i++)
		{
			var item = this.items[i];
			if(BX.prop.getString(item, "url", "") === url)
			{
				return item;
			}
		}
		return null;
	},
	isSliderEnabled: function(url)
	{
		if(!(window.top.BX.SidePanel && window.top.BX.SidePanel.Instance))
		{
			return false;
		}

		for(var key in this.sliders)
		{
			if(!this.sliders.hasOwnProperty(key))
			{
				continue;
			}

			var slider = this.sliders[key];
			if(slider.condition.test(url))
			{
				return true;
			}
		}
		return false;
	},
	open: function(url, options)
	{
		if(!this.initialized)
		{
			this.initialize();
		}

		if(!BX.browser.IsMobile() && this.isSliderEnabled(url))
		{
			this.openSlider(url);
			return null;
		}

		if(BX.prop.getBoolean(options, "openInNewWindow", false))
		{
			return window.open(url);
		}

		window.top.location.href = url;
		return null;
	},
	close: function(url, params)
	{
		var item = this.getItem(url);
		if(!item)
		{
			return;
		}

		if(BX.prop.getString(item, "", "isSlider", false))
		{
			this.closeSlider(url, false, params);
		}
		else
		{
			var wnd = BX.prop.getString(item, "", "wnd", null);
			if(wnd)
			{
				wnd.close();
			}
		}
	},
	openInNewTab: function(url)
	{
		if(this.isSliderEnabled(url))
		{
			this.openSlider(url);
		}
		else
		{
			this.openTab(url);
		}

	},
	openTab: function(url)
	{
		this.items.push({ url: url, isSlider: false, wnd: window.open(url) });
	},
	openPage: function(url)
	{
		window.top.location.href = BX.util.remove_url_param(url, ["IFRAME", "IFRAME_TYPE"]);
	},
	openSlider: function(url, params)
	{
		if(!(window.top.BX.SidePanel && window.top.BX.SidePanel.Instance))
		{
			return;
		}

		if(!BX.type.isPlainObject(params))
		{
			//Force apply default slider params.
			params = undefined;
		}

		window.top.BX.SidePanel.Instance.open (
			BX.util.add_url_param( url, { "IFRAME": "Y", "IFRAME_TYPE": "SIDE_SLIDER" } ),
			params
		);

		this.items.push({ url: url, isSlider: true });
	},
	closeSlider: function(url, keepalive, params)
	{
		if(!(window.top.BX.SidePanel && window.top.BX.SidePanel.Instance))
		{
			return;
		}

		//HACK: close slider before destroy due to window.top.BX.SidePanel.destroy bug
		var current = window.top.BX.SidePanel.Instance.getTopSlider();
		if(!current)
		{
			return;
		}

		var isFound = false, key = "", value = "";
		var identity = BX.prop.getObject(params, "identity", null);
		if(identity)
		{
			key = BX.prop.getString(identity, "key", "");
			value = BX.prop.getString(identity, "value", "");
		}

		if(key !== "" && value !== "")
		{
			var queryParam = "";
			if(typeof(BX.Uri) !== "undefined")
			{
				queryParam = (new BX.Uri(current.getUrl())).getQueryParam(key);
			}
			else
			{
				queryParam = this.getQueryParam(current.getUrl(), key);
			}
			isFound = BX.type.isString(queryParam) && queryParam.toUpperCase() === value.toUpperCase();
		}
		else
		{
			isFound = current.getUrl() === url;
		}

		if(isFound)
		{
			current.close(true);
			if(!keepalive)
			{
				window.top.BX.SidePanel.Instance.destroy(current.getUrl());
			}
		}
		else if(!keepalive)
		{
			window.top.BX.SidePanel.Instance.destroy(url);
		}
	},
	removeSlider: function(url)
	{
		window.top.BX.SidePanel.Instance.destroy(url);
	},
	getQueryParam: function(url, param)
	{
		if(!BX.type.isNotEmptyString(param))
		{
			return "";
		}

		var matches = (new RegExp('(?:^|[?&])'+ param +'=([^&#]*)', 'i')).exec(url);
		return BX.type.isArray(matches) && typeof(matches[1]) !== "undefined" ? matches[1] : "";
	},
	getTopSlider: function()
	{
		if(window.top.BX.SidePanel && window.top.BX.SidePanel.Instance)
		{
			return window.top.BX.SidePanel.Instance.getTopSlider();
		}
		return null;
	}
};

if(typeof(BX.Crm.Form) === "undefined")
{
	BX.Crm.Form = function()
	{
		this._id = "";
		this._settings = null;
		this._elementNode = null;
	};
	BX.Crm.Form.prototype =
	{
		initialize: function(id, setting)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = BX.type.isPlainObject(setting) ? setting : {};
			this._elementNode = BX.prop.getElementNode(this._settings, "elementNode", null);
			if(!this._elementNode)
			{
				throw "BX.Crm.Form: Could not find 'elementNode' parameter in settings.";
			}

			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		getId: function()
		{
			return this._id;
		},
		getElementNode: function()
		{
			return this._elementNode;
		},
		submit: function(options)
		{
			if(!BX.type.isPlainObject(options))
			{
				options = {};
			}

			var eventArgs = { cancel: false, options: options };
			BX.onCustomEvent(this, "onBeforeSubmit", [this, eventArgs]);
			if(eventArgs["cancel"])
			{
				BX.onCustomEvent(this, "onSubmitCancel", [this, eventArgs]);
				return false;
			}

			this.doSubmit(options);
			BX.onCustomEvent(this, "onAfterSubmit", [this, { options: options }]);
			return true;
		},
		doSubmit: function(options)
		{
		}
	};
}

if(typeof(BX.Crm.AjaxForm) === "undefined")
{
	BX.Crm.AjaxForm = function()
	{
		BX.Crm.AjaxForm.superclass.constructor.apply(this);
		this._config = null;
	};
	BX.extend(BX.Crm.AjaxForm, BX.Crm.Form);
	BX.Crm.AjaxForm.prototype.doInitialize = function()
	{
		this._config = BX.prop.getObject(this._settings, "config", null);
		if(!this._config)
		{
			throw "BX.Crm.AjaxForm: Could not find 'config' parameter in settings.";
		}

		if(BX.prop.getString(this._config, "url", "") === "")
		{
			throw "BX.Crm.AjaxForm: Could not find 'url' parameter in config";
		}

		if(BX.prop.getString(this._config, "method", "") === "")
		{
			this._config["method"] = "POST";
		}

		if(BX.prop.getString(this._config, "dataType", "") === "")
		{
			this._config["dataType"] = "json";
		}
	};
	BX.Crm.AjaxForm.prototype.getUrl = function()
	{
		return BX.prop.getString(this._config, "url", "");
	};
	BX.Crm.AjaxForm.prototype.setUrl = function(url)
	{
		this._config["url"] = url;
	};
	BX.Crm.AjaxForm.prototype.addUrlParams = function(params)
	{
		if(BX.type.isPlainObject(params) && Object.keys(params).length > 0)
		{
			this._config["url"] = BX.util.add_url_param(BX.prop.getString(this._config, "url", ""), params);
		}
	};
	BX.Crm.AjaxForm.prototype.doSubmit = function(options)
	{
		BX.ajax.submitAjax(this._elementNode, this._config);
	};
	BX.Crm.AjaxForm.create = function(id, settings)
	{
		var self = new BX.Crm.AjaxForm();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.ComponentAjax) === "undefined")
{
	BX.Crm.ComponentAjax = function()
	{
		BX.Crm.ComponentAjax.superclass.constructor.apply(this);
		this._className = "";
		this._actionName = "";
		this._signedParameters = null;
		this._callbacks = null;
		this._getParameters = {};
	};
	BX.extend(BX.Crm.ComponentAjax, BX.Crm.Form);
	BX.Crm.ComponentAjax.prototype.doInitialize = function()
	{
		this._className = BX.prop.getString(this._settings, "className", "");
		this._actionName = BX.prop.getString(this._settings, "actionName", "");
		this._signedParameters = BX.prop.getString(this._settings, "signedParameters", null);
		this._callbacks = BX.prop.getObject(this._settings, "callbacks", {});

	};
	BX.Crm.ComponentAjax.prototype.addUrlParams = function(params)
	{
		if(BX.type.isPlainObject(params) && Object.keys(params).length > 0)
		{
			this._getParameters = BX.merge(this._getParameters, params);
		}
	};
	BX.Crm.ComponentAjax.prototype.doSubmit = function(options)
	{
		BX.ajax.runComponentAction(
			this._className,
			this._actionName,
			{
				mode: "class",
				signedParameters: this._signedParameters,
				data: BX.ajax.prepareForm(this._elementNode),
				getParameters: this._getParameters
			}
		).then(
			function(response)
			{
				var callback = BX.prop.getFunction(this._callbacks, "onSuccess", null);
				if(callback)
				{
					BX.onCustomEvent(
						window,
						"BX.Crm.EntityEditorAjax:onSubmit",
						[ response["data"]["ENTITY_DATA"], response ]
					);
					callback(response["data"]);
				}
			}.bind(this)
		).catch(
			function(response)
			{
				var callback = BX.prop.getFunction(this._callbacks, "onFailure", null);
				if(!callback)
				{
					return;
				}

				var messages = [];
				var errors = response["errors"];
				for(var i = 0, length = errors.length; i < length; i++)
				{
					messages.push(errors[i]["message"]);
				}
				BX.onCustomEvent(
					window,
					"BX.Crm.EntityEditorAjax:onSubmitFailure",
					[ response["errors"] ]
				);
				callback({ "ERRORS": messages });
			}.bind(this)
		);
	};
	BX.Crm.ComponentAjax.create = function(id, settings)
	{
		var self = new BX.Crm.ComponentAjax();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Collection) === "undefined")
{
	BX.Collection = function()
	{
		this._items = [];
	};
	BX.Collection.prototype =
	{
		initialize: function(items)
		{
			this._items = BX.type.isArray(items) ? items : [];
		},
		findIndex: function(item)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				if(item === this._items[i])
				{
					return i;
				}
			}
			return -1;
		},
		search: function(comparer)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var item = this._items[i];
				if(comparer(item))
				{
					return item;
				}
			}
			return null;
		},
		getItems: function()
		{
			return ([].concat(this._items));
		},
		get: function(index)
		{
			return index < this._items.length ? this._items[index] : null;
		},
		set: function(index, item)
		{
			if(this.findIndex(item) >= 0)
			{
				return;
			}

			if(index >= 0 && index < this._items.length)
			{
				this._items[index] = item;
			}
			else
			{
				this._items.push(item);
			}
		},
		add: function(item)
		{
			if(this.findIndex(item) < 0)
			{
				this._items.push(item);
			}
		},
		remove: function(item)
		{
			var index = this.findIndex(item);
			if(index >= 0)
			{
				this._items.splice(index, 1);
			}
		},
		removeAll: function()
		{
			this._items = [];
		},
		length: function()
		{
			return this._items.length;
		}
	};
	BX.Collection.create = function(items)
	{
		var self = new BX.Collection();
		self.initialize(items);
		return self;
	};
}

if(typeof(BX.CrmLeadMode) === "undefined")
{
	BX.namespace("BX.CrmLeadMode");
	BX.CrmLeadMode = {
		currentCrmType: "simple",
		typeBlocks: [],
		message: {},
		isAdmin: false,
		leadPath: "",
		dealPath: "",
		isLeadEnabled: true,
		existActiveLeads: false,

		init: function (params)
		{
			this.existActiveLeads = false;

			if (typeof params == 'object' && params)
			{
				this.ajaxPath = params.ajaxPath || "";
				this.message = params.messages || "";
				this.leadPath = params.leadPath || "";
				this.dealPath = params.dealPath || "";
				this.isAdmin = params.isAdmin == "Y";
				this.isLeadEnabled = params.isLeadEnabled == "Y";
				this.currentCrmType = this.isLeadEnabled ? "classic" : "simple";
			}
		},

		changeCrmType: function (element)
		{
			if (!BX.type.isDomNode(element))
				return;

			for(var i=0, l=this.typeBlocks.length; i<l; i++)
			{
				var subBlock = BX.firstChild(this.typeBlocks[i]);
				if (!BX.type.isDomNode(subBlock))
					continue;

				if (this.typeBlocks[i] == element)
				{
					BX.addClass(subBlock, "crm-lead-info-popup-btn-active");
					this.currentCrmType = this.typeBlocks[i].getAttribute("data-crm-type");
				}
				else
				{
					BX.removeClass(subBlock, "crm-lead-info-popup-btn-active");
				}
			}
		},

		sendAjax: function (param)
		{
			BX.ajax({
				url: this.ajaxPath + "?analyticsModeChange=" + this.currentCrmType + (param == "convertCompleted"  ? "&analyticsLeadConvertToDeal" : ""),
				method: 'POST',
				dataType: 'json',
				data: {
					sessid: BX.bitrix_sessid(),
					action: "changeCrmType",
					crmType: this.currentCrmType
				},
				onsuccess: BX.proxy(function(result)
				{
					if (result.error)
					{
						alert(result.error);
					}
					else
					{
						document.location.href = this.currentCrmType == "simple" ? this.dealPath : this.leadPath;
					}
				}, this),
				onfailure: function()
				{
				}
			});
		},

		convertLead : function()
		{
			var manager = BX.Crm.BatchConversionManager.create(
				'simpleCrmConvert',
				{
					gridId: 'simpleCrmConvert',
					serviceUrl: "/bitrix/components/bitrix/crm.lead.list/list.ajax.php?sessid=" + BX.bitrix_sessid(),
					container: "crmLeadConverterWrapper",
					stateTemplate: this.message["CRM_LEAD_BATCH_CONVERSION_STATE"],
					messages:
					{
						title: this.message["CRM_LEAD_BATCH_CONVERSION_TITLE"],
						windowCloseConfirm: this.message["CRM_LEAD_BATCH_CONVERSION_DLG_CLOSE_CONFIRMATION"],
						summaryCaption: this.message["CRM_LEAD_BATCH_CONVERSION_COMPLETED"],
						summarySucceeded: this.message["CRM_LEAD_BATCH_CONVERSION_COUNT_SUCCEEDED"],
						summaryFailed: this.message["CRM_LEAD_BATCH_CONVERSION_COUNT_FAILED"]
					}
				}
			);
			var config = BX.CrmLeadConversionScheme.createConfig(BX.CrmLeadConversionScheme.dealcontactcompany);
			config.contact.initData = { defaultName: this.message["CRM_LEAD_BATCH_CONVERSION_NO_NAME"] };
			manager.setConfig(config);
			manager.setFilter({ "=STATUS_SEMANTIC_ID": "P" });
			manager.enableConfigCheck(false);
			manager.enableUserFieldCheck(false);
			BX.addCustomEvent(window, "BX.Crm.BatchConversionManager:onProcessComplete", function(){
				this.sendAjax("convertCompleted");
			}.bind(this));
			BX.addCustomEvent(window, "BX.Crm.BatchConversionManager:onStop", function(){
				BX.PopupWindowManager.getCurrentPopup().destroy();
			}.bind(this));
			manager.execute();
		},

		confirmLeadConvert: function ()
		{
			BX.PopupWindowManager.create('confirmLeadConvert', null, {
				closeIcon : false,
				lightShadow : true,
				overlay : true,
				titleBar: this.message["CRM_LEAD_CONVERT_TITLE"],
				zIndex: -970,
				buttons:  [
					new BX.UI.Button({
						text : this.message["CRM_TYPE_CONTINUE"],
						id: 'continue',
						color: BX.UI.Button.Color.SUCCESS,
						events : { click : function()
						{
							var convertButton = BX.PopupWindowManager.getPopupById('confirmLeadConvert').getButton('continue');
							if (convertButton.isWaiting())
							{
								//double click protection
								return;
							}
							convertButton.setWaiting();

							BX.loadCSS(
								'/bitrix/js/crm/css/autorun_proc.css'
							);
							BX.loadScript(
								['/bitrix/js/crm/batch_conversion.js', '/bitrix/js/crm/progress_control.js', '/bitrix/js/crm/autorun_proc.js'],
								function() {
									this.convertLead();
								}.bind(this)
							);

						}.bind(this)}
					}),
					new BX.UI.Button({
						text : this.message["CRM_TYPE_CANCEL"],
						color: BX.UI.Button.Color.LINK,
						events: { click : function()
						{
							this.getContext().close();
						}}
					})
				],
				content: '<div id="crmLeadConverterWrapper" style="margin-bottom: 6px;"></div><div style="width:420px;">' + this.message["CRM_LEAD_CONVERT_TEXT"] + '</div>'
			}).show();
		},

		preparePopup: function()
		{
			BX.ajax({
				method: 'POST',
				dataType: 'json',
				url: '/bitrix/components/bitrix/crm.lead.list/list.ajax.php',
				data: {
					sessid: BX.bitrix_sessid(),
					ACTION: "CHECK_ACTIVE_LEAD"
				},
				onsuccess: BX.proxy(function (json) {
					if (json.hasOwnProperty('EXIST_LEADS'))
					{
						this.existActiveLeads = json.EXIST_LEADS == "Y";
					}
					this.showPopup();
				}, this)
			});
		},

		showPopup: function ()
		{
			var buttons = [];

			if (this.isAdmin)
			{
				buttons.push(new BX.PopupWindowButton({
					text : this.message["CRM_TYPE_SAVE"],
					className : 'popup-window-button-create',
					events : { click : BX.proxy(function()
						{
							BX.addClass(BX.proxy_context.buttonNode, 'popup-window-button-wait');
							if (this.currentCrmType == "simple" && this.existActiveLeads)
							{
								this.confirmLeadConvert();
								BX.removeClass(BX.proxy_context.buttonNode, 'popup-window-button-wait');
							}
							else
							{
								this.sendAjax();
							}
						}, this)}
				}));
			}

			if (this.isLeadEnabled || window.location.toString().indexOf("deal") !== -1)
			{
				buttons.push(new BX.PopupWindowButtonLink({
					text : this.message["CRM_TYPE_CANCEL"],
					events: { click : function()
						{
							this.popupWindow.close();
						}
					}
				}));
			}
			else
			{
				buttons.push(new BX.PopupWindowButtonLink({
					text : this.message["CRM_TYPE_CANCEL"],
					events: { click : function()
						{
							history.back();
						}
					}
				}));
			}

			BX.PopupWindowManager.create('leadFirstPopup', null, {
				closeIcon : this.isLeadEnabled ? true : false,
				lightShadow : true,
				overlay : true,
				titleBar: this.message["CRM_TYPE_TITLE"],
				buttons: buttons,
				content: '<div style="width:600px;height:550px; background: url(/bitrix/js/crm/images/waiter-white-64px.gif) no-repeat center;"></div>',
				events : {
					onPopupClose : BX.proxy(function() {
						BX.ajax({
							url: this.ajaxPath,
							method: 'POST',
							dataType: 'json',
							data: {
								sessid: BX.bitrix_sessid(),
								action: "popupClose"
							},
							onsuccess: function(result)
							{
							},
							onfailure: function()
							{
							}
						});
					}, this),
					onAfterPopupShow: BX.proxy(function()
					{
						var self = BX.proxy_context;

						BX.ajax.post(
							'/bitrix/tools/crm_lead_mode.php',
							{
							},
							BX.proxy(function(result)
							{
								self.setContent(result);
								this.containerNode = BX("leadFirstPopupHtml");

								if (BX.type.isDomNode(this.containerNode))
								{
									this.typeBlocks = this.containerNode.getElementsByClassName("js-bx-lead-type-block");

									if (this.typeBlocks)
									{
										for(var i=0, l=this.typeBlocks.length; i<l; i++)
										{
											BX.bind(this.typeBlocks[i], "click", BX.proxy(function ()
											{
												this.self.changeCrmType(this.element);
											}, {element: this.typeBlocks[i], self: this}))
										}
									}

									var converterConfigBtn = this.containerNode.getElementsByClassName("js-bx-converter-config");
									if (converterConfigBtn)
									{
										BX.bind(converterConfigBtn[0], "click", this.showConverterConfig.bind(this));
									}
								}
							},
							this)
						);
					}, this)
				}
			}).show();
		},

		showConverterConfig: function(event)
		{
			event.preventDefault();
			BX.SidePanel.Instance.open("/bitrix/components/bitrix/crm.lead.mode/converter.php?site_id="
				+ BX.message('SITE_ID')
			);
		}
	};
}

BX.ready(function (){ BX.Crm.Page.initialize(); });

if(typeof(cssQuery) !== "function")
{
	eval(function(p,a,c,k,e,d){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--)d[e(c)]=k[c]||e(c);k=[function(e){return d[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('7 x=6(){7 1D="2.0.2";7 C=/\\s*,\\s*/;7 x=6(s,A){33{7 m=[];7 u=1z.32.2c&&!A;7 b=(A)?(A.31==22)?A:[A]:[1g];7 1E=18(s).1l(C),i;9(i=0;i<1E.y;i++){s=1y(1E[i]);8(U&&s.Z(0,3).2b("")==" *#"){s=s.Z(2);A=24([],b,s[1])}1A A=b;7 j=0,t,f,a,c="";H(j<s.y){t=s[j++];f=s[j++];c+=t+f;a="";8(s[j]=="("){H(s[j++]!=")")a+=s[j];a=a.Z(0,-1);c+="("+a+")"}A=(u&&V[c])?V[c]:21(A,t,f,a);8(u)V[c]=A}m=m.30(A)}2a x.2d;5 m}2Z(e){x.2d=e;5[]}};x.1Z=6(){5"6 x() {\\n  [1D "+1D+"]\\n}"};7 V={};x.2c=L;x.2Y=6(s){8(s){s=1y(s).2b("");2a V[s]}1A V={}};7 29={};7 19=L;x.15=6(n,s){8(19)1i("s="+1U(s));29[n]=12 s()};x.2X=6(c){5 c?1i(c):o};7 D={};7 h={};7 q={P:/\\[([\\w-]+(\\|[\\w-]+)?)\\s*(\\W?=)?\\s*([^\\]]*)\\]/};7 T=[];D[" "]=6(r,f,t,n){7 e,i,j;9(i=0;i<f.y;i++){7 s=X(f[i],t,n);9(j=0;(e=s[j]);j++){8(M(e)&&14(e,n))r.z(e)}}};D["#"]=6(r,f,i){7 e,j;9(j=0;(e=f[j]);j++)8(e.B==i)r.z(e)};D["."]=6(r,f,c){c=12 1t("(^|\\\\s)"+c+"(\\\\s|$)");7 e,i;9(i=0;(e=f[i]);i++)8(c.l(e.1V))r.z(e)};D[":"]=6(r,f,p,a){7 t=h[p],e,i;8(t)9(i=0;(e=f[i]);i++)8(t(e,a))r.z(e)};h["2W"]=6(e){7 d=Q(e);8(d.1C)9(7 i=0;i<d.1C.y;i++){8(d.1C[i]==e)5 K}};h["2V"]=6(e){};7 M=6(e){5(e&&e.1c==1&&e.1f!="!")?e:23};7 16=6(e){H(e&&(e=e.2U)&&!M(e))28;5 e};7 G=6(e){H(e&&(e=e.2T)&&!M(e))28;5 e};7 1r=6(e){5 M(e.27)||G(e.27)};7 1P=6(e){5 M(e.26)||16(e.26)};7 1o=6(e){7 c=[];e=1r(e);H(e){c.z(e);e=G(e)}5 c};7 U=K;7 1h=6(e){7 d=Q(e);5(2S d.25=="2R")?/\\.1J$/i.l(d.2Q):2P(d.25=="2O 2N")};7 Q=6(e){5 e.2M||e.1g};7 X=6(e,t){5(t=="*"&&e.1B)?e.1B:e.X(t)};7 17=6(e,t,n){8(t=="*")5 M(e);8(!14(e,n))5 L;8(!1h(e))t=t.2L();5 e.1f==t};7 14=6(e,n){5!n||(n=="*")||(e.2K==n)};7 1e=6(e){5 e.1G};6 24(r,f,B){7 m,i,j;9(i=0;i<f.y;i++){8(m=f[i].1B.2J(B)){8(m.B==B)r.z(m);1A 8(m.y!=23){9(j=0;j<m.y;j++){8(m[j].B==B)r.z(m[j])}}}}5 r};8(![].z)22.2I.z=6(){9(7 i=0;i<1z.y;i++){o[o.y]=1z[i]}5 o.y};7 N=/\\|/;6 21(A,t,f,a){8(N.l(f)){f=f.1l(N);a=f[0];f=f[1]}7 r=[];8(D[t]){D[t](r,A,f,a)}5 r};7 S=/^[^\\s>+~]/;7 20=/[\\s#.:>+~()@]|[^\\s#.:>+~()@]+/g;6 1y(s){8(S.l(s))s=" "+s;5 s.P(20)||[]};7 W=/\\s*([\\s>+~(),]|^|$)\\s*/g;7 I=/([\\s>+~,]|[^(]\\+|^)([#.:@])/g;7 18=6(s){5 s.O(W,"$1").O(I,"$1*$2")};7 1u={1Z:6(){5"\'"},P:/^(\'[^\']*\')|("[^"]*")$/,l:6(s){5 o.P.l(s)},1S:6(s){5 o.l(s)?s:o+s+o},1Y:6(s){5 o.l(s)?s.Z(1,-1):s}};7 1s=6(t){5 1u.1Y(t)};7 E=/([\\/()[\\]?{}|*+-])/g;6 R(s){5 s.O(E,"\\\\$1")};x.15("1j-2H",6(){D[">"]=6(r,f,t,n){7 e,i,j;9(i=0;i<f.y;i++){7 s=1o(f[i]);9(j=0;(e=s[j]);j++)8(17(e,t,n))r.z(e)}};D["+"]=6(r,f,t,n){9(7 i=0;i<f.y;i++){7 e=G(f[i]);8(e&&17(e,t,n))r.z(e)}};D["@"]=6(r,f,a){7 t=T[a].l;7 e,i;9(i=0;(e=f[i]);i++)8(t(e))r.z(e)};h["2G-10"]=6(e){5!16(e)};h["1x"]=6(e,c){c=12 1t("^"+c,"i");H(e&&!e.13("1x"))e=e.1n;5 e&&c.l(e.13("1x"))};q.1X=/\\\\:/g;q.1w="@";q.J={};q.O=6(m,a,n,c,v){7 k=o.1w+m;8(!T[k]){a=o.1W(a,c||"",v||"");T[k]=a;T.z(a)}5 T[k].B};q.1Q=6(s){s=s.O(o.1X,"|");7 m;H(m=s.P(o.P)){7 r=o.O(m[0],m[1],m[2],m[3],m[4]);s=s.O(o.P,r)}5 s};q.1W=6(p,t,v){7 a={};a.B=o.1w+T.y;a.2F=p;t=o.J[t];t=t?t(o.13(p),1s(v)):L;a.l=12 2E("e","5 "+t);5 a};q.13=6(n){1d(n.2D()){F"B":5"e.B";F"2C":5"e.1V";F"9":5"e.2B";F"1T":8(U){5"1U((e.2A.P(/1T=\\\\1v?([^\\\\s\\\\1v]*)\\\\1v?/)||[])[1]||\'\')"}}5"e.13(\'"+n.O(N,":")+"\')"};q.J[""]=6(a){5 a};q.J["="]=6(a,v){5 a+"=="+1u.1S(v)};q.J["~="]=6(a,v){5"/(^| )"+R(v)+"( |$)/.l("+a+")"};q.J["|="]=6(a,v){5"/^"+R(v)+"(-|$)/.l("+a+")"};7 1R=18;18=6(s){5 1R(q.1Q(s))}});x.15("1j-2z",6(){D["~"]=6(r,f,t,n){7 e,i;9(i=0;(e=f[i]);i++){H(e=G(e)){8(17(e,t,n))r.z(e)}}};h["2y"]=6(e,t){t=12 1t(R(1s(t)));5 t.l(1e(e))};h["2x"]=6(e){5 e==Q(e).1H};h["2w"]=6(e){7 n,i;9(i=0;(n=e.1F[i]);i++){8(M(n)||n.1c==3)5 L}5 K};h["1N-10"]=6(e){5!G(e)};h["2v-10"]=6(e){e=e.1n;5 1r(e)==1P(e)};h["2u"]=6(e,s){7 n=x(s,Q(e));9(7 i=0;i<n.y;i++){8(n[i]==e)5 L}5 K};h["1O-10"]=6(e,a){5 1p(e,a,16)};h["1O-1N-10"]=6(e,a){5 1p(e,a,G)};h["2t"]=6(e){5 e.B==2s.2r.Z(1)};h["1M"]=6(e){5 e.1M};h["2q"]=6(e){5 e.1q===L};h["1q"]=6(e){5 e.1q};h["1L"]=6(e){5 e.1L};q.J["^="]=6(a,v){5"/^"+R(v)+"/.l("+a+")"};q.J["$="]=6(a,v){5"/"+R(v)+"$/.l("+a+")"};q.J["*="]=6(a,v){5"/"+R(v)+"/.l("+a+")"};6 1p(e,a,t){1d(a){F"n":5 K;F"2p":a="2n";1a;F"2o":a="2n+1"}7 1m=1o(e.1n);6 1k(i){7 i=(t==G)?1m.y-i:i-1;5 1m[i]==e};8(!Y(a))5 1k(a);a=a.1l("n");7 m=1K(a[0]);7 s=1K(a[1]);8((Y(m)||m==1)&&s==0)5 K;8(m==0&&!Y(s))5 1k(s);8(Y(s))s=0;7 c=1;H(e=t(e))c++;8(Y(m)||m==1)5(t==G)?(c<=s):(s>=c);5(c%m)==s}});x.15("1j-2m",6(){U=1i("L;/*@2l@8(@\\2k)U=K@2j@*/");8(!U){X=6(e,t,n){5 n?e.2i("*",t):e.X(t)};14=6(e,n){5!n||(n=="*")||(e.2h==n)};1h=1g.1I?6(e){5/1J/i.l(Q(e).1I)}:6(e){5 Q(e).1H.1f!="2g"};1e=6(e){5 e.2f||e.1G||1b(e)};6 1b(e){7 t="",n,i;9(i=0;(n=e.1F[i]);i++){1d(n.1c){F 11:F 1:t+=1b(n);1a;F 3:t+=n.2e;1a}}5 t}}});19=K;5 x}();',62,190,'|||||return|function|var|if|for||||||||pseudoClasses||||test|||this||AttributeSelector|||||||cssQuery|length|push|fr|id||selectors||case|nextElementSibling|while||tests|true|false|thisElement||replace|match|getDocument|regEscape||attributeSelectors|isMSIE|cache||getElementsByTagName|isNaN|slice|child||new|getAttribute|compareNamespace|addModule|previousElementSibling|compareTagName|parseSelector|loaded|break|_0|nodeType|switch|getTextContent|tagName|document|isXML|eval|css|_1|split|ch|parentNode|childElements|nthChild|disabled|firstElementChild|getText|RegExp|Quote|x22|PREFIX|lang|_2|arguments|else|all|links|version|se|childNodes|innerText|documentElement|contentType|xml|parseInt|indeterminate|checked|last|nth|lastElementChild|parse|_3|add|href|String|className|create|NS_IE|remove|toString|ST|select|Array|null|_4|mimeType|lastChild|firstChild|continue|modules|delete|join|caching|error|nodeValue|textContent|HTML|prefix|getElementsByTagNameNS|end|x5fwin32|cc_on|standard||odd|even|enabled|hash|location|target|not|only|empty|root|contains|level3|outerHTML|htmlFor|class|toLowerCase|Function|name|first|level2|prototype|item|scopeName|toUpperCase|ownerDocument|Document|XML|Boolean|URL|unknown|typeof|nextSibling|previousSibling|visited|link|valueOf|clearCache|catch|concat|constructor|callee|try'.split('|'),0,{}));
}
