/**
 * @module user-profile/common-tab/src/block/department/view
 */
jn.define('user-profile/common-tab/src/block/department/view', (require, exports, module) => {
	const { createTestIdGenerator } = require('utils/test');
	const { Component } = require('tokens');
	const { isModuleInstalled } = require('module');
	const { Loc } = require('loc');

	let UserDepartmentSlider = null;
	if (isModuleInstalled('intranet'))
	{
		UserDepartmentSlider = require('intranet/user-department-slider').UserDepartmentSlider;
	}

	/**
	 * @typedef {Object} DepartmentProps
	 * @property {string} [testId]
	 * @property {number} [ownerId]
	 * @property {number[][]} [hierarchies]
	 * @property {bool} canInviteUsers
	 * @property {bool} canUseTelephony

	 * @class Department
	 */
	class Department extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.getTestId = createTestIdGenerator({
				prefix: 'department-block',
				context: this,
			});
		}

		render()
		{
			if (!UserDepartmentSlider)
			{
				return null;
			}
			const { hierarchies, ownerId } = this.props;

			return UserDepartmentSlider({
				hierarchies,
				userId: ownerId,
				sliderWidth: device.screen.width - 2 * Component.areaPaddingLr,
				chevron: true,
				onClick: this.onClick,
			});
		}

		onClick = () => {
			const { parentWidget, canInviteUsers, canUseTelephony } = this.props;
			const titleText = env.extranet
				? Loc.getMessage('M_PROFILE_DEPARTMENT_CONTACTS_BOX_TITLE')
				: Loc.getMessage('M_PROFILE_DEPARTMENT_EMPLOYEE_BOX_TITLE');

			PageManager.openComponent('JSStackComponent', {
				// eslint-disable-next-line no-undef
				scriptPath: availableComponents['intranet:user.list'].publicUrl,
				componentCode: 'intranet.user.list',
				params: {
					canInvite: canInviteUsers,
					canUseTelephony: canUseTelephony ? 'Y' : 'N',
				},
				rootWidget: {
					name: 'layout',
					componentCode: 'users',
					settings: {
						objectName: 'layout',
						titleParams: {
							text: titleText,
							type: 'section',
						},
					},
				},
			}, parentWidget);
		};
	}

	module.exports = {
		/**
		 * @property {DepartmentProps} props
		 * @returns {Department}
		 */
		Department: (props) => new Department(props),
	};
});
