/**
 * @module statemanager/redux/slices/gratitude/selector
 */
jn.define('statemanager/redux/slices/gratitude/selector', (require, exports, module) => {
	const { sliceName, gratitudeAdapter } = require('statemanager/redux/slices/gratitude/meta');
	const { createDraftSafeSelector } = require('statemanager/redux/toolkit');
	const {
		selectAll,
		selectById,
	} = gratitudeAdapter.getSelectors((state) => state[sliceName]);

	const selectGratitudesByOwnerId = createDraftSafeSelector(
		[selectAll, (_, userId) => userId],
		(gratitudes, userId) => gratitudes
			.filter((gratitude) => gratitude?.ownerId === userId)
			.sort((newer, older) => older.createdAt - newer.createdAt),
	);

	const selectGratitudeByPostId = createDraftSafeSelector(
		[selectAll, (_, relatedId) => relatedId],
		(gratitudes, relatedId) => gratitudes
			.filter((gratitude) => gratitude?.relatedPostId === relatedId)
			.sort((newer, older) => older.createdAt - newer.createdAt)[0],
	);

	module.exports = {
		selectById,
		selectAll,
		selectGratitudesByOwnerId,
		selectGratitudeByPostId,
	};
});
