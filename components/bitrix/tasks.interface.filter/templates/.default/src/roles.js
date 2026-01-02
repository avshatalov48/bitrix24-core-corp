import { Runtime, Loc } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Button, ButtonManager } from 'ui.buttons';
import { Menu, type MenuItemOptions } from 'ui.system.menu';
import { PULL } from 'pull.client';

import type { AnalyticsSender } from 'tasks.v2.lib.analytics';

export type Params = {
	button: HTMLElement,
	groupId: number,
	items: { [roleId: string]: RoleDto },
	totalCounter: number,
	selectedRoleId: string,
	analytics: AnalyticsParams,
};

type AnalyticsParams = {
	context: string,
	additionalContext: string,
	element: string,
};

type RoleDto = {
	TEXT: string,
	COUNTER: string,
};

type Role = {
	title: string,
	counter: number,
};

const defaultRole = 'view_all';

export class Roles
{
	#groupId: number;
	#selectedRoleId: string;
	#roles: { [roleId: string]: Role };
	#analytics: AnalyticsParams;

	#button: Button;
	#menu: Menu;

	constructor(params: Params)
	{
		if (!params.button)
		{
			return;
		}

		this.#groupId = params.groupId || 0;
		this.#selectedRoleId = params.selectedRoleId || defaultRole;
		this.#roles = {
			[defaultRole]: {
				title: Loc.getMessage('TASKS_ALL_ROLES'),
				counter: params.totalCounter,
			},
			...Object.fromEntries(Object.entries(params.items).map(([roleId, item]) => [roleId, {
				title: item.TEXT,
				counter: Number(item.COUNTER),
			}])),
		};
		this.#analytics = params.analytics;

		this.#initButton(params.button);
		this.#bindEvents();
	}

	#initButton(button: HTMLElement): void
	{
		this.#menu = new Menu({
			items: this.#menuItems,
			offsetTop: 6,
		});

		this.#button = ButtonManager.createFromNode(button);
		this.#button.bindEvent('click', (): void => {
			this.#menu.show(this.#button.getContainer());

			void this.#sendAnalyticsClick();
		});
	}

	#bindEvents(): void
	{
		EventEmitter.subscribe('BX.Main.Filter:beforeApply', this.#handleFilterBeforeApply);
		PULL.subscribe({
			moduleId: 'tasks',
			command: 'user_counter',
			callback: this.#handlePull,
		});
	}

	#handleFilterBeforeApply = (event: BaseEvent): void => {
		const previousRoleId = this.#selectedRoleId;
		this.#selectedRoleId = event.getData()[2].getFilterFieldsValues().ROLEID || defaultRole;
		this.#update();

		if (previousRoleId !== this.#selectedRoleId)
		{
			void this.#sendAnalyticsApply(this.#selectedRoleId);
		}
	};

	#handlePull = (data): void => {
		Object.entries(data[this.#groupId] ?? this.#getEmptyCounters()).forEach(([roleId, { total }]) => {
			this.#roles[roleId].counter = total;
		});

		this.#update();
	};

	#getEmptyCounters(): { [roleId: string]: { total: 0 } }
	{
		return Object.fromEntries(Object.keys(this.#roles).map((roleId) => [roleId, { total: 0 }]));
	}

	#update(): void
	{
		this.#button.setText(this.#roleName);
		this.#button.setRightCounter(this.#roleCounter ? { value: this.#roleCounter } : null);
		this.#menu.updateItems(this.#menuItems);
	}

	get #menuItems(): MenuItemOptions[]
	{
		const hasCounters = Object.values(this.#roles).some(({ counter }) => counter > 0);

		return Object.entries(this.#roles).map(([roleId, role: Role]) => ({
			title: role.title,
			isSelected: roleId === this.#selectedRoleId,
			onClick: (): void => EventEmitter.emit('Tasks.TopMenu:onItem', new BaseEvent({
				data: [roleId],
				compatData: [roleId],
			})),
			...(hasCounters ? {
				counter: {
					value: role.counter,
				},
			} : {}),
		}));
	}

	get #roleName(): string
	{
		return this.#roles[this.#selectedRoleId].title;
	}

	get #roleCounter(): string
	{
		return this.#roles[defaultRole].counter;
	}

	async #sendAnalyticsClick(): Promise<void>
	{
		const analytics = await this.#getAnalyticsSender();

		analytics.sendRoleClick(this.#analytics);
	}

	async #sendAnalyticsApply(role: string): Promise<void>
	{
		const analytics = await this.#getAnalyticsSender();

		analytics.sendRoleClickType(this.#analytics, {
			role,
			isFilterEnabled: role !== defaultRole,
		});
	}

	async #getAnalyticsSender(): Promise<AnalyticsSender>
	{
		return (await Runtime.loadExtension('tasks.v2.lib.analytics')).analytics;
	}
}
