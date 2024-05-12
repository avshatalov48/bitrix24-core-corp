/**
 * @module crm/timeline/controllers/visit
 */
jn.define('crm/timeline/controllers/visit', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');
	const { withCurrentDomain } = require('utils/url');

	const SupportedActions = {
		SCHEDULE_CALL: 'Activity:Visit:Schedule',
		TOGGLE_PLAYER: 'Activity:Visit:ChangePlayerState',
	};

	class TimelineVisitController extends TimelineBaseController
	{
		static getSupportedActions()
		{
			return Object.values(SupportedActions);
		}

		onItemAction({ action, actionParams = {} })
		{
			switch (action)
			{
				case SupportedActions.SCHEDULE_CALL:
					return this.schedule(actionParams);
				case SupportedActions.TOGGLE_PLAYER:
					return this.togglePlayer(actionParams);
				default:
			}
		}

		schedule(actionData)
		{
			this.scheduler.openActivityEditor(actionData);
		}

		togglePlayer(actionData = {})
		{
			if (!actionData.recordUri)
			{
				return;
			}

			this.itemScopeEventBus.emit('TimelineIconAudioPlayer::onChangePlay', [{
				uri: withCurrentDomain(actionData.recordUri),
			}]);
		}
	}

	module.exports = { TimelineVisitController };
});
