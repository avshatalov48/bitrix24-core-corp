import {Dom, Event, Loc, Tag, Text, Type} from 'main.core';
import {BaseEvent} from 'main.core.events';
import {Entity} from '../entity';
import {Item} from '../../item/item';
import {SprintHeader} from './sprint.header';
import {EventsHeader} from './events.header';
import {GroupActionsButton} from '../group.actions.button';
import {ListItems} from '../list.items';
import {StoryPointsHeader} from './story.points.header';
import {StoryPoints} from '../../utility/story.points';

import '../../css/sprint.css';

type SprintInfo = {
	sprintGoal?: string
}

type SprintParams = {
	id: number,
	name?: string,
	sort: number,
	dateStart?: number,
	dateEnd?: number,
	defaultSprintDuration: number,
	totalStoryPoints?: string,
	totalCompletedStoryPoints?: string,
	totalUncompletedStoryPoints?: string,
	numberTasks?: number,
	completedTasks?: number,
	unCompletedTasks?: number,
	status?: string,
	items?: Array,
	info?: SprintInfo
};

type EpicInfoType = {
	color: string
}

type EpicType = {
	id: number,
	name: string,
	description: string,
	info: EpicInfoType
}

export class Sprint extends Entity
{
	constructor(sprintData: SprintParams = {})
	{
		super(sprintData);

		this.setEventNamespace('BX.Tasks.Scrum.Sprint');

		this.name = sprintData.name;
		this.dateStart = (Type.isInteger(sprintData.dateStart) ? parseInt(sprintData.dateStart, 10) : 0);
		this.dateEnd = (Type.isInteger(sprintData.dateEnd) ? parseInt(sprintData.dateEnd, 10) : 0);
		this.status = (sprintData.status ? sprintData.status : 'planned');
		this.sort = (Type.isInteger(sprintData.sort) ? parseInt(sprintData.sort, 10) : 1);

		this.totalStoryPoints = new StoryPoints();
		this.totalStoryPoints.addPoints(sprintData.totalStoryPoints);
		this.totalCompletedStoryPoints = new StoryPoints();
		this.totalCompletedStoryPoints.addPoints(sprintData.totalCompletedStoryPoints);
		this.totalUncompletedStoryPoints = new StoryPoints();
		this.totalUncompletedStoryPoints.addPoints(sprintData.totalUncompletedStoryPoints);

		this.completedStoryPoints = new StoryPoints();
		this.uncompletedStoryPoints = new StoryPoints();

		this.numberTasks = (Type.isInteger(sprintData.numberTasks) ? parseInt(sprintData.numberTasks, 10) : 0);
		this.completedTasks = (Type.isInteger(sprintData.completedTasks) ? parseInt(sprintData.completedTasks, 10) : 0);
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

		this.info = sprintData.info;

		this.sprintHeader = null;
		this.eventsHeader = null;
		this.storyPointsHeader = null;
	}

	static buildSprint(sprintData: sprintParams = {}): Sprint
	{
		const sprint = new Sprint(sprintData);
		sprint.addSprintHeader(SprintHeader.buildHeader(sprint));
		sprint.addEventsHeader(new EventsHeader());
		sprint.addStoryPointsHeader(new StoryPointsHeader(sprint));
		sprint.addGroupActionsButton(new GroupActionsButton());
		sprint.addListItems(new ListItems(sprint));
		return sprint;
	}

	addSprintHeader(sprintHeader: SprintHeader)
	{
		this.sprintHeader = sprintHeader;
		this.sprintHeader.initStyle();
	}

	addStoryPointsHeader(storyPointsHeader: StoryPointsHeader)
	{
		this.storyPointsHeader = storyPointsHeader;
	}

	addEventsHeader(eventsHeader: EventsHeader)
	{
		this.eventsHeader = eventsHeader;
	}

	initStyle()
	{
		if (this.sprintHeader)
		{
			this.sprintHeader.initStyle();
		}
	}

	isActive(): boolean
	{
		return (this.status === 'active');
	}

	isPlanned(): boolean
	{
		return (this.status === 'planned');
	}

	isCompleted(): boolean
	{
		return (this.status === 'completed');
	}

	isDisabled(): boolean
	{
		return (this.isCompleted());
	}

	isExpired(): boolean
	{
		const sprintEndTime = new Date(this.dateEnd * 1000).getTime();

		const currentTime = new Date();
		currentTime.setHours(currentTime.getHours() + 8);

		return (this.isActive() && (sprintEndTime < currentTime.getTime()));
	}

	hasInput(): boolean
	{
		return !this.isDisabled();
	}

	setItem(newItem: Item)
	{
		super.setItem(newItem);
		newItem.setDisableStatus(this.isDisabled());
		this.updateStoryPoints();
	}

	removeItem(item: Item)
	{
		super.removeItem(item);
		this.updateStoryPoints();
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

	getDateStart(): number
	{
		return parseInt(this.dateStart, 10);
	}

	getDateEnd(): number
	{
		return parseInt(this.dateEnd, 10);
	}

	getEntityType()
	{
		return 'sprint';
	}

	getTotalStoryPoints(): StoryPoints
	{
		return this.totalStoryPoints;
	}

	getTotalCompletedStoryPoints(): StoryPoints
	{
		return this.totalCompletedStoryPoints;
	}

	getTotalUncompletedStoryPoints(): StoryPoints
	{
		return this.totalUncompletedStoryPoints;
	}

	getCompletedStoryPoints(): StoryPoints
	{
		return this.completedStoryPoints;
	}

	getUncompletedStoryPoints(): StoryPoints
	{
		return this.uncompletedStoryPoints;
	}

	getNumberTasks(): number
	{
		return (this.numberTasks ? this.numberTasks : this.getItems().size);
	}

	setCompletedTasks(completedTasks)
	{
		this.completedTasks = parseInt(completedTasks, 10);
	}

	getCompletedTasks(): number
	{
		return this.completedTasks;
	}

	setUnCompletedTasks(unCompletedTasks)
	{
		this.unCompletedTasks = parseInt(unCompletedTasks, 10);
	}

	getUnCompletedTasks(): number
	{
		return this.unCompletedTasks;
	}

	getDefaultSprintDuration()
	{
		return this.defaultSprintDuration;
	}

	getInfo(): SprintInfo
	{
		return this.info;
	}

	getEpics(): Map<number, EpicType>
	{
		const epics = new Map();
		this.items.forEach((item: Item) => {
			//todo wtf, why did not Set work?
			if (item.getEpic())
			{
				epics.set(item.getEpic().id, item.getEpic());
			}
		});
		return epics;
	}

	getUncompletedItems(): Map<string, Item>
	{
		const items = new Map();
		this.items.forEach((item: Item) => {
			if (!item.isCompleted())
			{
				items.set(item.getItemId(), item);
			}
		});
		return items;
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
			this.groupActionsButton.removeYourself();
		}
	}

	removeYourself()
	{
		Dom.remove(this.node);
		this.node = null;
		this.emit('removeSprint');
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum-sprint" data-sprint-sort="${this.sort}" data-sprint-id="${this.getId()}">
				${this.sprintHeader ? this.sprintHeader.render() : ''}
				<div class="tasks-scrum-sprint-content">
					<div class="tasks-scrum-sprint-sub-header">
						<div class="tasks-scrum-sprint-header-event">
							${this.eventsHeader ? '' : ''/*todo*/}
							${this.isCompleted() ? this.renderLinkToCompletedSprint() : ''}
						</div>
						<div class="tasks-scrum-sprint-header-event-params">
							${this.storyPointsHeader ? this.storyPointsHeader.render() : ''}
						</div>
					</div>
					<div class="tasks-scrum-sprint-actions">
						${this.groupActionsButton  && !this.isDisabled() ? this.groupActionsButton.render() : ''}
					</div>
					<div class="tasks-scrum-sprint-items">
						${this.listItems ? this.listItems.render() : ''}
					</div>
				</div>
			</div>
		`;

		return this.node;
	}

	renderLinkToCompletedSprint(): HTMLElement
	{
		return Tag.render`
			<a href="${Text.encode(this.views.completedSprint.url)}">
				${Loc.getMessage('TASKS_SCRUM_COMPLETED_SPRINT_LINK')}
			</a>
		`;
	}

	onAfterAppend()
	{
		this.contentNode = this.node.querySelector('.tasks-scrum-sprint-content');

		if (!this.isCompleted())
		{
			this.showContent();
		}

		if (this.sprintHeader)
		{
			this.sprintHeader.onAfterAppend();
			this.sprintHeader.subscribe('changeName', this.onChangeName.bind(this));
			this.sprintHeader.subscribe('removeSprint', this.onRemoveSprint.bind(this));
			this.sprintHeader.subscribe('completeSprint', () => this.emit('completeSprint'));
			this.sprintHeader.subscribe('startSprint', () => this.emit('startSprint'));
			this.sprintHeader.subscribe('changeSprintDeadline', this.onChangeSprintDeadline.bind(this));
			this.sprintHeader.subscribe('toggleVisibilityContent', this.toggleVisibilityContent.bind(this));
		}

		super.onAfterAppend();
	}

	updateStoryPoints()
	{
		super.updateStoryPoints();

		this.completedStoryPoints.clearPoints();
		[...this.getItems().values()].map((item: Item) => {
			if (item.isCompleted())
			{
				this.completedStoryPoints.addPoints(item.getStoryPoints().getPoints());
			}
		});

		this.uncompletedStoryPoints.clearPoints();
		[...this.getItems().values()].map((item: Item) => {
			if (!item.isCompleted())
			{
				this.uncompletedStoryPoints.addPoints(item.getStoryPoints().getPoints());
			}
		});

		if (this.storyPointsHeader)
		{
			if (this.isActive())
			{
				this.updateActiveSprintStoryPointsHeader();
			}
			else if (this.isCompleted())
			{
				this.storyPointsHeader.setStoryPoints(this.getTotalStoryPoints().getPoints());
				this.storyPointsHeader.setCompletedStoryPoints(this.getTotalCompletedStoryPoints().getPoints());
			}
			else if (this.isPlanned())
			{
				this.storyPointsHeader.setStoryPoints(this.storyPoints.getPoints());
			}
		}

		if (this.isPlanned())
		{
			this.getTotalStoryPoints().setPoints(this.storyPoints.getPoints());
			this.getTotalCompletedStoryPoints().setPoints(this.completedStoryPoints.getPoints());
		}

		if (this.sprintHeader)
		{
			this.sprintHeader.updateStatsHeader();
		}
	}

	updateTotalStoryPoints(item: Item)
	{
		if (!this.isActive())
		{
			return;
		}

		const differencePoints = item.getStoryPoints().getDifferencePoints();
		if (differencePoints > 0)
		{
			this.addTotalStoryPoints(item);
		}
		else
		{
			this.subtractTotalStoryPoints(item);
		}

		this.updateActiveSprintStoryPointsHeader();

		if (this.sprintHeader)
		{
			this.sprintHeader.updateStatsHeader();
		}
	}

	addTotalStoryPoints(item: Item)
	{
		const itemStoryPoints = item.getStoryPoints().getPoints();

		this.getTotalStoryPoints().addPoints(itemStoryPoints);
		if (item.isCompleted())
		{
			this.getTotalCompletedStoryPoints().addPoints(itemStoryPoints);
		}
		else
		{
			this.getTotalUncompletedStoryPoints().addPoints(itemStoryPoints);
		}
	}

	subtractTotalStoryPoints(item: Item)
	{
		const itemStoryPoints = item.getStoryPoints().getPoints();

		this.getTotalStoryPoints().subtractPoints(itemStoryPoints);
		if (item.isCompleted())
		{
			this.getTotalCompletedStoryPoints().subtractPoints(itemStoryPoints);
		}
		else
		{
			this.getTotalUncompletedStoryPoints().subtractPoints(itemStoryPoints);
		}
	}

	updateActiveSprintStoryPointsHeader()
	{
		if (this.storyPointsHeader)
		{
			this.storyPointsHeader.setStoryPoints(this.getTotalStoryPoints().getPoints());
			this.storyPointsHeader.setCompletedStoryPoints(this.getTotalCompletedStoryPoints().getPoints());
			this.storyPointsHeader.setUncompletedStoryPoints(this.getTotalUncompletedStoryPoints().getPoints());
		}
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

		item.subscribe('updateActiveSprintStoryPoints', (baseEvent: BaseEvent) => {
			this.updateTotalStoryPoints(baseEvent.getTarget())
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
		if (this.sprintHeader)
		{
			this.sprintHeader.updateDateStartNode(timestamp);
		}
	}

	updateDateEndNode(timestamp)
	{
		if (this.sprintHeader)
		{
			this.sprintHeader.updateDateEndNode(timestamp);
		}
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
			if (this.isCompleted() && this.getItems().size === 0)
			{
				this.emit('getSprintCompletedItems');
			}
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

	onActivateGroupMode(baseEvent: BaseEvent)
	{
		super.onActivateGroupMode(baseEvent);

		Dom.addClass(this.node.querySelector('.tasks-scrum-sprint-items'), 'tasks-scrum-sprint-items-group-mode');
	}

	onDeactivateGroupMode(baseEvent: BaseEvent)
	{
		super.onDeactivateGroupMode(baseEvent);

		Dom.removeClass(this.node.querySelector('.tasks-scrum-sprint-items'), 'tasks-scrum-sprint-items-group-mode');
	}
}