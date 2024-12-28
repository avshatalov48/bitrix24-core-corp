/**
 * @module im/messenger/controller/dialog/lib/select-manager
 */
jn.define('im/messenger/controller/dialog/lib/select-manager', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Haptics } = require('haptics');
	const { isEqual } = require('utils/object');
	const { ForwardSelector } = require('im/messenger/controller/forward-selector');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Notification, ToastType } = require('im/messenger/lib/ui/notification');
	const { EventType, EventsCheckpointType } = require('im/messenger/const');

	const logger = LoggerManager.getInstance().getLogger('dialog--select-manager');

	const ButtonId = Object.freeze({
		forward: 'forward',
		delete: 'delete',
	});

	const ButtonIconName = Object.freeze({
		forward: 'forward',
	});

	const ButtonHeight = Object.freeze({
		L: 'L',
	});

	const ButtonDesignType = Object.freeze({
		filled: 'filled',
		outline: 'outline',
		outlineNoAccent: 'outline-no-accent',
	});

	/**
	 * @class SelectManager
	 */
	class SelectManager
	{
		/** @type {Array<string>} */
		#selectedMessageIdList = [];
		#isHaveNotYoursMessages = false;
		/** @type {Array<ActionPanelButton|null>} */
		#actionPanelButtons = [];
		/** @type {number} */
		#selectLimit = MessengerParams.getMultipleActionMessageLimit();
		/** @type {MessagesModelState} */
		#savedQuoteMessage = null;

		/**
		 * @constructor
		 * @param {DialogLocator} dialogLocator
		 * @param {DialogId} dialogId
		 */
		constructor(dialogLocator, dialogId)
		{
			/** @type {DialogLocator} */
			this.locator = dialogLocator;
			/** @type {DialogId} */
			this.dialogId = dialogId;
			/** @type {DialogView} */
			this.view = this.locator.get('view');
			/** @type {MessengerCoreStore} */
			this.store = this.locator.get('store');
			/** @type {HeaderButtons} */
			this.headerButtons = this.locator.get('header-buttons');
			/** @type {ReplyManager} */
			this.replyManager = this.locator.get('reply-manager');

			this.bindMethods();
		}

		bindMethods()
		{
			this.onTapCancelMultipleSelectHeaderButton = this.onTapCancelMultipleSelectHeaderButton.bind(this);
			this.onMessageSelected = this.onMessageSelected.bind(this);
			this.onMessageUnselected = this.onMessageUnselected.bind(this);
			this.onMaxCountExceeded = this.onMaxCountExceeded.bind(this);
			this.onButtonTap = this.onButtonTap.bind(this);
			this.onDeleteMessages = this.onDeleteMessages.bind(this);
			this.onForwardMessages = this.onForwardMessages.bind(this);
		}

		/**
		 * @param {string} actionSelectMessageId
		 */
		async enableMultiSelectMode(actionSelectMessageId)
		{
			logger.log(`${this.constructor.name}.enableMultiSelectMode`);
			await this.checkQuoteInProcess();
			await this.checkForwardInProcess();
			this.activateSelectEventsCheckpoint();
			this.setSelectedMessageIdList([actionSelectMessageId]);
			this.subscribeView();
			await this.viewEnableSelectMessagesMode();
			await this.selectMessages([actionSelectMessageId]);
			this.renderRightHeaderCancelMultipleButton();
			await this.removeLeftHeaderButton();
			this.setSelectLimit();
			this.viewUpdateRestrictions({ longTap: false, reaction: false, quote: false });
			await this.actionPanelShow();
			await this.setActionPanelButtons();

			Haptics.impactMedium();
		}

		async disableSelectMessagesMode()
		{
			logger.log(`${this.constructor.name}.disableSelectMessagesMode`);
			this.restoreQuoteMessageProcess();
			this.deactivateSelectEventsCheckpoint();
			await this.viewDisableSelectMessagesMode();
			this.updateRightHeaderButton();
			this.restoreLeftHeaderButton();
			await this.actionPanelHide();
			this.viewUpdateRestrictions({ longTap: true, reaction: true, quote: true });
			this.unsubscribeView();
		}

		subscribeView()
		{
			this.view.selector.on(EventType.dialog.multiSelect.maxCountExceeded, this.onMaxCountExceeded);
			this.view.selector.on(EventType.dialog.multiSelect.selected, this.onMessageSelected);
			this.view.selector.on(EventType.dialog.multiSelect.unselected, this.onMessageUnselected);
			this.view.actionPanel.on(EventType.dialog.actionPanel.buttonTap, this.onButtonTap);
		}

		unsubscribeView()
		{
			this.view.selector.off(EventType.dialog.multiSelect.maxCountExceeded, this.onMaxCountExceeded);
			this.view.selector.off(EventType.dialog.multiSelect.selected, this.onMessageSelected);
			this.view.selector.off(EventType.dialog.multiSelect.unselected, this.onMessageUnselected);
			this.view.actionPanel.removeAll();
		}

		async onTapCancelMultipleSelectHeaderButton()
		{
			logger.log(`${this.constructor.name}.onTapCancelMultipleSelectHeaderButton`);
			await this.disableSelectMessagesMode();
		}

		/**
		 * @param {string} messageId
		 * @param {Array<string>} allSelectedMessages
		 */
		async onMessageUnselected(messageId, allSelectedMessages)
		{
			logger.log(`${this.constructor.name}.onMessageUnselected`, messageId, allSelectedMessages);
			this.setSelectedMessageIdList(this.#selectedMessageIdList.filter((id) => id !== messageId));
			await this.updateActionPanelTitle();
			await this.updateActionPanelButton();
		}

		/**
		 * @param {string} messageId
		 * @param {Array<string>} allSelectedMessages
		 */
		async onMessageSelected(messageId, allSelectedMessages)
		{
			logger.log(`${this.constructor.name}.onMessageSelected`, messageId, allSelectedMessages);
			if (this.#selectedMessageIdList.length >= this.#selectLimit)
			{
				Notification.showToast(ToastType.selectMessageLimit);

				return;
			}

			this.#selectedMessageIdList.push(messageId);
			this.setSelectedMessageIdList([...new Set(this.#selectedMessageIdList)]);
			await this.updateActionPanelTitle();
			await this.updateActionPanelButton();
		}

		onMaxCountExceeded()
		{
			logger.log(`${this.constructor.name}.onMaxCountExceeded`, this.#selectLimit);
			Notification.showToast(ToastType.selectMessageLimit);
		}

		/**
		 * @param {string} buttonId
		 */
		onButtonTap(buttonId)
		{
			logger.log(`${this.constructor.name}.onButtonTap id:`, buttonId);
			switch (buttonId)
			{
				case ButtonId.forward: return this.onForwardMessages();
				case ButtonId.delete: return this.onDeleteMessages();
				default: return null;
			}
		}

		async onForwardMessages()
		{
			const forwardController = new ForwardSelector();
			forwardController.open({
				messageIds: this.#selectedMessageIdList,
				fromDialogId: this.dialogId,
				locator: this.locator,
				onItemSelectedCallBack: async () => {
					await this.disableSelectMessagesMode();
				},
			});
		}

		onDeleteMessages()
		{
			Notification.showComingSoon();
		}

		activateSelectEventsCheckpoint()
		{
			this.view.eventsCheckpoint.activateCheckpoint(EventsCheckpointType.selectMessagesMode);
		}

		deactivateSelectEventsCheckpoint()
		{
			this.view.eventsCheckpoint.deactivateCheckpoint(EventsCheckpointType.selectMessagesMode);
		}

		async checkQuoteInProcess()
		{
			if (this.replyManager.isQuoteInProcess)
			{
				this.#savedQuoteMessage = this.replyManager.getQuoteMessage();
				await this.replyManager.finishQuotingMessage();
			}
		}

		async checkForwardInProcess()
		{
			if (this.replyManager.isForwardInProcess)
			{
				await this.replyManager.finishForwardingMessage();
			}
		}

		restoreQuoteMessageProcess()
		{
			if (this.#savedQuoteMessage)
			{
				this.replyManager.startQuotingMessage(this.#savedQuoteMessage, false);

				this.#savedQuoteMessage = null;
			}
		}

		/**
		 * @param {Array<string>|[]} [value=[]]
		 */
		setSelectedMessageIdList(value = [])
		{
			this.#selectedMessageIdList = value;
		}

		checkNotYoursMessages()
		{
			this.#isHaveNotYoursMessages = false;
			const currentUserId = MessengerParams.getUserId();
			for (const id of this.#selectedMessageIdList)
			{
				const messageModel = this.store.getters['messagesModel/getById'](id) || {};
				if (messageModel.authorId !== currentUserId)
				{
					this.#isHaveNotYoursMessages = true;
					break;
				}
			}
		}

		/**
		 * @param {Array<string>} messageIds
		 */
		selectMessages(messageIds)
		{
			return this.view.selectMessages(messageIds);
		}

		/**
		 * @param {Array<string>} messageIds
		 */
		unselectMessages(messageIds)
		{
			return this.view.unselectMessages(messageIds);
		}

		setSelectLimit()
		{
			this.view.setSelectMaxCount(this.#selectLimit);
		}

		/**
		 * @param {ChatRestrictionsParams} restrictions
		 */
		viewUpdateRestrictions(restrictions)
		{
			return this.view.updateRestrictions(restrictions);
		}

		viewEnableSelectMessagesMode()
		{
			return this.view.enableSelectMessagesMode();
		}

		viewDisableSelectMessagesMode()
		{
			return this.view.disableSelectMessagesMode();
		}

		actionPanelShow()
		{
			return this.view.actionPanelShow(this.getActionPanelTitle());
		}

		actionPanelHide()
		{
			return this.view.actionPanelHide();
		}

		updateActionPanelTitle()
		{
			return this.view.setActionPanelTitle(this.getActionPanelTitle());
		}

		/**
		 * @param {number} [count=this.#selectedMessageIdList.length]
		 * @return {object}
		 */
		getActionPanelTitle(count = this.#selectedMessageIdList.length)
		{
			const title = count > 0
				? Loc.getMessage(
					'IMMOBILE_MESSENGER_DIALOG_SELECT_MANAGER_ACTION_PANEL_COUNT',
					{
						'#COUNT#': count,
					},
				)
				: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_SELECT_MANAGER_ACTION_PANEL_EMPTY_COUNT')
			;

			return {
				text: title,
			};
		}

		/**
		 * @return {Promise}
		 */
		updateActionPanelButton()
		{
			const newButtons = this.getButtons();
			const oldButtons = this.#actionPanelButtons;
			if (!isEqual(oldButtons, newButtons))
			{
				this.#actionPanelButtons = newButtons;

				return this.view.setActionPanelButtons(this.#actionPanelButtons);
			}

			return Promise.resolve(false);
		}

		/**
		 * @return {Promise}
		 */
		setActionPanelButtons()
		{
			this.#actionPanelButtons = this.getButtons();

			return this.view.setActionPanelButtons(this.#actionPanelButtons);
		}

		renderRightHeaderCancelMultipleButton()
		{
			this.headerButtons.renderCancelMultipleButton(this.onTapCancelMultipleSelectHeaderButton);
		}

		updateRightHeaderButton()
		{
			this.headerButtons.render();
		}

		removeLeftHeaderButton()
		{
			this.view.setLeftButtons([]);
		}

		restoreLeftHeaderButton()
		{
			this.view.setLeftButtons([{
				id: 'back',
				type: 'back',
				callback: () => this.view.back(),
			}]);
		}

		/**
		 * @return {Array<ActionPanelButton>}
		 */
		getButtons()
		{
			this.checkNotYoursMessages();

			const disabledForwardButton = this.#selectedMessageIdList.length === 0;
			// const disabledDeleteButton = (this.#selectedMessageIdList.length === 0 || this.#isHaveNotYoursMessages);

			return [
				{
					id: ButtonId.forward,
					iconName: ButtonIconName.forward,
					text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_SELECT_MANAGER_FORWARD_BUTTON'),
					height: ButtonHeight.L,
					design: ButtonDesignType.filled,
					disabled: disabledForwardButton,
				},
				{
					id: ButtonId.delete,
					text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_SELECT_MANAGER_DELETE_BUTTON'),
					height: ButtonHeight.L,
					design: ButtonDesignType.outlineNoAccent, // this token needs to be changed to outline when the delete logic is done
					disabled: false, // this bool needs to be changed to disabledDeleteButton when the delete logic is done
				},
			];
		}
	}

	module.exports = { SelectManager };
});
