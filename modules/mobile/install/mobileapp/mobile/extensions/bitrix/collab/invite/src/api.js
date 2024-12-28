/**
 * @module collab/invite/src/api
 */
jn.define('collab/invite/src/api', (require, exports, module) => {
	const { ajaxAlertErrorHandler } = require('error');

	/**
	 * @param {number} collabId
	 * @param {number[]} userIds
	 * @param {boolean} showHistory
	 * @returns {Promise}
	 */
	const addEmployeeToCollab = (collabId, userIds, showHistory = true) => {
		const members = userIds.map((userId) => ['user', userId]);

		return BX.ajax.runAction('socialnetwork.collab.Member.add', {
			data: {
				groupId: collabId,
				members,
				showHistory: showHistory ? 'Y' : 'N',
			},
		})
			.catch(ajaxAlertErrorHandler);
	};

	/**
	 * @param {number} collabId
	 * @param {Array<{phone?: string, email?: string, firstName?: string, secondName?: string}>} users
	 * @returns {Promise}
	 */
	const inviteGuestsToCollab = (collabId, users) => {
		return BX.ajax.runAction('intranet.invite.inviteUsersToCollab', {
			data: {
				collabId,
				users,
			},
		})
			.catch(ajaxAlertErrorHandler);
	};

	module.exports = {
		addEmployeeToCollab,
		inviteGuestsToCollab,
	};
});
