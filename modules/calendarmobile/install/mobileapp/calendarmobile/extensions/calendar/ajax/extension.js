/**
 * @module calendar/ajax
 */
jn.define('calendar/ajax', (require, exports, module) => {
	const { SharingAjax, SharingActions } = require('calendar/ajax/sharing');

	module.exports = {
		SharingAjax,
		SharingActions,
	};

});
