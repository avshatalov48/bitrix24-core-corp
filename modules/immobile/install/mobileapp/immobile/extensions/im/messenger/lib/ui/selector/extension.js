/**
 * @module im/messenger/lib/ui/selector
 */
jn.define('im/messenger/lib/ui/selector', (require, exports, module) => {

	const { SingleSelector } = require('im/messenger/lib/ui/selector/single-selector');
	const { MultiSelector } = require('im/messenger/lib/ui/selector/multi-selector');

	module.exports = { SingleSelector, MultiSelector };
});