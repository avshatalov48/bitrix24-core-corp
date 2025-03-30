/**
 * @module sign/grid
 */
jn.define('sign/grid', (require, exports, module) => {
	const { ListItemType, ListItemsFactory } = require('sign/grid/item-factory');
	const { Master } = require('sign/master');

	const { usersUpserted, usersAdded } = require('statemanager/redux/slices/users');
	const { batchActions } = require('statemanager/redux/batched-actions');
	const store = require('statemanager/redux/store');
	const { dispatch } = store;

	const { StatusBlock } = require('ui-system/blocks/status-block');
	const { StatefulList } = require('layout/ui/stateful-list');
	const { SearchLayout } = require('layout/ui/search-bar');
	const { Box } = require('ui-system/layout/box');

	const { makeLibraryImagePath } = require('asset-manager');
	const { Color } = require('tokens');
	const { Loc } = require('loc');

	const EMPTY_GRID_IMAGE_NAME = 'empty-grid.svg';
	const EMPTY_SEARCH_IMAGE_NAME = 'error.svg';
	const FILTER_PRESET_IN_WORK = 'preset_in_progress';
	const FILTER_PRESET_SEND = 'preset_send';
	const FILTER_PRESET_SIGNED = 'preset_signed';
	const FILTER_PRESET_PROCESSED_BY_ME = 'preset_processed_by_me';

	/**
	 * @class Grid
	 */
	class Grid extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.master = new Master();
			this.stateFulListRef = null;
			this.isE2bAvailable = this.props.isE2bAvailable;

			this.search = new SearchLayout({
				layout,
				disablePresets: true,
				onSearch: this.#onSearch,
				onCancel: this.#onSearch,
			});

			this.tabs = [
				{ title: Loc.getMessage('SIGN_MOBILE_GRID_TAB_IN_WORK'), label: '', id: FILTER_PRESET_IN_WORK },
				{ title: Loc.getMessage('SIGN_MOBILE_GRID_TAB_SIGNED'), id: FILTER_PRESET_SIGNED },
				{ title: Loc.getMessage('SIGN_MOBILE_GRID_TAB_PROCESSED_BY_ME'), id: FILTER_PRESET_PROCESSED_BY_ME },
			]

			if (this.isE2bAvailable)
			{
				this.tabs.splice(
					1,
					0,
					{ title: Loc.getMessage('SIGN_MOBILE_GRID_TAB_SEND'), label: '', id: FILTER_PRESET_SEND },
				);
			}

			this.filter = { tabId: FILTER_PRESET_IN_WORK, searchString: '' };
		}

		render()
		{
			return Box(
				{
					resizableByKeyboard: true,
					backgroundColor: Color.bgPrimary,
				},
				TabView({
					ref: (ref) => {
						this.tabView = ref;
					},
					style: {
						height: 44,
						marginBottom: 15,
						backgroundColor: Color.bgPrimary,
					},
					params: {
						items: this.tabs,
					},
					onTabSelected: this.#onTabSelected,
				}),
				View(
					{
						style: {
							flex: 1,
						},
					},
					this.renderList(),
				),
			);
		}

		renderList()
		{
			return new StatefulList({
				layout,
				ref: this.onListRef,
				testId: 'user-list',
				needInitMenu: true,
				showAirStyle: true,
				useCache: false,
				isShowFloatingButton: this.isE2bAvailable,
				onFloatingButtonClick: this.#onFloatingButtonClick,
				getEmptyListComponent: this.getEmptyListComponent,
				menuButtons: this.getLayoutMenuButtons(),
				itemType: ListItemType.DOCUMENT,
				itemFactory: ListItemsFactory,
				actions: {
					loadItems: 'signmobile.document.getDocumentList',
				},
				actionParams: {
					loadItems: {
						filterParams: this.getFilterParams(),
					}
				},
				actionCallbacks: {
					loadItems: this.#onItemsLoaded,
				},
				pull: {
					moduleId: 'sign',
					callback: this.#onPullCallback,
					shouldReloadDynamically: true,
				},
			});
		}

		getPlaceholderText()
		{
			if (this.filter.searchString !== '')
			{
				return {
					title: Loc.getMessage('SIGN_MOBILE_GRID_EMPTY_STATE_EMPTY_SEARCH_TITLE'),
					description: Loc.getMessage('SIGN_MOBILE_GRID_EMPTY_STATE_EMPTY_SEARCH_DESCRIPTION'),
					imageName: EMPTY_SEARCH_IMAGE_NAME,
				};
			}

			const imageName = EMPTY_GRID_IMAGE_NAME;

			switch (this.filter.tabId)
			{
				case FILTER_PRESET_SEND:
					return {
						title: Loc.getMessage('SIGN_MOBILE_GRID_EMPTY_STATE_SEND_TITLE'),
						description: Loc.getMessage('SIGN_MOBILE_GRID_EMPTY_STATE_SEND_DESCRIPTION'),
						imageName
					};
				case FILTER_PRESET_SIGNED:
					return {
						title: Loc.getMessage('SIGN_MOBILE_GRID_EMPTY_STATE_SIGNED_TITLE'),
						description: Loc.getMessage('SIGN_MOBILE_GRID_EMPTY_STATE_SIGNED_DESCRIPTION'),
						imageName
					};
				case FILTER_PRESET_PROCESSED_BY_ME:
					return {
						title: Loc.getMessage('SIGN_MOBILE_GRID_EMPTY_STATE_PROCESSED_BY_ME_TITLE'),
						description: Loc.getMessage('SIGN_MOBILE_GRID_EMPTY_STATE_PROCESSED_BY_ME_DESCRIPTION'),
						imageName
					};
				default:
					return {
						title: Loc.getMessage('SIGN_MOBILE_GRID_EMPTY_STATE_IN_WORK_TITLE'),
						description: Loc.getMessage('SIGN_MOBILE_GRID_EMPTY_STATE_IN_WORK_DESCRIPTION'),
						imageName
					};
			}
		}

		getEmptyListComponent = () => {
			const { title, description, imageName } = this.getPlaceholderText();

			return StatusBlock({
				testId: 'empty-state',
				title,
				description,
				emptyScreen: false,
				onRefresh: () => {},
				image: Image({
					resizeMode: 'contain',
					style: {
						width: 202,
						height: 172,
					},
					svg: {
						uri: makeLibraryImagePath(imageName, 'empty-states', 'sign'),
					},
				}),
			});
		};

		getLayoutMenuButtons()
		{
			return [
				this.search.getSearchButton(),
			];
		}

		getFilterParams()
		{
			return this.filter;
		}

		#onItemsLoaded = (responseData, context) => {
			const { users = [], needActionCount = '', needCountForSendPreset = '' } = responseData || {};
			const isCache = context === 'cache';
			const actions = [];

			if (users.length > 0)
			{
				actions.push(isCache ? usersAdded(users) : usersUpserted(users));
				dispatch(batchActions(actions));
			}

			this.tabView.updateItem(FILTER_PRESET_IN_WORK, {label: this.prepareCounter(needActionCount)});
			this.tabView.updateItem(FILTER_PRESET_SEND, {label: this.prepareCounter(needCountForSendPreset)});
		};

		#onTabSelected = (tab) => {
			this.filter.tabId = tab.id;

			this.setState({}, () => this.stateFulListRef.reload({ skipUseCache: true }));
		};

		#onSearch = ({ text }) => {
			this.filter.searchString = text ?? '';

			this.setState({}, () => this.stateFulListRef.reload());
		};

		#onPullCallback = (params) => {
			return new Promise((resolve) => {
				if (params.command === 'updateMyDocumentGrid')
				{
					this.stateFulListRef.reload({ skipUseCache: true });
					resolve({
						params: { eventName: params.command },
					});
				}
			});
		};

		#onFloatingButtonClick = () => {
			this.master.openMaster(layout);
		};

		onListRef = (ref) => {
			this.stateFulListRef = ref;
		};

		prepareCounter(value)
		{
			if (typeof value !== 'boolean' && isFinite(Number(value)) && Number(value) !== 0)
			{
				return String(value)
			}

			return ''
		}
	}

	module.exports = { Grid };
});
