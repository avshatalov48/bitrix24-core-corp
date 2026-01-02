/**
 * @module intranet/invite-new/src/inviter/qr
 */
jn.define('intranet/invite-new/src/inviter/qr', (require, exports, module) => {
	const { BaseInviter } = require('intranet/invite-new/src/inviter/base');
	const { QRInvite, QrEntity } = require('layout/ui/qr-invite');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { InviteCases } = require('intranet/invite-new/src/inviter/type');
	const { Icon } = require('assets/icons');

	/**
	 * @class QRInviter
	 */
	class QRInviter extends BaseInviter
	{
		async open()
		{
			if (!this.inviteLink)
			{
				this.showAdminEmailNotConfirmedBoxByInviteCase(InviteCases.QR);

				return;
			}

			const isDepartment = Boolean(this.props.getDepartment()?.id);
			const params = {
				entityType: isDepartment ? QrEntity.DEPARTMENT : QrEntity.COMPANY,
				entityName: this.props.getDepartment()?.title,
				avatarUri: null,
			};

			if (!isDepartment)
			{
				const resultParams = await this.#getPortalSettings();
				const domainWithoutProtocol = currentDomain.replace(/^https?:\/\//, '');

				Object.assign(params, {
					entityName: resultParams?.name ?? domainWithoutProtocol,
					avatarUri: resultParams?.logo?.src ? `${currentDomain}${resultParams.logo.src}` : null,
				});
			}

			this.getAnalytics().sendOpenQRCodeEvent?.(this.isAdminConfirm());
			await QRInvite.open({
				...params,
				parentWidget: this.props.layout,
				uri: this.inviteLink,
			});
		}

		#getPortalSettings()
		{
			return new Promise((resolve) => {
				new RunActionExecutor(
					'mobile.QrInvite.getPortalSettings',
					{},
				)
					.enableJson()
					.setHandler((response) => resolve(response?.data))
					.setCacheHandler((cache) => resolve(cache?.data))
					.call(true)
				;
			});
		}

		getIconNameForUIMenu()
		{
			return Icon.QR_CODE;
		}

		isDisabledInviteMethod()
		{
			return !this.canInviteByLink();
		}

		getSortOrder()
		{
			return 40;
		}

		setInviteLink(inviteLink)
		{
			this.inviteLink = inviteLink;
		}

		shouldUpdateInviteLink()
		{
			return true;
		}

		isAvailableInviteMethod()
		{
			return true;
		}
	}

	module.exports = { QRInviter };
});
