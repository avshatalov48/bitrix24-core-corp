/**
 * @module im/messenger/controller/forward-selector/view
 */
jn.define('im/messenger/controller/forward-selector/view', (require, exports, module) => {
	const { SingleSelector } = require('im/messenger/lib/ui/selector');
	const { ChatLayoutConverter } = require('im/messenger/lib/converter');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('forward-selector');

	/**
	 * @class ForwardSelectorView
	 */
	class ForwardSelectorView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			/**
			 * @type {SingleSelector}
			 */
			this.selector = null;
		}

		componentDidMount()
		{
			super.componentDidMount();
			this.props.onMount();
		}

		render()
		{
			logger.log(`${this.constructor.name} render`);

			return View(
				{},
				this.selector = new SingleSelector({
					searchMode: 'inline',
					onItemSelected: (item) => {
						this.props.onItemSelected(item);
					},
					itemList: [],
					openWithLoader: true,
					openingLoaderTitle: this.props.openingLoaderTitle,
					onChangeText: (text) => {
						this.props.onChangeText(text);
					},
					onSearchShow: (...params) => {},
				}),
			);
		}

		/**
		 *
		 * @param {Array<DialogId>} itemIdList
		 * @param {boolean} withLoader
		 */
		setItems(itemIdList, withLoader)
		{
			const items = itemIdList.map((id) => ChatLayoutConverter.toSingleSelectorItem({ id }));

			this.selector.setItems(items, withLoader);
		}
	}

	module.exports = { ForwardSelectorView };
});
