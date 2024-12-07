/**
 * @module crm/entity-actions/conversion
 */
jn.define('crm/entity-actions/conversion', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { Type, TypeId } = require('crm/type');

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

		const svgIcon = Icon.DUPLICATE;

		const canUseConversion = (entityTypeId) => {
			if (!Type.isEntitySupportedById(entityTypeId))
			{
				return false;
			}

			return SUPPORTED_ENTITY_TYPES.has(entityTypeId);
		};

		/**
		 * @method onAction
		 * @param {Object} props
		 * @param {number} props.entityId
		 * @param {string} props.entityTypeId
		 * @param {JSStackNavigation} props.parentWidget
		 * @returns {Promise}
		 */
		const onAction = async (props) => {
			const { Conversion } = await requireLazy('crm:conversion');
			const conversionProps = await Conversion.createConversionWizard(props);

			return () => Conversion.show(conversionProps);
		};

		return { id, title, svgIcon, canUseConversion, onAction };
	};

	module.exports = { getActionToConversion };
});
