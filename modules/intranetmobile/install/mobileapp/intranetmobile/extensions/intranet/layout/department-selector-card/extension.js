/**
 * @module intranet/layout/department-selector-card
 */

jn.define('intranet/layout/department-selector-card', (require, exports, module) => {
	const { Loc } = require('loc');
	const { DepartmentSelector } = require('selector/widget/entity/intranet/department');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { InputDesign, InputSize, InputMode, Input } = require('ui-system/form/inputs/input');

	class DepartmentSelectorCard extends LayoutComponent
	{
		/**
		 * @param {Object} props
		 * @param {Object} [props.department]
		 * @param {Number} [props.department.id]
		 * @param {String} [props.department.title]
		 * @param {String} [props.title]
		 * @param {Object} [props.parentWidget]
		 * @param {Function} [props.onViewHidden]
		 * @param {Function} [props.onChange]
		 * @param {Function} [props.onLoadRootDepartment]
		 * @param {Object} [props.departmentProviderOption]
		 */
		constructor(props) {
			super(props);

			this.parentWidget = props.parentWidget ?? PageManager;
			this.defaultDepartment = null;

			this.state = {
				selectedDepartment: this.getDefaultDepartment(),
			};
		}

		componentDidMount()
		{
			super.componentDidMount();
			this.fetchDefaultDepartment();
		}

		getDefaultDepartment()
		{
			if (this.defaultDepartment)
			{
				return this.defaultDepartment;
			}

			return null;
		}

		fetchDefaultDepartment()
		{
			(new RunActionExecutor('intranetmobile.departments.getRootDepartment', {})
				.setCacheId('root-department')
				.setCacheHandler((response) => this.onLoadRootDepartment(response))
				.setHandler((response) => this.onLoadRootDepartment(response))
				.setCacheTtl(3600)
			).call(true);
		}

		onLoadRootDepartment(result)
		{
			if (result.status !== 'success')
			{
				return;
			}

			this.defaultDepartment = {
				id: result.data?.id,
				title: result.data?.name,
			};

			if (!this.state.selectedDepartment)
			{
				this.setState({ selectedDepartment: this.defaultDepartment });
				this.props.onLoadRootDepartment(this.defaultDepartment?.id);
			}
		}

		render()
		{
			return Input({
				value: this.state.selectedDepartment?.title ?? '',
				label: this.props.title ?? Loc.getMessage('M_INTRANET_DEPARTMENT_SELECTOR_CARD_DEFAULT_TITLE'),
				size: InputSize.L,
				design: InputDesign.PRIMARY,
				mode: InputMode.NAKED,
				align: 'left',
				dropdown: true,
				onDropdown: this.openDepartmentSelector,
				onFocus: this.openDepartmentSelector,
			});
		}

		openDepartmentSelector = () => {
			DepartmentSelector.make({
				provider: {
					options: this.props.departmentProviderOption ?? {},
				},
				initSelectedIds: this.state.selectedDepartment?.id ? [this.state.selectedDepartment.id] : null,
				widgetParams: {
					backdrop: {
						mediumPositionPercent: 70,
						horizontalSwipeAllowed: false,
					},
				},
				allowMultipleSelection: false,
				closeOnSelect: true,
				events: {
					onClose: this.onChange,
					onViewHidden: this.onViewHidden,
				},
			}).show({}, this.parentWidget ?? layout);
		};

		onViewHidden = () => {
			this.props.onViewHidden();
		};

		onChange = (departments) => {
			this.setState({
				selectedDepartment: departments.pop() ?? this.getDefaultDepartment(),
			}, () => {
				this.props.onChange?.(this.state.selectedDepartment.id);
			});
		};
	}

	DepartmentSelectorCard.propTypes = {
		title: PropTypes.string,
		parentWidget: PropTypes.object,
		departmentProviderOptions: PropTypes.object,
		onViewHidden: PropTypes.func,
		onChange: PropTypes.func,
	};

	module.exports = { DepartmentSelectorCard };
});
