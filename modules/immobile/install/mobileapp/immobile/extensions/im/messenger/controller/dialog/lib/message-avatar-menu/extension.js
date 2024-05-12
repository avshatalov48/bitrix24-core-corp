/**
 * @module im/messenger/controller/dialog/lib/message-avatar-menu
 */
jn.define('im/messenger/controller/dialog/lib/message-avatar-menu', (require, exports, module) => {
	const { Loc } = require('loc');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { EventType, BBCode, ComponentCode } = require('im/messenger/const');
	const { menuIcons } = require('im/messenger/assets/common');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { Logger } = require('im/messenger/lib/logger');
	const { ContextMenu } = require('layout/ui/context-menu');

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
			this.store = serviceLocator.get('core').getStore();

			this.menu = new ContextMenu({
				actions: this.createActions(),
			});

			Logger.log('MessageAvatarMenu: created for authorId: ', this.authorId);
		}

		createActions()
		{
			const openDialogWithUserHandler = () => {
				this.menu.close(() => this.openDialogWithUser());
			};

			const mentionUserHandler = () => {
				this.menu.close(() => this.mentionUser());
			};

			const actions = [
				{
					id: 'write-message',
					title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_AVATAR_MENU_WRITE_MESSAGE'),
					data: {
						svgIcon: menuIcons.send(),
					},
					onClickCallback: openDialogWithUserHandler,
				},
				{
					id: 'mention',
					title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_AVATAR_MENU_MENTION'),
					data: {
						svgIcon: menuIcons.mention(),
					},
					onClickCallback: mentionUserHandler,
				},
			];

			if (!this.options.isBot)
			{
				const showUserProfileHandler = () => {
					this.menu.close(() => this.showUserProfile());
				};

				actions.unshift(
					{
						id: 'show-profile',
						title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_AVATAR_MENU_PROFILE'),
						data: {
							svgIcon: menuIcons.profile(),
						},
						onClickCallback: showUserProfileHandler,
					},
				);
			}

			return actions;
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
			}, ComponentCode.imMessenger);
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
