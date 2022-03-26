(() => {
	const Type = {
		Base: 'Base',
		LoadingElement: 'LoadingElement',
	}

	class ListItemsFactory
	{
		static create(type, data)
		{
			if (type === Type.Base)
			{
				return new ListItems.Base(data);
			}

			if (type === Type.LoadingElement)
			{
				return new ListItems.LoadingElement();
			}
		}
	}

	this.ListItemsFactory = ListItemsFactory;
	this.ListItemsFactory.Type = Type;
})();
