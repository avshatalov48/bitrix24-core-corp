/**
 * @module intranet/user-list/src/department-button
 */
jn.define('intranet/user-list/src/department-button', (require, exports, module) => {
	const { DepartmentSelector } = require('selector/widget/entity/intranet/department');
	const { Text3 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Color, Corner, Component } = require('tokens');
	const { Loc } = require('loc');

	/**
	 * @class DepartmentButton
	 * @extends LayoutComponent
	 */
	class DepartmentButton extends LayoutComponent
	{
		getEmptyDepartment()
		{
			return {
				id: 0,
				title: Loc.getMessage('MOBILE_USERS_FILTER_DEFAULT_DEPARTMENT_TITLE'),
			};
		}

		/**
		 * @param {Object} props
		 * @param {Object} [props.department]
		 * @param {number} [props.department.id]
		 * @param {string} [props.department.title]
		 * @param {Object} props.providerOptions
		 * @param {Function} [props.onSelect]
		 * @param {Object} props.layout
		 */
		constructor(props)
		{
			super(props);

			this.selector = null;
			this.state.department = props.department;
		}

		render()
		{
			// todo replace this when the component stageSelector is ready
			return View(
				{
					style: {
						flex: 1,
						height: 52,
						width: '100%',
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'space-between',
						backgroundColor: Color.bgContentSecondary.toHex(),
						borderColor: Color.bgSeparatorPrimary.toHex(),
						paddingHorizontal: Component.cardPaddingLr.toNumber(),
						paddingVertical: Component.cardPaddingB.toNumber(),
						borderWidth: 1,
						borderRadius: Corner.L.toNumber(),
					},
					onClick: this.open,
					testId: 'openDepartmentSelector',
				},
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'row',
						},
					},
					IconView(
						{
							icon: Icon.THREE_PERSONS,
							size: 28,
							color: Color.base2,
							style: {
								marginRight: Component.cardCorner.toNumber(),
							},
						},
					),
					Text3(
						{
							text: this.state.department.title,
							color: Color.base1,
						},
					),
				),
				IconView(
					{
						icon: Icon.CHEVRON_DOWN,
						color: Color.base4,
						size: 24,
					},
				),
			);
		}

		open = () => {
			this.selector = DepartmentSelector.make({
				provider: {
					options: this.props.providerOptions || {},
				},
				initSelectedIds: this.state.department?.id ? [this.state.department.id] : null,
				widgetParams: {
					backdrop: {
						mediumPositionPercent: 70,
						horizontalSwipeAllowed: false,
					},
				},
				allowMultipleSelection: false,
				closeOnSelect: true,
				events: {
					onClose: (departments) => {
						this.setState({ department: departments.pop() || this.getEmptyDepartment() }, () => {
							if (this.props.onSelect)
							{
								this.props.onSelect(this.state.department);
							}
						});
					},
				},
			});

			this.selector.show({}, this.props.layout || layout);
		};
	}

	module.exports = { DepartmentButton };
});
