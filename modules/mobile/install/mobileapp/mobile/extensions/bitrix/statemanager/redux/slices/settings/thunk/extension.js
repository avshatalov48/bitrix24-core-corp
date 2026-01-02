/**
 * @module statemanager/redux/slices/settings/thunk
 */
jn.define('statemanager/redux/slices/settings/thunk', (require, exports, module) => {
	const { settingUpserted } = require('statemanager/redux/slices/settings');
	const { dispatch } = require('statemanager/redux/store');

	const fetchIsCollabToolEnabled = () => async () => {
		try
		{
			const response = await BX.ajax.runAction('mobile.Collab.isCollabToolEnabled', { json: {} });

			dispatch(
				settingUpserted({
					id: 'collab_tool_enabled',
					value: response?.data?.isCollabToolEnabled,
				}),
			);
		}
		catch (error)
		{
			console.error('Failed to load collab settings:', error);
		}
	};

	module.exports = {
		fetchIsCollabToolEnabled,
	};
});
