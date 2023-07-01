import {Event, Loc, Tag, Type, Dom} from 'main.core';
import {Loader} from 'main.loader';
import {EventEmitter} from 'main.core.events';

import 'main.polyfill.intersectionobserver';

import {EntityStorage} from '../../entity/entity.storage';
import {Sprint, SprintParams} from '../../entity/sprint/sprint';

import {RequestSender} from '../../utility/request.sender';

type Params = {
	requestSender: RequestSender,
	entityStorage: EntityStorage,
	pageNumber: number,
	isShortView: 'Y' | 'N'
}

type Stats = {
	numberSprints: number,
	averageNumberTasks: number,
	averageNumberStoryPoints: number,
	averagePercentageCompletion: number
}

import '../../css/completed.sprints.css';

export class CompletedSprints extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.CompletedSprints');

		this.requestSender = params.requestSender;
		this.entityStorage = params.entityStorage;
		this.pageNumber = parseInt(params.pageNumber, 10);
		this.isShortView = params.isShortView;

		this.statsUploaded = false;
		this.sprintsUploaded = false;

		this.loader = null;

		this.isActiveLoad = false;

		this.node = null;
		this.header = null;
		this.filteredSprintsNode = null;
		this.listNode = null;
		this.emptySearchStub = null;
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum__content">
				<div class="tasks-scrum__sprints--completed">
					<div class="tasks-scrum__sprints--completed-title">
						<span class="tasks-scrum__sprints--completed-title-text">
							${Loc.getMessage('TASKS_SCRUM_COMPLETED_SPRINTS_TITLE')}
						</span>
					</div>
					<div class="tasks-scrum__content-empty --no-results">
						${Loc.getMessage('TASKS_SCRUM_EMPTY_SEARCH_STUB_COMPLETED')}
					</div>
					<div class="tasks-scrum__sprints--filtered-sprints"></div>
					${this.renderHeader()}
					${this.renderList()}
				</div>
			</div>
		`;

		this.filteredSprintsNode = this.node.querySelector('.tasks-scrum__sprints--filtered-sprints');
		this.listNode = this.node.querySelector('.tasks-scrum__sprints--completed-list');

		this.emptySearchStub = this.node.querySelector('.tasks-scrum__content-empty');

		Event.bind(this.listNode, 'transitionend', this.onTransitionEnd.bind(this));

		const observerTargetNode = this.listNode.querySelector('.tasks-scrum-completed-sprints-observer-target');

		this.bindLoad(observerTargetNode);

		return this.node;
	}

	showEmptySearchStub(): void
	{
		Dom.addClass(this.emptySearchStub, '--open');

		this.hideFilteredHeader();
	}

	hideEmptySearchStub(): void
	{
		Dom.removeClass(this.emptySearchStub, '--open');

		this.showHeader();
	}

	renderHeader(): HTMLElement
	{
		const btnStyles = 'tasks-scrum__sprint--btn-dropdown ui-btn ui-btn-sm ui-btn-icon-angle-down';

		this.header = Tag.render`
			<div class="tasks-scrum__sprints--completed-header">
				<div class="tasks-scrum__sprints--completed-name">
					${Loc.getMessage('TASKS_SCRUM_COMPLETED_SPRINTS_NAME')}
				</div>
				<div class="tasks-scrum__sprints--completed-stats"></div>
				<div class="tasks-scrum__sprints--completed-btn ${btnStyles}"></div>
			</div>
		`;

		const btnNode = this.header.querySelector('.tasks-scrum__sprints--completed-btn');
		Event.bind(btnNode, 'click', this.onBtnClick.bind(this));

		return this.header;
	}

	onBtnClick(event)
	{
		const node = event.currentTarget;

		const isShown = Dom.hasClass(node, '--up');

		if (isShown)
		{
			Dom.removeClass(node, '--up');

			this.hideList();
		}
		else
		{
			Dom.addClass(node, '--up');

			Dom.addClass(this.listNode, '--visible');

			this.showList();

			if (!this.isSprintsUploaded() && Type.isNull(this.loader))
			{
				this.loader = this.showLoader();
			}
		}

		this.onLoadStats();
	}

	renderList(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum__sprints--completed-list">
				${this.renderObserverTarget()}
			</div>
		`;
	}

	renderObserverTarget(): HTMLElement
	{
		return Tag.render`<div class="tasks-scrum-completed-sprints-observer-target"></div>`;
	}

	renderStats(stats: Stats): HTMLElement
	{
		return Tag.render`
			<div>
				${Loc.getMessage('TASKS_SCRUM_COMPLETED_SPRINTS_STATS')
					.replace('#tasks#', parseInt(stats.averageNumberTasks, 10))
					.replace('#storypoints#', parseInt(stats.averageNumberStoryPoints, 10))
					.replace('#percent#', parseInt(stats.averagePercentageCompletion, 10))
				}
			</div>
		`;
	}

	isSprintsUploaded(): boolean
	{
		return this.sprintsUploaded;
	}

	bindLoad(loader: HTMLElement)
	{
		if (Type.isUndefined(IntersectionObserver))
		{
			return;
		}

		const observer = new IntersectionObserver((entries) =>
			{
				if(entries[0].isIntersecting === true)
				{
					if (!this.isActiveLoad)
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

	onLoadStats()
	{
		if (this.statsUploaded)
		{
			return;
		}

		this.statsUploaded = true;

		this.requestSender.getCompletedSprintsStats()
			.then((response) => {
				this.updateStats(response.data);
			})
			.catch((response) => {
				this.statsUploaded = false;
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	onLoadCompletedSprints()
	{
		this.isActiveLoad = true;

		if (this.isSprintsUploaded() && Type.isNull(this.loader))
		{
			this.loader = this.showLoader();
		}

		const requestData = {
			pageNumber: this.pageNumber
		};

		this.requestSender.getCompletedSprints(requestData)
			.then((response) => {
				const data = response.data;
				if (Type.isArray(data) && data.length)
				{
					this.pageNumber++;

					this.createSprints(data);

					this.isActiveLoad = false;

					this.showList();
				}
				this.sprintsUploaded = true;
				if (this.loader)
				{
					this.loader.hide();
				}
			})
			.catch((response) => {
				if (this.loader)
				{
					this.loader.hide();
				}
				this.isActiveLoad = false;
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	showLoader(): Loader
	{
		const listPosition = Dom.getPosition(this.listNode);

		const loader = new Loader({
			target: this.listNode,
			size: 60,
			mode: 'inline',
			offset: {
				left: `${(listPosition.width / 2 - 30)}px`
			}
		});

		loader.show();

		return loader;
	}

	updateStats(stats: Stats)
	{
		const nameNode = this.node.querySelector('.tasks-scrum__sprints--completed-name');
		const statsHeaderNode = this.node.querySelector('.tasks-scrum__sprints--completed-stats');

		nameNode.textContent = nameNode.textContent + '- ' + parseInt(stats.numberSprints, 10);

		Dom.append(this.renderStats(stats), statsHeaderNode);
	}

	createSprints(sprints: Array)
	{
		sprints.forEach((sprintData: SprintParams) => {
			sprintData.isShortView = 'Y';
			const sprint = Sprint.buildSprint(sprintData);

			this.entityStorage.addSprint(sprint);

			Dom.insertBefore(
				sprint.render(),
				this.listNode.querySelector('.tasks-scrum-completed-sprints-observer-target')
			);
			sprint.onAfterAppend();

			this.emit('createSprint', sprint);
		});
	}

	addSprint(sprint: Sprint)
	{
		this.entityStorage.addSprint(sprint);

		Dom.insertBefore(sprint.render(), this.listNode.firstElementChild);

		sprint.onAfterAppend();

		this.emit('createSprint', sprint);
	}

	showFilteredSprints(completedSprints: Map<Sprint>)
	{
		if (Type.isNull(this.filteredSprintsNode))
		{
			return;
		}

		Dom.clean(this.filteredSprintsNode);

		completedSprints
			.forEach((sprint: Sprint) => {
				Dom.append(sprint.render(), this.filteredSprintsNode);
				sprint.onAfterAppend();
				this.emit('createSprint', sprint);
				this.entityStorage.addFilteredCompletedSprint(sprint);
				setTimeout(() => sprint.toggleVisibilityContent(sprint.getContentContainer()), 100);
			})
		;

		this.hideFilteredHeader();
	}

	hideFilteredSprints()
	{
		Dom.clean(this.filteredSprintsNode);

		this.entityStorage.clearFilteredCompletedSprints();

		this.showHeader();
	}

	hideFilteredHeader()
	{
		this.hideHeader();
		this.hideList();

		Dom.removeClass(this.header.querySelector('.tasks-scrum__sprints--completed-btn'), '--up');
	}

	showHeader()
	{
		if (Type.isNull(this.header))
		{
			return;
		}

		Dom.removeClass(this.header, '--hide');
	}

	hideHeader()
	{
		if (Type.isNull(this.header))
		{
			return;
		}

		Dom.addClass(this.header, '--hide');
	}

	showList()
	{
		const parentNode = this.node.querySelector('.tasks-scrum__sprints--completed');

		Dom.addClass(parentNode, '--open');

		Dom.style(this.listNode, 'height', `${ this.listNode.scrollHeight }px`);

		this.emit('adjustWidth');
	}

	hideList()
	{
		const parentNode = this.node.querySelector('.tasks-scrum__sprints--completed');

		Dom.removeClass(parentNode, '--open');

		/* eslint-disable */
		this.listNode.style.height = `${ this.listNode.scrollHeight }px`;
		this.listNode.clientHeight;
		this.listNode.style.height = '0';
		/* eslint-enable */
	}

	onTransitionEnd()
	{
		const isHide = (Dom.style(this.listNode, 'height') === '0px');

		if (isHide)
		{
			Dom.removeClass(this.listNode, '--visible');
		}
		else
		{
			Dom.style(this.listNode, 'height', 'auto');
		}

		this.emit('adjustWidth');
	}
}