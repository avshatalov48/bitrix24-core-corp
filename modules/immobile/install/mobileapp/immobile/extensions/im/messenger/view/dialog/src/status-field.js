/**
 * @module im/messenger/view/dialog/status-field
 */
jn.define('im/messenger/view/dialog/status-field', (require, exports, module) => {
	const { EventFilterType } = require('im/messenger/const');

	const { ProxyView } = require('im/messenger/view/lib/proxy-view');
	const { StateManager } = require('im/messenger/view/lib/state-manager');

	/**
	 * @class DialogStatusField
	 */
	class DialogStatusField extends ProxyView
	{
		/**
		 * @constructor
		 * @param {JNBaseClassInterface} ui
		 * @param {EventFilter} eventFilter
		 */
		constructor(ui, eventFilter)
		{
			super(ui, eventFilter);

			this.initStateManager();
		}

		initStateManager()
		{
			const state = {
				params: null,
			};

			this.stateManager = new StateManager(state);
		}

		/**
		 * @return {AvailableEventCollection}
		 */
		getAvailableEvents()
		{
			return {
				[EventFilterType.selectMessagesMode]: [],
			};
		}

		clear()
		{
			if (this.isUiAvailable())
			{
				this.ui.clear();
				this.stateManager.updateState({ params: null });
			}
		}

		/**
		 * @param {StatusFieldSetParams} params
		 */
		set(params)
		{
			const newState = { params };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.ui.set(params);
				this.stateManager.updateState(newState);
			}
		}
	}

	module.exports = {
		DialogStatusField,
	};
});
