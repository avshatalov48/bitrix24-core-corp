/**
 * @module layout/ui/entity-editor/config/base
 */
jn.define('layout/ui/entity-editor/config/base', (require, exports, module) => {
	/**
	 * @class EntityConfigBaseItem
	 */
	class EntityConfigBaseItem
	{
		constructor()
		{
			this.settings = {};
			this.data = {};
			this.name = "";
			this.title = "";
		}

		initialize(settings)
		{
			this.settings = settings || {};
			this.data = BX.prop.getObject(this.settings, "data", {});
			this.name = BX.prop.getString(this.data, "name", "");
			this.title = BX.prop.getString(this.data, "title", "");

			this.doInitialize();
		}

		doInitialize()
		{

		}
	}

	module.exports = { EntityConfigBaseItem };
});