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

import type {EpicType, ItemParams} from '../../item/item';
import type {Views} from '../../view/view';

import '../../css/sprint.css';

type SprintInfo = {
	sprintGoal?: string
}

export type SprintParams = {
	id: number,
	tmpId: string,
	name: string,
	sort: number,
	dateStart?: number,
	dateEnd?: number,
	weekendDaysTime?: number,
	defaultSprintDuration: number,
	totalStoryPoints?: string,
	totalCompletedStoryPoints?: string,
	totalUncompletedStoryPoints?: string,
	completedTasks?: number,
	uncompletedTasks?: number,
	status?: string,
	numberTasks?: number,
	items?: Array<ItemParams>,
	info?: SprintInfo,
	views: Views
};

export class Sprint extends Entity
{
	constructor(params: SprintParams)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Sprint');

		this.setSprintParams(params);

		this.sprintHeader = null;
		this.eventsHeader = null;
		this.storyPointsHeader = null;
	}

	setSprintParams(params: SprintParams)
	{
		this.setTmpId(params.tmpId);
		this.setName(params.name);
		this.setSort(params.sort);
		this.setDateStart(params.dateStart);
		this.setDateEnd(params.dateEnd);
		this.setWeekendDaysTime(params.weekendDaysTime);
		this.setDefaultSprintDuration(params.defaultSprintDuration);
		this.setStatus(params.status);
		this.setTotalStoryPoints(params.totalStoryPoints);
		this.setTotalCompletedStoryPoints(params.totalCompletedStoryPoints);
		this.setTotalUncompletedStoryPoints(params.totalUncompletedStoryPoints);
		this.setCompletedTasks(params.completedTasks);
		this.setUncompletedTasks(params.uncompletedTasks);
		this.setItems(params.items);
		this.setInfo(params.info);
	}

	static buildSprint(sprintData: SprintParams = {}): Sprint
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
		this.sprintHeader.initStyle(this);

		this.sprintHeader.subscribe('changeName', this.onChangeName.bind(this));
		this.sprintHeader.subscribe('removeSprint', this.onRemoveSprint.bind(this));
		this.sprintHeader.subscribe('completeSprint', () => this.emit('completeSprint'));
		this.sprintHeader.subscribe('startSprint', () => this.emit('startSprint'));
		this.sprintHeader.subscribe('changeSprintDeadline', this.onChangeSprintDeadline.bind(this));
		this.sprintHeader.subscribe('toggleVisibilityContent', this.toggleVisibilityContent.bind(this));
	}

	addStoryPointsHeader(storyPointsHeader: StoryPointsHeader)
	{
		this.storyPointsHeader = storyPointsHeader;

		this.storyPointsHeader.subscribe('showSprintBurnDownChart', () => this.emit('showSprintBurnDownChart'));
	}

	addEventsHeader(eventsHeader: EventsHeader)
	{
		this.eventsHeader = eventsHeader;
	}

	initStyle()
	{
		if (this.sprintHeader)
		{
			this.sprintHeader.initStyle(this);
		}
	}

	isActive(): boolean
	{
		return (this.getStatus() === 'active');
	}

	isPlanned(): boolean
	{
		return (this.getStatus() === 'planned');
	}

	isCompleted(): boolean
	{
		return (this.getStatus() === 'completed');
	}

	isDisabled(): boolean
	{
		return (this.isCompleted());
	}

	isExpired(): boolean
	{
		const sprintEnd = new Date(this.dateEnd * 1000);
		return (this.isActive() && (sprintEnd.getTime() < (new Date()).getTime()));
	}

	hasInput(): boolean
	{
		return !this.isDisabled();
	}

	getEntityType()
	{
		return 'sprint';
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

	setName(name)
	{
		this.name = (Type.isString(name) ? name : '');

		if (this.isNodeCreated() && this.sprintHeader)
		{
			this.sprintHeader.updateNameNode(this.name);
		}
	}

	getName()
	{
		return this.name;
	}

	setTmpId(tmpId: string)
	{
		this.tmpId = (Type.isString(tmpId) ? tmpId : '');
	}

	getTmpId(): string
	{
		return this.tmpId;
	}

	setSort(sort)
	{
		this.sort = (Type.isInteger(sort) ? parseInt(sort, 10) : 1);
	}

	getSort()
	{
		return this.sort;
	}

	setDateStart(dateStart)
	{
		this.dateStart = (Type.isInteger(dateStart) ? parseInt(dateStart, 10) : 0);

		if (this.isNodeCreated() && this.sprintHeader)
		{
			this.sprintHeader.updateDateStartNode(this.dateStart);
		}
	}

	getDateStart(): number
	{
		return parseInt(this.dateStart, 10);
	}

	setDateEnd(dateEnd)
	{
		this.dateEnd = (Type.isInteger(dateEnd) ? parseInt(dateEnd, 10) : 0);

		if (this.isNodeCreated() && this.sprintHeader)
		{
			this.sprintHeader.updateDateEndNode(this.dateEnd);
		}
	}

	getDateEnd(): number
	{
		return parseInt(this.dateEnd, 10);
	}

	setWeekendDaysTime(weekendDaysTime)
	{
		this.weekendDaysTime = (Type.isInteger(weekendDaysTime) ? parseInt(weekendDaysTime, 10) : 0);
	}

	getWeekendDaysTime(): number
	{
		return this.weekendDaysTime;
	}

	getStoryPoints(): StoryPoints
	{
		return this.totalStoryPoints;
	}

	setTotalStoryPoints(totalStoryPoints)
	{
		this.totalStoryPoints = new StoryPoints();
		this.totalStoryPoints.addPoints(totalStoryPoints);
	}

	getTotalStoryPoints(): StoryPoints
	{
		return this.totalStoryPoints;
	}

	setTotalCompletedStoryPoints(totalCompletedStoryPoints)
	{
		this.totalCompletedStoryPoints = new StoryPoints();
		this.totalCompletedStoryPoints.addPoints(totalCompletedStoryPoints);
	}

	getTotalCompletedStoryPoints(): StoryPoints
	{
		return this.totalCompletedStoryPoints;
	}

	setTotalUncompletedStoryPoints(totalUncompletedStoryPoints)
	{
		this.totalUncompletedStoryPoints = new StoryPoints();
		this.totalUncompletedStoryPoints.addPoints(totalUncompletedStoryPoints);
	}

	getTotalUncompletedStoryPoints(): StoryPoints
	{
		return this.totalUncompletedStoryPoints;
	}

	setItems(items)
	{
		if (!Type.isArray(items))
		{
			return;
		}

		items.forEach((itemParams: ItemParams) => {
			const item = new Item(itemParams);
			item.setDisableStatus(this.isDisabled());
			this.items.set(item.itemId, item);
		});
	}

	setInfo(info: SprintInfo)
	{
		this.info = (Type.isPlainObject(info) ? info : {sprintGoal: ''});
	}

	addNumberTasks(value: number)
	{
		super.addNumberTasks(value);

		if (this.storyPointsHeader)
		{
			this.storyPointsHeader.updateNumberTasks();
		}
	}

	subtractNumberTasks(value: number)
	{
		super.subtractNumberTasks(value);

		if (this.storyPointsHeader)
		{
			this.storyPointsHeader.updateNumberTasks();
		}
	}

	setCompletedTasks(completedTasks)
	{
		this.completedTasks = (Type.isInteger(completedTasks) ? parseInt(completedTasks, 10) : 0);
	}

	getCompletedTasks(): number
	{
		return this.completedTasks;
	}

	setUncompletedTasks(uncompletedTasks)
	{
		this.uncompletedTasks = (Type.isInteger(uncompletedTasks) ? parseInt(uncompletedTasks, 10) : 0);
	}

	getUncompletedTasks(): number
	{
		return this.uncompletedTasks;
	}

	setDefaultSprintDuration(defaultSprintDuration)
	{
		this.defaultSprintDuration = (Type.isInteger(defaultSprintDuration) ? parseInt(defaultSprintDuration, 10) : 0);
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
		const availableStatus = new Set([
			'planned',
			'active',
			'completed'
		]);

		this.status = (availableStatus.has(status) ? status : 'planned');

		this.initStyle();

		this.items.forEach((item) => {
			item.setDisableStatus(this.isDisabled());
		});

		if (this.isDisabled())
		{
			if (this.input)
			{
				this.input.removeYourself();
			}
			if (this.groupActionsButton)
			{
				this.groupActionsButton.removeYourself();
			}
		}
	}

	getStatus(): string
	{
		return this.status;
	}

	updateYourself(tmpSprint: Sprint)
	{
		if (tmpSprint.getName() !== this.getName())
		{
			this.setName(tmpSprint.getName());
		}
		if (tmpSprint.getDateStart() !== this.getDateStart())
		{
			this.setDateStart(tmpSprint.getDateStart());
		}
		if (tmpSprint.getDateEnd() !== this.getDateEnd())
		{
			this.setDateEnd(tmpSprint.getDateEnd());
		}

		this.setTotalStoryPoints(tmpSprint.getTotalStoryPoints().getPoints());
		this.setTotalCompletedStoryPoints(tmpSprint.getTotalCompletedStoryPoints().getPoints());
		this.setTotalUncompletedStoryPoints(tmpSprint.getTotalUncompletedStoryPoints().getPoints());

		if (tmpSprint.getStatus() !== this.getStatus())
		{
			this.setStatus(tmpSprint.getStatus());
		}

		if (this.node)
		{
			this.addStoryPointsHeader(new StoryPointsHeader(this));
			Dom.replace(this.node.querySelector('.tasks-scrum-sprint-header-event-params'), this.renderParams());
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
						${this.renderParams()}
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

	renderParams(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-sprint-header-event-params">
				${this.storyPointsHeader ? this.storyPointsHeader.render() : ''}
			</div>
		`;
	}

	renderLinkToCompletedSprint(): HTMLElement
	{
		return Tag.render`
			<a href="${Text.encode(this.getViews().completedSprint.url)}">
				${Loc.getMessage('TASKS_SCRUM_COMPLETED_SPRINT_LINK')}
			</a>
		`;
	}

	onAfterAppend()
	{
		this.updateVisibility();

		if (this.sprintHeader)
		{
			this.sprintHeader.onAfterAppend();
		}

		super.onAfterAppend();
	}

	updateStoryPoints()
	{
		super.updateStoryPoints();

		if (!this.isCompleted())
		{
			this.totalStoryPoints.clearPoints();
			[...this.getItems().values()].map((item: Item) => {
				this.totalStoryPoints.addPoints(item.getStoryPoints().getPoints());
			});
			this.totalCompletedStoryPoints.clearPoints();
			[...this.getItems().values()].map((item: Item) => {
				if (item.isCompleted())
				{
					this.totalCompletedStoryPoints.addPoints(item.getStoryPoints().getPoints());
				}
			});
			this.totalUncompletedStoryPoints.clearPoints();
			[...this.getItems().values()].map((item: Item) => {
				if (!item.isCompleted())
				{
					this.totalUncompletedStoryPoints.addPoints(item.getStoryPoints().getPoints());
				}
			});
		}

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

		item.subscribe('updateCompletedStatus', (baseEvent: BaseEvent) => {
			[...this.getItems().values()].map((item: Item) => {
				if (item.isCompleted())
				{
					this.setCompletedTasks(this.getCompletedTasks() + 1);
				}
				else
				{
					this.setUncompletedTasks(this.getUncompletedTasks() - 1);
				}
			});
		});

		item.subscribe('toggleSubTasks', (baseEvent: BaseEvent) => {
			this.emit('toggleSubTasks', baseEvent.getTarget());
		})
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

	removeSprint()
	{
		[...this.items.values()].map((item) => {
			this.emit('moveToBacklog', {
				sprint: this,
				item: item
			});
		});

		Dom.remove(this.node);
		this.node = null;
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
		if (this.getContentNode().style.display === 'block')
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
		if (this.getContentNode())
		{
			this.getContentNode().style.display = 'block';
		}
	}

	hideContent()
	{
		if (this.getContentNode())
		{
			this.getContentNode().style.display = 'none';
		}
	}

	updateVisibility()
	{
		if (this.isCompleted())
		{
			if (this.isEmpty())
			{
				this.hideContent();
			}
			else
			{
				this.showContent();
			}

			this.showSprint();

			if (this.isExactSearchApplied())
			{
				if (this.isEmpty())
				{
					this.hideSprint();
				}
			}
		}
		else
		{
			this.showContent();

			if (this.isExactSearchApplied())
			{
				if (this.isEmpty())
				{
					this.hideSprint();
				}
				else
				{
					this.showSprint();
				}
			}
			else
			{
				this.showSprint();
			}
		}
	}

	showSprint()
	{
		this.node.style.display = 'block';
	}

	hideSprint()
	{
		this.node.style.display = 'none';
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

	getContentNode(): HTMLElement
	{
		if (this.node)
		{
			return this.node.querySelector('.tasks-scrum-sprint-content');
		}
	}
}