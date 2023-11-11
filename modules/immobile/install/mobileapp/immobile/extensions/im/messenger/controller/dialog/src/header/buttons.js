/**
 * @module im/messenger/controller/dialog/header/buttons
 */
jn.define('im/messenger/controller/dialog/header/buttons', (require, exports, module) => {
	const { Loc } = require('loc');

	const { Feature } = require('im/messenger/lib/feature');
	const { Logger } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');
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
			this.longTapHandler = this.onLongTap.bind(this);
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
					color: '#11A9D9',
					testId: 'DIALOG_HEADER_AUDIO_CALL_BUTTON',
				},
				{
					id: 'call_video',
					type: 'call_video',
					badgeCode: 'call_video',
					color: '#11A9D9',
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

			const maxParticipants = 24;
			if (
				dialogData.userCounter > maxParticipants
				|| (dialogData.entityType === 'VIDEOCONF' && dialogData.entityData1 === 'BROADCAST')
			)
			{
				return [];
			}

			return [
				{
					id: 'call_audio',
					type: 'call_audio',
					badgeCode: 'call_audio',
					testId: 'DIALOG_HEADER_AUDIO_CALL_BUTTON',
					color: '#11A9D9',
				},
				{
					id: 'call_video',
					type: 'call_video',
					badgeCode: 'call_video',
					testId: 'DIALOG_HEADER_VIDEO_CALL_BUTTON',
					color: '#11A9D9',
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

		onLongTap(buttonId)
		{
			const { bitrixCallsEnabled } = jnExtensionData.get('im:messenger/controller/dialog');
			if (!Feature.isCallBetaAvailable() || bitrixCallsEnabled)
			{
				return;
			}

			if (buttonId !== 'call_video')
			{
				return;
			}

			const menu = dialogs.createPopupMenu();

			const callBetaButtonId = 'call-beta';
			const topMenuButtons = [
				{
					id: callBetaButtonId,
					title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_CREATE_CALL_BETA'),
					sectionCode: 'general',
				},
			];

			const menuButtonHandler = (event, item) => {
				if (event === 'onItemSelected' && item.id === callBetaButtonId)
				{
					const createRoomAction = 'im.call.beta.createRoom';
					BX.ajax.runAction(createRoomAction, {
						data: {
							dialogId: this.dialogId,
						},
					}).then((response) => {
						Logger.log(`${createRoomAction}: `, response.data);
					}).catch((response) => {
						Logger.error(`${createRoomAction} error: `, response.errors);
					});
				}
			};

			menu.setData(topMenuButtons, [{ id: 'general' }], menuButtonHandler);
			menu.show();
		}
	}

	module.exports = { HeaderButtons };
});
