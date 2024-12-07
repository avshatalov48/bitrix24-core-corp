/**
 * @module im/messenger/lib/ui/selector/single-selector
 */
jn.define('im/messenger/lib/ui/selector/single-selector', (require, exports, module) => {
	const { ButtonSection } = require('im/messenger/lib/ui/selector/button-section');
	const { FullScreenShadow } = require('im/messenger/lib/ui/base/full-screen-shadow');
	const { SearchInput } = require('im/messenger/lib/ui/search/input');
	const { List } = require('im/messenger/lib/ui/base/list');
	const { Theme } = require('im/lib/theme');

	/**
	 * @class SingleSelector
	 * @typedef {LayoutComponent<SingleSelectorProps, SingleSelectorState>} SingleSelector
	 */
	class SingleSelector extends LayoutComponent
	{
		/**
		 * @param {SingleSelectorProps} props
		 */
		constructor(props)
		{
			super(props);

			this.itemList = props.itemList;
			this.isShadow = false;

			this.state.isSearchActive = false;

			if (props.ref)
			{
				props.ref(this);
			}
		}

		render()
		{
			if (this.state.isSearchActive && this.props.searchMode === 'overlay')
			{
				return View(
					{
						resizableByKeyboard: true,
						clickable: false,
					},
					this.createSearchWrapper(),
				);
			}

			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
					},
					resizableByKeyboard: true,
					clickable: false,
				},
				this.createSearchInput(),
				...this.getMainContent(),
				this.createShadow(),
			);
		}

		getMainContent()
		{
			return [
				this.createButtonSection(),
				this.createList(),
			];
		}

		createList()
		{
			return new List({
				recentText: this.props.recentText,
				itemList: this.props.itemList,
				openWithLoader: this.props.openWithLoader ?? false,
				openingLoaderTitle: this.props.openingLoaderTitle,
				onItemSelected: (itemData) => this.props.onItemSelected(itemData),
				ref: (ref) => {
					this.listRef = ref;
					if (this.props.searchMode === 'inline')
					{
						this.searchWrapperRef = ref;
					}
				},
			});
		}

		getList()
		{
			return this.listRef;
		}

		createButtonSection()
		{
			if (Array.isArray(this.props.buttons) && this.props.buttons.length > 0)
			{
				return new ButtonSection({ buttons: this.props.buttons });
			}

			return null;
		}

		createShadow()
		{
			return new FullScreenShadow({
				ref: (ref) => this.shadowRef = ref,
			});
		}

		getShadow()
		{
			return this.shadowRef;
		}

		createSearchInput()
		{
			if (this.props.searchMode !== 'inline')
			{
				return null;
			}

			return View(
				{
					style: {
						backgroundColor: Theme.isDesignSystemSupported ? Theme.colors.bgContentPrimary : Theme.colors.bgContentTertiary,
						padding: 10,
					},
				},
				new SearchInput(
					{
						onChangeText: (text) => this.props.onChangeText(text),
						onSearchShow: () => this.props.onSearchShow(),
						ref: (ref) => this.searchInputRef = ref,
					},
				),
			);
		}

		/**
		 *
		 * @return {SearchInput}
		 */
		getSearchInput()
		{
			return this.searchInputRef;
		}

		createSearchWrapper()
		{
			if (this.props.searchMode === 'overlay')
			{
				return new List({
					itemList: [],
					onItemSelected: (itemData) => this.props.onSearchItemSelected(itemData),
					ref: (ref) => this.searchWrapperRef = ref,
				});
			}
		}

		getSearchWrapper()
		{
			return this.searchWrapperRef;
		}

		enableShadow()
		{
			this.getShadow().enable();
		}

		disableShadow()
		{
			this.getShadow()?.disable();
		}

		showMainContent(withShadow = false)
		{
			if (this.props.searchMode === 'inline')
			{
				this.getList().setItems(this.itemList, false);

				return;
			}

			if (this.state.isSearchActive === true)
			{
				this.setState({ isSearchActive: false }, () => {
					if (withShadow)
					{
						this.enableShadow();
					}
				});
			}
		}

		/**
		 *
		 * @param {Array}items
		 * @param withLoader
		 */
		setItems(items, withLoader = false)
		{
			if (this.state.isSearchActive === false && this.props.searchMode === 'overlay')
			{
				this.setState({ isSearchActive: true }, () => {
					this.getSearchWrapper().setItems(items, withLoader);
				});

				return;
			}

			this.getSearchWrapper().setItems(items, withLoader);
		}
	}

	module.exports = { SingleSelector };
});
