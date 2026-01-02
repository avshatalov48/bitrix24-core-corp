/**
 * @module intranet/invite-new/src/inviter/link
 */
jn.define('intranet/invite-new/src/inviter/link', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BaseInviter } = require('intranet/invite-new/src/inviter/base');
	const { InviteCases } = require('intranet/invite-new/src/inviter/type');
	const { Icon } = require('assets/icons');

	/**
	 * @param {Object} props
	 * @param {Function} props.onInviteLinkChanged
	 * @class LinkInviter
	 */
	class LinkInviter extends BaseInviter
	{
		constructor(props)
		{
			super(props);

			this.inviteLinkLoadingPromise = null;
			this.isOpening = false;
		}

		updateDepartment = async (department) => {
			this.getAnalytics().setDepartmentParam(department !== null);
			this.inviteLink = null;
			this.inviteLinkLoadingPromise = this.#getInviteLink();
			const response = await this.inviteLinkLoadingPromise;
			this.handleGetLinkResponse(response);
			this.inviteLinkLoadingPromise = null;
		};

		handleGetLinkResponse(response)
		{
			if (response?.status === 'success')
			{
				this.inviteLink = response.data;
				this.props.onInviteLinkChanged(this.inviteLink);
			}
			this.inviteLinkLoadingPromise = null;
		}

		#getInviteLink = () => {
			return BX.ajax.runAction('intranet.invite.getLinkByDepartments', {
				data: {
					departmentIds: this.getDepartmentIds(),
				},
			})
				.catch((response) => {
					console.error(response);

					return response;
				});
		};

		#getSharingMessageWithLink()
		{
			return Loc.getMessage('INTRANET_INVITE_SHARING_MESSAGE_MSGVER_1', {
				'#link#': this.inviteLink,
			});
		}

		async open()
		{
			if (!this.inviteLink)
			{
				this.showAdminEmailNotConfirmedBoxByInviteCase(InviteCases.LINK);

				return;
			}

			if (this.isOpening)
			{
				return;
			}

			this.isOpening = true;
			if (this.inviteLinkLoadingPromise)
			{
				await this.inviteLinkLoadingPromise;
			}
			this.isOpening = false;

			if (this.inviteLink)
			{
				this.getAnalytics().sendShareLinkEvent(this.props.adminConfirm);
				dialogs.showSharingDialog({
					title: Loc.getMessage('INTRANET_SHARING_LINK_DIALOG_TITLE'),
					message: this.#getSharingMessageWithLink(),
				});
			}
		}

		getIconNameForUIMenu()
		{
			return Icon.LINK;
		}

		isAvailableInviteMethod()
		{
			return true;
		}

		isDisabledInviteMethod()
		{
			return !this.canInviteByLink();
		}

		getSortOrder()
		{
			return 10;
		}

		getImageFileName()
		{
			return 'invite-by-link.svg';
		}
	}

	module.exports = { LinkInviter };
});
