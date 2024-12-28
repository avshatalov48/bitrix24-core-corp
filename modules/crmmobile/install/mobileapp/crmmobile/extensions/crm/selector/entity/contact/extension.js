/**
 * @module crm/selector/entity/contact
 */
jn.define('crm/selector/entity/contact', (require, exports, module) => {
	const { get } = require('utils/object');
	const { BaseSelectorEntity } = require('selector/widget/entity');
	const { SelectorProcessing } = require('crm/selector/utils/processing');

	/**
	 * @class CrmContactSelector
	 */
	class CrmContactSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'contact';
		}

		static getContext()
		{
			return 'CRM_ENTITIES';
		}

		static getStartTypingText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_CONTACT');
		}

		static getStartTypingWithCreationText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_CREATE_CONTACT');
		}

		static getSearchPlaceholderWithCreation()
		{
			return BX.message('SELECTOR_COMPONENT_SEARCH_PLACEHOLDER_CONTACT');
		}

		static getSearchPlaceholderWithoutCreation()
		{
			return BX.message('SELECTOR_COMPONENT_SEARCH_PLACEHOLDER_CONTACT');
		}

		static isCreationEnabled()
		{
			return true;
		}

		static canCreateWithEmptySearch()
		{
			return true;
		}

		static getCreateText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATE_CONTACT');
		}

		static getCreatingText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATE_CONTACT');
		}

		static getCreateEntityHandler(providerOptions)
		{
			return () => {
				return new Promise((resolve) => {
					resolve({
						title: BX.message('SELECTOR_COMPONENT_CREATE_TITLE_CONTACT'),
						entityId: this.getEntityId(),
					});
				});
			};
		}

		static getTitle()
		{
			return BX.message('SELECTOR_COMPONENT_TITLE_CONTACT');
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

	module.exports = { CrmContactSelector };
});
