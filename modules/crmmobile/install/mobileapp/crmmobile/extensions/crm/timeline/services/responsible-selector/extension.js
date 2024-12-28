/**
 * @module crm/timeline/services/responsible-selector
 */
jn.define('crm/timeline/services/responsible-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { AnalyticsEvent } = require('analytics');
	const AppTheme = require('apptheme');
	const { EntitySelectorFactory } = require('selector/widget/factory');
	const { dispatch } = require('statemanager/redux/store');
	const { usersUpsertedFromEntitySelector } = require('statemanager/redux/slices/users');

	/**
	 * @class ResponsibleSelector
	 */
	class ResponsibleSelector
	{
		static show({ layout, ...restProps })
		{
			const self = new ResponsibleSelector(restProps);
			void self.selector
				.show({}, layout)
				.then((widget) => {
					self.layout = widget;
				});
		}

		/**
		 * @param {Number} responsibleId
		 * @param {Function} onSelectedUsers
		 * @param {?Function} onSelectorHidden
		 */
		constructor({ responsibleId, onSelectedUsers, onSelectorHidden })
		{
			this.onSelectedUsers = onSelectedUsers;
			this.onSelectorHidden = onSelectorHidden;
			this.responsibleId = responsibleId;
			this.selector = null;
			this.layout = null;

			this.onClose = this.onClose.bind(this);
			this.onViewHidden = this.onViewHidden.bind(this);

			this.createSelector();
		}

		createSelector()
		{
			const initSelectedIds = [];
			if (Type.isNumber(this.responsibleId))
			{
				initSelectedIds.push(this.responsibleId);
			}

			this.selector = EntitySelectorFactory.createByType(
				EntitySelectorFactory.Type.USER,
				{
					provider: {},
					createOptions: {
						enableCreation: true,
						closeAfterCreation: true,
						analytics: new AnalyticsEvent().setSection('crm'),
						getParentLayout: () => this.layout,
					},
					initSelectedIds,
					allowMultipleSelection: false,
					closeOnSelect: true,
					events: {
						onClose: this.onClose,
						onViewHiddenStrict: this.onViewHidden,
					},
					widgetParams: {
						title: Loc.getMessage('M_CRM_TIMELINE_SELECT_RESPONSIBLE_TITLE'),
						backdrop: {
							mediumPositionPercent: 70,
							horizontalSwipeAllowed: false,
							navigationBarColor: AppTheme.colors.bgSecondary,
						},
					},
				},
			);
		}

		onClose(selectedUsers)
		{
			if (Array.isArray(selectedUsers) && selectedUsers.length > 0)
			{
				dispatch(usersUpsertedFromEntitySelector(selectedUsers));
				this.onSelectedUsers(selectedUsers);
			}
		}

		onViewHidden()
		{
			if (this.onSelectorHidden)
			{
				this.onSelectorHidden();
			}
		}
	}

	module.exports = { ResponsibleSelector };
});
