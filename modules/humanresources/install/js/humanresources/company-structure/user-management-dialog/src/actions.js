import { useChartStore } from 'humanresources.company-structure.chart-store';

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
};
