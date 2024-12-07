/* eslint-disable no-underscore-dangle, @bitrix24/bitrix24-rules/no-pseudo-private */

import { Type } from 'main.core';

import { PhoneNumberInputFieldConfigurator } from './field-configurators/phone-number-input';
import RequisiteAddressFieldConfigurator from './field-configurators/requisite-address';

/**
 * @memberOf BX.Crm
 */
export default class EntityConfigurationManager extends BX.UI.EntityConfigurationManager
{
	static PHONE_NUMBER_FIELDS = ['PHONE', 'CLIENT', 'COMPANY', 'CONTACT', 'MYCOMPANY_ID'];
	static REQUISITE_ADDRESS_FIELDS = ['ADDRESS'];

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
			throw new TypeError('EntityConfigurationManager: The "params" argument must be object.');
		}

		let typeId = '';
		const { field: child, mandatoryConfigurator } = params;
		if (child)
		{
			typeId = child.getType();

			child.setVisible(false);
		}
		else
		{
			typeId = BX.prop.get(params, 'typeId', BX.UI.EntityUserFieldType.string);
		}

		const fieldConfiguratorOptions = {
			editor: this._editor,
			schemeElement: null,
			model: parent._model,
			mode: BX.UI.EntityEditorMode.edit,
			parent,
			typeId,
			field: child,
			mandatoryConfigurator,
		};

		// override for 'PHONE', 'CLIENT', 'COMPANY', 'CONTACT' fields:
		// add additional option to set up default country phone code
		if (EntityConfigurationManager.PHONE_NUMBER_FIELDS.includes(child.getId()))
		{
			this._fieldConfigurator = PhoneNumberInputFieldConfigurator.create('', fieldConfiguratorOptions);
		}
		else if (
			EntityConfigurationManager.REQUISITE_ADDRESS_FIELDS.includes(child.getId())
			&& typeId === 'requisite_address'
		)
		{
			this._fieldConfigurator = RequisiteAddressFieldConfigurator.create('', fieldConfiguratorOptions);
		}
		else
		{
			this._fieldConfigurator = BX.UI.EntityEditorFieldConfigurator.create('', fieldConfiguratorOptions);
		}

		return this._fieldConfigurator;
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
