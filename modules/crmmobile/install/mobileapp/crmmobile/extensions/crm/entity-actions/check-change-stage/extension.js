/**
 * @module crm/entity-actions/check-change-stage
 */
jn.define('crm/entity-actions/check-change-stage', (require, exports, module) => {
	const { Loc } = require('loc');
	const { TypeId } = require('crm/type');
	const { Alert, ButtonType } = require('alert');
	const { EventEmitter } = require('event-emitter');
	const { getActionToConversion } = require('crm/entity-actions/conversion');

	const actionCheckChangeStage = (props) => new Promise((resolve, reject) => {
		const {
			uid,
			category,
			entityId,
			entityTypeId,
			activeStageId,
			selectedStageId,
		} = props;

		const customEventEmitter = EventEmitter.createWithUid(uid);
		const isLead = TypeId.Lead === entityTypeId;

		if (!isLead)
		{
			return resolve();
		}

		const isFinalConvertedStage = (stageId) => category && category.successStages.find(({ id, statusId }) => id === stageId && statusId === 'CONVERTED');

		const { onAction } = getActionToConversion();
		if (isFinalConvertedStage(selectedStageId))
		{
			onAction({
				entityId,
				entityTypeId,
				onFinishConverted: () => {
					customEventEmitter.emit('DetailCard::reloadTabs');
					reject();

					return Promise.resolve();
				},
			}).then((menu) => {
				menu.show();
			});

			return;
		}

		if (isFinalConvertedStage(activeStageId) && !isFinalConvertedStage(selectedStageId))
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

			return;
		}

		return resolve();
	});

	module.exports = { actionCheckChangeStage };
});
