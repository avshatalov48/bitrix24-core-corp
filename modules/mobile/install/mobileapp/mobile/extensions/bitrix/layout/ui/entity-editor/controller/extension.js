(() => {
	const Type = {
		PRODUCT_LIST: 'product_list',
	};

	/**
	 * @class EntityEditorControllerFactory
	 */
	class EntityEditorControllerFactory
	{
		static create({type, controlId, settings})
		{
			if (type === Type.PRODUCT_LIST)
			{
				return new EntityEditorProductController({controlId, settings, type});
			}

			return null;
		}
	}

	jnexport(EntityEditorControllerFactory)
})();
