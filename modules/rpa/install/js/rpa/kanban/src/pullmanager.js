import {Text, Event, Type} from 'main.core';
import {Kanban as RpaKanban} from 'rpa.kanban';

export class PullManager
{
	grid;

	constructor(grid)
	{
		this.eventIds = new Set();
		if(grid instanceof RpaKanban.Grid)
		{
			this.grid = grid;
			if(Type.isArray(this.grid.getData().eventIds))
			{
				this.grid.getData().eventIds.forEach((eventId) =>
				{
					this.eventIds.add(eventId);
				});
			}
			if(Type.isString(grid.getData().pullTag) && Type.isString(grid.getData().moduleId) && grid.getData().userId > 0)
			{
				this.init();
			}
		}
	}

	registerEventId(eventId: string)
	{
		this.eventIds.add(eventId);
	}

	registerRandomEventId(): string
	{
		const eventId = Text.getRandom();
		this.registerEventId(eventId);
		return eventId;
	}

	init()
	{
		Event.ready(() =>
		{
			const Pull = BX.PULL;
			if(!Pull)
			{
				console.error('pull is not initialized');
				return;
			}
			if(Type.isString(this.grid.getData().pullTag))
			{
				Pull.subscribe({
					moduleId: this.grid.getData().moduleId,
					command: this.grid.getData().pullTag,
					callback: (params) =>
					{
						if(Type.isString(params.eventName))
						{
							if(Type.isString(params.eventId))
							{
								if(this.eventIds.has(params.eventId))
								{
									return;
								}
							}
							if(params.eventName.indexOf('ITEMUPDATED' + this.grid.getTypeId()) === 0 && Type.isPlainObject(params.item))
							{
								this.onPullItemUpdated(params);
							}
							else if(params.eventName === ('ITEMADDED' + this.grid.getTypeId()) && Type.isPlainObject(params.item))
							{
								this.onPullItemAdded(params);
							}
							else if(params.eventName.indexOf('ITEMDELETED' + this.grid.getTypeId()) === 0 && Type.isPlainObject(params.item))
							{
								this.onPullItemDeleted(params);
							}
							else if(params.eventName === ('STAGEADDED' + this.grid.getTypeId()) && Type.isPlainObject(params.stage))
							{
								this.onPullStageAdded(params);
							}
							else if(params.eventName.indexOf('STAGEUPDATED' + this.grid.getTypeId()) === 0 && Type.isPlainObject(params.stage))
							{
								this.onPullStageUpdated(params);
							}
							else if(params.eventName.indexOf('STAGEDELETED' + this.grid.getTypeId()) === 0 && Type.isPlainObject(params.stage))
							{
								this.onPullStageDeleted(params);
							}
							else if(params.eventName === ('ROBOTADDED' + this.grid.getTypeId()) && Type.isPlainObject(params.robot))
							{
								this.onPullRobotAdded(params);
							}
							else if(params.eventName.indexOf('ROBOTUPDATED' + this.grid.getTypeId()) === 0 && Type.isPlainObject(params.robot))
							{
								this.onPullRobotUpdated(params);
							}
							else if(params.eventName.indexOf('ROBOTDELETED' + this.grid.getTypeId()) === 0 && Type.isPlainObject(params.robot))
							{
								this.onPullRobotDeleted(params);
							}
							else if(params.eventName.indexOf('TYPEUPDATED' + this.grid.getTypeId()) === 0)
							{
								this.onPullTypeUpdated();
							}
						}
					},
				});
				Pull.extendWatch(this.grid.getData().pullTag);
			}

			if(Type.isString(this.grid.getData().taskCountersPullTag))
			{
				Pull.subscribe({
					moduleId: this.grid.getData().moduleId,
					command: this.grid.getData().taskCountersPullTag,
					callback: (params) =>
					{
						if(Type.isString(params.eventId))
						{
							if(this.eventIds.has(params.eventId))
							{
								return;
							}
						}
						if(this.grid.getTypeId() === Text.toInteger(params.typeId))
						{
							this.onPullCounters(params);
						}
					}
				});

				Pull.extendWatch(this.grid.getData().taskCountersPullTag);
			}
		});
	}

	onPullItemUpdated(params)
	{
		this.grid.addUsers(params.item.users);
		const item = this.grid.getItem(params.item.id);
		if(item)
		{
			item.setData(params.item);
			this.grid.insertItem(item);
		}
		else
		{
			const column = this.grid.getColumn(params.item.stageId);
			if(column && (column.isCanMoveFrom() || column.canAddItems()))
			{
				this.onPullItemAdded(params);
			}
		}
	}

	onPullItemAdded(params)
	{
		const itemData = params.item;
		this.grid.addUsers(itemData.users);

		const oldItem = this.grid.getItem(itemData.id);
		if(oldItem)
		{
			return;
		}
		const item = new RpaKanban.Item({
			id: itemData.id,
			columnId: itemData.stageId,
			name: itemData.name,
			data: itemData,
		});
		item.setGrid(this.grid);
		this.grid.items[item.getId()] = item;

		const column = this.grid.getColumn(item.getStageId());
		if(
			column
			//&& this.grid.getFirstColumn() !== column
			&& (column.isCanMoveFrom() || column.canAddItems()))
		{
			column.addItem(item, column.getFirstItem());
		}
	}

	onPullItemDeleted(params: Object)
	{
		if(!Type.isPlainObject(params.item))
		{
			return;
		}

		this.grid.removeItem(params.item.id);
	}

	onPullStageAdded(params)
	{
		this.grid.onApplyFilter();
	}

	onPullStageUpdated(params)
	{
		const column = this.grid.getColumn(params.stage.id);
		if(column)
		{
			column.update(params);
		}
	}

	onPullStageDeleted(params)
	{
		this.grid.removeColumn(params.stage.id);
	}

	onPullRobotAdded(params: Object)
	{
		this.onPullRobotChanged(params.robot.stageId);
	}

	onPullRobotUpdated(params: Object)
	{
		this.onPullRobotChanged(params.robot.stageId);
	}

	onPullRobotDeleted(params: Object)
	{
		if(Type.isPlainObject(params.robot) && Type.isString(params.robot.robotName))
		{
			const column = this.grid.getColumn(params.robot.stageId);
			if(column)
			{
				column.setTasks(column.getTasks().filter((filteredTask) =>
				{
					return (filteredTask.robotName !== params.robot.robotName);
				}));
				column.rerenderSubtitle();
			}
		}
	}

	onPullRobotChanged(stageId: number)
	{
		const column = this.grid.getColumn(stageId);
		if(column)
		{
			column.loadTasks().then(() =>
			{
				column.rerenderSubtitle();
			}).catch(() =>
			{

			});
		}
	}

	onPullCounters(params: {
		typeId: number,
		itemId: number,
		counter: string,
		tasksFaces: ?{
			completed: [],
			running: [],
			all: [],
		},
	})
	{
		let typeId = Text.toInteger(params.typeId);
		let itemId = Text.toInteger(params.itemId);
		if(typeId !== this.grid.getTypeId())
		{
			return;
		}
		const item = this.grid.getItem(itemId);
		if(item)
		{
			let currentCounter = item.getTasksCounter();
			if(params.counter === '+1')
			{
				currentCounter++;
			}
			else if(params.counter === '-1')
			{
				currentCounter--;
			}
			item.setTasksCounter(currentCounter);
			if(Type.isPlainObject(params.tasksFaces))
			{
				item.setTasksParticipants(params.tasksFaces);
			}
			item.render();
		}
	}

	onPullTypeUpdated()
	{
		this.grid.onApplyFilter();
	}
}