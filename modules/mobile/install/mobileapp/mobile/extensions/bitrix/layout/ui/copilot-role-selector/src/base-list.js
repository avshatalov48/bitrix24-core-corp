/**
 * @module layout/ui/copilot-role-selector/src/base-list
 */
jn.define('layout/ui/copilot-role-selector/src/base-list', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { transparent } = require('utils/color');
	const { UIScrollView } = require('layout/ui/scroll-view');
	const { search, clearSearchText } = require('layout/ui/copilot-role-selector/src/icons');
	const { debounce } = require('utils/function');
	const { renderListFeedbackItem } = require('layout/ui/copilot-role-selector/src/feedback');
	const { getFeedBackItemData, openFeedBackForm } = require('layout/ui/copilot-role-selector/src/feedback');
	const { ListItemType } = require('layout/ui/copilot-role-selector/src/types');
	const { checkValueMatchQuery } = require('utils/search');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { ListUniversalRoleItem } = require('layout/ui/copilot-role-selector/src/views');

	const searchBarAreaHeight = 64;

	/**
	 * @class CopilotRoleSelectorBaseList
	 * @abstract
	 */
	class CopilotRoleSelectorBaseList extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				isLoading: true,
				searchString: '',
			};
			this.items = [];
			this.universalRoleItemData = this.props.universalRoleItemData ?? null;
			this.searchTextFieldChangeHandler = this.searchTextFieldChangeHandler.bind(this);
			this.searchItems = this.searchItems.bind(this);
			this.renderList = this.renderList.bind(this);
			this.searchTextFieldRef = null;
			this.isSearchResultRendering = false;
			this.lastSearchTextInDebounce = '';
			this.layoutHeight = null;

			this.debounceSetSearchString = debounce(
				this.setSearchString,
				300,
				this,
			);
		}

		getUniversalRoleItemData()
		{
			return this.props.universalRoleItemData ?? this.universalRoleItemData;
		}

		setSearchString(text)
		{
			this.lastSearchTextInDebounce = text;
			if ((this.lastSearchTextInDebounce === ''
					|| this.lastSearchTextInDebounce.length > 2)
				&& this.state.searchString !== this.lastSearchTextInDebounce
				&& !this.isSearchResultRendering)
			{
				this.isSearchResultRendering = true;
				this.setState({
					searchString: text,
				}, () => {
					this.isSearchResultRendering = false;
					this.setSearchString(this.lastSearchTextInDebounce);
				});
			}
		}

		componentDidMount()
		{
			this.loadItems()
				.then((items) => {
					this.items = this.prepareItems(items);
					this.setState({
						isLoading: false,
					});
				})
				.catch(console.error);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						width: '100%',
						height: '100%',
					},
					resizableByKeyboard: true,
					onLayout: ({ height }) => {
						if (this.layoutHeight === null)
						{
							this.layoutHeight = height;
						}
					},
				},
				this.renderSearchBar(),
				!this.state.isLoading && this.renderList(),
				this.state.isLoading && this.renderListSkeleton(),
			);
		}

		renderSearchBar()
		{
			const clearButtonVisible = this.state.searchString !== '';

			return View(
				{
					style: {
						width: '100%',
						padding: 14,
					},
				},
				View(
					{
						style: {
							backgroundColor: transparent(AppTheme.colors.base5, 0.25),
							height: 36,
							borderRadius: 8,
							width: '100%',
							flexDirection: 'row',
							paddingVertical: 8,
							paddingHorizontal: 10,
							alignItems: 'center',
						},
					},
					Image({
						svg: {
							content: search(AppTheme.colors.base4),
						},
						tintColor: AppTheme.colors.base4,
						style: {
							width: 16,
							height: 16,
							alignSelf: 'center',
							marginRight: 6,
						},
					}),
					TextField({
						ref: (searchTextFieldRef) => {
							this.searchTextFieldRef = searchTextFieldRef;
						},
						placeholder: Loc.getMessage('COPILOT_CONTEXT_STEPPER_SEARCH_PLACEHOLDER'),
						placeholderTextColor: AppTheme.colors.base4,
						style: {
							color: AppTheme.colors.base1,
							flex: 1,
							fontSize: 16,
						},
						onChangeText: this.searchTextFieldChangeHandler,
					}),
					clearButtonVisible && Image({
						svg: {
							content: clearSearchText(AppTheme.colors.base5),
						},
						tintColor: AppTheme.colors.base5,
						style: {
							width: 16,
							height: 16,
							alignSelf: 'center',
						},
						onClick: () => {
							if (this.state.searchString !== '')
							{
								this.setState({
									searchString: '',
								}, () => {
									if (this.searchTextFieldRef)
									{
										this.searchTextFieldRef.clear();
									}
								});
							}
						},
					}),
				),
			);
		}

		searchTextFieldChangeHandler(text)
		{
			this.debounceSetSearchString(text);
		}

		prepareItems(items)
		{
			return items.map((item) => {
				return {
					...item,
					type: this.getItemType(),
				};
			});
		}

		searchItems(searchString)
		{
			return this.items.filter((item) => {
				return checkValueMatchQuery(searchString, item.name);
			});
		}

		renderList()
		{
			const itemsToRender = this.state.searchString === '' ? [...this.items] : this.searchItems(this.state.searchString);
			if (this.props.showOpenFeedbackItem
				&& itemsToRender.length > 0
				&& (
					(this.state.searchString !== '')
					|| (this.state.searchString === '' && this.isRenderFeedbackItemForEmptySearchString())
				)
			)
			{
				itemsToRender.push(getFeedBackItemData());
			}

			if (this.props.enableUniversalRole
				&& itemsToRender.length > 0
				&& this.state.searchString === ''
				&& this.getItemType() === ListItemType.ROLE)
			{
				itemsToRender.unshift(this.getUniversalRoleItemData());
			}

			const renderedItems = itemsToRender.map(
				(item, index) => {
					if (item.type === ListItemType.FEEDBACK)
					{
						return renderListFeedbackItem();
					}

					if (item.type === ListItemType.UNIVERSAL_ROLE)
					{
						return ListUniversalRoleItem(this.getUniversalRoleItemData(), () => {
							this.props.listItemClickHandler(item, ListItemType.UNIVERSAL_ROLE);
						});
					}

					return this.renderListItem(
						item,
						index === itemsToRender.length - 1,
					);
				},
			);

			if (renderedItems.length > 0)
			{
				return UIScrollView(
					{
						style: {
							flex: 1,
							flexDirection: 'column',
							width: '100%',
							height: '100%',
							backgroundColor: AppTheme.colors.bgContentPrimary,
							borderTopLeftRadius: 12,
							borderTopRightRadius: 12,
						},
					},
					...renderedItems,
				);
			}

			return this.renderEmptyState();
		}

		renderEmptyState()
		{
			const title = Loc.getMessage('COPILOT_CONTEXT_STEPPER_SEARCH_EMPTY_STATE_TITLE');
			const description = this.renderEmptyStateDescription;
			const currentTheme = AppTheme.id;
			const uri = `${currentDomain}/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/copilot-role-selector/image/search-empty-state-${currentTheme}.svg`;

			return View(
				{
					style: {
						width: '100%',
						height: this.layoutHeight - searchBarAreaHeight,
						justifyContent: 'center',
						alignItems: 'center',
					},
					onClick: () => {
						Keyboard.dismiss();
					},
				},
				new EmptyScreen({
					title,
					description,
					styles: {
						rootContainer: {
							borderTopLeftRadius: 12,
							borderTopRightRadius: 12,
						},
					},
					image: {
						resizeMode: 'contain',
						style: {
							width: 172,
							height: 172,
						},
						svg: { uri },
					},
				}),
			);
		}

		renderEmptyStateDescription()
		{
			return BBCodeText({
				value: Loc.getMessage('COPILOT_CONTEXT_STEPPER_SEARCH_EMPTY_STATE_DESCRIPTION', {
					'#LINK_COLOR#': AppTheme.colors.accentMainPrimaryalt,
					'#URL#': '/open_feedback_form',
				}),
				linksUnderline: false,
				style: {
					color: AppTheme.colors.base3,
					fontSize: 15,
					textAlign: 'center',
					lineHeightMultiple: 1.2,
				},
				onLinkClick: () => {
					openFeedBackForm();
				},
			});
		}

		isRenderFeedbackItemForEmptySearchString()
		{
			return false;
		}

		/**
		 * @abstract
		 * @return {View}
		 */
		renderListItem(item, isLastItem = false)
		{
			return View();
		}

		/**
		 * @abstract
		 * @return {View}
		 */
		renderListSkeleton(count = 10)
		{
			return View();
		}

		/**
		 * @abstract
		 * @return {Promise}
		 */
		loadItems()
		{
			return Promise.resolve([]);
		}

		/**
		 * @abstract
		 * @return {string}
		 */
		getItemType()
		{
			return null;
		}
	}

	module.exports = { CopilotRoleSelectorBaseList };
});
