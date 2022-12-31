/**
 * @module layout/ui/detail-card/toolbar/toolbar-padding
 */

jn.define('layout/ui/detail-card/toolbar/toolbar-padding', (require, exports, module) => {

	const { get } = require('utils/object');

	const HEIGHT_TOOL_PANEL = 45;

	/**
	 * @class ToolbarPadding
	 */
	class ToolbarPadding extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.ref = null;

			this.visible = false;
			this.height = props.height || HEIGHT_TOOL_PANEL;
		}

		show(animate = true)
		{
			if (this.visible)
			{
				return Promise.resolve();
			}

			return this.animateToolbar(true, animate);
		}

		hide(animate = true)
		{
			if (!this.visible)
			{
				return Promise.resolve();
			}

			return this.animateToolbar(false, animate);
		}

		/**
		 * @private
		 */
		animateToolbar(open, animate = true)
		{
			if (!animate)
			{
				this.visible = open;

				return new Promise((resolve) => this.setState({}, resolve));
			}

			const { animation = {} } = this.props;
			const height = open ? this.height : 0;

			return new Promise((resolve) => {
				this.ref.animate(
					{
						...animation,
						height,
					},
					() => {
						this.visible = open;
						resolve();
					},
				);
			});
		}

		getSafeAreaBottomHeight()
		{
			if (Application.getPlatform() === 'android')
			{
				return 0;
			}

			return get(device.screen, ['safeArea', 'bottom'], 0);
		}

		render()
		{
			const { content } = this.props;

			let height = 0;

			if (this.visible)
			{
				height = BX.prop.getNumber(this.props, 'height', HEIGHT_TOOL_PANEL);
				height += this.getSafeAreaBottomHeight();
			}

			return View({
					ref: (ref) => this.ref = ref,
					style: {
						height,
						display: 'flex',
					},
				},
				content,
			);
		}
	}

	module.exports = { ToolbarPadding };
});