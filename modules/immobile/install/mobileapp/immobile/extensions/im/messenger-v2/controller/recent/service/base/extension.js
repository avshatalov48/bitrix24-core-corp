/**
 * @module im/messenger-v2/controller/recent/service/base
 */
jn.define('im/messenger-v2/controller/recent/service/base', (require, exports, module) => {
	const { BaseRecentService } = require('im/messenger-v2/controller/recent/service/base/base');
	const { BaseUiRecentService } = require('im/messenger-v2/controller/recent/service/base/base-ui');

	module.exports = {
		BaseRecentService,
		BaseUiRecentService,
	};
});
