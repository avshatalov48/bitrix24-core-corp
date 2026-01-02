/**
 * @module im/messenger/controller/sidebar-v2/tabs/participants/src/menu/base
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/participants/src/menu/base', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Icon } = require('assets/icons');
	const { SidebarActionType } = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { onSendMessage } = require('im/messenger/controller/sidebar-v2/user-actions/participants');
	const { onLeaveChat, onOpenNotes, onMentionUser } = require('im/messenger/controller/sidebar-v2/user-actions/user');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { SidebarPermissionManager } = require('im/messenger/controller/sidebar-v2/controller/base');

	/**
	 * @class ParticipantsBaseMenu
	 */
	class ParticipantsBaseMenu
	{
		/**
		 * @param {ParticipantsMenuProps} props
		 */
		constructor(props)
		{
			/** @type {ParticipantsMenuProps} */
			this.props = props;

			this.dialogHelper = DialogHelper.createByDialogId(props.dialogId);
			this.store = serviceLocator.get('core').getStore();

			this.permissionManager = new SidebarPermissionManager({
				dialogId: props.dialogId,
				dialogHelper: this.dialogHelper,
			});
		}

		/**
		 * @public
		 * @returns {SidebarMenuItem[]}
		 */
		getActions = () => this.getActionItems().filter(Boolean);

		/**
		 * @protected
		 * @abstract
		 * @return {Array<SidebarMenuItem>}
		 */
		getActionItems()
		{
			return [];
		}

		notesAction()
		{
			return {
				id: SidebarActionType.notes,
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_PARTICIPANTS_MENU_ITEM_LIST_NOTES'),
				onItemSelected: onOpenNotes,
				icon: Icon.BOOKMARK,
				testId: 'SIDEBAR_USER_MENU_NOTES',
			};
		}

		leaveAction()
		{
			return {
				id: SidebarActionType.leave,
				title: this.getMessage('IMMOBILE_SIDEBAR_V2_PARTICIPANTS_MENU_ITEM_LIST_LEAVE'),
				onItemSelected: this.onLeaveChat,
				icon: Icon.DAY_OFF,
				testId: 'SIDEBAR_USER_MENU_LEAVE',
			};
		}

		mentionAction()
		{
			return {
				id: SidebarActionType.mention,
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_PARTICIPANTS_MENU_ITEM_LIST_MENTION'),
				onItemSelected: this.onMention,
				icon: Icon.MENTION,
				testId: 'SIDEBAR_USER_MENU_MENTION',
			};
		}

		sendAction()
		{
			return {
				id: SidebarActionType.send,
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_PARTICIPANTS_MENU_ITEM_LIST_SEND'),
				onItemSelected: this.onSendMessage,
				icon: Icon.MESSAGE,
				testId: 'SIDEBAR_USER_MENU_SEND',
			};
		}

		isYou()
		{
			const { isYou } = this.props;

			return Boolean(isYou);
		}

		isAdmin()
		{
			const { isAdmin } = this.props;

			return Boolean(isAdmin);
		}

		shouldShowMenu()
		{
			return this.hasActionItems();
		}

		hasActionItems()
		{
			return this.getActionItems().length > 0;
		}

		getUserId()
		{
			const { userId } = this.props;

			return userId;
		}

		getDialogId()
		{
			return this.dialogHelper.dialogId;
		}

		onLeaveChat = () => {
			void onLeaveChat(this.getDialogId());
		};

		onMention = () => {
			onMentionUser(this.getDialogId(), this.getUserId());
		};

		onSendMessage = () => {
			onSendMessage(this.getUserId());
		};

		getMessage(messageId)
		{
			const { sidebarType } = this.props;

			const messagesTypeId = `${messageId}_${sidebarType?.toUpperCase()}`;
			if (Loc.hasMessage(messagesTypeId))
			{
				return Loc.getMessage(messagesTypeId);
			}

			return Loc.getMessage(messageId);
		}
	}

	module.exports = {
		ParticipantsBaseMenu,
	};
});
