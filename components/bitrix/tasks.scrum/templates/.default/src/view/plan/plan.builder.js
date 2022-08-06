import {Dom, Event, Loc, Tag, Text, Type} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {EntityStorage} from '../../entity/entity.storage';
import {Sprint, SprintParams} from '../../entity/sprint/sprint';

import {RequestSender} from '../../utility/request.sender';
import {Scroller} from '../../utility/scroller';

import {CompletedSprints} from './completed.sprints';

type Params = {
	requestSender: RequestSender,
	entityStorage: EntityStorage,
	defaultSprintDuration: number,
	pageNumberToCompletedSprints: number,
	displayPriority: string,
	isShortView: 'Y' | 'N',
	mandatoryExists: 'Y' | 'N',
	isExactSearchApplied: 'Y' | 'N'
}

export class PlanBuilder extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.DomBuilder');

		this.requestSender = params.requestSender;
		this.entityStorage = params.entityStorage;
		this.defaultSprintDuration = params.defaultSprintDuration;
		this.pageNumberToCompletedSprints = params.pageNumberToCompletedSprints;
		this.displayPriority = params.displayPriority;
		this.isShortView = params.isShortView;
		this.mandatoryExists = params.mandatoryExists;
		this.isExactSearchApplied = params.isExactSearchApplied === 'Y';

		this.scroller = new Scroller({
			planBuilder: this,
			entityStorage: this.entityStorage
		});
	}

	renderTo(container: HTMLElement)
	{
		this.scrumContainer = container;

		this.setWidthPriority(this.displayPriority);

		Dom.append(this.entityStorage.getBacklog().render(), this.scrumContainer);
		this.entityStorage.getBacklog().onAfterAppend();

		Dom.append(this.renderSprintsContainer(), this.scrumContainer);
		this.entityStorage.getSprints().forEach((sprint: Sprint) => {
			if (!sprint.isCompleted())
			{
				sprint.onAfterAppend();
			}
		});

		this.emit('setDraggable');

		this.adjustSprintListWidth();
	}

	renderSprintsContainer(): HTMLElement
	{
		this.completedSprints = new CompletedSprints({
			requestSender: this.requestSender,
			entityStorage: this.entityStorage,
			pageNumber: this.pageNumberToCompletedSprints,
			isShortView: this.isShortView
		});
		this.completedSprints.subscribe('createSprint', (baseEvent: BaseEvent) => {
			this.emit('createSprint', baseEvent.getData());
		});
		this.completedSprints.subscribe('adjustWidth', () => this.adjustSprintListWidth());

		const activeSprint = this.entityStorage.getActiveSprint();
		const plannedSprints = this.entityStorage.getPlannedSprints();

		this.sprintsNode = Tag.render`
			<div class="tasks-scrum__sprints --scrollbar">
				<div class="tasks-scrum__sprints--active ${activeSprint ? '' : '--empty'}">
					${activeSprint ? activeSprint.render() : ''}
				</div>
				<div class="tasks-scrum__sprints--planned ${plannedSprints.size ? '' : '--empty'}">
					${[...plannedSprints.values()].map((sprint) => sprint.render())}
				</div>
				${this.renderSprintDropzone()}
				${this.entityStorage.existCompletedSprint() ? this.completedSprints.render() : ''}
			</div>
		`;

		this.updatePlannedSprints(plannedSprints, !Type.isUndefined(activeSprint));

		if (this.isExactSearchApplied)
		{
			const filteredCompletedSprints = this.entityStorage.getFilteredCompletedSprints();
			if (filteredCompletedSprints.size)
			{
				this.showFilteredCompletedSprints(filteredCompletedSprints);
			}
			else
			{
				this.showEmptySearchStub();
			}
		}

		Event.bind(this.sprintsNode, 'scroll', this.onSprintsScroll.bind(this));

		return this.sprintsNode;
	}

	renderSprintDropzone(): HTMLElement
	{
		this.sprintDropzone = Tag.render`
			<div class="tasks-scrum__content">
				<div class="tasks-scrum__sprints--new-sprint">
					${Loc.getMessage('TASKS_SCRUM_PLAN_SPRINT_DROPZONE')}
				</div>
			</div>
		`;

		Event.bind(this.sprintDropzone, 'click', this.createSprint.bind(this));

		return this.sprintDropzone;
	}

	setWidthPriority(value: string)
	{
		if (value === 'backlog')
		{
			Dom.addClass(this.scrumContainer, '--width-priority-backlog');
		}
		else
		{
			Dom.removeClass(this.scrumContainer, '--width-priority-backlog');
		}
	}

	setShortView(value: 'Y' | 'N')
	{
		this.isShortView = value;
	}

	getSprintsContainer(): HTMLElement
	{
		return this.sprintsNode;
	}

	getSprintDropzone(): HTMLElement
	{
		return this.sprintDropzone;
	}

	isSprintDropzone(container: HTMLElement): boolean
	{
		if (container.firstElementChild)
		{
			return Dom.hasClass(container.firstElementChild, 'tasks-scrum__sprints--new-sprint');
		}
		else
		{
			return false;
		}
	}

	createSprint(): Promise
	{
		const dateStart = Math.floor(Date.now() / 1000);
		const dateEnd = (Math.floor(Date.now() / 1000) + parseInt(this.defaultSprintDuration, 10));
		const sort = this.entityStorage.getPlannedSprints().size + 1;

		const requestData = {
			tmpId: Text.getRandom(),
			sort: sort + 1,
			dateStart: dateStart,
			dateEnd: dateEnd
		};

		this.emit('beforeCreateSprint', requestData);

		return this.requestSender.createSprint(requestData)
			.then((response) => {
				const sprintParams: SprintParams = response.data;
				sprintParams.isShortView = this.isShortView;
				sprintParams.mandatoryExists = this.mandatoryExists;
				sprintParams.isExactSearchApplied = this.isExactSearchApplied ? 'Y' : 'N';

				const sprint = Sprint.buildSprint(sprintParams);

				this.entityStorage.addSprint(sprint);

				this.appendToPlannedContainer(sprint);

				this.scroller.scrollToSprint(sprint);

				this.emit('createSprint', sprint);

				return sprint;
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	createSprintNode(sprint: Sprint)
	{
		sprint.setShortView(this.isShortView);
		sprint.setMandatory(this.mandatoryExists);

		this.entityStorage.addSprint(sprint);

		this.appendToPlannedContainer(sprint);

		this.emit('createSprintNode', sprint);
	}

	appendToPlannedContainer(sprint: Sprint)
	{
		const container = this.getSprintsContainer().querySelector('.tasks-scrum__sprints--planned');

		Dom.append(sprint.render(), container);

		Dom.removeClass(container, '--empty');

		sprint.onAfterAppend();

		this.adjustSprintListWidth();

		this.updatePlannedSprints(
			this.entityStorage.getPlannedSprints(),
			!Type.isUndefined(this.entityStorage.getActiveSprint())
		);
	}

	moveSprintToActiveListNode(sprint: Sprint)
	{
		const container = this.getSprintsContainer().querySelector('.tasks-scrum__sprints--active');

		Dom.append(sprint.getNode(), container);

		Dom.removeClass(container, '--empty');
	}

	moveSprintToCompletedListNode(sprint: Sprint)
	{
		sprint.removeNode();

		this.entityStorage.removeSprint(sprint.getId());

		if (this.completedSprints.isSprintsUploaded())
		{
			this.completedSprints.addSprint(sprint);
		}

		this.adjustSprintListWidth();
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
	}

	onSprintsScroll()
	{
		this.emit('sprintsScroll');
	}

	adjustSprintListWidth()
	{
		this.updateSprintContainers();

		const hasScroll = this.getSprintsContainer().scrollHeight > this.getSprintsContainer().clientHeight;

		if (hasScroll)
		{
			Dom.addClass(this.getSprintsContainer(), '--scrollbar');
		}
		else
		{
			Dom.removeClass(this.getSprintsContainer(), '--scrollbar');
		}
	}

	updateSprintContainers()
	{
		const activeSprint = this.entityStorage.getActiveSprint();
		const activeContainer = this.getSprintsContainer().querySelector('.tasks-scrum__sprints--active');
		if (activeSprint)
		{
			Dom.removeClass(activeContainer, '--empty');
		}
		else
		{
			Dom.addClass(activeContainer, '--empty');
		}

		const plannedSprints = this.entityStorage.getPlannedSprints();
		const plannedContainer = this.getSprintsContainer().querySelector('.tasks-scrum__sprints--planned');
		if (plannedSprints.size)
		{
			Dom.removeClass(plannedContainer, '--empty');
		}
		else
		{
			Dom.addClass(plannedContainer, '--empty');
		}
	}

	updatePlannedSprints(plannedSprints: Set<Sprint>, existActiveSprint: boolean)
	{
		if (existActiveSprint)
		{
			plannedSprints.forEach((plannedSprint: Sprint) => {
				plannedSprint.disableHeaderButton();
			});
		}
		else
		{
			plannedSprints.forEach((plannedSprint: Sprint) => {
				plannedSprint.unDisableHeaderButton();
			});
		}
	}

	getScrumContainer(): HTMLElement
	{
		return this.scrumContainer;
	}

	blockScrumContainerSelect()
	{
		Dom.addClass(this.scrumContainer, '--select-none');
	}

	unblockScrumContainerSelect()
	{
		setTimeout(() => {
			Dom.removeClass(this.scrumContainer, '--select-none');
		}, 500);
	}

	showEmptySearchStub()
	{
		if (this.entityStorage.existCompletedSprint())
		{
			this.completedSprints.showEmptySearchStub();
		}
	}

	hideEmptySearchStub()
	{
		if (this.entityStorage.existCompletedSprint())
		{
			this.completedSprints.hideEmptySearchStub();
		}
	}

	showFilteredCompletedSprints(completedSprints: Map<Sprint>)
	{
		if (this.entityStorage.existCompletedSprint())
		{
			this.completedSprints.showFilteredSprints(completedSprints);
		}
	}

	hideFilteredCompletedSprints()
	{
		if (this.entityStorage.existCompletedSprint())
		{
			this.completedSprints.hideFilteredSprints();
		}
	}
}
