/**
 * @module im/messenger/controller/dialog/lib/comment-button
 */
jn.define('im/messenger/controller/dialog/lib/comment-button', (require, exports, module) => {
	const { debounce } = require('utils/function');
	const { DialogType, EventType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { Feature } = require('im/messenger/lib/feature');

	/**
	 * @class CommentButton
	 */
	class CommentButton
	{
		/**
		 * @param {DialogView} view
		 * @param {DialogId} dialogId
		 * @param {DialogLocator} dialogLocator
		 */
		constructor(view, dialogId, dialogLocator)
		{
			this.view = view;
			this.dialogId = dialogId;
			this.dialogLocator = dialogLocator;
			/** @type {MessengerCoreStore} */
			this.store = serviceLocator.get('core').getStore();

			this.isButtonShown = false;
			this.lastScrolledChatId = 0;

			this.bindViewEventHandlers();
			this.subscribeViewEvents();
			this.redraw();
		}

		bindViewEventHandlers()
		{
			this.onButtonTap = debounce(this.onButtonTap, 300, this, true);
		}

		subscribeViewEvents()
		{
			this.view.commentsButton.on(EventType.dialog.commentsButton.tap, this.onButtonTap);
		}

		get channelComments()
		{
			return this.store.getters['commentModel/getChannelCounterCollection'](this.dialog.chatId);
		}

		get totalChannelCommentsCounter()
		{
			let counter = 0;
			Object.values(this.channelComments).forEach((commentCounter) => {
				counter += commentCounter;
			});

			return counter;
		}

		/**
		 * @return {?DialoguesModelState}
		 */
		get dialog()
		{
			return this.store.getters['dialoguesModel/getById'](this.dialogId);
		}

		get isNeedShowButton()
		{
			return this.totalChannelCommentsCounter > 0;
		}

		redraw()
		{
			if (!this.isButtonAvailable())
			{
				return;
			}

			const commentsButton = this.view.commentsButton;
			if (this.isNeedShowButton)
			{
				if (!this.isButtonShown)
				{
					commentsButton.setCounter(String(this.totalChannelCommentsCounter));
					commentsButton.show();
					this.isButtonShown = true;

					return;
				}
				commentsButton.setCounter(String(this.totalChannelCommentsCounter));

				return;
			}

			if (this.isButtonShown)
			{
				commentsButton.hide();
				this.isButtonShown = false;
			}
		}

		getNextChatIdToJump()
		{
			const commentChatIds = this.getCommentsChatIds();
			commentChatIds.sort((a, z) => a - z);
			if (this.lastScrolledChatId === 0)
			{
				return commentChatIds[0];
			}

			const filteredChatIds = commentChatIds.filter((chatId) => chatId > this.lastScrolledChatId);
			if (filteredChatIds.length === 0)
			{
				return commentChatIds[0];
			}

			return filteredChatIds[0];
		}

		getCommentsChatIds()
		{
			return Object.keys(this.channelComments).map((chatId) => {
				return Number(chatId);
			});
		}

		isButtonAvailable()
		{
			if (!this.view.commentsButton || Feature.isFloatingButtonsBarAvailable)
			{
				return false;
			}

			const dialogHelper = DialogHelper.createByDialogId(this.dialogId);

			return Boolean(dialogHelper?.isChannel);
		}

		onButtonTap()
		{
			const chatIdToJump = this.getNextChatIdToJump();
			this.lastScrolledChatId = chatIdToJump;

			void this.dialogLocator.get('context-manager').goToMessageContextByCommentChatId({
				dialogId: this.dialogId,
				commentChatId: chatIdToJump,
				withMessageHighlight: true,
			});
		}
	}

	module.exports = { CommentButton };
});
