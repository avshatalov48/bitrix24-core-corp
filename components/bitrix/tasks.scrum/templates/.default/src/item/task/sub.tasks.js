import {Dom, Event, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Loader} from 'main.loader';

import {Item} from '../item';

export class SubTasks extends EventEmitter
{
	constructor(parentItem: Item)
	{
		super();

		this.parentItem = parentItem;

		this.list = new Map();

		this.node = null;
	}

	render()
	{
		this.node = Tag.render`<div class="tasks-scrum__item-sub-tasks"></div>`;

		Event.bind(this.node, 'transitionend', this.onTransitionEnd.bind(this, this.node));

		return this.node;
	}

	getNode(): ?HTMLElement
	{
		return this.node;
	}

	removeYourself()
	{
		Dom.remove(this.node);

		this.node = null;
	}

	isEmpty(): boolean
	{
		return this.list.size === 0;
	}

	getParentItem(): Item
	{
		return this.parentItem;
	}

	addTask(item: Item)
	{
		this.list.set(item.getId(), item);
	}

	getList(): Map<number, Item>
	{
		return this.list;
	}

	cleanTasks()
	{
		this.list.forEach((item: Item) => {
			Dom.remove(item.getNode());
		});

		this.list.clear();
	}

	show()
	{
		if (this.list.size)
		{
			this.hideLoader();

			this.renderSubTasks();
		}
		else
		{
			this.showLoader();
		}

		this.getNode().style.height = `${ this.getNode().scrollHeight }px`;
	}

	hide()
	{
		this.hideLoader();

		this.getNode().style.height = `${ this.getNode().scrollHeight }px`;
		this.getNode().clientHeight;
		this.getNode().style.height = '0';
	}

	isShown(): boolean
	{
		return this.node !== null;
	}

	renderSubTasks()
	{
		this.node.innerHTML = '';

		this.list.forEach((item: Item) => {
			Dom.append(item.render(), this.getNode());
		});
	}

	showLoader()
	{
		if (this.loader)
		{
			this.loader.show();

			return;
		}

		const listPosition = Dom.getPosition(this.getNode());

		this.loader = new Loader({
			target: this.getNode(),
			size: 60,
			mode: 'inline',
			color: 'rgba(82, 92, 105, 0.9)',
			offset: {
				top: `12px`,
				left: `${(listPosition.width / 2 - 30)}px`
			}
		});

		this.loader.show();
	}

	hideLoader()
	{
		if (this.loader)
		{
			this.loader.hide();
		}
	}

	onTransitionEnd(node: HTMLElement)
	{
		const isHide = (node.style.height === '0px');

		if (isHide)
		{
			this.removeYourself();
		}
		else
		{
			node.style.height = 'auto';
		}
	}
}
