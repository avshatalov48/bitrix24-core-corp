/**
 * @module layout/ui/context-menu/button
 */
jn.define('layout/ui/context-menu/button', (require, exports, module) => {
	const { cancelConfig } = require('layout/ui/context-menu/buttons/cancel');
	const { ContextMenuItem } = require('layout/ui/context-menu/item');

	/**
	 * @param props
	 * @param {string} props.type
	 * @param {string} props.title
	 * @param {string} props.subtitle
	 * @param {string} props.showArrow
	 * @param {boolean} props.radius
	 * @param {boolean} props.divider
	 * @param {object} props.style
	 * @param {function} props.onClickCallback
	 * @return {ContextMenuItem}
	 */
	const menuButton = (props = {}) => {
		const { type, style = {}, divider = false, radius = true, ...restProps } = props;
		const isCancel = type === ContextMenuItem.getTypeCancelName();

		let config = {
			...restProps,
			isActive: true,
			type: ContextMenuItem.getTypeButtonName(),
			firstInSection: !divider,
			lastInSection: !divider,
			needProcessing: false,
			data: {
				style,
			},
		};

		if (isCancel)
		{
			config = cancelConfig(config);
		}

		return View({
				style: {
					width: '100%',
					borderRadius: radius ? 12 : 0,
				},
			},
			new ContextMenuItem(config),
		);
	};

	module.exports = { menuButton, cancelConfig };
});