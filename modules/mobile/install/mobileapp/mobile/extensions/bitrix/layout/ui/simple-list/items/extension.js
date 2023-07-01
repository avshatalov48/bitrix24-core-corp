(() => {
	const Type = {
		Base: 'Base',
		Kanban: 'Kanban',
		Terminal: 'Terminal',
		LoadingElement: 'LoadingElement',
		EmptySpace: 'EmptySpace',
	}

	class ListItemsFactory
	{
		static create(type, data)
		{
			if (type === Type.Base)
			{
				return new ListItems.Base(data);
			}

			if (type === Type.Kanban)
			{
				return new ListItems.Kanban(data);
			}

			if (type === Type.Terminal)
			{
				return new ListItems.Terminal(data);
			}

			if (type === Type.LoadingElement)
			{
				return new ListItems.LoadingElement();
			}

			if (type === Type.EmptySpace)
			{
				const defaultHeight = (Application.getPlatform() === 'android' ? 0 : 20);
				const height = (data && data.item && data.item.height) ? data.item.height : defaultHeight;
				return View(
					{
						style: {
							height,
							backgroundColor: '#f5f7f8',
						},
					},
					Text({
						style: {
							height,
						},
						text: '', // empty View not render in Android
					}),
				);
			}
		}
	}

	this.ListItemsFactory = ListItemsFactory;
	this.ListItemsFactory.Type = Type;
})();
