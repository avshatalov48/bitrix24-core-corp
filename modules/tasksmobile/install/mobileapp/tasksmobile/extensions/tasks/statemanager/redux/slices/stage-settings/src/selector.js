/**
 * @module tasks/statemanager/redux/slices/stage-settings/src/selector
 */
jn.define('tasks/statemanager/redux/slices/stage-settings/src/selector', (require, exports, module) => {
	const { createDraftSafeSelector } = require('statemanager/redux/toolkit');
	const {
		sliceName,
		adapter,
	} = require('tasks/statemanager/redux/slices/stage-settings/meta');

	const {
		selectById,
		selectEntities,
	} = adapter.getSelectors((state) => state[sliceName]);

	const selectByViewAndProjectId = createDraftSafeSelector(
		selectEntities,
		(state, viewAndProject) => viewAndProject,
		(stages, viewAndProject) => Object.values(stages).filter(
			(item) => item.view === viewAndProject.view && item.projectId === viewAndProject.projectId,
		),
	);

	module.exports = {
		selectById,
		selectEntities,
		selectByViewAndProjectId,
	};
});
