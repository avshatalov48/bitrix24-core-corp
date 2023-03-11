(() => {
	/**
	 * @class CatalogSectionSelector
	 */
	class CatalogSectionSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'section';
		}

		static getContext()
		{
			return 'catalog-sections'
		}

		static getStartTypingText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_SECTION');
		}

		static getStartTypingWithCreationText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_CREATE_SECTION');
		}

		static isCreationEnabled()
		{
			return true;
		}

		static getCreateText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATE_SECTION');
		}

		static getCreatingText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATING_SECTION');
		}

		static getCreateEntityHandler(providerOptions)
		{
			return (text) => {
				return BX.ajax.runComponentAction(
					'bitrix:catalog.productcard.iblocksectionfield',
					'addSection',
					{
						mode: 'ajax',
						data: {
							iblockId: providerOptions.iblockId,
							name: text
						}
					}
				).then((response) => {
					if (response.data && response.data.id)
					{
						return {
							id: response.data.id,
							entityId: this.getEntityId(),
							title: text
						};
					}

					return null;
				}).catch((response) => console.error(response));
			};
		}

		static getTitle()
		{
			return BX.message('SELECTOR_COMPONENT_PICK_SECTION_2');
		}
	}

	this.CatalogSectionSelector = CatalogSectionSelector;
})();
