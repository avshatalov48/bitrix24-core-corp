import { memberRoles } from 'humanresources.company-structure.api';

type UserData = {
	id: number,
	avatar: ?string,
	name: string,
	workPosition: ?string,
	role: string,
};

export const getUserStoreItemByDialogItem = (item, role): UserData => {
	const { id, avatar, title, customData } = item;
	const link = item.getLink();
	const workPosition = customData.get('position') ?? '';

	return {
		id,
		avatar,
		name: title.text,
		workPosition,
		role,
		url: link,
	};
};

export const moveUserStoreToAnotherDepartment = (departments, nodeId, userId, targetNodeId, role) => {
	const department = departments.get(nodeId);
	if (!department)
	{
		return;
	}

	let user = null;
	department.userCount -= 1;
	if (role === memberRoles.employee)
	{
		user = department.employees.find((employee) => employee.id === userId);
		department.employees = department.employees.filter((employee) => employee.id !== userId);
	}
	else
	{
		user = department.heads.find((head) => head.id === userId);
		department.heads = department.heads.filter((head) => head.id !== userId);
	}

	if (!user)
	{
		return;
	}

	const targetDepartment = departments.get(targetNodeId);
	if (!targetDepartment)
	{
		return;
	}

	targetDepartment.userCount += 1;
	user.role = memberRoles.employee;
	if (!targetDepartment.employees)
	{
		return;
	}
	targetDepartment.employees.push(user);
};

export const removeUserFromStore = (departments, nodeId, userId, role) => {
	const department = departments.get(nodeId);
	if (!department)
	{
		return;
	}

	department.userCount -= 1;
	if (role === memberRoles.employee)
	{
		department.employees = department.employees.filter((employee) => employee.id !== userId);

		return;
	}

	department.heads = department.heads.filter((head) => head.id !== userId);
};