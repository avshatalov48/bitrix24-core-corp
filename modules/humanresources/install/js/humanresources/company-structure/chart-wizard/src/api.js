import { postData } from 'humanresources.company-structure.api';
import { DepartmentUserIds } from './types';

export const WizardAPI = {
	addDepartment: (name: string, parentId: number, description: ?string): Promise<void> => {
		return postData('humanresources.api.Structure.Node.add', {
			name,
			parentId,
			description,
		});
	},
	getEmployees: (nodeId: number): Promise<Array<number>> => {
		return postData('humanresources.api.Structure.Node.Member.Employee.getIds', { nodeId });
	},
	updateDepartment: (nodeId: number, parentId: number, name: string, description: ?string): Promise<void> => {
		return postData('humanresources.api.Structure.Node.update', {
			nodeId,
			name,
			parentId,
			description,
		});
	},
	saveUsers: (nodeId: number, userIds: DepartmentUserIds, parentId: ?number): Promise<Array> => {
		return postData('humanresources.api.Structure.Node.Member.saveUserList', {
			nodeId,
			userIds,
			parentId,
		});
	},
	moveUsers: (nodeId: number, userIds: DepartmentUserIds, parentId: ?number): Promise<Array> => {
		return postData('humanresources.api.Structure.Node.Member.moveUserListToDepartment', {
			nodeId,
			userIds,
			parentId,
		});
	},
};
