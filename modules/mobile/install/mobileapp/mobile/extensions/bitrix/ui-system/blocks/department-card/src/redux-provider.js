/**
 * @module ui-system/blocks/department-card/src/redux-provider
 */
jn.define('ui-system/blocks/department-card/src/redux-provider', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { DepartmentCardMode } = require('ui-system/blocks/department-card/src/mode-enum');
	const { selectById: selectDepartmentById } = require('intranet/statemanager/redux/slices/department');
	const { selectById: selectUserById } = require('statemanager/redux/slices/users/selector');

	const mapStateToProps = (state, {
		departmentId,
		depth = 0,
		userId = null,
		departmentsCount,
		chevron = true,
	}) => {
		const department = selectDepartmentById(state, departmentId);
		if (Type.isNil(department))
		{
			return {};
		}

		const { headIds, employeeCount } = department;

		const employee = userId ? selectUserById(state, userId) : null;
		const employeesCountText = Loc.getMessagePlural(
			'DEPARTMENT_STRUCTURE_USERS_COUNT',
			employeeCount,
			{ '#COUNT#': employeeCount },
		);
		const managers = headIds.map((headId) => selectUserById(state, headId)).filter((user) => !Type.isNil(user));
		const isLastDepartment = depth === departmentsCount - 1;
		const shouldShowEmployee = isLastDepartment && !Type.isNil(employee);
		const mainManager = managers[0] ?? {};

		if (shouldShowEmployee)
		{
			const employeeIsHeadOfDepartment = managers.some((manager) => manager.id === employee.id);
			const managerProps = employeeIsHeadOfDepartment ? {} : {
				managerTitle: Loc.getMessage('DEPARTMENT_MANAGER_TITLE'),
				managerName: mainManager.fullName,
				managerAvatarProps: {
					uri: mainManager.avatarSize100 || '',
				},
				managerCounterValue: managers.length > 1 ? `+${managers.length - 1}` : undefined,
			};

			return {
				testId: `department-card-${departmentsCount - depth - 1}`,
				mode: employeeIsHeadOfDepartment ? DepartmentCardMode.UPPER : DepartmentCardMode.CURRENT,
				departmentName: department.name,
				employeeName: employee.fullName,
				employeePosition: employee.workPosition,
				employeeAvatarProps: {
					uri: employee.avatarSize100 || '',
				},
				employeesCountText,
				chevron,
				depth,
				accent: isLastDepartment,
				...managerProps,
			};
		}

		return {
			testId: `department-card-${departmentsCount - depth - 1}`,
			mode: DepartmentCardMode.UPPER,
			departmentName: department.name,
			employeeName: mainManager.fullName,
			employeePosition: mainManager.workPosition,
			employeeCounterValue: managers.length > 1 ? `+${managers.length - 1}` : undefined,
			employeeAvatarProps: {
				uri: mainManager?.avatarSize100 || '',
			},
			employeesCountText,
			chevron,
			depth,
			accent: isLastDepartment,
		};
	};

	module.exports = { mapStateToProps };
});
