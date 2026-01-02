/**
 * @module im/messenger-v2/application/lib/dialog-manager/service
 */
jn.define('im/messenger-v2/application/lib/dialog-manager/service', (require, exports, module) => {
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const { ChatDataProvider } = require('im/messenger/provider/data/chat');
	const { ChatService } = require('im/messenger/provider/services/chat');

	/**
	 * @class DialogManagerService
	 */
	class DialogManagerService
	{
		static #instance;

		#chatDataProvider;
		#chatService;

		/**
		 * @return {DialogManagerService}
		 */
		static getInstance()
		{
			if (!this.#instance)
			{
				this.#instance = new this();
			}

			return this.#instance;
		}

		constructor()
		{
			this.logger = getLoggerWithContext('messenger-v2--dialog-manager', this);

			this.#chatDataProvider = new ChatDataProvider();
			this.#chatService = new ChatService();
		}

		/**
		 * @param dialogId
		 * @return {Promise<DialoguesModelState|null>}
		 */
		async getDialogByDialogId(dialogId)
		{
			const result = await this.#chatDataProvider.get({ dialogId });
			let dialog = result.getData();
			if (!dialog)
			{
				dialog = await this.#chatService.getByDialogId(dialogId);
			}

			this.logger.log('getDialog complete:', dialogId, dialog);

			return dialog;
		}
	}

	module.exports = { DialogManagerService };
});
