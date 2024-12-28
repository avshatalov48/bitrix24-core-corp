import { getData, postData } from 'humanresources.company-structure.api';

export const DepartmentAPI = {
	getPagedEmployees: (id: Number, page: Number, countPerPage: Number) => {
		return getData('humanresources.api.Structure.Node.Member.Employee.list', { nodeId: id, page, countPerPage });
	},
	removeUserFromDepartment: (nodeId: number, userId: number): Promise<void> => {
		return postData('humanresources.api.Structure.Node.Member.deleteUser', {
			nodeId,
			userId,
		});
	},
	moveUserToDepartment: (nodeId: number, userId: number, targetNodeId: number,): Promise<void> => {
		return postData('humanresources.api.Structure.Node.Member.moveUser', {
			nodeId,
			userId,
			targetNodeId,
		});
	},
	isUserInMultipleDepartments: (userId: number): Promise<void> => {
		return getData('humanresources.api.User.isUserInMultipleDepartments', {
			userId,
		});
	},
	getUserInfo: (nodeId: number, userId: number): Promise<void> => {
		return getData('humanresources.api.User.getInfoByUserMember', {
			nodeId,
			userId,
		});
	},
	findMemberByQuery: (nodeId: number, query: string): Promise<void> => {
		return getData('humanresources.api.Structure.Node.Member.find', {
			nodeId,
			query,
		});
	},
};
