import {Type} from 'main.core';

import {PhoneNumberInputFieldConfigurator} from './phone-number-input-field-configurator';

/**
 * @memberOf BX.Crm
 */
export default class EntityConfigurationManager extends BX.UI.EntityConfigurationManager
{
	/**
	 * @param {Object} params
	 * @param {Object} parent
	 *
	 * @returns {BX.UI.EntityEditorFieldConfigurator}
	 *
	 * @override
	 */
	getSimpleFieldConfigurator(params: Object, parent: Object): BX.UI.EntityEditorFieldConfigurator
	{
		if (!Type.isPlainObject(params))
		{
			throw 'EntityConfigurationManager: The "params" argument must be object.';
		}

		let typeId = '';
		let child = BX.prop.get(params, 'field', null);
		if (child)
		{
			typeId = child.getType();

			child.setVisible(false);
		}
		else
		{
			typeId = BX.prop.get(params, 'typeId', BX.UI.EntityUserFieldType.string);
		}

		// override for 'PHONE', 'CLIENT', 'COMPANY', 'CONTACT' fields: add additional option to setup default country phone code
		if (['PHONE', 'CLIENT', 'COMPANY', 'CONTACT', 'MYCOMPANY_ID'].indexOf(child.getId()) >= 0)
		{
			return this._fieldConfigurator = PhoneNumberInputFieldConfigurator.create(
				'',
				{
					editor: this._editor,
					schemeElement: null,
					model: parent._model,
					mode: BX.UI.EntityEditorMode.edit,
					parent: parent,
					typeId: typeId,
					field: child,
					mandatoryConfigurator: params.mandatoryConfigurator
				}
			);
		}
		else
		{
			return this._fieldConfigurator = BX.UI.EntityEditorFieldConfigurator.create(
				'',
				{
					editor: this._editor,
					schemeElement: null,
					model: parent._model,
					mode: BX.UI.EntityEditorMode.edit,
					parent: parent,
					typeId: typeId,
					field: child,
					mandatoryConfigurator: params.mandatoryConfigurator
				}
			);
		}
	}

	/**
	 * @param {Object} params
	 * @param {Object} parent
	 *
	 * @returns { BX.UI.EntityEditorUserFieldConfigurator}
	 *
	 * @override
	 */
	getUserFieldConfigurator(params: Object, parent: Object): BX.UI.EntityEditorUserFieldConfigurator
	{
		if (!Type.isPlainObject(params))
		{
			throw 'EntityConfigurationManager: The "params" argument must be object.';
		}

		let typeId = '';
		let field = BX.prop.get(params, 'field', null);
		if (field)
		{
			if (!(field instanceof BX.UI.EntityEditorUserField))
			{
				throw 'EntityConfigurationManager: The "field" param must be EntityEditorUserField.';
			}

			typeId = field.getFieldType();

			field.setVisible(false);
		}
		else
		{
			typeId = BX.prop.get(params, 'typeId', BX.UI.EntityUserFieldType.string);
		}

		if (typeId === 'resourcebooking')
		{
			let options = {
				editor: this._editor,
				schemeElement: null,
				model: parent.getModel(),
				mode: BX.UI.EntityEditorMode.edit,
				parent: parent,
				typeId: typeId,
				field: field,
				showAlways: true,
				enableMandatoryControl: BX.prop.getBoolean(params, 'enableMandatoryControl', true),
				mandatoryConfigurator: params.mandatoryConfigurator
			};

			if (BX.Calendar && BX.type.isFunction(BX.Calendar.ResourcebookingUserfield))
			{
				return BX.Calendar.ResourcebookingUserfield.getCrmFieldConfigurator('', options);
			}
			else if (BX.Calendar && BX.Calendar.UserField && BX.Calendar.UserField.EntityEditorUserFieldConfigurator)
			{
				return BX.Calendar.UserField.EntityEditorUserFieldConfigurator.create('', options);
			}
		}
		else
		{
			return BX.Crm.EntityEditorUserFieldConfigurator.create(
				'',
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

	getTypeInfos()
	{
		let typeInfos = super.getTypeInfos();
		let ufAddRestriction = this._editor.getRestriction('userFieldAdd');
		let ufResourceBookingRestriction = this._editor.getRestriction('userFieldResourceBooking');

		if (ufAddRestriction && !ufAddRestriction['isPermitted'] && ufAddRestriction['restrictionCallback'])
		{
			for (let i = 0, length = typeInfos.length; i < length; i++)
			{
				typeInfos[i].callback = function()
				{
					eval(ufAddRestriction['restrictionCallback']);
				};
			}
		}
		else if (
			ufResourceBookingRestriction
			&& !ufResourceBookingRestriction['isPermitted']
			&& ufResourceBookingRestriction['restrictionCallback']
		)
		{
			for (let j = 0; j < typeInfos.length; j++)
			{
				if (typeInfos[j].name === 'resourcebooking')
				{
					typeInfos[j].callback = function()
					{
						eval(ufResourceBookingRestriction['restrictionCallback']);
					};
				}
			}
		}

		return typeInfos;
	};

	static create(id, settings): EntityConfigurationManager
	{
		const self = new this;

		self.initialize(id, settings);

		return self;
	}
}
