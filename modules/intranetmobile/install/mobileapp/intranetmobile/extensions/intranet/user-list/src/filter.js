/**
 * @module intranet/user-list/src/filter
 */
jn.define('intranet/user-list/src/filter', (require, exports, module) => {
	const { BaseListFilter } = require('layout/ui/list/base-filter');
	const { EmployeeStatus } = require('intranet/enum');
	const { Loc } = require('loc');
	/**
	 * @class UsersFilter
	 */
	class UserListFilter extends BaseListFilter
	{
		static get presetType()
		{
			return {
				default: 'company',
			};
		}

		static get defaultDepartment()
		{
			return {
				id: 0,
				title: Loc.getMessage('MOBILE_USERS_FILTER_DEFAULT_DEPARTMENT_TITLE'),
			};
		}

		/**
		 * @param {String} department
		 * @param {String} presetId
		 * @param {String} searchString
		 */
		constructor({ presetId, searchString, department })
		{
			super(presetId, searchString);
			this.setDepartment(department);
		}

		/**
		 * @public
		 * @param {String} department
		 */
		setDepartment(department)
		{
			this.department = department || UserListFilter.defaultDepartment;
		}

		/**
		 * @public
		 * @return {String}
		 */
		getDepartment()
		{
			return this.department;
		}

		getDefaultDepartment()
		{
			return UserListFilter.defaultDepartment;
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isDefault()
		{
			return (
				this.isSearchStringEmpty()
				&& this.isDefaultDepartment()
				&& this.isDefaultPreset()
			);
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isEmpty()
		{
			return (
				this.isSearchStringEmpty()
				&& this.isEmptyPreset()
				&& this.isDefaultDepartment()
			);
		}

		/**
		 * @public
		 * @return {String}
		 */
		getDefaultPreset()
		{
			return UserListFilter.presetType.default;
		}

		/**
		 * @public
		 * @return {Boolean}
		 */
		isDefaultDepartment()
		{
			return this.department.id === UserListFilter.defaultDepartment.id;
		}

		getFillPresetParams()
		{
			return null;
		}

		static get presetsIds()
		{
			return {
				company: 'company',
				extranet: 'extranet',
				fired: 'fired',
				invited: 'invited',
				waitConfirmation: 'wait_confirmation',
			};
		}

		getEmployeesFilter = () => {
			switch (this.presetId)
			{
				case UserListFilter.presetsIds.company:
					return {
						isExtranetUser: false,
						employeeStatus: [
							EmployeeStatus.INVITED.getValue(),
							EmployeeStatus.INVITE_AWAITING_APPROVE.getValue(),
							EmployeeStatus.ACTIVE.getValue(),
						],
					};
				case UserListFilter.presetsIds.extranet:
					return {
						isExtranetUser: true,
						employeeStatus: [
							EmployeeStatus.INVITED.getValue(),
							EmployeeStatus.INVITE_AWAITING_APPROVE.getValue(),
							EmployeeStatus.ACTIVE.getValue(),
						],
					};
				case UserListFilter.presetsIds.fired:
					return {
						employeeStatus: [
							EmployeeStatus.FIRED.getValue(),
						],
					};
				case UserListFilter.presetsIds.invited:
					return {
						employeeStatus: [
							EmployeeStatus.INVITED.getValue(),
						],
					};
				case UserListFilter.presetsIds.waitConfirmation:
					return {
						employeeStatus: [
							EmployeeStatus.INVITE_AWAITING_APPROVE.getValue(),
						],
					};
				default:
					return {
						employeeStatus: [
							EmployeeStatus.INVITED.getValue(),
							EmployeeStatus.INVITE_AWAITING_APPROVE.getValue(),
							EmployeeStatus.ACTIVE.getValue(),
							EmployeeStatus.FIRED.getValue(),
						],
					};
			}
		}
	}
	module.exports = { UserListFilter };
});
