/**
 * @module crm/entity-actions/check-change-stage
 */
jn.define('crm/entity-actions/check-change-stage', (require, exports, module) => {
	const { Loc } = require('loc');
	const { TypeId } = require('crm/type');
	const { Alert, ButtonType } = require('alert');
	const { EventEmitter } = require('event-emitter');
	const { getActionToConversion } = require('crm/entity-actions/conversion');
	const { AnalyticsEvent } = require('analytics');

	const actionCheckChangeStage = (props) => new Promise((resolve, reject) => {
		const {
			entityTypeId,
			isSelectedStageFinalConverted,
			isActiveStageFinalConverted,
		} = props;

		const isLead = TypeId.Lead === entityTypeId;

		if (!isLead)
		{
			return resolve();
		}

		if (isSelectedStageFinalConverted)
		{
			return showConversion(props);
		}

		if (isActiveStageFinalConverted && !isSelectedStageFinalConverted)
		{
			Alert.confirm(
				Loc.getMessage('M_CRM_ENTITY_ACTION_CONFIRM_CHANGE_STAGE_TITLE'),
				Loc.getMessage('M_CRM_ENTITY_ACTION_CONFIRM_CHANGE_STAGE_DESCRIPTION'),
				[
					{
						type: ButtonType.CANCEL,
						onPress: reject,
					},
					{
						text: Loc.getMessage('M_CRM_ENTITY_ACTION_CONFIRM_CHANGE_STAGE_CONTINUE'),
						onPress: resolve,
					},
				],
			);
		}

		return resolve();
	});

	const getAnalytics = (props) => {
		return new AnalyticsEvent(props.analytics ?? {})
			.setEvent('entity_convert');
	};

	const showConversion = async (props) => {
		const { uid, entityTypeId, entityId } = props;
		const customEventEmitter = EventEmitter.createWithUid(uid);

		const { onAction } = getActionToConversion();

		const conversionAction = await onAction({
			entityId,
			entityTypeId,
			onFinishConverted: () => {
				customEventEmitter.emit('DetailCard::reloadTabs');
				customEventEmitter.emit('DetailCard::onUpdate', [{
					entityId,
					entityTypeId,
				}]);

				return Promise.resolve();
			},
			analytics: getAnalytics(props),
		});

		return conversionAction();
	};

	module.exports = { actionCheckChangeStage };
});
