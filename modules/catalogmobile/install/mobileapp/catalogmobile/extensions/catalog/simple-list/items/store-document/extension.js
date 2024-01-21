/**
 * @module catalog/simple-list/items/store-document
 */
jn.define('catalog/simple-list/items/store-document', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Extended } = require('layout/ui/simple-list/items/extended');
	const { mergeImmutable } = require('utils/object');

	class StoreDocument extends Extended
	{
		constructor(props)
		{
			super(props);

			this.styles = mergeImmutable(this.styles, styles);
		}
	}

	const styles = {
		client: {
			fontSize: 19,
			color: AppTheme.colors.base1,
		},
		wrapper: {
			paddingBottom: 0,
			paddingTop: 12,
			backgroundColor: AppTheme.colors.bgPrimary,
		},
	};

	module.exports = { StoreDocument };
});
