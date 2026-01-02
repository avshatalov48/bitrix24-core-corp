/**
 * @module layout/ui/jn-list
 */
jn.define('layout/ui/jn-list', (require, exports, module) => {
	const { mergeImmutable } = require('utils/object');
	const { Color, Indent } = require('tokens');
	const { Loc } = require('loc');
	const { Box } = require('ui-system/layout/box');
	const { debounce } = require('utils/function');
	const { Text2 } = require('ui-system/typography/text');
	const { OptimizedListView } = require('layout/ui/optimized-list-view');
	const { Icon } = require('ui-system/blocks/icon');
	const { FloatingActionButton, FloatingActionButtonType } = require('ui-system/form/buttons/floating-action-button');
	const { JnListItem } = require('layout/ui/jn-list/src/item');
	const { JnListSearchBar } = require('layout/ui/jn-list/src/search-bar');
	const { JnListSkeleton } = require('layout/ui/jn-list/src/skeleton');
	const { JnListScopeBar } = require('layout/ui/jn-list/src/scope-bar');

	/**
	 *
	 * 	@typedef {Object} JnListProps
	 * 	@property {Function} options.onSelectionChanged
	 * 	@property {string} [options.searchString]
	 * 	@property {string} [options.searchInputPlaceholder]
	 * 	@property {Object} [options.layout]
	 * 	@property {string} [options.testId]
	 * 	@property {Object} [options.openWidgetConfig]
	 * 	@property {Array} [options.widgetRightButtons]
	 * 	@property {Function} [options.getListItemContextMenuItems]
	 * 	@property {Function} [options.contextMenuButtonDisplayField]
	 * 	@property {string} [options.badgeDisplayField]
	 * 	@property {BadgeCounterDesign} [options.badgeDesign]
	 * 	@property {string} [options.badgeText]
	 * 	@property {Function} [onListItemClick]
	 * 	@property {Function} [onScopeSelectionChanged]
	 * 	@property {Function} [onSearchStringChanged]
	 * 	@property {Color} [searchMatchColor = Color.accentMainPrimary]
	 * 	@property {boolean} [displayFloatingButton = false]
	 * 	@property {Object} [floatingButtonProps]
	 * 	@property {Icon} [floatingButtonProps.icon = Icon.PLUS]
	 * 	@property {FloatingActionButtonType} [floatingButtonProps.type = FloatingActionButtonType.COMMON]
	 * 	@property {boolean} [floatingButtonProps.accentByDefault = true]
	 * 	@property {Function} [floatingButtonProps.onClick]
	 * 	@property {Function} [floatingButtonProps.onLongClick]
	 * 	@property {boolean} [pending]
	 * 	@property {boolean} [showSkeletonOnlyForList]
	 * 	@property {Array} [scopes]

	 * @class JnList
	 */
	class JnList extends LayoutComponent
	{
		/**
		 * @param {JnListProps} props
		 * @param {string} [props.title]
		 * @param {Layout} [props.parentLayout]
		 * @param {Object} [props.openWidgetConfig]
		 * @param {Array} [props.widgetRightButtons]
		 */
		static open({
			title = '',
			parentLayout = PageManager,
			openWidgetConfig = {},
			widgetRightButtons = [],
			...restParams
		})
		{
			return new Promise((resolve) => {
				const config = mergeImmutable({
					enableNavigationBarBorder: false,
					titleParams: {
						text: title,
					},
					onReady: (readyLayout) => {
						const listInstance = new JnList({
							layout: readyLayout,
							...restParams,
						});
						if (Array.isArray(widgetRightButtons) && widgetRightButtons.length > 0)
						{
							readyLayout.setRightButtons(widgetRightButtons);
						}
						readyLayout.showComponent(listInstance);
						resolve(listInstance);
					},
				}, openWidgetConfig);

				parentLayout.openWidget('layout', config);
			});
		}

		constructor(props)
		{
			super(props);
			this.debounceSetSearchString = debounce(
				this.setSearchString,
				500,
				this,
			);
			this.scopeBarRef = null;
		}

		getTestId(suffix)
		{
			const { testId } = this.props;

			return [testId, suffix].join('-').trim();
		}

		setSearchString(text)
		{
			const { onSearchStringChanged } = this.props;
			onSearchStringChanged?.(text);
		}

		render()
		{
			const { pending, showSkeletonOnlyForList } = this.props;

			return Box(
				{
					resizableByKeyboard: true,
					safeArea: { bottom: true },
				},
				(!pending || showSkeletonOnlyForList) && this.renderSearchBar(),
				(!pending || showSkeletonOnlyForList) && this.renderScopes(),
				!pending && this.renderList(),
				pending && this.renderSkeleton(),
				this.renderFloatingButton(),
			);
		}

		/**
		 * @public
		 */
		renderFloatingButton = () => {
			const { layout, pending, displayFloatingButton, floatingButtonProps = {} } = this.props;
			const {
				icon = Icon.PLUS,
				type = FloatingActionButtonType.COMMON,
				accentByDefault = true,
				onClick,
				onLongClick,
			} = floatingButtonProps;

			return FloatingActionButton({
				testId: this.getTestId('floating-button'),
				icon,
				type,
				accentByDefault,
				onClick,
				onLongClick,
				hide: (pending || !displayFloatingButton),
				parentLayout: layout,
			});
		};

		/**
		 * @param {string[]} keys
		 * @returns {Promise<void>}
		 */
		deleteItemsByKeys = async (keys) => {
			if (Array.isArray(keys) && keys.length > 0)
			{
				await this.listViewRef?.deleteRowsByKeys(keys, true);
			}
		};

		/**
		 * @public
		 */
		renderEmptyState()
		{
			return View(
				{
					style: {
						justifyContent: 'center',
						alignItems: 'center',
						paddingVertical: Indent.XL4.toNumber(),
					},
				},
				Text2({
					text: Loc.getMessage('JNLIST_EMPTY_STATE_TEXT'),
					color: Color.base1,
				}),
			);
		}

		/**
		 * @public
		 */
		renderScopes()
		{
			const {
				scopes,
				selectedScopeId,
				onScopeSelectionChanged,
			} = this.props;

			return JnListScopeBar({
				ref: this.bindScopeBarRef,
				testId: this.getTestId('scope-bar'),
				scopes,
				selectedScopeId,
				onScopeSelectionChanged,
			});
		}

		bindScopeBarRef = (ref) => {
			this.scopeBarRef = ref;
		};

		scrollToSelectedScope(animated = false)
		{
			this.scopeBarRef?.scrollToSelectedScope(animated);
		}

		scrollToScopesEnd(animated = false)
		{
			this.scopeBarRef?.scrollToScopesEnd(animated);
		}

		/**
		 * @public
		 */
		renderList()
		{
			const { items } = this.props;
			if (items.length === 0)
			{
				return this.renderEmptyState();
			}

			const preparedItems = items.map((item) => ({
				...item,
				key: item.id,
			}));

			return OptimizedListView({
				ref: this.bindListViewRef,
				style: { flex: 1 },
				data: [{ items: preparedItems }],
				isRefreshing: false,
				renderItem: this.renderListItem,
				onScrollBeginDrag: this.onScrollBeginDrag,
			});
		}

		onScrollBeginDrag = () => {
			Keyboard.dismiss();
		};

		bindListViewRef = (ref) => {
			this.listViewRef = ref;
		};

		/**
		 * @public
		 */
		renderListItem = (item) => {
			const {
				onListItemClick,
				searchMatchColor = Color.accentMainPrimary,
				searchString,
				getListItemContextMenuItems,
				badgeText,
				badgeDesign,
			} = this.props;
			const isContextMenuButtonVisible = this.#isContextMenuButtonVisible(item);
			const isBadgeVisible = this.#isBadgeVisible(item);

			return JnListItem({
				testId: this.getTestId('list-item'),
				item,
				onListItemClick,
				searchMatchColor,
				searchString,
				getListItemContextMenuItems,
				badgeText,
				badgeDesign,
				isContextMenuButtonVisible,
				isBadgeVisible,
			});
		};

		#isContextMenuButtonVisible(item)
		{
			const { contextMenuButtonDisplayField } = this.props;
			if (contextMenuButtonDisplayField)
			{
				return Boolean(item[contextMenuButtonDisplayField]);
			}

			return false;
		}

		#isBadgeVisible(item)
		{
			const { badgeDisplayField } = this.props;
			if (badgeDisplayField)
			{
				return Boolean(item[badgeDisplayField]);
			}

			return false;
		}

		/**
		 * @public
		 */
		renderSearchBar()
		{
			const {
				searchInputPlaceholder = Loc.getMessage('JNLIST_SEARCH_INPUT_PLACEHOLDER'),
				searchString,
			} = this.props;

			return JnListSearchBar({
				testId: this.getTestId('search-bar'),
				searchInputPlaceholder,
				searchString,
				onSearchTextFieldChange: this.onSearchTextFieldChange,
				onSearchTextFieldSubmit: this.onSearchTextFieldSubmit,
				onClearSearchButtonClick: this.onClearSearchButtonClick,
			});
		}

		onSearchTextFieldSubmit = () => {
			Keyboard.dismiss();
		};

		onClearSearchButtonClick = () => {
			this.setSearchString('');
		};

		onSearchTextFieldChange = (searchString) => {
			this.debounceSetSearchString(searchString);
		};

		/**
		 * @public
		 */
		renderSkeleton()
		{
			const { showSkeletonOnlyForList } = this.props;

			return JnListSkeleton({
				showSkeletonOnlyForList,
			});
		}

		close()
		{
			this.props.layout?.close();
		}
	}

	module.exports = {
		/**
		 * @param {JnListProps} props
		 * @returns {JnList}
		 */
		JnList: (props) => new JnList(props),
		JnListClass: JnList,
		JnListItem,
		JnListSearchBar,
		JnListScopeBar,
		JnListSkeleton,
	};
});
