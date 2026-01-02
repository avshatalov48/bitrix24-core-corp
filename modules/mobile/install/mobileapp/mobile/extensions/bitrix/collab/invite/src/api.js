/**
 * @module collab/invite/src/api
 */
jn.define('collab/invite/src/api', (require, exports, module) => {
	const { ajaxAlertErrorHandler } = require('error');
	const { RunActionExecutor } = require('rest/run-action-executor');

	const MONTH_IN_SECONDS = 2_592_000;

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

	/**
	 * @param {number} collabId
	 * @param {string} languageCode
	 * @returns {Promise}
	 */
	const getLinkByCollabId = (collabId, languageCode) => {
		return BX.ajax.runAction('intranet.invite.getLinkByCollabId', {
			data: {
				collabId,
				userLang: languageCode,
			},
		})
			.catch(ajaxAlertErrorHandler);
	};

	/**
	 * @param {string} languageCode
	 * @returns {Promise}
	 */
	const getSharingMessageText = (languageCode) => {
		return new Promise((resolve) => {
			const handler = (response) => {
				if (response?.status === 'error')
				{
					console.error(response.errors);
				}
				resolve(response);
			};

			(new RunActionExecutor('mobile.Collab.getSharingMessageText', {
				languageCode,
			}))
				.enableJson()
				.setSkipRequestIfCacheExists()
				.setCacheTtl(MONTH_IN_SECONDS)
				.setCacheId(`mobile.Collab.getSharingMessageText_${languageCode}`)
				.setCacheHandler(handler)
				.setHandler(handler)
				.call(true);
		});
	};

	module.exports = {
		addEmployeeToCollab,
		inviteGuestsToCollab,
		getLinkByCollabId,
		getSharingMessageText,
	};
});
