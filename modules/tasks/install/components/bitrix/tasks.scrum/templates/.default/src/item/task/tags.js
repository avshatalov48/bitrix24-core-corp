import {Event, Tag, Text, Type} from 'main.core';
import {EventEmitter} from 'main.core.events';

export class Tags extends EventEmitter
{
	constructor(tags?: Array<string>)
	{
		super(tags);

		this.setEventNamespace('BX.Tasks.Scrum.Item.Tags');

		this.tags = (Type.isArray(tags) ? tags : []);

		this.node = null;
	}

	render(): HTMLElement|Array<HTMLElement>
	{
		if (this.tags.length)
		{
			this.node = Tag.render`
				${this.tags.map((tag) => {
					return Tag.render`<div class="tasks-scrum__item--hashtag --visible">#${Text.encode(tag)}</div>`;
				})}
			`;
		}
		else
		{
			this.node = Tag.render`<div class="tasks-scrum__item--hashtag"></div>`
		}

		if (Type.isArray(this.node))
		{
			this.node.forEach((node: HTMLElement) => {
				Event.bind(node, 'click', this.onClick.bind(this));
			});
		}
		else
		{
			Event.bind(this.node, 'click', this.onClick.bind(this));
		}

		return this.node;
	}

	getNode(): ?HTMLElement|?Array
	{
		return this.node;
	}

	getValue(): Array<string>
	{
		return this.tags;
	}

	onClick(event)
	{
		this.emit('click', event.target.textContent.substring(1));
	}

	isEqualTags(tags: Tags): boolean
	{
		return JSON.stringify(this.getValue()) === JSON.stringify(tags.getValue());
	}
}