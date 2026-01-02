/**
 * @module intranet/invite-new/src/admin-email-not-confirmed-box
 */
jn.define('intranet/invite-new/src/admin-email-not-confirmed-box', (require, exports, module) => {
	const { Loc } = require('loc');
	const { StatusBox } = require('layout/ui/status-box');
	const { makeLibraryImagePath } = require('asset-manager');
	const { ButtonDesign } = require('ui-system/form/buttons/button');

	const showAdminEmailNotConfirmedBox = (props) => {
		const {
			layout,
			inviteCase,
			analytics,
		} = props;

		analytics?.sendProhibitInviteEvent?.(inviteCase);
		const imageUri = makeLibraryImagePath(`invite-by-${inviteCase}-disabled.svg`, 'invite', 'intranet');

		StatusBox.open({
			backdropTitle: Loc.getMessage(`INVITE_BY_${inviteCase.toUpperCase()}_ADMIN_NOT_CONFIRMED_EMAIL_BOX_TOP_TITLE`),
			testId: 'admin-email-not-confirmed-status-box',
			imageUri,
			parentWidget: layout,
			title: Loc.getMessage(`INVITE_BY_${inviteCase.toUpperCase()}_ADMIN_NOT_CONFIRMED_EMAIL_BOX_TITLE`),
			description: Loc.getMessage(`INVITE_BY_${inviteCase.toUpperCase()}_ADMIN_NOT_CONFIRMED_EMAIL_BOX_DESCRIPTION`),
			buttonText: Loc.getMessage(`INVITE_BY_${inviteCase.toUpperCase()}_ADMIN_NOT_CONFIRMED_EMAIL_BOX_BUTTON_TEXT`),
			buttonDesign: ButtonDesign.OUTLINE,
		});
	};

	module.exports = {
		showAdminEmailNotConfirmedBox,
	};
});
