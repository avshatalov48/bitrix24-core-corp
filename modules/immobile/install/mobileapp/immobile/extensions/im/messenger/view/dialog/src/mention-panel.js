/**
 * @module im/messenger/view/dialog/mention-panel
 */
jn.define('im/messenger/view/dialog/mention-panel', (require, exports, module) => {
	const { EventFilterType } = require('im/messenger/const');

	const { StateManager } = require('im/messenger/view/lib/state-manager');
	const { ProxyView } = require('im/messenger/view/lib/proxy-view');

	/**
	 * @class DialogMentionPanel
	 */
	class DialogMentionPanel extends ProxyView
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
				isOpen: false,
				isShowLoader: false,
				items: null,
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
		 * @param {Array<MentionItem>} items
		 */
		open(items)
		{
			const newState = { items, isOpen: true };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.stateManager.updateState(newState);
				this.ui.open(items);
			}
		}

		/**
		 * @void
		 */
		close()
		{
			const newState = { isOpen: false };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.stateManager.updateState(newState);
				this.ui.close();
			}
		}

		/**
		 * @param {Array<MentionItem>} items
		 */
		setItems(items)
		{
			const newState = { items };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.ui.setItems(items);
				this.stateManager.updateState(newState);
			}
		}

		/**
		 * @void
		 */
		showLoader()
		{
			const newState = { isShowLoader: true };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.stateManager.updateState(newState);
				this.ui.showLoader();
			}
		}

		/**
		 * @void
		 */
		hideLoader()
		{
			const newState = { isShowLoader: false };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.stateManager.updateState(newState);
				this.ui.hideLoader();
			}
		}
	}

	module.exports = {
		DialogMentionPanel,
	};
});
