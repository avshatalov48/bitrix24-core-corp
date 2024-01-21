/**
 * @module layout/ui/button-list
 */
jn.define('layout/ui/button-list', (require, exports, module) => {

	const { PillButton } = require('layout/ui/button-list/pill-button');
	const { SlidingButtonList } = require('layout/ui/button-list/sliding-button-list');

	module.exports = {
		SlidingButtonList,
		PillButton,
	};
});
