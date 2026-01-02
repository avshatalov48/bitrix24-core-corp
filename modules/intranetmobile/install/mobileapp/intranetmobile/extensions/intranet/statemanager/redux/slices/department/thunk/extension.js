/**
 * @module intranet/statemanager/redux/slices/department/thunk
 */
jn.define('intranet/statemanager/redux/slices/department/thunk', (require, exports, module) => {
	const { sliceName } = require('intranet/statemanager/redux/slices/department/meta');
	const { createAsyncThunk } = require('statemanager/redux/toolkit');
	const { DepartmentDataConverter } = require('intranet/statemanager/redux/slices/department/model/department');
	const { isOnline } = require('device/connection');

	const condition = () => isOnline();

	const fetchParentDepartmentsThunk = createAsyncThunk(
		`${sliceName}/fetchParentDepartments`,
		async ({ departmentId }, { rejectWithValue }) => {
			try
			{
				const response = await BX.ajax.runAction('intranetmobile.department.getParents', {
					data: { departmentId },
				})
					.catch(console.error);

				const { status, data, errors } = response;
				if (status === 'error')
				{
					console.error(errors);

					return rejectWithValue({
						isSuccess: false,
						errors,
					});
				}

				const {
					departments = [],
					heads = [],
					employeeCounts = [],
				} = data;

				const departmentHierarchies = {
					[departmentId]: {
						departments,
						heads,
						employeeCounts,
					},
				};
				const {
					users: preparedUsers,
					departments: preparedDepartments,
				} = DepartmentDataConverter.getPreparedReduxDataFromDepartmentHierarchies(departmentHierarchies);

				const [departmentIds] = DepartmentDataConverter.getPreparedHierarchies(departmentHierarchies);

				return {
					isSuccess: true,
					users: preparedUsers,
					departments: preparedDepartments,
					hierarchy: departmentIds,
				};
			}
			catch (response)
			{
				console.error(response);

				return rejectWithValue({
					isSuccess: false,
					errors: response?.errors,
				});
			}
		},
		{ condition },
	);

	module.exports = {
		fetchParentDepartmentsThunk,
	};
});
