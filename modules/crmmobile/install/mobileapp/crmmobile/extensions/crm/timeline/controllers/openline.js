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
		COMPLETE: 'Openline:Complete',
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

		getChannelTitle()
		{
			return get(
				this.item.model,
				'props.layout.body.blocks.chatTitle.properties.contentBlock.properties.value',
				'',
			);
		}

		getItemTitle()
		{
			return get(
				this.item.model,
				'props.layout.header.title',
				'',
			);
		}

		getOpenLineTitle()
		{
			return get(
				this.item.model,
				'props.layout.body.blocks.lineTitle.properties.contentBlock.properties.text',
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
							name: this.getChannelTitle(),
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

					this.openDetailCardTopToolbar('Activity:OpenLine', {
						title: this.getItemTitle(),
						subtitle: this.getOpenLineTitle(),
						actionParams: openLineActionParams,
					});

					setTimeout(() => {
						this.openChat(openLineActionParams);
					}, 400);
					break;

				case SupportedActions.COMPLETE:
					this.onComplete(actionParams);
					break;
			}
		}

		onComplete(actionParams)
		{
			const action = actionParams.ajaxAction;

			this.item.showLoader();

			const data = {
				activityId: actionParams.activityId,
				ownerId: actionParams.ownerId,
				ownerTypeId: actionParams.ownerTypeId,
			};

			BX.ajax.runAction(action, { data })
				.catch((response) => {
					this.item.hideLoader();
					void ErrorNotifier.showError(response.errors[0].message);
				});
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
