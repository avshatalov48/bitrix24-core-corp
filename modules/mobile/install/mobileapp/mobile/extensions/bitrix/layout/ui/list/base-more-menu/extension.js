/**
 * @module layout/ui/list/base-more-menu
 */
jn.define('layout/ui/list/base-more-menu', (require, exports, module) => {
	const { downloadImages } = require('asset-manager');
	const { Color } = require('tokens');
	const { OutlineIconTypes } = require('assets/icons/types');
	const { Button, ButtonSize } = require('ui-system/form/buttons');

	/**
	 * @class BaseListMoreMenu
	 * @abstract
	 */
	class BaseListMoreMenu
	{
		get icons()
		{
			return {};
		}

		/**
		 * @param {Array} counters
		 * @param {String} selectedCounter
		 * @param {String} selectedSorting
		 * @param {Object} callbacks
		 */
		constructor(
			counters,
			selectedCounter,
			selectedSorting,
			callbacks = {},
		)
		{
			this.counters = counters;
			this.selectedCounter = selectedCounter;
			this.selectedSorting = selectedSorting;

			this.onCounterClick = callbacks.onCounterClick;
			this.onSortingClick = callbacks.onSortingClick;

			this.menu = null;

			setTimeout(() => this.prefetchAssets(), 1000);
		}

		/**
		 * @private
		 * @return {null|string}
		 */
		getHelpdeskArticleCode()
		{
			return null;
		}

		/**
		 * @public
		 * @param counters
		 */
		setCounters(counters)
		{
			this.counters = counters;
		}

		/**
		 * @public
		 * @param counter
		 */
		setSelectedCounter(counter)
		{
			this.selectedCounter = counter;
		}

		/**
		 * @public
		 * @param sorting
		 */
		setSelectedSorting(sorting)
		{
			this.selectedSorting = sorting;
		}

		/**
		 * @private
		 */
		prefetchAssets()
		{
			const icons = Object.values(this.icons).filter((icon) => icon !== null);

			void downloadImages(icons);
		}

		/**
		 * @public
		 * @returns {{svg: {content: string}, callback: ((function(): void)|*), type: string}}
		 */
		getMenuButton()
		{
			return Button({
				size: ButtonSize.M,
				leftIcon: OutlineIconTypes.more,
				leftIconColor: Color.base4,
				onClick: this.openMoreMenu,
			});
		}

		/**
		 * @private
		 * @returns {string}
		 */
		getMenuBackgroundColor()
		{
			return Color.bgContentPrimaryLayer;
		}

		/**
		 * @private
		 */
		openMoreMenu = () => {
			const menuItems = this.getMenuItems();

			this.menu = new UI.Menu(menuItems);
			this.menu.show();
		};

		/**
		 * @abstract
		 * @private
		 */
		getMenuItems()
		{}

		/**
		 * @param {Object} options
		 * @param {string} options.id
		 * @param {string} options.title
		 * @param {string} options.counterColor
		 * @param {boolean} options.checked
		 * @param {string} [options.testId]
		 * @param {boolean} [options.showTopSeparator=false]
		 * @param {boolean} [options.showCheckedIcon=false]
		 * @returns {Object}
		 */
		createMenuItem({
			id,
			testId,
			title,
			counterColor,
			showIcon = true,
			iconUrl,
			icon,
			checked = false,
			showCheckedIcon = false,
			showTopSeparator = false,
			sectionCode = 'default',
			sectionTitle,
			nextMenu,
		})
		{
			let iconUrlToShow = null;
			let iconToShow = null;
			if (showIcon)
			{
				iconUrlToShow = iconUrl ?? this.icons[id];
				iconToShow = icon ?? null;
			}

			return {
				id,
				testId: testId ?? id,
				title,
				iconUrl: iconUrlToShow,
				icon: iconToShow,
				sectionCode,
				sectionTitle,
				checked: (this.selectedCounter === id) || checked,
				showCheckedIcon,
				showTopSeparator,
				counterValue: this.counters[id],
				counterStyle: { backgroundColor: counterColor },
				onItemSelected: (event, item) => this.onMenuItemSelected(event, item),
				nextMenu,
			};
		}

		/**
		 * @abstract
		 * @private
		 * @param event
		 * @param item
		 */
		onMenuItemSelected(event, item)
		{}
	}

	module.exports = { BaseListMoreMenu };
});
