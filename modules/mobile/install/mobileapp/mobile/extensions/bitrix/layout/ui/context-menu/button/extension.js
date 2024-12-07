/**
 * @module layout/ui/context-menu/button
 */
jn.define('layout/ui/context-menu/button', (require, exports, module) => {
	const { cancelConfig } = require('layout/ui/context-menu/buttons/src/cancel');
	const { ContextMenuItem, ContextMenuItemType } = require('layout/ui/context-menu/item');

	/**
	 * @deprecated
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
		const { type, testId, style = {}, divider = false, radius = true, ...restProps } = props;
		const isCancel = type === 'cancel';

		let config = {
			...restProps,
			divider,
			active: true,
			type: ContextMenuItemType.BUTTON,
			needProcessing: false,
			data: {
				style,
			},
		};

		if (isCancel)
		{
			config = cancelConfig(config);
		}

		return View(
			{
				testId,
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
