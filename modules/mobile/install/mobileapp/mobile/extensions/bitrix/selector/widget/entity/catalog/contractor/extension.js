(() => {
	/**
	 * @class CatalogContractorSelector
	 */
	class CatalogContractorSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'contractor';
		}

		static getContext()
		{
			return 'catalog-contractors';
		}

		static getStartTypingText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_CONTRACTOR');
		}

		static getStartTypingWithCreationText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_CREATE_CONTRACTOR');
		}

		static isCreationEnabled()
		{
			return true;
		}

		static getCreateText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATE_CONTRACTOR');
		}

		static getCreatingText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATING_CONTRACTOR');
		}

		static getCreateEntityHandler(providerOptions)
		{
			return (text) => {
				return BX.ajax.runAction(
					'catalog.contractor.createContractor',
					{
						data: {
							fields: {
								companyName: text
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
			return BX.message('SELECTOR_COMPONENT_PICK_CONTRACTOR_2');
		}
	}

	this.CatalogContractorSelector = CatalogContractorSelector;
})();
