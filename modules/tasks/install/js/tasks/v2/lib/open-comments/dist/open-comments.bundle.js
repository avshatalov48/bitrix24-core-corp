/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,tasks_v2_lib_apiClient) {
	'use strict';

	const openComments = async taskId => {
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
	    contentCallback: () => commentsElement
	  });
	};
	async function getLegacyCommentsByTaskId(id) {
	  try {
	    var _data$html;
	    const data = await tasks_v2_lib_apiClient.apiClient.post('LegacyComment.get', {
	      task: {
	        id
	      }
	    });
	    return (_data$html = data.html) != null ? _data$html : '';
	  } catch (error) {
	    console.error('getLegacyCommentsByTaskId error', error);
	    return '';
	  }
	}

	exports.openComments = openComments;

}((this.BX.Tasks.V2.Lib = this.BX.Tasks.V2.Lib || {}),BX.Tasks.V2.Lib));
//# sourceMappingURL=open-comments.bundle.js.map
