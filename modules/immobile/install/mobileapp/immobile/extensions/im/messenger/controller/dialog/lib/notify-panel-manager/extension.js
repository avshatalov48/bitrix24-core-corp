/**
 * @module im/messenger/controller/dialog/lib/notify-panel-manager
 */
jn.define('im/messenger/controller/dialog/lib/notify-panel-manager', (require, exports, module) => {
	const { EventType } = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');

	const { Color } = require('tokens');
	const { Icon } = require('assets/icons');

	const { getLogger } = require('im/messenger/lib/logger');

	const logger = getLogger('dialog--notify-panel-manager');

	/**
	 * @class NotifyPanelManager
	 */
	class NotifyPanelManager
	{
		/** @type {DialogLocator} */
		#dialogLocator = null;

		/** @type {MessengerCoreStore} */
		#store = null;

		/** @type {ChatService} */
		#chatService = null;

		/**
		 * @param {DialogLocator} locator
		 * @param {Function} onButtonTap
		 */
		constructor({ dialogLocator })
		{
			if (!Feature.isNotifyPanelAvailable)
			{
				return;
			}

			this.#dialogLocator = dialogLocator;
			this.#store = this.#dialogLocator.get('store');
			this.#chatService = this.#dialogLocator.get('chat-service');

			this.#bindsMethods();
			this.#subscribeViewEvents();

			logger.log(`${this.constructor.name} init: `, this);
		}

		unsubscribeViewEvents()
		{
			this.#dialogLocator.get('view').notifyPanel.off(EventType.dialog.notifyPanel.buttonTap, this.buttonTapHandler);
		}

		/**
		 * @param props
		 * @param {boolean} isAnimated
		 */
		show(props, isAnimated = true)
		{
			logger.log(`${this.constructor.name}.show props: `, props);

			this.#dialogLocator.get('view').notifyPanel.show(props, isAnimated);
		}

		/**
		 * @param {boolean} isAnimated
		 */
		hide(isAnimated = true)
		{
			logger.log(`${this.constructor.name}.hide`);

			this.#dialogLocator.get('view').notifyPanel.hide(isAnimated);
		}

		update(props)
		{
			logger.log(`${this.constructor.name}.update props: `, props);

			this.#dialogLocator.get('view').notifyPanel.update(props);
		}

		/**
		 * @param {string} title
		 * @param {string} text
		 * @returns {NotifyPanelProps}
		 */
		buildErrorNotifyPanelProps({ title, text })
		{
			const titleColor = Color.baseWhiteFixed.toHex();
			const textColor = Color.chatOverallBaseWhite2.toHex();
			const backgroundColor = Color.accentMainAlert.toHex();

			return this.#buildBaseNotifyPanelProps({
				title,
				text,
				titleColor,
				textColor,
				backgroundColor,
			});
		}

		async checkServiceHealthStatus()
		{
			if (!Feature.isNotifyPanelAvailable)
			{
				return;
			}

			const isClosedNotifyPanel = this.#store.getters['dialoguesModel/aiAssistantModel/isClosedNotifyPanel']();

			if (isClosedNotifyPanel)
			{
				return;
			}

			try
			{
				const status = await this.#chatService.healthCheckService.getServiceHealthStatus();

				if (!status)
				{
					return;
				}

				const { statusTitle, statusInfo } = status;

				const props = this.buildErrorNotifyPanelProps({
					title: statusTitle,
					text: statusInfo,
				});

				logger.log(`${this.constructor.name}.checkServiceHealthStatus props: `, props);
				this.show(props);
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}.checkServiceHealthStatus error: `, error);
			}
		}

		#bindsMethods()
		{
			this.buttonTapHandler = this.#buttonTapHandler.bind(this);
		}

		#subscribeViewEvents()
		{
			this.#dialogLocator.get('view').notifyPanel.on(EventType.dialog.notifyPanel.buttonTap, this.buttonTapHandler);
		}

		#buttonTapHandler()
		{
			this.hide();
			this.#store.dispatch('dialoguesModel/aiAssistantModel/setIsClosedNotifyPanel', true);
		}

		/**
		 * @param {string} title
		 * @param {string} text
		 * @param {string} textColor
		 * @param {string} backgroundColor
		 * @returns {NotifyPanelProps}
		 */
		#buildBaseNotifyPanelProps({
			title = '',
			text = '',
			titleColor,
			textColor,
			backgroundColor,
		})
		{
			return {
				title: {
					text: title,
					color: titleColor,
				},
				text: {
					text,
					color: textColor,
				},
				backgroundColor,
				button: {
					iconName: Icon.CROSS.getIconName(),
					color: titleColor,
				},
			};
		}
	}

	module.exports = { NotifyPanelManager };
});
