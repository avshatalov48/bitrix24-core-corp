import { getData } from 'humanresources.company-structure.api';
import { useChartStore } from 'humanresources.company-structure.chart-store';

export const refreshDepartments = async (nodeIds: number[]) => {
	const chartStore = useChartStore();
	const departments = await getData('humanresources.api.Structure.Node.getByIds', { nodeIds });

	Object.keys(departments).forEach((id) => {
		const department = departments[id];
		const existingDepartment = chartStore.departments.get(Number(id)) || {};

		chartStore.departments.set(Number(id), {
			...existingDepartment,
			heads: department.heads,
			userCount: department.userCount,
			page: 0,
			shouldUpdateList: true,
			employees: [],
		});
	});
};
