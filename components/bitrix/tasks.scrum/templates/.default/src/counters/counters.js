import {Type} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {PULL as Pull} from 'pull.client';

import {Filter} from '../service/filter';

import {RequestSender} from '../utility/request.sender';
import {EntityStorage} from '../utility/entity.storage';

import {PullCounters} from './pull.counters';

type Params = {
	requestSender: RequestSender,
	entityStorage: EntityStorage,
	filter: Filter,
	isOwnerCurrentUser: boolean,
	userId: number,
	groupId: number
}

export class Counters
{
	constructor(params: Params)
	{
		this.requestSender = params.requestSender;
		this.entityStorage = params.entityStorage;
		this.filterService = params.filter;

		this.isOwnerCurrentUser = params.isOwnerCurrentUser;

		this.userId = parseInt(params.userId, 10);
		this.groupId = parseInt(params.groupId, 10);

		this.filter = this.filterService.getFilterManager();

		this.updateFields();
		this.bindEvents();
		this.subscribeToPull();
	}

	bindEvents()
	{
		EventEmitter.subscribe('BX.Main.Filter:apply', this.onFilterApply.bind(this));
		EventEmitter.subscribe('Tasks.Toolbar:onItem', this.onCounterClick.bind(this));
	}

	subscribeToPull()
	{
		Pull.subscribe(new PullCounters({
			requestSender: this.requestSender,
			entityStorage: this.entityStorage,
			filterService: this.filterService,
			userId: this.userId,
			groupId: this.groupId
		}));
	}

	onFilterApply()
	{
		this.updateFields();
	}

	updateFields()
	{
		this.fields = this.filter.getFilterFieldsValues();
	}

	onCounterClick(baseEvent: BaseEvent)
	{
		const data = baseEvent.getData();

		if (data.counter && data.counter.filter)
		{
			this.toggleByField({[data.counter.filterField]: data.counter.filterValue});
		}
	}

	isFilteredByField(field)
	{
		if (!Object.keys(this.fields).includes(field))
		{
			return false;
		}

		if (Type.isArray(this.fields[field]))
		{
			return this.fields[field].length > 0;
		}

		return this.fields[field] !== '';
	}

	isFilteredByFieldValue(field, value)
	{
		return this.isFilteredByField(field) && this.fields[field] === value;
	}

	toggleByField(field)
	{
		const name = Object.keys(field)[0];
		const value = field[name];

		if (!this.isFilteredByFieldValue(name, value))
		{
			this.filter.getApi().extendFilter({[name]: value});
			return;
		}

		this.filter.getFilterFields().forEach((field) => {
			if (field.getAttribute('data-name') === name)
			{
				this.filter.getFields().deleteField(field);
			}
		});

		this.filter.getSearch().apply();
	}
}