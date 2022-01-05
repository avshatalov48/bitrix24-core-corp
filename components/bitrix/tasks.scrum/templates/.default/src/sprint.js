import {Dom, Event, Loc, Tag, Text, Type} from 'main.core';
import {Entity} from './entity';
import {Item} from './item';
import {SprintHeader} from './sprint.header';

import './css/sprint.css';

type sprintParams = {
	id: number,
	name?: string,
	sort: number,
	dateStart?: number,
	dateEnd?: number,
	defaultSprintDuration: number,
	storyPoints?: string,
	completedStoryPoints?: string,
	unCompletedStoryPoints?: string,
	completedTasks?: number,
	unCompletedTasks?: number,
	status?: string,
	items?: Array
};

export class Sprint extends Entity
{
	constructor(sprintData: sprintParams = {})
	{
		super(sprintData);

		this.setEventNamespace('BX.Tasks.Scrum.Sprint');

		this.name = sprintData.name;
		this.dateStart = Type.isInteger(sprintData.dateStart) ?
			parseInt(sprintData.dateStart, 10) : 0;
		this.dateEnd = Type.isInteger(sprintData.dateEnd) ?
			parseInt(sprintData.dateEnd, 10) : 0;
		this.status = (sprintData.status ? sprintData.status : 'planned');
		this.sort = Type.isInteger(sprintData.sort) ?
			parseInt(sprintData.sort, 10) : 1;

		this.completedStoryPoints = Type.isNumber(sprintData.completedStoryPoints) ?
			parseFloat(sprintData.completedStoryPoints) : '';
		this.unCompletedStoryPoints = Type.isNumber(sprintData.unCompletedStoryPoints) ?
			parseFloat(sprintData.unCompletedStoryPoints) : '';
		this.completedTasks = Type.isInteger(sprintData.completedTasks) ?
			parseInt(sprintData.completedTasks, 10) : 0;
		this.unCompletedTasks = Type.isInteger(sprintData.unCompletedTasks) ?
			parseInt(sprintData.unCompletedTasks, 10) : 0;

		this.defaultSprintDuration = Type.isInteger(sprintData.defaultSprintDuration) ?
			parseInt(sprintData.defaultSprintDuration, 10) : 0;

		if (sprintData.items)
		{
			sprintData.items.forEach((itemData) => {
				const item = new Item(itemData);
				item.setDisableStatus(this.isDisabled());
				this.items.set(item.itemId, item);
			});
		}

		this.sprintHeader = new SprintHeader(this);

		this.initStyle();
	}

	initStyle()
	{
		this.sprintHeader.initStyle();
	}

	isActive(): Boolean
	{
		return (this.status === 'active');
	}

	isPlanned(): Boolean
	{
		return (this.status === 'planned');
	}

	isCompleted(): Boolean
	{
		return (this.status === 'completed');
	}

	isDisabled(): Boolean
	{
		return (this.isCompleted());
	}

	isExpired(): Boolean
	{
		return (new Date(this.dateEnd * 1000).getTime() < Date.now());
	}

	hasInput(): Boolean
	{
		return !this.isDisabled();
	}

	setItem(newItem)
	{
		super.setItem(newItem);
		newItem.setDisableStatus(this.isDisabled());
	}

	setId(id)
	{
		this.sprintId = parseInt(id, 10);
	}

	getSort()
	{
		return this.sort;
	}

	setSort(sort)
	{
		this.sort = parseInt(sort, 10);
	}

	getName()
	{
		return this.name;
	}

	getDateStart()
	{
		return parseInt(this.dateStart, 10);
	}

	getDateEnd()
	{
		return parseInt(this.dateEnd, 10);
	}

	getEntityType()
	{
		return 'sprint';
	}

	setCompletedStoryPoints(completedStoryPoints)
	{
		this.completedStoryPoints = parseFloat(completedStoryPoints);
	}

	getCompletedStoryPoints()
	{
		return this.completedStoryPoints;
	}

	setUnCompletedStoryPoints(unCompletedStoryPoints)
	{
		this.unCompletedStoryPoints = parseFloat(unCompletedStoryPoints);
	}

	getUnCompletedStoryPoints()
	{
		return this.unCompletedStoryPoints;
	}

	setCompletedTasks(completedTasks)
	{
		this.completedTasks = parseInt(completedTasks, 10);
	}

	getCompletedTasks()
	{
		return this.completedTasks;
	}

	setUnCompletedTasks(unCompletedTasks)
	{
		this.unCompletedTasks = parseInt(unCompletedTasks, 10);
	}

	getUnCompletedTasks()
	{
		return this.unCompletedTasks;
	}

	getDefaultSprintDuration()
	{
		return this.defaultSprintDuration;
	}

	setStatus(status)
	{
		this.status = status;
		this.initStyle();

		this.items.forEach((item) => {
			item.setDisableStatus(this.isDisabled());
		});

		if (this.isDisabled())
		{
			this.input.removeYourself();
		}

		if (this.isDisabled())
		{
			this.actionsHeader.removeYourself();
		}
	}

	getSprintNode(): ?HTMLElement
	{
		return this.node;
	}

	removeYourself()
	{
		Dom.remove(this.node);
	}

	render(): HTMLElement
	{
		const getStartEventsButton = () => {
			return '';
			return Tag.render`
				<a class="ui-link">
					${Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_EVENT')}
				</a>
			`;
		};

		const getTotalPoints = () => {
			this.storyPointsNodeId = 'tasks-scrum-sprint-story-points-' + this.getId();
			return Tag.render`
				<div class="tasks-scrum-sprint-story-point-title">
					${Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_STORY_POINTS')}
				</div>
				<div id="${this.storyPointsNodeId}" class="tasks-scrum-sprint-story-point">
					${this.storyPoints}
				</div>
			`;
		};

		const getInWorkPoints = () => {
			if (!this.isActive())
			{
				return '';
			}
			return Tag.render`
				<div class="tasks-scrum-sprint-story-point-in-work-title">
					${Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_STORY_POINTS_IN_WORK')}
				</div>
				<div class="tasks-scrum-sprint-story-point tasks-scrum-sprint-story-point-in-work">
					${this.unCompletedStoryPoints}
				</div>
			`;
		};

		const getDonePoints = () => {
			if (this.isPlanned())
			{
				return '';
			}
			return Tag.render`
				<div class="tasks-scrum-sprint-story-point-done-title">
					${Loc.getMessage('TASKS_SCRUM_SPRINT_TITLE_STORY_POINTS_DONE')}
				</div>
				<div class="tasks-scrum-sprint-story-point tasks-scrum-sprint-story-point-done">
					${this.completedStoryPoints}
				</div>
			`;
		};
		
		const createEvents = () => {
			return Tag.render`
				<div class="tasks-scrum-sprint-header-events">
					<div class="tasks-scrum-sprint-header-event">
						${getStartEventsButton()}
					</div>
					<div class="tasks-scrum-sprint-header-event-params">
						${getTotalPoints()}
						${getInWorkPoints()}
						${getDonePoints()}
					</div>
				</div>
			`;
		};

		const createItems = () => {
			this.itemsNodeId = 'tasks-scrum-sprint-items-' + this.getId();
			return Tag.render`
				<div class="tasks-scrum-sprint-items">
					<div id="${this.itemsNodeId}" class="tasks-scrum-sprint-items-list" data-entity-id=
						"${this.getId()}">
						${this.isCompleted() ? '' : this.input.render()}
						${[...this.items.values()].map((item) => item.render())}
					</div>
				</div>
			`;
		};

		this.nodeId = 'tasks-scrum-sprint-' + this.getId();
		return Tag.render`
			<div id="${this.nodeId}" class="tasks-scrum-sprint" data-sprint-sort=
				"${this.sort}" data-sprint-id="${this.getId()}">
				${this.sprintHeader.createHeader()}
				<div class="tasks-scrum-sprint-content">
					${createEvents()}
					${this.actionsHeader.createActionsHeader()}
					${createItems()}
				</div>
			</div>
		`;
	}

	onAfterAppend()
	{
		this.node = document.getElementById(this.nodeId);
		this.contentNode = this.node.querySelector('.tasks-scrum-sprint-content');
		this.listItemsNode = document.getElementById(this.itemsNodeId);
		this.storyPointsNode = document.getElementById(this.storyPointsNodeId);

		if (!this.isCompleted())
		{
			this.showContent();
		}

		this.sprintHeader.onAfterAppend();
		this.sprintHeader.subscribe('changeName', this.onChangeName.bind(this));
		this.sprintHeader.subscribe('removeSprint', this.onRemoveSprint.bind(this));
		this.sprintHeader.subscribe('completeSprint', () => this.emit('completeSprint'));
		this.sprintHeader.subscribe('startSprint', () => this.emit('startSprint'));
		this.sprintHeader.subscribe('changeSprintDeadline', this.onChangeSprintDeadline.bind(this));
		this.sprintHeader.subscribe('toggleVisibilityContent', this.toggleVisibilityContent.bind(this));

		super.onAfterAppend();
	}

	subscribeToItem(item)
	{
		super.subscribeToItem(item);

		item.subscribe('moveToBacklog', (baseEvent) => {
			this.emit('moveToBacklog', {
				sprint: this,
				item: baseEvent.getTarget()
			});
		});
	}

	onChangeName (baseEvent)
	{
		const createInput = (value) => {
			return Tag.render`
				<input type="text" class="tasks-scrum-sprint-header-name" value="${Text.encode(value)}">
			`;
		};

		const inputNode = createInput(this.name);
		const nameNode = baseEvent.getData().querySelector('.tasks-scrum-sprint-header-name');

		Event.bind(inputNode, 'change', (event) => {
			const newValue = event.target['value'];
			this.emit('changeSprintName', {
				sprintId: this.getId(),
				name: newValue
			});
			this.name = newValue;
			inputNode.blur();
		}, true);

		const blockEnterInput = (event) => {
			if (event.isComposing || event.keyCode === 13)
				inputNode.blur();
		};

		Event.bind(inputNode, 'keydown', blockEnterInput);
		Event.bindOnce(inputNode, 'blur', () => {
			Event.unbind(inputNode, 'keydown', blockEnterInput);
			nameNode.textContent = Text.encode(this.name);
			Dom.replace(inputNode, nameNode);
		}, true);

		Dom.replace(nameNode, inputNode);

		inputNode.focus();
		inputNode.setSelectionRange(this.name.length, this.name.length);
	}

	onRemoveSprint()
	{
		[...this.items.values()].map((item) => {
			this.emit('moveToBacklog', {
				sprint: this,
				item: item
			});
		});
		this.removeYourself();
		this.emit('removeSprint');
	}

	onChangeSprintDeadline(baseEvent)
	{
		const requestData = baseEvent.getData();
		this.emit('changeSprintDeadline', requestData);
		if (requestData.hasOwnProperty('dateStart'))
		{
			this.dateStart = parseInt(requestData.dateStart, 10);
		}
		else if (requestData.hasOwnProperty('dateEnd'))
		{
			this.dateEnd = parseInt(requestData.dateEnd, 10);
		}
	}

	updateDateStartNode(timestamp)
	{
		this.sprintHeader.updateDateStartNode(timestamp);
	}

	updateDateEndNode(timestamp)
	{
		this.sprintHeader.updateDateEndNode(timestamp);
	}

	toggleVisibilityContent()
	{
		if (this.contentNode.style.display === 'block')
		{
			this.hideContent();
		}
		else
		{
			this.showContent();
		}
	}

	showContent()
	{
		this.contentNode.style.display = 'block';
	}

	hideContent()
	{
		this.contentNode.style.display = 'none';
	}
}