/**
 * @module crm/entity-detail/component/aha-moments-manager
 */
jn.define('crm/entity-detail/component/aha-moments-manager', (require, exports, module) => {
	const { GoToChat } = require('crm/entity-detail/component/aha-moments-manager/go-to-chat');

	const availableAhaMoments = {
		goToChat: GoToChat,
	};

	/**
	 * @returns {*[]}
	 */
	const ahaMomentsManager = (name) => getAhaMomentClassByName(name);

	const getAhaMomentClassByName = (ahaMomentName) => {
		if (!availableAhaMoments.hasOwnProperty(ahaMomentName))
		{
			console.error(`Unknown aha: ${ahaMomentName}`);

			return null;
		}

		return availableAhaMoments[ahaMomentName];
	};

	module.exports = { ahaMomentsManager };
});
