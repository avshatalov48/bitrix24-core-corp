/**
 * @module calendar/collab-slots
 */
jn.define('calendar/collab-slots', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { NotifyManager } = require('notify-manager');
	const { EntitySelectorFactory } = require('selector/widget/factory');
	const { tariffPlanRestrictionsReady, getFeatureRestriction } = require('tariff-plan-restriction');

	const { SharingAjax } = require('calendar/ajax');
	const { FeatureId } = require('calendar/enums');

	/**
	 * @class CollabSlots
	 */
	class CollabSlots
	{
		/**
		 * @param props
		 * @param [props.layout] {PageManager}
		 * @param [props.userId] {number}
		 * @param [props.groupId] {number}
		 * @param [props.dialogId] {string}
		 */
		constructor(props)
		{
			this.layout = props.layout || PageManager;
			this.userId = props.userId || env.userId;
			this.groupId = props.groupId;
			this.dialogId = props.dialogId;
		}

		async create()
		{
			await tariffPlanRestrictionsReady();

			const { isRestricted, showRestriction } = getFeatureRestriction(FeatureId.CALENDAR_SHARING);

			if (isRestricted())
			{
				showRestriction({
					parentWidget: this.layout,
				});
			}
			else
			{
				await EntitySelectorFactory.createByType(EntitySelectorFactory.Type.USER, {
					provider: {
						context: 'CALENDAR_SLOTS',
					},
					canUseRecent: true,
					createOptions: {
						enableCreation: false,
					},
					initSelectedIds: [Number(this.userId)],
					undeselectableIds: [Number(this.userId)],
					allowMultipleSelection: true,
					events: {
						onClose: (currentEntities) => {
							this.generateLink(currentEntities);
						},
					},
					widgetParams: {
						title: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EVENT_ATTENDEES'),
						backdrop: {
							mediumPositionPercent: 80,
							horizontalSwipeAllowed: false,
						},
					},
				}).show({}, this.layout);
			}
		}

		async generateLink(currentEntities)
		{
			await NotifyManager.showLoadingIndicator();

			const memberIds = currentEntities.map((entity) => entity.id);

			const { data, errors } = await SharingAjax.generateGroupJointSharingLink({
				memberIds,
				groupId: this.groupId,
				dialogId: this.dialogId,
			});

			if (Type.isArrayFilled(errors))
			{
				console.error(errors);
			}

			NotifyManager.hideLoadingIndicator(Boolean(data));
		}
	}

	module.exports = { CollabSlots };
});
