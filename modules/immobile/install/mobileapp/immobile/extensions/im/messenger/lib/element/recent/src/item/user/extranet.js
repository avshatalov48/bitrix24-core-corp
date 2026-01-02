/**
 * @module im/messenger/lib/element/recent/item/user/extranet
 */
jn.define('im/messenger/lib/element/recent/item/user/extranet', (require, exports, module) => {
	const { UserItem } = require('im/messenger/lib/element/recent/item/user');

	/**
	 * @class ExtranetUserItem
	 */
	class ExtranetUserItem extends UserItem
	{}

	module.exports = {
		ExtranetUserItem,
	};
});
