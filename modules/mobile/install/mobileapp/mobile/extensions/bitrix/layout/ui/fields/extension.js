/**
 * @module layout/ui/fields
 */
jn.define('layout/ui/fields', (require, exports, module) => {

	const { MultipleField } = require('layout/ui/fields/multiple-field');
	const { StringType, StringField } = require('layout/ui/fields/string');
	const { TextAreaType, TextAreaField } = require('layout/ui/fields/textarea');
	const { NumberType, NumberField } = require('layout/ui/fields/number');
	const { BarcodeType, BarcodeField } = require('layout/ui/fields/barcode');
	const { DateTimeType, DateTimeField } = require('layout/ui/fields/datetime');
	const { FileType, FileField } = require('layout/ui/fields/file');
	const { StatusType, StatusField } = require('layout/ui/fields/status');
	const { SelectType, SelectField } = require('layout/ui/fields/select');
	const { UserType, UserField } = require('layout/ui/fields/user');
	const { ProjectType, ProjectField } = require('layout/ui/fields/project');
	const { TagType, TagField } = require('layout/ui/fields/tag');
	const { ImageSelectType, ImageSelectField } = require('layout/ui/fields/image-select');
	const { MenuSelectType, MenuSelectField } = require('layout/ui/fields/menu-select');
	const { EntitySelectorType, EntitySelectorField } = require('layout/ui/fields/entity-selector');
	const { MoneyType, MoneyField } = require('layout/ui/fields/money');
	const { AddressType, AddressField } = require('layout/ui/fields/address');
	const { UrlType, UrlField } = require('layout/ui/fields/url');
	const { BooleanType, BooleanField } = require('layout/ui/fields/boolean');
	const { PhoneType, PhoneField } = require('layout/ui/fields/phone');
	const { ImType, ImField } = require('layout/ui/fields/im');
	const { EmailType, EmailField } = require('layout/ui/fields/email');
	const { WebType, WebField } = require('layout/ui/fields/web');
	const { RequisiteType, RequisiteField } = require('layout/ui/fields/requisite');
	const { RequisiteAddressType, RequisiteAddressField } = require('layout/ui/fields/requisite-address');
	const { CombinedType, CombinedField } = require('layout/ui/fields/combined');
	const { CombinedV2Type, CombinedV2Field } = require('layout/ui/fields/combined-v2');
	const { MultipleCombinedType, MultipleCombinedField } = require('layout/ui/fields/multiple-combined');

	let CrmElementType;
	let CrmElementField;
	let ClientType;
	let ClientField;
	let CrmStageType;
	let CrmStageField;

	try
	{
		CrmElementType = require('layout/ui/fields/crm-element').CrmElementType;
		CrmElementField = require('layout/ui/fields/crm-element').CrmElementField;
		ClientType = require('layout/ui/fields/client').ClientType;
		ClientField = require('layout/ui/fields/client').ClientField;
		CrmStageType = require('layout/ui/fields/crm-stage').CrmStageType;
		CrmStageField = require('layout/ui/fields/crm-stage').CrmStageField;
	}
	catch (e)
	{
		console.warn(e);
	}

	const Types = [
		StringType,
		TextAreaType,
		NumberType,
		BarcodeType,
		DateTimeType,
		FileType,
		StatusType,
		SelectType,
		UserType,
		ProjectType,
		TagType,
		ImageSelectType,
		MenuSelectType,
		EntitySelectorType,
		MoneyType,
		AddressType,
		UrlType,
		BooleanType,
		PhoneType,
		ImType,
		EmailType,
		WebType,
		ClientType,
		CrmStageType,
		CrmElementType,
		RequisiteType,
		RequisiteAddressType,
		CombinedType,
		CombinedV2Type,
		MultipleCombinedType,
	];

	const ALIAS_TYPES = {
		text: TextAreaType,
		integer: NumberType,
		double: NumberType,
		date: DateTimeType,

		enumeration: SelectType,
		list: SelectType,
		crm_status: SelectType,

		iblock_element: EntitySelectorType,
		iblock_section: EntitySelectorType,

		employee: UserType,
		client_light: ClientType,

		crm_entity: CrmElementType,
		crm_lead: CrmElementType,
		crm_deal: CrmElementType,
		crm_quote: CrmElementType,
		crm_invoice: CrmElementType,
		// these fields render with client_light type
		// crm_contact: CrmElementType,
		// crm_company: CrmElementType,
	};

	const WRAPPED_WITH_MULTIPLE_FIELD = [
		StringType,
		TextAreaType,
		NumberType,
		MoneyType,
		BarcodeType,
		DateTimeType,
		AddressType,
		UrlType,
		BooleanType,
		CombinedType,
	];

	const renderField = (fieldData) => FieldFactory.create(fieldData.type, fieldData);

	/**
	 * @class FieldFactory
	 */
	class FieldFactory
	{
		static checkForAlias(type)
		{
			return ALIAS_TYPES[type] || type;
		}

		static has(type)
		{
			type = this.checkForAlias(type);

			return Types.includes(type);
		}

		static create(type, data)
		{
			if (!FieldFactory.has(type))
			{
				return null;
			}

			const fieldType = this.checkForAlias(type);

			data = { ...data, type: fieldType };

			if (data.multiple && WRAPPED_WITH_MULTIPLE_FIELD.includes(fieldType))
			{
				return MultipleField({ ...data, renderField });
			}

			switch (fieldType)
			{
				case StringType:
					return StringField(data);

				case EmailType:
					return EmailField(data);

				case TextAreaType:
					return TextAreaField(data);

				case DateTimeType:
					return DateTimeField(data);

				case FileType:
					return FileField(data);

				case StatusType:
					return StatusField(data);

				case SelectType:
					return SelectField(data);

				case EntitySelectorType:
					return EntitySelectorField(data);

				case UserType:
					return UserField(data);

				case ProjectType:
					return ProjectField(data);

				case TagType:
					return TagField(data);

				case ImageSelectType:
					return ImageSelectField(data);

				case MenuSelectType:
					return MenuSelectField(data);

				case NumberType:
					return NumberField(data);

				case MoneyType:
					return MoneyField(data);

				case BarcodeType:
					return BarcodeField(data);

				case AddressType:
					return AddressField(data);

				case UrlType:
					return UrlField(data);

				case BooleanType:
					return BooleanField(data);

				case PhoneType:
					return PhoneField(data);

				case ImType:
					return ImField(data);

				case WebType:
					return WebField(data);

				case RequisiteType:
					return RequisiteField(data);

				case RequisiteAddressType:
					return RequisiteAddressField(data);

				case CombinedType:
					return CombinedField({ ...data, renderField });

				case CombinedV2Type:
					return CombinedV2Field({ ...data, renderField });

				case MultipleCombinedType:
					return MultipleCombinedField({ ...data, renderField });
			}

			if (fieldType === CrmStageType && CrmStageField)
			{
				return CrmStageField(data);
			}

			if (fieldType === ClientType && ClientField)
			{
				return ClientField(data);
			}

			if (fieldType === CrmElementType && CrmElementField)
			{
				return CrmElementField(data);
			}

			console.warn('Type ' + type + ' not found. Trying to render the field as a StringInput.');

			return null;
		}
	}

	module.exports = {
		FieldFactory,
		StringType,
		TextAreaType,
		NumberType,
		BarcodeType,
		DateTimeType,
		FileType,
		StatusType,
		SelectType,
		UserType,
		ProjectType,
		TagType,
		ImageSelectType,
		MenuSelectType,
		EntitySelectorType,
		MoneyType,
		AddressType,
		UrlType,
		BooleanType,
		PhoneType,
		ImType,
		EmailType,
		WebType,
		ClientType,
		CrmStageType,
		CrmElementType,
		RequisiteType,
		RequisiteAddressType,
		CombinedType,
		CombinedV2Type,
		MultipleCombinedType,
	};

});
