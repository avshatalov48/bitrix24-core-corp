import { Tag, Event, Type, Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';

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
		if(!this.$counter)
		{
			const count = this.count > 99 ? '99+' : this.count;
			this.$counter = Tag.render`
				<div class="tasks-counters--item-counter-num ${this.getCounterColor()}">
					<div class="tasks-counters--item-counter-num-text --stop --without-animate">${count}</div>					
				</div>
			`;
		}

		return this.$counter;
	}

	getCounterColor()
	{
		if (!this.color)
		{
			return null;
		}

		return `--${this.color}`;
	}

	animateCounter(start, value)
	{
		if(start > 99 && value > 99)
			return;

		value > 99
			? value = 99
			: null;

		if(start > 99)
			start = 99;

		let duration = start - value;
		if(duration < 0)
			duration = duration * -1;

		this.$counter.innerHTML = '';
		this.getCounter().classList.remove('--update');
		this.getCounter().classList.remove('--update-multi');

		if(duration > 5)
		{
			setTimeout(()=> {
				this.getCounter().style.animationDuration = (duration * 50) + 'ms';
				this.getCounter().classList.add('--update-multi');
			});
		}
		const timer = setInterval(()=> {
			value < start
				? start--
				: start++;

			const node = Tag.render`
				<div class="tasks-counters--item-counter-num-text ${value < start ? '--decrement' : ''}">${start}</div>
			`;

			if(start === value)
			{
				node.classList.add('--stop');

				if(duration < 5)
					this.getCounter().classList.add('--update');

				clearInterval(timer);
				start === 0 ? this.fade() : this.unFade();
			}

			if(start !== value)
			{
				Event.bind(node, 'animationend', ()=> {
					node.parentNode.removeChild(node);
				});
			}
			this.$counter.appendChild(node);
		}, 50);
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

		this.$container.classList.contains('--hover')
			? this.unActive()
			: this.active()
		;
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
}