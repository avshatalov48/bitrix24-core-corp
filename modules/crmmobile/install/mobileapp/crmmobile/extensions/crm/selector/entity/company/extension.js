/**
 * @module crm/selector/entity/company
 */
jn.define('crm/selector/entity/company', (require, exports, module) => {
	const { get } = require('utils/object');
	const { SelectorProcessing } = require('crm/selector/utils/processing');

	/**
	 * @class CrmCompanySelector
	 */
	class CrmCompanySelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'company';
		}

		static getContext()
		{
			return 'CRM_ENTITIES';
		}

		static getStartTypingText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_COMPANY2');
		}

		static getStartTypingWithCreationText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_CREATE_COMPANY');
		}

		static getSearchPlaceholderWithCreation()
		{
			return BX.message('SELECTOR_COMPONENT_SEARCH_PLACEHOLDER_COMPANY');
		}

		static getSearchPlaceholderWithoutCreation()
		{
			return BX.message('SELECTOR_COMPONENT_SEARCH_PLACEHOLDER_COMPANY');
		}

		static isCreationEnabled(providerOptions)
		{
			const { entities = [] } = providerOptions || {};
			const company = entities.find(({ id }) => id === CrmCompanySelector.getEntityId());

			if (company)
			{
				const { enableMyCompanyOnly = false } = company.options || {};

				return !enableMyCompanyOnly;
			}

			return false;
		}

		static canCreateWithEmptySearch()
		{
			return true;
		}

		static getCreateText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATE_COMPANY');
		}

		static getCreatingText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATE_COMPANY');
		}

		static getCreateEntityHandler(providerOptions)
		{
			return () => Promise.resolve({
				title: BX.message('SELECTOR_COMPONENT_CREATE_TITLE_COMPANY'),
				entityId: this.getEntityId(),
			});
		}

		static getTitle()
		{
			return BX.message('SELECTOR_COMPONENT_TITLE_COMPANY');
		}

		static prepareItemForDrawing(entity)
		{
			const entityInfo = get(entity, ['customData', 'entityInfo'], null);
			if (!entityInfo)
			{
				return entity;
			}

			const params = SelectorProcessing.prepareContact(entityInfo);
			const { subtitle } = entity;

			params.subtitle = subtitle;

			return { subtitle, params };
		}

		static useRawResult()
		{
			return true;
		}
	}

	module.exports = { CrmCompanySelector };
});
