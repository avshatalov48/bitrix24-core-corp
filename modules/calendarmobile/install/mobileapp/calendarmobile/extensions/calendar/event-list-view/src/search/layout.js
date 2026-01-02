/**
 * @module calendar/event-list-view/search/layout
 */
jn.define('calendar/event-list-view/search/layout', (require, exports, module) => {
	const { debounce } = require('utils/function');
	const { Icon } = require('ui-system/blocks/icon');

	const { EventManager } = require('calendar/data-managers/event-manager');
	const { SearchLayoutView } = require('calendar/event-list-view/search/layout-view');
	const { State } = require('calendar/event-list-view/state');

	const MINIMAL_SEARCH_LENGTH = 3;

	/**
	 * @class SearchLayout
	 */
	class SearchLayout
	{
		constructor(props)
		{
			this.props = props;

			this.setupNativeSearchField();

			this.isOpened = false;
			this.debounceSearch = debounce((params) => this.searchHandler(params), 500, this);
		}

		get search()
		{
			return this.props.layout.search;
		}

		get presets()
		{
			return this.props.filterPresets;
		}

		getButton()
		{
			return {
				type: Icon.SEARCH.getIconName(),
				id: 'calendar-search',
				testId: 'calendar-search',
				accent: State.isSearchMode,
				callback: this.show,
			};
		}

		setupNativeSearchField()
		{
			this.search.mode = 'layout';

			this.search.removeAllListeners('textChanged');
			this.search.removeAllListeners('hide');
			this.search.removeAllListeners('cancel');
			this.search.removeAllListeners('clickEnter');

			this.search.on('textChanged', this.onTextChange);
			this.search.on('hide', this.onHide);
			this.search.on('cancel', this.onCancel);
			this.search.on('clickEnter', this.onDone);
			this.search.setReturnKey('done');
		}

		show = () => {
			if (this.isOpened)
			{
				return;
			}

			const searchText = State.searchString || '';

			this.isOpened = true;

			this.createSearchLayoutView();

			this.search.text = searchText;
			this.search.show(this.searchLayoutView, 46);
		};

		createSearchLayoutView()
		{
			this.searchLayoutView = new SearchLayoutView({
				presets: this.presets,
				presetId: State.presetId,
				onPresetSelected: this.onPresetSelected,
			});
		}

		onTextChange = (params) => {
			if (State.presetId)
			{
				// eslint-disable-next-line no-param-reassign
				params.preset = this.getPresetById(State.presetId);
			}

			this.debounceSearch(params);
		};

		onPresetSelected = (params, active) => {
			const searchParams = active ? params : {};

			this.searchHandler(searchParams);
		};

		onHide = () => {
			this.isOpened = false;
		};

		onCancel = () => {
			State.closeFilter();
			this.isOpened = false;

			void this.onSearch();
		};

		onDone = () => {
			this.close();

			this.onHide();
		};

		searchHandler(params)
		{
			if (params.text && params.text.length < MINIMAL_SEARCH_LENGTH)
			{
				return;
			}

			// eslint-disable-next-line no-param-reassign
			params.text = params.text === undefined ? State.searchString : params.text;

			const filterParams = {
				searchString: params.text,
				presetId: '',
				preset: null,
			};

			const { preset } = params;
			if (preset)
			{
				filterParams.presetId = preset.id || '';
			}

			// eslint-disable-next-line no-param-reassign
			filterParams.preset = preset === undefined ? this.getDefaultPreset() : preset;

			State.setFilterParams(filterParams);

			void this.onSearch();
		}

		async onSearch()
		{
			State.setInvitesSelected(false);

			let eventIds = [];
			if (State.isSearchMode)
			{
				State.setIsLoading(true);

				eventIds = await EventManager.getEventsByFilter({
					...State.searchData,
					ownerId: State.ownerId,
					calType: State.calType,
				});
			}

			State.setFilterResultIds(eventIds);

			BX.postComponentEvent('Calendar.EventListView::onSearch');
		}

		getPresetById(presetId)
		{
			let result = null;

			Object.values(this.presets).forEach((preset) => {
				if (preset.id === presetId)
				{
					result = preset;
				}
			});

			return result;
		}

		getDefaultPreset()
		{
			return {
				id: '',
			};
		}

		close()
		{
			this.search.close();

			this.isOpened = false;
		}
	}

	module.exports = { SearchLayout };
});
