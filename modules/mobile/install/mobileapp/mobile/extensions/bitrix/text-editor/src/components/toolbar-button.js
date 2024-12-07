/**
 * @module text-editor/components/toolbar-button
 */
jn.define('text-editor/components/toolbar-button', (require, exports, module) => {
	const { Color } = require('tokens');

	class ToolbarButton extends LayoutComponent
	{
		render()
		{
			return ImageButton({
				iconName: this.props.icon.getIconName(),
				style: {
					width: 30,
					height: 52,
					...this.props.style,
				},
				tintColor: this.props.active ? Color.base3.toHex() : Color.base5.toHex(),
				onClick: this.props.onClick,
			});
		}
	}

	module.exports = {
		ToolbarButton: (props) => new ToolbarButton(props),
	};
});
