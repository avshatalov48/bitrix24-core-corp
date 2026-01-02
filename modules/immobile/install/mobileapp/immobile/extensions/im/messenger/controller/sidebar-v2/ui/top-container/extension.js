/**
 * @module im/messenger/controller/sidebar-v2/ui/top-container
 */
jn.define('im/messenger/controller/sidebar-v2/ui/top-container', (require, exports, module) => {
	class SidebarTopContainer extends LayoutComponent
	{
		/**
		 * @public
		 */
		refresh()
		{
			this.setState({});
		}

		render()
		{
			return View(
				{
					onLayout: this.props.onLayout,
					style: {
						width: '100%',
					},
				},
				this.props.renderHeader?.(),
				this.props.renderDescription?.(),
				this.props.renderPrimaryActionButtons?.(),
				this.props.renderPlanLimitBanner?.(),
			);
		}
	}

	module.exports = { SidebarTopContainer };
});
