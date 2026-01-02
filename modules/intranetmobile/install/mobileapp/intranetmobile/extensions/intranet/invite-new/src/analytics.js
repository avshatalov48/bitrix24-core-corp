/**
 * @module intranet/invite-new/src/analytics
 */
jn.define('intranet/invite-new/src/analytics', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');

	/**
	 * @class IntranetInviteAnalytics
	 */
	class IntranetInviteAnalytics
	{
		constructor(props)
		{
			this.analytics = this.getCommonAnalyticsData(props?.analytics);
		}

		/**
		 * @param isDepartmentSelected
		 */
		setDepartmentParam(isDepartmentSelected)
		{
			this.analytics?.setP3(`depart_${isDepartmentSelected === true ? 'Y' : 'N'}`);
		}

		sendChooseEmailsEvent()
		{
			if (this.analytics)
			{
				new AnalyticsEvent(this.analytics)
					.setEvent('сhoose_emails')
					.send();
			}
		}

		sendEmailsContinueEvent()
		{
			if (this.analytics)
			{
				new AnalyticsEvent(this.analytics)
					.setEvent('emails_continue')
					.send();
			}
		}

		sendChooseContactsEvent()
		{
			if (this.analytics)
			{
				new AnalyticsEvent(this.analytics)
					.setEvent('choose_contacts')
					.send();
			}
		}

		/**
		 * @param {boolean} multipleInvitation
		 */
		sendContactListContinueEvent(multipleInvitation)
		{
			if (this.analytics)
			{
				new AnalyticsEvent(this.analytics)
					.setEvent('contactlist_continue')
					.setP2(`multiple_${multipleInvitation === true ? 'Y' : 'N'}`)
					.send();
			}
		}

		sendTypeNumberWithoutContactListEvent()
		{
			if (this.analytics)
			{
				new AnalyticsEvent(this.analytics)
					.setEvent('type_number_without_contactlist')
					.send();
			}
		}

		/**
		 * @param {boolean} multipleInvitation
		 * @param {string[]} recipientIds
		 * @param {InviteCases} inviteCase
		 */
		sendInvitationSuccessEvent(multipleInvitation, inviteCase, recipientIds = [])
		{
			if (Array.isArray(recipientIds) && recipientIds.length > 0)
			{
				recipientIds.forEach((id) => {
					new AnalyticsEvent(this.analytics)
						.setEvent('invitation')
						.setSubSection(`by_${inviteCase}`)
						.setStatus('success')
						.setP2(`multiple_${multipleInvitation === true ? 'Y' : 'N'}`)
						.setP5(`userId_${id}`)
						.send();
				});
			}
		}

		/**
		 * @param {boolean} multipleInvitation
		 * @param {string[]} recipientIds
		 * @param {InviteCases} inviteCase
		 */
		sendInvitationFailedEvent(multipleInvitation, inviteCase, recipientIds = [])
		{
			if (Array.isArray(recipientIds) && recipientIds.length > 0)
			{
				recipientIds.forEach((id) => {
					new AnalyticsEvent(this.analytics)
						.setEvent('invitation')
						.setSubSection(`by_${inviteCase}`)
						.setStatus('failed')
						.setP2(`multiple_${multipleInvitation === true ? 'Y' : 'N'}`)
						.setP5(`userId_${id}`)
						.send();
				});
			}
			else
			{
				new AnalyticsEvent(this.analytics)
					.setEvent('invitation')
					.setSubSection(`by_${inviteCase}`)
					.setStatus('failed')
					.setP2(`multiple_${multipleInvitation === true ? 'Y' : 'N'}`)
					.send();
			}
		}

		sendSelectFromContactListEvent()
		{
			if (this.analytics)
			{
				new AnalyticsEvent(this.analytics)
					.setEvent('select_from_contactlist')
					.send();
			}
		}

		/**
		 * @param {boolean} adminConfirm
		 */
		sendShareLinkEvent(adminConfirm)
		{
			new AnalyticsEvent(this.analytics)
				.setEvent('share_invitation_link')
				.setSubSection('by_link')
				.setP2(`askAdminToAllow_${adminConfirm === true ? 'Y' : 'N'}`)
				.send();
		}

		/**
		 * @param {boolean} adminConfirm
		 */
		sendOpenQRCodeEvent(adminConfirm)
		{
			new AnalyticsEvent(this.analytics)
				.setEvent('open_qr_code')
				.setSubSection('by_qr')
				.setP2(`askAdminToAllow_${adminConfirm === true ? 'Y' : 'N'}`)
				.send();
		}

		sendAllowContactsEvent()
		{
			new AnalyticsEvent(this.analytics)
				.setEvent('allow_contactlist')
				.send();
		}

		/**
		 * @param {InviteCases} inviteCase
		 */
		sendProhibitInviteEvent(inviteCase = null)
		{
			new AnalyticsEvent(this.analytics)
				.setEvent('prohibit_invite')
				.setSubSection(inviteCase === null ? 'not_admin_box' : `by_${inviteCase}`)
				.send();
		}

		sendDrawerOpenEvent()
		{
			new AnalyticsEvent(this.analytics)
				.setEvent('drawer_open')
				.send();
		}

		/**
		 * @param {boolean} adminConfirm
		 */
		sendInvitationLocalDrawerOpenEvent(adminConfirm)
		{
			new AnalyticsEvent(this.analytics)
				.setEvent('invitation_local_drawer_open')
				.setP2(`askAdminToAllow_${adminConfirm === true ? 'Y' : 'N'}`)
				.send();
		}

		/**
		 * @param {boolean} adminConfirm
		 */
		sendInvitationLocalMailEvent(adminConfirm)
		{
			new AnalyticsEvent(this.analytics)
				.setEvent('invitation_local_mail')
				.setP2(`askAdminToAllow_${adminConfirm === true ? 'Y' : 'N'}`)
				.send();
		}

		sendInvitationLocalMailSkipEvent()
		{
			new AnalyticsEvent(this.analytics)
				.setEvent('invitation_local_mail_skip')
				.send();
		}

		/**
		 * @param {Object} analytics
		 */
		getCommonAnalyticsData(analytics = {})
		{
			const isAdminParam = env.isAdmin === true ? 'isAdmin_Y' : 'isAdmin_N';

			return new AnalyticsEvent(analytics)
				.setTool('invitation')
				.setCategory('invitation')
				.setP1(isAdminParam);
		}
	}

	module.exports = { IntranetInviteAnalytics };
});
