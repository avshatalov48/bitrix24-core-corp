import {Event, Loc, Tag, Type, Dom} from 'main.core';
import {Loader} from 'main.loader';
import {EventEmitter} from 'main.core.events';

import {EntityStorage} from '../../entity/entity.storage';
import {Sprint} from '../../entity/sprint/sprint';

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

import type {SprintParams} from '../../entity/sprint/sprint';

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
					${this.renderHeader()}
					${this.renderList()}
				</div>
			</div>
		`;

		const listNode = this.node.querySelector('.tasks-scrum__sprints--completed-list');

		Event.bind(listNode, 'transitionend', this.onTransitionEnd.bind(this));

		const observerTargetNode = listNode.querySelector('.tasks-scrum-completed-sprints-observer-target');

		this.bindLoad(observerTargetNode);

		return this.node;
	}

	renderHeader(): HTMLElement
	{
		const btnStyles = 'tasks-scrum__sprint--btn-dropdown ui-btn ui-btn-sm ui-btn-icon-angle-down';

		const header = Tag.render`
			<div class="tasks-scrum__sprints--completed-header">
				<div class="tasks-scrum__sprints--completed-name">
					${Loc.getMessage('TASKS_SCRUM_COMPLETED_SPRINTS_NAME')}
				</div>
				<div class="tasks-scrum__sprints--completed-stats"></div>
				<div class="tasks-scrum__sprints--completed-btn ${btnStyles}"></div>
			</div>
		`;

		const btnNode = header.querySelector('.tasks-scrum__sprints--completed-btn');
		Event.bind(btnNode, 'click', this.onBtnClick.bind(this));

		return header;
	}

	onBtnClick(event)
	{
		const node = event.currentTarget;

		const isShown = node.classList.contains('--up');

		if (isShown)
		{
			Dom.removeClass(node, '--up');
		}
		else
		{
			Dom.addClass(node, '--up');

			const listNode = this.node.querySelector('.tasks-scrum__sprints--completed-list');

			Dom.addClass(listNode, '--visible');

			if (!this.sprintsUploaded)
			{
				this.loader = this.showLoader();
			}
		}

		if (isShown)
		{
			this.hideList();
		}
		else
		{
			this.showList();
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

	bindLoad(loader: HTMLElement)
	{
		if (typeof IntersectionObserver === `undefined`)
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

		this.requestSender.getCompletedSprintsStats()
			.then((response) => {
				this.statsUploaded = true;
				this.updateStats(response.data);
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	onLoadCompletedSprints()
	{
		this.isActiveLoad = true;

		if (this.sprintsUploaded)
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
		const listNode = this.node.querySelector('.tasks-scrum__sprints--completed-list');

		const listPosition = Dom.getPosition(listNode);

		const loader = new Loader({
			target: listNode,
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
		const listNode = this.node.querySelector('.tasks-scrum__sprints--completed-list');

		sprints.forEach((sprintData: SprintParams) => {
			sprintData.isShortView = 'Y';
			const sprint = Sprint.buildSprint(sprintData);

			this.entityStorage.addSprint(sprint);

			Dom.insertBefore(
				sprint.render(),
				listNode.querySelector('.tasks-scrum-completed-sprints-observer-target')
			);
			sprint.onAfterAppend();

			this.emit('createSprint', sprint);
		});
	}

	showList()
	{
		const parentNode = this.node.querySelector('.tasks-scrum__sprints--completed');
		const listNode = this.node.querySelector('.tasks-scrum__sprints--completed-list');

		Dom.addClass(parentNode, '--open');

		listNode.style.height = `${ listNode.scrollHeight }px`;

		this.emit('adjustWidth');
	}

	hideList()
	{
		const parentNode = this.node.querySelector('.tasks-scrum__sprints--completed');
		const listNode = this.node.querySelector('.tasks-scrum__sprints--completed-list');

		Dom.removeClass(parentNode, '--open');

		listNode.style.height = `${ listNode.scrollHeight }px`;
		listNode.clientHeight;
		listNode.style.height = '0';
	}

	onTransitionEnd()
	{
		const listNode = this.node.querySelector('.tasks-scrum__sprints--completed-list');

		const isHide = (listNode.style.height === '0px');

		if (isHide)
		{
			Dom.removeClass(listNode, '--visible');
		}
		else
		{
			listNode.style.height = 'auto';
		}

		this.emit('adjustWidth');
	}
}