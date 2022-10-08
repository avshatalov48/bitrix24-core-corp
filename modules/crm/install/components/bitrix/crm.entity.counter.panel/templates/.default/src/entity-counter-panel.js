import { Loc, Reflection, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { CounterPanel, CounterItem } from 'ui.counterpanel';
import EntityCounterManager from './entity-counter-manager';
import EntityCounterType from './entity-counter-type';
import EntityCounterFilterManager from './entity-counter-filter-manager';

import type { EntityCounterPanelOptions } from './entity-counter-panel-options';

const namespace = Reflection.namespace('BX.Crm');

class EntityCounterPanel extends CounterPanel
{
	#id: String;
	#entityTypeId: Number;
	#userId: Number;
	#userName: String;
	#data: Array;
	#counterManager: ?EntityCounterManager;
	#filterManager: ?EntityCounterFilterManager;
	#filterLastPresetId: String;
	#filterLastPreset: Object;

	constructor(options: EntityCounterPanelOptions): void
	{
		if (!Type.isPlainObject(options))
		{
			throw 'BX.Crm.EntityCounterPanel: The "options" argument must be object.';
		}

		const data = Type.isPlainObject(options.data) ? options.data : {};

		super({
			target: BX(options.id),
			items: EntityCounterPanel.getCounterItems(data),
			multiselect: false // disable multiselect for CRM counters
		});

		this.#id = options.id;
		this.#entityTypeId = options.entityTypeId ? Text.toInteger(options.entityTypeId) : 0;
		this.#userId = options.userId ? Text.toInteger(options.userId) : 0;
		this.#userName = Type.isStringFilled(options.userName) ? options.userName : this.#userId;
		this.#data = data;

		if (BX.CrmEntityType.isDefined(this.#entityTypeId))
		{
			this.#counterManager = new EntityCounterManager({
				id: this.#id,
				entityTypeId: this.#entityTypeId,
				serviceUrl: Type.isString(options.serviceUrl) ? options.serviceUrl : '',
				codes: Type.isArray(options.codes) ? options.codes : [],
				extras: Type.isObject(options.extras) ? options.extras : {}
			});
		}

		this.#filterManager = new EntityCounterFilterManager();
		this.#filterLastPresetId = options.filterLastPresetId;
		this.#filterLastPreset = Type.isArray(options.filterLastPresetData)
			? JSON.parse(options.filterLastPresetData[0])
			: {presetId: null};

		this.#bindEvents();
	}

	#bindEvents(): void
	{
		EventEmitter.subscribe('BX.UI.CounterPanel.Item:activate', this.#onActivateItem.bind(this));
		EventEmitter.subscribe('BX.UI.CounterPanel.Item:deactivate', this.#onDeactivateItem.bind(this));
		EventEmitter.subscribe('BX.Main.Filter:apply', this.#onFilterApply.bind(this));
		EventEmitter.subscribe('BX.Crm.EntityCounterManager:onRecalculate', this.#onRecalculate.bind(this));
	}

	#onActivateItem(event: BaseEvent): void
	{
		const item = event.getData();

		if (!this.#processItemSelection(item))
		{
			return BX.PreventDefault(event);
		}
	}

	#onDeactivateItem(): void
	{
		if (this.#isAllDeactivated() && this.#filterManager.isActive())
		{
			const api = this.#filterManager.getApi();

			if (this.#filterLastPreset.presetId === 'tmp_filter')
			{
				api.setFields(this.#filterLastPreset.fields);
				api.apply();
			}
			else
			{
				api.setFilter({preset_id: this.#filterLastPreset.presetId});
			}
		}
	}

	#onFilterApply(): void
	{
		if (this.#filterManager.isActive())
		{
			this.#filterManager.updateFields();
		}
		this.#markCounters();
	}

	#onRecalculate(): void
	{
		const data = this.#counterManager.getCounterData();
		for (let code in data)
		{
			if (
				!data.hasOwnProperty(code)
				|| !(code.indexOf('crm') === 0 && data[code] >= 0) // HACK: Skip of CRM counter reset
				|| !this.#data.hasOwnProperty(code)
				|| Text.toNumber(this.#data[code].VALUE) === Text.toNumber(data[code])
			)
			{
				continue;
			}

			this.#data[code].VALUE = data[code];

			const item = this.getItemById(code);
			item.updateValue(Text.toNumber(data[code]));
			item.updateColor(EntityCounterPanel.detectCounterItemColor(this.#data[code].TYPE_NAME, Text.toNumber(data[code])));
		}
	}

	#processItemSelection(item: CounterItem): Boolean
	{
		const typeId = parseInt(this.#data[item.id].TYPE_ID, 10);
		if (typeId > 0)
		{
			const eventArgs = {
				userId: this.#userId.toString(),
				userName: this.#userName,
				counterTypeId: this.#prepareFilterTypeId(typeId),
				cancel: false
			};

			if (this.#filterManager.isActive())
			{
				const filteredFields = this.#filterManager.getFields(true);
				if (typeof (filteredFields[EntityCounterFilterManager.COUNTER_TYPE_FIELD]) === 'undefined')
				{
					this.#filterLastPreset.presetId = this.#filterManager.getApi().parent.getPreset().getCurrentPresetId();
					if (this.#filterLastPreset.presetId === 'tmp_filter')
					{
						this.#filterLastPreset.fields = filteredFields
					}

					BX.userOptions.save('crm', this.#filterLastPresetId, '', JSON.stringify(this.#filterLastPreset));
				}

				BX.onCustomEvent(window, 'BX.CrmEntityCounterPanel:applyFilter', [this, eventArgs]);
				if (eventArgs.cancel)
				{
					return false;
				}
			}
			else
			{
				return false;
			}
		}

		return true;
	}
	#prepareFilterTypeId(typeId: Number): Object
	{
		if (typeId === EntityCounterType.CURRENT)
		{
			return {
				0: EntityCounterType.OVERDUE.toString(),
				1: EntityCounterType.PENDING.toString(),
			}
		}

		return typeId.toString();
	}

	#markCounters(): void
	{
		if (!this.#filterManager.isActive())
		{
			return;
		}
		Object.entries(this.#data).forEach(([code, record]) => {
			let item = this.getItemById(code);

			this.#filterManager.isFiltered(this.#userId, parseInt(record.TYPE_ID, 10), this.#entityTypeId)
				? item.activate(false)
				: item.deactivate(false)

			// TODO: need fix it in parent CounterItem class
			if (item.value !== item.counter.getValue())
			{
				item.updateValue(item.value);
			}
		});
	}

	#isAllDeactivated(): Boolean
	{
		return this.getItems().every((record: CounterItem) => {
			return !record.isActive
		});
	}

	init(): void
	{
		super.init();

		this.#markCounters();
	}

	getId(): String
	{
		return this.#id;
	}

	static getCounterItems(input: Object): Array
	{
		return Object.entries(input).map(([code: String, item: Object]) => {
			const value = parseInt(item.VALUE, 10);

			return {
				id: code,
				title: Loc.getMessage('NEW_CRM_COUNTER_TYPE_' + item.TYPE_NAME),
				value: value,
				color: EntityCounterPanel.detectCounterItemColor(item.TYPE_NAME, value)
			};
		}, this);
	}

	static detectCounterItemColor(type: String, value: Number): String
	{
		const isRedCounter = [
			EntityCounterType.IDLE_NAME,
			EntityCounterType.OVERDUE_NAME,
			EntityCounterType.CURRENT_NAME,
		].includes(type);

		const isGreenCounter  =  [
			EntityCounterType.INCOMING_CHANNEL_NAME,
		].includes(type);

		return (value > 0)
			? (isRedCounter ? 'DANGER' : (isGreenCounter ? 'SUCCESS' : 'THEME'))
			: 'THEME';
	}
}

namespace.EntityCounterPanel = EntityCounterPanel;
