/**
 * @module selector/widget/entity/intranet/department
 */
jn.define('selector/widget/entity/intranet/department', (require, exports, module) => {
	const { Loc } = require('loc');
	const { mergeImmutable } = require('utils/object');

	/**
	 * @class DepartmentSelector
	 */
	class DepartmentSelector extends BaseSelectorEntity
	{
		/**
		 * @returns {Object}
		 */
		static get selectModes()
		{
			return {
				MODE_DEPARTMENTS_ONLY: 'departmentsOnly',
				MODE_USERS_ONLY: 'usersOnly',
				MODE_USERS_AND_DEPARTMENTS: 'usersAndDepartments',
			};
		}

		/**
		 * @returns {string}
		 */
		static getEntityId()
		{
			return 'department';
		}

		/**
		 * @returns {string}
		 */
		static getContext()
		{
			return 'mobile-department';
		}

		/**
		 * @returns {string}
		 */
		static getStartTypingText()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_DEPARTMENT');
		}

		/**
		 * @returns {string}
		 */
		static getTitle()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_PICK_DEPARTMENT');
		}

		/**
		 * @param {Object} providerOptions
		 * @param {Array} entityIds
		 * @returns {Array}
		 */
		static getEntitiesOptions(providerOptions, entityIds)
		{
			return [
				{
					id: entityIds[0],
					options: mergeImmutable(this.getDefaultProviderOption(), providerOptions),
					searchable: true,
					dynamicLoad: true,
					dynamicSearch: true,
				},
			];
		}

		/**
		 * @returns {Object}
		 */
		static getDefaultProviderOption()
		{
			return {
				selectMode: this.selectModes.MODE_DEPARTMENTS_ONLY,
				allowOnlyUserDepartments: false,
				allowFlatDepartments: true,
				allowSelectRootDepartment: true,
				fillRecentTab: true,
				fillDepartmentsTab: false,
				depthLevel: 2,
			};
		}
	}

	module.exports = { DepartmentSelector };
});
