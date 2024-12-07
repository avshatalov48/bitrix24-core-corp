/* eslint-disable es/no-nullish-coalescing-operators */
/* eslint-disable es/no-optional-chaining */
/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/controller/dialog/lib/context-manager/context-manager
 */
jn.define('im/messenger/controller/dialog/lib/context-manager/context-manager', (require, exports, module) => {
	const { Type } = require('type');

	const { AfterScrollMessagePosition } = require('im/messenger/view/dialog');
	const { EventType, ComponentCode, DialogType, OpenDialogContextType, Analytics } = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const { SoftLoader } = require('im/messenger/lib/helper');
	const { ComponentCodeService } = require('im/messenger/provider/service');
	const { openPlanLimitsWidgetByError, openPlanLimitsWidget } = require('im/messenger/lib/plan-limit');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { AnalyticsEvent } = require('analytics');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	const logger = LoggerManager.getInstance().getLogger('dialog--context-manager');

	/**
	 * @class ContextManager
	 */
	class ContextManager
	{
		#dialogId;
		#dialogLocator;
		#topLoader;
		#goToMessageContextHandler;

		/**
		 * @param {number || string} dialogId
		 * @param {DialogLocator} dialogLocator
		 */
		constructor({ dialogId, dialogLocator })
		{
			this.#dialogId = dialogId;
			this.#dialogLocator = dialogLocator;

			this.#topLoader = new SoftLoader({
				safeDisplayTime: 2000,
				showDelay: 1000,
				onShow: () => {
					this.#view.showTopLoader();
				},
				onHide: () => {
					this.#view.hideTopLoader();
				},
			});

			this.#bindEvents();
			this.#subscribeEvents();
		}

		destructor()
		{
			this.#unsubscribeEvents();
		}

		/**
		 * @return {MessageService|null}
		 */
		get #messageService()
		{
			const messageService = this.#dialogLocator.get('message-service');
			if (messageService)
			{
				return messageService;
			}

			this.#logError('messageService is not initialized.');

			return null;
		}

		/**
		 * @return {ChatService|null}
		 */
		get #chatService()
		{
			const chatService = this.#dialogLocator.get('chat-service');
			if (chatService)
			{
				return chatService;
			}

			this.#logError('chatService is not initialized.');

			return null;
		}

		/**
		 * @return {MessageRenderer|null}
		 */
		get #messageRenderer()
		{
			const messageRenderer = this.#dialogLocator.get('message-renderer');
			if (messageRenderer)
			{
				return messageRenderer;
			}

			this.#logError('messageRenderer is not initialized.');

			return null;
		}

		/**
		 * @return {DialogView|null}
		 */
		get #view()
		{
			const view = this.#dialogLocator.get('view');
			if (view)
			{
				return view;
			}

			this.#logError('view is not initialized.');

			return null;
		}

		/**
		 * @return {MessengerCoreStore|null}
		 */
		get #store()
		{
			const store = this.#dialogLocator.get('store');
			if (store)
			{
				return store;
			}

			this.#logError('store is not initialized.');

			return null;
		}

		/**
		 * @return {CoreApplication|null}
		 */
		get #core()
		{
			const core = serviceLocator.get('core');
			if (core)
			{
				return core;
			}

			this.#logError('core is not initialized.');

			return null;
		}

		#bindEvents()
		{
			this.#goToMessageContextHandler = this.goToMessageContextInCurrentDialog.bind(this);
		}

		#subscribeEvents()
		{
			BX.addCustomEvent(EventType.dialog.external.goToMessageContext, this.#goToMessageContextHandler);
		}

		#unsubscribeEvents()
		{
			BX.removeCustomEvent(EventType.dialog.external.goToMessageContext, this.#goToMessageContextHandler);
		}

		goToMessageContextInCurrentDialog(params)
		{
			if (params.dialogId !== this.#dialogId)
			{
				return;
			}

			void this.goToMessageContext(params);
		}

		/**
		 * @param {GoToMessageContextEvent} event
		 */
		async goToMessageContext({
			dialogId,
			messageId,
			parentMessageId = null,
			withMessageHighlight = true,
			targetMessagePosition = AfterScrollMessagePosition.top,
			showNotificationIfUnsupported = true,
			showPlanLimitWidget = true,
			context = OpenDialogContextType.default,
		})
		{
			if (!Feature.isGoToMessageContextSupported && showNotificationIfUnsupported)
			{
				Notification.showComingSoon();
				this.#log('goToMessageContext() method is not supported');

				return;
			}

			this.#log(`goToMessageContext: dialogId: ${dialogId}, messageId: ${messageId}, parentMessageId: ${parentMessageId}, withMessageHighlight: ${withMessageHighlight}`);

			const messageIdAsNumber = parseInt(messageId, 10);
			messageId = Type.isNumber(messageIdAsNumber) ? messageIdAsNumber : messageId;

			const isCurrentDialogContext = dialogId === this.#dialogId;
			if (!isCurrentDialogContext)
			{
				await this.#goToAnotherDialogMessageContext(
					dialogId,
					messageId,
					withMessageHighlight,
					parentMessageId,
					context,
				);

				return;
			}

			if (this.#isMessageRendered(messageId))
			{
				await this.#goToRenderedMessageContext(messageId, withMessageHighlight, targetMessagePosition);

				return;
			}

			if (Type.isStringFilled(messageId))
			{
				this.#log(`goToMessageContext: cannot access a template message through the database or server ${messageId}`);

				return;
			}

			if (showPlanLimitWidget === true)
			{
				const isChannel = DialogHelper.createByDialogId(this.#dialogId)?.isChannelOrComment;
				showPlanLimitWidget = isChannel !== true;
			}

			const isContextLoaded = await this.#goToLocalStorageMessageContext(
				messageId,
				withMessageHighlight,
				targetMessagePosition,
				showPlanLimitWidget,
			);
			if (isContextLoaded)
			{
				return;
			}

			await this.#goToServerMessageContext(messageId, withMessageHighlight, targetMessagePosition, showPlanLimitWidget);
		}

		async goToLastReadMessageContext()
		{
			const lastReadMessageId = this.#getDialogById(this.#dialogId).lastReadId;
			if (!Type.isNumber(lastReadMessageId) || lastReadMessageId === 0)
			{
				logger.warn(`${this.constructor.name}.goToLastReadMessageContext: without lastReadId`, lastReadMessageId);

				await this.goToBottomMessageContext();

				return;
			}

			/**
			 * @type GoToMessageContextEvent
			 */
			const goToMessageContextEvent = {
				dialogId: this.#dialogId,
				messageId: this.#getDialogById(this.#dialogId).lastReadId,
				withMessageHighlight: false,
			};

			this.#log('goToLastReadMessageContext()', goToMessageContextEvent);

			await this.goToMessageContext(goToMessageContextEvent);
		}

		async goToBottomMessageContext()
		{
			/**
			 * @type GoToMessageContextEvent
			 */
			const goToMessageContextEvent = {
				dialogId: this.#dialogId,
				messageId: this.#getDialogById(this.#dialogId).lastMessageId,
				withMessageHighlight: false,
				targetMessagePosition: AfterScrollMessagePosition.bottom,
				showNotificationIfUnsupported: false,
				showPlanLimitWidget: false,
			};

			this.#log('goToBottomMessageContext()', goToMessageContextEvent);

			await this.goToMessageContext(goToMessageContextEvent);
		}

		/**
		 * @param {GoToPostMessageContextEvent} options
		 */
		async goToPostMessageContext({
			postMessageId,
			withMessageHighlight = true,
		} = {})
		{
			this.#log(`goToPostMessageContext: postMessageId: ${postMessageId}`);
			if (!Type.isNumber(postMessageId))
			{
				throw new TypeError('ContextManager.goToPostMessageContext: postMessageId must be a number value.');
			}

			if (!Feature.isGoToMessageContextSupported)
			{
				Notification.showComingSoon();

				return;
			}

			if (this.#isMessageRendered(postMessageId))
			{
				await this.#goToRenderedMessageContext(postMessageId, withMessageHighlight);

				return;
			}

			this.#topLoader.show();

			const result = await this.#messageService.loadFirstPage();
			this.#resetRenderedState();
			this.#view.setContextOptions(postMessageId, withMessageHighlight);
			await this.#messageService.updateModelByContextResult(result);

			this.#topLoader.hide();

			await this.#goToRenderedMessageContext(postMessageId, withMessageHighlight);
		}

		/**
		 * @param {GoToMessageContextByCommentsChatIdEvent} options
		 */
		async goToMessageContextByCommentChatId({
			commentChatId,
			withMessageHighlight = true,
		})
		{
			this.#log(`goToMessageContextByCommentsChatId: commentChatId: ${commentChatId}`);
			if (!Type.isNumber(commentChatId))
			{
				throw new TypeError('ContextManager.goToMessageContextByCommentChatId: commentChatId must be a number value.');
			}

			if (!Feature.isGoToMessageContextSupported)
			{
				Notification.showComingSoon();

				return;
			}

			this.#topLoader.show();

			const context = await this.#messageService.loadContextByCommentChatId(commentChatId);
			const contextMessageId = context.contextMessageId;
			this.#resetRenderedState();
			this.#view.setContextOptions(contextMessageId, false, AfterScrollMessagePosition.bottom);
			await this.#messageService.updateModelByContextResult(context.result);

			this.#topLoader.hide();

			await this.#goToRenderedMessageContext(contextMessageId, withMessageHighlight, AfterScrollMessagePosition.bottom);
		}

		/**
		 * @param {number|string} dialogId
		 * @param {number|string} messageId
		 * @param {boolean} withMessageHighlight
		 * @param {number|string|null} [parentMessageId]
		 * @param {string} context
		 */
		async #goToAnotherDialogMessageContext(
			dialogId,
			messageId,
			withMessageHighlight,
			parentMessageId = null,
			context = OpenDialogContextType.default,
		)
		{
			const componentCode = await this.#getComponentCode(dialogId, messageId, parentMessageId);
			this.#log(`#goToAnotherDialogMessageContext: dialogId: ${dialogId}, messageId: ${messageId}, withMessageHighlight: ${withMessageHighlight} componentCode: ${componentCode}`);
			if (!componentCode)
			{
				Notification.showComingSoon();

				return;
			}

			MessengerEmitter.emit(EventType.messenger.openDialog, {
				dialogId,
				messageId,
				withMessageHighlight,
				checkComponentCode: false,
				context,
			}, componentCode);
		}

		/**
		 * @param {number|string} dialogId
		 * @param {number|string} messageId
		 * @param {number|string|null} [parentMessageId]
		 * @return Promise<string|null>
		 */
		async #getComponentCode(dialogId, messageId, parentMessageId = null)
		{
			// forwarded message from CoPilot
			if (Type.isStringFilled(parentMessageId) || Type.isNumber(parentMessageId))
			{
				const modelMessage = this.#store.getters['messagesModel/getById'](parentMessageId);
				const chatType = modelMessage?.forward?.chatType;
				if (chatType === DialogType.copilot)
				{
					return ComponentCode.imCopilotMessenger;
				}
			}

			return (new ComponentCodeService()).getCodeByDialogId(dialogId, ComponentCode.imMessenger);
		}

		/**
		 * @param {number|string} messageId
		 * @param {boolean} withMessageHighlight
		 * @param {string} [position]
		 */
		async #goToRenderedMessageContext(messageId, withMessageHighlight, position = AfterScrollMessagePosition.top)
		{
			this.#log(`#goToRenderedMessageContext: messageId: ${messageId}, withMessageHighlight: ${withMessageHighlight}`);
			if (!Type.isStringFilled(messageId) && !Type.isNumber(messageId))
			{
				return;
			}

			if (parseInt(messageId, 10) <= 0)
			{
				return;
			}

			await this.#view.scrollToMessageById(messageId, true, () => {
				if (withMessageHighlight)
				{
					this.#view.highlightMessageById(messageId);
				}
			}, position);
		}

		/**
		 * @param {number} messageId
		 * @param {boolean} withMessageHighlight
		 * @param {string} targetMessagePosition
		 * @param {boolean} showPlanLimitWidget
		 * @return {Promise}
		 */
		async #goToLocalStorageMessageContext(
			messageId,
			withMessageHighlight,
			targetMessagePosition = AfterScrollMessagePosition.top,
			showPlanLimitWidget = true,
		)
		{
			if (!Feature.isLocalStorageEnabled)
			{
				this.#log(`#goToLocalStorageMessageContext: messageId: ${messageId} skipped because local storage is disabled.`);

				return false;
			}

			this.#log(`#goToLocalStorageMessageContext: messageId: ${messageId}`);

			const result = await this.#messageService.loadLocalStorageContext(messageId);
			if (!result.isCompleteContext)
			{
				return false;
			}

			const isPlanLimitHistoryExceeded = result?.result?.dialogFields?.tariffRestrictions?.isHistoryLimitExceeded;
			if (isPlanLimitHistoryExceeded && showPlanLimitWidget)
			{
				const dialog = this.#getDialogById(this.#dialogId);

				const analytics = new AnalyticsEvent()
					.setSection(Analytics.Section.messageLink)
					.setP1(Analytics.P1[dialog?.type]);

				await openPlanLimitsWidget(analytics);

				return true;
			}

			const isHasContextMessage = result?.result?.hasContextMessage;
			if (!isHasContextMessage)
			{
				return false;
			}

			this.#resetRenderedState();
			this.#view.setContextOptions(messageId, false, targetMessagePosition);
			await this.#messageService.enablePageNavigation();
			await this.#messageService.updateModelByLocalStorageContextResult(result.result);

			await this.#goToRenderedMessageContext(messageId, withMessageHighlight, targetMessagePosition);

			return true;
		}

		/**
		 * @param {number} messageId
		 * @param {boolean} withMessageHighlight
		 * @param {string} targetMessagePosition
		 * @param {boolean} showPlanLimitWidget
		 * @return {Promise}
		 */
		async #goToServerMessageContext(
			messageId,
			withMessageHighlight,
			targetMessagePosition = AfterScrollMessagePosition.top,
			showPlanLimitWidget = true,
		)
		{
			this.#log(`#goToServerMessageContext: messageId: ${messageId}`);

			this.#topLoader.show();

			try
			{
				const result = await this.#messageService.loadContext(messageId);
				if (result?.answer?.error)
				{
					this.#topLoader.hide();
					if (showPlanLimitWidget)
					{
						await this.checkPlanLimitByError(result?.answer?.error);
					}

					return;
				}

				this.#resetRenderedState();
				this.#view.setContextOptions(messageId, false, targetMessagePosition);
				await this.#messageService.updateModelByContextResult(result);
				this.#topLoader.hide();
				await this.#goToRenderedMessageContext(messageId, withMessageHighlight, targetMessagePosition);
			}
			catch (error)
			{
				this.#topLoader.hide();

				logger.error('ContextManager.#goToServerMessageContext: error', error);
			}
		}

		/**
		 * @param {object} error
		 * @return void
		 */
		async checkPlanLimitByError(error)
		{
			await openPlanLimitsWidgetByError(error);
		}

		#isMessageRendered(messageId)
		{
			return this.#messageRenderer.messageIdCollection.has(messageId);
		}

		#resetRenderedState()
		{
			this.#messageRenderer.resetState();
			this.#view.resetState();
		}

		/**
		 * @param {string|number} dialogId
		 *
		 * @return {DialoguesModelState|{}}
		 */
		#getDialogById(dialogId)
		{
			const dialog = this.#store.getters['dialoguesModel/getById'](dialogId);

			return dialog || {};
		}

		#log(message)
		{
			logger.log(`${this.constructor.name}.${message}`);
		}

		#logError(message)
		{
			logger.error(`${this.constructor.name}.${message}`);
		}
	}

	module.exports = { ContextManager };
});
