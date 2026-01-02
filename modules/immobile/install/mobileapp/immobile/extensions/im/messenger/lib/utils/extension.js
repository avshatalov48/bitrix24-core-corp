/**
 * @module im/messenger/lib/utils
 */
jn.define('im/messenger/lib/utils', (require, exports, module) => {
	const { UserUtils } = require('im/messenger/lib/utils/user');
	const { DateUtils } = require('im/messenger/lib/utils/date');
	const { ObjectUtils } = require('im/messenger/lib/utils/object');
	const { ColorUtils } = require('im/messenger/lib/utils/color');
	const { emojiRegex } = require('im/messenger/lib/utils/emoji-regex');
	const { createPromiseWithResolvers } = require('im/messenger/lib/utils/promise');
	const { AsyncQueue } = require('im/messenger/lib/utils/src/async-queue');
	const { ModelUtils } = require('im/messenger/lib/utils/model');
	const { Normalizer } = require('im/messenger/lib/utils/normalizer');

	module.exports = {
		UserUtils,
		DateUtils,
		ObjectUtils,
		ColorUtils,
		ModelUtils,
		emojiRegex,
		AsyncQueue,
		createPromiseWithResolvers,
		Normalizer,
	};
});
