/**
 * @module im/messenger/lib/ui/base/item
 */
jn.define('im/messenger/lib/ui/base/item', (require, exports, module) => {

	const { Item } = require('im/messenger/lib/ui/base/item/item');
	const { SelectedItem } = require('im/messenger/lib/ui/base/item/selected-item');
	const { EmptySearchItem } = require('im/messenger/lib/ui/base/item/empty-search-item');

	module.exports = { Item, SelectedItem, EmptySearchItem };
});