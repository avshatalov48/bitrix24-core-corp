/**
 * @module ui-system/form/inputs/email/src/domain-icon-place-enum
 */
jn.define('ui-system/form/inputs/email/src/domain-icon-place-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class InputDomainIconPlace
	 * @template TInputDomainIconPlace
	 * @extends {BaseEnum<InputDomainIconPlace>}
	 */
	class InputDomainIconPlace extends BaseEnum
	{
		static RIGHT = new InputDomainIconPlace('RIGHT', 'rightContent');

		static LEFT = new InputDomainIconPlace('LEFT', 'leftContent');

		static RIGHT_STICK = new InputDomainIconPlace('RIGHT_STICK', 'rightStickContent');
	}

	module.exports = { InputDomainIconPlace };
});
