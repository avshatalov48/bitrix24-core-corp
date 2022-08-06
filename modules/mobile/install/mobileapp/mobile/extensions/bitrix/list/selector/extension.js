(() => {
	class SelectorListAdapter
	{
		constructor(list)
		{
			this.list = list;
			this.selectorListener = () => {};

			this.sections = new Map();
		}

		setListener(listener)
		{
			this.selectorListener = listener;
		}

		on(eventName, eventHandler)
		{
			this.list.on(eventName, eventHandler);

			return this;
		}

		once(eventName, eventHandler)
		{
			this.list.once(eventName, eventHandler);

			return this;
		}

		off(eventName, eventHandler)
		{
			this.list.off(eventName, eventHandler);

			return this;
		}

		onScopeSelected(data)
		{
			this.selectorListener('onScopeChanged', data);
		}

		onUserTypeText(data)
		{
			this.selectorListener('onListFill', data);
		}

		onSearchItemSelected(data)
		{
			this.selectorListener('onItemSelected', {
				item: data,
			});
		}

		setTitle(title)
		{
			this.list.setTitle(title);
		}

		setScopes(scopes)
		{
			this.list.setSearchScopes(scopes);
		}

		setItems(items)
		{
			const displayedSections = new Set();

			items.forEach(item => {
				const itemSection = this.sections.get(item.sectionCode);
				displayedSections.add(itemSection);
			});

			this.list.setSearchResultItems(items, Array.from(displayedSections));
		}

		show()
		{
			return Promise.resolve();
		}

		close(callback)
		{
			this.list.setSearchScopes([]);

			callback();
		}

		setSelected(items)
		{

		}

		allowMultipleSelection(allow)
		{

		}

		setSections(sections)
		{
			sections.forEach(section => {
				this.sections.set(section.id, section);
			});
		}
	}

	window.SelectorListAdapter = SelectorListAdapter;
})();