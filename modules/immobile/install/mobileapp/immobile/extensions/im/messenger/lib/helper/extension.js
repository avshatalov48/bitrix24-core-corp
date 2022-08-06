/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-bx-message */

/**
 * @module im/messenger/lib/helper
 */
jn.define('im/messenger/lib/helper', (require, exports, module) => {

	const { DialogHelper } = jn.require('im/messenger/lib/helper/dialog');
	const { DateHelper } = jn.require('im/messenger/lib/helper/date');
	const { Worker } = jn.require('im/messenger/lib/helper/worker');
	const { SoftLoader } = jn.require('im/messenger/lib/helper/soft-loader');

	module.exports = {
		DialogHelper: new DialogHelper(),
		DateHelper: new DateHelper(),
		Worker,
		SoftLoader,
	};
});
