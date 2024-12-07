/**
 * @module layout/ui/context-menu/buttons/src/cancel
 */
jn.define('layout/ui/context-menu/buttons/src/cancel', (require, exports, module) => {
	const { Loc } = require('loc');
	const { ContextMenuItemType } = require('layout/ui/context-menu/item');

	/**
	 * @deprecated
	 * @function cancelConfig
	 */
	const cancelConfig = (props = {}) => ({
		...props,
		id: ContextMenuItemType.CANCEL.getTypeName(),
		type: ContextMenuItemType.CANCEL,
		active: true,
		showActionLoader: false,
		title: Loc.getMessage('CONTEXT_MENU_CANCEL'),
	});

	module.exports = { cancelConfig };
});
