/**
 * @module im/messenger/lib/core
 */
jn.define('im/messenger/lib/core', (require, exports, module) => {

	const { Type } = jn.require('im/messenger/lib/core/type');
	const { Loc } = jn.require('im/messenger/lib/core/loc');
	const { Runtime } = jn.require('im/messenger/lib/core/runtime');

	module.exports = {
		Type,
		Loc,
		Runtime,
	};
});
