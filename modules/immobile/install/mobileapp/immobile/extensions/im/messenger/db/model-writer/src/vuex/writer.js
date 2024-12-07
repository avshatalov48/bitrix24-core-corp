/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/model-writer/vuex/writer
 */
jn.define('im/messenger/db/model-writer/vuex/writer', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');

	class Writer
	{
		/**
		 * @param options
		 * @param {MessengerCoreStoreManager} options.storeManager
		 * @param {{
		 *  option: OptionRepository,
		 *  recent: RecentRepository,
		 *  dialog: DialogRepository,
		 *  file: FileRepository,
		 *  user: UserRepository,
		 *  message: MessageRepository,
		 *  tempMessage: TempMessageRepository,
		 *  reaction: ReactionRepository
		 *  queue: QueueRepository
		 *  pinMessage: PinMessageRepository
		 *  sidebarFile: SidebarFileRepository
		 * }} options.repository
		 */
		constructor(options)
		{
			this.storeManager = options.storeManager;
			/** @type {MessengerCoreStore} */
			this.store = options.storeManager.store;
			this.repository = options.repository;

			this.initRouters();
			this.subscribeEvents();
		}

		initRouters()
		{
			this.addRouter = this.addRouter.bind(this);
			this.updateRouter = this.updateRouter.bind(this);
			this.updateWithIdRouter = this.updateWithIdRouter.bind(this);
			this.deleteRouter = this.deleteRouter.bind(this);
		}

		subscribeEvents()
		{
			throw new Error('Writer: You must implement subscribeEvents() method');
		}

		unsubscribeEvents()
		{
			throw new Error('Writer: You must implement unsubscribeEvents() method');
		}

		/**
		 * @param {MutationPayload} mutation.payload
		 */
		addRouter(mutation)
		{}

		/**
		 * @param {MutationPayload} mutation.payload
		 */
		updateRouter(mutation)
		{}

		/**
		 * @param {MutationPayload} mutation.payload
		 */
		updateWithIdRouter(mutation)
		{}

		/**
		 * @param {MutationPayload} mutation.payload
		 */
		deleteRouter(mutation)
		{}

		/**
		 * @protected
		 * @param mutation
		 * @return {boolean}
		 */
		checkIsValidMutation(mutation)
		{
			const actionName = mutation?.payload?.actionName;
			if (actionName)
			{
				return true;
			}

			Logger.error('Writer: invalid mutation skipped: ', mutation);

			return false;
		}
	}

	module.exports = {
		Writer,
	};
});
