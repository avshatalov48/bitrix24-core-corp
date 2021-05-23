import {Dom, Event, Loc, Tag, Type} from 'main.core';

import {RequestSender} from './request.sender';
import {Filter} from '../service/filter';

import '../css/counters.css';

export type CountersData = {
	total: {
		counter: number,
		code: number
	},
	expired: {
		counter: number,
		code: number
	},
	new_comments: {
		counter: number,
		code: number
	}
}

type Params = {
	requestSender: RequestSender,
	filter: Filter,
	counters: ?CountersData,
	isOwnerCurrentUser: boolean,
	userId: number,
	groupId: number
}

export class Counters
{
	constructor(params: Params)
	{
		this.requestSender = params.requestSender;
		this.filter = params.filter;
		this.counters = (params.counters ? params.counters : null);
		this.isOwnerCurrentUser = params.isOwnerCurrentUser;
		this.userId = params.userId;
		this.groupId = params.groupId;

		this.container = null;
		this.node = null;
	}

	renderTo(container: HTMLElement)
	{
		if (!Type.isDomNode(container))
		{
			throw new Error('Counters: HTMLElement for Counters not found');
		}

		this.container = container;

		if (!this.isEmptyCounters())
		{
			Dom.append(this.renderCounters(), this.container);
		}
	}

	updateState(counters: ?CountersData)
	{
		this.counters = counters;

		if (this.isNodeCreated())
		{
			this.destroy();
			if (!this.isEmptyCounters())
			{
				Dom.append(this.renderCounters(), this.container);
			}
		}
		else
		{
			if (!this.isEmptyCounters())
			{
				Dom.append(this.renderCounters(), this.container);
			}
		}
	}

	renderTitle(): HTMLElement
	{
		const className = this.isEmptyCounters() ? 'tasks-page-name' : 'tasks-counter-page-name';
		const text = (
			this.isOwnerCurrentUser
				? Loc.getMessage('TASKS_SCRUM_COUNTER_TOTAL')
				: Loc.getMessage('TASKS_SCRUM_COUNTER_TOTAL_EMPL')
		);

		return Tag.render`
			<span class="${className}">${text}</span>
		`;
	}

	renderCounters(): HTMLElement
	{
		const newCommentsInfo = this.counters['new_comments'];
		const commentsNumber = parseInt(newCommentsInfo.counter, 10);

		this.node = Tag.render`
			<div class="tasks-counter-container">
				${this.renderTitle()}
				<span class="tasks-counter-counters">
					<span class="tasks-comment-icon ui-counter ui-counter-success">
						<span class="ui-counter-inner">${commentsNumber}</span>
					</span>
					<span class="tasks-comment-text">${this.getCommentsLabel(commentsNumber)}</span>
				</span>
				${this.renderReadAllButton()}
			</div>
		`;

		const commentIcon = this.node.querySelector('.tasks-comment-icon');
		const commentText = this.node.querySelector('.tasks-comment-text');

		const onMouseEnter = () => Dom.addClass(commentText, 'tasks-comment-text-hover');
		const onMouseLeave = () => Dom.removeClass(commentText, 'tasks-comment-text-hover');

		Event.bind(commentIcon, 'mouseenter', onMouseEnter);
		Event.bind(commentIcon, 'mouseleave', onMouseLeave);
		Event.bind(commentText, 'mouseenter', onMouseEnter);
		Event.bind(commentText, 'mouseleave', onMouseLeave);

		Event.bind(commentIcon, 'click', this.applyFilterRequiringAttentionToComments.bind(this));
		Event.bind(commentText, 'click', this.applyFilterRequiringAttentionToComments.bind(this));

		return this.node;
	}

	renderReadAllButton(): HTMLElement
	{
		const title = Loc.getMessage('TASKS_SCRUM_NEW_COMMENTS_READ_ALL_TITLE');

		const readAllButton = Tag.render`
			<span class="tasks-counter-counter-button">
				<span class="tasks-counter-counter-button-icon"></span>
				<span class="tasks-counter-counter-button-text">${title}</span>
			</span>
		`;

		Event.bind(readAllButton, 'click', this.onClickReadAll.bind(this));

		return readAllButton;
	}

	isEmptyCounters(): boolean
	{
		return !(this.counters && this.counters['new_comments'].counter > 0);
	}

	onClickReadAll()
	{
		this.requestSender.readAllTasksComment({
			groupId: this.groupId,
			userId: this.userId
		}).then((response) => {
			Dom.clean(this.container);
			this.filter.applyFilter();
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	isNodeCreated(): boolean
	{
		return (this.node !== null);
	}

	destroy()
	{
		Dom.remove(this.node);
		this.node = null;
	}

	getCommentsLabel(count: number): string
	{
		if (count > 5)
		{
			return Loc.getMessage('TASKS_SCRUM_COUNTER_NEW_COMMENTS_PLURAL_2');
		}
		else if (count === 1)
		{
			return Loc.getMessage('TASKS_SCRUM_COUNTER_NEW_COMMENTS_PLURAL_0');
		}
		else
		{
			return Loc.getMessage('TASKS_SCRUM_COUNTER_NEW_COMMENTS_PLURAL_1');
		}
	}

	applyFilterRequiringAttentionToComments()
	{
		this.filter.setValuesToField([
			{
				name: 'COUNTER_TYPE',
				value: 'TASKS_COUNTER_TYPE_12582912'
			},
			{
				name: 'PROBLEM',
				value: '12582912'
			},
		], true);
	}
}