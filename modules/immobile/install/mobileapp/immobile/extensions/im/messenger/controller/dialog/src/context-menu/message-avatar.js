/**
 * @module im/messenger/controller/dialog/context-menu/message-avatar
 */
jn.define('im/messenger/controller/dialog/context-menu/message-avatar', (require, exports, module) => {
	const { Loc } = require('loc');

	const { core } = require('im/messenger/core');
	const { EventType, BBCode } = require('im/messenger/const');
	const { menuIcons } = require('im/messenger/assets/common');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { Logger } = require('im/messenger/lib/logger');

	/**
	 * @class MessageAvatarMenu
	 */
	class MessageAvatarMenu
	{
		static createByAuthorId(authorId, options = {})
		{
			return new this(authorId, options);
		}

		constructor(authorId, options)
		{
			this.authorId = authorId;
			this.options = options;
			this.store = core.getStore();

			this.menu = new ContextMenu({
				actions: this.createActions(),
			});

			Logger.log('MessageAvatarMenu: created for authorId: ', this.authorId);
		}

		createActions()
		{
			const showUserProfileHandler = () => {
				this.menu.close(() => this.showUserProfile());
			};

			const openDialogWithUserHandler = () => {
				this.menu.close(() => this.openDialogWithUser());
			};

			const mentionUserHandler = () => {
				this.menu.close(() => this.mentionUser());
			};

			return [
				{
					id: 'show-profile',
					title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_CONTEXT_MENU_MESSAGE_AVATAR_PROFILE'),
					data: {
						svgIcon: menuIcons.profile(),
					},
					onClickCallback: showUserProfileHandler,
				},
				{
					id: 'write-message',
					title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_CONTEXT_MENU_MESSAGE_AVATAR_WRITE_MESSAGE'),
					data: {
						svgIcon: menuIcons.send(),
					},
					onClickCallback: openDialogWithUserHandler,
				},
				{
					id: 'mention',
					title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_CONTEXT_MENU_MESSAGE_AVATAR_MENTION'),
					data: {
						svgIcon: menuIcons.mention(),
					},
					onClickCallback: mentionUserHandler,
				},
			];
		}

		open()
		{
			this.menu.show();
		}

		showUserProfile()
		{
			UserProfile.show(this.authorId, { backdrop: true });
		}

		openDialogWithUser()
		{
			MessengerEmitter.emit(EventType.messenger.openDialog, {
				dialogId: this.authorId,
			});
		}

		mentionUser()
		{
			BX.onCustomEvent(EventType.dialog.external.mention, [this.authorId, BBCode.user]);
		}
	}

	module.exports = {
		MessageAvatarMenu,
	};
});
