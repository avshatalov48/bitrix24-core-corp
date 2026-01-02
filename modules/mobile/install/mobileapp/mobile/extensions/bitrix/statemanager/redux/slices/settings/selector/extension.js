/**
 * @module statemanager/redux/slices/settings/selector
 */

jn.define('statemanager/redux/slices/settings/selector', (require, exports, module) => {
	const { sliceName, settingsAdapter } = require('statemanager/redux/slices/settings/meta');
	const { createDraftSafeSelector } = require('statemanager/redux/toolkit');

	const { selectById } = settingsAdapter.getSelectors((state) => state[sliceName]);

	const selectReactionsTemplate = createDraftSafeSelector(
		(state) => selectById(state, 'reactions_template'),
		(reactionTemplate) => reactionTemplate?.value,
	);

	const selectReactionMessageById = createDraftSafeSelector(
		(state) => selectById(state, 'reactions_like_message'),
		(reactionMessage) => reactionMessage?.value,
	);

	const selectIsCollabToolEnabled = createDraftSafeSelector(
		(state) => selectById(state, 'collab_tool_enabled'),
		(collabSetting) => collabSetting?.value ?? true,
	);

	module.exports = {
		selectById,
		selectReactionsTemplate,
		selectReactionMessageById,
		selectIsCollabToolEnabled,
	};
});
