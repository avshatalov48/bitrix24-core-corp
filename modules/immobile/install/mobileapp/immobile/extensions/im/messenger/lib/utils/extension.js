/**
 * @module im/messenger/lib/utils
 */
jn.define('im/messenger/lib/utils', (require, exports, module) => {

	const { UserUtils } = require('im/messenger/lib/utils/user');
	const { DateUtils } = require('im/messenger/lib/utils/date');
	const { ObjectUtils } = require('im/messenger/lib/utils/object');


	module.exports = { UserUtils, DateUtils, ObjectUtils };
});