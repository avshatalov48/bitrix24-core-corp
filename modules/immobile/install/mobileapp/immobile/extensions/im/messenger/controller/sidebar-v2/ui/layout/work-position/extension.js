/**
 * @module im/messenger/controller/sidebar-v2/ui/layout/work-position
 */
jn.define('im/messenger/controller/sidebar-v2/ui/layout/work-position', (require, exports, module) => {
	const { Text5 } = require('ui-system/typography');
	const { Color } = require('tokens');
	const { Line } = require('utils/skeleton');
	const { stringify } = require('utils/string');
	const { Ellipsize } = require('utils/enums/style/src/ellipsize');
	const { Type } = require('type');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class SidebarWorkPosition
	 */
	class SidebarWorkPosition extends LayoutComponent
	{
		/**
		 * @param {object} props
		 * @param {string} props.userId
		 */
		constructor(props)
		{
			super(props);

			this.userId = props.userId;

			this.store = serviceLocator.get('core').getStore();
			this.storeManager = serviceLocator.get('core').getStoreManager();

			const departmentName = this.getDepartmentNameFromStore();

			this.state = {
				departmentName,
				pending: (departmentName === ''),
			};
		}

		/**
		 * @private
		 * @return {string}
		 */
		getDepartmentNameFromStore()
		{
			const userData = this.store.getters['usersModel/getById'](this.userId);
			if (Type.isArrayFilled(userData?.departments))
			{
				return stringify(userData?.departmentName);
			}

			return '';
		}

		componentDidMount()
		{
			this.storeManager.on('usersModel/set', this.onUserUpdate);
		}

		componentWillUnmount()
		{
			this.storeManager.off('usersModel/set', this.onUserUpdate);
		}

		onUserUpdate = () => {
			const departmentName = this.getDepartmentNameFromStore();
			if (this.state.pending)
			{
				this.setState({ departmentName, pending: false });
			}
			else if (departmentName !== this.state.departmentName && departmentName !== '')
			{
				this.setState({ departmentName });
			}
		};

		render()
		{
			return View(
				{
					testId: this.state.pending ? `${this.props.testId}-container-pending` : `${this.props.testId}-container-ready`,
					style: {
						flex: 1,
					},
				},
				this.state.pending ? Line('100%', 16) : this.renderDepartment(),
			);
		}

		renderDepartment()
		{
			return Text5({
				testId: `${this.props.testId}-value`,
				text: this.state.departmentName,
				color: Color.base4,
				ellipsize: Ellipsize.END.toString(),
				numberOfLines: 1,
			});
		}
	}

	module.exports = {
		SidebarWorkPosition: (props) => new SidebarWorkPosition(props),
	};
});
