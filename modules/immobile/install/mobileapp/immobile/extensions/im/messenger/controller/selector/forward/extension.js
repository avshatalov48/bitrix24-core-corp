/**
 * @module im/messenger/controller/selector/forward
 */
jn.define('im/messenger/controller/selector/forward', (require, exports, module) => {
	const { openDialogSelector } = require('im/messenger/controller/selector/dialog/opener');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType } = require('im/messenger/const');
	const { Loc } = require('im/messenger/loc');
	const REPLY_MANAGER_KEY = 'reply-manager';

	class ForwardSelector
	{
		/**
		 * @param {ForwardSelectorInitProps} props
		 */
		constructor(props)
		{
			this.props = props;
		}

		/**
		 * @param {Object} parentWidget
		 * @returns {Promise}
		 */
		open({ parentWidget })
		{
			return openDialogSelector({
				title: Loc.getMessage('IMMOBILE_MESSENGER_FORWARD_SELECTOR_TITLE'),
				providerOptions: {
					withFavorite: true,
				},
				onItemSelected: this.#onDialogSelected,
				closeOnSelect: this.props.closeOnSelect ?? true,
			}, parentWidget);
		}

		/**
		 * @param {Object} item
		 * @param {string} item.id
		 */
		#onDialogSelected = async ({ item }) => {
			const {
				onDialogSelected,
				messageIds,
				fromDialogId,
				locator,
			} = this.props;

			const dialogId = item.id;

			if (onDialogSelected)
			{
				await onDialogSelected();
			}

			if (String(dialogId) === String(fromDialogId) && locator)
			{
				locator.get(REPLY_MANAGER_KEY).startForwardingMessages(messageIds);

				return;
			}

			const openDialogParams = {
				dialogId,
				forwardMessageIds: messageIds,
			};

			MessengerEmitter.emit(EventType.messenger.openDialog, openDialogParams, 'im.messenger');
		};
	}

	module.exports = { ForwardSelector };
});
