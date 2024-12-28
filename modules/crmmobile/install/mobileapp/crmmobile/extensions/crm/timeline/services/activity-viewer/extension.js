/**
 * @module crm/timeline/services/activity-viewer
 */
jn.define('crm/timeline/services/activity-viewer', (require, exports, module) => {
	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const { get } = require('utils/object');

	const ActivityType = {
		UNDEFINED: 0,
		MEETING: 1,
		CALL: 2,
		TASK: 3,
		EMAIL: 4,
		ACTIVITY: 5,
		PROVIDER: 6,
	};

	/**
	 * @class ActivityViewer
	 */
	class ActivityViewer
	{
		/**
		 * @param {number} activityId
		 * @param {TimelineEntityProps} entity
		 */
		constructor({ activityId, entity })
		{
			this.activityId = activityId;
			this.entity = entity;
		}

		/**
		 * @public
		 */
		open()
		{
			this.loadActivity()
				.then((data) => this.handleActivity(data))
				.catch((response) => this.handleError(response));
		}

		/**
		 * @private
		 * @return {Promise}
		 */
		loadActivity()
		{
			return new Promise((resolve, reject) => {
				const data = {
					activityId: this.activityId,
					entityId: this.entity.id,
					entityTypeId: this.entity.typeId,
					categoryId: this.entity.categoryId,
				};

				BX.ajax.runAction('crmmobile.Timeline.loadActivity', { json: data })
					.then((response) => resolve(response.data))
					.catch((response) => reject(response));
			});
		}

		/**
		 * @private
		 * @param {TimelineActivityResponse} data
		 */
		handleActivity(data)
		{
			const { typeId } = data;

			switch (typeId)
			{
				case ActivityType.TASK:
					this.openTask(data);
					break;

				default:
					this.openDesktop(data);
					break;
			}
		}

		/**
		 * @private
		 * @param {TimelineActivityResponse} data
		 */
		openTask(data)
		{
			const taskId = data.associatedEntityId;
			const subject = get(data, 'activity.SUBJECT', '');

			if (typeof Task === 'undefined' || taskId <= 0)
			{
				this.openDesktop(data);

				return;
			}

			// @todo Probably we should better send some event, and listen it in tasks background
			const task = new Task({ id: env.userId });
			task.updateData({
				id: taskId,
				title: subject,
			});
			task.canSendMyselfOnOpen = false;
			task.open();
		}

		/**
		 * @private
		 * @param {TimelineActivityResponse} data
		 */
		openDesktop(data)
		{
			qrauth.open({
				title: Loc.getMessage('CRM_TIMELINE_DESKTOP_VERSION'),
				redirectUrl: this.entity.detailPageUrl,
				analyticsSection: 'crm',
			});
		}

		/**
		 * @private
		 * @param {object} ajaxResponse
		 */
		handleError(ajaxResponse)
		{
			console.warn('Timeline: activity viewer: cannot load activity', ajaxResponse);

			Alert.alert(
				Loc.getMessage('M_CRM_TIMELINE_COMMON_ERROR_TITLE'),
				Loc.getMessage('M_CRM_TIMELINE_COMMON_ERROR_DESCRIPTION'),
				() => {},
				Loc.getMessage('M_CRM_TIMELINE_COMMON_ERROR_OK_BUTTON'),
			);
		}
	}

	module.exports = { ActivityViewer };
});
