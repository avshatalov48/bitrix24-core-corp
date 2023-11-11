/**
 * @module im/messenger/lib/utils
 */
jn.define('im/messenger/lib/utils', (require, exports, module) => {

	const { UserUtils } = require('im/messenger/lib/utils/user');
	const { DateUtils } = require('im/messenger/lib/utils/date');
	const { ObjectUtils } = require('im/messenger/lib/utils/object');
	const { ColorUtils } = require('im/messenger/lib/utils/color');

	module.exports = { UserUtils, DateUtils, ObjectUtils, ColorUtils };
});