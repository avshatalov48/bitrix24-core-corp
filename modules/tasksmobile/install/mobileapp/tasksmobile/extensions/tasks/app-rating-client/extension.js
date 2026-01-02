/**
 * @module tasks/app-rating-client
 */
jn.define('tasks/app-rating-client', (require, exports, module) => {
	const { AppRatingClientBase } = require('app-rating-client');
	const AppRatingUserEvent = {
		TASK_VIEWED: 'task_viewed',
		TASK_COMPLETED: 'task_completed',
		TASK_CREATED: 'task_created',
		FLOW_TASK_CREATED: 'flow_task_created',
	};

	/**
	 * @class AppRatingClient
	 */
	class AppRatingClient extends AppRatingClientBase
	{
		/**
		 * @public
		 * @returns {object}
		 */
		getLimits()
		{
			return {
				[AppRatingUserEvent.TASK_VIEWED]: 25,
				[AppRatingUserEvent.TASK_COMPLETED]: 5,
				[AppRatingUserEvent.TASK_CREATED]: 5,
				[AppRatingUserEvent.FLOW_TASK_CREATED]: 3,
			};
		}

		/**
		 * @public
		 * @returns {void}
		 */
		increaseTaskViewedCounter()
		{
			void this.increaseCounter(AppRatingUserEvent.TASK_VIEWED);
		}

		/**
		 * @public
		 * @returns {Promise<void>}
		 */
		async increaseTaskCompletedCounter()
		{
			await this.increaseCounter(AppRatingUserEvent.TASK_COMPLETED);
		}

		/**
		 * @public
		 * @returns {Promise<void>}
		 */
		async increaseTaskCreatedCounter()
		{
			await this.increaseCounter(AppRatingUserEvent.TASK_CREATED);
		}

		/**
		 * @public
		 * @returns {Promise<void>}
		 */
		async increaseFlowTaskCreatedCounter()
		{
			await this.increaseCounter(AppRatingUserEvent.FLOW_TASK_CREATED);
		}

		/**
		 * @param {AppRatingProps} [props]
		 * @param {boolean} [props.openInComponent = false]
		 * @returns {void}
		 */
		tryOpenAppRatingAfterTaskCompleted(props)
		{
			this.tryOpenAppRating({
				event: AppRatingUserEvent.TASK_COMPLETED,
				...props,
			});
		}

		/**
		 * @param {AppRatingProps} [props]
		 * @param {boolean} [props.openInComponent = false]
		 * @returns {void}
		 */
		tryOpenAppRatingAfterTaskCreated(props)
		{
			this.tryOpenAppRating({
				event: AppRatingUserEvent.TASK_CREATED,
				...props,
			});
		}

		/**
		 * @param {AppRatingProps} [props]
		 * @param {boolean} [props.openInComponent = false]
		 * @returns {void}
		 */
		tryOpenAppRatingAfterFlowTaskCreated(props)
		{
			this.tryOpenAppRating({
				event: AppRatingUserEvent.FLOW_TASK_CREATED,
				...props,
			});
		}

		/**
		 * @param {AppRatingProps} [props]
		 * @param {boolean} [props.openInComponent = false]
		 * @returns {void}
		 */
		tryOpenAppRatingAfterTaskViewed(props)
		{
			this.tryOpenAppRating({
				event: AppRatingUserEvent.TASK_VIEWED,
				...props,
			});
		}
	}

	module.exports = {
		AppRatingClient: new AppRatingClient(),
		AppRatingUserEvent,
	};
});
