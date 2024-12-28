/**
 * @module collab/invite/src/utils
 */
jn.define('collab/invite/src/utils', (require, exports, module) => {
	const { showToast } = require('toast');
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');

	/**
	 * Show success toast after guests invitation or employee adding
	 * @param {object} params
	 * @param {number} params.collabId
	 * @param {boolean} params.multipleInvitation
	 * @param {CollabInviteAnalytics} params.analytics
	 * @param {boolean} params.isTextForInvite
	 */
	const showSuccessInvitationToast = ({ collabId, multipleInvitation, analytics, isTextForInvite = true }) => {
		let message = '';
		if (isTextForInvite)
		{
			message = multipleInvitation
				? Loc.getMessage('COLLAB_INVITE_MULTIPLE_SEND_SUCCESS_TOAST_TEXT')
				: Loc.getMessage('COLLAB_INVITE_SINGLE_SEND_SUCCESS_TOAST_TEXT');
		}
		else
		{
			message = multipleInvitation
				? Loc.getMessage('COLLAB_INVITE_MULTIPLE_ADD_SUCCESS_TOAST_TEXT')
				: Loc.getMessage('COLLAB_INVITE_SINGLE_ADD_SUCCESS_TOAST_TEXT');
		}
		showToast(
			{
				message,
				icon: Icon.CHECK,
				buttonText: Loc.getMessage('COLLAB_INVITE_TOAST_INVITE_BUTTON_TEXT'),
				onButtonTap: async () => {
					const { openCollabInvite } = await requireLazy('collab/invite');
					openCollabInvite({
						collabId,
						analytics,
					});
				},
				time: 5,
			},
		);
	};

	module.exports = {
		showSuccessInvitationToast,
	};
});
