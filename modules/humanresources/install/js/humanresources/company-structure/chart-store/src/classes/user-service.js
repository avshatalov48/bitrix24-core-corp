import { getData, getMemberRoles } from 'humanresources.company-structure.api';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { Type } from 'main.core';
import { EntityTypes } from 'humanresources.company-structure.utils';

export class UserServiceClass
{
	async addUsersToEntity(
		entityId: number,
		users: Array, // array of user objects. Each object should contain user data such as id, name, etc
		userCount: number,
		role: string,
	): void
	{
		const store = useChartStore();
		const targetEntity = store.departments.get(entityId);
		if (!targetEntity)
		{
			return;
		}

		const memberRoles = getMemberRoles(targetEntity.entityType);

		const newMemberUserIds = new Set(users.map((user) => user.id));
		if (newMemberUserIds.has(store.userId))
		{
			store.changeCurrentDepartment(0, targetEntity.id);
		}
		const heads = (targetEntity.heads ?? []).filter((user) => !newMemberUserIds.has(user.id));

		const employees = (targetEntity.employees ?? []).filter((user) => !newMemberUserIds.has(user.id));
		(role === memberRoles.employee ? employees : heads).push(...users);

		targetEntity.heads = heads;
		targetEntity.employees = employees;
		targetEntity.userCount = userCount;

		await this.refreshMultipleUsers();
	}

	removeUserFromEntity(entityId: number, userId: number, role: ?string): void
	{
		const store = useChartStore();
		const entity = store.departments.get(entityId);
		if (!entity)
		{
			return;
		}

		const oldMemberRoles = getMemberRoles(entity.entityType);

		if (userId === store.userId)
		{
			store.changeCurrentDepartment(entityId);
		}

		entity.userCount -= 1;
		if (role === oldMemberRoles.employee)
		{
			entity.employees = entity.employees.filter((employee) => employee.id !== userId);
		}
		else
		{
			entity.heads = entity.heads.filter((head) => head.id !== userId);
		}

		const key = String(userId);
		const nodeIds = store.multipleUsers[key];
		if (Type.isArray(nodeIds))
		{
			const filtered = nodeIds.filter((id) => Number(id) !== Number(entityId));
			if (filtered.length >= 2)
			{
				store.multipleUsers[key] = filtered;
			}
			else
			{
				delete store.multipleUsers[key];
			}
		}
	}

	moveUserToEntity(entityId: number, userId: number, targetEntityId: number, role: string): void
	{
		const store = useChartStore();
		const entity = store.departments.get(entityId);
		const targetEntity = store.departments.get(targetEntityId);

		if (!entity || !targetEntity)
		{
			return;
		}

		const oldMemberRoles = getMemberRoles(entity.entityType);
		const targetMemberRoles = getMemberRoles(targetEntity.entityType);
		const user = role === oldMemberRoles.employee
			? entity.employees?.find((employee) => employee.id === userId)
			: entity.heads.find((head) => head.id === userId)
		;

		if (!user)
		{
			return;
		}

		entity.userCount -= 1;
		if (role === oldMemberRoles.employee)
		{
			entity.employees = entity.employees.filter((employee) => employee.id !== userId);
		}
		else
		{
			entity.heads = entity.heads.filter((head) => head.id !== userId);
		}

		targetEntity.userCount += 1;
		if (userId === store.userId)
		{
			store.changeCurrentDepartment(entityId, targetEntityId);
		}

		user.role = targetMemberRoles.employee;
		if (!targetEntity.employees)
		{
			return;
		}
		targetEntity.employees.push(user);

		if (entity.entityType === EntityTypes.department)
		{
			const key = String(userId);
			const entityIds = store.multipleUsers[key];
			if (Type.isArray(entityIds))
			{
				const current = new Set(entityIds.map((v) => Number(v)));
				current.delete(Number(entityId));
				current.add(Number(targetEntityId));
				store.multipleUsers[key] = [...current];
			}
		}
	}

	async moveUsersToEntity(
		entityId: number,
		users: Array, // array of user objects. Each object should contain user data such as id, name, etc
		userCount: number,
		updatedDepartmentIds: number[],
	): void
	{
		const store = useChartStore();
		const entityDepartment = store.departments.get(entityId);
		if (!entityDepartment)
		{
			return;
		}

		const newMemberUserIds = new Set(users.map((user) => user.id));
		const employees = (entityDepartment.employees ?? []).filter((user) => !newMemberUserIds.has(user.id));
		const headsUserIds = new Set(entityDepartment.heads.map((head) => head.id));
		const newUsers = users.filter((user) => !headsUserIds.has(user.id));
		employees.push(...newUsers);
		entityDepartment.employees = employees;
		entityDepartment.userCount = userCount;

		users.forEach(({ id }) => {
			const key = String(id);
			if (Object.prototype.hasOwnProperty.call(store.multipleUsers, key))
			{
				delete store.multipleUsers[key];
			}
		});

		if (updatedDepartmentIds.length > 0)
		{
			void store.refreshDepartments(updatedDepartmentIds);
		}
	}

	async removeUserFromAllEntities(userId: number): Promise<void>
	{
		const store = useChartStore();
		const entities = store.departments;
		const entitiesToUpdate = [];

		for (const [key, entity] of entities)
		{
			if (
				(
					'heads' in entity
					&& Type.isArray(entity.heads)
					&& entity.heads.some((employee) => employee.id === userId)
				)
				|| (
					'employees' in entity
					&& Type.isArray(entity.employees)
					&& entity.employees.some((employee) => employee.id === userId)
				)
			)
			{
				entitiesToUpdate.push(key);
			}
		}
		delete store.multipleUsers[String(userId)];

		return store.refreshDepartments(entitiesToUpdate);
	}

	async refreshMultipleUsers(): Promise<void>
	{
		const store = useChartStore();
		store.multipleUsers = await getData('humanresources.api.Structure.Node.Member.getMultipleUsersMap');
	}
}

export const UserService = new UserServiceClass();
