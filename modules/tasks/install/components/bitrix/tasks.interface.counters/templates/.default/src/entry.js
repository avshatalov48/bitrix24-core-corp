import {Tag, Event, Loc, ajax as Ajax, Dom} from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Menu, MenuItem } from 'main.popup';
import { Filter } from './counters-helper';
import CountersItem from './counters-item';
import { Controller as Viewed } from 'tasks.viewed';

import 'ui.fonts.opensans';
import './style.css';

export class Counters
{
	static get counterTypes()
	{
		return {
			my: [
				'expired',
				'my_expired',
				'originator_expired',
				'accomplices_expired',
				'auditor_expired',
				'new_comments',
				'my_new_comments',
				'originator_new_comments',
				'accomplices_new_comments',
				'auditor_new_comments',
				'projects_total_expired',
				'projects_total_comments',
				'sonet_total_expired',
				'sonet_total_comments',
				'groups_total_expired',
				'groups_total_comments',
				'scrum_total_comments',
				'flow_total_expired',
				'flow_total_comments',
			],
			other: [
				'project_expired',
				'project_comments',
				'projects_foreign_expired',
				'projects_foreign_comments',
				'groups_foreign_expired',
				'groups_foreign_comments',
				'sonet_foreign_expired',
				'sonet_foreign_comments',
				'scrum_foreign_comments',
			],
			additional: [
				'muted_new_comments',
			],
			expired: [
				'expired',
				'my_expired',
				'originator_expired',
				'accomplices_expired',
				'auditor_expired',
				'project_expired',
				'projects_total_expired',
				'projects_foreign_expired',
				'groups_total_expired',
				'groups_foreign_expired',
				'sonet_total_expired',
				'sonet_foreign_expired',
				'flow_total_expired',
			],
			comment: [
				'new_comments',
				'my_new_comments',
				'originator_new_comments',
				'accomplices_new_comments',
				'auditor_new_comments',
				'muted_new_comments',
				'project_comments',
				'projects_total_comments',
				'projects_foreign_comments',
				'groups_total_comments',
				'groups_foreign_comments',
				'sonet_total_comments',
				'sonet_foreign_comments',
				'scrum_total_comments',
				'scrum_foreign_comments',
				'flow_total_comments',
			],
			project: [
				'project_expired',
				'projects_total_expired',
				'projects_foreign_expired',
				'groups_total_expired',
				'groups_foreign_expired',
				'sonet_total_expired',
				'sonet_foreign_expired',
				'project_comments',
				'projects_total_comments',
				'projects_foreign_comments',
				'groups_total_comments',
				'groups_foreign_comments',
				'sonet_total_comments',
				'sonet_foreign_comments',
			],
			scrum: [
				'scrum_total_comments',
				'scrum_foreign_comments',
			],
		};
	}

	static updateTimeout = false;
	static needUpdate = false;

	static timeoutTTL = 5000;

	constructor(options)
	{
		this.userId = options.userId;
		this.targetUserId = options.targetUserId;
		this.groupId = options.groupId;
		this.counters = options.counters;
		this.initialCounterTypes = options.counterTypes;
		this.renderTo = options.renderTo;
		this.role = options.role;
		this.signedParameters = options.signedParameters;

		this.popupMenu = null;

		this.$readAll = {
			cropped: null,
			layout: null
		};
		this.$more = null;
		this.$moreArrow = null;
		this.$other = {
			cropped: null,
			layout: null
		};
		this.$myTaskHead = null;

		this.filter = new Filter({filterId: options.filterId});

		this.bindEvents();
		this.setData(this.counters);

		this.initPull();
	}

	isMyTaskList()
	{
		return this.userId === this.targetUserId;
	}

	isUserTaskList()
	{
		return (Object.keys(this.otherCounters).length === 0);
	}

	isProjectsTaskList()
	{
		return this.groupId > 0;
	}

	isProjectList()
	{
		return !this.isUserTaskList() && !this.isProjectsTaskList();
	}

	initPull()
	{
		BX.PULL.subscribe({
			moduleId: 'tasks',
			callback: data => this.processPullEvent(data),
		});

		this.extendWatch();
	}

	extendWatch()
	{
		if (this.isProjectsTaskList() || this.isProjectList())
		{
			let tagId = 'TASKS_PROJECTS';

			if (this.isProjectsTaskList())
			{
				tagId = `TASKS_PROJECTS_${this.groupId}`;
			}

			BX.PULL.extendWatch(tagId, true);
			setTimeout(() => this.extendWatch(), 29 * 60 * 1000);
		}
	}

	processPullEvent(data)
	{
		const eventHandlers = {
			user_counter: this.onUserCounter.bind(this),
			project_counter: this.onProjectCounter.bind(this),
			comment_read_all: this.onCommentReadAll.bind(this),
		};
		const has = Object.prototype.hasOwnProperty;
		const {command, params} = data;
		if (has.call(eventHandlers, command))
		{
			const method = eventHandlers[command];
			if (method)
			{
				method.apply(this, [params]);
			}
		}
	}

	bindEvents()
	{
		EventEmitter.subscribe('BX.Main.Filter:apply', this.onFilterApply.bind(this));
	}

	onFilterApply()
	{
		this.filter.updateFields();

		if (this.isRoleChanged())
		{
			this.updateRole();
			this.updateCountersData();
		}
		else
		{
			const counters = {...this.myCounters, ...this.otherCounters};
			Object.values(counters).forEach((counter) => {
				if (counter)
				{
					this.filter.isFilteredByFieldValue(counter.filterField, counter.filterValue)
						? counter.active()
						: counter.unActive()
					;
				}
			});
		}
	}

	updateCountersData()
	{
		if (Counters.updateTimeout)
		{
			Counters.needUpdate = true;
			return;
		}

		Counters.updateTimeout = true;
		Counters.needUpdate = false;

		Ajax.runComponentAction('bitrix:tasks.interface.counters', 'getCounters', {
			mode: 'class',
			data: {
				groupId: this.groupId,
				role: this.role,
				counters: this.initialCounterTypes,
			},
			signedParameters: this.signedParameters,
		}).then(
			response => this.rerender(response.data),
			response => console.log(response)
		);

		setTimeout(function() {
			Counters.updateTimeout = false;
			if (Counters.needUpdate)
			{
				this.updateCountersData();
			}
		}.bind(this), Counters.timeoutTTL);
	}

	isRoleChanged()
	{
		return this.role !== (this.filter.isFilteredByField('ROLEID') ? this.filter.fields.ROLEID : 'view_all');
	}

	updateRole()
	{
		this.role = (this.filter.isFilteredByField('ROLEID') ? this.filter.fields.ROLEID : 'view_all');
	}

	onCommentReadAll(data)
	{
		this.updateCountersData();
	}

	onUserCounter(data)
	{
		const has = Object.prototype.hasOwnProperty;
		if (
			!this.isUserTaskList()
			|| !this.isMyTaskList()
			|| !has.call(data, this.groupId)
			|| this.userId !== Number(data.userId)
		)
		{
			// most likely project counters were updated, but due to 'isSonetEnable' flag only user counters are comming
			this.updateCountersData();

			return;
		}

		let newCommentsCount = 0;

		Object.entries(data[this.groupId][this.role]).forEach(([type, value]) => {
			if (this.myCounters[type])
			{
				this.myCounters[type].updateCount(value);

				if (Counters.counterTypes.comment.includes(type))
				{
					newCommentsCount += value;
				}
			}
			else if (this.additionalCounters[type] && Counters.counterTypes.comment.includes(type))
			{
				newCommentsCount += value;
			}
		});

		if (newCommentsCount > 0)
		{
			this.$readAllInner.classList.remove('--fade');
		}

		if (data.isSonetEnabled !== undefined && data.isSonetEnabled === false)
		{
			this.updateCountersData();
		}
	}

	onProjectCounter(data)
	{
		if (this.isUserTaskList())
		{
			return;
		}

		this.updateCountersData();
	}

	getCounterItem(param: Object): Object
	{
		return new CountersItem({
			count: param.count,
			name: param.name,
			type: param.type,
			color: param.color,
			filterField: param.filterField,
			filterValue: param.filterValue,
			filter: this.filter,
		});
	}

	getCounterNameByType(type: String)
	{
		if (Counters.counterTypes.expired.includes(type))
		{
			return Loc.getMessage('TASKS_COUNTER_EXPIRED');
		}
		else if (Counters.counterTypes.comment.includes(type))
		{
			return Loc.getMessage('TASKS_COUNTER_NEW_COMMENTS');
		}
	}

	setData(counters)
	{
		this.myCounters = {};
		this.otherCounters = {};
		this.additionalCounters = {};

		const availableTypes = [
			...Counters.counterTypes.additional,
			...Counters.counterTypes.my,
			...Counters.counterTypes.other
		];

		Object.entries(counters).forEach(([type, data]) => {
			if (!availableTypes.includes(type))
			{
				return;
			}

			const counterItem = this.getCounterItem({
				type,
				name: this.getCounterNameByType(type),
				count: Number(data.VALUE),
				color: data.STYLE,
				filterField: data.FILTER_FIELD,
				filterValue: data.FILTER_VALUE,
			});

			if (Counters.counterTypes.additional.includes(type))
			{
				this.additionalCounters[type] = counterItem;
			}
			else if (Counters.counterTypes.my.includes(type))
			{
				this.myCounters[type] = counterItem;
			}
			else if (Counters.counterTypes.other.includes(type))
			{
				this.otherCounters[type] = counterItem;
			}
		});
	}

	isCroppedBlock(node: HTMLElement)
	{
		if(node)
			return node.classList.contains('--cropp');
	}

	getReadAllBlock(): HTMLElement
	{
		const counters = {...this.myCounters, ...this.otherCounters, ...this.additionalCounters};
		let newCommentsCount = 0;

		Object.entries(counters).forEach(([type, counter]) => {
			if (Counters.counterTypes.comment.includes(type))
			{
				newCommentsCount += counter.count;
			}
		});

		this.$readAllInner = Tag.render`
			<div data-role="tasks-counters--item-head-read-all" class="tasks-counters--item-head
						${newCommentsCount === 0 ? '--fade' : ''} 
						--action 
						--read-all">
				<div class="tasks-counters--item-head-read-all--icon"></div>
				<div class="tasks-counters--item-head-read-all--text">
					${Loc.getMessage('TASKS_COUNTER_READ_ALL')}
				</div>
			</div>
		`;

		let readAllClick = this.readAllForProjects.bind(this);
		if (
			this.isUserTaskList()
			|| (this.isProjectsTaskList() && this.role !== 'view_all')
		)
		{
			readAllClick = this.readAllByRole.bind(this);
		}
		else if (
			this.myCounters['scrum_total_comments']
			|| this.otherCounters['scrum_foreign_comments']
		)
		{
			readAllClick = this.readAllForScrum.bind(this);
		}

		Event.bind(this.$readAllInner, 'click', readAllClick);
		Event.bind(this.$readAllInner, 'click', () => this.$readAllInner.classList.add('--fade'));

		this.$readAll.layout = Tag.render`
			<div class="tasks-counters--item">${this.$readAllInner}</div>
		`;

		return this.$readAll.layout;
	}

	readAllByRole()
	{
		(new Viewed()).userComments({
			groupId: this.groupId,
			userId: this.userId,
			role: this.role,
		});
	}

	readAllForProjects()
	{
		const allCounters = {...this.myCounters, ...this.otherCounters};
		Object.entries(allCounters).forEach(([type, counter]) => {
			if (Counters.counterTypes.comment.includes(type))
			{
				counter.updateCount(0);
			}
		});

		(new Viewed()).projectComments({
			groupId: this.groupId,
		});
	}

	readAllForScrum()
	{
		const allCounters = {...this.myCounters, ...this.otherCounters};
		Object.entries(allCounters).forEach(([type, counter]) => {
			if (Counters.counterTypes.comment.includes(type))
			{
				counter.updateCount(0);
			}
		});

		(new Viewed()).scrumComments({
			groupId: this.groupId,
		});
	}

	getPopup()
	{
		const itemsNode = [];

		Object.values(this.otherCounters).forEach((counter) => {
			const menuItem = new MenuItem({
				html: counter.getPopupMenuItemContainer().innerHTML,
			});
			menuItem.onclick = this.onPopupItemClick.bind(this, menuItem, counter);

			itemsNode.push(menuItem);
		});

		this.popupMenu = new Menu({
			bindElement: this.$moreArrow,
			className: 'tasks-counters--scope',
			angle: {
				offset: 96,
			},
			autoHide: true,
			closeEsc: true,
			offsetTop: 5,
			offsetLeft: -67,
			animation: 'fading-slide',
			items: itemsNode,
			events: {
				onPopupShow: () => this.$more.classList.add('--hover'),
				onPopupClose: () => {
					this.$more.classList.remove('--hover');
					this.popupMenu.destroy();
				},
			},
		});

		return this.popupMenu;
	}

	onPopupItemClick(item, counter)
	{
		counter.adjustClick();
		this.popupMenu.close();
	}

	getMoreArrow()
	{
		if(!this.$moreArrow)
		{
			this.$moreArrow = Tag.render`
				<div class="tasks-counters--item-counter-arrow"></div>
			`;
		}

		return this.$moreArrow;
	}

	getMore()
	{
		let value = 0;

		Object.values(this.otherCounters).forEach((counter) => {
			value += Number(counter.count);
		});

		const count = value > 99
			? '99+'
			: value;

		this.$moreArrow = Tag.render`
			<div class="tasks-counters--item-counter-arrow"></div>
		`;

		this.$more = Tag.render`
			<div class="tasks-counters--item-counter--more">
				<div class="tasks-counters--item-counter-wrapper">
					<div class="tasks-counters--item-counter-title">${Loc.getMessage('TASKS_COUNTER_MORE')}:</div>
					<div class="tasks-counters--item-counter-num">
						${this.getInnerCounter(count)}						
					</div>
					${this.$moreArrow}
				</div>
			</div>
		`;

		Event.bind(this.$more, 'click', ()=> this.getPopup().show());

		return this.$more;
	}

	getInnerCounter(counter: number)
	{
		if (!this.$innerContainer)
		{
			this.$innerContainer = Tag.render`
				<div class="tasks-counters--item-counter-num-text --stop --without-animate">${counter}</div>		
			`;
		}

		return this.$innerContainer;
	}

	getOther()
	{
		if (Object.keys(this.otherCounters).length === 0)
		{
			return '';
		}

		const content = [];
		Object.values(this.otherCounters).forEach(counter => content.push(counter.getContainer()));

		this.$other.cropped = this.isCroppedBlock(this.$other.layout);

		this.$other.layout = Tag.render`
			<div class="tasks-counters--item --other ${this.$other.cropped ? '--cropp' : ''}">
				<div data-role="tasks-counters--item-head-other" class="tasks-counters--item-head">${Loc.getMessage('TASKS_COUNTER_OTHER')}</div>
				<div class="tasks-counters--item-content">
					${content}
					${this.getMore()}
				</div>
			</div>
		`;

		return this.$other.layout;
	}

	getContainer()
	{
		const content = [];
		Object.values(this.myCounters).forEach(counter => content.push(counter.getContainer()));

		this.$myTaskHead = Tag.render`
			<div class="tasks-counters--item-head">
				${Loc.getMessage('TASKS_COUNTER_MY')}
			</div>
		`;

		this.$element = Tag.render`
			<div class="tasks-counters tasks-counters--scope">
				<div class="tasks-counters--item">
					${this.$myTaskHead}
					<div class="tasks-counters--item-content">${content}</div>
				</div>
				${this.getOther()}
				${this.isUserTaskList() && !this.isMyTaskList() ? '' : this.getReadAllBlock()}
			</div>
		`;

		return this.$element;
	}

	rerender(counters)
	{
		this.setData(counters);
		this.render();
	}

	render()
	{
		let node = this.getContainer();
		let fakeNode = node.cloneNode(true);
		fakeNode.classList.add('task-interface-toolbar');
		fakeNode.style.position = 'fixed';
		fakeNode.style.opacity = '0';
		fakeNode.style.width = 'auto';
		fakeNode.style.pointerEvents = 'none';
		document.body.appendChild(fakeNode);
		this.nodeWidth = fakeNode.offsetWidth;
		document.body.removeChild(fakeNode);

		Dom.replace(this.renderTo.firstChild, node);
	}
}
