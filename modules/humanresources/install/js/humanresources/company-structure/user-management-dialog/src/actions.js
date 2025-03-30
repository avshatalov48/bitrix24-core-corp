import { useChartStore } from 'humanresources.company-structure.chart-store';
import { memberRoles } from 'humanresources.company-structure.api';

export const UserManagementDialogActions = {
	getDepartmentName: (nodeId: number): string => {
		const { departments } = useChartStore();
		const targetDepartment = departments.get(nodeId);
		if (!targetDepartment)
		{
			return '';
		}

		return targetDepartment.name;
	},
	moveUsersToDepartment: (
		nodeId: number,
		users: Array,
		userCount: number,
		updatedDepartmentIds: number[],
	): void => {
		const store = useChartStore();
		const targetDepartment = store.departments.get(nodeId);
		if (!targetDepartment)
		{
			return;
		}

		const newMemberUserIds = new Set(users.map((user) => user.id));
		const employees = (targetDepartment.employees ?? []).filter((user) => !newMemberUserIds.has(user.id));
		const headsUserIds = new Set(targetDepartment.heads.map((head) => head.id));
		const newUsers = users.filter((user) => !headsUserIds.has(user.id));
		employees.push(...newUsers);
		targetDepartment.employees = employees;
		targetDepartment.userCount = userCount;

		if (updatedDepartmentIds.length > 0)
		{
			void store.refreshDepartments(updatedDepartmentIds);
		}
	},
	addUsersToDepartment: (
		nodeId: number,
		users: Array,
		userCount: number,
		role: string,
	): void => {
		const store = useChartStore();
		const targetDepartment = store.departments.get(nodeId);
		if (!targetDepartment)
		{
			return;
		}

		const newMemberUserIds = new Set(users.map((user) => user.id));
		if (newMemberUserIds.has(store.userId))
		{
			store.changeCurrentDepartment(0, targetDepartment.id);
		}
		const heads = (targetDepartment.heads ?? []).filter((user) => !newMemberUserIds.has(user.id));

		const employees = (targetDepartment.employees ?? []).filter((user) => !newMemberUserIds.has(user.id));
		(role === memberRoles.employee ? employees : heads).push(...users);

		targetDepartment.heads = heads;
		targetDepartment.employees = employees;
		targetDepartment.userCount = userCount;
	},
};
