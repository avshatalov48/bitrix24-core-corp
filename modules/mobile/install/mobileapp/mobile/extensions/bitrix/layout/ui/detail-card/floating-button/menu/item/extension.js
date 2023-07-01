/**
 * @module layout/ui/detail-card/floating-button/menu/item
 */
jn.define('layout/ui/detail-card/floating-button/menu/item', (require, exports, module) => {
	const { checkForChanges } = require('layout/ui/detail-card/action/check-for-changes');
	const { FloatingButtonMenu } = require('layout/ui/detail-card/floating-button/menu');
	const { Loc } = require('loc');
	const { Type } = require('type');

	/**
	 * @typedef {object} FloatingMenuItemOptions
	 * @property {string} [id]
	 * @property {string} [title]
	 * @property {?string} [subtitle]
	 * @property {?string} [subtitleType]
	 * @property {?string} [shortTitle]
	 * @property {boolean|function} [isSupported]
	 * @property {boolean|function} [isAvailable]
	 * @property {number} [position]
	 * @property {string} [icon]
	 * @property {object} [iconAfter]
	 * @property {?string} [sectionCode]
	 * @property {?function} [actionHandler]
	 * @property {?function} [preActionHandler]
	 * @property {?FloatingMenuItem} [parent]
	 * @property {FloatingMenuItem[]} [nestedItems]
	 * @property {?boolean} [shouldSaveInRecent]
	 * @property {?string} [tabId]
	 * @property {?boolean} [shouldLoadTab]
	 */

	/**
	 * @class FloatingMenuItem
	 */
	class FloatingMenuItem
	{
		/**
		 * @param {FloatingMenuItemOptions} options
		 */
		constructor(options)
		{
			const { id, title, nestedItems } = options;

			if (!Type.isStringFilled(id))
			{
				throw new Error('FloatingMenuItem: Key {id} must be a filled string.');
			}

			if (!Type.isStringFilled(title))
			{
				throw new Error('FloatingMenuItem: Key {title} must be a filled string.');
			}

			if (Type.isArray(nestedItems))
			{
				nestedItems.forEach((item) => item.setParentItem(this));
			}

			this.options = options;
			/** @type {DetailCardComponent} */
			this.detailCard = null;
			this.parentItem = null;
		}

		/**
		 * @param {DetailCardComponent} detailCard
		 * @return {FloatingMenuItem}
		 */
		setDetailCard(detailCard)
		{
			this.detailCard = detailCard;

			this.getNestedItems().forEach((item) => item.setDetailCard(detailCard));

			return this;
		}

		/**
		 * @param {?FloatingMenuItem} parent
		 * @return {FloatingMenuItem}
		 */
		setParentItem(parent)
		{
			this.parentItem = parent;

			return this;
		}

		/**
		 * @public
		 * @return {FloatingMenuItem}
		 */
		getParentItem()
		{
			return this.parentItem;
		}

		/**
		 * @public
		 * @return {string}
		 */
		getId()
		{
			return this.options.id;
		}

		/**
		 * @public
		 * @return {string}
		 */
		getTitle()
		{
			return this.options.title;
		}

		/**
		 * @public
		 * @return {?string}
		 */
		getSubtitle()
		{
			return this.options.subtitle;
		}

		/**
		 * @public
		 * @return {?string}
		 */
		getSubtitleType()
		{
			return this.options.subtitleType;
		}

		/**
		 * Mainly used for recent menu items in GridView.
		 *
		 * @public
		 * @return {?string}
		 */
		getShortTitle()
		{
			return this.options.shortTitle;
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isActive()
		{
			return this.isSupported() && this.isAvailable();
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isSupported()
		{
			if (this.getParentItem() && !this.getParentItem().isSupported())
			{
				return false;
			}

			if (typeof this.options.isSupported === 'function')
			{
				return this.options.isSupported(this.detailCard);
			}

			return BX.prop.getBoolean(this.options, 'isSupported', false);
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isAvailable()
		{
			if (this.getParentItem() && !this.getParentItem().isAvailable())
			{
				return false;
			}

			if (typeof this.options.isAvailable === 'function')
			{
				return this.options.isAvailable(this.detailCard);
			}

			return BX.prop.getBoolean(this.options, 'isAvailable', false);
		}

		/**
		 * @public
		 * @return {number}
		 */
		getPosition()
		{
			return BX.prop.getNumber(this.options, 'position', 0);
		}

		/**
		 * @public
		 * @return {?string}
		 */
		getIcon()
		{
			return BX.prop.getString(this.options, 'icon', null);
		}

		/**
		 * @public
		 * @return {?string}
		 */
		getIconAfter()
		{
			return BX.prop.getObject(this.options, 'iconAfter', null);
		}

		/**
		 * @public
		 * @return {string}
		 */
		getSectionCode()
		{
			if (this.isRecent())
			{
				return 'recent';
			}

			return BX.prop.getString(this.options, 'sectionCode', ContextMenuSection.getDefaultSectionName());
		}

		/**
		 * @public
		 * @return {?function}
		 */
		getOnClickCallback()
		{
			/**
			 * @param {ContextMenu} parentWidget
			 */
			return (id, parentId, { parentWidget }) => {
				const promises = [];

				if (this.getTabId() && this.shouldLoadTab())
				{
					promises.push(new Promise((resolve) => this.loadTab().then(resolve)));
				}

				if (this.hasPreActionHandler())
				{
					promises.push(new Promise((resolve) => this.handlePreAction().then(resolve)));
				}

				return (
					Promise.allSettled(promises)
						.then((result) => {
							parentWidget.close(() => this.execute(result));
						})
				);
			};
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		execute(result)
		{
			let promise = Promise.resolve();

			if (!this.isAvailable())
			{
				return promise;
			}

			if (!this.isSupported())
			{
				const { qrUrl } = this.detailCard.getComponentParams();

				qrauth.open({
					title: Loc.getMessage('M_CRM_DETAIL_MENU_DESKTOP_VERSION'),
					redirectUrl: qrUrl,
				});

				return promise;
			}

			if (this.hasNestedItems())
			{
				return promise.then(() => this.showSubMenu(this.getNestedItems()));
			}

			if (this.shouldCheckUnsavedChanges())
			{
				promise = promise.then(() => this.checkUnsavedChanges());
			}

			if (this.getTabId() && this.shouldLoadTab())
			{
				promise = promise.then(() => this.showAndLoadTab(this.getTabId()));
			}

			if (this.hasActionHandler())
			{
				return promise.then(() => this.handleAction(result));
			}

			if (this.shouldSaveInRecent())
			{
				promise = promise.then(() => this.saveInRecent());
			}

			return promise.then(() => this.emitItemAction(result));
		}

		/**
		 * @public
		 * @return {FloatingMenuItem[]}
		 */
		getNestedItems()
		{
			return BX.prop.getArray(this.options, 'nestedItems', []);
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		hasNestedItems()
		{
			return this.getNestedItems().length > 0;
		}

		/**
		 * @public
		 * @return {FloatingMenuItem[]}
		 */
		getNestedItemsRecursive()
		{
			const nestedItems = this.getNestedItems();

			return nestedItems.reduce((acc, item) => {
				acc.push(item);
				acc.push(...item.getNestedItemsRecursive());

				return acc;
			}, []);
		}

		hasActionHandler()
		{
			return typeof this.options.actionHandler === 'function';
		}

		handleAction(result)
		{
			return this.options.actionHandler(this.detailCard, result);
		}

		hasPreActionHandler()
		{
			return typeof this.options.preActionHandler === 'function';
		}

		handlePreAction()
		{
			return this.options.preActionHandler(this.detailCard);
		}

		/**
		 * @private
		 * @return {Promise}
		 */
		emitItemAction(result)
		{
			const { customEventEmitter } = this.detailCard;
			const eventArgs = [{
				actionId: this.getId(),
				tabId: this.getTabId(),
				result,
			}];

			customEventEmitter.emit('DetailCard.FloatingMenu.Item::onAction', eventArgs);
		}

		/**
		 * @public
		 * @param {boolean} isRecent
		 * @return {FloatingMenuItem}
		 */
		setIsRecent(isRecent)
		{
			this.options.isRecent = isRecent;

			return this;
		}

		isRecent()
		{
			return this.options.isRecent;
		}

		/**
		 * @public
		 * @internal
		 * @return {boolean}
		 */
		shouldSaveInRecent()
		{
			if (this.hasNestedItems())
			{
				return false;
			}

			return BX.prop.getBoolean(this.options, 'saveInRecent', true);
		}

		saveInRecent()
		{
			const { customEventEmitter } = this.detailCard;
			const eventArgs = [{
				actionId: this.getId(),
				tabId: this.getTabId(),
			}];

			customEventEmitter.emit('DetailCard.FloatingMenu.Item::onSaveInRecent', eventArgs);
		}

		shouldShowLoader()
		{
			if (this.shouldLoadTab() && !this.isTabLoaded())
			{
				return true;
			}

			return this.hasPreActionHandler();
		}

		shouldCheckUnsavedChanges()
		{
			return BX.prop.getBoolean(this.options, 'checkUnsavedChanges', !this.hasNestedItems());
		}

		checkUnsavedChanges()
		{
			return checkForChanges(this.detailCard);
		}

		/**
		 * @public
		 * @return {?string}
		 */
		getTabId()
		{
			let defaultValue = null;

			if (this.getParentItem() && this.getParentItem().getTabId())
			{
				defaultValue = this.getParentItem().getTabId();
			}

			return BX.prop.getString(this.options, 'tabId', defaultValue);
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		shouldLoadTab()
		{
			let defaultValue = true;

			if (this.getParentItem() && this.getParentItem().getTabId())
			{
				defaultValue = this.getParentItem().shouldLoadTab();
			}

			return BX.prop.getBoolean(this.options, 'loadTab', defaultValue);
		}

		/**
		 * @private
		 * @return {Promise}
		 */
		loadTab()
		{
			return this.detailCard.loadTab(this.getTabId());
		}

		isTabLoaded()
		{
			return this.detailCard.isTabLoaded(this.getTabId());
		}

		/**
		 * @private
		 * @param {string} selectedTabId
		 * @return {Promise}
		 */
		showAndLoadTab(selectedTabId)
		{
			const tabIsActive = selectedTabId === this.detailCard.activeTab;
			const tabIsLoaded = this.detailCard.isTabLoaded(selectedTabId);

			const promise = this.detailCard.showAndLoadTab(selectedTabId);

			if (tabIsActive && tabIsLoaded)
			{
				return promise;
			}

			if (tabIsLoaded)
			{
				return promise.then(() => new Promise((resolve) => setTimeout(resolve, 500)));
			}

			return promise.then(() => new Promise((resolve) => setTimeout(resolve, 200)));
		}

		showSubMenu(items)
		{
			this.menu = new FloatingButtonMenu({
				detailCard: this.detailCard,
				items,
				useRecent: false,
			});

			return this.menu.showContextMenu();
		}

		shouldShowArrow()
		{
			if (this.hasNestedItems())
			{
				return true;
			}

			return BX.prop.getBoolean(this.options, 'showArrow', false);
		}

		getBadges()
		{
			return BX.prop.getArray(this.options, 'badges', []);
		}
	}

	module.exports = { FloatingMenuItem };
});
