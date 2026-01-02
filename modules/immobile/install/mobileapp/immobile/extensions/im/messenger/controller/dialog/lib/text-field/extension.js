/**
 * @module im/messenger/controller/dialog/lib/text-field
 */
jn.define('im/messenger/controller/dialog/lib/text-field', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Type } = require('type');
	const { transparent } = require('utils/color');

	const { Theme } = require('im/lib/theme');
	const { UserRole } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');
	const { DialogHelper } = require('im/messenger/lib/helper');

	/**
	 * @class {DialogTextFieldManager}
	 */
	class DialogTextFieldManager
	{
		/**
		 * @type {DialogId}
		 */
		#dialogId;
		/**
		 * @type {DialogLocator}
		 */
		#dialogLocator;
		/**
		 * @type {DialogView}
		 */
		#view;

		/**
		 * @param {DialogId} dialogId
		 * @param {DialogLocator} locator
		 */
		constructor({ dialogId, locator })
		{
			this.#dialogLocator = locator;
			this.#dialogId = dialogId;
		}

		get dialogId()
		{
			return this.#dialogId;
		}

		/**
		 * @return {DialogHelper||null}
		 */
		get dialogHelper()
		{
			return DialogHelper.createByDialogId(this.dialogId);
		}

		get view()
		{
			this.#view = this.#view ?? this.#dialogLocator.get('view');

			return this.#view;
		}

		update()
		{
			const isHide = this.#shouldHide();
			if (isHide)
			{
				this.view.hideTextField(true);
				this.view.hideKeyboard();
				this.view.hideChatJoinButton();

				return;
			}

			const isHideByPermission = this.#shouldHideByPermissions();
			if (!isHideByPermission)
			{
				this.view.showTextField(true);
				this.view.hideChatJoinButton();

				return;
			}

			this.view.hideTextField(false);
			this.setJoinButton();
		}

		setJoinButton()
		{
			const dialogModel = this.dialogHelper?.dialogModel;

			if (dialogModel?.role === UserRole.guest)
			{
				if (this.dialogHelper?.isOpenChat)
				{
					this.view.showChatJoinButton({
						text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_JOIN_BUTTON_TEXT'),
						backgroundColor: transparent(Theme.colors.accentMainPrimaryalt, 1),
						testId: 'DIALOG_OPEN_CHAT_JOIN_BUTTON',
					});

					return;
				}

				if (this.dialogHelper?.isChannel || this.dialogHelper?.isComment)
				{
					this.view.showChatJoinButton({
						text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_CHANNEL_JOIN_BUTTON_TEXT'),
						backgroundColor: transparent(Theme.colors.accentMainPrimaryalt, 1),
						testId: 'DIALOG_OPEN_CHANNEL_JOIN_BUTTON',
					});

					return;
				}
			}

			if (this.dialogHelper?.isMuted)
			{
				this.view.showChatJoinButton({
					text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_CHANNEL_JOIN_BUTTON_UNMUTE_TEXT'),
					backgroundColor: Theme.colors.chatOverallTech3.length === 7
						? transparent(Theme.colors.chatOverallTech3, 0.16)
						: Theme.colors.chatOverallTech3,
					testId: 'DIALOG_UNMUTE_BUTTON',
				});

				return;
			}

			this.view.showChatJoinButton({
				text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_CHANNEL_JOIN_BUTTON_MUTE_TEXT'),
				backgroundColor: Theme.colors.chatOverallTech3.length === 7
					? transparent(Theme.colors.chatOverallTech3, 0.16)
					: Theme.colors.chatOverallTech3,
				testId: 'DIALOG_MUTE_BUTTON',
			});
		}

		/**
		 * @desc Method check permission user for use text field
		 * @return {boolean}
		 */
		#shouldHideByPermissions()
		{
			const canPost = ChatPermission.canPost(this.dialogId);
			const isGroupDialog = DialogHelper.isDialogId(this.dialogId);

			return !canPost && isGroupDialog;
		}

		/**
		 * @desc Method check additional settings for use text field (for bot yep)
		 * @return {boolean}
		 */
		#shouldHide()
		{
			const dialogModel = serviceLocator.get('core').getStore().getters['dialoguesModel/getById'](this.dialogId);
			if (!dialogModel)
			{
				return false;
			}

			if (!Type.isBoolean(dialogModel.textFieldEnabled))
			{
				return false;
			}

			return !dialogModel.textFieldEnabled;
		}

		/**
		 * @return {boolean}
		 */
		isHide()
		{
			return this.#shouldHide() || this.#shouldHideByPermissions();
		}

		setPlaceholder()
		{
			let placeholder = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_INPUT_PLACEHOLDER_TEXT_V2');

			if (this.dialogHelper?.isChannel)
			{
				placeholder = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_CHANNEL_INPUT_PLACEHOLDER_TEXT');
			}
			else if (this.dialogHelper?.isComment)
			{
				placeholder = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_COMMENT_INPUT_PLACEHOLDER_TEXT');
			}

			this.view.setInputPlaceholder(placeholder);
		}
	}

	module.exports = { DialogTextFieldManager };
});
