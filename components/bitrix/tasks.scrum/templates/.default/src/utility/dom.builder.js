import {Dom, Event, Loc, Tag, Text, Type} from 'main.core';
import {Loader} from 'main.loader';
import {EventEmitter} from 'main.core.events';
import {Draggable} from 'ui.draganddrop.draggable';

import {Sprint} from '../entity/sprint/sprint';

import {RequestSender} from './request.sender';
import {EntityStorage} from './entity.storage';

import type {SprintParams} from '../entity/sprint/sprint';

type Params = {
	requestSender: RequestSender,
	entityStorage: EntityStorage,
	defaultSprintDuration: number,
	pageNumberToCompletedSprints: number
}

export class DomBuilder extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.DomBuilder');

		this.requestSender = params.requestSender;
		this.entityStorage = params.entityStorage;
		this.defaultSprintDuration = params.defaultSprintDuration;
		this.pageNumberToCompletedSprints = parseInt(params.pageNumberToCompletedSprints, 10);
	}

	renderTo(container: HTMLElement)
	{
		this.scrumContainer = container;

		this.append(this.entityStorage.getBacklog().render(), this.scrumContainer);
		this.entityStorage.getBacklog().onAfterAppend();

		this.append(this.renderSprintsContainer(), this.scrumContainer);
		this.entityStorage.getSprints().forEach((sprint) => {
			sprint.onAfterAppend();
		});

		this.sprintCreatingButtonNode = document.getElementById(this.sprintCreatingButtonNodeId);
		this.sprintCreatingDropZoneNode = document.getElementById(this.sprintCreatingDropZoneNodeId);
		this.sprintListNode = document.getElementById(this.sprintListNodeId);

		Event.bind(this.sprintCreatingButtonNode, 'click', this.createSprint.bind(this));

		this.setDraggable();
	}

	getSprintCreatingDropZoneNode(): HTMLElement
	{
		return this.sprintCreatingDropZoneNode;
	}

	getSprintPlannedListNode(): HTMLElement
	{
		return this.sprintListNode.querySelector('.tasks-scrum-sprint-planned-list');
	}

	setDraggable()
	{
		const itemContainers = [];
		itemContainers.push(this.entityStorage.getBacklog().getListItemsNode());
		if (this.sprintCreatingDropZoneNode)
		{
			itemContainers.push(this.sprintCreatingDropZoneNode);
		}
		this.entityStorage.getSprints().forEach((sprint) => {
			if (!sprint.isDisabled())
			{
				itemContainers.push(sprint.getListItemsNode());
			}
		});
		this.draggableItems = new Draggable({
			container: itemContainers,
			draggable: '.tasks-scrum-item-drag', // todo add tmp class
			dragElement: '.tasks-scrum-item',
			type: Draggable.DROP_PREVIEW,
			delay: 200
		});
		this.draggableItems.subscribe('start', (baseEvent) => {
			const dragEndEvent = baseEvent.getData();
			this.emit('itemMoveStart', dragEndEvent);
		});
		this.draggableItems.subscribe('end', (baseEvent) => {
			const dragEndEvent = baseEvent.getData();
			this.emit('itemMoveEnd', dragEndEvent);
		});

		this.draggableSprints = new Draggable({
			container: this.sprintListNode.querySelector('.tasks-scrum-sprint-planned-list'),
			draggable: '.tasks-scrum-sprint',
			dragElement: '.tasks-scrum-sprint-dragndrop',
			type: Draggable.DROP_PREVIEW,
		});
		this.draggableSprints.subscribe('end', (baseEvent) => {
			const dragEndEvent = baseEvent.getData();
			this.emit('sprintMove', dragEndEvent);
		});
	}

	renderSprintsContainer(): HTMLElement
	{
		const createCreatingButton = () => {
			this.sprintCreatingButtonNodeId = 'tasks-scrum-sprint-creating-button';
			return Tag.render`
				<div id="${this.sprintCreatingButtonNodeId}" class=
					"tasks-scrum-sprint-create ui-btn ui-btn-md ui-btn-themes ui-btn-light-border ui-btn-icon-add">
					<span>${Loc.getMessage('TASKS_SCRUM_SPRINT_ADD')}</span>
				</div>
			`;
		};

		const createCreatingDropZone = () => {
			if (this.entityStorage.getSprints().size)
			{
				return '';
			}
			this.sprintCreatingDropZoneNodeId = 'tasks-scrum-sprint-creating-drop-zone';
			return Tag.render`
				<div id="${this.sprintCreatingDropZoneNodeId}">
					<label class="ui-ctl ui-ctl-file-drop tasks-scrum-sprint-sprint-add-drop">
						<div class="ui-ctl-label-text">
							<small>${Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_DROP')}</small>
						</div>
					</label>
				</div>
			`;
		};

		const createSprintsList = () => {
			this.sprintListNodeId = 'tasks-scrum-sprint-list';
			return Tag.render`
				<div id="${this.sprintListNodeId}" class="tasks-scrum-sprint-list">
					<div class="tasks-scrum-sprint-active-list">
						${[...this.entityStorage.getSprints().values()].map((sprint) => {
							if (sprint.isActive())
							{
								return sprint.render();
							}
							else
							{
								return '';
							}
						})}
					</div>
					<div class="tasks-scrum-sprint-planned-list">
						${[...this.entityStorage.getSprints().values()].map((sprint) => {
							if (sprint.isPlanned())
							{
								return sprint.render();
							}
							else
							{
								return '';
							}
						})}
					</div>
					<div class="tasks-scrum-sprint-completed-list">
						${[...this.entityStorage.getSprints().values()].map((sprint) => {
							if (sprint.isCompleted())
							{
								return sprint.render();
							}
							else
							{
								return '';
							}
						})}
					</div>
					<div class="tasks-scrum-sprint-completed-loader"></div>
				</div>
			`;
		};

		const sprintsNode = Tag.render`
			<div class="tasks-scrum-sprints">
				${createCreatingButton()}
				${createCreatingDropZone()}
				${createSprintsList()}
			</div>
		`;

		this.completedSprintLoader = sprintsNode.querySelector('.tasks-scrum-sprint-completed-loader');
		this.bindLoadCompletedSprints(this.completedSprintLoader);

		return sprintsNode;
	}

	bindLoadCompletedSprints(loader: HTMLElement)
	{
		const observer = new IntersectionObserver((entries) =>
			{
				if(entries[0].isIntersecting === true)
				{
					if (!this.isActiveLoadCompletedSprints)
					{
						this.onLoadCompletedSprints();
					}
				}
			},
			{
				threshold: [0]
			}
		);

		observer.observe(loader);
	}

	onLoadCompletedSprints()
	{
		this.isActiveLoadCompletedSprints = true;

		const loader = this.showLoader(this.completedSprintLoader);

		const requestData = {
			pageNumber: this.pageNumberToCompletedSprints + 1
		};

		this.requestSender.getCompletedSprints(requestData)
			.then((response) => {
				const data = response.data;
				if (Type.isArray(data) && data.length)
				{
					this.pageNumberToCompletedSprints++;
					this.isActiveLoadCompletedSprints = false;

					this.createSprints(data);
				}
				loader.hide();
			})
			.catch((response) => {
				loader.hide();
				this.isActiveLoadCompletedSprints = false;
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	createSprints(sprints: Array)
	{
		const sprintListNode = this.sprintListNode.querySelector('.tasks-scrum-sprint-completed-list');

		sprints.forEach((sprintData: SprintParams) => {
			const sprint = Sprint.buildSprint(sprintData);
			if (!this.entityStorage.findEntityByEntityId(sprint.getId()))
			{
				this.entityStorage.addSprint(sprint);
				this.append(sprint.render(), sprintListNode);
				sprint.onAfterAppend();
				this.emit('createSprint', sprint);
			}
		});
	}

	showLoader(container: HTMLElement): Loader
	{
		const listPosition = this.getPosition(container);

		const loader = new Loader({
			target: container,
			size: 60,
			mode: 'inline',
			color: 'rgba(82, 92, 105, 0.9)',
			offset: {
				left: `${(listPosition.width / 2 - 30)}px`
			}
		});

		loader.show();

		return loader;
	}

	createSprint(): Promise
	{
		this.remove(this.sprintCreatingDropZoneNode);

		const countSprints = this.entityStorage.getSprints().size;
		const title = Loc.getMessage('TASKS_SCRUM_SPRINT_NAME').replace('%s', countSprints + 1);
		const storyPoints = 0;
		const dateStart = Math.floor(Date.now() / 1000);
		const dateEnd = (Math.floor(Date.now() / 1000) + parseInt(this.defaultSprintDuration, 10));

		const sprintListNode = this.sprintListNode.querySelector('.tasks-scrum-sprint-planned-list');
		const sort = (sprintListNode.children.length ? sprintListNode.children.length + 1 : 1);

		const sprint = Sprint.buildSprint({
			name: title,
			sort: sort,
			dateStart: dateStart,
			dateEnd: dateEnd,
			storyPoints: storyPoints
		});
		this.append(sprint.render(), sprintListNode);

		const requestData = {
			tmpId: Text.getRandom(),
			name: title,
			sort: sort,
			dateStart: dateStart,
			dateEnd: dateEnd
		};

		this.emit('beforeCreateSprint', requestData);

		return this.requestSender.createSprint(requestData).then((response) => {
			sprint.setId(response.data.sprintId);
			sprint.onAfterAppend();
			sprint.getNode().scrollIntoView(true);
			this.entityStorage.addSprint(sprint);
			this.draggableItems.addContainer(sprint.getListItemsNode());
			this.emit('createSprint', sprint);
			return sprint;
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	createSprintNode(sprint: Sprint)
	{
		this.remove(this.sprintCreatingDropZoneNode);

		const sprintListNode = this.sprintListNode.querySelector('.tasks-scrum-sprint-planned-list');

		this.append(sprint.render(), sprintListNode);

		sprint.onAfterAppend();

		this.entityStorage.addSprint(sprint);

		this.draggableItems.addContainer(sprint.getListItemsNode());

		this.emit('createSprintNode', sprint);
	}

	moveSprintToActiveListNode(sprint: Sprint)
	{
		const sprintListNode = this.sprintListNode.querySelector('.tasks-scrum-sprint-active-list');

		this.append(sprint.getNode(), sprintListNode);
	}

	moveSprintToCompletedListNode(sprint: Sprint)
	{
		const sprintListNode = this.sprintListNode.querySelector('.tasks-scrum-sprint-completed-list');

		if (sprintListNode.firstElementChild)
		{
			this.insertBefore(sprint.getNode(), sprintListNode.firstElementChild);
		}
		else
		{
			this.append(sprint.getNode(), sprintListNode);
		}
	}

	append(current: ?HTMLElement, target: ?HTMLElement)
	{
		Dom.append(current, target);
	}

	insertBefore(current: ?HTMLElement, target: ?HTMLElement)
	{
		Dom.insertBefore(current, target);
	}

	remove(element: ?HTMLElement)
	{
		Dom.remove(element);
	}

	getPosition(element: HTMLElement): DOMRect
	{
		return Dom.getPosition(element);
	}

	appendItemAfterItem(newItemNode: HTMLElement, bindItemNode: HTMLElement)
	{
		if (bindItemNode.nextElementSibling)
		{
			Dom.insertBefore(newItemNode, bindItemNode.nextElementSibling);
		}
		else
		{
			Dom.append(newItemNode, bindItemNode.parentElement);
		}
	};
}