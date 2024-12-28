/**
 * @module im/messenger/controller/sidebar/base/sidebar-controller
 */
jn.define('im/messenger/controller/sidebar/base/sidebar-controller', (require, exports, module) => {
	const { UIMenu } = require('layout/ui/menu');
	const { Icon } = require('assets/icons');
	const { Loc } = require('loc');
	const { SidebarHeaderContextMenuActionType } = require('im/messenger/const');

	const { DialogHelper } = require('im/messenger/lib/helper');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');
	const { Feature } = require('im/messenger/lib/feature');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--sidebar-controller');

	const { SidebarHeaderContextMenuType } = require('im/messenger/controller/sidebar/lib/const');

	/**
	 * @class BaseSidebarController
	 */
	class BaseSidebarController
	{
		/**
		 * @constructor
		 * @param {object} options
		 * @param {string} options.dialogId
		 */
		constructor(options)
		{
			this.options = options;
			this.dialogId = options.dialogId;
			this.store = serviceLocator.get('core').getStore();
			this.storeManager = serviceLocator.get('core').getStoreManager();
			this.widget = null;
			this.headerContextMenuButtons = [];
			this.getHelper();
		}

		/**
		 * @return {Array<*>}
		 */
		getRightButtons()
		{
			this.prepareHeaderContextMenuButtons();

			const buttons = [];
			if (this.headerContextMenuButtons.length > 0)
			{
				buttons.push({
					type: SidebarHeaderContextMenuType.more,
					id: SidebarHeaderContextMenuType.more,
					testId: `SIDEBAR_HEADER_BUTTON_${SidebarHeaderContextMenuType.more.toUpperCase()}`,
				});
			}

			return buttons;
		}

		/**
		 * @void
		 */
		prepareHeaderContextMenuButtons()
		{
			if (this.canEdit())
			{
				this.headerContextMenuButtons.push(this.getHeaderButtonEdit());
			}

			if (this.canLeave())
			{
				this.headerContextMenuButtons.push(this.getHeaderButtonLeave());
			}

			if (this.canDelete())
			{
				this.headerContextMenuButtons.push(this.getHeaderButtonDelete());
			}
		}

		canEdit()
		{
			return Feature.isChatComposerSupported && ChatPermission.isCanEditDialog(this.dialogId);
		}

		canLeave()
		{
			if (!this.helper)
			{
				return false;
			}

			const canLeave = ChatPermission.isCanLeaveFromChat(this.dialogId);
			if (this.helper.isCopilot)
			{
				return canLeave && this.helper.dialogModel.userCounter > 2;
			}

			return canLeave;
		}

		canDelete()
		{
			if (!this.helper)
			{
				return false;
			}

			return this.helper.canBeDeleted;
		}

		getHelper()
		{
			this.helper = DialogHelper.createByDialogId(this.dialogId);
			if (!this.helper)
			{
				logger.error(`${this.constructor.name}.prepareHeaderContextMenuButtons: unknown dialogId`, this.dialogId);
			}
		}

		getHeaderButtonEdit()
		{
			return {
				id: SidebarHeaderContextMenuActionType.edit,
				title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_CONTEXT_HEADER_MENU_EDIT'),
				showIcon: true,
				iconName: Icon.EDIT_SIZE_M,
				testId: this.getContextMenuTestId(SidebarHeaderContextMenuActionType.edit),
				onItemSelected: this.onHeaderMenuEditDialog.bind(this),
			};
		}

		getHeaderButtonLeave()
		{
			return {
				id: SidebarHeaderContextMenuActionType.leave,
				title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_CONTEXT_HEADER_MENU_LEAVE'),
				showIcon: true,
				iconName: Icon.LOG_OUT,
				testId: this.getContextMenuTestId(SidebarHeaderContextMenuActionType.leave),
				onItemSelected: this.onHeaderMenuLeaveDialog.bind(this),
			};
		}

		getHeaderButtonDelete()
		{
			return {
				id: SidebarHeaderContextMenuActionType.delete,
				title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_CONTEXT_HEADER_MENU_DELETE'),
				showIcon: true,
				iconName: Icon.TRASHCAN,
				testId: this.getContextMenuTestId(SidebarHeaderContextMenuActionType.delete),
				isDestructive: true,
				onItemSelected: this.onHeaderMenuDeleteDialog.bind(this),
			};
		}

		/**
		 * @desc Method binding this for use in handlers
		 * @void
		 */
		bindMethods()
		{
			this.onBarButtonTapWidget = this.onBarButtonTapWidget.bind(this);
			this.onDestroySidebar = this.onDestroySidebar.bind(this);
			this.onDeleteChat = this.onDeleteChat.bind(this);
		}

		subscribeWidgetEvents()
		{
			logger.log(`${this.constructor.name}.subscribeWidgetEvents`);
			this.widget.on('barButtonTap', this.onBarButtonTapWidget);
		}

		/**
		 * @param {DialogId} dialogId
		 */
		onDeleteChat({ dialogId })
		{
			if (String(this.dialogId) !== String(dialogId))
			{
				return;
			}
			this.headerContextMenu?.hide();
			this.widget.back();
		}

		onDestroySidebar()
		{
			this.widget.back();
		}

		onBarButtonTapWidget()
		{
			logger.info(`${this.constructor.name}.onBarButtonTapWidget`);
			this.showHeaderPopupMenu();
		}

		showHeaderPopupMenu()
		{
			this.headerContextMenu = new UIMenu(this.headerContextMenuButtons);
			this.headerContextMenu.show();
		}

		/**
		 * @abstract
		 */
		onHeaderMenuEditDialog()
		{
			logger.error(`${this.constructor.name}.onHeaderMenuEditDialog is not override`);
		}

		/**
		 * @abstract
		 */
		onHeaderMenuDeleteDialog()
		{
			logger.error(`${this.constructor.name}.onHeaderMenuDeleteDialog is not override`);
		}

		/**
		 * @abstract
		 */
		onHeaderMenuLeaveDialog()
		{
			logger.error(`${this.constructor.name}.onHeaderMenuLeaveDialog is not override`);
		}

		/**
		 * @param {string} id
		 * @returns {string}
		 */
		getContextMenuTestId(id)
		{
			return `SIDEBAR_CONTEXT_MENU_${id.toUpperCase()}`;
		}
	}
	module.exports = { BaseSidebarController };
});
