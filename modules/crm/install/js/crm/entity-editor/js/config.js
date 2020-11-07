BX.namespace("BX.Crm");

if(typeof BX.Crm.EntityConfig === "undefined")
{
	/**
	 * @deprecated
	 * @extends BX.UI.EntityConfig
	 * @constructor
	 */
	BX.Crm.EntityConfig = function()
	{
		BX.Crm.EntityConfig.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.EntityConfig, BX.UI.EntityConfig);

	BX.Crm.EntityConfig.create = function(id, settings)
	{
		var self = new BX.Crm.EntityConfig();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityConfigItem === "undefined")
{
	/**
	 * @deprecated
	 * @extends BX.UI.EntityConfigItem
	 * @constructor
	 */
	BX.Crm.EntityConfigItem = function()
	{
		BX.Crm.EntityConfigItem.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.EntityConfigItem, BX.UI.EntityConfigItem);
}

if(typeof BX.Crm.EntityConfigSection === "undefined")
{
	/**
	 * @deprecated
	 * @extends BX.UI.EntityConfigSection
	 * @constructor
	 */
	BX.Crm.EntityConfigSection = function()
	{
		BX.Crm.EntityConfigSection.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.EntityConfigSection, BX.UI.EntityConfigSection);

	BX.Crm.EntityConfigSection.create = function(settings)
	{
		var self = new BX.Crm.EntityConfigSection();
		self.initialize(settings);
		return self;
	};
}

if(typeof BX.Crm.EntityConfigField === "undefined")
{
	/**
	 * @deprecated
	 * @extends BX.UI.EntityConfigField
	 * @constructor
	 */
	BX.Crm.EntityConfigField = function()
	{
		BX.Crm.EntityConfigField.superclass.constructor.apply(this);

	};
	BX.extend(BX.Crm.EntityConfigField, BX.UI.EntityConfigItem);

	BX.Crm.EntityConfigField.create = function(settings)
	{
		var self = new BX.Crm.EntityConfigField();
		self.initialize(settings);
		return self;
	};
}
//region ENTITY CONFIGURATION SCOPE
if(typeof BX.Crm.EntityConfigScope === "undefined")
{
	/**
	 * @deprecated
	 * @see BX.UI.EntityConfigScope
	 */
	BX.Crm.EntityConfigScope =
		{
			undefined: '',
			personal:  'P',
			common: 'C',
			custom: 'CUSTOM'
		};

	if(typeof(BX.Crm.EntityConfigScope.captions) === "undefined")
	{
		BX.Crm.EntityConfigScope.captions = {};
	}

	BX.Crm.EntityConfigScope.setCaptions = function(captions)
	{
		if(BX.type.isPlainObject(captions))
		{
			this.captions = captions;
		}
	};

	BX.Crm.EntityConfigScope.getCaption = function(scope)
	{
		return BX.prop.getString(this.captions, scope, scope);
	};
}
//endregion