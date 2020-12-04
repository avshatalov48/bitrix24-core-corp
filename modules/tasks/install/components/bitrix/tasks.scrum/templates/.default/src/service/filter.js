import {Dom} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Plan} from '../view/plan';
import {RequestSender} from '../utility/request.sender';
import {Sprint} from '../entity/sprint/sprint';

type ListTypeItem = {
	NAME: string,
	VALUE: string
}

type ValueTypeField = {
	name: string,
	value: string
}

type Params = {
	filterId: string,
	scrumManager?: Plan,
	requestSender?: RequestSender
}

export class Filter extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.filterId = params.filterId;
		this.scrumManager = params.scrumManager;
		this.requestSender = params.requestSender;

		this.initUiFilterManager();
		this.bindHandlers();
	}

	initUiFilterManager()
	{
		/* eslint-disable */
		this.filterManager = BX.Main.filterManager.getById(this.filterId);
		/* eslint-enable */
	}

	bindHandlers()
	{
		/* eslint-disable */
		BX.addCustomEvent('BX.Main.Filter:apply', this.onApplyFilter.bind(this));
		/* eslint-enable */
	}

	getSearchContainer(): HTMLElement
	{
		return this.filterManager.getSearch().getContainer();
	}

	scrollToSearchContainer()
	{
		const filterSearchContainer = this.getSearchContainer();
		if (!this.isNodeInViewport(filterSearchContainer))
		{
			filterSearchContainer.scrollIntoView(true);
		}
	}

	onApplyFilter(filterId, values, filterInstance, promise, params)
	{
		if (this.filterId !== filterId || !this.scrumManager)
		{
			return;
		}

		params.autoResolve = false;

		this.emit('applyFilter', {
			promise: promise
		});
	}

	addItemToListTypeField(name: string, item: ListTypeItem)
	{
		//todo set item to list after epic crud actions

		const fieldInstances = this.filterManager.getField(name);
		const fieldOptions = this.filterManager.getFieldByName(name);

		fieldInstances.options.ITEMS.push(item);
		fieldOptions.ITEMS.push(item);

		const itemsNode = fieldInstances.node.querySelector('[data-name='+name+']');

		const items = Dom.attr(itemsNode, 'data-items');
		items.push(item);
		Dom.attr(itemsNode, 'data-items', items);
	}

	setValueToField(value: ValueTypeField)
	{
		const filterApi = this.filterManager.getApi();
		const filterFieldsValues = this.filterManager.getFilterFieldsValues();

		filterFieldsValues[value.name] = value.value;

		filterApi.setFields(filterFieldsValues);
		filterApi.apply();
	}

	getValueFromField(value: ValueTypeField): string
	{
		const filterFieldsValues = this.filterManager.getFilterFieldsValues();
		return filterFieldsValues[value.name];
	}

	resetFilter()
	{
		this.filterManager.resetFilter();
	}

	isNodeInViewport(element: HTMLElement): boolean
	{
		const rect = element.getBoundingClientRect();
		return (
			rect.top >= 0 &&
			rect.left >= 0 &&
			rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
			rect.right <= (window.innerWidth || document.documentElement.clientWidth)
		);
	}
}