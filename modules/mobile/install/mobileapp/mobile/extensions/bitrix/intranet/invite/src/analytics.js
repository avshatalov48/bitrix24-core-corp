/**
 * @module intranet/invite/src/analytics
 */
jn.define('intranet/invite/src/analytics', (require, exports, module) => {
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

		sendInvitationSuccessEvent(recipientIds = [])
		{
			if (Array.isArray(recipientIds) && recipientIds.length > 0)
			{
				const multipleInvitation = recipientIds.length > 1;
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

		sendSelectFromContactListEvent(multipleInvitation = false)
		{
			if (this.analytics)
			{
				new AnalyticsEvent(this.analytics)
					.setEvent('select_from_contactlist')
					.setP2(`multiple_${multipleInvitation === true ? 'Y' : 'N'}`)
					.send();
			}
		}

		sendCopyLinkEvent()
		{
			new AnalyticsEvent(this.analytics)
				.setEvent('copy_invitation_link')
				.send();
		}

		sendShareLinkEvent(adminConfirm = false)
		{
			new AnalyticsEvent(this.analytics)
				.setEvent('share_invitation_link')
				.setP2(`askAdminToAllow_${adminConfirm === true ? 'Y' : 'N'}`)
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
