/**
 * @module testing/tests/redux/optimistic-ui/playground-slice-users/src/selector
 */
jn.define('testing/tests/redux/optimistic-ui/playground-slice-users/src/selector', (require, exports, module) => {
	const { sliceName, usersAdapter } = require('testing/tests/redux/optimistic-ui/playground-slice-users/meta');
	const { createDraftSafeSelector } = require('statemanager/redux/toolkit');

	const usersSelector = usersAdapter.getSelectors((state) => state[sliceName]);

	const selectCustomData = createDraftSafeSelector(
		(state) => state[sliceName],
		(slice) => {
			const result = {};
			Object.keys(slice).forEach((key) => {
				if (key !== 'ids' && key !== 'entities')
				{
					result[key] = slice[key];
				}
			});

			return result;
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
		selectCustomData,
	};
});
