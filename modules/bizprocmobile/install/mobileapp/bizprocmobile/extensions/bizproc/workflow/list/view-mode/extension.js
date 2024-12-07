/**
 * @module bizproc/workflow/list/view-mode
 */
jn.define('bizproc/workflow/list/view-mode', (require, exports, module) => {
	const ViewMode = Object.freeze({
		REGULAR: 'regular',
		MULTISELECT: 'multiselect',
	});

	module.exports = { ViewMode };
});
