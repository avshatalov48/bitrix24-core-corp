/**
 * @module im/messenger/controller/dialog/lib/header/buttons
 */
jn.define('im/messenger/controller/dialog/lib/header/buttons', (require, exports, module) => {
	const AppTheme = require('apptheme');

	const { Calls } = require('im/messenger/lib/integration/immobile/calls');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { ChatPermission, UserPermission } = require('im/messenger/lib/permission-manager');

	/**
	 * @class HeaderButtons
	 */
	class HeaderButtons
	{
		/**
		 * @param {MessengerCoreStore} store
		 * @param {number|string} dialogId
		 * @param {DialogView} view
		 */
		constructor({ store, dialogId })
		{
			/** @private */
			this.store = store;

			/** @private */
			this.dialogId = dialogId;

			/** @private */
			this.timerId = null;

			this.buttons = [];

			this.tapHandler = this.onTap.bind(this);
		}

		/**
		 * @return {Array<Object>}}
		 */
		getButtons()
		{
			const isDialogWithUser = !DialogHelper.isDialogId(this.dialogId);

			this.buttons = isDialogWithUser
				? this.renderUserHeaderButtons()
				: this.renderDialogHeaderButtons()
			;
			if (Application.getPlatform() === 'android')
			{
				this.buttons.reverse();
			}

			return this.buttons;
		}

		/**
		 * @param {DialogView} view
		 */
		render(view)
		{
			if (this.buttons.length > 0)
			{
				return;
			}

			const buttons = this.getButtons().map((button) => {
				return { ...button, callback: () => {} };
			});
			if (Application.getPlatform() === 'ios')
			{
				buttons.reverse();
			}

			view.setRightButtons(buttons);
		}

		/**
		 * @private
		 */
		renderUserHeaderButtons()
		{
			const userData = this.store.getters['usersModel/getById'](this.dialogId);

			if (!UserPermission.isCanCall(userData))
			{
				return [];
			}

			return [
				{
					id: 'call_audio',
					type: 'call_audio',
					badgeCode: 'call_audio',
					color: AppTheme.colors.accentMainPrimaryalt,
					testId: 'DIALOG_HEADER_AUDIO_CALL_BUTTON',
				},
				{
					id: 'call_video',
					type: 'call_video',
					badgeCode: 'call_video',
					color: AppTheme.colors.accentMainPrimaryalt,
					testId: 'DIALOG_HEADER_VIDEO_CALL_BUTTON',
				},
			];
		}

		/**
		 * @private
		 */
		renderDialogHeaderButtons()
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (!dialogData || !ChatPermission.isCanCall(dialogData))
			{
				return [];
			}

			return [
				{
					id: 'call_audio',
					type: 'call_audio',
					badgeCode: 'call_audio',
					testId: 'DIALOG_HEADER_AUDIO_CALL_BUTTON',
					color: AppTheme.colors.accentMainPrimaryalt,
				},
				{
					id: 'call_video',
					type: 'call_video',
					badgeCode: 'call_video',
					testId: 'DIALOG_HEADER_VIDEO_CALL_BUTTON',
					color: AppTheme.colors.accentMainPrimaryalt,
				},
			];
		}

		/**
		 * @private
		 * @param {DialogHeaderButtonsIds} buttonId
		 */
		onTap(buttonId)
		{
			switch (buttonId)
			{
				case 'call_video': {
					Calls.createVideoCall(this.dialogId);
					break;
				}

				case 'call_audio': {
					Calls.createAudioCall(this.dialogId);
					break;
				}

				default: {
					break;
				}
			}
		}
	}

	module.exports = { HeaderButtons };
});
