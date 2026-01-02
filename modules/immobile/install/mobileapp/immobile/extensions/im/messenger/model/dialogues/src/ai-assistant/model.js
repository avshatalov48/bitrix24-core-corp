/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/dialogues/ai-assistant/model
 */
jn.define('im/messenger/model/dialogues/ai-assistant/model', (require, exports, module) => {
	const { validateNotifyPanel } = require('im/messenger/model/dialogues/ai-assistant/validator');
	const { aiAssistantNotifyPanelDefaultElement } = require('im/messenger/model/dialogues/ai-assistant/default-element');

	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('model--dialogues-ai-assistant');

	/** @type {AiAssistantModel} */
	const aiAssistantModel = {
		namespaced: true,
		state: () => ({
			notifyPanel: aiAssistantNotifyPanelDefaultElement,
		}),
		getters: {
			/**
			 * @function dialoguesModel/aiAssistantModel/isClosedNotifyPanel
			 * @return function(): boolean
			 */
			isClosedNotifyPanel: (state) => () => state.notifyPanel.isClosedNotifyPanel,
		},
		actions: {
			/**
			 * @function dialoguesModel/aiAssistantModel/setIsClosedNotifyPanel
			 * @param {boolean} payload
			 */
			setIsClosedNotifyPanel: (store, payload) => {
				const data = {
					isClosedNotifyPanel: payload,
				};

				store.commit('updateNotifyPanel', {
					actionName: 'setIsClosedNotifyPanel',
					data: {
						...aiAssistantNotifyPanelDefaultElement,
						...validateNotifyPanel(data),
					},
				});
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<AiAssistantNotifyPanelUpdateData, AiAssistantModelActions>} payload
			 */
			updateNotifyPanel: (state, payload) => {
				logger.log('aiAssistantModel: updateNotifyPanel mutation', payload);

				state.notifyPanel = { ...payload.data };
			},
		},
	};

	module.exports = { aiAssistantModel };
});
