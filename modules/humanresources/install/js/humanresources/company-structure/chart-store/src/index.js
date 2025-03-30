import { defineStore } from 'ui.vue3.pinia';
import { getData } from 'humanresources.company-structure.api';

export const useChartStore = defineStore('hr-org-chart', {
	state: () => ({
		departments: new Map(),
		currentDepartments: [],
		focusedNode: 0,
		searchedUserId: 0,
		userId: 0,
	}),
	actions: {
		async refreshDepartments(nodeIds: number[]): Promise<void>
		{
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
					heads: department.heads,
					userCount: department.userCount,
					employees: null,
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
				return departmentId !== oldDepartmentId;
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
	},
});
