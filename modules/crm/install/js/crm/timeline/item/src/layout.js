export default class Layout
{
	#layout: Object = null;

	constructor(layout: Object)
	{
		this.#layout = layout;
	}

	asPlainObject(): Object
	{
		return Object.assign({}, this.#layout);
	}

	getFooterMenuItemById(id: string): ?Object
	{
		const items = this.#layout?.footer?.menu?.items ?? {};

		return items.hasOwnProperty(id) ? items.id : null;
	}

	addFooterMenuItem(menuItem: Object): void
	{
		this.#layout.footer = this.#layout.footer || {};
		this.#layout.footer.menu = this.#layout.footer.menu || {};
		this.#layout.footer.menu.items = this.#layout.footer.menu.items || {};
		this.#layout.footer.menu.items[menuItem.id] = menuItem;
	}
}
