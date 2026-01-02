/**
 * @module im/messenger/lib/ui/base/loader
 */
jn.define('im/messenger/lib/ui/base/loader', (require, exports, module) => {
	const { BaseLoaderItem } = require('im/messenger/lib/ui/base/loader/src/base-loader');
	const { SpinnerLoaderItem, SpinnerDesign } = require('im/messenger/lib/ui/base/loader/src/spinner-loader');

	module.exports = {
		LoaderItem: BaseLoaderItem,
		SpinnerLoaderItem,
		SpinnerDesign,
	};
});
