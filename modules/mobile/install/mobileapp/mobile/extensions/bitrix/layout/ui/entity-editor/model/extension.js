(() => {
	/**
	 * @class EntityModel
	 */
	class EntityModel
	{
		static create(id, settings)
		{
			const self = new EntityModel();
			self.initialize(id, settings);
			return self;
		}

		constructor()
		{
			this.id = "";
			this.settings = {};
			this.data = null;
		}

		initialize(id, settings)
		{
			this.id = CommonUtils.isNotEmptyString(id) ? id : CommonUtils.getRandom(4);
			this.settings = settings ? settings : {};
			this.isIdentifiableEntity = BX.prop.getBoolean(this.settings, "IS_IDENTIFIABLE_ENTITY", true);
			this.data = BX.prop.getObject(this.settings, "data", {});
			this.lockedFields = {};
		}

		isIdentifiable()
		{
			return this.isIdentifiableEntity;
		}

		getField(name, defaultValue)
		{
			if (defaultValue === undefined)
			{
				defaultValue = null;
			}

			return BX.prop.get(this.data, name, defaultValue);
		}

		setField(name, newValue)
		{
			this.data[name] = newValue;
		}

		getFields()
		{
			return {...this.data};
		}
	}

	jnexport(EntityModel);
})();