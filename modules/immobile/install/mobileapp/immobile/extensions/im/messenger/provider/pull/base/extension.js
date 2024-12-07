/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/provider/pull/base
 */
jn.define('im/messenger/provider/pull/base', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base/pull-handler');
	const { BaseDialogPullHandler } = require('im/messenger/provider/pull/base/dialog');
	const { BaseMessagePullHandler } = require('im/messenger/provider/pull/base/message');

	module.exports = {
		BasePullHandler,
		BaseDialogPullHandler,
		BaseMessagePullHandler,
	};
});
