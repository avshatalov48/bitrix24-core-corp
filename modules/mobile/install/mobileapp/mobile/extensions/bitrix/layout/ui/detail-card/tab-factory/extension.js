(() => {
	const Type = {
		EDITOR: 'editor',
		PRODUCT: 'product'
	}

	class TabFactory
	{
		static create(type, config)
		{
			let tab;
			switch (type)
			{
				case Type.EDITOR:
					tab = EditorTab;
					break;
				case Type.PRODUCT:
					tab = ProductTab;
					break;
			}

			if (!tab)
			{
				throw new Error(`Tab implementation {${type}} not found.`);
			}

			return new tab(config);
		}
	}

	this.TabFactory = TabFactory;
	this.TabFactory.Type = Type;
})();
