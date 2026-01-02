/**
 * @module im/messenger/controller/sidebar-v2/factory
 */
jn.define('im/messenger/controller/sidebar-v2/factory', (require, exports, module) => {
	const { DialogHelper } = require('im/messenger/lib/helper');

	const SidebarType = {
		notes: 'notes',
		directChat: 'directChat',
		groupChat: 'groupChat',
		collab: 'collab',
		channel: 'channel',
		copilot: 'copilot',
		bot: 'bot',
		comments: 'comments',
	};

	const SidebarImplementation = {
		[SidebarType.notes]: 'im:messenger/controller/sidebar-v2/controller/notes',
		[SidebarType.directChat]: 'im:messenger/controller/sidebar-v2/controller/chat',
		[SidebarType.groupChat]: 'im:messenger/controller/sidebar-v2/controller/group-chat',
		[SidebarType.collab]: 'im:messenger/controller/sidebar-v2/controller/collab',
		[SidebarType.channel]: 'im:messenger/controller/sidebar-v2/controller/channel',
		[SidebarType.copilot]: 'im:messenger/controller/sidebar-v2/controller/copilot',
		[SidebarType.bot]: 'im:messenger/controller/sidebar-v2/controller/bot',
		[SidebarType.comments]: 'im:messenger/controller/sidebar-v2/controller/comment',
	};

	class SidebarLazyFactory
	{
		constructor()
		{
			this.preloadInProgress = false;
			this.preloadCompleted = false;
			this.openingRegistry = new Set();
		}

		/**
		 * @public
		 * @param {string} dialogId
		 * @param {ServiceLocator} dialogLocator
		 * @param {SidebarHeaderIntegrationConfigParams | {}} config
		 * @return {Promise<SidebarBaseController>}
		 */
		async make(dialogId, dialogLocator, config = {})
		{
			const type = resolveSidebarType(dialogId);

			if (!type)
			{
				return Promise.reject(new Error(`Sidebar V2: cannot resolve sidebar for dialogId ${dialogId}`));
			}

			if (!SidebarImplementation[type])
			{
				return Promise.reject(new Error(`Sidebar V2: cannot resolve implementation for type ${type}`));
			}

			this.openingRegistry.add(dialogId);

			try
			{
				const { ControllerClass } = await requireLazy(SidebarImplementation[type], true);

				const controller = (new ControllerClass().init({ dialogId, dialogLocator, config }));

				this.openingRegistry.delete(dialogId);

				return controller;
			}
			catch (err)
			{
				this.openingRegistry.delete(dialogId);

				return Promise.reject(err);
			}
		}

		/**
		 * @public
		 * @param {string} dialogId
		 * @return {boolean}
		 */
		alreadyOpening(dialogId)
		{
			return this.openingRegistry.has(dialogId);
		}

		/**
		 * @public
		 * @param {number} delay Wait N milliseconds before require
		 */
		preload(delay = 3000)
		{
			if (this.preloadInProgress || this.preloadCompleted)
			{
				return;
			}

			this.preloadInProgress = true;

			setTimeout(async () => {
				const extensions = [...new Set(Object.values(SidebarImplementation))].filter(Boolean);
				for (const ext of extensions)
				{
					// eslint-disable-next-line no-await-in-loop
					await requireLazy(ext, false);
				}
				this.preloadInProgress = false;
				this.preloadCompleted = true;
			}, delay);
		}
	}

	/**
	 * @param {number} dialogId
	 * @return {string|null}
	 */
	const resolveSidebarType = (dialogId) => {
		const helper = DialogHelper.createByDialogId(dialogId);

		if (!helper)
		{
			return null;
		}

		if (helper.isCurrentUserGuest)
		{
			return null;
		}

		if (helper.isNotes)
		{
			return SidebarType.notes;
		}

		if (helper.isCollab)
		{
			return SidebarType.collab;
		}

		if (helper.isChannel)
		{
			return SidebarType.channel;
		}

		if (helper.isCopilot)
		{
			return SidebarType.copilot;
		}

		// check this after copilot, because copilot is also bot
		if (helper.isBot)
		{
			return SidebarType.bot;
		}

		if (helper.isComment)
		{
			return SidebarType.comments;
		}

		if (helper.isDirect)
		{
			return SidebarType.directChat;
		}

		return SidebarType.groupChat;
	};

	module.exports = {
		SidebarType,
		resolveSidebarType,
		SidebarLazyFactory: new SidebarLazyFactory(),
	};
});
