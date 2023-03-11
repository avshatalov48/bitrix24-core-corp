(() => {
	/**
	 * @class CatalogStoreSelector
	 */
	class CatalogStoreSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'store';
		}

		static getContext()
		{
			return 'catalog-store';
		}

		static getStartTypingText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_STORE');
		}

		static getStartTypingWithCreationText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_CREATE_STORE');
		}

		static isCreationEnabled()
		{
			return true;
		}

		static getCreateText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATE_STORE');
		}

		static getCreatingText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATING_STORE');
		}

		static getCreateEntityHandler(providerOptions)
		{
			return (text) => {
				return BX.ajax.runAction(
					'catalog.storeSelector.createStore',
					{
						json: {name: text}
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
				}).catch((response) => {
					ErrorNotifier.showErrors(response.errors);
				});
			};
		}

		static getTitle()
		{
			return BX.message('SELECTOR_COMPONENT_PICK_STORE_2');
		}
	}

	this.CatalogStoreSelector = CatalogStoreSelector;
})();
