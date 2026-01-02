/**
 * @module settings-v2/services/native
 */
jn.define('settings-v2/services/native', (require, exports, module) => {
	const { NativeLocService } = require('settings-v2/services/native/src/loc');
	const { NativeDebugService } = require('settings-v2/services/native/src/debug');
	const { NativeCacheService } = require('settings-v2/services/native/src/cache');

	module.exports = {
		NativeLocService,
		NativeDebugService,
		NativeCacheService,
	};
});
