import { postData } from 'humanresources.company-structure.api';

export const MoveAPI = {
	moveUserToDepartment: (nodeId: number, userId: number, targetNodeId: number, role: string): Promise<void> => {
		return postData('humanresources.api.Structure.Node.Member.moveUser', {
			nodeId,
			userId,
			targetNodeId,
			roleXmlId: role,
		});
	},
};
