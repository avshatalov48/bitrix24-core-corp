/**
 * @module intranet/invite-new/src/name-checker-department-chooser
 */
jn.define('intranet/invite-new/src/name-checker-department-chooser', (require, exports, module) => {
	const { DepartmentChooser } = require('intranet/invite-new/src/department-chooser');
	const { Indent } = require('tokens');
	const { Area } = require('ui-system/layout/area');
	const { createTestIdGenerator } = require('utils/test');

	/**
	 * @typedef {Object} NameCheckerDepartmentChooserProps
	 * @property {string} department
	 * @property {Function} onChange
	 * @property {Object} layout

	 * @class NameCheckerDepartmentChooser
	 */
	class NameCheckerDepartmentChooser extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.getTestId = createTestIdGenerator({
				prefix: 'name-checker-department-chooser',
			});
			this.#initState(props);
		}

		#initState(props)
		{
			this.state = {
				department: props.department ?? null,
			};
		}

		render()
		{
			const { layout } = this.props;
			const { department } = this.state;

			return Area(
				{
					excludePaddingSide: {
						bottom: true,
					},
					style: {
						marginTop: Indent.S.toNumber(),
						marginBottom: Indent.S.toNumber(),
						alignItems: 'center',
					},
				},
				DepartmentChooser({
					testId: this.getTestId(),
					layout,
					department,
					selectedDepartmentChanged: this.selectedDepartmentChanged,
				}),
			);
		}

		selectedDepartmentChanged = (department) => {
			const { onChange } = this.props;
			this.setState({
				department,
			}, () => {
				onChange?.(department);
			});
		};
	}

	module.exports = {
		/**
		 * @param {NameCheckerDepartmentChooserProps} props
		 * @returns {NameCheckerDepartmentChooser}
		 */
		NameCheckerDepartmentChooser: (props) => new NameCheckerDepartmentChooser(props),
	};
});
