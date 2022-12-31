/**
 * @module crm/timeline/controllers/openline
 */
jn.define('crm/timeline/controllers/openline', (require, exports, module) => {

	const { TimelineBaseController } = require('crm/controllers/base');
	const { CommunicationEvents } = require('communication/events');
	const { get } = require('utils/object');
	const { Loc } = require('loc');

	const SupportedActions = {
		OPEN_CHAT: 'Openline:OpenChat',
	};

	/**
	 * @class TimelineOpenlineController
	 */
	class TimelineOpenlineController extends TimelineBaseController
	{
		static getSupportedActions()
		{
			return Object.values(SupportedActions);
		}

		getTitle()
		{
			return get(
				this.item.model,
				['props', 'layout', 'body', 'blocks', 'chatTitle', 'properties', 'contentBlock', 'properties', 'text'],
				'',
			);
		}

		prepareOpenLineActionParams({ dialogId })
		{
			return {
				type: 'im',
				props: {
					event: 'openline',
					params: {
						userCode: dialogId,
						titleParams: {
							name: this.getTitle(),
							description: Loc.getMessage('CRM_TIMELINE_OPEN_LINE_NAME'),
						},
					},
				},
			};
		}

		onItemAction({ action, actionParams = {} })
		{
			switch (action)
			{
				case SupportedActions.OPEN_CHAT:
					const openLineActionParams = this.prepareOpenLineActionParams(actionParams);
					this.pinInTopToolbar(openLineActionParams);

					setTimeout(() => {
						this.openChat(openLineActionParams);
					}, 400);
				default:
					return;
			}
		}

		openChat(params)
		{
			if (!params)
			{
				return null;
			}

			CommunicationEvents.execute(params);
		}
	}

	module.exports = { TimelineOpenlineController };

});