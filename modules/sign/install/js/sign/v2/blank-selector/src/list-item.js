import { Tag, Dom } from 'main.core';

export class ListItem<T>
{
	#layout: HTMLElement;
	#props: T;
	#titleNode: HTMLElement;
	#descriptionNode: HTMLElement;

	constructor(props: T)
	{
		this.#titleNode = Tag.render`
			<span class="sign-blank-selector__list_item-title"></span>
		`;
		this.#descriptionNode = Tag.render`
			<span class="sign-blank-selector__list_item-info"></span>
		`;
		this.setProps(props);
		this.#layout = this.#createListItem();
	}

	#createListItem(): HTMLElement
	{
		const { title, description, modifier } = this.getProps();
		this.setTitle(title);
		this.setDescription(description);

		return Dom.create('div', {
			attrs: {
				className: `sign-blank-selector__list_item --${modifier}`,
			},
			children: [this.#titleNode, this.#descriptionNode],
		});
	}

	getLayout(): HTMLElement
	{
		return this.#layout;
	}

	setTitle(title: string = ''): void
	{
		this.#titleNode.textContent = title;
		this.#titleNode.title = title;
		this.setProps({
			...this.getProps(),
			title,
		});
	}

	setDescription(description: string = ''): void
	{
		this.#descriptionNode.textContent = description;
		this.#descriptionNode.title = description;
		this.setProps({
			...this.getProps(),
			description,
		});
	}

	getProps(): T
	{
		return this.#props;
	}

	setProps(props: T): void
	{
		this.#props = props;
	}
}
