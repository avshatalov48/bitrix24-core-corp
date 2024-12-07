/**
 * @module im/messenger/controller/dialog/lib/draft-manager
 */
jn.define('im/messenger/controller/dialog/lib/draft-manager', (require, exports, module) => {
	const { debounce } = require('utils/function');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DraftType } = require('im/messenger/const');
	const { ObjectUtils } = require('im/messenger/lib/utils');

	class DraftManager
	{
		/**
		 * @param {DialogView} view
		 * @param {ReplyManager} replyManager
		 * @param {string|number} dialogId
		 * @param {boolean} initWithExternalForward
		 */
		constructor({ view, replyManager, dialogId, initWithExternalForward })
		{
			this.store = serviceLocator.get('core').getStore();
			this.view = view;
			this.dialogId = dialogId;
			this.replyManager = replyManager;
			this.initWithExternalForward = initWithExternalForward;
			this.setInStore = debounce(this.setInStore, 250, this, true);

			this.changeTextHandler = this.saveDraft.bind(this);

			if (initWithExternalForward)
			{
				this.clearDraft();
			}
			this.start();
		}

		start()
		{
			const draft = this.store.getters['draftModel/getById'](this.dialogId);

			if (!draft)
			{
				return;
			}

			const draftMessage = {
				id: draft.messageId,
				type: draft.messageType,
				username: draft.userName,
				message: draft.message,
				image: draft.image,
				video: draft.video,
			};

			if (!ObjectUtils.isStringFullSpace(draft.text))
			{
				this.view.setInput(draft.text);
			}

			switch (draft.type)
			{
				case DraftType.edit:
				{
					this.replyManager.initializeEditingMessage(draftMessage, this.initWithExternalForward);

					break;
				}

				case DraftType.reply:
				{
					this.replyManager.initializeQuotingMessage(draftMessage, this.initWithExternalForward);

					break;
				}

				default:
				{
					break;
				}
			}
		}

		saveDraft(message)
		{
			/** @type DraftModelState */
			const draft = {
				dialogId: this.dialogId,
				type: DraftType.text,
				text: message,
			};

			if (this.replyManager.isQuoteInProcess)
			{
				draft.type = DraftType.reply;
				draft.messageId = this.replyManager.quoteMessage.id;
				draft.messageType = this.replyManager.quoteMessage.type;
				draft.message = this.replyManager.quoteMessage.message;
				draft.userName = this.replyManager.quoteMessage.username;
				draft.video = this.replyManager.quoteMessage.video ?? null;
				draft.image = this.replyManager.quoteMessage.image ?? null;
			}
			else if (this.replyManager.isEditInProcess)
			{
				draft.type = DraftType.edit;
				draft.messageId = this.replyManager.editMessage.id;
				draft.messageType = this.replyManager.editMessage.type;
				draft.message = this.replyManager.editMessage.message;
				draft.userName = this.replyManager.editMessage.username;
				draft.video = this.replyManager.editMessage.video ?? null;
				draft.image = this.replyManager.editMessage.image ?? null;
			}

			this.setInStore(draft);
		}

		cancelReply()
		{
			const draft = this.store.getters['draftModel/getById'](this.dialogId);

			draft.type = DraftType.text;

			this.setInStore(draft);
		}

		/**
		 *
		 * @param {Message} message
		 * @param {typeof InputQuoteType} type
		 */
		setQuotMessageInStore(message, type, text)
		{
			/** @type {DraftModelState} */
			const draft = {
				dialogId: this.dialogId,
				text,
				messageType: message.type,
				messageId: message.id,
				message: message.message,
				userName: message.username,
				type,
			};

			this.setInStore(draft);
		}

		clearDraft()
		{
			this.store.dispatch('draftModel/delete', { dialogId: this.dialogId });
		}

		setInStore(draft)
		{
			this.store.dispatch('draftModel/set', draft);
		}
	}

	module.exports = { DraftManager };
});
