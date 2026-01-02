import { Dom, Tag, Type } from 'main.core';
import { PULL } from 'pull.client';
import { EventEmitter } from 'main.core.events';

import './work-time-state-icon.css';

const State = Object.freeze({
	Closed: 'CLOSED',
	Opened: 'OPENED',
	Paused: 'PAUSED',
	Expired: 'EXPIRED',
});

const CommandType = Object.freeze({
	Start: 'start',
	Pause: 'pause',
	Continue: 'continue',
	Reopen: 'relaunch',
	Stop: 'stop',
});

type PullData = {
	command: string,
	params: {
		info: {
			state: string,
			action: string,
		}
	},
};

type Params = {
	state: string,
	action: string,
};

export class WorkTimeStateIcon extends EventEmitter
{
	#params: Params;

	#layout: {
		node: ?HTMLElement,
		iconNode: ?HTMLElement,
	};

	constructor(params: Params)
	{
		super();
		this.setEventNamespace('BX.Timeman.WorkTimeStateIcon');
		this.#params = params;

		this.#layout = {};

		this.#subscribeToPull();
	}

	renderTo(container: HTMLElement): void
	{
		if (Type.isDomNode(container))
		{
			Dom.append(this.render(), container);
		}
	}

	render(): HTMLElement
	{
		const iconClass = this.#getIconClass();
		const style = this.#getIconStyle();

		const { node, icon } = Tag.render`
			<div ref="node" class="work-time-state" style="${style}">
				<div ref="icon" class="work-time-state-icon ${iconClass}"></div>
			</div>
		`;

		this.#layout.node = node;
		this.#layout.iconNode = icon;

		return node;
	}

	#subscribeToPull(): void
	{
		PULL.subscribe({
			moduleId: 'timeman',
			callback: (data: PullData) => this.#handlePullEvent(data),
		});
	}

	#handlePullEvent(data: PullData): void
	{
		if (!this.#isValidCommand(data.command))
		{
			return;
		}

		this.#params.state = data.params.info.state;
		this.#params.action = data.params.info.action;
		this.emit('onUpdateState', this.#params);

		this.#updateIcon();
	}

	#isValidCommand(command: string): boolean
	{
		return Object.values(CommandType).includes(command);
	}

	#updateIcon(): void
	{
		if (this.#layout.node === null)
		{
			return;
		}

		const iconClass = this.#getIconClass();
		const style = this.#getIconStyle();

		this.#layout.iconNode.className = 'work-time-state-icon';
		if (iconClass)
		{
			Dom.addClass(this.#layout.iconNode, iconClass);
		}

		Dom.style(this.#layout.node, style);
	}

	#getIconClass(): string
	{
		const { state, action } = this.#params;

		if (state === State.Opened)
		{
			return '';
		}

		if (state === State.Closed && action === 'OPEN')
		{
			return '--start';
		}

		if (state === State.Paused)
		{
			return '--pause';
		}

		if (state === State.Closed && action === 'REOPEN')
		{
			return '--check';
		}

		if (state === State.Expired)
		{
			return '--warn';
		}

		return '';
	}

	#getIconStyle(): ?string
	{
		const { state } = this.#params;

		if (state === State.Opened)
		{
			return 'display: none';
		}

		return null;
	}
}
