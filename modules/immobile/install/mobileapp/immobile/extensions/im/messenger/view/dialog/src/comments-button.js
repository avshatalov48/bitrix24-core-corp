/**
 * @module im/messenger/view/dialog/comments-button
 */
jn.define('im/messenger/view/dialog/comments-button', (require, exports, module) => {
	const { EventFilterType } = require('im/messenger/const');

	const { StateManager } = require('im/messenger/view/lib/state-manager');
	const { ProxyView } = require('im/messenger/view/lib/proxy-view');

	/**
	 * @class DialogCommentsButton
	 */
	class DialogCommentsButton extends ProxyView
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
				counter: null,
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
		 * @void
		 */
		show()
		{
			const newState = { isShow: true };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.stateManager.updateState(newState);
				this.ui.show();
			}
		}

		/**
		 * @param {boolean} [isAnimated=false]
		 */
		hide({ isAnimated = false } = {})
		{
			const newState = { isShow: false };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.stateManager.updateState(newState);
				this.ui.hide(isAnimated);
			}
		}

		/**
		 * @param {string} value
		 */
		setCounter(value)
		{
			const newState = { counter: value };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.ui.setCounter(value);
				this.stateManager.updateState(newState);
			}
		}
	}

	module.exports = {
		DialogCommentsButton,
	};
});
