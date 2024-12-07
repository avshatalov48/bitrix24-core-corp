/**
 * @module im/messenger/controller/dialog/lib/message-avatar-menu
 */
jn.define('im/messenger/controller/dialog/lib/message-avatar-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const {
		EventType,
		BBCode,
		ComponentCode,
	} = require('im/messenger/const');
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
					icon: Icon.MESSAGE,
					onClickCallback: openDialogWithUserHandler,
				},

			];

			if (this.options.isCanMention)
			{
				actions.push({
					id: 'mention',
					title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_AVATAR_MENU_MENTION'),
					icon: Icon.MENTION,
					onClickCallback: mentionUserHandler,
				});
			}

			if (!this.options.isBot)
			{
				const showUserProfileHandler = () => {
					this.menu.close(() => this.showUserProfile());
				};

				actions.unshift(
					{
						id: 'show-profile',
						title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_MESSAGE_AVATAR_MENU_PROFILE'),
						icon: Icon.PERSON,
						onClickCallback: showUserProfileHandler,
					},
				);
			}

			return actions;
		}

		async open()
		{
			this.bindMethods();
			this.subscribeExternalEvents();

			this.layoutWidget = await this.menu.show();
			this.layoutWidget.on(EventType.view.close, () => {
				this.unsubscribeExternalEvents();
			});
		}

		showUserProfile()
		{
			UserProfile.show(this.authorId, {
				backdrop: true,
				openingDialogId: this.options.dialogId,
			});
		}

		openDialogWithUser()
		{
			MessengerEmitter.emit(EventType.messenger.openDialog, {
				dialogId: this.authorId,
			}, ComponentCode.imMessenger);
		}

		mentionUser()
		{
			BX.onCustomEvent(EventType.dialog.external.mention, [this.authorId, BBCode.user, this.options.dialogId]);
		}

		bindMethods()
		{
			this.deleteDialogHandler = this.deleteDialogHandler.bind(this);
		}

		subscribeExternalEvents()
		{
			BX.addCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
		}

		unsubscribeExternalEvents()
		{
			BX.removeCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
		}

		deleteDialogHandler({ dialogId })
		{
			if (String(this.options.dialogId) !== String(dialogId))
			{
				return;
			}

			this.menu.close(() => {});
		}
	}

	module.exports = {
		MessageAvatarMenu,
	};
});
