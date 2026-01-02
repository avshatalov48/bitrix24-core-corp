import { Type } from 'main.core';
import { defineStore } from 'ui.vue3.pinia';
import { getData } from 'humanresources.company-structure.api';
import {
	EntityTypes,
	WizardApiEntityChangedDict,
	NodeSettingsTypes,
	type CommunicationDetailed,
	type UserData,
	type NodeColorSettingsType,
} from 'humanresources.company-structure.utils';
import { UserService } from './classes/user-service';

export type DepartmentData = {
	id: number;
	name: string;
	description: string;
	parentId: number;
	heads: Array<UserData>;
	employees: Array<UserData>;
	userCount: number;
	chats: Array<number>,
	chatsDetailed: Array<CommunicationDetailed>,
	chatsNoAccess: number,
	channels: Array<number>,
	channelsDetailed: Array<CommunicationDetailed>,
	channelsNoAccess: number,
	collabsDetailed: Array<CommunicationDetailed>,
	collabsNoAccess: number,
	communicationsCount?: number,
	children?: Array<number>,
	createDefaultChat: boolean,
	createDefaultChannel: boolean,
	teamColor: NodeColorSettingsType,
	settings: {
		[NodeSettingsTypes.businessProcAuthority]: Set,
		[NodeSettingsTypes.reportsAuthority]: Set,
	},
	entityType: EntityTypes.department | EntityTypes.team | EntityTypes.company,
	apiEntityChanged: WizardApiEntityChangedDict.department | WizardApiEntityChangedDict.employees,
};

export const useChartStore = defineStore('hr-org-chart', {
	state: () => ({
		departments: new Map(), // list of all entities of structures visible to the user with detailed information
		currentDepartments: [],
		focusedNode: 0,
		searchedUserId: 0,
		userId: 0,
		/** @var Map<number, { id: number, parentId: number, entityType: string }> */
		structureMap: new Map(), // map of the entire structure (all entities) with minimal information
		multipleUsers: [],
	}),
	actions: {
		async refreshDepartments(nodeIds: number[]): Promise<void>
		{
			if (nodeIds.length === 0)
			{
				return;
			}

			const [departments, currentDepartments] = await Promise.all([
				getData('humanresources.api.Structure.Node.getByIds', { nodeIds }),
				getData('humanresources.api.Structure.Node.current'),
			]);
			this.currentDepartments = currentDepartments;
			Object.keys(departments).forEach((id) => {
				const department = departments[id];
				const existingDepartment = this.departments.get(Number(id)) || {};
				this.departments.set(Number(id), {
					...existingDepartment,
					...department,
					heads: department.heads,
					userCount: department.userCount,
					employees: [],
					employeeListOptions: {
						page: 0,
						shouldUpdateList: true,
						isListUpdated: false,
					},
				});
			});
		},
		changeCurrentDepartment(oldDepartmentId: number, newDepartmentId: ?number): void
		{
			const currentDepartments = this.currentDepartments.filter((departmentId) => {
				return departmentId !== oldDepartmentId && departmentId !== newDepartmentId;
			});

			if (!newDepartmentId)
			{
				this.currentDepartments = currentDepartments;

				return;
			}

			this.currentDepartments = [
				...currentDepartments,
				newDepartmentId,
			];
		},
		async loadHeads(nodeIds: number[]): Promise<void>
		{
			if (nodeIds.length === 0)
			{
				return;
			}

			const heads = await getData('humanresources.api.Structure.Node.getHeadsByIds', { nodeIds });
			nodeIds.forEach((departmentId) => {
				const department = this.departments.get(departmentId);
				if (heads[departmentId])
				{
					this.departments.set(departmentId, { ...department, heads: heads[departmentId] });
				}
			});
		},
		updateDepartment(departmentData: DepartmentData, position: ?number): void
		{
			const { id, parentId } = departmentData;
			const oldData = this.departments.get(id);
			const entityType = oldData.entityType;
			const prevParent = this.departments.get(oldData.parentId);
			this.departments.set(id, { ...oldData, ...departmentData });
			this.structureMap.set(id, {
				id,
				parentId: parentId ?? oldData.parentId,
				entityType,
			});
			if (parentId !== 0 && prevParent.id !== parentId)
			{
				prevParent.children = prevParent.children.filter((childId) => childId !== id);
				const newParent = this.departments.get(parentId);
				newParent.children = newParent.children ?? [];
				if (Type.isNumber(position))
				{
					newParent.children.splice(position, 0, id);
				}
				else
				{
					newParent.children.push(id);
				}

				this.departments.set(id, { ...this.departments.get(id), prevParentId: prevParent.id });
			}
		},
		updateChatsInChildrenNodes(parentNodeId: number): void
		{
			const parentDepartment = this.departments.get(parentNodeId);

			if (!parentDepartment || !parentDepartment.children)
			{
				return;
			}

			this.markDescendantsForChatReload(parentDepartment.children);
		},
		markDescendantsForChatReload(childrenIds: Array<number>): void
		{
			const store = useChartStore();
			const queue = [...childrenIds];
			const visited = new Set();
			const maxIterations = 10000;
			let iterations = 0;

			while (queue.length > 0 && iterations < maxIterations)
			{
				iterations++;
				const childId = queue.shift();

				if (visited.has(childId))
				{
					continue;
				}
				visited.add(childId);

				const childDepartment = store.departments.get(childId);
				if (!childDepartment)
				{
					continue;
				}

				childDepartment.chatsDetailed = null;
				childDepartment.channelsDetailed = null;
				childDepartment.collabsDetailed = null;

				if (childDepartment.children && childDepartment.children.length > 0)
				{
					queue.push(...childDepartment.children);
				}
			}
		},
	},
});

export {
	UserService,
};
