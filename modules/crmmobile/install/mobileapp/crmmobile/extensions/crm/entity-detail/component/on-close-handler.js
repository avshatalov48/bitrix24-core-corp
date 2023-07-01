/**
 * @module crm/entity-detail/component/on-close-handler
 */
jn.define('crm/entity-detail/component/on-close-handler', (require, exports, module) => {
	const { CategoryStorage } = require('crm/storage/category');
	const { TimelineScheduler } = require('crm/timeline/scheduler');
	const { Haptics } = require('haptics');
	const { TypeId } = require('crm/type');

	/**
	 * @param {DetailCardComponent} detailCard
	 * @param {boolean} entityWasSaved
	 */
	const onCloseHandler = (detailCard, entityWasSaved = false) => {
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

		const category = CategoryStorage.getCategory(entityTypeId, categoryId || 0);
		if (!category)
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
		const { entityTypeId, entityId, categoryId, todoNotificationParams: { user } } = detailCard.getComponentParams();

		return new Promise((resolve) => {
			const timelineScheduler = new TimelineScheduler({
				user,
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
