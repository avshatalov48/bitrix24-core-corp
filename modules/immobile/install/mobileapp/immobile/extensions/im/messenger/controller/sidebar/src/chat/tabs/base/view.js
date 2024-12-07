/**
 * @module im/messenger/controller/sidebar/chat/tabs/base/view
 */
jn.define('im/messenger/controller/sidebar/chat/tabs/base/view', (require, exports, module) => {
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--base-tab-view');

	/**
	 * @class BaseSidebarTabView
	 */
	class BaseSidebarTabView extends LayoutComponent
	{
		componentDidMount()
		{
			logger.log(`${this.constructor.name}.componentDidMount`);
			this.bindListener();
			this.subscribeAllEvents();
		}

		componentDidUpdate()
		{
			logger.log(`${this.constructor.name}.componentDidUpdate`);
		}

		componentWillUnmount()
		{
			logger.log(`${this.constructor.name}.componentWillUnmount`);
			this.onClose();
		}

		bindListener()
		{
			this.onCurrentTabSelected = this.onCurrentTabSelected.bind(this);
			this.onClose = this.onClose.bind(this);
		}

		subscribeAllEvents()
		{
			this.subscribeStoreEvents();
			this.subscribeCustomEvents();
		}

		subscribeStoreEvents()
		{}

		unsubscribeStoreEvents()
		{}

		subscribeCustomEvents()
		{
			logger.log(`${this.constructor.name}.subscribeCustomEvents`);
			BX.addCustomEvent('onCurrentTabSelected', this.onCurrentTabSelected);
			BX.addCustomEvent('onCloseSidebarWidget', this.onClose);
		}

		unsubscribeCustomEvents()
		{
			logger.log(`${this.constructor.name}.unsubscribeCustomEvents`);
			BX.removeCustomEvent('onCurrentTabSelected', this.onCurrentTabSelected);
			BX.removeCustomEvent('onCloseSidebarWidget', this.onClose);
		}

		onCurrentTabSelected(id)
		{
			logger.log(`${this.constructor.name}.onCurrentTabSelected id:`, id);
			if (id === this.props.id)
			{
				this.scrollToBegin();
			}
		}

		/**
		 * @abstract
		 */
		scrollToBegin()
		{}

		onClose()
		{
			logger.log(`${this.constructor.name}.onClose`);
			this.unsubscribeStoreEvents();
			this.unsubscribeCustomEvents();
		}
	}

	module.exports = { BaseSidebarTabView };
});
