/**
 * @module im/messenger/controller/dialog/lib/pull-watch-manager
 */
jn.define('im/messenger/controller/dialog/lib/pull-watch-manager', (require, exports, module) => {
	const { Type } = require('type');
	const { RestMethod, UserRole, DialogType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { runAction } = require('im/messenger/lib/rest');

	const MESSAGES_TAG_PREFIX = 'IM_PUBLIC_';
	const COMMENTS_TAG_PREFIX = 'IM_PUBLIC_COMMENT_';
	const EXTEND_WATCH_INTERVAL = 600_000;

	/**
	 * @class PullWatchManager
	 */
	class PullWatchManager
	{
		#dialogId;
		#dialog;
		/** @type {PullClient} */
		#pullClient;
		#timerId;

		constructor(dialogId)
		{
			this.#dialogId = dialogId;
			this.#dialog = null;
			this.#pullClient = BX.PULL;
			this.#timerId = null;
		}

		subscribe()
		{
			this.#dialog = serviceLocator.get('core').getStore().getters['dialoguesModel/getById'](this.#dialogId);
			if (this.#isChannel())
			{
				this.#subscribeChannel();

				return;
			}

			if (this.#isCommentsChat() || !this.#isGuest())
			{
				return;
			}

			this.#subscribeOpenChat();
		}

		unsubscribe()
		{
			if (Type.isNull(this.#timerId))
			{
				return;
			}

			clearInterval(this.#timerId);
			this.#timerId = null;
			this.#pullClient.clearWatch(`${MESSAGES_TAG_PREFIX}${this.#dialog.chatId}`);
			this.#pullClient.clearWatch(`${COMMENTS_TAG_PREFIX}${this.#dialog.chatId}`);
		}

		#subscribeChannel()
		{
			this.#requestWatchStart();
			this.#pullClient.extendWatch(`${MESSAGES_TAG_PREFIX}${this.#dialog.chatId}`);
			this.#pullClient.extendWatch(`${COMMENTS_TAG_PREFIX}${this.#dialog.chatId}`);

			this.#timerId = setInterval(() => {
				this.#requestWatchStart();
				this.#pullClient.extendWatch(`${MESSAGES_TAG_PREFIX}${this.#dialog.chatId}`);
				this.#pullClient.extendWatch(`${COMMENTS_TAG_PREFIX}${this.#dialog.chatId}`);
			}, EXTEND_WATCH_INTERVAL)
			;
		}

		#subscribeOpenChat()
		{
			this.#requestWatchStart();
			this.#pullClient.extendWatch(`${MESSAGES_TAG_PREFIX}${this.#dialog.chatId}`);

			this.#timerId = setInterval(() => {
				this.#requestWatchStart();
				this.#pullClient.extendWatch(`${MESSAGES_TAG_PREFIX}${this.#dialog.chatId}`);
			}, EXTEND_WATCH_INTERVAL)
			;
		}

		#requestWatchStart()
		{
			runAction(RestMethod.imV2ChatExtendPullWatch, {
				data: {
					dialogId: this.#dialog.dialogId,
				},
			});
		}

		#isGuest()
		{
			return this.#dialog?.role === UserRole.guest && this.#dialog?.dialogId !== 'settings';
		}

		#isChannel()
		{
			return [DialogType.openChannel, DialogType.channel, DialogType.generalChannel].includes(this.#dialog?.type);
		}

		#isCommentsChat()
		{
			return this.#dialog?.type === DialogType.comment;
		}
	}

	module.exports = { PullWatchManager };
});
