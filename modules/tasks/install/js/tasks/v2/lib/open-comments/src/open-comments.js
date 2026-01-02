import { apiClient } from 'tasks.v2.lib.api-client';

import './open-comments.css';

export const openComments = async (taskId: number): Promise<void> => {
	const content = await getLegacyCommentsByTaskId(taskId);
	const sidePanelId = `tasks-task-legacy-comments-${taskId}`;
	const maxWidth = 650;

	const commentsElement = document.createElement('div');
	BX.Runtime.html(commentsElement, `<div class="tasks-task-full-card-legacy-comments">${content}</div>`);

	BX.SidePanel.Instance.open(sidePanelId, {
		customLeftBoundary: 0,
		width: maxWidth,
		cacheable: false,
		customRightBoundary: 0,
		contentCallback: () => commentsElement,
	});
};

async function getLegacyCommentsByTaskId(id: number): Promise<string>
{
	try
	{
		const data = await apiClient.post('LegacyComment.get', { task: { id } });

		return (data.html) ?? '';
	}
	catch (error)
	{
		console.error('getLegacyCommentsByTaskId error', error);

		return '';
	}
}
