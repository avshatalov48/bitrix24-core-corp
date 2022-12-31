import { Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';

export default class TaskEvent
{
	constructor(params)
	{
		this.init(params);
	}

	init(params)
	{
		this.pageId = Type.isStringFilled(params.pageId) ? params.pageId : '';
		this.currentUserId = !Type.isUndefined(params.currentUserId) ? Number(params.currentUserId) : 0;
		this.groupId = !Type.isUndefined(params.groupId) ? Number(params.groupId) : 0;

		const compatMode = {
			compatMode: true,
		};

		EventEmitter.subscribe('onPullEvent-tasks', (command, params) => {
			if (command === 'user_counter')
			{
				this.onUserCounter(params);
			}
		}, compatMode);

		if (this.pageId !== 'group_tasks')
		{
			return;
		}

		document.querySelectorAll('.tasks_role_link').forEach((element) => {
			element.addEventListener('click', this.onTaskMenuItemClick.bind(this));
		});

		EventEmitter.subscribe('BX.Main.Filter:apply', (event) => {
			const [ filterId, data, ctx ] = event.getCompatData();

			this.onFilterApply(filterId, data, ctx);
		});
	}

	onTaskMenuItemClick(event)
	{
		const element = event.currentTarget;

		event.preventDefault();

		const roleId = (element.dataset.id === 'view_all' ? '' : element.dataset.id);
		const url = element.dataset.url;

		EventEmitter.emit('Tasks.TopMenu:onItem', new BaseEvent({
			compatData: [ roleId, url ],
			data: [ roleId, url ],
		}));

		document.querySelectorAll('.tasks_role_link').forEach((element) => {
			element.classList.remove('main-buttons-item-active')
		});

		element.classList.add('main-buttons-item-active')
	}

	onUserCounter(data)
	{
		if (
			this.currentUserId !== Number(data.userId)
			|| !Object.prototype.hasOwnProperty.call(data, this.groupId)
		)
		{
			return;
		}

		Object.keys(data[this.groupId]).forEach((role) => {
			const roleButton = document.getElementById(`group_panel_menu_${(this.groupId ? this.groupId + '_' : '')}${role}`);
			if (roleButton)
			{
				roleButton.querySelector('.main-buttons-item-counter').innerText = this.getCounterValue(data[this.groupId][role].total);
			}
		});
	}

	getCounterValue(value)
	{
		if (!value)
		{
			return '';
		}

		const maxValue = 99;

		return (value > maxValue ? `${maxValue}+` : value);
	}

	onFilterApply(filterId, data, ctx)
	{
		let roleId = ctx.getFilterFieldsValues().ROLEID;
		document.querySelectorAll('.tasks_role_link').forEach((element) => {
			element.classList.remove('main-buttons-item-active');
		});

		if (Type.isUndefined(roleId) || !roleId)
		{
			roleId = 'view_all';
		}

		const panelMenuNode = document.getElementById(`group_panel_menu_${this.groupId}_${roleId}`);
		if (panelMenuNode)
		{
			panelMenuNode.classList.add('main-buttons-item-active');
		}
	}
}
