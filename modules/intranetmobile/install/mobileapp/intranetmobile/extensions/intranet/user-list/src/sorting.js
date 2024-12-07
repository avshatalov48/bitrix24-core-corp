/**
 * @module intranet/user-list/src/sorting
 */
jn.define('intranet/user-list/src/sorting', (require, exports, module) => {
	const { EmployeeStatus } = require('intranet/enum');
	const { BaseListSorting } = require('layout/ui/list/base-sorting');
	const { selectById } = require('intranet/statemanager/redux/slices/employees/selector');
	const store = require('statemanager/redux/store');

	/**
	 * @class UserListSorting
	 * @extends BaseListSorting
	 */
	class UserListSorting extends BaseListSorting
	{
		/**
		 * @public
		 * @static
		 * @returns {Object}
		 */
		static get types()
		{
			return {
				DATE_REGISTER: 'SORT_INVITATION',
				WORK_DEPARTMENT: 'SORT_STRUCTURE',
				FULL_NAME: 'SORT_APH',
			};
		}

		/**
		 * @param {Object} data
		 */
		constructor(data = {})
		{
			super({
				types: UserListSorting.types,
				type: data.type,
				isASC: data.isASC,
				noPropertyValue: data.noPropertyValue ?? Infinity,
			});
		}

		getSortItemsCallback()
		{
			return (a, b) => {
				const aUser = selectById(store.getState(), a.id);
				const bUser = selectById(store.getState(), b.id);

				return super.getSortItemsCallback()(aUser, bUser);
			};
		}

		/**
		 * @private
		 * @param {Object} user
		 * @return {number}
		 */
		getSortingSection(user)
		{
			switch (user.employeeStatus)
			{
				case EmployeeStatus.INVITE_AWAITING_APPROVE.getValue():
					return 0;
				case EmployeeStatus.INVITED.getValue():
					return 1;
				default:
					return 2;
			}
		}

		getPropertyValue = (user) => {
			const userSection = this.getSortingSection(user);

			return userSection === 2 ? 0 : user.dateRegister;
		};
	}

	module.exports = { UserListSorting };
});
