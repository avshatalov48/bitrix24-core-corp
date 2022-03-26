(() => {
	/**
	 * @class EntitySelectorFactory.Type
	 */
	const Type = {
		SECTION: 'section',
		PRODUCT: 'product',
		STORE: 'store',
		CONTRACTOR: 'contractor',
		USER: 'user',
		PROJECT_TAG: 'project_tag'
	}

	/**
	 * @class EntitySelectorFactory
	 */
	class EntitySelectorFactory
	{
		static createByType(type, data)
		{
			if (type === Type.SECTION)
			{
				return CatalogSectionSelector.make(data);
			}

			if (type === Type.PRODUCT)
			{
				return CatalogProductSelector.make(data);
			}

			if (type === Type.STORE)
			{
				return CatalogStoreSelector.make(data);
			}

			if (type === Type.CONTRACTOR)
			{
				return CatalogContractorSelector.make(data);
			}

			if (type === Type.USER)
			{
				return SocialNetworkUserSelector.make(data);
			}

			if (type === Type.PROJECT_TAG)
			{
				return ProjectTagSelector.make(data);
			}

			return null;
		}
	}

	this.EntitySelectorFactory = EntitySelectorFactory;
	this.EntitySelectorFactory.Type = Type;
})();
