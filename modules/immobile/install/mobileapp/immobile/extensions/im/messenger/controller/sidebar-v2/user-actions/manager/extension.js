/**
 * @module im/messenger/controller/sidebar-v2/user-actions/manager
 */
jn.define('im/messenger/controller/sidebar-v2/user-actions/manager', (require, exports, module) => {

	const { addManager, removeManager } = require(
		'im/messenger/controller/sidebar-v2/user-actions/manager/src/rest-service',
	);

	/**
	 * @desc Handler remove manager
	 * @param {DialogId} dialogId
	 * @param {Number} userId
	 * @return {Promise<string>}
	 */
	function onRemoveManager(dialogId, userId)
	{
		return removeManager(dialogId, userId);
	}

	/**
	 * @desc Handler add manager
	 * @param {DialogId} dialogId
	 * @param {Number} userId
	 * @return {Promise<string>}
	 */
	function onAddManager(dialogId, userId)
	{
		return addManager(dialogId, userId);
	}

	module.exports = {
		onAddManager,
		onRemoveManager,
	};
});
