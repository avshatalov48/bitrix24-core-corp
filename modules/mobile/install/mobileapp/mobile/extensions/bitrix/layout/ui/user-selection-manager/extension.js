/**
 * @module layout/ui/user-selection-manager
 */
jn.define('layout/ui/user-selection-manager', (require, exports, module) => {
	const { UserSection } = require('layout/ui/user-selection-manager/src/user-section');
	const { UserSelectionManager } = require('layout/ui/user-selection-manager/src/selection-manager');

	module.exports = { UserSection, UserSelectionManager };
});
