/**
 * @module crm/timeline/controllers/activity
 */
jn.define('crm/timeline/controllers/activity', (require, exports, module) => {
	const { Loc } = require('loc');
	const { TimelineBaseController } = require('crm/controllers/base');
	const { ActivityViewer } = require('crm/timeline/services/activity-viewer');
	const { Alert, ButtonType } = require('alert');

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
		 */
		onItemAction({ action, actionParams = {} })
		{
			switch (action)
			{
				case SupportedActions.DELETE:
					return this.deleteActivity(actionParams);

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
		 */
		deleteActivity({ activityId, ownerId, ownerTypeId, confirmationText })
		{
			if (!activityId)
			{
				return;
			}

			const data = { activityId, ownerTypeId, ownerId };

			if (confirmationText)
			{
				Alert.confirm(
					'',
					confirmationText,
					[
						{
							text: Loc.getMessage('CRM_TIMELINE_CONFIRM_REMOVE'),
							type: ButtonType.DESTRUCTIVE,
							onPress: () => this.executeDeleteAction(data),
						},
						{
							type: ButtonType.CANCEL,
						},
					],
				);
			}
			else
			{
				this.executeDeleteAction(data);
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
		 */
		executeDeleteAction(data = {})
		{
			const action = 'crm.timeline.activity.delete';

			this.item.showLoader();

			BX.ajax.runAction(action, { data })
				.catch((response) => {
					this.item.hideLoader();
					void ErrorNotifier.showError(response.errors[0].message);
				});
		}
	}

	module.exports = { TimelineActivityController };
});
