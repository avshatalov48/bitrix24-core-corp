/**
 * @module layout/ui/entity-editor/control/combined/phone
 */
jn.define('layout/ui/entity-editor/control/combined/phone', (require, exports, module) => {

	const { EntityEditorCombinedBase } = require('layout/ui/entity-editor/control/combined/base');
	const { phoneUtils } = require('native/phonenumber');
	const { get } = require('utils/object');

	/**
	 * @class EntityEditorPhone
	 */
	class EntityEditorPhone extends EntityEditorCombinedBase
	{

		prepareBeforeSaving(values)
		{
			return values.map((singleValue) => {
				const { phoneNumber, countryCode } = get(singleValue, ['value', 'VALUE'], {});
				const phoneCode = phoneUtils.getPhoneCode(countryCode);

				if (`+${phoneCode}` === phoneNumber)
				{
					return null;
				}

				const value = { ...singleValue.value, VALUE: phoneNumber, VALUE_COUNTRY_CODE: countryCode };

				return { ...singleValue, value };
			}).filter(Boolean);
		}

		getCountryCodeEditorOptions()
		{
			const clientOptionsCountryCode = this.getContext().defaultCountry;
			const userOptionsCountryCode = this.getOptions().defaultCountry;

			return clientOptionsCountryCode || userOptionsCountryCode;
		}

		prepareConfig()
		{
			const config = super.prepareConfig();

			return {
				...config,
				countryCode: this.getCountryCodeEditorOptions(),
			};
		}

		prepareValue(value)
		{
			const { VALUE, VALUE_EXTRA, VALUE_TYPE } = value;
			const countryCode = VALUE_EXTRA ? VALUE_EXTRA.COUNTRY_CODE : null;

			return { VALUE: { phoneNumber: VALUE, countryCode }, VALUE_TYPE };
		}
	}

	module.exports = { EntityEditorPhone };
});
