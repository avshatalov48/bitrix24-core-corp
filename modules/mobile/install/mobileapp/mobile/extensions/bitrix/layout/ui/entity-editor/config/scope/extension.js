/**
 * @module layout/ui/entity-editor/config/scope
 */
jn.define('layout/ui/entity-editor/config/scope', (require, exports, module) => {
	const EntityConfigScope = {
		undefined: '',
		personal: 'P',
		common: 'C',
		custom: 'CUSTOM',
	};

	module.exports = { EntityConfigScope };
});