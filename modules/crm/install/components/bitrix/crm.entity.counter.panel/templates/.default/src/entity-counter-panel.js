import { Loc, Reflection, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { CounterItem, CounterPanel } from 'ui.counterpanel';
import EntityCounterManager from './entity-counter-manager';
import EntityCounterType from './entity-counter-type';
import EntityCounterFilterManager from './entity-counter-filter-manager';

import type { EntityCounterPanelOptions } from './entity-counter-panel-options';

const namespace = Reflection.namespace('BX.Crm');

class EntityCounterPanel extends CounterPanel
{
	static EXCLUDE_USERS_CODE_SUFFIX = 'excl';
	static EXCLUDE_ALL_USERS_CODE_SUFFIX = 'all_excl';

	#id: String;
	#entityTypeId: Number;
	#entityTypeName: String;
	#userId: Number;
	#userName: String;
	#codes: Array;
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
		const withExcludeUsers = Type.isBoolean(options.withExcludeUsers) ? options.withExcludeUsers : false;

		super({
			target: BX(options.id),
			items: EntityCounterPanel.getCounterItems(data, options),
			multiselect: false, // disable multiselect for CRM counters
			title: Loc.getMessage('NEW_CRM_COUNTER_TITLE_MY')
		});

		this.#id = options.id;
		this.#entityTypeId = options.entityTypeId ? Text.toInteger(options.entityTypeId) : 0;
		this.#entityTypeName = options.entityTypeName;
		this.#userId = options.userId ? Text.toInteger(options.userId) : 0;
		this.#userName = Type.isStringFilled(options.userName) ? options.userName : this.#userId;
		this.#codes = Type.isArray(options.codes) ? options.codes : [];
		this.#data = data;

		if (BX.CrmEntityType.isDefined(this.#entityTypeId))
		{
			this.#counterManager = new EntityCounterManager({
				id: this.#id,
				entityTypeId: this.#entityTypeId,
				serviceUrl: Type.isString(options.serviceUrl) ? options.serviceUrl : '',
				codes: this.#codes,
				extras: Type.isObject(options.extras) ? options.extras : {},
				withExcludeUsers: withExcludeUsers,
			});
		}

		this.#filterManager = new EntityCounterFilterManager();
		this.#filterLastPresetId = options.filterLastPresetId;
		this.#filterLastPreset = Type.isArray(options.filterLastPresetData)
			? JSON.parse(options.filterLastPresetData[0])
			: {presetId: null};

		this.#bindEvents(options);
	}

	#bindEvents(options: EntityCounterPanelOptions): void
	{
		if (Type.isStringFilled(options.lockedCallback))
		{
			BX.bind(BX(options.id), 'click', function() {
				eval(options.lockedCallback);
			});
		}
		else
		{
			EventEmitter.subscribe('BX.UI.CounterPanel.Item:activate', this.#onActivateItem.bind(this));
			EventEmitter.subscribe('BX.UI.CounterPanel.Item:deactivate', this.#onDeactivateItem.bind(this));
			EventEmitter.subscribe('BX.Main.Filter:apply', this.#onFilterApply.bind(this));
			EventEmitter.subscribe('BX.Crm.EntityCounterManager:onRecalculate', this.#onRecalculate.bind(this));
		}
	}

	#onActivateItem(event: BaseEvent): void
	{
		const item = event.getData();

		if (!this.#processItemSelection(item))
		{
			return BX.PreventDefault(event);
		}
	}

	#onDeactivateItem(event: BaseEvent): void
	{
		this.#deactivateLinkedMenuItem(event.getData());
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
		let isValueUpdated = false;

		const isNoSliders = BX.SidePanel.Instance.getTopSlider() === null;
		const data = this.#counterManager.getCounterData();
		const parentItem = this.getItemById(EntityCounterPanel.getMenuParentItemId(this.#codes));

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

			isValueUpdated = isValueUpdated || true;
		}

		if (parentItem)
		{
			parentItem.updateValue(this.#getParentItemTotalValue());
		}
	}

	#processItemSelection(item: CounterItem): Boolean
	{
		const isOtherUsersFilter = item.id.endsWith(EntityCounterPanel.EXCLUDE_USERS_CODE_SUFFIX);
		const typeId = parseInt(this.#data[item.id].TYPE_ID, 10);
		if (typeId > 0)
		{
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

				// BX.onCustomEvent(window, 'BX.CrmEntityCounterPanel:applyFilter', [this, eventArgs]);

				const userId = isOtherUsersFilter ? EntityCounterFilterManager.FILTER_OTHER_USERS : this.#userId.toString();
				const userName = isOtherUsersFilter ? Loc.getMessage('NEW_CRM_COUNTER_TYPE_OTHER') : this.#userName;
				const counterTypeId = this.#prepareFilterTypeId(typeId);


				const api = this.#filterManager.getApi();

				let fields = {
					"ACTIVITY_COUNTER": BX.Type.isPlainObject(counterTypeId)
						? counterTypeId
						: { 0: counterTypeId }
				};

				if (this.#entityTypeId === BX.CrmEntityType.enumeration.order)
				{
					fields = {
						...fields,
						"RESPONSIBLE_ID": { 0: userId },
						"RESPONSIBLE_ID_label": [ userName ],
					}
				}
				else
				{
					fields = {
						...fields,
						"ASSIGNED_BY_ID": { 0: userId },
						"ASSIGNED_BY_ID_label": [ userName ],
					}
				}

				api.setFields(fields);
				api.apply({'COUNTER': this.#makeFilterAnalyticsLabel(counterTypeId)});
			}
			else
			{
				return false;
			}
		}

		return true;
	}

	// entityTypeName
	#makeFilterAnalyticsLabel(counterTypeId: Object | string): string
	{
		if (this.#entityTypeName && counterTypeId)
		{
			return 'CRM_' + this.#entityTypeName + '_COUNTER_TYPE_' + counterTypeId;
		}
		return '';
	}

	#prepareFilterTypeId(typeId: Number): Object
	{
		if (typeId === EntityCounterType.CURRENT)
		{
			return {
				0: EntityCounterType.OVERDUE.toString(),
				1: EntityCounterType.READY_TODO.toString(),
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

		const parentItem = this.getItemById(EntityCounterPanel.getMenuParentItemId(this.#codes));

		let isOtherUsersFilterUse = false;

		Object.entries(this.#data).forEach(([code, record]) => {
			let item = this.getItemById(code);

			const isOtherUsersFilter = item.id.endsWith(EntityCounterPanel.EXCLUDE_USERS_CODE_SUFFIX);

			if (this.#filterManager.isFiltered(this.#userId, parseInt(record.TYPE_ID, 10), this.#entityTypeId, isOtherUsersFilter))
			{
				item.activate(false);

				if (isOtherUsersFilter)
				{
					isOtherUsersFilterUse = true;
				}
			}
			else
			{
				item.deactivate(false);
			}

			// TODO: need fix it in parent CounterItem class
			if (item.value !== item.counter.getValue())
			{
				item.updateValue(item.value);
			}
		});

		if (parentItem)
		{
			isOtherUsersFilterUse ? parentItem.activate(false) : parentItem.deactivate(false);
		}
	}

	#isAllDeactivated(): Boolean
	{
		return this.getItems().every((record: CounterItem) => {
			return !record.isActive
		});
	}

	#deactivateLinkedMenuItem(item: CounterItem): void
	{
		if (item.hasParentId())
		{
			const parentItem = this.getItemById(EntityCounterPanel.getMenuParentItemId(this.#codes));
			parentItem.deactivate(false);

			return;
		}

		if (item.parent)
		{
			item.getItems().forEach(childItemId => {
				let childItem = this.getItemById(childItemId);
				if (childItem.isActive)
				{
					childItem.deactivate(false);
				}
			});
		}
	}

	#getParentItemTotalValue(): number
	{
		let result = 0;

		this.getItems().forEach((record: CounterItem) => {
			if (record.hasParentId())
			{
				result += record.value;
			}
		});

		return result;
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

	static getCounterItems(input: Object, options: EntityCounterPanelOptions): Array
	{
		const withExcludeUsers = Type.isBoolean(options.withExcludeUsers) ? options.withExcludeUsers : false;
		const isRestricted = Type.isStringFilled(options.lockedCallback);
		const parentItemId = EntityCounterPanel.getMenuParentItemId(Type.isArray(options.codes) ? options.codes : [])

		let otherUsersItems = [];
		if (withExcludeUsers && !Type.isUndefined(parentItemId))
		{
			let parentTotal = 0;

			otherUsersItems = Object.entries(input).map(([code: String, item: Object]) => {
				if (code.endsWith(EntityCounterPanel.EXCLUDE_USERS_CODE_SUFFIX))
				{
					const value = parseInt(item.VALUE, 10);

					parentTotal += value;

					let color = EntityCounterPanel.detectCounterItemColor(item.TYPE_NAME, value);

					return {
						id: code,
						title: Loc.getMessage('NEW_CRM_COUNTER_TYPE_OTHER_' + item.TYPE_NAME),
						value: value,
						color: color === 'THEME' ? 'GRAY' : color, // override color to correct display on different themes
						parentId: parentItemId
					};
				}
			}, this);

			// add parent item
			otherUsersItems = [{
				id: parentItemId,
				title: Loc.getMessage('NEW_CRM_COUNTER_TYPE_OTHER_TITLE'),
				value: parentTotal,
				isRestricted: isRestricted,
				color: 'THEME'
			}].concat(otherUsersItems);
		}

		let currentUserItems = Object.entries(input).map(([code: String, item: Object]) => {
			if (!code.endsWith(EntityCounterPanel.EXCLUDE_USERS_CODE_SUFFIX))
			{
				const value = parseInt(item.VALUE, 10);

				return {
					id: code,
					title: Loc.getMessage('NEW_CRM_COUNTER_TYPE_' + item.TYPE_NAME),
					value: value,
					isRestricted: isRestricted,
					color: EntityCounterPanel.detectCounterItemColor(item.TYPE_NAME, value)
				};
			}
		}, this);

		return currentUserItems.concat(otherUsersItems).filter(item => Type.isObject(item));
	}

	static getMenuParentItemId(codes: Array): ?String
	{
		return codes.find(element => element.endsWith(EntityCounterPanel.EXCLUDE_ALL_USERS_CODE_SUFFIX));
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
