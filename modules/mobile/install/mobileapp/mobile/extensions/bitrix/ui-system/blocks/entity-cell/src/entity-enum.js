/**
 * @module ui/system/blocks/entity-cell/src/entity-enum
 */
jn.define('ui/system/blocks/entity-cell/src/entity-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class EntityCellMode
	 */
	class EntityCellMode extends BaseEnum
	{
		static SINGLE = new EntityCellMode('SINGLE');
		static MULTIPLE = new EntityCellMode('MULTIPLE');
		static GROUP = new EntityCellMode('GROUP');
		static ENTITY_MENU = new EntityCellMode('ENTITY_MENU');
	}

	module.exports = {
		EntityCellMode,
	};
});
