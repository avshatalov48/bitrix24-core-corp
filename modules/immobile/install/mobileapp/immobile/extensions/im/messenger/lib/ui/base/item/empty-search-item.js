/**
 * @module im/messenger/lib/ui/base/item/empty-search-item
 */
jn.define('im/messenger/lib/ui/base/item/empty-search-item', (require, exports, module) => {

	const { Loc } = require('loc');
	const { Theme } = require('im/lib/theme');
	class EmptySearchItem extends LayoutComponent
	{
		constructor(props = {})
		{
			super(props);
		}

		render()
		{
			return View(
				{
					style: {
						minHeight: 60,
						justifyContent: 'center',
						alignItems: 'center',
						alignContent: 'center',
					},
				},
				Text({
					style: {
						color: Theme.colors.base3,
						fontSize: 18,
					},
					text: Loc.getMessage('MESSENGER_ITEM_EMPTY_SEARCH_TEXT'),
				}),
			);
		}
	}

	module.exports = { EmptySearchItem };
});