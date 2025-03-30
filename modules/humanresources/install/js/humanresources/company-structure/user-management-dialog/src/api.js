import { postData } from 'humanresources.company-structure.api';

export const UserManagementDialogAPI = {
	moveUsersToDepartment: (nodeId: number, userIds: number[]): Promise<void> => {
		return postData('humanresources.api.Structure.Node.Member.moveUserListToDepartment', {
			nodeId,
			userIds,
		});
	},
	addUsersToDepartment: (nodeId: number, userIds: number[], role: string): Promise<void> => {
		return postData('humanresources.api.Structure.Node.Member.addUserMember', {
			nodeId,
			userIds,
			roleXmlId: role,
		});
	},
};
