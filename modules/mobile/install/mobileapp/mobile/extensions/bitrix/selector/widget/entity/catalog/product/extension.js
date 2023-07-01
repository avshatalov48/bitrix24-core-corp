(() => {
	const require = (ext) => jn.require(ext);
	const { Loc } = require('loc');

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
			return Loc.getMessage('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_PRODUCT');
		}

		static getStartTypingWithCreationText()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_START_TYPING_TO_CREATE_PRODUCT');
		}

		static getSearchPlaceholderWithCreation()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_SEARCH_WITH_CREATION_PLACEHOLDER_PRODUCT');
		}

		static getSearchPlaceholderWithoutCreation()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_SEARCH_WITHOUT_CREATION_PLACEHOLDER_PRODUCT');
		}

		static isCreationEnabled()
		{
			return true;
		}

		static getCreateText()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_CREATE_PRODUCT');
		}

		static getCreatingText()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_CREATING_PRODUCT');
		}

		static canCreateWithEmptySearch()
		{
			return true;
		}

		static getCreateEntityHandler(providerOptions)
		{
			return (text) => {
				text = text || Loc.getMessage('SELECTOR_COMPONENT_NEW_PRODUCT_NAME');

				return BX.ajax.runAction(
					'catalog.productSelector.createProduct',
					{
						json: {
							fields: {
								'NAME': text,
								'IBLOCK_ID': providerOptions.iblockId,
								'CURRENCY': providerOptions.currency,
							},
						},
					},
				).then((response) => {
					if (response.data && response.data.id)
					{
						return {
							id: response.data.id,
							entityId: this.getEntityId(),
							title: text,
						};
					}

					return null;
				}).catch((response) => console.error(response));
			};
		}

		static getTitle()
		{
			return Loc.getMessage('SELECTOR_COMPONENT_PICK_PRODUCT_2');
		}

		static getSearchFields()
		{
			return [
				'PARENT_DETAIL_TEXT',
				'PARENT_PREVIEW_TEXT',
				'PARENT_SEARCH_PROPERTIES',
				'PARENT_NAME',
				'DETAIL_TEXT',
				'PREVIEW_TEXT',
				'SEARCH_PROPERTIES',
				...super.getSearchFields(),
			];
		}
	}

	this.CatalogProductSelector = CatalogProductSelector;
})();
