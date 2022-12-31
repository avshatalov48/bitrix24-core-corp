/**
 * @module crm/entity-detail/component/on-close-handler
 */
jn.define('crm/entity-detail/component/on-close-handler', (require, exports, module) => {

	const { CategoryStorage } = require('crm/storage/category');
	const { TimelineScheduler } = require('crm/timeline/scheduler');
	const { Haptics } = require('haptics');

	/**
	 * @param {DetailCardComponent} detailCard
	 */
	const onCloseHandler = (detailCard) => {
		if (
			detailCard.isNewEntity()
			|| (detailCard.hasEntityModel() && detailCard.isReadonly())
		)
		{
			return Promise.resolve();
		}

		const { todoNotificationParams } = detailCard.getComponentParams();
		if (!todoNotificationParams)
		{
			return Promise.resolve();
		}

		const { isSkipped, plannedActivityCounter, isFinalStage } = todoNotificationParams;
		if (isSkipped || plannedActivityCounter > 0)
		{
			return Promise.resolve();
		}

		if (detailCard.hasEntityModel() && checkIfFinalStage(detailCard) || isFinalStage)
		{
			return Promise.resolve();
		}

		return showTodoNotification(detailCard);
	};

	/**
	 * @param {DetailCardComponent} detailCard
	 */
	const checkIfFinalStage = (detailCard) => {
		const { entityTypeId, categoryId } = detailCard.getComponentParams();

		const category = CategoryStorage.getCategory(entityTypeId, categoryId);
		if (!category)
		{
			return true;
		}

		const stageId = detailCard.getFieldFromModel('STAGE_ID');
		if (!stageId)
		{
			return true;
		}

		return (
			[
				...(category.successStages || []),
				...(category.failureStages || []),
			]
				.some(({ id }) => id === stageId)
		);
	};

	/**
	 * @param {DetailCardComponent} detailCard
	 */
	const showTodoNotification = (detailCard) => {
		const { entityTypeId, entityId, categoryId } = detailCard.getComponentParams();

		return new Promise((resolve) => {
			const timelineScheduler = new TimelineScheduler({
				entity: {
					id: entityId,
					typeId: entityTypeId,
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
