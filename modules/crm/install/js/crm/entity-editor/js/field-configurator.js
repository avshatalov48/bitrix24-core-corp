if(typeof BX.Crm.EntityConfigurationManager === "undefined")
{
	/**
	 * @extends BX.UI.EntityConfigurationManager
	 * @constructor
	 */
	BX.Crm.EntityConfigurationManager = function()
	{
		BX.Crm.EntityConfigurationManager.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.EntityConfigurationManager, BX.UI.EntityConfigurationManager);
	BX.Crm.EntityConfigurationManager.prototype.getUserFieldConfigurator = function(params, parent)
	{
		if(!BX.type.isPlainObject(params))
		{
			throw "EntityEditorSection: The 'params' argument must be object.";
		}

		var typeId = "";
		var field = BX.prop.get(params, "field", null);
		if(field)
		{
			if(!(field instanceof BX.UI.EntityEditorUserField))
			{
				throw "EntityEditorSection: The 'field' param must be EntityEditorUserField.";
			}

			typeId = field.getFieldType();
			field.setVisible(false);
		}
		else
		{
			typeId = BX.prop.get(params, "typeId", BX.UI.EntityUserFieldType.string);
		}

		if (typeId === 'resourcebooking')
		{
			var options = {
				editor: this._editor,
				schemeElement: null,
				model: parent.getModel(),
				mode: BX.UI.EntityEditorMode.edit,
				parent: parent,
				typeId: typeId,
				field: field,
				showAlways: true,
				enableMandatoryControl: BX.prop.getBoolean(params, "enableMandatoryControl", true),
				mandatoryConfigurator: params.mandatoryConfigurator
			};

			if (BX.Calendar && BX.type.isFunction(BX.Calendar.ResourcebookingUserfield))
			{
				return BX.Calendar.ResourcebookingUserfield.getCrmFieldConfigurator("", options);
			}
			else if (BX.Calendar && BX.Calendar.UserField && BX.Calendar.UserField.EntityEditorUserFieldConfigurator)
			{
				return BX.Calendar.UserField.EntityEditorUserFieldConfigurator.create("", options);
			}
		}
		else
		{
			return BX.Crm.EntityEditorUserFieldConfigurator.create(
				"",
				{
					editor: this._editor,
					schemeElement: null,
					model: parent.getModel(),
					mode: BX.UI.EntityEditorMode.edit,
					parent: parent,
					typeId: typeId,
					field: field,
					mandatoryConfigurator: params.mandatoryConfigurator,
					visibilityConfigurator: params.visibilityConfigurator,
					showAlways: true
				}
			);
		}
	};

	BX.Crm.EntityConfigurationManager.create = function(id, settings)
	{
		var self = new BX.Crm.EntityConfigurationManager();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorFieldConfigurator === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityEditorFieldConfigurator = BX.UI.EntityEditorFieldConfigurator;
}