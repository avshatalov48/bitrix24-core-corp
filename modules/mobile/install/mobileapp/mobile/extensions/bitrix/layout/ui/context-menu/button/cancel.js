/**
 * @module layout/ui/context-menu/buttons/cancel
 */
jn.define('layout/ui/context-menu/buttons/cancel', (require, exports, module) => {
	const { Loc } = require('loc');
	const { ContextMenuItem } = require('layout/ui/context-menu/item');

	/**
	 * @function cancelConfig
	 */
	const cancelConfig = (props = {}) => {
		const { onClickCallback } = props;
		const type = ContextMenuItem.getTypeCancelName();

		return {
			...props,
			id: type,
			type,
			isActive: true,
			largeIcon: false,
			showActionLoader: false,
			title: Loc.getMessage('CONTEXT_MENU_CANCEL'),
			data: {
				svgIcon: '<svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.7562 8.54581L16.4267 14.2163L14.2165 16.4266L8.54596 10.7561L2.87545 16.4266L0.665192 14.2163L6.3357 8.54581L0.665192 2.87529L2.87545 0.665039L8.54596 6.33555L14.2165 0.665039L16.4267 2.87529L10.7562 8.54581Z" fill="#A8ADB4"/></svg>',
			},
			onClickCallback,
		};
	};

	module.exports = { cancelConfig };
});
