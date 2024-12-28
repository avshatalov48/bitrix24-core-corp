/* eslint-disable operator-linebreak */
import { Dom, Event, Loc, Tag, Uri } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { TagSelector, Item } from 'ui.entity-selector';
import { UI } from 'ui.notification';
import { Button } from 'ui.buttons';
import { Popup } from 'main.popup';
import type { Parameter, CheckCompatibilityResult } from './types';
import './css/main.css';

export class DashboardParametersSelector
{
	#scopes: Set<string>;
	#initialScopes: Set<string>;
	#params: Set<string>;
	#initialParams: Set<string>;
	#scopeParamsMap: {[scopeCode: string]: Array<Parameter>};
	#scopeSelector: TagSelector;
	#paramsSelector: TagSelector;

	constructor(params)
	{
		this.#scopes = params.scopes;
		this.#initialScopes = new Set(params.scopes);
		this.#params = params.params;
		this.#initialParams = new Set(params.params);
		this.#scopeParamsMap = params.scopeParamsMap;

		EventEmitter.subscribe('BIConnector.DashboardParamsSelector:onLoadScope', this.#onLoadScope.bind(this));
	}

	getValues(): {scopes: Set<string>, params: Set<string>}
	{
		return {
			scopes: this.#scopes,
			params: this.#params,
		};
	}

	getLayout(): HTMLElement
	{
		this.#scopeSelector = this.#getScopeSelector();

		const paramsHintText = Loc.getMessage(
			'DASHBOARD_PARAMS_SELECTOR_PARAMS_HINT',
			{
				'[link]': '<a class="ui-link" onclick="top.BX.Helper.show(`redirect=detail&code=22658454`)">',
				'[/link]': '</a>',
			},
		);

		const container = Tag.render`
			<div class="dashboard-params-container">
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

		this.#scopeSelector.renderTo(container.querySelector('.dashboard-params-scope-selector'));

		// Params selector will be loaded in onLoadScope event handler.
		const stubParamsSelector = new TagSelector({
			locked: true,
		});
		stubParamsSelector.renderTo(container.querySelector('.dashboard-params-params-selector'));

		Event.bind(container.querySelector('.dashboard-params-list-link'), 'click', this.#openParamListSlider.bind(this));

		return container;
	}

	#getScopeSelector(): TagSelector
	{
		const preselectedItems = [];
		let hasSelectedAutomatedSolutions = false;
		this.#scopes.forEach((scope) => {
			if (scope.startsWith('automated_solution_'))
			{
				hasSelectedAutomatedSolutions = true;
			}
			preselectedItems.push(['biconnector-superset-scope', scope]);
		});

		const selector = new TagSelector({
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
				events: {
					onLoad: (event) => {
						if (hasSelectedAutomatedSolutions)
						{
							const items = event.getTarget()?.getItems();
							const automatedSolutionItem = items.find((item) => item.getId() === 'automated_solution');
							const itemNode = automatedSolutionItem.getNodes()?.values()?.next()?.value;
							itemNode?.setOpen(true);
						}
						EventEmitter.emit('BIConnector.DashboardParamsSelector:onLoadScope');
					},
					'Item:onSelect': (event: BaseEvent) => {
						const item: Item = event.getData().item;
						const compatibilityResult = this.#checkParamsCompatibility(item.getId());
						if (compatibilityResult.paramsToDelete.size > 0 || compatibilityResult.paramsNotToSave.size > 0)
						{
							const popup = this.#getCompatibilityPopup(compatibilityResult, item);
							popup.show();
						}
						else
						{
							this.#onScopeAdd(compatibilityResult);
						}
					},
				},
			},
			events: {
				onBeforeTagAdd: (event) => {
					const { tag } = event.getData();
					this.#scopes.add(tag.getId());
					this.#onChange();
				},
				onBeforeTagRemove: (event) => {
					const { tag } = event.getData();
					this.#scopes.delete(tag.getId());
					this.#onChange();
				},
				onAfterTagRemove: () => {
					this.#onScopeRemove();
				},
			},
		});
		Dom.addClass(selector.getDialog().getContainer(), 'biconnector-settings-scope-selector');

		return selector;
	}

	#onLoadScope(): void
	{
		this.#paramsSelector = this.#getParamsSelector();
		Dom.clean(document.querySelector('.dashboard-params-params-selector'));
		this.#paramsSelector.renderTo(document.querySelector('.dashboard-params-params-selector'));
	}

	#getParamsSelector(): TagSelector
	{
		const items = [];
		const availableParams = this.#getAvailableParamsByScope(this.#scopes);
		this.#scopes.forEach((scope) => {
			const scopeParams = this.#scopeParamsMap[scope] ?? [];
			scopeParams.forEach((param: Parameter) => {
				const itemTitle = this.#getItemTitle(param.code);
				const isAvailable = availableParams.has(param.code);
				items.push({
					id: param.code,
					entityId: 'biconnector-superset-params',
					title: itemTitle.title,
					supertitle: itemTitle.supertitle,
					tabs: 'params',
					customData: {
						isAvailable,
					},
					textColor: isAvailable ? '#535C69' : '#B0B6BB',
				});
			});
		});

		this.#scopeParamsMap.global.forEach((param) => {
			const itemTitle = this.#getItemTitle(param.code);
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
		this.#params.forEach((param) => {
			preselectedItems.push(['biconnector-superset-params', param.code]);
			const itemTitle = this.#getItemTitle(param);
			tagItems.push({
				id: param,
				entityId: 'biconnector-superset-params',
				title: itemTitle.title,
				supertitle: itemTitle.supertitle,
			});
		});

		return new TagSelector({
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
				events: {
					'Item:onBeforeSelect': (event: BaseEvent) => {
						const item: Item = event.getData().item;
						if (item.getCustomData().get('isAvailable') === false)
						{
							UI.Notification.Center.notify({
								content: Loc.getMessage('DASHBOARD_PARAMS_SELECTOR_PARAMS_UNAVAILABLE'),
							});
							event.preventDefault();
						}
					},
				},
			},
			events: {
				onBeforeTagAdd: (event) => {
					const { tag } = event.getData();
					this.#params.add(tag.getId());
					this.#onChange();
				},
				onBeforeTagRemove: (event) => {
					const { tag } = event.getData();
					this.#params.delete(tag.getId());
					this.#onChange();
				},
			},
		});
	}

	#checkParamsCompatibility(addedScope: string): CheckCompatibilityResult
	{
		const newParamCodes = new Set();
		const newParams = this.#scopeParamsMap[addedScope] ?? [];
		newParams.forEach((param: Parameter) => newParamCodes.add(param.code));

		if (this.#scopes.size === 0)
		{
			return {
				paramsToDelete: new Set(),
				paramsToSave: newParamCodes,
				paramsNotToSave: new Set(),
				intersection: new Set(),
			};
		}

		// Intersection of previously selected scopes
		const firstScope = this.#scopes.values().next().value;
		let availableParamsWithOldScopes = new Set(
			(this.#scopeParamsMap[firstScope] ?? []).map((item: Parameter) => item.code) ?? [],
		);
		this.#scopes.forEach((scope) => {
			const scopeParams = new Set(
				(this.#scopeParamsMap[scope] ?? []).map((item: Parameter) => item.code) ?? [],
			);
			availableParamsWithOldScopes = this.#getIntersection(availableParamsWithOldScopes, scopeParams);
		});

		const intersection = this.#getIntersection(availableParamsWithOldScopes, newParamCodes);
		const currentParamCodes = this.#params;
		const globalParams = this.#getGlobalParamsCodes();

		// Don't delete and save global params even if they are not in the intersection.
		const paramsToDelete = this.#getDifference(currentParamCodes, intersection, globalParams);
		const paramsToSave = this.#getUnion(
			this.#getDifference(intersection, currentParamCodes),
			this.#getIntersection(newParamCodes, globalParams),
		);
		const paramsNotToSave = this.#getDifference(newParamCodes, intersection, globalParams);

		return {
			paramsToDelete,
			paramsToSave,
			paramsNotToSave,
			intersection,
		};
	}

	#getCompatibilityPopup(compatibilityResult: CheckCompatibilityResult, selectedScope: Item): Popup
	{
		let paramsToDeleteText = null;
		if (compatibilityResult.paramsToDelete.size > 0)
		{
			const paramsToDeleteNames = [];
			compatibilityResult.paramsToDelete.forEach((param) => paramsToDeleteNames.push(this.#getItemTitle(param).title));

			paramsToDeleteText = Tag.render`
				<li>
					${Loc.getMessagePlural(
						'DASHBOARD_PARAMS_SELECTOR_PARAMS_COMPATIBILITY_POPUP_PARAMS_TO_DELETE',
						paramsToDeleteNames.length,
						{ '#PARAMS_TO_DELETE#': paramsToDeleteNames.join(', ') },
					)}
				</li>
			`;
		}

		let paramsNotToSaveText = null;
		if (compatibilityResult.paramsNotToSave.size > 0)
		{
			const paramsNotToSaveNames = [];
			compatibilityResult.paramsNotToSave.forEach(
				(param) => paramsNotToSaveNames.push(this.#getItemTitle(param).title),
			);
			paramsNotToSaveText = Tag.render`
				<li>
					${Loc.getMessagePlural(
						'DASHBOARD_PARAMS_SELECTOR_PARAMS_COMPATIBILITY_POPUP_PARAMS_NOT_TO_SAVE',
						paramsNotToSaveNames.length,
						{ '#PARAMS_NOT_TO_SAVE#': paramsNotToSaveNames.join(', ') },
					)}
				</li>
			`;
		}

		const content = Tag.render`
			<div class="compatibility-params">
				<div class="compatibility-params-title">
					${Loc.getMessage('DASHBOARD_PARAMS_SELECTOR_PARAMS_COMPATIBILITY_POPUP_TITLE')}
				</div>
				<div class="compatibility-params-content">
					${Loc.getMessage('DASHBOARD_PARAMS_SELECTOR_PARAMS_COMPATIBILITY_POPUP_CONTENT')}
					<ul>
						${paramsToDeleteText}
						${paramsNotToSaveText}
					</ul>
				</div>
			</div>
		`;

		const popup = new Popup({
			width: 330,
			padding: 14,
			overlay: true,
			cacheable: false,
			content,
			buttons: [
				new Button({
					text: Loc.getMessage('DASHBOARD_PARAMS_SELECTOR_PARAMS_COMPATIBILITY_POPUP_BUTTON_CANCEL'),
					color: Button.Color.PRIMARY,
					onclick: () => {
						selectedScope.deselect();
						popup.close();
					},
				}),
				new Button({
					text: Loc.getMessage('DASHBOARD_PARAMS_SELECTOR_PARAMS_COMPATIBILITY_POPUP_BUTTON_CONFIRM'),
					color: Button.Color.LIGHT_BORDER,
					onclick: () => {
						popup.close();
						selectedScope.select(true);
						this.#onScopeAdd(compatibilityResult);
					},
				}),
			],
		});

		return popup;
	}

	#onScopeAdd(compatibilityResult: CheckCompatibilityResult): void
	{
		const dialogItems = this.#paramsSelector.getDialog().getItems();
		const availableParams = this.#getAvailableParamsByScope(this.#scopes);

		dialogItems.forEach((item: Item) => {
			if (
				compatibilityResult.paramsToDelete.has(item.getId())
				|| !availableParams.has(item.getId())
			)
			{
				item.deselect();
				item.setTextColor('#B0B6BB');
				item.customData.set('isAvailable', false);
			}
		});

		[...compatibilityResult.paramsToSave.values()].forEach((param) => {
			const item = this.#paramsSelector.getDialog().getItem(['biconnector-superset-params', param]);
			if (item)
			{
				item.customData.set('isAvailable', true);
				item.setTextColor('#535C69');
				item.select(true);
			}
			else
			{
				const itemText = this.#getItemTitle(param);
				this.#paramsSelector.getDialog().addItem({
					id: param,
					title: itemText.title,
					entityId: 'biconnector-superset-params',
					supertitle: itemText.supertitle,
					tabs: 'params',
					selected: true,
				});
			}
		});

		[...compatibilityResult.paramsNotToSave.values()].forEach((param) => {
			const itemText = this.#getItemTitle(param);
			this.#paramsSelector.getDialog().addItem({
				id: param,
				title: itemText.title,
				entityId: 'biconnector-superset-params',
				supertitle: itemText.supertitle,
				tabs: 'params',
				textColor: '#B0B6BB',
				customData: {
					isAvailable: false,
				},
			});
		});
	}

	#onScopeRemove()
	{
		const availableParams = this.#getAvailableParamsByScope(this.#scopes);

		[...availableParams.values()].forEach((param) => {
			const item = this.#paramsSelector.getDialog().getItem(['biconnector-superset-params', param]);
			if (item)
			{
				item.customData.set('isAvailable', true);
				item.setTextColor('#535C69');
			}
			else
			{
				const itemText = this.#getItemTitle(param);
				this.#paramsSelector.getDialog().addItem({
					id: param,
					title: itemText.title,
					entityId: 'biconnector-superset-params',
					supertitle: itemText.supertitle,
					tabs: 'params',
				});
			}
		});

		const dialogItems = this.#paramsSelector.getDialog().getItems();
		dialogItems.forEach((item: Item) => {
			if (!availableParams.has(item.getId()))
			{
				item.deselect();
				this.#paramsSelector.getDialog().removeItem(item);
			}
		});
	}

	#getAvailableParamsByScope(scopes: Set): Set
	{
		const globalParamsCodes = this.#getGlobalParamsCodes();
		if (scopes.size === 0)
		{
			return globalParamsCodes;
		}

		let availableParams: ?Set = null;
		scopes.forEach((scope) => {
			if (availableParams === null)
			{
				availableParams = new Set((this.#scopeParamsMap[scope] ?? []).map((item) => item.code));
			}
			else
			{
				availableParams = this.#getIntersection(
					availableParams,
					new Set((this.#scopeParamsMap[scope] ?? []).map((item) => item.code)),
				);
			}
		});

		[...globalParamsCodes.values()].forEach((globalParam) => availableParams.add(globalParam));

		return availableParams;
	}

	#getGlobalParamsCodes(): Set<string>
	{
		const result = [];
		const globalParams = this.#scopeParamsMap.global ?? [];
		globalParams.forEach((param: Parameter) => result.push(param.code));

		return new Set(result);
	}

	#getItemTitle(paramCode: string): {title: string, supertitle: string}
	{
		let paramTitle = paramCode;
		const paramScopes = new Set();

		this.#scopeParamsMap.global.forEach((mapParam) => {
			if (paramCode === mapParam.code)
			{
				paramTitle = mapParam.title;
				paramScopes.add('global');
			}
		});

		Object.keys(this.#scopeParamsMap).forEach((scope) => {
			(this.#scopeParamsMap[scope] ?? []).forEach((mapParam) => {
				if (paramCode === mapParam.code)
				{
					paramTitle = mapParam.title;
					if (!paramScopes.has('global'))
					{
						paramScopes.add(scope);
					}
				}
			});
		});

		const scopeNames = [];
		this.#scopeSelector.getDialog().getItems().forEach((scopeItem) => {
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

	#onChange()
	{
		const isScopeChanged =
			this.#scopes.size !== this.#initialScopes.size
			|| [...this.#scopes].some((scope) => !this.#initialScopes.has(scope))
		;

		const isParamsChanged =
			this.#params.size !== this.#initialParams.size
			|| [...this.#params].some((param) => !this.#initialParams.has(param))
		;

		const isChanged = isScopeChanged || isParamsChanged;

		EventEmitter.emit('BIConnector.DashboardParamsSelector:onChange', { isChanged });
	}

	#openParamListSlider()
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

	/**
	 * Returns set of elements which are both in set1 and set2.
	 * @param set1
	 * @param set2
	 * @returns Set
	 */
	#getIntersection(set1: Set, set2: Set): Set
	{
		const result = new Set();
		set1.forEach((item) => {
			if (set2.has(item))
			{
				result.add(item);
			}
		});

		return result;
	}

	/**
	 * Returns set of elements which are contained in set1 but not contained in set2 and set3.
	 * @param set1
	 * @param set2
	 * @param set3
	 * @returns Set
	 */
	#getDifference(set1: Set, set2: Set, set3: ?Set): Set
	{
		const result = new Set();
		set1.forEach((item) => {
			if (!set2.has(item))
			{
				if (!set3)
				{
					result.add(item);
				}
				else if (set3 && !set3.has(item))
				// eslint-disable-next-line sonarjs/no-duplicated-branches
				{
					result.add(item);
				}
			}
		});

		return result;
	}

	/**
	 * Returns summary of elements in set1 and set2.
	 * @param set1
	 * @param set2
	 * @returns Set
	 */
	#getUnion(set1: Set, set2: Set): Set
	{
		const result = new Set();
		set1.forEach((item) => {
			result.add(item);
		});
		set2.forEach((item) => {
			result.add(item);
		});

		return result;
	}
}
