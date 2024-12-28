/**
 * @module im/messenger/lib/element/recent/item/user/collaber
 */
jn.define('im/messenger/lib/element/recent/item/user/collaber', (require, exports, module) => {
	const { UserItem } = require('im/messenger/lib/element/recent/item/user');

	/**
	 * @class CollaberUserItem
	 */
	class CollaberUserItem extends UserItem
	{}

	module.exports = {
		CollaberUserItem,
	};
});
