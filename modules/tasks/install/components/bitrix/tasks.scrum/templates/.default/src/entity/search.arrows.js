import {Dom, Tag, Event} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Item} from '../item/item';

import '../css/search.arrows.css';

type Params = {
	currentPosition: number,
	list: Set<Item>
}

export class SearchArrows extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.SearchArrows');

		this.list = params.list;

		this.currentPosition = parseInt(params.currentPosition, 10);

		this.node = null;
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum__item--nav-linked tasks-scrum__scope">
				<div class="tasks-scrum__item--nav-linked-close"></div>
				<div class="tasks-scrum__item--nav-linked-block">
					<div class="tasks-scrum__item--nav-linked-prev"></div>
					<div class="tasks-scrum__item--nav-linked-num-container --visible">
					<div class="tasks-scrum__item--nav-linked-num">${this.currentPosition}/${this.list.size}</div>
					</div>
					<div class="tasks-scrum__item--nav-linked-next"></div>
				</div>
			</div>
		`;

		const closeBtn = this.node.querySelector('.tasks-scrum__item--nav-linked-close');
		const prevBtn = this.node.querySelector('.tasks-scrum__item--nav-linked-prev');
		const nextBtn = this.node.querySelector('.tasks-scrum__item--nav-linked-next');

		Event.bind(closeBtn, 'click', this.onClose.bind(this));
		Event.bind(prevBtn, 'click', this.onPrev.bind(this));
		Event.bind(nextBtn, 'click', this.onNext.bind(this));

		return this.node;
	}

	getNode(): HTMLElement
	{
		return this.node;
	}

	updateCurrentPosition(value: number)
	{
		this.currentPosition = parseInt(value, 10);

		if (this.node)
		{
			this.node.querySelector('.tasks-scrum__item--nav-linked-num')
				.textContent = `${this.currentPosition}/${this.list.size}`
			;
		}
	}

	remove()
	{
		Dom.remove(this.node);

		this.node = null;
	}

	onClose()
	{
		this.emit('close');
	}

	onPrev()
	{
		this.emit('prev');
	}

	onNext()
	{
		this.emit('next');
	}
}
