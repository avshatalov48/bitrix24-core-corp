/**
 * @module im/messenger/view/dialog/notify-panel
 */
jn.define('im/messenger/view/dialog/notify-panel', (require, exports, module) => {
	const { EventFilterType } = require('im/messenger/const');
	const { StateManager } = require('im/messenger/view/lib/state-manager');
	const { ProxyView } = require('im/messenger/view/lib/proxy-view');
	const { Feature } = require('im/messenger/lib/feature');

	/**
	 * @class DialogNotifyPanel
	 */
	class DialogNotifyPanel extends ProxyView
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
				notifyPanelParams: null,
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
		 * @param {PinPanelShowParams} notifyPanelParams
		 * @param {boolean} isAnimated
		 */
		show(notifyPanelParams, isAnimated = true)
		{
			const newState = { isShow: true, notifyPanelParams };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.stateManager.updateState(newState);
				this.ui.show(notifyPanelParams, isAnimated);
			}
		}

		/**
		 * @void
		 */
		hide(isAnimated = true)
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
		 * @param {PinPanelShowParams} pinPanelParams
		 */
		update(notifyPanelParams)
		{
			const newState = { notifyPanelParams };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.ui.update(notifyPanelParams);
				this.stateManager.updateState(newState);
			}
		}
	}

	module.exports = {
		DialogNotifyPanel,
	};
});
