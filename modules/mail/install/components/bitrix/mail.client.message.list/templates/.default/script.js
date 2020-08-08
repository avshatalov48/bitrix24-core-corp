;(function ()
{
	BX.namespace('BX.Mail.Client.Message.List');
	BX.Mail.Client.Message.List = function (options)
	{
		this.gridId = options.gridId;
		this.mailboxId = options.mailboxId;
		this.canMarkSpam = options.canMarkSpam;
		this.canDelete = options.canDelete;
		this.moveBtnMailIdPrefix = options.moveBtnMailIdPrefix;
		this.connectedMailboxesLicenseInfo = options.connectedMailboxesLicenseInfo;
		this.ERROR_CODE_CAN_NOT_DELETE = options.ERROR_CODE_CAN_NOT_DELETE;
		this.ERROR_CODE_CAN_NOT_MARK_SPAM = options.ERROR_CODE_CAN_NOT_MARK_SPAM;
		this.disabledClassName = 'js-disabled';
		this.userInterfaceManager = new BX.Mail.Client.Message.List.UserInterfaceManager(options);
		this.userInterfaceManager.reloadGrid = this.reloadGrid.bind(this);
		this.userInterfaceManager.resetGridSelection = this.resetGridSelection.bind(this);
		this.userInterfaceManager.isSelectedRowsHaveClass = this.isSelectedRowsHaveClass.bind(this);
		this.userInterfaceManager.getGridInstance = this.getGridInstance.bind(this);
		this.cache = {};
		this.addEventHandlers();

		BX.Mail.Client.Message.List[options.id] = this;
	};
	BX.Mail.Client.Message.List.prototype = {
		addEventHandlers: function ()
		{
			// todo delete this hack
			// it is here to prevent grid's title changing after filter apply
			BX.ajax.UpdatePageData = (function() {});

			BX.addCustomEvent(
				'onSubMenuShow',
				function ()
				{
					var container = this.getMenuWindow().getPopupWindow().getPopupContainer();
					var id = null;

					if (container)
					{
						id = BX.data(container, 'grid-row-id');
					}

					BX.data(
						this.getSubMenu().getPopupWindow().getPopupContainer(),
						'grid-row-id',
						this.gridRowId || id
					);
				}
			);

			BX.Event.EventEmitter.subscribe('BX.Main.Menu.Item:onmouseenter', function (event)
			{
				var menuItem = event.target;

				if (!menuItem.dataset || !menuItem.getMenuWindow())
				{
					return;
				}

				var menuWindow = menuItem.getMenuWindow();
				var subMenuItems = menuWindow.getMenuItems();

				var path = menuItem.dataset.path;
				var hash = menuItem.dataset.dirMd5;
				var hasChild = menuItem.dataset.hasChild;

				if (!hasChild)
				{
					return;
				}

				for (var i = 0; i < subMenuItems.length; i++)
				{
					var item = subMenuItems[i];

					if (item.getId() === path)
					{
						var hasSubMenu = item.hasSubMenu();

						if (hasSubMenu)
						{
							item.showSubMenu();
							var subMenu = item.getSubMenu();

							if (subMenu)
							{
								var items = subMenu.getMenuItems();
								var hasLoadingItem = false;

								for (var k = 0; k < items.length; k++)
								{
									var subItem = items[k];

									if (subItem.getId() === 'loading')
									{
										hasLoadingItem = true;
									}
								}
							}

							if (!hasLoadingItem)
							{
								return;
							}
						}

						this.loadLevelMenu(item, hash)
					}
				}
			}.bind(this));
		},
		loadLevelMenu: function (menuItem, hash)
		{
			var menu = this.getCache(menuItem.getId());
			var popup = BX.Main.PopupManager.getPopupById('menu-popup-popup-submenu-' + menuItem.getId());

			if (popup)
			{
				popup.destroy();
			}

			if (menu)
			{
				menuItem.destroySubMenu();
				menuItem.addSubMenu(menu);
				menuItem.showSubMenu();
				return;
			}

			var subItem = {
				'id': 'loading',
				'text': BX.message('MAIL_CLIENT_BUTTON_LOADING'),
				'disabled': true
			};

			menuItem.destroySubMenu();
			menuItem.addSubMenu([subItem]);
			menuItem.showSubMenu();

			BX.ajax.runComponentAction('bitrix:mail.client.config.dirs', 'level', {
				mode: 'class',
				data: {mailboxId: this.mailboxId, dir: {path: menuItem.getId(), dirMd5: hash}}
			}).then(
				function (response)
				{
					var dirs = response.data.dirs;
					var items = [];

					for (var i = 0; i < dirs.length; i++)
					{
						var hasChild = /(HasChildren)/i.test(dirs[i].FLAGS);
						var item = {
							'id': dirs[i].PATH,
							'text': dirs[i].NAME,
							'dataset': {
								'path': dirs[i].PATH,
								'dirMd5': dirs[i].DIR_MD5,
								'isDisabled': dirs[i].IS_DISABLED,
								'hasChild': hasChild,
							},
							items: hasChild ? [{
								id: 'loading',
								'text': BX.message('MAIL_CLIENT_BUTTON_LOADING'),
								'disabled': true
							}] : []
						};

						items.push(item);
					}

					this.setCache(menuItem.getId(), items);

					var popup = BX.Main.PopupManager.getPopupById('menu-popup-popup-submenu-' + menuItem.getId());
					var isShown = menuItem.getMenuWindow().getPopupWindow().isShown();

					if (popup)
					{
						popup.destroy();
					}

					if (isShown)
					{
						menuItem.destroySubMenu();
						menuItem.addSubMenu(items);
						menuItem.showSubMenu();
					}
				}.bind(this),
				function (response)
				{
				}.bind(this)
			);
		},
		showLicensePopup: function (code)
		{
			B24.licenseInfoPopup.show(
				code,
				BX.message('MAIL_MAILBOX_LICENSE_CONNECTED_MAILBOXES_LIMIT_TITLE'),
				this.connectedMailboxesLicenseInfo
			);
		},
		onCrmClick: function (id)
		{
			var selected = this.getGridInstance().getRows().getSelected();
			var row = id ? this.getGridInstance().getRows().getById(id) : selected[0];
			if (!(row && row.node))
			{
				return;
			}
			var addToCrm = this.userInterfaceManager.isAddToCrmActionAvailable(row.node);
			var messageIdNode = row.node.querySelector('[data-message-id]');
			if (!(messageIdNode.dataset && messageIdNode.dataset.messageId))
			{
				return;
			}

			this.resetGridSelection();

			if (addToCrm)
			{
				if (typeof this.isAddingToCrmInProgress !== "object")
				{
					this.isAddingToCrmInProgress = {};
				}
				if (this.isAddingToCrmInProgress[id] === true)
				{
					return;
				}
				this.isAddingToCrmInProgress[id] = true;
				BX.ajax.runComponentAction(
					'bitrix:mail.client',
					'createCrmActivity',
					{
						mode: 'ajax',
						data: {
							messageId: messageIdNode.dataset.messageId
						},
						analyticsLabel: {
							'groupCount': selected.length,
							'bindings': this.getRowsBindings([row])
						}
					}
				).then(
					function (id, json)
					{
						this.isAddingToCrmInProgress[id] = false;
						this.notify(BX.message('MAIL_MESSAGE_LIST_NOTIFY_ADDED_TO_CRM'));
						this.userInterfaceManager.onBindingCreated();
					}.bind(this, id),
					function (json)
					{
						this.isAddingToCrmInProgress[id] = false;
						if (json.errors && json.errors.length > 0)
						{
							this.notify(json.errors.map(
								function (item)
								{
									return item.message;
								}
							).join('<br>'), 5000);
						}
						else
						{
							this.notify(BX.message('MAIL_MESSAGE_LIST_NOTIFY_ADD_TO_CRM_ERROR'));
						}
					}.bind(this)
				);
			}
			else
			{
				BX.ajax.runComponentAction(
					'bitrix:mail.client',
					'removeCrmActivity',
					{
						mode: 'ajax',
						data: {
							messageId: messageIdNode.dataset.messageId
						},
						analyticsLabel: {
							'groupCount': selected.length,
							'bindings': this.getRowsBindings([row])
						}
					}
				).then(function (messageIdNode)
				{
					this.userInterfaceManager.onCrmBindingDeleted(messageIdNode.dataset.messageId);
					this.notify(BX.message('MAIL_MESSAGE_LIST_NOTIFY_EXCLUDED_FROM_CRM'));
				}.bind(this, messageIdNode));
			}
		},
		onViewClick: function (id)
		{
			if (id === undefined && this.getGridInstance().getRows().getSelectedIds().length === 0)
			{
				return;
			}
			// @TODO: path
			BX.SidePanel.Instance.open("/mail/message/" + id, {
				width: 1080,
				loader: 'view-mail-loader'
			});
		},
		onDeleteClick: function (id)
		{
			var selected = this.getGridInstance().getRows().getSelected();
			if (id === undefined && selected.length === 0)
			{
				return;
			}
			if (!this.canDelete)
			{
				this.showDirsSlider();
				return;
			}
			var options = {
				analyticsLabel: {
					'groupCount': selected.length,
					'bindings': this.getRowsBindings(id ? [this.getGridInstance().getRows().getById(id)] : selected)
				},
				onSuccess: function ()
				{
					this.reloadGrid({});
				}
			};
			if (id !== undefined)
			{
				options.ids = [id];
			}
			if (this.userInterfaceManager.isCurrentFolderTrash)
			{
				/*
				BX.UI.Dialogs.MessageBox.show({
					title: BX.message('MAIL_MESSAGE_LIST_CONFIRM_TITLE'),
					message: BX.message('MAIL_MESSAGE_LIST_CONFIRM_DELETE_ALL'),
					onYes: function () { return true; }, // handler.bind(this),
					buttons: BX.UI.Dialogs.MessageBoxButtons.YES_CANCEL
				});
				*/

				var confirmPopup = this.getConfirmDeletePopup(options);
				confirmPopup.show();
			}
			else
			{
				/*
				BX.UI.Dialogs.MessageBox.show({
					title: BX.message('MAIL_MESSAGE_LIST_CONFIRM_TITLE'),
					message: BX.message('MAIL_MESSAGE_LIST_CONFIRM_TRASH_ALL'),
					onYes: function () { return true; }, // handler.bind(this),
					buttons: BX.UI.Dialogs.MessageBoxButtons.YES_CANCEL
				});
				*/

				this.runAction('delete', options);
			}
		},
		onMoveToFolderClick: function (event)
		{
			var folderOptions = event.currentTarget.dataset;
			var id = null;
			var popupSubmenu = BX.findParent(event.currentTarget, {className: 'popup-window'});
			if (popupSubmenu)
			{
				id = BX.data(popupSubmenu, 'grid-row-id');
			}
			var isDisabled = JSON.parse(folderOptions.isDisabled);
			var path = folderOptions.path;
			if ((id === null && this.getGridInstance().getRows().getSelectedIds().length === 0) || isDisabled)
			{
				return;
			}
			var selected = this.getGridInstance().getRows().getSelected();
			var resultIds = (id ? [id] : this.getGridInstance().getRows().getSelectedIds());
			resultIds = this.filterRowsByClassName(this.disabledClassName, resultIds, true);
			if (!resultIds.length)
			{
				return;
			}
			this.resetGridSelection();

			/*
			BX.UI.Dialogs.MessageBox.show({
				title: BX.message('MAIL_MESSAGE_LIST_CONFIRM_TITLE'),
				message: BX.message('MAIL_MESSAGE_LIST_CONFIRM_MOVE_ALL'),
				onYes: function () { return true; }, // handler.bind(this),
				buttons: BX.UI.Dialogs.MessageBoxButtons.YES_CANCEL
			});
			*/

			this.runAction(
				'moveToFolder',
				{
					ids: resultIds,
					params: {
						folder: path
					},
					analyticsLabel: {
						'groupCount': selected.length,
						'bindings': this.getRowsBindings(id ? [this.getGridInstance().getRows().getById(id)] : selected)
					}
				}
			);
		},
		onReadClick: function (id)
		{
			var selected = this.getGridInstance().getRows().getSelected();
			if (id === undefined && selected.length === 0)
			{
				return;
			}
			var actionName = 'all' == id || this.isSelectedRowsHaveClass('mail-msg-list-cell-unseen', id) ? 'markAsSeen' : 'markAsUnseen';
			var resultIds = this.filterRowsByClassName('mail-msg-list-cell-unseen', id, actionName !== 'markAsSeen');
			resultIds = this.filterRowsByClassName(this.disabledClassName, resultIds, true);
			if (!resultIds.length)
			{
				return;
			}

			var handler = function ()
			{
				this.userInterfaceManager.onMessagesRead(resultIds, {action: actionName});
				if (actionName === 'markAsSeen')
				{
					var count = resultIds.length;
					if ('all' == id)
					{
						count = Math.max(this.userInterfaceManager.getQuickFilterUnseenCounter(), count);
					}
					this.userInterfaceManager.updateUnreadCounters(-count);
				}
				else
				{
					this.userInterfaceManager.updateUnreadCounters(resultIds.length);
				}
				this.resetGridSelection();

				if ('all' == id)
				{
					resultIds['for_all'] = this.mailboxId + '-' + this.userInterfaceManager.getCurrentFolder();
				}

				this.runAction(actionName, {
					ids: resultIds,
					keepRows: true,
					successParams: actionName,
					analyticsLabel: {
						'groupCount': selected.length,
						'bindings': this.getRowsBindings(id ? [this.getGridInstance().getRows().getById(id)] : selected)
					},
					onSuccess: false
				});

				return true;
			};

			if ('all' == id)
			{
				BX.UI.Dialogs.MessageBox.show({
					title: BX.message('MAIL_MESSAGE_LIST_CONFIRM_TITLE'),
					message: BX.message('MAIL_MESSAGE_LIST_CONFIRM_READ_ALL'),
					onYes: handler.bind(this),
					buttons: BX.UI.Dialogs.MessageBoxButtons.YES_CANCEL
				});
			}
			else
			{
				handler.apply(this);
			}
		},
		onSpamClick: function (id)
		{
			var selected = this.getGridInstance().getRows().getSelected();
			if (id === undefined && selected.length === 0)
			{
				return;
			}
			if (!this.canMarkSpam)
			{
				this.showDirsSlider();
				return;
			}
			var actionName = this.isSelectedRowsHaveClass('js-spam', id) ? 'restoreFromSpam' : 'markAsSpam';
			var resultIds = this.filterRowsByClassName('js-spam', id, actionName !== 'restoreFromSpam');
			resultIds = this.filterRowsByClassName(this.disabledClassName, resultIds, true);
			if (!resultIds.length)
			{
				return;
			}
			var options = {
				analyticsLabel: {
					'groupCount': selected.length,
					'bindings': this.getRowsBindings(id ? [this.getGridInstance().getRows().getById(id)] : selected)
				},
				onSuccess: function ()
				{
					this.reloadGrid({});
				}
			};
			if (id !== undefined)
			{
				options.ids = [id];
			}

			/*
			BX.UI.Dialogs.MessageBox.show({
				title: BX.message('MAIL_MESSAGE_LIST_CONFIRM_TITLE'),
				message: BX.message('MAIL_MESSAGE_LIST_CONFIRM_SPAM_ALL'),
				onYes: function () { return true; }, // handler.bind(this),
				buttons: BX.UI.Dialogs.MessageBoxButtons.YES_CANCEL
			});
			*/

			this.runAction(actionName, options);
		},
		getConfirmDeletePopup: function (options)
		{
			return new BX.UI.Dialogs.MessageBox({
				title: BX.message('MAIL_MESSAGE_LIST_CONFIRM_TITLE'),
				message: BX.message('MAIL_MESSAGE_LIST_CONFIRM_DELETE'),
				buttons: [
					new BX.UI.Button({
						color: BX.UI.Button.Color.DANGER,
						text: BX.message('MAIL_MESSAGE_LIST_CONFIRM_DELETE_BTN'),
						onclick: (function (button)
						{
							this.runAction('delete', options);
							button.getContext().close();
						}).bind(this)
					}),
					new BX.UI.CancelButton({
						onclick: function (button)
						{
							button.getContext().close();
						}
					})
				]
			});
		},
		resetGridSelection: function ()
		{
			this.getGridInstance().getRows().unselectAll();
			this.getGridInstance().adjustCheckAllCheckboxes();
			// todo there is no other way to hide panel for now
			// please delete this line below
			BX.onCustomEvent('Grid::updated');
		},
		isSelectedRowsHaveClass: function (className, id)
		{
			var selectedIds = this.getGridInstance().getRows().getSelectedIds();
			var ids = selectedIds.length ? selectedIds : (id ? [id] : []);
			var resultIds = [];
			for (var i = 0; i < ids.length; i++)
			{
				var row = this.getGridInstance().getRows().getById(ids[i]);
				if (row && row.node)
				{
					var columns = row.node.getElementsByClassName(className);
					if (columns && columns.length)
					{
						return true;
					}
				}
			}
			return false;
		},
		filterRowsByClassName: function (className, ids, isReversed)
		{
			var resIds = [];
			if ('all' == ids)
			{
				resIds = this.getGridInstance().getRows().getBodyChild().map(
					function (current)
					{
						return current.getId();
					}
				);
			}
			else if (Array.isArray(ids))
			{
				resIds = ids;
			}
			else
			{
				var selectedIds = this.getGridInstance().getRows().getSelectedIds();
				resIds = selectedIds.length ? selectedIds : (ids ? [ids] : []);
			}
			var resultIds = [];
			for (var i = resIds.length - 1; i >= 0; i--)
			{
				var row = this.getGridInstance().getRows().getById(resIds[i]);
				if (row && row.node)
				{
					var columns = row.node.getElementsByClassName(className);
					if (!isReversed && (columns && columns.length))
					{
						resultIds.push(resIds[i]);
					}
					else if (isReversed && !(columns && columns.length))
					{
						resultIds.push(resIds[i]);
					}
				}
			}
			return resultIds;
		},
		notify: function (text, delay)
		{
			top.BX.UI.Notification.Center.notify({
				autoHideDelay: delay > 0 ? delay : 2000,
				content: text ? text : BX.message('MAIL_MESSAGE_LIST_NOTIFY_SUCCESS')
			});
		},
		runAction: function (actionName, options)
		{
			options = options ? options : {};
			var selectedIds = this.getGridInstance().getRows().getSelectedIds();

			if (options.ids)
			{
				selectedIds = options.ids;
			}
			if (!selectedIds.length && !selectedIds.for_all)
			{
				return;
			}
			if (!options.keepRows)
			{
				this.getGridInstance().tableFade();
			}
			var data = {ids: selectedIds};
			if (options.params)
			{
				var optionsKeys = Object.keys(Object(options.params));
				for (var nextIndex = 0, len = optionsKeys.length; nextIndex < len; nextIndex++)
				{
					var nextKey = optionsKeys[nextIndex];
					var desc = Object.getOwnPropertyDescriptor(options.params, nextKey);
					if (desc !== undefined && desc.enumerable)
					{
						data[nextKey] = options.params[nextKey];
					}
				}
			}
			BX.ajax.runComponentAction('bitrix:mail.client', actionName, {
				mode: 'ajax',
				data: data,
				analyticsLabel: options.analyticsLabel
			}).then(
				function (response)
				{
					if (options.onSuccess === false)
					{
						return;
					}
					if (options.onSuccess && typeof(options.onSuccess) === "function")
					{
						options.onSuccess.bind(this, selectedIds, options.successParams)();
						return;
					}
					this.onSuccessRequest(response, actionName);
				}.bind(this),
				function (response)
				{
					options.onError && typeof(options.onError) === "function" ?
						options.onError().bind(this, response) :
						this.onErrorRequest(response)
				}.bind(this)
			);
		},
		onErrorRequest: function (response)
		{
			options = {};
			this.checkErrorRights(response.errors);
			options.errorMessage = response.errors[0].message;
			this.reloadGrid(options)
		},
		checkErrorRights: function (errors)
		{
			for (var i = 0; i < errors.length; i++)
			{
				if (errors[i].code === this.ERROR_CODE_CAN_NOT_DELETE)
				{
					this.canDelete = false;
				}
				if (errors[i].code === this.ERROR_CODE_CAN_NOT_MARK_SPAM)
				{
					this.canMarkSpam = false;
				}
			}
		},
		onSuccessRequest: function (response, action)
		{
			this.notify();
			this.reloadGrid({});
		},
		reloadGrid: function (options)
		{
			var gridInstance = this.getGridInstance();
			if (gridInstance)
			{
				options.apply_filter = 'Y';
				gridInstance.reloadTable('POST', options);
			}
		},
		showDirsSlider: function ()
		{
			var url = BX.util.add_url_param("/mail/config/dirs", {
				mailboxId: this.mailboxId
			});
			BX.SidePanel.Instance.open(url, {
				width: 640,
				cacheable: false,
				allowChangeHistory: false
			});
			this.canDelete = true;
			this.canMarkSpam = true;
		},
		onDisabledGroupActionClick: function ()
		{
		},
		onUnreadCounterClick: function ()
		{
			this.userInterfaceManager.onUnreadCounterClick();
		},
		getCurrentFolder: function ()
		{
			return this.userInterfaceManager.getCurrentFolder();
		},
		onDirsMenuItemClick: function (el)
		{
			if (BX.data(el, 'is-disabled') == 'true')
			{
				return;
			}

			var filter = this.userInterfaceManager.getFilterInstance();

			var filterApi = filter.getApi();
			filterApi.setFields({
				'DIR': BX.data(el, 'path')
			});
			filterApi.apply();

			this.userInterfaceManager.closeMailboxMenu();
		},
		getGridInstance: function ()
		{
			return BX.Main.gridManager.getById(this.gridId).instance;
		},
		getRowsBindings: function (rows)
		{
			return BX.util.array_unique(Array.prototype.concat.apply(
				[],
				rows.map(
					function (row)
					{
						if (!row || !row.node)
						{
							return null;
						}

						return Array.prototype.map.call(
							row.node.querySelectorAll('[class^="js-bind-"] [data-type]'),
							function (node)
							{
								return node.dataset.type;
							}
						)
					}
				)
			));
		},
		getCache: function(key)
		{
			if (!key)
			{
				return;
			}

			return this.cache[key] ? this.cache[key] : null;
		},
		setCache: function(key, value)
		{
			return this.cache[key] = value;
		},
	};
})();