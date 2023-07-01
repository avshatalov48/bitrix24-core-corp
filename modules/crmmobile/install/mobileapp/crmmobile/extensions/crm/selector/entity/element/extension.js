/**
 * @module crm/selector/entity/element
 */
jn.define('crm/selector/entity/element', (require, exports, module) => {
	const { SelectorProcessing } = require('crm/selector/utils/processing');
	const { ComplexSelector } = require('selector/widget/entity/complex');
	const { get } = require('utils/object');

	/**
	 * @class CrmElementSelector
	 */
	class CrmElementSelector extends ComplexSelector
	{
		static getEntityIds()
		{
			return [
				'contact',
				'company',
				'lead',
				'deal',
				'dynamic_multiple',
				'smart_invoice',
			];
		}

		static getContext()
		{
			return 'CRM_ENTITIES';
		}

		static getEntityWeight()
		{
			return {
				contact: 100,
				company: 100,
				smart_invoice: 90,
				dynamic_multiple: 80,
				lead: 70,
				deal: 70,
			};
		}

		static prepareCreateOptions()
		{
			return {};
		}

		static getStartTypingText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_ELEMENT');
		}

		static getSearchPlaceholderWithCreation()
		{
			return BX.message('SELECTOR_COMPONENT_SEARCH_PLACEHOLDER_ELEMENT');
		}

		static getSearchPlaceholderWithoutCreation()
		{
			return BX.message('SELECTOR_COMPONENT_SEARCH_PLACEHOLDER_ELEMENT');
		}

		static getTitle()
		{
			return BX.message('SELECTOR_COMPONENT_TITLE_ELEMENT');
		}

		static prepareItemForDrawing(entity)
		{
			let params = {};
			let subtitle = entity.subtitle;

			const entityInfo = get(entity, ['customData', 'entityInfo'], null);
			if (entityInfo)
			{
				subtitle = subtitle || entityInfo.typeNameTitle;
			}

			if (
				entityInfo
				&& (entity.entityId === 'contact' || entity.entityId === 'company')
			)
			{
				params = SelectorProcessing.prepareContact(entityInfo);
			}

			params.subtitle = subtitle;

			return { subtitle, params };
		}

		static useRawResult()
		{
			return true;
		}
	}

	module.exports = { CrmElementSelector };
});
