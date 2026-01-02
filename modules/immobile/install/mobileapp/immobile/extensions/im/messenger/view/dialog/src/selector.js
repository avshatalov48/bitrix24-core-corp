/**
 * @module im/messenger/view/dialog/selector
 */
jn.define('im/messenger/view/dialog/selector', (require, exports, module) => {
	const { EventFilterType, EventType } = require('im/messenger/const');

	const { ProxyView } = require('im/messenger/view/lib/proxy-view');
	const { StateManager } = require('im/messenger/view/lib/state-manager');

	/**
	 * @class DialogSelector
	 */
	class DialogSelector extends ProxyView
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
				isEnabled: false,
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
					EventType.dialog.multiSelect.selected,
					EventType.dialog.multiSelect.unselected,
					EventType.dialog.multiSelect.maxCountExceeded,
				],
			};
		}

		/**
		 * @param {boolean} value
		 * @param {boolean} animated
		 */
		async setEnabled(value, animated)
		{
			const newState = { isEnabled: value };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.stateManager.updateState(newState);

				return this.ui.setEnabled(value, animated);
			}

			return false;
		}

		/**
		 * @param {Array<string>} messageIds
		 */
		async select(messageIds)
		{
			if (this.isUiAvailable())
			{
				return this.ui.select(messageIds);
			}

			return false;
		}

		/**
		 * @param {Array<string>} messageIds
		 */
		async unselect(messageIds)
		{
			if (this.isUiAvailable())
			{
				return this.ui.unselect(messageIds);
			}

			return false;
		}

		/**
		 * @return {Array<string>} messageIds
		 */
		async getSelectedItems(messageIds)
		{
			if (this.isUiAvailable())
			{
				return this.ui.getSelectedItems(messageIds);
			}

			return [];
		}

		/**
		 * @return {boolean}
		 */
		getSelectEnable()
		{
			if (this.isUiAvailable())
			{
				return this.ui.enabled;
			}

			return false;
		}

		/**
		 * @param {number} count
		 * @return {boolean}
		 */
		async setSelectMaxCount(count)
		{
			if (this.isUiAvailable())
			{
				this.ui.maxCount = count;

				return true;
			}

			return false;
		}
	}

	module.exports = {
		DialogSelector,
	};
});
