/**
 * @module disk/file-grid/navigation
 */
jn.define('disk/file-grid/navigation', (require, exports, module) => {
	const { FileGridFilter } = require('disk/file-grid/navigation/src/filter');
	const { FileGridMoreMenu } = require('disk/file-grid/navigation/src/more-menu');
	const { FileGridSorting } = require('disk/file-grid/navigation/src/sorting');

	module.exports = {
		FileGridFilter,
		FileGridMoreMenu,
		FileGridSorting,
	};
});
