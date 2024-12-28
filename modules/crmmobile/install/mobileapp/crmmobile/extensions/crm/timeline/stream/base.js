/**
 * @module crm/timeline/stream/base
 */
jn.define('crm/timeline/stream/base', (require, exports, module) => {
	const { Loc } = require('loc');
	const { TimelineItemModel, TimelineItemFactory } = require('crm/timeline/item');
	const { isArray } = require('utils/object');
	const { mapPromise } = require('utils/function');
	const { TimelineItemBackground } = require('crm/timeline/item/ui/styles');
	const { Patch } = require('crm/timeline/stream/utils/patch');

	/**
	 * @abstract
	 * @class TimelineStreamBase
	 */
	class TimelineStreamBase
	{
		/**
		 * @param {TimelineStreamProps} props
		 */
		constructor(props)
		{
			const { items, timelineScopeEventBus, isEditable, onChange, onItemAction, entityType } = props;

			/** @type {TimelineItemModel[]} */
			this.items = [];
			this.setItems(items);

			/** @type {EventEmitter} */
			this.timelineScopeEventBus = timelineScopeEventBus;

			/** @type {boolean} */
			this.isEditable = isEditable;

			/** @type {Function} */
			this.onChangeHandler = onChange;

			/** @type {Function} */
			this.onItemActionHandler = onItemAction;

			/** @type {{string, TimelineItemBase}} */
			this.itemRefs = {};

			/** @type {object|null} */
			this.listViewRef = null;

			/** @type {ItemPositionCalculator} */
			this.itemPositionCalculator = null;

			this.entityType = entityType;
		}

		/**
		 * @public
		 * @abstract
		 * @return {TimelineListViewItem[]}
		 */
		exportToListView()
		{}

		/**
		 * @public
		 * @abstract
		 * @return {string}
		 */
		getId()
		{}

		/**
		 * @protected
		 * @abstract
		 * @return {'asc'|'desc'}
		 */
		getItemSortDirection()
		{}

		/**
		 * @public
		 * @param {object|undefined} ref
		 */
		registerListViewRef(ref)
		{
			if (ref)
			{
				this.listViewRef = ref;
			}
		}

		/**
		 * @public
		 * @param {ItemPositionCalculator} calculator
		 */
		setItemPositionCalculator(calculator)
		{
			this.itemPositionCalculator = calculator;
		}

		/**
		 * @public
		 * @param {TimelineItemProps} props
		 * @return {TimelineItemModel}
		 */
		makeItemModel(props)
		{
			return new TimelineItemModel({
				...props,
				isEditable: this.isEditable,
			});
		}

		/**
		 * @public
		 * @param {TimelineItemProps[]} rawItems
		 */
		setItems(rawItems)
		{
			this.items = rawItems.map((item) => this.makeItemModel(item));
		}

		/**
		 * @public
		 * @return {TimelineItemModel[]}
		 */
		getItems()
		{
			return this.items;
		}

		/**
		 * @public
		 * @param {string|number} itemId
		 * @return {boolean}
		 */
		hasItem(itemId)
		{
			if (this.findItem(itemId))
			{
				return true;
			}

			return false;
		}

		/**
		 * @public
		 * @param {string|number} itemId
		 * @return {TimelineItemModel|undefined}
		 */
		findItem(itemId)
		{
			return this.items.find((item) => item.id == itemId);
		}

		/**
		 * @public
		 * @param {string|number} itemId
		 * @return {number}
		 */
		findItemIndex(itemId)
		{
			return this.items.findIndex((item) => item.id == itemId);
		}

		/**
		 * @param {string|number} itemId
		 * @return {TimelineItemBase|null}
		 */
		findItemRef(itemId)
		{
			return this.itemRefs[itemId] || null;
		}

		/**
		 * @private
		 * @param {function} fn
		 * @return {Patch}
		 */
		makePatch(fn)
		{
			const itemsBefore = this.exportToListView();
			fn();
			const itemsAfter = this.exportToListView();

			return new Patch(itemsBefore, itemsAfter);
		}

		/**
		 * @private
		 * @param {Patch} patch
		 * @param {string} animationType
		 * @return {Promise}
		 */
		animateAddRemove(patch, animationType = 'automatic')
		{
			const removeItems = () => mapPromise(patch.getRemovedItems(), (item) => new Promise((resolve) => {
				const { section, index } = this.listViewRef.getElementPosition(item.key);
				this.listViewRef.deleteRow(section, index, animationType, resolve);
			}));

			const addItems = () => mapPromise(patch.getAddedItems(), (item) => {
				const position = this.itemPositionCalculator.calculateByKey(item.key);

				return (position < 0)
					? Promise.resolve()
					: this.listViewRef.insertRows([item], 0, position, animationType);
			});

			return removeItems().then(() => addItems());
		}

		/**
		 * @public
		 * @param {TimelineItemProps} props
		 * @return {Promise}
		 */
		addItem(props)
		{
			const patch = this.makePatch(() => {
				const item = this.makeItemModel(props);
				this.items.push(item);
				this.resort();
			});

			return this.animateAddRemove(patch);
		}

		/**
		 * @public
		 * @param {string|number} id
		 * @param {TimelineItemProps} props
		 * @param {boolean} animated
		 * @return {Promise}
		 */
		updateItem(id, props, animated = true)
		{
			const item = this.makeItemModel(props);
			const index = this.findItemIndex(id);
			if (index < 0)
			{
				return Promise.resolve();
			}

			const patch = this.makePatch(() => {
				this.items.splice(index, 1, item);
				this.resort();
			});

			const key = this.prepareItemKey(item);
			const exportedItem = this.exportItem(item);

			return this.animateAddRemove(patch, animated ? 'automatic' : 'none')
				.then(() => this.listViewRef.updateRows([exportedItem]))
				.then(() => {
					if (patch.isItemMoved(key))
					{
						const newPosition = this.itemPositionCalculator.calculateByKey(key);

						return this.listViewRef.moveRow(exportedItem, 0, newPosition, animated);
					}

					return Promise.resolve();
				})
				.then(() => {
					const itemRef = this.findItemRef(item.id);

					return itemRef ? itemRef.blink() : Promise.resolve();
				});
		}

		/**
		 * @public
		 * @param {string|number} id
		 * @return {Promise}
		 */
		deleteItem(id)
		{
			const patch = this.makePatch(() => {
				const index = this.findItemIndex(id);
				if (index > -1)
				{
					this.items.splice(index, 1);
				}
			});

			return this.animateAddRemove(patch);
		}

		/**
		 * @protected
		 */
		resort()
		{
			const direction = this.getItemSortDirection();

			this.items.sort((a, b) => {
				for (let i = 0; i < a.sort.length; i++)
				{
					const aValue = a.sort[i];
					const bValue = b.sort[i] || 0;

					if (aValue === bValue)
					{
						continue;
					}

					return (direction === 'asc') ? (aValue - bValue) : (bValue - aValue);
				}

				return 0;
			});
		}

		/**
		 * @param {TimelineItemModel[]} items
		 * @return {[]}
		 */
		exportCollapsibleGroup(items)
		{
			let collapsedRecords = [];
			const result = [];

			items.forEach((item) => {
				if (item.isCompatible)
				{
					collapsedRecords.push(item);
				}
				else
				{
					if (collapsedRecords.length > 0)
					{
						result.push(collapsedRecords);
						collapsedRecords = [];
					}
					result.push(item);
				}
			});

			if (collapsedRecords.length > 0)
			{
				result.push(collapsedRecords);
			}

			return result.map((item) => (isArray(item) ? this.exportCollapsedRecords(item) : this.exportItem(item)));
		}

		/**
		 * @param {TimelineItemModel[]} records
		 */
		exportCollapsedRecords(records)
		{
			const model = records[0];

			if (records.length === 1)
			{
				return {
					type: 'RecordNotSupported',
					key: `RecordNotSupported_${model.id}`,
					props: {
						title: Loc.getMessage('CRM_TIMELINE_ITEM_NOT_SUPPORTED_TITLE'),
						description: Loc.getMessage('CRM_TIMELINE_ITEM_NOT_SUPPORTED_DESCRIPTION'),
						style: {
							marginBottom: 16,
							innerOpacity: 0.6,
							backgroundColor: TimelineItemBackground.getByModel(model),
						},
					},
				};
			}

			const replacements = {
				'#NUM#': records.length,
			};

			return {
				type: 'RecordsNotSupported',
				key: `RecordsNotSupported_${model.id}`,
				props: {
					title: Loc.getMessagePlural(
						'CRM_TIMELINE_ITEM_NOT_SUPPORTED_MULTIPLE_TITLE',
						records.length,
						replacements,
					),
					description: Loc.getMessage('CRM_TIMELINE_ITEM_NOT_SUPPORTED_MULTIPLE_DESCRIPTION'),
					style: {
						marginBottom: 16,
						innerOpacity: 0.6,
						backgroundColor: TimelineItemBackground.getByModel(model),
					},
				},
			};
		}

		/**
		 * @param {TimelineItemModel} model
		 * @return {TimelineListViewItem}
		 */
		exportItem(model)
		{
			return {
				type: `TimelineItem:${this.getId()}:${model.type}`,
				key: this.prepareItemKey(model),
				props: model.props,
			};
		}

		/**
		 * @param {TimelineItemModel} model
		 * @return {string}
		 */
		prepareItemKey(model)
		{
			return `${this.getId()}_${model.id}`;
		}

		renderItem(id)
		{
			const model = this.findItem(id);
			if (!model)
			{
				return null;
			}

			return TimelineItemFactory.make(model.type, {
				model,
				timelineScopeEventBus: this.timelineScopeEventBus,
				ref: (ref) => {
					this.itemRefs[id] = ref;
				},
				onAction: this.onItemAction.bind(this),
				entityType: this.entityType,
			});
		}

		/**
		 * @return {Promise}
		 */
		onChange()
		{
			return new Promise((resolve) => {
				if (this.onChangeHandler)
				{
					this.onChangeHandler(this).then(resolve);
				}
				resolve();
			});
		}

		onItemAction(params)
		{
			if (this.onItemActionHandler)
			{
				this.onItemActionHandler(params);
			}
		}
	}

	module.exports = { TimelineStreamBase };
});
