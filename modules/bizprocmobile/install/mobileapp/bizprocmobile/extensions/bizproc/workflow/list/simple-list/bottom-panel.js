/**
 * @module bizproc/workflow/list/simple-list/bottom-panel
 */
jn.define('bizproc/workflow/list/simple-list/bottom-panel', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Type } = require('type');
	const { PureComponent } = require('layout/pure-component');
	const { BottomToolbar } = require('layout/ui/bottom-toolbar');

	class BottomPanel extends PureComponent
	{
		/**
		 * @param {{}} props
		 * @param {[]} props.items
		 * @param {Function} props.renderContent
		 */
		constructor(props)
		{
			super(props);

			this.state = {
				items: Type.isArrayFilled(props.items) ? props.items : [],
			};
		}

		componentWillReceiveProps(props)
		{
			this.setState({ items: Type.isArrayFilled(props.items) ? props.items : [] });
		}

		render()
		{
			return new BottomToolbar({
				style: {
					borderRadius: 0,
					backgroundColor: AppTheme.colors.bgSecondary,
					paddingLeft: 18,
					paddingRight: 18,
					paddingBottom: 12,
				},
				renderContent: this.renderContent.bind(this),
			});
		}

		renderContent()
		{
			const content = BX.prop.getFunction(this.props, 'renderContent', () => null);

			return content(this.state.items);
		}
	}

	module.exports = { BottomPanel };
});
