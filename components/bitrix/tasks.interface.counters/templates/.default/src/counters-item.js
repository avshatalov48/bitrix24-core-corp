import { Tag, Event, Type, Loc, Dom } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { sendData } from "ui.analytics";
import {Counters} from "./entry";

export default class CountersItem
{
	constructor(options)
	{
		this.count = options.count;
		this.name = options.name;
		this.type = options.type;
		this.color = options.color;
		this.filterField = options.filterField;
		this.filterValue = options.filterValue;
		this.filter = options.filter;

		this.$container = null;
		this.$innerContainer = null;
		this.$remove = null;
		this.$counter = null;

		this.bindEvents();
	}

	bindEvents()
	{
		EventEmitter.subscribe('BX.Tasks.Counters:active', (param)=> {
			this !== param.data ? this.unActive() : null;
		});
	}

	getCounter()
	{
		if (!this.$counter)
		{
			const count = this.count > 99 ? '99+' : this.count;
			this.$counter = Tag.render`
				<div class="tasks-counters--item-counter-num ${this.getCounterColor()}">
					${this.getInnerCounter(count)}
				</div>
			`;
		}

		return this.$counter;
	}

	getInnerCounter(counter: number)
	{
		if (!this.$innerContainer)
		{
			this.$innerContainer = Tag.render`
				<div class="tasks-counters--item-counter-num-text --stop --without-animate">${counter}</div>		
			`;
		}

		return this.$innerContainer;
	}

	animateCounter(start, value)
	{
		if (start > 99 && value > 99)
		{
			return;
		}

		if (value > 99)
		{
			value = '99+';
		}

		if (start > 99)
		{
			start = 99;
		}

		if (value === 0)
		{
			this.getContainer().classList.add('--fade');
		}

		if (value > 0)
		{
			this.getContainer().classList.remove('--fade');
		}

		Dom.clean(this.getCounter());
		this.getInnerCounter().innerHTML = value;
		this.getCounter().appendChild(this.getInnerCounter());
		this.getCounter().classList.remove('--update');
		this.getCounter().classList.remove('--update-multi');
	}

	getCounterColor()
	{
		if (!this.color)
		{
			return null;
		}

		return `--${this.color}`;
	}

	updateCount(param: number)
	{
		if(this.count === param)
			return;

		this.animateCounter(this.count, param);

		this.count = param;
	}

	getRemove()
	{
		if(!this.$remove)
		{
			this.$remove = Tag.render`
				<div class="tasks-counters--item-counter-remove"></div>
			`;
		}

		return this.$remove;
	}

	fade()
	{
		this.getContainer().classList.add('--fade');
	}

	unFade()
	{
		this.getContainer().classList.remove('--fade');
	}

	active(node: HTMLElement)
	{
		const targetNode = Type.isDomNode(node) ? node : this.getContainer();
		targetNode.classList.add('--hover');
		EventEmitter.emit('BX.Tasks.Counters:active', this);
	}

	unActive(node: HTMLElement)
	{
		const targetNode = Type.isDomNode(node) ? node : this.getContainer();
		targetNode.classList.remove('--hover');
		EventEmitter.emit('BX.Tasks.Counters:unActive', this);
	}

	adjustClick()
	{
		EventEmitter.emit('Tasks.Toolbar:onItem', {counter: this});

		if (this.$container.classList.contains('--hover'))
		{
			this.unActive();
		}
		else
		{
			this.active();
			this.sendAnalytics();
		}
	}

	getPopupMenuItemContainer()
	{
		const title = Loc.getMessage('TASKS_COUNTER_OTHER_TASKS').replace('#TITLE#', this.name.toLowerCase());

		return Tag.render`
			<div>
				<div class="task-counters--popup-item">
					<span class="tasks-counters--item-counter-num ${this.getCounterColor()}">${this.count}</span>
					<span class="task-counters--popup-item-text">${title}</span>
				</div>
			</div>
		`;
	}

	getContainer(param): HTMLElement
	{
		if(!this.$container)
		{
			this.$container = Tag.render`
				<div class="tasks-counters--item-counter ${Number(this.count) === 0 ? ' --fade' : ''}">
					<div class="tasks-counters--item-counter-wrapper">
						${this.getCounter()}
						<div class="tasks-counters--item-counter-title">${this.name}</div>
						${this.getRemove()}
					</div>
				</div>
			`;

			if (this.filter.isFilteredByFieldValue(this.filterField, this.filterValue))
			{
				this.active(this.$container);
			}

			Event.bind(this.$container, 'click', this.adjustClick.bind(this));
		}

		return this.$container;
	}

	sendAnalytics()
	{
		sendData({
			tool: 'tasks',
			category: 'task_operations',
			type: 'task',
			event: this.getAnalyticsEvent(),
			c_section: this.getAnalyticsSection(),
			c_element: this.getAnalyticsElement(),
		});
	}

	getAnalyticsEvent()
	{
		if (Counters.counterTypes.expired.includes(this.type))
		{
			return 'overdue_counters_on';
		}

		return 'comments_counters_on';
	}

	getAnalyticsSection()
	{
		if (Counters.counterTypes.scrum.includes(this.type))
		{
			return 'scrum';
		}

		if (Counters.counterTypes.project.includes(this.type))
		{
			return 'project';
		}

		return 'tasks';
	}

	getAnalyticsElement()
	{
		if (Counters.counterTypes.expired.includes(this.type))
		{
			return 'overdue_counters_filter';
		}

		return 'comments_counters_filter';
	}
}