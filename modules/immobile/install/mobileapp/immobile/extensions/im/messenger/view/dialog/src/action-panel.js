/**
 * @module im/messenger/view/dialog/action-panel
 */
jn.define('im/messenger/view/dialog/action-panel', (require, exports, module) => {
	const { Type } = require('type');
	const { EventFilterType, EventType } = require('im/messenger/const');

	const { StateManager } = require('im/messenger/view/lib/state-manager');
	const { ProxyView } = require('im/messenger/view/lib/proxy-view');

	/**
	 * @class DialogActionPanel
	 */
	class DialogActionPanel extends ProxyView
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
				title: null,
				buttons: null,
			};

			this.stateManager = new StateManager(state);
		}

		/**
		 * @return {AvailableEventCollection}
		 */
		getAvailableEvents()
		{
			return {
				[EventFilterType.selectMessagesMode]: [
					EventType.dialog.actionPanel.buttonTap,
					EventType.dialog.actionPanel.disabledButtonTap,
				],
			};
		}

		/**
		 * @param {boolean} animated
		 */
		async hide(animated)
		{
			const newState = { isShow: false };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.stateManager.updateState(newState);

				return this.ui.hide(animated);
			}

			return false;
		}

		/**
		 * @param {object} titleData
		 * @param {Array<ActionPanelButton>} buttons
		 */
		async show(titleData, buttons = [])
		{
			const title = Type.isStringFilled(titleData?.text) ? titleData : { text: '' };
			const newState = { title, buttons, isShow: true };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.stateManager.updateState(newState);

				return this.ui.show(title, buttons);
			}

			return false;
		}

		/**
		 * @param {object} title
		 */
		async setTitle(title)
		{
			const newState = { title };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.stateManager.updateState(newState);

				return this.ui.setTitle(title);
			}

			return false;
		}

		/**
		 * @param {Array<ActionPanelButton>} buttons
		 */
		async setButtons(buttons)
		{
			const newState = { buttons };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.stateManager.updateState(newState);

				return this.ui.setButtons(buttons);
			}

			return false;
		}
	}

	module.exports = {
		DialogActionPanel,
	};
});
