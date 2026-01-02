/**
 * @module statemanager/redux/slices/users/thunk
 */
jn.define('statemanager/redux/slices/users/thunk', (require, exports, module) => {
	const { createAsyncThunk } = require('statemanager/redux/toolkit');
	const { sliceName } = require('statemanager/redux/slices/users/meta');
	const { selectNonExistentUsersByIds } = require('statemanager/redux/slices/users/selector');

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

	/**
	 * @function fetchUsersIfNotLoaded
	 * @param {Array} userIds
	 */
	const fetchUsersIfNotLoaded = createAsyncThunk(
		`${sliceName}/fetchUsersIfNotLoaded`,
		async ({ userIds }, { rejectWithValue, getState }) => {
			try
			{
				const usersToLoad = selectNonExistentUsersByIds(getState(), userIds);

				if (usersToLoad.length === 0)
				{
					return {
						isSuccess: false,
						data: [],
					};
				}

				return await fetchUsers(usersToLoad, rejectWithValue);
			}
			catch (response)
			{
				return rejectWithValue({
					error: response?.error,
				});
			}
		},
	);

	/**
	 * @function fetchUsers
	 * @param {Array} userIds
	 * @param {Function} rejectWithValue
	 */
	const fetchUsers = async (userIds, rejectWithValue) => {
		try
		{
			const params = {
				filter: {
					ID: userIds,
				},
			};

			const response = await BX.rest.callMethod('mobile.user.get', params);

			if (!response?.answer?.result)
			{
				return rejectWithValue({
					error: response?.answer?.error,
					error_description: response?.answer?.error_description,
				});
			}

			return {
				isSuccess: response.status === 200,
				data: response.answer.result,
			};
		}
		catch (response)
		{
			return rejectWithValue({
				error: response?.answer?.error,
				error_description: response?.answer?.error_description,
			});
		}
	};

	const updateProfilePhoto = createAsyncThunk(
		`${sliceName}/updateUserAvatar`,
		async ({ userId, image, base64 }, { rejectWithValue }) => {
			try
			{
				if (!image || !base64)
				{
					return rejectWithValue({
						error: 'Invalid image data',
						error_description: 'Image or base64 data is not provided',
					});
				}

				const data = {
					id: userId,
					PERSONAL_PHOTO: ['avatar.png', base64],
				};

				// todo replace with BX.runActionExecutor()
				const response = await BX.rest.callMethod('user.update', data);
				if (!response?.answer?.result)
				{
					return rejectWithValue({
						error: response?.answer?.error,
						errorDescription: response?.answer?.error_description,
					});
				}

				return {
					image,
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

	module.exports = {
		updateUserThunk,
		fetchUsersIfNotLoaded,
		updateProfilePhoto,
	};
});
