/**
 * @module crm/entity-detail/component/on-close-handler
 */
jn.define('crm/entity-detail/component/on-close-handler', (require, exports, module) => {
	const { TimelineScheduler } = require('crm/timeline/scheduler');
	const { Haptics } = require('haptics');
	const { TypeId } = require('crm/type');
	const store = require('statemanager/redux/store');
	const {
		getCrmKanbanUniqId,
		selectStagesIdsBySemantics,
	} = require('crm/statemanager/redux/slices/kanban-settings');

	/**
	 * @param {DetailCardComponent} detailCard
	 * @param {boolean} entityWasSaved
	 */
	const onCloseHandler = (detailCard, entityWasSaved = false) => {
		device.setProximitySensorEnabled(false);

		let promise = Promise.resolve();

		if (
			detailCard.isNewEntity()
			|| (detailCard.hasEntityModel() && detailCard.isReadonly())
		)
		{
			return promise;
		}

		const { todoNotificationParams } = detailCard.getComponentParams();
		if (!todoNotificationParams || !todoNotificationParams.notificationSupported)
		{
			return promise;
		}

		const { notificationEnabled, plannedActivityCounter, isFinalStage } = todoNotificationParams;
		if (!notificationEnabled || plannedActivityCounter > 0)
		{
			return promise;
		}

		if (detailCard.hasEntityModel() && checkIfFinalStage(detailCard) || isFinalStage)
		{
			return promise;
		}

		if (entityWasSaved)
		{
			const fakeSaveIndicatorTimeout = () => new Promise((resolve) => setTimeout(resolve, 500));
			promise = promise.then(fakeSaveIndicatorTimeout);
		}

		return promise.then(() => showTodoNotification(detailCard));
	};

	/**
	 * @param {DetailCardComponent} detailCard
	 */
	const checkIfFinalStage = (detailCard) => {
		const { entityTypeId, categoryId } = detailCard.getComponentParams();

		const stages = selectStagesIdsBySemantics(store.getState(), getCrmKanbanUniqId(entityTypeId, categoryId));
		if (!stages)
		{
			return true;
		}

		const fieldName = (entityTypeId === TypeId.Lead ? 'STATUS_ID' : 'STAGE_ID');
		const stageId = detailCard.getFieldFromModel(fieldName);
		if (!stageId)
		{
			return true;
		}

		return (
			[
				...(stages.successStages || []),
				...(stages.failureStages || []),
			]
				.includes(stageId)
		);
	};

	/**
	 * @param {DetailCardComponent} detailCard
	 */
	const showTodoNotification = (detailCard) => {
		const {
			entityTypeId,
			entityId,
			categoryId,
			todoNotificationParams: { user, reminders },
		} = detailCard.getComponentParams();

		return new Promise((resolve) => {
			const timelineScheduler = new TimelineScheduler({
				user,
				entity: {
					id: entityId,
					typeId: entityTypeId,
					reminders,
					categoryId,
				},
				onActivityCreate: resolve,
				onSkip: resolve,
				onCancel: resolve,
			});

			timelineScheduler.openActivityReminder();
			Haptics.notifyWarning();
		});
	};

	module.exports = { onCloseHandler };
});
