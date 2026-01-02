/**
 * @module im/messenger/provider/services/anchor
 */
jn.define('im/messenger/provider/services/anchor', (require, exports, module) => {
	const { Type } = require('type');
	const { debounce } = require('utils/function');
	const { RestMethod } = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	class AnchorService
	{
		constructor(dialogId, dialogLocator)
		{
			this.dialogId = dialogId;

			this.dialogLocator = dialogLocator;

			this.serviceLocator = serviceLocator;
			/**
			 * @type {CoreApplication}
			 */
			this.core = this.serviceLocator.get('core');
			/**
			 * @type {MessengerCoreStore}
			 */
			this.store = this.core.getStore();

			this.messagesToRead = new Set();

			this.debouncedReadMessageListAnchors = debounce(this.readMessageListAnchors, 500, this);
		}

		#getChatId = () => {
			return this.store.getters['dialoguesModel/getById'](this.dialogId)?.chatId ?? null;
		};

		/**
		 * @return {DialogView|null}
		 */
		get #view()
		{
			const view = this.dialogLocator.get('view');
			if (view)
			{
				return view;
			}

			Logger.error('view is not initialized.');

			return null;
		}

		/**
		 * @public
		 * @param messageList
		 */
		readMessageListAnchors(messageList)
		{
			const anchorsToRead = this.#collectAnchorsFromMessages(messageList);
			if (Type.isArrayFilled(anchorsToRead))
			{
				anchorsToRead.forEach((anchor) => this.messagesToRead.add(anchor.messageId));
			}

			return this.#executeRead();
		}

		/**
		 * @public
		 */
		readChatAnchors()
		{
			const anchors = this.store.getters['anchorModel/getByChatId'](this.#getChatId());
			if (!Type.isArrayFilled(anchors))
			{
				return;
			}

			this.store.dispatch('anchorModel/deleteByChatId', { chatId: this.#getChatId() });

			BX.ajax.runAction(RestMethod.imV2AnchorReadChat, {
				data: {
					chatId: this.#getChatId(),
				},
			}).catch((error) => {
				Logger.error('AnchorService: read anchor error', error);
			})
			;
		}

		#collectAnchorsFromMessages(messageList)
		{
			const messageIdList = messageList.map((message) => Number(message.id));

			return this.store.getters['anchorModel/getByMessageIdList'](this.#getChatId(), messageIdList);
		}

		#executeRead = async () => {
			if (this.messagesToRead.size === 0)
			{
				return;
			}

			const messagesToReadAnchors = [...this.messagesToRead];
			this.messagesToRead.clear();

			this.store.dispatch('anchorModel/deleteByMessageIdList', {
				chatId: this.#getChatId(),
				messageIdList: messagesToReadAnchors,
			});

			this.#highlightMessages(messagesToReadAnchors);

			BX.ajax.runAction(RestMethod.imV2AnchorRead, {
				data: {
					messageIds: messagesToReadAnchors,
				},
			}).catch((error) => {
				Logger.error('AnchorService: read anchor error', error);
			});
		};

		#highlightMessages(messages)
		{
			messages.forEach((messageId) => {
				this.#view.highlightMessageById(messageId);
			});
		}
	}

	module.exports = { AnchorService };
});
