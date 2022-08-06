import {Loc, Tag} from 'main.core';
import {Entity} from './entity';

export class EmptySearchStub
{
	constructor(entity: Entity)
	{
		this.entity = entity;

		this.node = null;
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum__content-empty --no-results">
				${this.getStubText()}
			</div>
		`;

		return this.node;
	}

	getNode(): ?HTMLElement
	{
		return this.node;
	}

	getStubText(): string
	{
		if (this.entity.isBacklog())
		{
			return Loc.getMessage('TASKS_SCRUM_EMPTY_SEARCH_STUB_BACKLOG');
		}
		else
		{
			return Loc.getMessage('TASKS_SCRUM_EMPTY_SEARCH_STUB_SPRINT');
		}
	}
}