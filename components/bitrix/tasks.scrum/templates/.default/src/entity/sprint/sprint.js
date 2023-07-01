import {Dom, Event, Tag, Type} from 'main.core';
import {BaseEvent} from 'main.core.events';

import {Entity} from '../entity';
import {Blank} from '../blank';
import {Dropzone} from '../dropzone';
import {EmptySearchStub} from '../empty.search.stub';

import {Item, ItemParams} from '../../item/item';
import {SubTasks} from '../../item/task/sub.tasks';

import {Header} from './header/header';

import {StoryPointsStorage} from '../../utility/story.points.storage';

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
	averageNumberStoryPoints?: number,
	storyPoints?: string,
	completedStoryPoints?: string,
	uncompletedStoryPoints?: string,
	completedTasks?: number,
	uncompletedTasks?: number,
	status?: string,
	numberTasks?: number,
	items?: Array<ItemParams>,
	info?: SprintInfo,
	views?: Views,
	allowedActions?: AllowedActions,
	isShownContent: 'Y' | 'N'
};

type AllowedActions = {
	start: boolean,
	complete: boolean
}

export class Sprint extends Entity
{
	constructor(params: SprintParams)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Sprint');

		this.completedStoryPoints = new StoryPointsStorage();
		this.uncompletedStoryPoints = new StoryPointsStorage();

		this.setSprintParams(params);

		this.toggling = false;
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
		this.setAverageNumberStoryPoints(params.averageNumberStoryPoints);
		this.setStatus(params.status);
		this.setCompletedStoryPoints(params.completedStoryPoints);
		this.setUncompletedStoryPoints(params.uncompletedStoryPoints);
		this.setCompletedTasks(params.completedTasks);
		this.setUncompletedTasks(params.uncompletedTasks);
		this.setItems(params.items);
		this.setInfo(params.info);
		this.setAllowedActions(params.allowedActions);
		this.setContentVisibility(params.isShownContent);
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
		sprint.setEmptySearchStub(sprint);
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
			(baseEvent: BaseEvent) => {
				if (this.mandatoryExists)
				{
					this.emit('createSprint');
				}
				else
				{
					this.emit('showSprintCreateMenu', baseEvent.getData());
				}
			}
		);
	}

	setBlank(sprint: Sprint)
	{
		this.blank = new Blank(sprint);
	}

	setDropzone(sprint: Sprint)
	{
		this.dropzone = new Dropzone(sprint);

		if (this.mandatoryExists)
		{
			this.dropzone.setMandatory();
		}

		this.dropzone.subscribe('createTask', () => this.emit('showInput'));
	}

	setEmptySearchStub(sprint: Sprint)
	{
		if (!this.isCompleted())
		{
			this.emptySearchStub = new EmptySearchStub(sprint);
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

	getEntityType(): string
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
			this.emit('showDropzone');
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

	setAllowedActions(allowedActions: AllowedActions)
	{
		this.allowedActions = {
			start: false,
			complete: false
		};

		if (Type.isPlainObject(allowedActions))
		{
			this.allowedActions.start = allowedActions.start === true;
			this.allowedActions.complete = allowedActions.complete === true;
		}
	}

	setContentVisibility(isShown)
	{
		this.hideCont = isShown !== 'Y';
	}

	canStart(): boolean
	{
		return this.allowedActions.start === true;
	}

	canComplete(): boolean
	{
		return this.allowedActions.complete === true;
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

	setDefaultSprintDuration(defaultSprintDuration: number)
	{
		this.defaultSprintDuration = (Type.isInteger(defaultSprintDuration)
			? parseInt(defaultSprintDuration, 10)
			: 0
		);
	}

	getDefaultSprintDuration(): number
	{
		return this.defaultSprintDuration;
	}

	setAverageNumberStoryPoints(averageNumberStoryPoints: number)
	{
		this.averageNumberStoryPoints = (Type.isNumber(averageNumberStoryPoints)
			? parseFloat(averageNumberStoryPoints)
			: 0
		);
	}

	getAverageNumberStoryPoints(): number
	{
		return this.averageNumberStoryPoints;
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
		Event.bind(this.node, 'transitionend', this.removeNode.bind(this));

		/* eslint-disable */
		this.node.style.height = `${ this.node.scrollHeight }px`;
		this.node.clientHeight;
		this.node.style.height = '0';
		/* eslint-enable */
	}

	removeNode()
	{
		Dom.remove(this.node);
		this.node = null;
	}

	render(): HTMLElement
	{
		const openClass = this.isHideContent() ? '' : '--open';
		const defaultContentStyle = this.isHideContent() ? 'height: 0;' : '';

		this.node = Tag.render`
			<div
				class="tasks-scrum__content --with-header ${openClass}"
				data-sprint-sort="${this.sort}"
				data-sprint-id="${this.getId()}"
			>
				${this.header ? this.header.render() : ''}
				<div class="tasks-scrum__content-container" style="${defaultContentStyle}">
					${this.blank ? this.blank.render() : ''}
					${this.dropzone ? this.dropzone.render() : ''}
					${this.emptySearchStub ? this.emptySearchStub.render() : ''}
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

	onAfterAppend()
	{
		super.onAfterAppend();

		if (this.isEmpty() && !this.isCompleted())
		{
			if (this.getNumberTasks() > 0 && this.isExactSearchApplied())
			{
				this.showEmptySearchStub();
			}
			else
			{
				this.showDropzone();
			}
		}
	}

	subscribeToItem(item)
	{
		super.subscribeToItem(item);

		item.subscribe('showSubTasks', (baseEvent: BaseEvent) => {
			const parentItem: Item = baseEvent.getTarget();
			const subTasks: SubTasks = baseEvent.getData();

			if (this.isSubTaskLoadingActive())
			{
				return;
			}

			if (subTasks.isEmpty())
			{
				this.subTaskLoadingActive = true;

				this.emit('getSubTasks', subTasks);
			}
			else
			{
				this.appendNodeAfterItem(subTasks.render(), parentItem.getNode());

				parentItem.showSubTasks();
			}
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

				event.stopImmediatePropagation();
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
		if (Dom.style(node, 'height') !== '0px')
		{
			Dom.style(node, 'height', 'auto');
		}

		if (this.toggling)
		{
			this.bindItemsLoader();

			this.toggling = false;

			this.emit('toggleVisibilityContent');
		}
	}

	toggleVisibilityContent(node: HTMLElement)
	{
		this.toggling = true;

		this.unbindItemsLoader();

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

		if (node)
		{
			Dom.style(node, 'height', `${ node.scrollHeight }px`);
		}

		if (this.header)
		{
			this.header.upTick();
		}
	}

	hideContent(node?: HTMLElement)
	{
		this.hideCont = true;

		if (node)
		{
			/* eslint-disable */
			node.style.height = `${ node.scrollHeight }px`;
			node.clientHeight;
			node.style.height = '0';
			/* eslint-enable */
		}

		if (this.header)
		{
			this.header.downTick();
		}
	}

	showSprint()
	{
		if (this.node)
		{
			Dom.style(this.node, 'display', 'block');
		}
	}

	hideSprint()
	{
		if (this.node)
		{
			Dom.style(this.node, 'display', 'none');
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

	deactivateSubTaskLoading(item: Item)
	{
		this.subTaskLoadingActive = false;

		item.unDisableToggle();
	}

	isSubTaskLoadingActive(): boolean
	{
		return this.subTaskLoadingActive === true;
	}
}

