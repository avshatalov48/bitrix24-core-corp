/**
 * @module selector/widget/entity/tree-selectors/nested-department-selector-entity
 */
jn.define('selector/widget/entity/tree-selectors/nested-department-selector-entity', (require, exports, module) => {
	const { NestedDepartmentProvider } = require('selector/providers/tree-providers/nested-department-provider');
	const { BaseSelectorEntity } = require('selector/widget/entity');
	const { Loc } = require('loc');

	/**
	 * @class NestedDepartmentSelectorEntity
	 */
	class NestedDepartmentSelectorEntity extends BaseSelectorEntity
	{
		static make(props)
		{
			return super.make(
				NestedDepartmentSelectorEntity.getDefaultProps(props),
			);
		}

		static getDefaultProps(props)
		{
			return {
				...props,
				shouldRenderHiddenItemsInList: false,
				provider: {
					...props.provider,
					class: NestedDepartmentProvider,
				},
				sectionTitles: {
					recent: '',
					search: '',
				},
			};
		}

		/**
		 * @returns {string|string[]}
		 */
		static getEntityId()
		{
			return ['meta-user', 'department', 'user'];
		}

		static getTitle()
		{
			return Loc.getMessage('NESTED_DEPARTMENT_PROVIDER_USERS_TITLE');
		}

		getPreselectedItems()
		{
			return [];
		}

		getRecentItemsLimit()
		{
			return null;
		}

		shouldClearUnavailableItems()
		{
			return false;
		}
	}

	module.exports = { NestedDepartmentSelectorEntity };
});
