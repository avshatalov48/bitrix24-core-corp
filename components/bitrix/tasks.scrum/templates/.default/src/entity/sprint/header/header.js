import {Tag, Dom} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {Sprint} from '../sprint';

import {Name} from './name';
import {Stats} from './stats';
import {Info} from './info/info';
import {Button} from './button';
import {Tick} from './tick';

export class Header extends EventEmitter
{
	constructor(sprint: Sprint)
	{
		super(sprint);

		this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header');

		this.sprint = sprint;

		this.node = null;

		this.name = null;
		this.stats = null;
		this.info = null;
		this.button = null;
		this.tick = null;
	}

	static buildHeader(sprint: Sprint): Header
	{
		const header = new Header(sprint);

		header.setName(sprint);

		if (sprint.isCompleted())
		{
			header.setStats(sprint);
		}
		else
		{
			header.setInfo(sprint);
			header.setButton(sprint);
		}

		header.setTick(sprint);

		return header;
	}

	setName(sprint: Sprint)
	{
		const name = new Name(sprint);

		if (sprint.isPlanned())
		{
			name.setDate(sprint);
		}

		if (sprint.isActive())
		{
			name.setStats(sprint);
		}

		if (this.name)
		{
			Dom.replace(this.name.getNode(), name.render());
		}

		this.name = name;

		this.name.subscribe('editClick', (baseEvent) => {
			this.emit('changeName', baseEvent.getData());
		});
		this.name.subscribe('removeSprint', () => this.emit('removeSprint'));
		this.name.subscribe('changeSprintDeadline', (baseEvent) => {
			this.emit('changeSprintDeadline', baseEvent.getData());
		});
	}

	getName(): Name
	{
		return this.name;
	}

	setStats(sprint: Sprint)
	{
		if (!sprint.isCompleted())
		{
			return;
		}

		const stats = new Stats(sprint);

		if (this.stats)
		{
			Dom.replace(this.stats.getNode(), stats.render());
		}

		this.stats = stats;
	}

	setInfo(sprint: Sprint)
	{
		const info = new Info(sprint);

		if (this.info)
		{
			Dom.replace(this.info.getNode(), info.render());
		}

		this.info = info;

		this.info.subscribe('showBurnDownChart', () => this.emit('showBurnDownChart'));
		this.info.subscribe(
			'showCreateMenu',
			(baseEvent: BaseEvent) => this.emit('showCreateMenu', baseEvent.getData())
		);
	}

	setButton(sprint: Sprint)
	{
		const button = new Button(sprint);

		if (this.button)
		{
			Dom.replace(this.button.getNode(), button.render());
		}

		this.button = button;

		this.button.subscribe('click', () => {
			if (this.sprint.isActive())
			{
				this.emit('completeSprint');
			}
			else if (this.sprint.isPlanned())
			{
				this.emit('startSprint');
			}
		});
	}

	setTick(sprint: Sprint)
	{
		this.tick = new Tick(sprint);

		this.tick.subscribe('click', () => {
			this.emit('toggleVisibilityContent');
		});
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum__content-header ${this.getHeaderClass()}">
				${this.name ? this.name.render() : ''}
				${this.stats ? this.stats.render() : ''}
				${this.info ? this.info.render() : ''}
				${this.button ? this.button.render() : ''}
				${this.tick ? this.tick.render(): ''}
			</div>
		`;

		return this.node;
	};

	getNode(): ?HTMLElement
	{
		return this.node;
	}

	getHeaderClass(): string
	{
		return 'tasks-scrum__content-header --' + this.sprint.getStatus();
	}

	activateEditMode()
	{
		Dom.addClass(this.getNode(), '--editing');
	}

	deactivateEditMode()
	{
		Dom.removeClass(this.getNode(), '--editing');
	}

	upTick()
	{
		if (!this.tick)
		{
			return;
		}

		this.tick.upTick();
	}

	downTick()
	{
		if (!this.tick)
		{
			return;
		}

		this.tick.downTick();
	}

	disableButton()
	{
		if (this.button)
		{
			this.button.disable();
		}
	}

	unDisableButton()
	{
		if (this.button)
		{
			this.button.unDisable();
		}
	}
}
