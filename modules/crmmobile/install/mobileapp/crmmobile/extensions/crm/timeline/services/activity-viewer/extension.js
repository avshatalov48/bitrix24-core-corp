/**
 * @module crm/timeline/services/activity-viewer
 */
jn.define('crm/timeline/services/activity-viewer', (require, exports, module) => {
	const { Alert } = require('alert');
	const { Loc } = require('loc');

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

			if (typeId === ActivityType.TASK)
			{
				void this.openTask(data);
			}
			else
			{
				this.openDesktop(data);
			}
		}

		/**
		 * @private
		 * @param {TimelineActivityResponse} data
		 */
		async openTask(data)
		{
			const taskId = data.associatedEntityId;
			const { Entry } = await requireLazy('tasks:entry');

			if (taskId <= 0 || !Entry)
			{
				this.openDesktop(data);

				return;
			}

			Entry.openTask({ taskId });
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
