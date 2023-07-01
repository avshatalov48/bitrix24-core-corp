/**
 * @module bottom-sheet
 */
jn.define('bottom-sheet', (require, exports, module) => {
	const { Type } = require('type');
	const { prepareHexColor } = require('utils/color');

	const DEFAULT_TOP_HEIGHT_OFFSET = 70;
	const DEFAULT_MEDIUM_POSITION_PERCENT = 70;
	const DEFAULT_BACKGROUND_COLOR = '#eef2f4';

	const DEFAULT_WIDGET_PARAMS = {
		modal: true,
		title: '',
		enableNavigationBarBorder: false,
		backgroundColor: DEFAULT_BACKGROUND_COLOR,
		backdrop: {
			showOnTop: false,
			topPosition: DEFAULT_TOP_HEIGHT_OFFSET,
			onlyMediumPosition: true,
			mediumPositionPercent: DEFAULT_MEDIUM_POSITION_PERCENT,
			mediumPositionHeight: undefined,
			navigationBarColor: DEFAULT_BACKGROUND_COLOR,
			swipeAllowed: true,
			swipeContentAllowed: true,
			horizontalSwipeAllowed: false,
			hideNavigationBar: true,
			shouldResizeContent: true,
			forceDismissOnSwipeDown: true,
			helpUrl: undefined,
		},
	};

	/**
	 * @class BottomSheet
	 */
	class BottomSheet
	{
		/**
		 * @param {?String} title
		 * @param {?Object} component
		 */
		constructor({ title = '', component = null } = {})
		{
			/** @type {PageManager} */
			this.parentWidget = PageManager;
			/** @type {JSStackNavigation|function} */
			this.component = null;
			this.widget = null;

			/** @type {BottomSheetWidgetOptions} */
			this.widgetOptions = DEFAULT_WIDGET_PARAMS;

			if (Type.isStringFilled(title))
			{
				this.setTitle(title);
			}

			if (component)
			{
				this.setComponent(component);
			}
		}

		/**
		 * @public
		 * @param {Object} parentWidget
		 * @return {BottomSheet}
		 */
		setParentWidget(parentWidget)
		{
			this.parentWidget = parentWidget;

			return this;
		}

		/**
		 * @param {LayoutComponent|function} component
		 * @return {BottomSheet}
		 */
		setComponent(component)
		{
			this.component = component;

			return this;
		}

		/**
		 * @public
		 * @param {string} title
		 * @return {BottomSheet}
		 */
		setTitle(title)
		{
			if (!Type.isString(title))
			{
				throw new TypeError('title must be a string');
			}

			this.widgetOptions.title = title;

			this.checkNavigationBar();

			return this;
		}

		checkNavigationBar()
		{
			if (Type.isStringFilled(this.widgetOptions.title))
			{
				this.showNavigationBar();
			}
			else
			{
				this.hideNavigationBar();
			}
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		showNavigationBar()
		{
			this.widgetOptions.backdrop.hideNavigationBar = false;

			return this;
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		hideNavigationBar()
		{
			this.widgetOptions.backdrop.hideNavigationBar = true;

			return this;
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		showNavigationBarBorder()
		{
			this.widgetOptions.enableNavigationBarBorder = true;

			return this;
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		hideNavigationBarBorder()
		{
			this.widgetOptions.enableNavigationBarBorder = false;

			return this;
		}

		/**
		 * @public
		 * @param {string} navigationBarColor
		 * @return {BottomSheet}
		 */
		setNavigationBarColor(navigationBarColor)
		{
			if (!Type.isStringFilled(navigationBarColor))
			{
				throw new TypeError('navigationBarColor must be a filled string');
			}

			navigationBarColor = prepareHexColor(navigationBarColor);

			this.widgetOptions.backdrop.navigationBarColor = navigationBarColor;

			return this;
		}

		/**
		 * @public
		 * @param {string} backgroundColor
		 * @return {BottomSheet}
		 */
		setBackgroundColor(backgroundColor)
		{
			if (!Type.isStringFilled(backgroundColor))
			{
				throw new TypeError('backgroundColor must be a filled string');
			}

			backgroundColor = prepareHexColor(backgroundColor);

			this.widgetOptions.backgroundColor = backgroundColor;

			return this;
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		enableShowOnTop()
		{
			this.widgetOptions.backdrop.showOnTop = true;

			this.disableOnlyMediumPosition();

			return this;
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		disableShowOnTop()
		{
			this.widgetOptions.backdrop.showOnTop = false;

			return this;
		}

		/**
		 * @public
		 * @param {number} topPosition
		 * @return {BottomSheet}
		 */
		setTopPosition(topPosition)
		{
			if (!Type.isNumber(topPosition))
			{
				throw new TypeError('topPosition must be a number');
			}

			this.widgetOptions.backdrop.topPosition = topPosition;

			return this;
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		enableOnlyMediumPosition()
		{
			this.widgetOptions.backdrop.onlyMediumPosition = true;

			this.disableShowOnTop();

			return this;
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		disableOnlyMediumPosition()
		{
			this.widgetOptions.backdrop.onlyMediumPosition = false;

			return this;
		}

		/**
		 * @public
		 * @param {number} mediumPositionPercent
		 * @return {BottomSheet}
		 */
		setMediumPositionPercent(mediumPositionPercent)
		{
			if (!Type.isNumber(mediumPositionPercent))
			{
				throw new TypeError('mediumPositionPercent must be a number');
			}

			if (mediumPositionPercent < 0 || mediumPositionPercent > 100)
			{
				throw new TypeError('mediumPositionPercent must be between 0 and 100');
			}

			this.widgetOptions.backdrop.mediumPositionPercent = mediumPositionPercent;

			return this;
		}

		/**
		 * @public
		 * @param {number} mediumPositionHeight
		 * @return {BottomSheet}
		 */
		setMediumPositionHeight(mediumPositionHeight)
		{
			if (!Type.isNumber(mediumPositionHeight))
			{
				throw new TypeError('mediumPositionHeight must be a number');
			}

			this.widgetOptions.backdrop.mediumPositionHeight = mediumPositionHeight;

			return this;
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		enableSwipe()
		{
			this.widgetOptions.backdrop.swipeAllowed = true;

			return this;
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		disableSwipe()
		{
			this.widgetOptions.backdrop.swipeAllowed = false;

			return this;
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		enableContentSwipe()
		{
			this.widgetOptions.backdrop.swipeContentAllowed = true;

			return this;
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		disableContentSwipe()
		{
			this.widgetOptions.backdrop.swipeContentAllowed = false;

			return this;
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		enableHorizontalSwipe()
		{
			this.widgetOptions.backdrop.horizontalSwipeAllowed = true;

			return this;
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		disableHorizontalSwipe()
		{
			this.widgetOptions.backdrop.horizontalSwipeAllowed = false;

			return this;
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		enableForceDismissOnSwipeDown()
		{
			this.widgetOptions.backdrop.forceDismissOnSwipeDown = true;

			return this;
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		disableForceDismissOnSwipeDown()
		{
			this.widgetOptions.backdrop.forceDismissOnSwipeDown = false;

			return this;
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		enableResizeContent()
		{
			this.widgetOptions.backdrop.shouldResizeContent = true;

			return this;
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		disableResizeContent()
		{
			this.widgetOptions.backdrop.shouldResizeContent = false;

			return this;
		}

		/**
		 * @public
		 * @param {string} helpUrl
		 * @return {BottomSheet}
		 */
		setHelpUrl(helpUrl)
		{
			if (!Type.isStringFilled(helpUrl))
			{
				throw new TypeError('helpUrl must be a filled string');
			}

			this.widgetOptions.helpUrl = helpUrl;

			return this;
		}

		/**
		 * @public
		 * @param {?number} topOffset
		 * @return {BottomSheet}
		 */
		showOnTop(topOffset = DEFAULT_TOP_HEIGHT_OFFSET)
		{
			return (
				this
					.enableShowOnTop()
					.setTopPosition(topOffset)
			);
		}

		/**
		 * @public
		 * @return {BottomSheet}
		 */
		alwaysOnTop(topOffset = DEFAULT_TOP_HEIGHT_OFFSET)
		{
			const { screen: { height: screenHeight } = {} } = device || {};

			return (
				this
					.enableOnlyMediumPosition()
					.setMediumPositionHeight(screenHeight - topOffset)
			);
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		open()
		{
			if (this.widget)
			{
				return Promise.resolve();
			}

			if (!this.component)
			{
				throw new Error('component is not set');
			}

			return new Promise((resolve) => {
				this.parentWidget
					.openWidget('layout', this.widgetOptions)
					.then((widget) => {
						this.widget = widget;

						this.widget.setTitle({ text: this.widgetOptions.title });
						this.widget.enableNavigationBarBorder(this.widgetOptions.enableNavigationBarBorder);

						const component = typeof this.component === 'function' ? this.component(widget) : this.component;
						this.widget.showComponent(component);

						resolve(this.widget);
					});
			});
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		close()
		{
			if (!this.widget)
			{
				return Promise.resolve();
			}

			return new Promise((resolve) => {
				this.widget.close(resolve);
				this.widget = null;
			});
		}
	}

	module.exports = { BottomSheet };
});
