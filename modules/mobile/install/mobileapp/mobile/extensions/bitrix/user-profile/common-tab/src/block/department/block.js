/**
 * @module user-profile/common-tab/src/block/department/block
 */
jn.define('user-profile/common-tab/src/block/department/block', (require, exports, module) => {
	const { BaseBlock } = require('user-profile/common-tab/src/block/base-block');
	const { Department } = require('user-profile/common-tab/src/block/department/view');
	const { Component } = require('tokens');
	const { Loc } = require('loc');
	const { usersUpserted } = require('statemanager/redux/slices/users');
	const { dispatch } = require('statemanager/redux/store');
	const { Type } = require('type');
	const { departmentsUpserted, DepartmentDataConverter } = require('intranet/statemanager/redux/slices/department');

	class DepartmentBlock extends BaseBlock
	{
		prepareProps(commonTabData)
		{
			const {
				ownerId,
				isEditMode,
				departments: departmentsData,
				parentWidget,
			} = commonTabData ?? {};
			const { departmentHierarchies, canInviteUsers, canUseTelephony } = departmentsData;

			const {
				users,
				departments,
			} = DepartmentDataConverter.getPreparedReduxDataFromDepartmentHierarchies(departmentHierarchies);
			if (Type.isArrayFilled(users))
			{
				dispatch(usersUpserted(users));
			}

			if (Type.isArrayFilled(departments))
			{
				dispatch(departmentsUpserted(departments));
			}

			const hierarchies = DepartmentDataConverter.getPreparedHierarchies(departmentHierarchies);

			return {
				ownerId,
				isEditMode,
				hierarchies,
				departments: departmentHierarchies,
				canInviteUsers,
				canUseTelephony,
				parentWidget,
				...this.getWrapperOptions(),
			};
		}

		getTitle()
		{
			return Loc.getMessage('M_PROFILE_DEPARTMENTS_TITLE');
		}

		getContentClass()
		{
			return Department;
		}

		isAvailable()
		{
			const { hierarchies, isEditMode } = this.props;

			return !isEditMode && Type.isArrayFilled(hierarchies);
		}

		getWrapperOptions()
		{
			return {
				testId: 'department-card',
				cardProps: {
					excludePaddingSide: {
						all: true,
					},
				},
				titleStyle: {
					marginHorizontal: Component.cardPaddingLr.toNumber(),
					marginTop: Component.cardPaddingT.toNumber(),
					marginBottom: 0,
				},
			};
		}
	}

	module.exports = {
		DepartmentBlock,
	};
});
