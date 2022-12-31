/**
 * @module layout/ui/simple-list/view-mode
 */
jn.define('layout/ui/simple-list/view-mode', (require, exports, module) => {

	/**
	 * @class ViewMode
	 */
	const ViewMode = {
		list: 'list',
		empty: 'empty',
		loading: 'loading',
		forbidden: 'forbidden',
	};

	module.exports = { ViewMode };
});
