import { Type, Tag, Dom, ajax as Ajax } from 'main.core';
import { EventEmitter, type BaseEvent } from 'main.core.events';
import { MemoryCache, type BaseCache } from 'main.core.cache';
import { PULL } from 'pull.client';
import { getBackUrl } from './helpers';

import { createToolbarSkeleton, type ToolbarSkeletonOptions } from './skeletons/create-toolbar-skeleton';
import { createActionsBarSkeleton, type ActionsBarSkeletonOptions } from './skeletons/create-actions-bar-skeleton';
import { createGridSkeleton } from './skeletons/create-grid-skeleton';
import { createKanbanSkeleton } from './skeletons/create-kanban-skeleton';

type GridSkeletonOptions = {
	toolbarOptions?: ToolbarSkeletonOptions,
	actionsBarOptions?: ActionsBarSkeletonOptions | boolean;
};

type KanbanSkeletonOptions = {
	toolbarOptions?: ToolbarSkeletonOptions,
	actionsBarOptions?: ActionsBarSkeletonOptions | boolean;
};

export class Composite
{
	#refs: BaseCache<HTMLElement> = new MemoryCache();

	constructor()
	{
		if (this.isEnabled())
		{
			this.#bindEvents();
		}
	}

	isEnabled(): boolean
	{
		return !Type.isUndefined(window.frameRequestStart);
	}

	isReady(): boolean
	{
		return window.BX?.frameCache?.frameDataInserted === true || !Type.isUndefined(window.frameRequestFail);
	}

	showLoader(): void
	{
		if (this.isReady())
		{
			return;
		}

		const page = window.location.pathname;
		if (page === '/stream/' || page === '/stream/index.php' || page === '/index.php')
		{
			this.#showLoader(this.getLiveFeedSkeleton());

			return;
		}

		const kanbanOptions = this.#getKanbanSkeletonOptions(page);
		if (kanbanOptions !== null)
		{
			this.#showLoader(this.#createKanbanSkeleton(kanbanOptions));

			return;
		}

		const gridOptions = this.#getGridSkeletonOptions(page);
		if (gridOptions !== null)
		{
			this.#showLoader(this.#createGridSkeleton(gridOptions));

			return;
		}

		this.#showLoader();
	}

	#showLoader(skeleton: HTMLElement = null): void
	{
		const container = this.getStubContainer();
		const stub = skeleton ?? this.getLoaderContainer();
		if (!container || stub.parentNode)
		{
			return;
		}

		Dom.append(stub, container);
	}

	#getGridSkeletonOptions(page: string): GridSkeletonOptions | null
	{
		const patterns: Array<[RegExp, GridSkeletonOptions]> = [
			[/^\/workgroups\/$/, { actionsBarOptions: true }],

			[/^\/crm\/(lead|deal|quote)\/(list|category)\/.*?$/, { toolbarOptions: { showIconButton: true }, actionsBarOptions: { showCounterPanel: true, rightButtonsCount: 2 } }],
			[/^\/crm\/(contact|company)\/list\/.*?$/, { toolbarOptions: { showIconButton: true }, actionsBarOptions: { rightButtonsCount: 1 } }],
			[/^\/crm\/type\/\d+\/list\/.*?$/, { toolbarOptions: { showIconButton: true }, actionsBarOptions: { rightButtonsCount: 1 } }],
			[/^\/crm\/configs\/mycompany\/.*?$/, { toolbarOptions: { showIconButton: true } }],
			[/^\/crm\/(events|activity|webform|copilot-call-assessment|catalog)\/.*?$/, {}],
			[/^\/crm\/type\/$/, {}],

			[/^\/company\/$/, { toolbarOptions: { showIconButton: true }, actionsBarOptions: { rightButtonsCount: 1 } }],
			[/^\/company\/personal\/user\/\d+\/tasks\/(projects|flow|scrum)\/.*?$/, { actionsBarOptions: { rightButtonsCount: 2 } }],
			[/^\/company\/personal\/user\/\d+\/tasks\/(departments|templates)\/.*?$/, {}],

			[/^\/sign\/(list|contact)\/.*?$/, { toolbarOptions: { showIconButton: true }, actionsBarOptions: { rightButtonsCount: 1 } }],
			[/^\/sign\/mysafe\/.*?$/, {}],
			[/^\/sign\/b2e\/list\/.*?$/, { toolbarOptions: { showIconButton: true }, actionsBarOptions: { rightButtonsCount: 1 } }],
			[/^\/sign\/b2e\/my-documents\/.*?$/, { actionsBarOptions: true }],
			[/^\/sign\/b2e\/(settings|member_dynamic_settings|signers)\/.*?$/, {}],

			[/^\/shop\/documents\/(contractors|contractors_contacts)\/.*?$/, { toolbarOptions: { showIconButton: true }, actionsBarOptions: { rightButtonsCount: 1 } }],
			[/^\/shop\/documents\/.*?$/, { toolbarOptions: { showIconButton: true } }],
			[/^\/shop\/catalog\/\d+\/.*?$/, {}],
			[/^\/shop\/documents-catalog\/.*?$/, {}],
			[/^\/shop\/orders\/.*?$/, { toolbarOptions: { showIconButton: true }, actionsBarOptions: { rightButtonsCount: 1 } }],
			[/^\/shop\/settings\/(sale_location_type_list|sale_location_node_list|sale_person_type|sale_transact_admin|sale_basket)\/.*?$/, {}],

			[/^\/company\/lists\/\d+\/view\/\d+\/.*?$/, { toolbarOptions: { showIconButton: true } }],
			[/^\/company\/lists\/\d+\/fields\/.*?$/, { toolbarOptions: { showIconButton: true } }],

			[/^\/automation\/type\/.*?$/, {}],
			[/^\/bizproc\/userprocesses\/.*?$/, { actionsBarOptions: true }],
			[/^\/bizproc\/(start|bizproc)\/.*?$/, {}],
			[/^\/marketing\/(letter|ads|segment|template|blacklist|contact|rc|toloka)\/.*?$/, {}],
			[/^\/conference\/.*?$/, {}],
			[/^\/bi\/dashboard\/.*?$/, {}],
			[/^\/rpa\/tasks\/.*?$/, {}],
		];

		for (const pattern of patterns)
		{
			if (pattern[0].test(page))
			{
				return pattern[1];
			}
		}

		return null;
	}

	#createKanbanSkeleton(options: KanbanSkeletonOptions): HTMLElement
	{
		const actionsBarOptions = options?.actionsBarOptions ?? {};
		const showActionsBar = Type.isObject(options?.actionsBarOptions) || options?.actionsBarOptions === true;

		return Tag.render`
			<div class="grid-skeleton-wrapper">
				${createToolbarSkeleton(options.toolbarOptions)}
				${showActionsBar ? createActionsBarSkeleton(actionsBarOptions) : null}
				${createKanbanSkeleton()}
			</div>
		`;
	}

	#getKanbanSkeletonOptions(page: string): KanbanSkeletonOptions | null
	{
		const patterns: Array<[RegExp, KanbanSkeletonOptions]> = [
			[/^\/crm\/(lead|deal)\/(kanban|activity)\/.*?$/, { toolbarOptions: { showIconButton: true }, actionsBarOptions: { rightButtonsCount: 2 } }],
			[/^\/crm\/type\/\d+\/kanban\/.*?$/, {}],
			[/^\/sign\/$/, { toolbarOptions: { showIconButton: true }, actionsBarOptions: { rightButtonsCount: 1 } }],
			[/^\/sign\/b2e\/$/, { toolbarOptions: { showIconButton: true }, actionsBarOptions: { rightButtonsCount: 1 } }],
		];

		for (const pattern of patterns)
		{
			if (pattern[0].test(page))
			{
				return pattern[1];
			}
		}

		return null;
	}

	#createGridSkeleton(options: GridSkeletonOptions): HTMLElement
	{
		const actionsBarOptions = options?.actionsBarOptions ?? {};
		const showActionsBar = Type.isObject(options?.actionsBarOptions) || options?.actionsBarOptions === true;

		return Tag.render`
			<div class="grid-skeleton-wrapper">
				${createToolbarSkeleton(options.toolbarOptions)}
				${showActionsBar ? createActionsBarSkeleton(actionsBarOptions) : null}
				${createGridSkeleton()}
			</div>
		`;
	}

	getStubContainer(): HTMLElement | null
	{
		return document.querySelector('#page-area');
	}

	getLoaderContainer(): HTMLElement
	{
		return this.#refs.remember('loader', () => {
			return Tag.render`
				<div class="composite-skeleton-container">
					<div class="composite-loader-container">
						<svg class="composite-loader-circular" viewBox="25 25 50 50">
							<circle class="composite-loader-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10" />
						</svg>
					</div>
				</div>
			`;
		});
	}

	#bindEvents(): void
	{
		EventEmitter.subscribe('onFrameDataRequestFail', () => {
			console.error('Composite ajax request failed');
			top.location = `/auth/?backurl=${encodeURIComponent(getBackUrl())}`;
		});

		EventEmitter.subscribe('onAjaxFailure', (event: BaseEvent) => {
			const [reason, status] = event.getCompatData();
			const redirectUrl = `/auth/?backurl=${getBackUrl()}`;
			if (this.isEnabled() && (reason === 'auth' || (reason === 'status' && status === 401)))
			{
				console.error('Auth ajax request failed', reason, status);
				top.location = redirectUrl;
			}
		});

		if (PULL)
		{
			PULL.subscribe({
				moduleId: 'main',
				command: 'composite-cache-up',
				callback: () => {
					setTimeout(() => {
						const value = BX.localStorage.get('ajax-composite-cache-up-lock');
						if (!value)
						{
							BX.localStorage.set('ajax-composite-cache-up-lock', 'EXECUTE', 2);
							Ajax({
								url: '/blank.php',
								method: 'GET',
								processData: false,
								skipBxHeader: true,
								emulateOnload: false,
							});
						}
					}, Math.floor(Math.random() * 500));
				},
			});
		}
	}

	getLiveFeedSkeleton(): HTMLElement
	{
		return this.#refs.remember('feed-skeleton', () => {
			return Tag.render`
				<div class="page top-menu-mode start-page no-background no-all-paddings no-page-header">
					<div class="page__workarea">
						<div class="page__sidebar">${this.getLiveFeedSidebar()}</div>
						<main class="page__workarea-content">${this.getLiveFeedWorkArea()}</main>
					</div>
				</div>
			`;
		});
	}

	getLiveFeedSidebar(): HTMLElement
	{
		return this.#refs.remember('feed-sidebar', () => {
			return Tag.render`
				<div class="skeleton__white-bg-element skeleton__sidebar skeleton__intranet-ustat">
					<div class="skeleton__graph-circle"></div>
					<div class="skeleton__graph-right">
						<div class="skeleton__graph-right_top">
							<div class="skeleton__graph-right_top-circle --first"></div>
							<div class="skeleton__graph-right_top-circle"></div>
							<div class="skeleton__graph-right_top-circle"></div>
							<div class="skeleton__graph-right_top-circle"></div>
							<div class="skeleton__graph-right_top-circle"></div>
							<div class="skeleton__graph-right_top-circle"></div>
							<div class="skeleton__graph-right_top-circle"></div>
						</div>
						<div class="skeleton__graph-right_bottom">
							<div class="skeleton__graph-right_bottom-line"></div>
							<div class="skeleton__graph-right_bottom-line"></div>
						</div>
					</div>
				</div>

				<div class="skeleton__white-bg-element skeleton__sidebar">
					<div class="skeleton__sidebar-header">
						<div class="skeleton__sidebar-header_line"></div>
						<div class="skeleton__sidebar-header_circle"></div>
					</div>
					<div class="skeleton__tasks-row">
						<div class="skeleton__tasks-row_line"></div>
						<div class="skeleton__tasks-row_short-line"></div>
						<div class="skeleton__tasks-row_circle"></div>
					</div>
					<div class="skeleton__tasks-row">
						<div class="skeleton__tasks-row_line"></div>
						<div class="skeleton__tasks-row_short-line"></div>
						<div class="skeleton__tasks-row_circle"></div>
					</div>
					<div class="skeleton__tasks-row">
						<div class="skeleton__tasks-row_line"></div>
						<div class="skeleton__tasks-row_short-line"></div>
						<div class="skeleton__tasks-row_circle"></div>
					</div>
					<div class="skeleton__tasks-row">
						<div class="skeleton__tasks-row_line"></div>
						<div class="skeleton__tasks-row_short-line"></div>
						<div class="skeleton__tasks-row_circle"></div>
					</div>
				</div>
				<div class="skeleton__white-bg-element skeleton__sidebar">
					<div class="skeleton__sidebar-header">
						<div class="skeleton__sidebar-header_line"></div>
					</div>
					<div class="skeleton__birthdays-row">
						<div class="skeleton__birthdays-circle"></div>
						<div class="skeleton__birthdays-info">
							<div class="skeleton__birthdays-name"></div>
							<div class="skeleton__birthdays-date"></div>
						</div>
					</div>
					<div class="skeleton__birthdays-row">
						<div class="skeleton__birthdays-circle"></div>
						<div class="skeleton__birthdays-info">
							<div class="skeleton__birthdays-name"></div>
							<div class="skeleton__birthdays-date"></div>
						</div>
					</div>
					<div class="skeleton__birthdays-row">
						<div class="skeleton__birthdays-circle"></div>
						<div class="skeleton__birthdays-info">
							<div class="skeleton__birthdays-name"></div>
							<div class="skeleton__birthdays-date"></div>
						</div>
					</div>
					<div class="skeleton__birthdays-row">
						<div class="skeleton__birthdays-circle"></div>
						<div class="skeleton__birthdays-info">
							<div class="skeleton__birthdays-name"></div>
							<div class="skeleton__birthdays-date"></div>
						</div>
					</div>
				</div>
				<div class="skeleton__white-bg-element skeleton__sidebar">
					<div class="skeleton__sidebar-header">
						<div class="skeleton__sidebar-header_line"></div>
					</div>
					<div class="skeleton__birthdays-row">
						<div class="skeleton__birthdays-circle"></div>
						<div class="skeleton__birthdays-info">
							<div class="skeleton__birthdays-name"></div>
							<div class="skeleton__birthdays-date"></div>
						</div>
					</div>
					<div class="skeleton__birthdays-row">
						<div class="skeleton__birthdays-circle"></div>
						<div class="skeleton__birthdays-info">
							<div class="skeleton__birthdays-name"></div>
							<div class="skeleton__birthdays-date"></div>
						</div>
					</div>
					<div class="skeleton__birthdays-row">
						<div class="skeleton__birthdays-circle"></div>
						<div class="skeleton__birthdays-info">
							<div class="skeleton__birthdays-name"></div>
							<div class="skeleton__birthdays-date"></div>
						</div>
					</div>
					<div class="skeleton__birthdays-row">
						<div class="skeleton__birthdays-circle"></div>
						<div class="skeleton__birthdays-info">
							<div class="skeleton__birthdays-name"></div>
							<div class="skeleton__birthdays-date"></div>
						</div>
					</div>
				</div>
			`;
		});
	}

	getLiveFeedWorkArea(): HTMLElement
	{
		return this.#refs.remember('feed-work-area', () => {
			return Tag.render`
				<div class="skeleton__white-bg-element skeleton__feed-wrap">
					<div class="skeleton__feed-wrap_header">
						<div class="skeleton__feed-wrap_header-link --long"></div>
						<div class="skeleton__feed-wrap_header-link --one"></div>
						<div class="skeleton__feed-wrap_header-link --two"></div>
						<div class="skeleton__feed-wrap_header-link --one"></div>
						<div class="skeleton__feed-wrap_header-link --two"></div>
					</div>
					<div class="skeleton__feed-wrap_header-content">
						<div class="skeleton__feed-wrap_header-text"></div>
					</div>
				</div>
				<div class="skeleton__title-block">
					<div class="skeleton__title-block_text"></div>
				</div>
				<div class="skeleton__white-bg-element skeleton__feed-item">
					<div class="skeleton__feed-item_user-icon"></div>
					<div class="skeleton__feed-item_content">
						<div class="skeleton__feed-item_main">
							<div class="skeleton__feed-item_text --name"></div>
							<div class="skeleton__feed-item_date"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text --short"></div>
						</div>
						<div class="skeleton__feed-item_nav">
							<div class="skeleton__feed-item_nav-line --one"></div>
							<div class="skeleton__feed-item_nav-line --two"></div>
							<div class="skeleton__feed-item_nav-line --three"></div>
							<div class="skeleton__feed-item_nav-line --four"></div>
						</div>
						<div class="skeleton__feed-item_like">
							<div class="skeleton__feed-item_like-icon"></div>
							<div class="skeleton__feed-item_like-name"></div>
						</div>
						<div class="skeleton__feed-item_comment">
							<div class="skeleton__feed-item_comment-icon"></div>
							<div class="skeleton__feed-item_comment-block">
								<div class="skeleton__feed-item_comment-text"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="skeleton__white-bg-element skeleton__feed-item">
					<div class="skeleton__feed-item_user-icon"></div>
					<div class="skeleton__feed-item_content">
						<div class="skeleton__feed-item_main">
							<div class="skeleton__feed-item_text --name"></div>
							<div class="skeleton__feed-item_date"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text --short"></div>
						</div>
						<div class="skeleton__feed-item_nav">
							<div class="skeleton__feed-item_nav-line --one"></div>
							<div class="skeleton__feed-item_nav-line --two"></div>
							<div class="skeleton__feed-item_nav-line --three"></div>
							<div class="skeleton__feed-item_nav-line --four"></div>
						</div>
						<div class="skeleton__feed-item_like">
							<div class="skeleton__feed-item_like-icon"></div>
							<div class="skeleton__feed-item_like-name"></div>
						</div>
						<div class="skeleton__feed-item_comment">
							<div class="skeleton__feed-item_comment-icon"></div>
							<div class="skeleton__feed-item_comment-block">
								<div class="skeleton__feed-item_comment-text"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="skeleton__white-bg-element skeleton__feed-item">
					<div class="skeleton__feed-item_user-icon"></div>
					<div class="skeleton__feed-item_content">
						<div class="skeleton__feed-item_main">
							<div class="skeleton__feed-item_text --name"></div>
							<div class="skeleton__feed-item_date"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text --short"></div>
						</div>
						<div class="skeleton__feed-item_nav">
							<div class="skeleton__feed-item_nav-line --one"></div>
							<div class="skeleton__feed-item_nav-line --two"></div>
							<div class="skeleton__feed-item_nav-line --three"></div>
							<div class="skeleton__feed-item_nav-line --four"></div>
						</div>
						<div class="skeleton__feed-item_like">
							<div class="skeleton__feed-item_like-icon"></div>
							<div class="skeleton__feed-item_like-name"></div>
						</div>
						<div class="skeleton__feed-item_comment">
							<div class="skeleton__feed-item_comment-icon"></div>
							<div class="skeleton__feed-item_comment-block">
								<div class="skeleton__feed-item_comment-text"></div>
							</div>
						</div>
					</div>
				</div>
			`;
		});
	}
}
