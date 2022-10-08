import {Tag, Loc, Text, Dom, Type} from 'main.core';

export type StatsData = {
	average: number,
	maximum: number,
	minimum: number
}

export class Stats
{
	constructor(data: StatsData)
	{
		this.data = data;

		this.node = null;
	}

	renderTo(rootNode: HTMLElement)
	{
		this.node = this.build();

		Dom.append(this.node, rootNode);
	}

	render(data?: StatsData): HTMLElement
	{
		if (!Type.isUndefined(data))
		{
			this.data = data;
		}

		if (this.node)
		{
			this.sync(this.build(), this.node);
		}
		else
		{
			this.node = this.build();
		}

		return this.node;
	}

	build(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-sprint-team-speed-stats-container">
				<div class="tasks-scrum-sprint-team-speed-stats-row">
					<div>${Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_STATS_AVERAGE_LABEL')}</div>
					<div>${Text.encode(this.data.average)}</div>
				</div>
				<div class="tasks-scrum-sprint-team-speed-stats-row">
					<div>${Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_STATS_MAX_LABEL')}</div>
					<div>${Text.encode(this.data.maximum)}</div>
				</div>
				<div class="tasks-scrum-sprint-team-speed-stats-row">
					<div>${Loc.getMessage('TASKS_SCRUM_TEAM_SPEED_STATS_MIN_LABEL')}</div>
					<div>${Text.encode(this.data.minimum)}</div>
				</div>
			</div>
		`;
	}

	showLoader()
	{
		if (this.node)
		{
			Dom.addClass(this.node, '--loader');
		}
	}

	// todo move it to Dom library
	sync (virtualNode: HTMLElement, realNode: HTMLElement)
	{
		if (virtualNode.attributes)
		{
			Array.from(virtualNode.attributes)
				.forEach((attr) => {
					if (realNode.getAttribute(attr.name) !== attr.value)
					{
						realNode.setAttribute(attr.name, attr.value);
					}
				})
			;
		}

		if (virtualNode.nodeValue !== realNode.nodeValue)
		{
			realNode.nodeValue = virtualNode.nodeValue;
		}

		// Sync child nodes
		const virtualChildren = virtualNode.childNodes;
		const realChildren = realNode.childNodes;

		for (let k = 0; k < virtualChildren.length || k < realChildren.length; k++)
		{
			const virtual = virtualChildren[k];
			const real = realChildren[k];

			// Remove
			if (
				virtual === undefined
				&& real !== undefined
			)
			{
				realNode.remove(real);
			}
			// Update
			if (
				virtual !== undefined
				&& real !== undefined
				&& virtual.tagName === real.tagName
			)
			{
				this.sync(virtual, real);
			}
			// Replace
			if (
				virtual !== undefined
				&& real !== undefined
				&& virtual.tagName !== real.tagName
			)
			{
				const newReal = this.createRealNodeByVirtual(virtual);

				this.sync(virtual, newReal);

				Dom.replace(real, newReal);
			}
			// Add
			if (
				virtual !== undefined
				&& real === undefined
			)
			{
				const newReal = this.createRealNodeByVirtual(virtual);

				this.sync(virtual, newReal);

				Dom.append(newReal, realNode);
			}
		}
	}

	// todo move it to Dom library
	createRealNodeByVirtual(virtual: HTMLElement): HTMLElement
	{
		if (virtual.nodeType === Node.TEXT_NODE)
		{
			return document.createTextNode('');
		}

		return document.createElement(virtual.tagName);
	}

}