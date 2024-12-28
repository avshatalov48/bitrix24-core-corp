/**
 * @module collab/invite/src/analytics
 */
jn.define('collab/invite/src/analytics', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Type } = require('type');

	const UserType = {
		COLLABER: env.isCollaber ? 'user_collaber' : false,
		EXTRANET: env.extranet ? 'user_extranet' : false,
		INTRANET: 'user_intranet',
		get()
		{
			return String(this.COLLABER || this.EXTRANET || this.INTRANET);
		},
	};

	/**
     * @class CollabInviteAnalytics
     */
	class CollabInviteAnalytics extends AnalyticsEvent
	{
		static get Event()
		{
			return {
				INVITE: 'invitation',
			};
		}

		static get Tool()
		{
			return {
				IM: 'im',
				INVITATION: 'invitation',
			};
		}

		static get Category()
		{
			return {
				INVITATION: 'invitation',
				COLLAB: 'collab',
			};
		}

		static get Section()
		{
			return {
				CHAT_SIDEBAR: 'chat_sidebar',
				CHAT_HEADER: 'chat_header',
				COLLAB_CREATE: 'collab_create',
			};
		}

		getDefaults()
		{
			return {
				tool: CollabInviteAnalytics.Tool.INVITATION,
				category: CollabInviteAnalytics.Category.INVITATION,
				event: null,
				type: null,
				c_section: null,
				c_sub_section: null,
				c_element: null,
				status: null,
				p1: null,
				p2: UserType.get(),
				p3: null,
				p4: null,
				p5: null,
			};
		}

		sendInviteEvent = (inviteByPhone, invitedUsersIds = []) => {
			if (Type.isArrayFilled(invitedUsersIds))
			{
				invitedUsersIds.forEach((userId) => {
					new CollabInviteAnalytics(this)
						.setEvent(CollabInviteAnalytics.Event.INVITE)
						.setUserId(userId)
						.setType(inviteByPhone ? 'phone' : 'email')
						.send();
				});
			}
		};

		setUserId = (userId) => {
			return this.setP5(`userId_${userId}`);
		};

		setCollabId = (collabId) => {
			return this.setP4(`collabId_${collabId}`);
		};

		setChatId = (chatId) => {
			return this.setP5(`chatId_${chatId}`);
		};
	}

	module.exports = {
		CollabInviteAnalytics,
	};
});
