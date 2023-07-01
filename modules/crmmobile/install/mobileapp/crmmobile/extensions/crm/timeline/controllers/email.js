/**
 * @module crm/timeline/controllers/email
 */
jn.define('crm/timeline/controllers/email', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');

	const SupportedActions = {
		OPEN_MESSAGE: 'Email::OpenMessage',
		SCHEDULE: 'Email::Schedule',
	};

	class TimelineEmailController extends TimelineBaseController
	{
		static getSupportedActions()
		{
			return Object.values(SupportedActions);
		}

		openMessage({ threadId, componentTitle })
		{
			ComponentHelper.openLayout({
				name: 'crm:mail.messageview',
				object: 'layout',
				widgetParams: {
					title: componentTitle,
				},
				componentParams: {
					threadId,
				},
			});
		}

		onItemAction({ action, actionParams = {} })
		{
			switch (action)
			{
				case SupportedActions.OPEN_MESSAGE:
					return this.openMessage(actionParams);
				case SupportedActions.SCHEDULE:
					return this.schedule(actionParams);
			}
		}

		schedule(actionData)
		{
			this.scheduler.openActivityEditor(actionData);
		}
	}

	module.exports = { TimelineEmailController };
});
