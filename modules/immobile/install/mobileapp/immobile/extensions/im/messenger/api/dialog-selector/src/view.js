/**
 * @module im/messenger/api/dialog-selector/view
 */
jn.define('im/messenger/api/dialog-selector/view', (require, exports, module) => {
	const { SingleSelector } = require('im/messenger/lib/ui/selector');
	const { ChatLayoutConverter } = require('im/messenger/lib/converter/ui/layout');
	const { getLogger } = require('im/messenger/lib/logger');

	const logger = getLogger('forward-selector');

	/**
	 * @typedef {LayoutComponent<SingleSelectorProps, {}>} DialogSelectorView
	 * @class DialogSelectorView
	 */
	class DialogSelectorView extends LayoutComponent
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
	module.exports = { DialogSelectorView };
});
