/**
 * @module crm/selector/documentgenerator/template
 */
jn.define('crm/selector/documentgenerator/template', (require, exports, module) => {
	const { Loc } = require('loc');

	/**
	 * @class DocumentGeneratorTemplateSelector
	 */
	class DocumentGeneratorTemplateSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'documentgenerator-template';
		}

		static getStartTypingText()
		{
			return Loc.getMessage('M_CRM_DOCUMENTGENERATOR_TEMPLATE_SELECTOR_SEARCH_PLACEHOLDER');
		}

		static getStartTypingWithCreationText()
		{
			return Loc.getMessage('M_CRM_DOCUMENTGENERATOR_TEMPLATE_SELECTOR_SEARCH_PLACEHOLDER');
		}

		static getSearchPlaceholderWithCreation()
		{
			return Loc.getMessage('M_CRM_DOCUMENTGENERATOR_TEMPLATE_SELECTOR_SEARCH_PLACEHOLDER');
		}

		static getSearchPlaceholderWithoutCreation()
		{
			return Loc.getMessage('M_CRM_DOCUMENTGENERATOR_TEMPLATE_SELECTOR_SEARCH_PLACEHOLDER');
		}

		static isCreationEnabled()
		{
			return true;
		}

		static getCreateText()
		{
			return Loc.getMessage('M_CRM_DOCUMENTGENERATOR_TEMPLATE_SELECTOR_CREATE_BUTTON');
		}

		static getCreateEntityHandler(providerOptions)
		{
			return (searchQuery) => new Promise((resolve) => {
				resolve({ title: searchQuery, entityId: this.getEntityId() });
			});
		}

		static getTitle()
		{
			return Loc.getMessage('M_CRM_DOCUMENTGENERATOR_TEMPLATE_SELECTOR_TITLE');
		}

		static useRawResult()
		{
			return true;
		}
	}

	module.exports = { DocumentGeneratorTemplateSelector };
});
