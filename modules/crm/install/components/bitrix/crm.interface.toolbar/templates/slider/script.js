if (typeof (BX.InterfaceToolBar) === 'undefined')
{
	BX.InterfaceToolBar = function()
	{
		this._id = '';
		this._settings = null;
		this._container = null;
		this._menuButton = null;
		this._menuPopup = null;
		this._isMenuOpened = false;
	};

	BX.InterfaceToolBar.prototype = {
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
			var container = this._container = BX(this.getSetting('containerId', ''));
			if (container)
			{
				var btnClassName = this.getSetting('menuButtonClassName');
				if (!BX.type.isNotEmptyString(btnClassName))
				{
					btnClassName = this.getSetting('moreButtonClassName', 'crmsettingbtn');
				}
				if (BX.type.isNotEmptyString(btnClassName))
				{
					this._menuButton = BX.findChild(container, { 'className': btnClassName }, true, false);
					if (this._menuButton)
					{
						BX.bind(this._menuButton, 'click', BX.delegate(this.onMenuButtonClick, this));
					}
				}
			}
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		prepareMenuItem: function(item)
		{
			var hdlrRx1 = /return\s+false(\s*;)?\s*$/;
			var hdlrRx2 = /;\s*$/;

			var isSeparator = typeof (item['SEPARATOR']) !== 'undefined' ? item['SEPARATOR'] : false;
			if (isSeparator)
			{
				return { delimiter: true };
			}

			var link = typeof (item['LINK']) !== 'undefined' ? item['LINK'] : '';
			var hdlr = typeof (item['ONCLICK']) !== 'undefined' ? item['ONCLICK'] : '';

			if (link !== '')
			{
				var s = 'window.top.location.href = "' + link + '";';
				hdlr = hdlr !== '' ? (s + ' ' + hdlr) : s;
			}

			if (hdlr !== '')
			{
				if (!hdlrRx1.test(hdlr))
				{
					if (!hdlrRx2.test(hdlr))
					{
						hdlr += ';';
					}
					hdlr += ' return false;';
				}
			}

			var result = {
				text: typeof (item['TEXT']) !== 'undefined' ? item['TEXT'] : '', className: 'menupopupitemnone',
			};

			if (hdlr !== '')
			{
				result['onclick'] = hdlr;
			}

			if (BX.type.isArray(item['MENU']))
			{
				var subMenuItems = [];
				for (
					var i = 0,
						l = item['MENU'].length; i < l; i++
				)
				{
					subMenuItems.push(this.prepareMenuItem(item['MENU'][i]));
				}
				result['items'] = subMenuItems;
			}

			return result;
		},
		openMenu: function(e)
		{
			if (this._isMenuOpened)
			{
				this.closeMenu();
				return;
			}

			var items = this.getSetting('items', null);
			if (!BX.type.isArray(items))
			{
				return;
			}

			var menuItems = [];
			for (var i = 0; i < items.length; i++)
			{
				menuItems.push(this.prepareMenuItem(items[i]));
			}
			BX.onCustomEvent(window, 'Crm.InterfaceToolbar.MenuBuild', [this, { items: menuItems }]);

			this._menuId = this._id.toLowerCase() + '_menu';
			BX.PopupMenu.show(this._menuId, this._menuButton, menuItems, {
				autoHide: true, closeByEsc: true, offsetTop: 0, offsetLeft: 0, events: {
					onPopupShow: BX.delegate(this.onPopupShow, this),
					onPopupClose: BX.delegate(this.onPopupClose, this),
					onPopupDestroy: BX.delegate(this.onPopupDestroy, this),
				},
			});
			this._menuPopup = BX.PopupMenu.currentItem;
		},
		closeMenu: function()
		{
			if (this._menuPopup)
			{
				if (this._menuPopup.popupWindow)
				{
					this._menuPopup.popupWindow.destroy();
				}
			}
		},
		onMenuButtonClick: function(e)
		{
			this.openMenu();
		},
		onPopupShow: function()
		{
			this._isMenuOpened = true;
		},
		onPopupClose: function()
		{
			this.closeMenu();
		},
		onPopupDestroy: function()
		{
			this._isMenuOpened = false;
			this._menuPopup = null;

			if (typeof (BX.PopupMenu.Data[this._menuId]) !== 'undefined')
			{
				delete (BX.PopupMenu.Data[this._menuId]);
			}
		},
		onEditorConfigReset: function()
		{
			var editor = BX.Crm.EntityEditor.getDefault();
			if (editor)
			{
				editor.resetConfig();
			}
		},
	};

	BX.InterfaceToolBar.create = function(id, settings)
	{
		var self = new BX.InterfaceToolBar();
		self.initialize(id, settings);
		return self;
	};
}
