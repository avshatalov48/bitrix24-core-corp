/**
 * @module intranet/statemanager/redux/slices/department/model/department
 */
jn.define('intranet/statemanager/redux/slices/department/model/department', (require, exports, module) => {
	class DepartmentDataConverter
	{
		/**
		 * Method gets entities from department hierarchies data to save into redux store.
		 *
		 * @public
		 * @param {object} departmentHierarchies
		 * @returns {object}
		 */
		static getPreparedReduxDataFromDepartmentHierarchies(departmentHierarchies)
		{
			return {
				departments: DepartmentDataConverter.getDepartmentsFromDepartmentHierarchies(departmentHierarchies),
				users: DepartmentDataConverter.getUsersFromDepartmentHierarchies(departmentHierarchies),
			};
		}

		/**
		 * Method represents hierarchies data only with ids of departments.
		 * @param departmentHierarchies
		 * @returns {*[]}
		 */
		static getPreparedHierarchies(departmentHierarchies)
		{
			return Object.keys(departmentHierarchies).map((departmentId) => {
				return departmentHierarchies[departmentId].departments.map((department) => department.id);
			});
		}

		/**
		 * Method gets departments from department hierarchies data.
		 * @param {object} departmentHierarchies
		 * @returns {IntranetDepartmentReduxModel[]}
		 */
		static getDepartmentsFromDepartmentHierarchies(departmentHierarchies)
		{
			const departments = [];
			Object.keys(departmentHierarchies).forEach((departmentId) => {
				departments.push(...departmentHierarchies[departmentId].departments.map((department) => {
					const {
						accessCode,
						active,
						createdBy,
						description,
						globalActive,
						id,
						name,
						parentId,
						sort,
						structureId,
						type,
					} = department;

					return {
						accessCode,
						active,
						createdBy,
						description,
						globalActive,
						id,
						name,
						parentId,
						sort,
						structureId,
						type,
						employeeCount: departmentHierarchies[departmentId].employeeCounts[id] ?? null,
						headIds: departmentHierarchies[departmentId].heads[id]?.map((user) => Number(user.id)) ?? [],
					};
				}));
			});

			return departments.filter((department, index, self) => {
				return index === self.findIndex((u) => u.id === department.id);
			});
		}

		/**
		 * Method gets users from department hierarchies data.
		 * @param {object} departmentHierarchies
		 * @returns {Object[]}
		 */
		static getUsersFromDepartmentHierarchies(departmentHierarchies)
		{
			const users = [];
			Object.keys(departmentHierarchies).forEach((departmentId) => {
				Object.keys(departmentHierarchies[departmentId].heads).forEach((userId) => {
					users.push(...departmentHierarchies[departmentId].heads[userId]);
				});
			});

			return users.filter((user, index, self) => {
				return index === self.findIndex((u) => u.id === user.id);
			});
		}
	}

	module.exports = { DepartmentDataConverter };
});
