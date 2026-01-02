/**
 * @module im/messenger/lib/ui/context-menu/messages-auto-delete
 */

jn.define('im/messenger/lib/ui/context-menu/messages-auto-delete', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Icon } = require('assets/icons');
	const { UIMenu } = require('layout/ui/menu');

	const { Theme } = require('im/lib/theme');

	const { MessagesAutoDeleteDelay, MessagesAutoDeleteMenuIds } = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('messages-auto-delete');

	/**
	 * @class MessagesAutoDeleteContextMenu
	 */
	class MessagesAutoDeleteContextMenu
	{
		/**
		 * @param {AutoDeleteMessagesContextMenuProps} props
		 */
		static createByFileId(props)
		{
			return new this(props);
		}

		/**
		 * @constructor
		 * @param {AutoDeleteMessagesContextMenuProps} props
		 */
		constructor(props)
		{
			this.ref = props?.ref;

			this.selectedItem = Object.values(MessagesAutoDeleteDelay).includes(props.selectedItem)
				? props.selectedItem
				: MessagesAutoDeleteDelay.off;
			this.onItemSelected = props.onItemSelected;

			const actions = props.disabled ? this.getActionsWithoutRights() : this.getActions();
			this.menu = new UIMenu(actions);
		}

		/**
		 * @desc context menu actions
		 * @return {Array<contexMenuItem>}
		 */
		getActions()
		{
			return [
				this.getActionToOpenHelpdesk(),
				{
					id: MessagesAutoDeleteMenuIds.off,
					testId: this.getTestId(MessagesAutoDeleteMenuIds.off),
					title: Loc.getMessage('IMMOBILE_MESSENGER_UI_CONTEXT_MENU_AUTO_DELETE_MESSAGES_OFF'),
					iconName: this.getIcon(MessagesAutoDeleteDelay.off),
					onItemSelected: () => {
						this.onItemSelected(MessagesAutoDeleteDelay.off);
					},
					style: this.getStyle(),
				},
				{
					id: MessagesAutoDeleteMenuIds.hour,
					testId: this.getTestId(MessagesAutoDeleteMenuIds.hour),
					title: Loc.getMessage('IMMOBILE_MESSENGER_UI_CONTEXT_MENU_AUTO_DELETE_MESSAGES_HOUR'),
					iconName: this.getIcon(MessagesAutoDeleteDelay.hour),
					onItemSelected: () => {
						this.onItemSelected(MessagesAutoDeleteDelay.hour);
					},
					style: this.getStyle(),
				},
				{
					id: MessagesAutoDeleteMenuIds.day,
					testId: this.getTestId(MessagesAutoDeleteMenuIds.day),
					title: Loc.getMessage('IMMOBILE_MESSENGER_UI_CONTEXT_MENU_AUTO_DELETE_MESSAGES_DAY'),
					iconName: this.getIcon(MessagesAutoDeleteDelay.day),
					onItemSelected: () => {
						this.onItemSelected(MessagesAutoDeleteDelay.day);
					},
					style: this.getStyle(),
				},
				{
					id: MessagesAutoDeleteMenuIds.week,
					testId: this.getTestId(MessagesAutoDeleteMenuIds.week),
					title: Loc.getMessage('IMMOBILE_MESSENGER_UI_CONTEXT_MENU_AUTO_DELETE_MESSAGES_WEEK'),
					iconName: this.getIcon(MessagesAutoDeleteDelay.week),
					onItemSelected: () => {
						this.onItemSelected(MessagesAutoDeleteDelay.week);
					},
					style: this.getStyle(),
				},
				{
					id: MessagesAutoDeleteMenuIds.month,
					testId: this.getTestId(MessagesAutoDeleteMenuIds.month),
					title: Loc.getMessage('IMMOBILE_MESSENGER_UI_CONTEXT_MENU_AUTO_DELETE_MESSAGES_MONTH'),
					iconName: this.getIcon(MessagesAutoDeleteDelay.month),
					onItemSelected: () => {
						this.onItemSelected(MessagesAutoDeleteDelay.month);
					},
					style: this.getStyle(),
				},
			];
		}

		/**
		 * @desc context menu actions
		 * @return {Array<contexMenuItem>}
		 */
		getActionsWithoutRights()
		{
			const messages = {
				[MessagesAutoDeleteDelay.off]: Loc.getMessage('IMMOBILE_MESSENGER_UI_CONTEXT_MENU_AUTO_DELETE_MESSAGES_OFF'),
				[MessagesAutoDeleteDelay.hour]: Loc.getMessage('IMMOBILE_MESSENGER_UI_CONTEXT_MENU_AUTO_DELETE_MESSAGES_HOUR'),
				[MessagesAutoDeleteDelay.day]: Loc.getMessage('IMMOBILE_MESSENGER_UI_CONTEXT_MENU_AUTO_DELETE_MESSAGES_DAY'),
				[MessagesAutoDeleteDelay.week]: Loc.getMessage('IMMOBILE_MESSENGER_UI_CONTEXT_MENU_AUTO_DELETE_MESSAGES_WEEK'),
				[MessagesAutoDeleteDelay.month]: Loc.getMessage('IMMOBILE_MESSENGER_UI_CONTEXT_MENU_AUTO_DELETE_MESSAGES_MONTH'),
			};

			return [
				this.getActionToOpenHelpdesk(),
				{
					id: MessagesAutoDeleteMenuIds.notEnoughRights,
					testId: this.getTestId(MessagesAutoDeleteMenuIds.notEnoughRights),
					title: messages[this.selectedItem],
					iconName: Icon.CHECK,
					style: this.getStyle(),
				},
			];
		}

		getActionToOpenHelpdesk()
		{
			return {
				id: MessagesAutoDeleteMenuIds.helpdesk,
				testId: this.getTestId(MessagesAutoDeleteMenuIds.helpdesk),
				title: Loc.getMessage('IMMOBILE_MESSENGER_UI_CONTEXT_MENU_AUTO_DELETE_MESSAGES_HELPDESK'),
				iconName: Icon.QUESTION,
				sectionCode: 'helpdesk',
				onItemSelected: () => {
					MessagesAutoDeleteContextMenu.openHelpdesk();
				},
				style: {
					title: {
						font: {
							color: Theme.colors.base4,
						},
					},
					icon: {
						color: Theme.colors.base5,
					},
				},
			};
		}

		static openHelpdesk()
		{
			const articleCode = '24560328';
			logger.log(`MessagesAutoDeleteContextMenu.helpdesk, articleCode: ${articleCode}`);
			helpdesk.openHelpArticle(articleCode, 'helpdesk');
		}

		/**
		 * @param {string} time
		 * return {typeof Icon | null}
		 */
		getIcon(time)
		{
			return time === this.selectedItem ? Icon.CHECK : null;
		}

		/**
		 * return {object}
		 */
		getStyle()
		{
			return {
				icon: {
					color: Theme.colors.accentMainPrimary,
				},
			};
		}

		/**
		 * @param {string} time
		 * return {string}
		 */
		getTestId(time)
		{
			return `button-messages-auto-delete-${time}`;
		}

		open()
		{
			this.menu.show({ target: this.ref });
		}

		getMenuConfig()
		{
			return this.menu.getMenuConfig();
		}
	}

	module.exports = {
		MessagesAutoDeleteContextMenu,
	};
});
