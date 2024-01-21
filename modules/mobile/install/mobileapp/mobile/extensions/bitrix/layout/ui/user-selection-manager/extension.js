/**
 * @module layout/ui/user-selection-manager
 */
jn.define('layout/ui/user-selection-manager', (require, exports, module) => {

	const { UserSelectedList } = require('layout/ui/user-selection-manager/src/user-selected-list');
	const { UserSelectionManager } = require('layout/ui/user-selection-manager/src/selection-manager');

	module.exports = { UserSelectionManager, UserSelectedList };
});
