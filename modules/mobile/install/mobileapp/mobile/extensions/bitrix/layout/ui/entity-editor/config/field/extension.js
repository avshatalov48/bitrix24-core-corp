/**
 * @module layout/ui/entity-editor/config/field
 */
jn.define('layout/ui/entity-editor/config/field', (require, exports, module) => {

	const { EntityConfigBaseItem } = require('layout/ui/entity-editor/config/base');
	class EntityConfigField extends EntityConfigBaseItem
	{
		static create(settings)
		{
			const self = new EntityConfigField();
			self.initialize(settings);

			return self;
		}

		constructor()
		{
			super();
			this.index = -1;
			this.optionFlags = 0;
			this.options = {};
		}

		doInitialize()
		{
			this.optionFlags = BX.prop.getInteger(this.data, "optionFlags", 0);
			this.options = BX.prop.getObject(this.data, "options", {});
		}

		setIndex(index)
		{
			this.index = index;
		}
	}

	module.exports = { EntityConfigField };
});