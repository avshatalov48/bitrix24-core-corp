/**
 * @module im/messenger/controller/sidebar/chat/tabs/links/context-menu
 */
jn.define('im/messenger/controller/sidebar/chat/tabs/links/context-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { UIMenu } = require('layout/ui/menu');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType, DialogType, SidebarActionType } = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--links-context-menu');
	const { SidebarLinksService } = require('im/messenger/controller/sidebar/chat/tabs/links/service');
	const { DialogTextHelper } = require('im/messenger/controller/dialog/lib/helper/text');

	/**
	 * @class LinkContextMenu
	 */
	class LinkContextMenu
	{
		static open(props)
		{
			return new this(props).openMenu();
		}

		/**
		 * @constructor
		 * @param {LinkContextMenuProps} props
		 */
		constructor(props)
		{
			this.linkId = props.id;
			this.linkAuthorId = props.authorId;
			this.chatId = props.chatId;
			this.dialogId = props.dialogId;
			this.messageId = props.messageId;
			this.url = props.url.source;
			this.ref = props.ref;

			this.store = serviceLocator.get('core').getStore();
			this.linksService = new SidebarLinksService(this.chatId);
			this.menu = new UIMenu(this.getActions());

			logger.log(`${this.constructor.name} created for link: `);
		}

		/**
		 * @desc context menu actions
		 * @return {{
		 * id: string,
		 * title: string,
		 * iconName: typeof Icon,
		 * onItemSelected: () => void,
		 * }[]}
		 */
		getActions()
		{
			const dialog = this.store.getters['dialoguesModel/getById'](this.dialogId);
			const channelTypes = [DialogType.openChannel, DialogType.channel, DialogType.generalChannel];
			const isChannel = channelTypes.includes(dialog?.type);

			const openInDialogTitle = isChannel
				? Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_CONTEXT_MENU_OPEN_IN_CHANNEL')
				: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_CONTEXT_MENU_OPEN_IN_CHAT');

			const actions = [
				{
					id: SidebarActionType.openMessageInChat,
					testId: this.getTestId(SidebarActionType.openMessageInChat),
					title: openInDialogTitle,
					iconName: Icon.ARROW_TO_THE_RIGHT,
					onItemSelected: this.#openMessageInChat.bind(this),
				},
				{
					id: SidebarActionType.copyLink,
					testId: this.getTestId(SidebarActionType.copyLink),
					title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_LINKS_CONTEXT_MENU_COPY'),
					iconName: Icon.LINK,
					onItemSelected: this.#copyLink.bind(this),
				},
			];

			if (this.linkAuthorId === serviceLocator.get('core').getUserId())
			{
				actions.push({
					id: SidebarActionType.deleteLink,
					testId: this.getTestId(SidebarActionType.deleteLink),
					title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_LINKS_CONTEXT_MENU_DELETE'),
					iconName: Icon.MINUS,
					onItemSelected: this.#deleteLink.bind(this),
				});
			}

			return actions;
		}

		/**
		 * @constructor
		 * @param {string} id
		 * @return string
		 */
		getTestId(id)
		{
			return `SIDEBAR_TAB_LINKS_${id.toUpperCase()}`;
		}

		openMenu()
		{
			this.menu.show({ target: this.ref });
		}

		#openMessageInChat()
		{
			if (!this.messageId || !this.dialogId)
			{
				return;
			}

			logger.log(`${this.constructor.name}.openMessageInChat`, this.messageId, this.dialogId);

			MessengerEmitter.emit(EventType.sidebar.destroy);
			MessengerEmitter.emit(EventType.dialog.external.goToMessageContext, {
				dialogId: this.dialogId,
				messageId: this.messageId,
			});
		}

		#copyLink()
		{
			DialogTextHelper.copyToClipboard(
				this.url,
				{
					notificationText: this.url,
					notificationIcon: Icon.LINK,
				},
			);
			logger.log(`${this.constructor.name}.copyLink`);
		}

		#deleteLink()
		{
			if (!this.linkId)
			{
				return;
			}

			void this.linksService.deleteLink(this.linkId);
			logger.log(`${this.constructor.name}.deleteLink`, this.linkId);
		}
	}

	module.exports = {
		LinkContextMenu,
	};
});
