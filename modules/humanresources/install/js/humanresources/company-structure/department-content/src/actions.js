import { useChartStore } from 'humanresources.company-structure.chart-store';
import { memberRoles } from 'humanresources.company-structure.api';

export const DepartmentContentActions = {
	moveUserToDepartment: (departmentId, userId, targetDepartmentId, role): void => {
		const store = useChartStore();
		const department = store.departments.get(departmentId);
		const targetDepartment = store.departments.get(targetDepartmentId);

		if (!department || !targetDepartment)
		{
			return;
		}

		const user = role === memberRoles.employee
			? department.employees?.find((employee) => employee.id === userId)
			: department.heads.find((head) => head.id === userId)
		;
		if (!user)
		{
			return;
		}

		department.userCount -= 1;
		if (role === memberRoles.employee)
		{
			department.employees = department.employees.filter((employee) => employee.id !== userId);
		}
		else
		{
			department.heads = department.heads.filter((head) => head.id !== userId);
		}

		targetDepartment.userCount += 1;
		if (userId === store.userId)
		{
			store.changeCurrentDepartment(departmentId, targetDepartmentId);
		}

		user.role = memberRoles.employee;
		if (!targetDepartment.employees)
		{
			return;
		}
		targetDepartment.employees.push(user);
	},
	removeUserFromDepartment: (departmentId, userId, role): void => {
		const store = useChartStore();
		const department = store.departments.get(departmentId);
		if (!department)
		{
			return;
		}

		if (userId === store.userId)
		{
			store.changeCurrentDepartment(departmentId);
		}

		department.userCount -= 1;
		if (role === memberRoles.employee)
		{
			department.employees = department.employees.filter((employee) => employee.id !== userId);

			return;
		}

		department.heads = department.heads.filter((head) => head.id !== userId);
	},
	updateEmployees: (departmentId: number, employees: Array): void => {
		const { departments } = useChartStore();
		const department = departments.get(departmentId);

		if (!department)
		{
			return;
		}

		departments.set(departmentId, { ...department, employees });
	},
	updateEmployeeListOptions: (
		departmentId: number,
		options: { page?: number, shouldUpdateList?: boolean, isListUpdated?: boolean }
	): void => {
		const { departments } = useChartStore();
		const department = departments.get(departmentId);

		if (!department)
		{
			return;
		}

		department.employeeListOptions = {
			...department.employeeListOptions,
			...options,
		};

		departments.set(departmentId, department);
	},
};
