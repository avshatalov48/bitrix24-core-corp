/**
 * @module im/messenger/view/dialog/join-button
 */
jn.define('im/messenger/view/dialog/join-button', (require, exports, module) => {
	const { EventFilterType } = require('im/messenger/const');

	const { StateManager } = require('im/messenger/view/lib/state-manager');
	const { ProxyView } = require('im/messenger/view/lib/proxy-view');

	/**
	 * @class DialogJoinButton
	 */
	class DialogJoinButton extends ProxyView
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
				isShow: false,
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

		/**
		 * @param {boolean} [isAnimated=false]
		 */
		hide(isAnimated = false)
		{
			const newState = { isShow: false };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.stateManager.updateState(newState);
				this.ui.hide({ animated: isAnimated });
			}
		}

		/**
		 * @param {JoinButtonShowParams} params
		 */
		show(params)
		{
			const newState = { ...params, isShow: true };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.stateManager.updateState(newState);
				this.ui.show(params);
			}
		}
	}

	module.exports = {
		DialogJoinButton,
	};
});
