/**
 * @module statemanager/redux/slices/users/selector
 */
jn.define('statemanager/redux/slices/users/selector', (require, exports, module) => {
	const { sliceName, usersAdapter } = require('statemanager/redux/slices/users/meta');
	const { createDraftSafeSelector } = require('statemanager/redux/toolkit');

	const usersSelector = usersAdapter.getSelectors((state) => state[sliceName]);

	const selectNonExistentUsersByIds = createDraftSafeSelector(
		[usersSelector.selectEntities, (state, userIds) => userIds],
		(users, userIds) => {
			return userIds.filter((id) => users[id] === undefined);
		},
	);

	const {
		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,
	} = usersSelector;

	module.exports = {
		usersSelector,
		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,
		selectNonExistentUsersByIds,
	};
});
