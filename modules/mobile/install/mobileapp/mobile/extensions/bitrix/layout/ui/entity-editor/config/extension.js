(() => {
	/**
	 * @class EntityConfig
	 */
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
			this.scope = BX.UI.EntityConfigScope.undefined;

			this.canUpdatePersonalConfiguration = true;
			this.canUpdateCommonConfiguration = false;

			this.data = [];
			this.items = [];
			this.options = {};
		}

		initialize(id, settings)
		{
			this.id = CommonUtils.isNotEmptyString(id) ? id : CommonUtils.getRandom(4);
			this.settings = settings ? settings : {};
			this.scope = BX.prop.getString(this.settings, "scope", BX.UI.EntityConfigScope.personal);

			this.canUpdatePersonalConfiguration = BX.prop.getBoolean(this.settings, "canUpdatePersonalConfiguration", true);
			this.canUpdateCommonConfiguration = BX.prop.getBoolean(this.settings, "canUpdateCommonConfiguration", false);

			this.data = BX.prop.getArray(this.settings, "data", []);

			this.items = [];

			this.data.forEach((item) => {
				const type = BX.prop.getString(item, "type", "");
				const config = EntityConfigFactory.createByType(type, {data: item});
				this.items.push(config);
			});

			this.options = BX.prop.getObject(this.settings, "options", {});
		}

		isChangeable()
		{
			if (
				this.scope === BX.UI.EntityConfigScope.common
				|| this.scope === BX.UI.EntityConfigScope.custom
			)
			{
				return this.canUpdateCommonConfiguration;
			}
			else if (this.scope === BX.UI.EntityConfigScope.personal)
			{
				return this.canUpdatePersonalConfiguration;
			}

			return false;
		}
	}

	jnexport(EntityConfig);
})();