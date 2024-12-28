/**
 * @module calendar/event-list-view/search/layout
 */
jn.define('calendar/event-list-view/search/layout', (require, exports, module) => {
	const { debounce } = require('utils/function');
	const { Color, Indent } = require('tokens');

	const { EventManager } = require('calendar/data-managers/event-manager');
	const { Preset } = require('calendar/event-list-view/search/preset');
	const { State, observeState } = require('calendar/event-list-view/state');

	const MINIMAL_SEARCH_LENGTH = 3;
	const PRESET_LIST_LAYOUT_HEIGHT = 46;

	/**
	 * @class SearchLayout
	 */
	class SearchLayout extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.isOpened = false;

			this.show = this.show.bind(this);
			this.onHide = this.onHide.bind(this);
			this.onDone = this.onDone.bind(this);
			this.onCancel = this.onCancel.bind(this);
			this.onTextChange = this.onTextChange.bind(this);
			this.onPresetSelected = this.onPresetSelected.bind(this);

			this.debounceSearch = debounce((params) => this.searchHandler(params), 500, this);
		}

		componentDidMount()
		{
			this.bindEvents();
		}

		componentWillUnmount()
		{
			this.unbindEvents();
		}

		componentDidUpdate(prevProps, prevState)
		{
			if (this.props.visible)
			{
				this.show();
			}
		}

		bindEvents()
		{
			const { layout } = this.props;

			layout.search.on('textChanged', this.onTextChange);
			layout.search.on('hide', this.onHide);
			layout.search.on('cancel', this.onCancel);
			layout.search.on('clickEnter', this.onDone);
			layout.search.setReturnKey('done');
		}

		unbindEvents()
		{
			const { layout } = this.props;

			layout.search.removeAllListeners('textChanged');
			layout.search.removeAllListeners('hide');
			layout.search.removeAllListeners('cancel');
			layout.search.removeAllListeners('clickEnter');
		}

		show()
		{
			if (this.isOpened)
			{
				return;
			}

			const { search } = this.props.layout;
			const searchText = this.props.search || '';

			this.isOpened = true;

			search.mode = 'bar';
			search.text = searchText;
			search.show();
		}

		onTextChange(params)
		{
			if (this.props.presetId)
			{
				// eslint-disable-next-line no-param-reassign
				params.preset = this.getPresetById(this.props.presetId);
			}

			this.debounceSearch(params);
		}

		onPresetSelected(params, active)
		{
			const searchParams = active ? params : {};

			this.searchHandler(searchParams);
		}

		onHide()
		{
			if (!this.props.visible)
			{
				return;
			}

			State.setIsSearchVisible(false);
			this.isOpened = false;
		}

		onCancel()
		{
			State.closeFilter();
			this.isOpened = false;

			void this.onSearch();
		}

		onDone()
		{
			this.close();

			this.onHide();
		}

		searchHandler(params)
		{
			if (params.text && params.text.length < MINIMAL_SEARCH_LENGTH)
			{
				return;
			}

			// eslint-disable-next-line no-param-reassign
			params.text = params.text === undefined ? this.props.search : params.text;

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
			const { presets } = this.props;

			Object.values(presets).forEach((preset) => {
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
			this.props.layout.search.close();

			this.isOpened = false;
		}

		render()
		{
			const { visible } = this.props;

			return View(
				{
					style: styles.container(visible),
				},
				visible && View(
					{},
					ScrollView(
						{
							showsHorizontalScrollIndicator: false,
							horizontal: true,
							style: styles.presetsScrollView,
						},
						View(
							{
								style: {
									flexDirection: 'row',
									alignItems: 'center',
								},
								testId: 'presetList',
							},
							...this.renderPresets(),
						),
					),
				),
			);
		}

		renderPresets()
		{
			const { presets, presetId } = this.props;
			const presetLength = Object.keys(presets).length;

			return Object.values(presets).map((preset, index) => {
				// eslint-disable-next-line no-param-reassign
				preset = {
					...preset,
					active: (presetId === preset.id),
					last: (index === presetLength - 1),
					onPresetSelected: this.onPresetSelected,
				};

				return Preset(preset);
			});
		}
	}

	const styles = {
		container: (visible) => {
			return {
				paddingHorizontal: Indent.XL3.toNumber(),
				paddingVertical: Indent.XS.toNumber(),
				position: visible ? 'absolute' : 'relative',
				display: visible ? 'flex' : 'none',
				zIndex: 10,
				height: visible ? PRESET_LIST_LAYOUT_HEIGHT + 2 * Indent.XS.toNumber() : 0,
				width: '100%',
				backgroundColor: Color.bgNavigation.toHex(),
				borderBottomWidth: visible ? 1 : 0,
				borderBottomColor: Color.bgSeparatorSecondary.toHex(),
			};
		},
		presetsScrollView: {
			height: PRESET_LIST_LAYOUT_HEIGHT,
		},
		presets: {
			flexDirection: 'row',
			alignItems: 'center',
		},
	};

	const mapStateToProps = (state) => ({
		visible: state.isSearchVisible,
		search: state.searchString,
		presetId: state.presetId,
	});

	module.exports = { SearchLayout: observeState(SearchLayout, mapStateToProps) };
});
