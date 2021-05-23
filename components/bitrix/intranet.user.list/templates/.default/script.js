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
			this.componentName = params.componentName;
			this.signedParameters = params.signedParameters;
			this.gridId = params.gridId;
			this.filterId = (BX.type.isNotEmptyString(params.filterId) ? params.filterId : null);
			this.gridContainer = (BX.type.isNotEmptyString(params.gridContainerId) ? BX(params.gridContainerId) : null);
			this.invitationLink = params.invitationLink;

			params.toolbar.componentName = this.componentName;
			this.toolbarInstance = new namespace.Toolbar(params.toolbar);
			this.bindTags();

			BX.addCustomEvent("SidePanel.Slider:onMessage", function(event) {
				if (event.getEventId() == 'userProfileSlider::reloadList')
				{
					BX.Main.gridManager.reload(this.gridId);
				}
			}.bind(this));
		},

		showInvitation: function ()
		{
			BX.SidePanel.Instance.open(this.invitationLink, {cacheable: false, allowChangeHistory: false})
		},

		addTask: function(userId)
		{
			if (BX.type.isNotEmptyObject(taskIFramePopup))
			{
				taskIFramePopup.add({
					RESPONSIBLE_ID: userId
				});
			}
		},

		sendMessage: function(userId)
		{
			if (BX.type.isNotEmptyObject(BXIM))
			{
				BXIM.openMessenger(userId);
			}
		},

		viewMessageHistory: function(userId)
		{
			if (BX.type.isNotEmptyObject(BXIM))
			{
				BXIM.openHistory(userId);
			}
		},

		videoCall: function(userId)
		{
			if (
				BX.type.isNotEmptyObject(BXIM)
				&& BXIM.checkCallSupport()
			)
			{
				BXIM.callTo(userId);
			}
		},

		reinvite: function(userId, isExtranetUser, bindNode)
		{
			BX.ajax.runAction('intranet.controller.invite.reinvite', {
				data: {
					params: {
						userId: userId,
						extranet: (isExtranetUser ? 'Y' : 'N')
					}
				}
			}).then(function (response) {
				if (response.data.result)
				{
					var InviteAccessPopup = BX.PopupWindowManager.create('invite_access' + Math.floor(Math.random() * 1000), bindNode, {
						content: '<p>' + BX.message('INTRANET_USER_LIST_ACTION_REINVITE_SUCCESS') + '</p>',
						offsetLeft: -10,
						offsetTop: 7,
						autoHide: true
					});

					InviteAccessPopup.show();
				}
			}.bind(this), function (response) {

			}.bind(this));
		},

		activityAction: function(action, userId)
		{
			var userActive = 'N';
			if (action == 'restore')
			{
				userActive = 'Y';
			}
			else if (action == 'deactivate' || action == 'deactivateInvited')
			{
				userActive = 'D';
			}

			if (this.confirmUser(action))
			{
				BX.ajax.runComponentAction(this.componentName, 'setActivity', {
					mode: 'class',
					signedParameters: this.signedParameters,
					data: {
						params: {
							userId: userId,
							action: action
						}
					}
				}).then(function (response) {
					BX.Main.gridManager.reload(this.gridId);
				}.bind(this), function (response) {
					if (
						BX.type.isNotEmptyObject(response)
						&& BX.type.isArray(response.errors)
					)
					{
						if (action === "delete")
						{
							this.activityAction("deactivateInvited", userId);
						}
						else
						{
							var DeleteErrorPopup = BX.PopupWindowManager.create('delete_error' + Math.floor(Math.random() * 1000), null, {
								content: response.errors[0].message,
								offsetLeft: -10,
								offsetTop: 7,
								autoHide: true
							});

							DeleteErrorPopup.show();
						}
					}
				}.bind(this));
			}
		},

		confirmUser: function(action)
		{
			var confirmMess = '';

			if (action == 'restore')
			{
				confirmMess = BX.message('INTRANET_USER_LIST_ACTION_RESTORE_CONFIRM');
			}
			else if (action == 'delete')
			{
				confirmMess = BX.message('INTRANET_USER_LIST_ACTION_DELETE_CONFIRM');
			}
			else if (action == 'deactivateInvited')
			{
				confirmMess = BX.message('INTRANET_USER_LIST_ACTION_DEACTIVATE_INVITED_CONFIRM');
			}
			else
			{
				confirmMess = BX.message('INTRANET_USER_LIST_ACTION_DEACTIVATE_CONFIRM');
			}

			return (confirm(confirmMess));
		},

		bindTags: function()
		{
			if (!BX.type.isDomNode(this.gridContainer))
			{
				return;
			}

			if (
				BX.type.isNotEmptyString(this.filterId)
				&& BX.type.isNotEmptyObject(BX.Main)
				&& BX.type.isNotEmptyObject(BX.Main.filterManager)
			)
			{
				var filterManager = BX.Main.filterManager.getById(this.filterId);
				if(filterManager)
				{
					this.filterApi = filterManager.getApi();
				}
			}

			this.gridContainer.addEventListener('click', BX.delegate(function(e)
			{
				var tagValue = BX.getEventTarget(e).getAttribute('bx-tag-value');
				if (BX.type.isNotEmptyString(tagValue))
				{
					if (this.clickTag(tagValue))
					{
						e.preventDefault();
					}
				}
			}, this), true);
		},

		clickTag: function(tagValue)
		{
			var result = false;

			if (
				BX.type.isNotEmptyString(tagValue)
				&& BX.type.isNotEmptyObject(this.filterApi)
			)
			{
				this.filterApi.setFields({
					TAGS: tagValue
				});
				this.filterApi.apply();

				var windowScroll = BX.GetWindowScrollPos();

				(new BX.easing({
					duration : 500,
					start : { scroll : windowScroll.scrollTop },
					finish : { scroll : 0 },
					transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
					step : function(state){
						window.scrollTo(0, state.scroll);
					},
					complete: function() {
					}
				})).animate();

				result = true;
			}

			return result;
		}
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