/**
 * @module im/messenger/controller/sidebar-v2/user-actions/manager/src/rest-service
 */
jn.define('im/messenger/controller/sidebar-v2/user-actions/manager/src/rest-service', (require, exports, module) => {
	const { RestMethod } = require('im/messenger/const');
	const { runAction } = require('im/messenger/lib/rest');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--manager-rest-service');

	/**
	 * @param {DialogId} dialogId
	 * @param {Number} userId
	 * @return {Promise<string>}
	 */
	async function addManager(dialogId, userId)
	{
		const response = await runAction(RestMethod.imV2ChatAddManagers, {
			data: {
				dialogId,
				userIds: [userId],
			},
		}).catch((error) => {
			logger.error('ManagerActions.onAddManager.catch:', error);
		});

		log(response);

		return response.result;
	}

	/**
	 * @param {DialogId} dialogId
	 * @param {Number} userId
	 * @return {Promise<string>}
	 */
	async function removeManager(dialogId, userId)
	{
		const response = await runAction(RestMethod.imV2ChatDeleteManagers, {
			data: { dialogId, userIds: [userId] },
		}).catch((error) => {
			logger.error('ManagerActions.removeManager.catch:', error);
		});

		log(response);

		return response.result;
	}

	const log = ({ result } = {}) => {
		if (result === true)
		{
			logger.log('ManagerRest.removeManager.result:', result);
		}
		else
		{
			logger.error('ManagerRest.removeManager.error:', result);
		}
	};

	module.exports = {
		addManager,
		removeManager,
	};
});
