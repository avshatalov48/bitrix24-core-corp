/**
 * @module im/messenger/controller/dialog/lib/sidebar
 */
jn.define('im/messenger/controller/dialog/lib/sidebar', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { UserRole, DialogType } = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { Feature } = require('im/messenger/lib/feature');

	const { ChannelSidebarController } = require('im/messenger/controller/sidebar/channel/sidebar-controller');
	const { ChatSidebarController } = require('im/messenger/controller/sidebar/chat/sidebar-controller');
	const { CommentSidebarController } = require('im/messenger/controller/sidebar/comment/sidebar-controller');
	const { CollabSidebarController } = require('im/messenger/controller/sidebar/collab/sidebar-controller');
	const { SidebarLazyFactory } = require('im/messenger/controller/sidebar-v2/factory');

	const logger = LoggerManager.getInstance().getLogger('dialog--sidebar-manager');

	/**
	 * @class SidebarManager
	 */
	class SidebarManager
	{
		/**
		 * @private
		 * @type {ChatSidebarController | ChannelSidebarController | CommentSidebarController |
		 * CollabSidebarController | null}
		 */
		#sidebar = null;

		/**
		 * @param {DialogLocator} dialogLocator
		 * @param {?DialogId} dialogId
		 * @return SidebarManager
		 */
		static getInstance(dialogLocator, dialogId)
		{
			return new this(dialogLocator, dialogId);
		}

		/**
		 * @constructor
		 * @param {DialogLocator} dialogLocator
		 * @param {?DialogId} dialogId
		 */
		constructor(dialogLocator, dialogId)
		{
			/** @type {DialogLocator} */
			this.dialogLocator = dialogLocator;
			/** @type {DialogId} */
			this.dialogId = dialogId ?? this.dialogLocator.get('dialogId');
		}

		/**
		 * @return {DialogConfigurator}
		 */
		get dialogConfigurator()
		{
			return this.dialogLocator.get('configurator');
		}

		/**
		 * @return {MessengerCoreStore}
		 */
		get store()
		{
			return this.dialogLocator.get('store');
		}

		/**
		 * @return {DialogView}
		 */
		get view()
		{
			return this.dialogLocator.get('view');
		}

		/**
		 * @return {ChatSidebarController | ChannelSidebarController
		 * | CommentSidebarController | CollabSidebarController | SidebarBaseController | null}
		 */
		get sidebar()
		{
			return this.#sidebar;
		}

		/**
		 * @param {ChatSidebarController | ChannelSidebarController
		 * | CommentSidebarController | CollabSidebarController | SidebarBaseController} value
		 */
		set sidebar(value)
		{
			this.#sidebar = value;
		}

		async open()
		{
			const { isSidebarEnabled, sidebarConfig } = this.dialogConfigurator;
			if (!isSidebarEnabled)
			{
				return;
			}

			if (Feature.isSidebarV2Enabled)
			{
				try
				{
					if (SidebarLazyFactory.alreadyOpening(this.dialogId))
					{
						logger.info('Dialog.openSidebar.v2: already opening - skip', this.dialogId);

						return;
					}

					logger.info('Dialog.openSidebar.v2: try open', this.dialogId);
					this.sidebar = await SidebarLazyFactory.make(this.dialogId, this.dialogLocator, sidebarConfig);
					void this.sidebar.open(this.view.ui);

					return;
				}
				catch (e)
				{
					logger.error('Dialog.openSidebar.v2: unable to use sidebar v2, fallback to v1', this.dialogId, e);
				}
			}

			logger.info(`${this.constructor.name}.open:`, this.dialogId);

			const dialogModel = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (!dialogModel)
			{
				logger.error(`${this.constructor.name}.open dialogModel is ${dialogModel}, dialogId:`, this.dialogId);
				Haptics.notifyFailure();

				return;
			}

			// if curren role guest then open sidebar is none for it
			if (dialogModel.role === UserRole.guest)
			{
				Haptics.notifyFailure();

				return;
			}

			const openSidebarParams = {
				dialogId: this.dialogId,
			};

			if (DialogType.comment === dialogModel.type)
			{
				this.sidebar = new CommentSidebarController(openSidebarParams);
				this.sidebar?.open(this.view.ui);

				return;
			}

			if (DialogType.collab === dialogModel.type)
			{
				this.sidebar = new CollabSidebarController(openSidebarParams);
				this.sidebar?.open(this.view.ui);

				return;
			}

			if ([DialogType.channel, DialogType.openChannel, DialogType.generalChannel].includes(dialogModel.type)
				&& dialogModel?.role !== UserRole.guest)
			{
				this.sidebar = new ChannelSidebarController(openSidebarParams);
				this.sidebar?.open(this.view.ui);

				return;
			}

			this.sidebar = new ChatSidebarController(openSidebarParams);
			this.sidebar?.open(this.view.ui);
		}
	}

	module.exports = { SidebarManager };
});
