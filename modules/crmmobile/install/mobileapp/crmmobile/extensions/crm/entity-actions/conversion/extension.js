/**
 * @module crm/entity-actions/conversion
 */
jn.define('crm/entity-actions/conversion', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type, TypeId } = require('crm/type');
	const { Conversion } = require('crm/conversion');
	const SUPPORTED_ENTITY_TYPES = new Set([
		TypeId.Lead, TypeId.Deal, TypeId.Quote,
	]);
	/**
	 * @method getActionToConversion
	 * @returns {object}
	 */
	const getActionToConversion = () => {
		const id = 'crm-conversion';
		const title = Loc.getMessage('M_CRM_ENTITY_ACTION_CONVERSION');
		const svgIcon = '<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.26514 23.2739L21.5936 23.2739C22.1198 23.2739 22.5503 22.8434 22.5503 22.3172L22.5503 9.98878L23.9239 9.98878C24.4441 9.98878 24.8697 10.4144 24.8697 10.9345L24.8697 24.6476C24.8697 25.1677 24.4441 25.5933 23.9239 25.5933L10.2109 25.5933C9.69079 25.5933 9.26514 25.1677 9.26514 24.6476L9.26514 23.2739Z" fill="#767C87"/><path fill-rule="evenodd" clip-rule="evenodd" d="M19.7892 21.4585L6.0761 21.4585C5.55603 21.4585 5.13037 21.0329 5.13037 20.5128L5.13037 6.7997C5.13037 6.27963 5.55603 5.85397 6.0761 5.85397L19.7892 5.85397C20.3093 5.85397 20.7349 6.27963 20.7349 6.7997L20.7349 20.5128C20.7349 21.0329 20.3093 21.4585 19.7892 21.4585ZM11.8716 9.37509H13.9938V12.595H17.2138V14.7172H13.9938V17.9373H11.8716V14.7172H8.65163V12.595H11.8716V9.37509Z" fill="#767C87"/></svg>';
		const canUseConversion = (entityTypeId) => {
			if (!Type.isEntitySupportedById(entityTypeId))
			{
				return false;
			}

			return SUPPORTED_ENTITY_TYPES.has(entityTypeId);
		};

		/**
		 * @method onAction
		 * @param {string} entityId
		 * @param {string} entityTypeId
		 * @param {JSStackNavigation} parentWidget
		 * @returns {Promise}
		 */
		const onAction = (props) => new Promise((resolve, reject) => {
			Conversion.createMenu(props)
				.then(resolve)
				.catch((error) => {
					console.error(error);
					reject(error);
				});
		});

		return { id, title, svgIcon, canUseConversion, onAction };
	};

	module.exports = { getActionToConversion };
});
