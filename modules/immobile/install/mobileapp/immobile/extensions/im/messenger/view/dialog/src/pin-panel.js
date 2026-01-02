/**
 * @module im/messenger/view/dialog/pin-panel
 */
jn.define('im/messenger/view/dialog/pin-panel', (require, exports, module) => {
	const { EventFilterType } = require('im/messenger/const');
	const { StateManager } = require('im/messenger/view/lib/state-manager');
	const { ProxyView } = require('im/messenger/view/lib/proxy-view');
	const { Feature } = require('im/messenger/lib/feature');

	/**
	 * @class DialogPinPanel
	 */
	class DialogPinPanel extends ProxyView
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
				pinPanelParams: null,
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
		 * @param {PinPanelShowParams} pinPanelParams
		 */
		show(pinPanelParams)
		{
			const newState = { isShow: true, pinPanelParams };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.stateManager.updateState(newState);
				this.ui.show(pinPanelParams);
			}
		}

		/**
		 * @void
		 */
		hide()
		{
			const newState = { isShow: false };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.stateManager.updateState(newState);
				this.ui.hide();
			}
		}

		/**
		 * @param {PinPanelShowParams} pinPanelParams
		 */
		update(pinPanelParams)
		{
			const newState = { pinPanelParams };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && Feature.isPinPanelNewAPIAvailable && hasChanges)
			{
				this.ui.update(pinPanelParams);
				this.stateManager.updateState(newState);
			}
		}

		/**
		 * @param {object} itemData
		 */
		updateItem(itemData)
		{
			if (!this.isUiAvailable())
			{
				return;
			}

			if (Feature.isPinPanelNewAPIAvailable)
			{
				this.ui.updateItems([itemData]);
			}
			else
			{
				this.ui.updateItem(itemData.item);
			}
		}

		/**
		 * @param {object[]} itemData
		 */
		updateItems(items)
		{
			if (!this.isUiAvailable() && Feature.isPinPanelNewAPIAvailable)
			{
				this.ui.updateItems(items);
			}
		}

		/**
		 * @void
		 */
		showNextItem()
		{
			if (this.isUiAvailable())
			{
				this.ui.showNextItem();
			}
		}

		/**
		 * @void
		 */
		showPreviousItem()
		{
			if (this.isUiAvailable())
			{
				this.ui.showPreviousItem();
			}
		}

		/**
		 * @param {string} id
		 */
		showItemById(id)
		{
			if (this.isUiAvailable())
			{
				this.ui.showItemById(id);
			}
		}
	}

	module.exports = {
		DialogPinPanel,
	};
});
