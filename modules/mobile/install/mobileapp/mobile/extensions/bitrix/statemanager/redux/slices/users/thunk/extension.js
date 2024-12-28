/**
 * @module statemanager/redux/slices/users/thunk
 */
jn.define('statemanager/redux/slices/users/thunk', (require, exports, module) => {
	const { createAsyncThunk } = require('statemanager/redux/toolkit');
	const { sliceName } = require('statemanager/redux/slices/users/meta');

	/**
	 * @function updateUserThunk
	 * @param {Object} data
	 * @param {string} data.ID
	 * @param {string} data.NAME
	 * @param {string} data.LAST_NAME
	 * @param {string} data.SECOND_NAME
	 * @param {string} data.EMAIL
	 * @param {string} data.PERSONAL_MOBILE
	 * @param {string} data.PERSONAL_BIRTHDAY
	 * @param {string} data.WORK_POSITION
	 * @param {string} data.PERSONAL_GENDER
	 * @param {string} data.PERSONAL_WWW
	 * @param {string} data.WORK_PHONE
	 * @param {string} data.UF_PHONE_INNER
	 * @param {string} data.UF_SKYPE
	 * @param {string} data.UF_TWITTER
	 * @param {string} data.UF_FACEBOOK
	 * @param {string} data.UF_LINKEDIN
	 * @param {string} data.UF_XING
	 */
	const updateUserThunk = createAsyncThunk(
		`${sliceName}/updateUser`,
		async ({ data }, { rejectWithValue }) => {
			try
			{
				const response = await BX.rest.callMethod('mobile.user.update', data);
				if (!response?.answer?.result)
				{
					return rejectWithValue({
						error: response?.answer?.error,
						error_description: response?.answer?.error_description,
					});
				}

				return {
					isSuccess: response.answer.result,
					data: response.query.data,
				};
			}
			catch (response)
			{
				return rejectWithValue({
					error: response?.answer?.error,
					error_description: response?.answer?.error_description,
				});
			}
		},
	);

	module.exports = { updateUserThunk };
});
