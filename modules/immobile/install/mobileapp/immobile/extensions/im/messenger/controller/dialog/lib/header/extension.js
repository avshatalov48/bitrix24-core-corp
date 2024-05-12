/**
 * @module im/messenger/controller/dialog/lib/header
 */
jn.define('im/messenger/controller/dialog/lib/header', (require, exports, module) => {
	const { HeaderButtons } = require('im/messenger/controller/dialog/lib/header/buttons');
	const { HeaderTitle } = require('im/messenger/controller/dialog/lib/header/title');

	module.exports = { HeaderButtons, HeaderTitle };
});
