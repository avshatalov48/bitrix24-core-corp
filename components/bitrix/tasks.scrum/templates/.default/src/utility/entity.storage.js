import {Entity} from '../entity/entity';
import {Backlog} from '../entity/backlog/backlog';
import {Sprint} from '../entity/sprint/sprint';

export class EntityStorage
{
	constructor()
	{
		this.backlog = null;
		this.sprints = new Map();
	}

	addBacklog(backlog: Backlog)
	{
		if (!(backlog instanceof Backlog))
		{
			throw new Error('EntityStorage: Backlog is in wrong format');
		}

		this.backlog = backlog;
	}

	addSprint(sprint: Sprint)
	{
		this.sprints.set(sprint.getId(), sprint);
	}

	removeSprint(sprintId: number)
	{
		this.sprints.delete(sprintId);
	}

	getBacklog(): Backlog
	{
		if (this.backlog === null)
		{
			throw new Error('EntityStorage: Backlog not found');
		}

		return this.backlog;
	}

	getSprints(): Map<number, Sprint>
	{
		return this.sprints;
	}

	getSprintsAvailableForFilling(entityFrom: Entity): Set<Sprint>
	{
		const sprints = new Set();

		this.sprints.forEach((sprint: Sprint) => {
			if (!sprint.isCompleted() && entityFrom.getId() !== sprint.getId())
			{
				sprints.add(sprint);
			}
		});

		return sprints;
	}

	getAllEntities(): Map<number, Entity>
	{
		const entities = new Map();

		entities.set(this.backlog.getId(), this.backlog);

		[...this.sprints.values()].map((sprint) => entities.set(sprint.getId(), sprint));

		return entities;
	}

	getAllItems(): Map<number, Item>
	{
		let items = new Map(this.backlog.getItems());

		[...this.sprints.values()].map((sprint) => items = new Map([...items, ...sprint.getItems()]));

		return items;
	}

	recalculateItemsSort()
	{
		this.backlog.recalculateItemsSort();
		this.sprints.forEach((sprint) => sprint.recalculateItemsSort());
	}

	findEntityByEntityId(entityId: number): Entity
	{
		entityId = parseInt(entityId, 10);

		if (this.backlog.getId() === entityId)
		{
			return this.backlog;
		}

		return [...this.sprints.values()].find((sprint) => sprint.getId() === entityId);
	}

	findItemByItemId(itemId: number): Item
	{
		itemId = parseInt(itemId, 10);

		const backlogItems = this.backlog.getItems();
		if (backlogItems.has(itemId))
		{
			return backlogItems.get(itemId);
		}

		const sprint = [...this.sprints.values()].find((sprint) => sprint.getItems().has(itemId));
		if (sprint)
		{
			return sprint.getItems().get(itemId);
		}

		return null;
	}

	findItemBySourceId(sourceId: number): Item
	{
		sourceId = parseInt(sourceId, 10);

		let items = new Map(this.backlog.getItems());

		[...this.sprints.values()].map((sprint) => items = new Map([...items, ...sprint.getItems()]));

		return [...items.values()].find((item: Item) => item.getSourceId() === sourceId);
	}

	findEntityByItemId(itemId: number): Entity
	{
		itemId = parseInt(itemId, 10);

		const backlogItems = this.backlog.getItems();
		if (backlogItems.has(itemId))
		{
			return this.backlog;
		}

		return [...this.sprints.values()].find((sprint) => sprint.getItems().has(itemId));
	}
}