;(function ()
{
	var namespace = BX.namespace('BX.Intranet.UserList');
	if (namespace.Manager)
	{
		return;
	}

	namespace.Manager = function(params)
	{
		this.init(params);
	};

	namespace.Manager.prototype = {
		init: function(params)
		{
			this.gridId = params.gridId;
			this.invitationLink = params.invitationLink;

			BX.addCustomEvent("SidePanel.Slider:onMessage", function(event) {
				if (event.getEventId() === 'userProfileSlider::reloadList')
				{
					BX.Main.gridManager.reload(this.gridId);
				}
			}.bind(this));

			const popup = BX.PopupWindowManager.getPopupById('userGridSettingsMenu');
			this.sortButton = popup.getContentContainer().querySelector('[data-unqid="user-grid-sort-btn"]');

			BX.addCustomEvent('BX.Main.grid:sort', () => {
				BX.removeClass(this.sortButton, 'menu-popup-item-accept');
			});

			BX.addCustomEvent('onPullEvent-intranet', this.onPullEvent.bind(this));
		},

		setSort(sortParams)
		{
			BX.addClass(this.sortButton, 'menu-popup-item-accept');
			BX.Intranet.UserList.GridManager.setSort(sortParams);
		},

		onPullEvent: function(command, params)
		{
			if (params.userId && command)
			{
				const grid = BX.Main.gridManager.getById(this.gridId)?.instance;
				const row = grid?.getRows().getById(params.userId);

				switch (command)
				{
					case 'userInitialized':
					case 'userDeleted':
						row?.update();
						break;
					case 'userRegister':
						grid?.addRow(params.userId);
						break;
					default:
						break;
				}
			}
		},

		showInvitation: function ()
		{
			BX.SidePanel.Instance.open(this.invitationLink, {cacheable: false, allowChangeHistory: false})
		},
	};

	namespace.Toolbar = function(params)
	{
		this.id = "";
		this.menuItems = null;
		this.menuId = null;
		this.menu = null;
		this.menuOpened = false;
		this.menuPopup = null;
		this.componentName = null;

		this.initialize(params);
	};

	namespace.Toolbar.prototype = {

		initialize: function(params)
		{
			this.id = params.id;
			this.menuItems = params.menuItems;
			this.componentName = params.componentName;

			if (
				BX.type.isNotEmptyString(params.menuButtonId)
				&& BX(params.menuButtonId)
			)
			{
				BX.bind(BX(params.menuButtonId), 'click', function(e) {
					this.menuButtonClick(e.currentTarget);
				}.bind(this));
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
		_onMenuClose: function()
		{
			var eventArgs = { menu: this._menu };
			BX.onCustomEvent(window, "CrmInterfaceToolbarMenuClose", [ this, eventArgs]);
		},
		menuButtonClick: function(bindNode)
		{
			this.openMenu(bindNode)
		},
		openMenu: function(bindNode)
		{
			if(this.menuOpened)
			{
				this.closeMenu();
				return;
			}

			if(!BX.type.isArray(this.menuItems))
			{
				return;
			}

			var menuItems = [];
			var onClick = '';

			for(var i = 0; i < this.menuItems.length; i++)
			{
				var item = this.menuItems[i];

				if (
					typeof(item.SEPARATOR) !== "undefined"
					&& item.SEPARATOR
				)
				{
					menuItems.push({ 'SEPARATOR': true });
					continue;
				}

				if (!BX.type.isNotEmptyString(item.TYPE))
				{
					continue;
				}

				if (BX.type.isNotEmptyString(item.LINK))
				{
					onClick = 'window.location.href = "' + item.LINK + '"; return false;';
				}

				menuItems.push({
					text: (BX.type.isNotEmptyString(item.TITLE) ? item.TITLE : ''),
					onclick: onClick
				});
			}

			this.menuId = this.id.toLowerCase() + "_menu";

			BX.PopupMenu.show(
				this.menuId,
				bindNode,
				menuItems,
				{
					autoHide: true,
					closeByEsc: true,
					offsetTop: 0,
					offsetLeft: 0,
					events: {
						onPopupShow: BX.delegate(this.onPopupShow, this),
						onPopupClose: BX.delegate(this.onPopupClose, this),
						onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
					}
				}
			);
			this.menuPopup = BX.PopupMenu.currentItem;
		},
		closeMenu: function()
		{
			if(this.menuPopup)
			{
				if(this.menuPopup.popupWindow)
				{
					this.menuPopup.popupWindow.destroy();
				}
			}
		},
		onPopupShow: function()
		{
			this.menuOpened = true;
		},
		onPopupClose: function()
		{
			this.closeMenu();
		},
		onPopupDestroy: function()
		{
			this.menuOpened = false;
			this.menuPopup = null;

			if(typeof(BX.PopupMenu.Data[this.menuId]) !== "undefined")
			{
				delete(BX.PopupMenu.Data[this.menuId]);
			}
		}
	};

})();