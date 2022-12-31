/**
 * @module communication/connection/item
 */
jn.define('communication/connection/item', (require, exports, module) => {

	const { ConnectionSvg } = require('assets/communication/connection');

	const ICON_COLOR = {
		ENABLED: '#E6F6FD',
		DISABLED: '#F6F7F7',
	};
	const ICON_SIZE = 18;

	const connectionItem = ({ enabled, horizontal, connectionMenu }) => {

		const svgContent = ConnectionSvg['phone'];
		const iconColor = enabled
			? ICON_COLOR.ENABLED
			: ICON_COLOR.DISABLED;

		return View(
			{
				style: {
					padding: 12,
					backgroundColor: enabled && iconColor,
					borderRadius: 20,
					alignItems: 'center',
					justifyContent: 'center',
					width: horizontal ? 36 : 42,
					height: horizontal ? 36 : 42,
					marginBottom: horizontal ? 0 : 13,
				},
				onClick: () => {
					if (enabled && connectionMenu)
					{
						connectionMenu.show();
					}
				},
			},
			Image({
				style: {
					width: ICON_SIZE,
					height: ICON_SIZE,
				},
				svg: {
					content: svgContent(enabled),
				},
			}),
		);
	};

	module.exports = { connectionItem };
});