/**
 * @module assets/icons
 */
jn.define('assets/icons', (require, exports, module) => {
	const outline = require('assets/icons/src/outline');
	const { Icon } = require('assets/icons/src/main');
	const { DiskIcon } = require('assets/icons/src/disk');

	module.exports = { Icon, DiskIcon, outline };
});
