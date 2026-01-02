/**
 * @module crm/timeline/controllers/activity
 */
jn.define('crm/timeline/controllers/activity', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');
	const { ActivityViewer } = require('crm/timeline/services/activity-viewer');
	const { confirmDestructiveAction } = require('alert');

	const SupportedActions = {
		DELETE: 'Activity:Delete',
		VIEW: 'Activity:View',
	};

	/**
	 * @class TimelineActivityController
	 */
	class TimelineActivityController extends TimelineBaseController
	{
		static getSupportedActions()
		{
			return Object.values(SupportedActions);
		}

		/**
		 * @public
		 * @param {string} action
		 * @param {object} actionParams
		 * @param {AnalyticsEvent|null} analyticsEvent
		 */
		// eslint-disable-next-line consistent-return
		onItemAction({ action, actionParams = {}, analyticsEvent = null })
		{
			switch (action)
			{
				case SupportedActions.DELETE:
					return this.deleteActivity(actionParams, analyticsEvent);

				case SupportedActions.VIEW:
					return this.viewActivity(actionParams);

				default:
			}
		}

		/**
		 * @private
		 * @param {string|number} activityId
		 * @param {number} ownerId
		 * @param {number} ownerTypeId
		 * @param {string|null} confirmationText
		 * @param {AnalyticsEvent|null} analyticsEvent
		 */
		deleteActivity({
			activityId,
			ownerId,
			ownerTypeId,
			confirmationText,
		}, analyticsEvent = null)
		{
			if (!activityId)
			{
				return;
			}

			const data = { activityId, ownerTypeId, ownerId };

			if (confirmationText)
			{
				confirmDestructiveAction({
					title: '',
					description: confirmationText,
					onDestruct: () => this.executeDeleteAction(data, analyticsEvent),
				});
			}
			else
			{
				this.executeDeleteAction(data, analyticsEvent);
			}
		}

		viewActivity({ activityId })
		{
			if (!activityId)
			{
				return;
			}

			const activityViewer = new ActivityViewer({
				activityId,
				entity: this.entity,
			});
			activityViewer.open();
		}

		/**
		 * @private
		 * @param {{
		 *   activityId: string|number,
		 *   ownerTypeId: number,
		 *   ownerId: number,
		 * }} data
		 * @param {AnalyticsEvent|null} analyticsEvent
		 */
		executeDeleteAction(data = {}, analyticsEvent = null)
		{
			const action = 'crm.timeline.activity.delete';

			this.item.showLoader();

			BX.ajax.runAction(action, {
				data,
				analyticsLabel: analyticsEvent
					?.setEvent('activity_delete')
					.setElement('delete_button')
					.exportToObject(),
			})
				.catch((response) => {
					this.item.hideLoader();
					void ErrorNotifier.showError(response.errors[0].message);
				});
		}
	}

	module.exports = { TimelineActivityController };
});
