import {Dom, Event, Tag, Type} from 'main.core';
import {BaseEvent} from 'main.core.events';

import {Entity} from '../entity';
import {Blank} from '../blank';
import {Dropzone} from '../dropzone';

import {Item} from '../../item/item';
import {SubTasks} from '../../item/task/sub.tasks';

import {Header} from './header/header';

import {StoryPointsStorage} from '../../utility/story.points.storage';

import type {ItemParams} from '../../item/item';
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
	dateStartFormatted?: string,
	dateEndFormatted?: string,
	weekendDaysTime?: number,
	defaultSprintDuration: number,
	storyPoints?: string,
	completedStoryPoints?: string,
	uncompletedStoryPoints?: string,
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

		this.completedStoryPoints = new StoryPointsStorage();
		this.uncompletedStoryPoints = new StoryPointsStorage();

		this.setSprintParams(params);

		this.hideCont = this.isCompleted();
	}

	setSprintParams(params: SprintParams)
	{
		this.setTmpId(params.tmpId);
		this.setName(params.name);
		this.setSort(params.sort);
		this.setDateStart(params.dateStart);
		this.setDateEnd(params.dateEnd);
		this.setDateStartFormatted(params.dateStartFormatted);
		this.setDateEndFormatted(params.dateEndFormatted);
		this.setWeekendDaysTime(params.weekendDaysTime);
		this.setDefaultSprintDuration(params.defaultSprintDuration);
		this.setStatus(params.status);
		this.setCompletedStoryPoints(params.completedStoryPoints);
		this.setUncompletedStoryPoints(params.uncompletedStoryPoints);
		this.setCompletedTasks(params.completedTasks);
		this.setUncompletedTasks(params.uncompletedTasks);
		this.setItems(params.items);
		this.setInfo(params.info);
	}

	static buildSprint(params: SprintParams): Sprint
	{
		const sprint = new Sprint(params);

		sprint.setHeader(sprint);
		if (sprint.isCompleted())
		{
			sprint.setBlank(sprint);
		}
		sprint.setDropzone(sprint);
		sprint.setListItems(sprint);

		return sprint;
	}

	setHeader(sprint: Sprint)
	{
		const header = Header.buildHeader(sprint);

		if (this.header)
		{
			Dom.replace(this.header.getNode(), header.render());
		}

		this.header = header;

		this.header.subscribe('changeName', this.onChangeName.bind(this));
		this.header.subscribe('removeSprint', () => this.emit('removeSprint'));
		this.header.subscribe('completeSprint', () => this.emit('completeSprint'));
		this.header.subscribe('startSprint', () => this.emit('startSprint'));
		this.header.subscribe('changeSprintDeadline', this.onChangeSprintDeadline.bind(this));
		this.header.subscribe('toggleVisibilityContent', () => {
			this.toggleVisibilityContent(this.getContentContainer());
		});
		this.header.subscribe('showBurnDownChart', () => this.emit('showSprintBurnDownChart'));
		this.header.subscribe(
			'showCreateMenu',
			(baseEvent: BaseEvent) => this.emit('showSprintCreateMenu', baseEvent.getData())
		);
	}

	setBlank(sprint: Sprint)
	{
		this.blank = new Blank(sprint);
	}

	setDropzone(sprint: Sprint)
	{
		this.dropzone = new Dropzone(sprint);

		this.dropzone.subscribe('createTask', () => this.emit('showInput'));
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

	getEntityType()
	{
		return 'sprint';
	}

	setItem(newItem: Item)
	{
		super.setItem(newItem);

		newItem.setDisableStatus(this.isDisabled());

		if (newItem.getNode())
		{
			Dom.addClass(newItem.getNode(), '--item-sprint');
		}
	}

	removeItem(item: Item)
	{
		super.removeItem(item);

		if (this.isEmpty())
		{
			this.showDropzone();
		}
	}

	setName(name)
	{
		this.name = (Type.isString(name) ? name : '');

		if (this.isNodeCreated())
		{
			this.header.setName(this);
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

		if (this.getNode())
		{
			this.getNode().dataset.sprintSort = this.sort;
		}
	}

	getSort()
	{
		return this.sort;
	}

	setDateStart(dateStart)
	{
		this.dateStart = (Type.isInteger(dateStart) ? parseInt(dateStart, 10) : 0);

		if (this.isNodeCreated())
		{
			this.header.setName(this);
		}
	}

	getDateStart(): number
	{
		return parseInt(this.dateStart, 10);
	}

	setDateEnd(dateEnd)
	{
		this.dateEnd = (Type.isInteger(dateEnd) ? parseInt(dateEnd, 10) : 0);

		if (this.isNodeCreated())
		{
			this.header.setName(this);
		}
	}

	getDateEnd(): number
	{
		return parseInt(this.dateEnd, 10);
	}

	setDateStartFormatted(dateStart)
	{
		this.dateStartFormatted = (Type.isString(dateStart) ? dateStart : '');
	}

	getDateStartFormatted(): string
	{
		return this.dateStartFormatted;
	}

	setDateEndFormatted(dateEnd)
	{
		this.dateEndFormatted = (Type.isString(dateEnd) ? dateEnd : '');
	}

	getDateEndFormatted(): string
	{
		return this.dateEndFormatted;
	}

	setWeekendDaysTime(weekendDaysTime)
	{
		this.weekendDaysTime = (Type.isInteger(weekendDaysTime) ? parseInt(weekendDaysTime, 10) : 0);
	}

	getWeekendDaysTime(): number
	{
		return this.weekendDaysTime;
	}

	setCompletedStoryPoints(completedStoryPoints)
	{
		this.completedStoryPoints.setPoints(completedStoryPoints);

		this.setStats();
	}

	getCompletedStoryPoints(): StoryPointsStorage
	{
		return this.completedStoryPoints;
	}

	setUncompletedStoryPoints(uncompletedStoryPoints)
	{
		this.uncompletedStoryPoints.setPoints(uncompletedStoryPoints);

		this.setStats();
	}

	getUncompletedStoryPoints(): StoryPointsStorage
	{
		return this.uncompletedStoryPoints;
	}

	setStats()
	{
		if (this.header)
		{
			this.header.setStats(this);
			this.header.setName(this);
			this.header.setInfo(this);
		}
	}

	setItems(items)
	{
		if (!Type.isArray(items))
		{
			return;
		}

		items.forEach((itemParams: ItemParams) => {
			const item = Item.buildItem(itemParams);
			item.setDisableStatus(this.isDisabled());
			item.setShortView(this.getShortView());
			this.items.set(item.getId(), item);
		});
	}

	setInfo(info: SprintInfo)
	{
		this.info = (Type.isPlainObject(info) ? info : {sprintGoal: ''});
	}

	setNumberTasks(numberTasks: number)
	{
		super.setNumberTasks(numberTasks);

		if (this.header)
		{
			this.header.setInfo(this);
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

	getUncompletedItems(): Map<string, Item>
	{
		const items = new Map();

		this.items.forEach((item: Item) => {
			if (!item.isCompleted())
			{
				items.set(item.getId(), item);
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

		this.setHeader(this);

		this.items.forEach((item) => {
			item.setDisableStatus(this.isDisabled());
		});

		if (this.isDisabled())
		{
			if (this.input)
			{
				this.input.removeYourself();
			}
		}
	}

	getStatus(): string
	{
		return this.status;
	}

	disableHeaderButton()
	{
		if (this.header)
		{
			this.header.disableButton();
		}
	}

	unDisableHeaderButton()
	{
		if (this.header)
		{
			this.header.unDisableButton();
		}
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

		this.setStoryPoints(tmpSprint.getStoryPoints().getPoints());
		this.setCompletedStoryPoints(tmpSprint.getCompletedStoryPoints().getPoints());
		this.setUncompletedStoryPoints(tmpSprint.getUncompletedStoryPoints().getPoints());

		if (tmpSprint.getStatus() !== this.getStatus())
		{
			this.setStatus(tmpSprint.getStatus());
		}

		if (this.node && this.header)
		{
			this.setHeader(this);
		}
	}

	removeYourself()
	{
		Dom.remove(this.node);

		this.node = null;
	}

	render(): HTMLElement
	{
		const openClass = this.isCompleted() ? '' : '--open';

		this.node = Tag.render`
			<div
				class="tasks-scrum__content --with-header ${openClass}"
				data-sprint-sort="${this.sort}"
				data-sprint-id="${this.getId()}"
			>
				${this.header ? this.header.render() : ''}
				<div class="tasks-scrum__content-container">
					${this.blank ? this.blank.render() : ''}
					${this.dropzone ? this.dropzone.render() : ''}
					${this.listItems ? this.listItems.render() : ''}
				</div>
			</div>
		`;

		Event.bind(
			this.getContentContainer(),
			'transitionend',
			this.onTransitionEnd.bind(this, this.getContentContainer())
		);

		return this.node;
	}

	// renderLinkToCompletedSprint(): HTMLElement
	// {
	// 	//todo remove it method
	// 	return Tag.render`
	// 		<a href="${Text.encode(this.getViews().completedSprint.url)}">
	// 			${Loc.getMessage('TASKS_SCRUM_COMPLETED_SPRINT_LINK')}
	// 		</a>
	// 	`;
	// }

	onAfterAppend()
	{
		super.onAfterAppend();

		if (this.isEmpty() && !this.isCompleted())
		{
			this.showDropzone();
		}
	}

	subscribeToItem(item)
	{
		super.subscribeToItem(item);

		item.subscribe('showSubTasks', (baseEvent: BaseEvent) => {
			const parentItem: Item = baseEvent.getTarget();
			const subTasks: SubTasks = baseEvent.getData();

			if (subTasks.isEmpty())
			{
				this.emit('getSubTasks', subTasks);
			}

			this.appendNodeAfterItem(subTasks.render(), parentItem.getNode());

			subTasks.show();
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

	onChangeName(baseEvent: BaseEvent)
	{
		const header: Header = baseEvent.getTarget();
		const input: HTMLInputElement = baseEvent.getData();

		header.activateEditMode();

		const length = input.value.length;

		input.focus();
		input.setSelectionRange(length, length);

		Event.bind(input, 'keydown', (event: KeyboardEvent) => {
			if (event.isComposing || event.key === 'Escape' || event.key === 'Enter')
			{
				input.blur();
			}
		});

		Event.bindOnce(input, 'blur', () => {
			if (this.getName() !== input.value)
			{
				this.setName(input.value);

				this.emit('changeSprintName', {
					sprintId: this.getId(),
					name: this.getName()
				});
			}

			header.deactivateEditMode();
		});
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

	onTransitionEnd(node: HTMLElement)
	{
		if (node.style.height !== '0px')
		{
			node.style.height = 'auto'
		}

		this.emit('toggleVisibilityContent');
	}

	toggleVisibilityContent(node: HTMLElement)
	{
		if (this.isHideContent())
		{
			this.showContent(node);

			Dom.addClass(this.node, '--open');

			if (this.isCompleted())
			{
				if (this.getItems().size === 0)
				{
					this.emit('getSprintCompletedItems');
				}
			}
		}
		else
		{
			this.hideContent(node);

			Dom.removeClass(this.node, '--open');
		}
	}

	showContent(node: HTMLElement)
	{
		this.hideCont = false;

		node.style.height = `${ node.scrollHeight }px`

		if (this.header)
		{
			this.header.upTick();
		}
	}

	hideContent(node: HTMLElement)
	{
		this.hideCont = true;

		node.style.height = `${ node.scrollHeight }px`;
		node.clientHeight;
		node.style.height = '0';

		if (this.header)
		{
			this.header.downTick();
		}
	}

	isHideContent(): boolean
	{
		return this.hideCont;
	}

	showSprint()
	{
		if (this.node)
		{
			this.node.style.display = 'block';
		}
	}

	hideSprint()
	{
		if (this.node)
		{
			this.node.style.display = 'none';
		}
	}

	getContentContainer(): HTMLElement
	{
		return this.node.querySelector('.tasks-scrum__content-container');
	}

	fadeOut()
	{
		if (!this.isCompleted())
		{
			super.fadeOut();
		}
	}

	fadeIn()
	{
		if (!this.isCompleted())
		{
			super.fadeIn();
		}
	}
}

