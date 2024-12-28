import { getData, postData } from 'humanresources.company-structure.api';

const createTreeDataStore = (treeData) => {
	const dataMap = new Map();
	treeData.forEach((item) => {
		const { id, parentId } = item;
		const mapItem = dataMap.get(id) ?? {};
		dataMap.set(id, { ...mapItem, ...item });
		const mapParentItem = dataMap.get(parentId) ?? {};
		const children = mapParentItem.children ?? [];
		dataMap.set(parentId, {
			...mapParentItem,
			children: [...children, id],
		});
	});

	return dataMap;
};

export const chartAPI = {
	getDepartment: (id: Number) => {
		return getData('humanresources.api.Structure.Node.get', { nodeId: id });
	},
	removeDepartment: (id: Number) => {
		return getData('humanresources.api.Structure.Node.delete', { nodeId: id });
	},
	getEmployees: (id: Number) => {
		return getData('humanresources.api.Structure.Node.Member.Employee.get', { nodeId: id });
	},
	getChartData: () => {
		return getData('humanresources.api.Structure.get', {}, { tool: 'structure', category: 'structure', event: 'open_structure' });
	},
	getCurrentDepartment: () => {
		return getData('humanresources.api.Structure.Node.current');
	},
	getDictionary: () => {
		return getData('humanresources.api.Structure.dictionary');
	},
	getUserId: () => {
		return getData('humanresources.api.User.getCurrentId');
	},
	firstTimeOpened: () => {
		return postData('humanresources.api.User.firstTimeOpen');
	},
	createTreeDataStore,
};
