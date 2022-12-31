(() => {
	const Type = {
		PRODUCT_LIST: 'product_list',
	};

	/**
	 * @class EntityEditorControllerFactory
	 */
	class EntityEditorControllerFactory
	{
		static create(props)
		{
			const { type } = props;

			if (type === Type.PRODUCT_LIST)
			{
				return new EntityEditorProductController(props);
			}

			return null;
		}
	}

	jnexport(EntityEditorControllerFactory);
})();
