/* eslint-disable operator-linebreak */
import { Dom, Event, Loc, Tag, Uri } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Item, TagItem, TagSelector } from 'ui.entity-selector';
import type { Parameter, ParameterSelectorParams } from './types';
import './css/main.css';

export class DashboardParametersSelector
{
	#groups: Set<number>;
	#initialGroups: Set<number>;
	#scopes: Set<string>;
	#initialScopes: Set<string>;
	#params: Set<string>;
	#initialParams: Set<string>;
	#scopeParamsMap: {[scopeCode: string]: Parameter[]};
	#groupSelector: TagSelector;
	#scopeSelector: TagSelector;
	#paramsSelector: TagSelector;

	constructor(params: ParameterSelectorParams)
	{
		this.#groups = params.groups;
		this.#initialGroups = new Set(params.groups);

		this.#scopes = params.scopes;
		this.#initialScopes = new Set(params.scopes);

		this.#params = params.params;
		this.#initialParams = new Set(params.params);

		this.#scopeParamsMap = params.scopeParamsMap;
	}

	getValues(): { groups: Set<number>, scopes: Set<string>, params: Set<string> }
	{
		return {
			groups: this.#groups,
			scopes: this.#scopes,
			params: this.#params,
		};
	}

	getLayout(): HTMLElement
	{
		const paramsHintText = Loc.getMessage(
			'DASHBOARD_PARAMS_SELECTOR_PARAMS_HINT_MSGVER_2',
			{
				'[link]': '<a class="ui-link" onclick="top.BX.Helper.show(`redirect=detail&code=22658454`)">',
				'[/link]': '</a>',
			},
		);

		const container = Tag.render`
			<div class="dashboard-params-container">
				<div class="dashboard-params-title-container">
					<div class="dashboard-params-title">
						${Loc.getMessage('DASHBOARD_PARAMS_SELECTOR_GROUPS')}
						<span data-hint="${Loc.getMessage('DASHBOARD_PARAMS_SELECTOR_GROUPS_HINT')}"></span>
					</div>
				</div>
				<div class="dashboard-params-groups-selector"></div>

				<div class="dashboard-params-title-container">
					<div class="dashboard-params-title">
						${Loc.getMessage('DASHBOARD_PARAMS_SELECTOR_SCOPE')}
						<span data-hint="${Loc.getMessage('DASHBOARD_PARAMS_SELECTOR_SCOPE_HINT')}"></span>
					</div>
				</div>
				<div class="dashboard-params-scope-selector"></div>

				<div class="dashboard-params-title-container">
					<div>
						<div class="dashboard-params-title">
							${Loc.getMessage('DASHBOARD_PARAMS_SELECTOR_PARAMS')}
							<span data-hint-html data-hint-interactivity data-hint='${paramsHintText}'></span>
						</div>
					</div>
					<div class="dashboard-params-list-link">
						${Loc.getMessage('DASHBOARD_PARAMS_SELECTOR_PARAMS_LIST')}
					</div>
				</div>
				<div class="dashboard-params-params-selector"></div>
			</div>
		`;
		BX.UI.Hint.init(container);

		this.#initGroupSelector();
		this.#groupSelector.renderTo(container.querySelector('.dashboard-params-groups-selector'));

		// Scope selector will be loaded on GroupSelector's onLoadScope event handler.
		const stubScopeSelector: TagSelector = new TagSelector({ locked: true });
		stubScopeSelector.renderTo(container.querySelector('.dashboard-params-scope-selector'));

		// Param selector will be loaded on ScopeSelector's onLoadScope event handler.
		const stubParamsSelector: TagSelector = new TagSelector({ locked: true });
		stubParamsSelector.renderTo(container.querySelector('.dashboard-params-params-selector'));

		Event.bind(container.querySelector('.dashboard-params-list-link'), 'click', this.#openParamListSlider.bind(this));

		return container;
	}

	selectGroups(groupIds: number[]): void
	{
		for (const groupId: number of groupIds)
		{
			const groupItem: ?Item = this.#groupSelector.getDialog().getItem({ id: groupId, entityId: 'biconnector-superset-group' });
			if (groupItem)
			{
				groupItem.select();
			}
		}
	}

	#initGroupSelector(): void
	{
		const preselectedItems = [];
		this.#groups.forEach((groupId: number) => {
			preselectedItems.push(['biconnector-superset-group', groupId]);
		});

		const groupSelector: TagSelector = new TagSelector({
			multiple: true,
			dialogOptions: {
				id: 'biconnector-superset-group',
				context: 'biconnector-superset-group',
				enableSearch: false,
				dropdownMode: true,
				showAvatars: true,
				compactView: false,
				dynamicLoad: true,
				preload: true,
				width: 383,
				height: 419,
				entities: [
					{
						id: 'biconnector-superset-group',
						dynamicLoad: true,
						options: {},
					},
				],
				preselectedItems,
				events: {
					onLoad: () => {
						this.#initScopeSelector();
					},
					'Item:onSelect': (event: BaseEvent) => {
						const item: Item = event.getData().item;
						this.#groups.add(item.getId());
						const groupScopes: string[] = item.getCustomData().get('groupScopes') ?? [];
						for (const groupScopeCode: string of groupScopes)
						{
							const scopeItem: Item = this.#scopeSelector.getDialog().getItem({ id: groupScopeCode, entityId: 'biconnector-superset-scope' });
							scopeItem.setDeselectable(false);
							scopeItem.select();
							const scopeTag: TagItem = this.#scopeSelector.getTag({ id: groupScopeCode, entityId: 'biconnector-superset-scope' });
							scopeTag.setDeselectable(false);
							scopeTag.render();
						}
						this.#onChange();
					},
					'Item:onDeselect': (event: BaseEvent) => {
						const item: Item = event.getData().item;
						this.#groups.delete(item.getId());
						const groupScopes: string[] = item.getCustomData().get('groupScopes') ?? [];
						for (const groupScopeCode: string of groupScopes)
						{
							const scopeItem: Item = this.#scopeSelector.getDialog().getItem({ id: groupScopeCode, entityId: 'biconnector-superset-scope' });
							scopeItem.setDeselectable(true);
							scopeItem.deselect();
						}
						this.#onChange();
					},
				},
			},
		});
		Dom.addClass(groupSelector.getDialog().getContainer(), 'biconnector-settings-entity-selector');

		this.#groupSelector = groupSelector;
	}

	#initScopeSelector(): void
	{
		const groupItems: Item[] = this.#groupSelector.getDialog().getSelectedItems();
		const groupScopes: Set<string> = new Set();
		for (const groupItem: Item of groupItems)
		{
			for (const groupScope of groupItem.getCustomData().get('groupScopes') ?? [])
			{
				groupScopes.add(groupScope);
			}
		}

		const preselectedItems = [];
		const undeselectedItems = [];
		let hasSelectedAutomatedSolutions = false;
		this.#scopes.forEach((scopeCode: string) => {
			if (scopeCode.startsWith('automated_solution_'))
			{
				hasSelectedAutomatedSolutions = true;
			}

			if (groupScopes.has(scopeCode))
			{
				undeselectedItems.push(['biconnector-superset-scope', scopeCode]);
			}
			preselectedItems.push(['biconnector-superset-scope', scopeCode]);
		});

		const scopeSelector: TagSelector = new TagSelector({
			multiple: true,
			dialogOptions: {
				id: 'biconnector-superset-scope',
				context: 'biconnector-superset-scope',
				enableSearch: false,
				dropdownMode: true,
				showAvatars: false,
				compactView: true,
				dynamicLoad: true,
				preload: true,
				width: 383,
				height: 419,
				entities: [
					{
						id: 'biconnector-superset-scope',
						dynamicLoad: true,
						options: {},
					},
				],
				preselectedItems,
				undeselectedItems,
				events: {
					onLoad: (event: BaseEvent) => {
						if (hasSelectedAutomatedSolutions)
						{
							const items = event.getTarget()?.getItems();
							const automatedSolutionItem = items.find((item) => item.getId() === 'automated_solution');
							const itemNode = automatedSolutionItem.getNodes()?.values()?.next()?.value;
							itemNode?.setOpen(true);
						}
						this.#initParamsSelector();
					},
					'Item:onSelect': (event: BaseEvent) => {
						const item: Item = event.getData().item;
						this.#onScopeSelect(item);
					},
				},
			},
			events: {
				onBeforeTagAdd: (event: BaseEvent) => {
					const { tag } = event.getData();
					this.#scopes.add(tag.getId());
					this.#onChange();
				},
				onBeforeTagRemove: (event: BaseEvent) => {
					const { tag } = event.getData();
					this.#scopes.delete(tag.getId());
					this.#onChange();
				},
				onAfterTagRemove: (event: BaseEvent) => {
					const { tag } = event.getData();
					this.#onScopeRemove(tag.getId());
				},
			},
		});
		Dom.addClass(scopeSelector.getDialog().getContainer(), 'biconnector-settings-entity-selector');
		Dom.clean(document.querySelector('.dashboard-params-scope-selector'));
		scopeSelector.renderTo(document.querySelector('.dashboard-params-scope-selector'));

		this.#scopeSelector = scopeSelector;
	}

	#initParamsSelector(): void
	{
		const items = [];
		this.#scopes.forEach((scopeCode: string) => {
			const scopeParams: Parameter[] = this.#scopeParamsMap[scopeCode] ?? [];
			scopeParams.forEach((param: Parameter) => {
				const itemTitle = this.#getParamTitle(param.code);
				items.push({
					id: param.code,
					entityId: 'biconnector-superset-params',
					title: itemTitle.title,
					supertitle: itemTitle.supertitle,
					tabs: 'params',
					textColor: '#535C69',
				});
			});
		});

		this.#scopeParamsMap.global.forEach((param: Parameter) => {
			const itemTitle = this.#getParamTitle(param.code);
			items.push({
				id: param.code,
				entityId: 'biconnector-superset-params',
				title: itemTitle.title,
				supertitle: itemTitle.supertitle,
				tabs: 'params',
			});
		});

		const preselectedItems = [];
		const tagItems = [];
		this.#params.forEach((paramCode: string) => {
			preselectedItems.push(['biconnector-superset-params', paramCode]);
			const itemTitle = this.#getParamTitle(paramCode);
			tagItems.push({
				id: paramCode,
				entityId: 'biconnector-superset-params',
				title: itemTitle.title,
				supertitle: itemTitle.supertitle,
			});
		});

		const paramSelector: TagSelector = new TagSelector({
			id: 'biconnector-superset-params',
			multiple: true,
			items: tagItems,
			dialogOptions: {
				id: 'biconnector-superset-params',
				context: 'biconnector-superset-params',
				enableSearch: false,
				dropdownMode: true,
				showAvatars: false,
				compactView: false,
				dynamicLoad: true,
				items,
				preselectedItems,
				width: 383,
				height: 419,
				entities: [{
					id: 'biconnector-superset-params',
				}],
				tabs: [{
					id: 'params',
					title: 'params',
				}],
			},
			events: {
				onBeforeTagAdd: (event: BaseEvent) => {
					const { tag } = event.getData();
					this.#params.add(tag.getId());
					this.#onChange();
				},
				onBeforeTagRemove: (event: BaseEvent) => {
					const { tag } = event.getData();
					this.#params.delete(tag.getId());
					this.#onChange();
				},
			},
		});

		Dom.addClass(paramSelector.getDialog().getContainer(), 'biconnector-settings-entity-selector');
		Dom.clean(document.querySelector('.dashboard-params-params-selector'));
		paramSelector.renderTo(document.querySelector('.dashboard-params-params-selector'));

		this.#paramsSelector = paramSelector;

		EventEmitter.emit('BIConnector.DashboardParamsSelector:initCompleted');
	}

	#onScopeSelect(scopeItem: Item): void
	{
		const params: Parameter[] = this.#scopeParamsMap[scopeItem.getId()] ?? [];
		params.forEach((param: Parameter) => {
			const item: ?Item = this.#paramsSelector.getDialog().getItem(['biconnector-superset-params', param.code]);
			if (item)
			{
				item.select(true);
			}
			else
			{
				this.#paramsSelector.getDialog().addItem({
					id: param.code,
					title: param.title,
					entityId: 'biconnector-superset-params',
					supertitle: this.#getParamTitle(param.code).supertitle,
					tabs: 'params',
					selected: true,
				});
			}
		});
	}

	#onScopeRemove(scopeCode: string): void
	{
		const params: Parameter[] = this.#scopeParamsMap[scopeCode] ?? [];
		const dialogItems: Item[] = this.#paramsSelector.getDialog().getItems();
		params.forEach((param: Parameter) => {
			dialogItems.forEach((item: Item) => {
				if (item.getId() === param.code)
				{
					item.deselect();
					this.#paramsSelector.getDialog().removeItem(item);
				}
			});
		});
	}

	#getParamTitle(paramCode: string): { title: string, supertitle: string }
	{
		let paramTitle: string = paramCode;
		const paramScopes: Set<string> = new Set();

		this.#scopeParamsMap.global.forEach((mapParam: Parameter) => {
			if (paramCode === mapParam.code)
			{
				paramTitle = mapParam.title;
				paramScopes.add('global');
			}
		});

		Object.keys(this.#scopeParamsMap).forEach((scopeCode: string) => {
			(this.#scopeParamsMap[scopeCode] ?? []).forEach((mapParam) => {
				if (paramCode === mapParam.code)
				{
					paramTitle = mapParam.title;
					if (!paramScopes.has('global'))
					{
						paramScopes.add(scopeCode);
					}
				}
			});
		});

		const scopeNames = [];
		this.#scopeSelector.getDialog().getItems().forEach((scopeItem: Item) => {
			if (paramScopes.has(scopeItem.getId()))
			{
				scopeNames.push(scopeItem.getTitle());
			}
		});

		if (paramScopes.has('global'))
		{
			scopeNames.push(Loc.getMessage('DASHBOARD_PARAMS_SELECTOR_PARAMS_GLOBAL'));
		}

		return {
			title: paramTitle,
			supertitle: scopeNames.join(', '),
		};
	}

	#onChange(): void
	{
		const isGroupsChanged: boolean =
			this.#groups.size !== this.#initialGroups.size
			|| [...this.#groups].some((groupId: number) => !this.#initialGroups.has(groupId))
		;

		const isScopeChanged: boolean =
			this.#scopes.size !== this.#initialScopes.size
			|| [...this.#scopes].some((scopeCode: string) => !this.#initialScopes.has(scopeCode))
		;

		const isParamsChanged: boolean =
			this.#params.size !== this.#initialParams.size
			|| [...this.#params].some((paramCode: string) => !this.#initialParams.has(paramCode))
		;

		const isChanged: boolean = isScopeChanged || isParamsChanged || isGroupsChanged;

		EventEmitter.emit('BIConnector.DashboardParamsSelector:onChange', { isChanged });
	}

	#openParamListSlider(): void
	{
		const componentLink = '/bitrix/components/bitrix/biconnector.apachesuperset.dashboard.url.parameter.list/slider.php';
		const sliderLink = new Uri(componentLink);

		BX.SidePanel.Instance.open(
			sliderLink.toString(),
			{
				width: 600,
				allowChangeHistory: false,
			},
		);
	}
}
