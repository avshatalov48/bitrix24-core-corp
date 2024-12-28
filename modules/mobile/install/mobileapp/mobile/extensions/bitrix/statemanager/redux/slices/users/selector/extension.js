/**
 * @module statemanager/redux/slices/users/selector
 */
jn.define('statemanager/redux/slices/users/selector', (require, exports, module) => {
	const { sliceName, usersAdapter } = require('statemanager/redux/slices/users/meta');

	const usersSelector = usersAdapter.getSelectors((state) => state[sliceName]);

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
	};
});
