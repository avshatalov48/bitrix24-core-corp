/**
 * @module layout/ui/entity-editor/config
 */
jn.define('layout/ui/entity-editor/config', (require, exports, module) => {

	const { Type } = require('type');
	const { EntityConfigFactory } = require('layout/ui/entity-editor/config/factory');
	const { EntityConfigScope } = require('layout/ui/entity-editor/config/scope');

	class EntityConfig
	{
		static create(id, settings)
		{
			const self = new EntityConfig();
			self.initialize(id, settings);

			return self;
		}

		constructor()
		{
			this.id = '';
			this.settings = {};
			this.scope = EntityConfigScope.undefined;

			this.canUpdatePersonalConfiguration = true;
			this.canUpdateCommonConfiguration = false;

			this.data = [];
			this.items = [];
			this.options = {};
		}

		initialize(id, settings)
		{
			this.id = Type.isStringFilled(id) ? id : Random.getString();
			this.settings = settings ? settings : {};
			this.scope = BX.prop.getString(this.settings, 'scope', EntityConfigScope.personal);

			this.canUpdatePersonalConfiguration = BX.prop.getBoolean(this.settings, 'canUpdatePersonalConfiguration', true);
			this.canUpdateCommonConfiguration = BX.prop.getBoolean(this.settings, 'canUpdateCommonConfiguration', false);

			this.data = BX.prop.getArray(this.settings, 'data', []);

			this.items = [];

			this.data.forEach((item) => {
				const type = BX.prop.getString(item, 'type', '');
				const config = EntityConfigFactory.createByType(type, { data: item });
				this.items.push(config);
			});

			this.options = BX.prop.getObject(this.settings, 'options', {});
		}

		isChangeable()
		{
			if (
				this.scope === EntityConfigScope.common
				|| this.scope === EntityConfigScope.custom
			)
			{
				return this.canUpdateCommonConfiguration;
			}
			else if (this.scope === EntityConfigScope.personal)
			{
				return this.canUpdatePersonalConfiguration;
			}

			return false;
		}
	}

	module.exports = { EntityConfig };
});
