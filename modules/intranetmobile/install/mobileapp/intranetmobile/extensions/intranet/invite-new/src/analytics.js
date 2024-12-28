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
			this.analytics = this.getCommonAnalyticsData(props.analytics);
		}

		setDepartmentParam(isDepartmentSelected)
		{
			if (this.analytics)
			{
				this.analytics.setP3(`depart_${isDepartmentSelected === true ? 'Y' : 'N'}`);
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

		sendInvitationSuccessEvent(multipleInvitation, recipientIds = [])
		{
			if (Array.isArray(recipientIds) && recipientIds.length > 0)
			{
				recipientIds.forEach((id) => {
					new AnalyticsEvent(this.analytics)
						.setEvent('invitation')
						.setType('phone')
						.setStatus('success')
						.setP2(`multiple_${multipleInvitation === true ? 'Y' : 'N'}`)
						.setP5(`userId_${id}`)
						.send();
				});
			}
		}

		sendInvitationFailedEvent(multipleInvitation, recipientIds = [])
		{
			if (Array.isArray(recipientIds) && recipientIds.length > 0)
			{
				recipientIds.forEach((id) => {
					new AnalyticsEvent(this.analytics)
						.setEvent('invitation')
						.setType('phone')
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
					.setType('phone')
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

		sendShareLinkEvent(adminConfirm)
		{
			new AnalyticsEvent(this.analytics)
				.setEvent('share_invitation_link')
				.setP2(`askAdminToAllow_${adminConfirm === true ? 'Y' : 'N'}`)
				.setP3(null)
				.send();
		}

		sendAllowContactsEvent()
		{
			new AnalyticsEvent(this.analytics)
				.setEvent('allow_contactlist')
				.send();
		}

		sendDrawerOpenEvent()
		{
			new AnalyticsEvent(this.analytics)
				.setEvent('drawer_open')
				.send();
		}

		getCommonAnalyticsData(analytics = {})
		{
			const isAdminParam = env.isAdmin === true ? 'isAdmin_Y' : 'isAdmin_N';

			return new AnalyticsEvent(analytics)
				.setTool('invitation')
				.setCategory('invitation')
				.setP1(isAdminParam);
		}
	}

	module.exports = {
		IntranetInviteAnalytics,
	};
});
