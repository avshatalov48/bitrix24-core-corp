/**
 * @module crm/timeline/controllers/call
 */
jn.define('crm/timeline/controllers/call', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');
	const { Type: CrmType } = require('crm/type');
	const { Type } = require('type');
	const { Filesystem } = require('native/filesystem');
	const { withCurrentDomain } = require('utils/url');

	const SupportedActions = {
		MAKE_CALL: 'Call:MakeCall',
		SCHEDULE_CALL: 'Call:Schedule',
		DOWNLOAD_RECORD: 'Call:DownloadRecord',
		TOGGLE_PLAYER: 'Call:ChangePlayerState',
	};

	class TimelineCallController extends TimelineBaseController
	{
		static getSupportedActions()
		{
			return Object.values(SupportedActions);
		}

		onItemAction({ action, actionParams = {} })
		{
			switch (action)
			{
				case SupportedActions.MAKE_CALL:
					return this.makeCall(actionParams);
				case SupportedActions.SCHEDULE_CALL:
					return this.scheduleCall(actionParams);
				case SupportedActions.DOWNLOAD_RECORD:
					return this.downloadRecord(actionParams);
				case SupportedActions.TOGGLE_PLAYER:
					return this.togglePlayer(actionParams);
				default:
			}
		}

		makeCall(actionData)
		{
			if (!Type.isStringFilled(actionData.phone))
			{
				return;
			}

			const params = {
				SHOW_NAME: actionData.showName,
				NAME: actionData.formattedName,
				ENTITY_TYPE_NAME: CrmType.resolveNameById(actionData.entityTypeId),
				ENTITY_ID: actionData.entityId,
				AUTO_FOLD: true,
			};

			if (actionData.ownerTypeId !== actionData.entityTypeId || actionData.ownerId !== actionData.entityId)
			{
				params.BINDINGS = {
					OWNER_TYPE_NAME: CrmType.resolveNameById(actionData.ownerTypeId),
					OWNER_ID: actionData.ownerId,
				};
			}

			if (actionData.activityId > 0)
			{
				params.SRC_ACTIVITY_ID = actionData.activityId;
			}

			BX.postComponentEvent('onPhoneTo', [{
				number: actionData.phone,
				params,
			}], 'calls');
		}

		scheduleCall(actionData)
		{
			this.scheduler.openActivityEditor(actionData);
		}

		downloadRecord(actionData)
		{
			const { url } = actionData;
			if (!url)
			{
				return;
			}

			Notify.showIndicatorLoading();

			Filesystem.downloadFile(withCurrentDomain(url)).then((uri) => {
				Notify.hideCurrentIndicator();
				dialogs.showSharingDialog({ uri });
			});
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

	module.exports = { TimelineCallController };
});
