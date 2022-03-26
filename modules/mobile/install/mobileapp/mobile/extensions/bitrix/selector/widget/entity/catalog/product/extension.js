(() => {
	/**
	 * @class CatalogProductSelector
	 */
	class CatalogProductSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'product';
		}

		static getContext()
		{
			return 'catalog-products';
		}

		static getStartTypingText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_PRODUCT');
		}

		static isCreationEnabled()
		{
			return true;
		}

		static getCreateText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATE_PRODUCT');
		}

		static getCreatingText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATING_PRODUCT');
		}

		static getCreateEntityHandler(providerOptions)
		{
			return (text) => {
				return BX.ajax.runAction(
					'catalog.productSelector.createProduct',
					{
						json: {
							fields: {
								'NAME': text,
								'IBLOCK_ID': providerOptions.iblockId,
								'CURRENCY': providerOptions.currency
							}
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
			return BX.message('SELECTOR_COMPONENT_PICK_PRODUCT_2');
		}
	}

	this.CatalogProductSelector = CatalogProductSelector;
})();
