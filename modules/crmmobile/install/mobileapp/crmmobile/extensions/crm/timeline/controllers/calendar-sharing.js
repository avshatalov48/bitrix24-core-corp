/**
 * @module crm/timeline/controllers/calendar-sharing
 */
jn.define('crm/timeline/controllers/calendar-sharing', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');
	const { inAppUrl } = require('in-app-url');
	const DialogOpener = () => {
		try
		{
			const { DialogOpener } = require('im/messenger/api/dialog-opener');

			return DialogOpener;
		}
		catch (e)
		{
			console.log(e, 'DialogOpener not found');

			return null;
		}
	};

	const SupportedActions = {
		OPEN_PUBLIC_PAGE: 'CalendarSharingLinkCopied:OpenPublicPageInNewTab',
		START_VIDEOCONFERENCE: 'Activity:CalendarSharing:StartVideoconference',
	};

	/**
	 * @class TimelineTodoController
	 */
	class TimelineCalendarSharingController extends TimelineBaseController
	{
		static getSupportedActions()
		{
			return Object.values(SupportedActions);
		}

		onItemAction({ action, actionParams = {} })
		{
			switch (action)
			{
				case SupportedActions.OPEN_PUBLIC_PAGE:
					return this.openPublicPageInNewTab(actionParams);
				case SupportedActions.START_VIDEOCONFERENCE:
					return this.startVideoconference(actionParams);
			}
		}

		openPublicPageInNewTab(actionParams)
		{
			if (actionParams.url)
			{
				inAppUrl.open(actionParams.url);
			}
		}

		startVideoconference(actionParams)
		{
			const imOpener = DialogOpener();
			const eventId = actionParams.eventId;
			const ownerId = actionParams.ownerId;
			const ownerTypeId = actionParams.ownerTypeId;
			if (imOpener && eventId)
			{
				const action = 'crm.timeline.calendar.sharing.getConferenceChatId';

				const data = {
					eventId,
					ownerId,
					ownerTypeId,
				};

				BX.ajax.runAction(action, { data })
					.then((response) => {
						const chatId = response.data.chatId;
						if (chatId)
						{
							const dialogParams = {
								dialogId: `chat${chatId}`,
							};

							return imOpener.open(dialogParams);
						}
					})
					.catch((response) => {
						console.error(response);
					});
			}
		}
	}

	module.exports = { TimelineCalendarSharingController };
});
