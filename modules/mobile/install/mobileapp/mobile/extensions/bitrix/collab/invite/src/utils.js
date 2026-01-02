/**
 * @module collab/invite/src/utils
 */
jn.define('collab/invite/src/utils', (require, exports, module) => {
	const { showToast } = require('toast');
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { makeLibraryImagePath } = require('asset-manager');
	const { Link4, LinkMode, Ellipsize } = require('ui-system/blocks/link');
	const { Color } = require('tokens');
	const { ButtonDesign } = require('ui-system/form/buttons/button');
	const { qrauth } = require('qrauth/utils');
	const { StatusBox } = require('layout/ui/status-box');

	/**
	 * Show success toast after guests invitation or employee adding
	 * @param {object} params
	 * @param {number} params.collabId
	 * @param {boolean} params.multipleInvitation
	 * @param {CollabInviteAnalytics} params.analytics
	 * @param {boolean} params.isTextForInvite
	 */
	const showSuccessInvitationToast = ({
		collabId,
		multipleInvitation,
		analytics,
		isTextForInvite = true,
	}) => {
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

	const openGuestsInviteRestrictionsBox = async (props) => {
		const { parentWidget, onClose } = props;

		const imageUri = makeLibraryImagePath('invite-guests-disabled.svg', 'collab/invite');
		const isAdmin = env.isAdmin;

		const inviteBoxInstance = await StatusBox.open({
			parentWidget,
			backdropTitle: Loc.getMessage('COLLAB_GUEST_INVITE_RESTRICTIONS_BOX_TITLE'),
			testId: 'guests-invite-restrictions-box',
			imageUri,
			height: 440,
			title: Loc.getMessage('COLLAB_GUEST_INVITE_RESTRICTIONS_BOX_STATUS_BLOCK_TITLE'),
			description: isAdmin
				? Loc.getMessage('COLLAB_GUEST_INVITE_RESTRICTIONS_BOX_STATUS_BLOCK_DESCRIPTION_FOR_ADMIN')
				: Loc.getMessage('COLLAB_GUEST_INVITE_RESTRICTIONS_BOX_STATUS_BLOCK_DESCRIPTION'),
			buttonText: Loc.getMessage('COLLAB_GUEST_INVITE_RESTRICTIONS_BOX_BUTTON_TEXT'),
			buttonDesign: ButtonDesign.OUTLINE,
			statusBlockStyle: {
				justifyContent: 'flex-start',
			},
			statusBlockButtons: isAdmin ? [
				Link4({
					testId: 'guests-invite-restrictions-box-details-button',
					text: Loc.getMessage('COLLAB_GUEST_INVITE_RESTRICTIONS_BOX_DETAILS_BUTTON_TEXT'),
					ellipsize: Ellipsize.END,
					mode: LinkMode.SOLID,
					color: Color.base3,
					numberOfLines: 1,
					onClick: () => {
						qrauth.open({
							layout: inviteBoxInstance.parentWidget,
							redirectUrl: `${currentDomain}/settings/configs/?page=employee`,
							showHint: true,
							analyticsSection: 'collab_invite_box',
						});
					},
				}),
			] : [],
			onClose,
		});

		return inviteBoxInstance;
	};

	const openCurrentCollabGuestsInviteRestrictionsBox = (props) => {
		const { parentWidget, onClose } = props;

		const imageUri = makeLibraryImagePath('invite-guests-disabled.svg', 'collab/invite');

		return StatusBox.open({
			parentWidget,
			backdropTitle: Loc.getMessage('COLLAB_GUESTS_INVITE_DISABLED_BY_COLLAB_ADMIN_BOX_TITLE'),
			testId: 'current-collab-guests-invite-restrictions-box',
			imageUri,
			height: 440,
			title: Loc.getMessage('COLLAB_GUESTS_INVITE_DISABLED_BY_COLLAB_ADMIN_TITLE'),
			description: Loc.getMessage('COLLAB_GUESTS_INVITE_DISABLED_BY_COLLAB_ADMIN_DESCRIPTION'),
			buttonText: Loc.getMessage('COLLAB_GUESTS_INVITE_DISABLED_BY_COLLAB_ADMIN_BUTTON_TEXT'),
			buttonDesign: ButtonDesign.OUTLINE,
			statusBlockStyle: {
				justifyContent: 'flex-start',
			},
			onClose,
		});
	};

	module.exports = {
		showSuccessInvitationToast,
		openGuestsInviteRestrictionsBox,
		openCurrentCollabGuestsInviteRestrictionsBox,
	};
});
