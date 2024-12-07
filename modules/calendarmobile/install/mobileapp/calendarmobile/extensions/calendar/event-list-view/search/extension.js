/**
 * @module calendar/event-list-view/search
 */
jn.define('calendar/event-list-view/search', (require, exports, module) => {
	const { debounce } = require('utils/function');
	const { Preset } = require('calendar/event-list-view/search/preset');
	const { Color } = require('tokens');

	const MINIMAL_SEARCH_LENGTH = 3;
	const PRESET_LIST_LAYOUT_HEIGHT = 46;

	/**
	 * @class Search
	 */
	class Search extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.isApiGreaterThen44 = (Application.getApiVersion() > 44);
			this.nameOfClickEnterEvent = (this.isApiGreaterThen44 ? 'clickEnter' : 'clickSearch');

			this.state = this.getInitialState();

			this.show = this.show.bind(this);
			this.onHide = this.onHideHandler.bind(this);
			this.onDone = this.onDoneHandler.bind(this);
			this.onCancel = this.onCancelHandler.bind(this);
			this.onTextChange = this.onTextChangeHandler.bind(this);
			this.onPresetSelected = this.onPresetSelectedHandler.bind(this);

			this.debounceSearch = debounce((params) => this.searchHandler(params), 500, this);
		}

		getInitialState()
		{
			return {
				visible: false,
				presets: this.props.presets || {},
				search: '',
				presetId: null,
			};
		}

		componentDidMount()
		{
			this.bindEvents();
		}

		componentWillUnmount()
		{
			this.unbindEvents();
		}

		bindEvents()
		{
			const { layout } = this.props;

			BX.addCustomEvent('Calendar.EventListView::onSearchShow', this.show);

			layout.search.on('textChanged', (params) => this.onTextChange(params));
			layout.search.on('hide', () => this.onHide());
			layout.search.on('cancel', () => this.onCancel());
			layout.search.on(this.nameOfClickEnterEvent, () => this.onDone());

			Keyboard.on(Keyboard.Event.Hidden, () => {
				layout.search.close();
			});

			if (this.isApiGreaterThen44)
			{
				layout.search.setReturnKey('done');
			}
		}

		unbindEvents()
		{
			const { layout } = this.props;

			BX.removeCustomEvent('Calendar.EventListView::onSearchShow', this.show);

			layout.search.removeAllListeners('textChanged');
			layout.search.removeAllListeners('hide');
			layout.search.removeAllListeners('cancel');
			layout.search.removeAllListeners(this.nameOfClickEnterEvent);
		}

		show(params)
		{
			if (this.state.visible)
			{
				return;
			}

			const { search } = this.props.layout;
			const searchText = params.search || '';

			search.mode = 'bar';
			search.text = searchText;
			search.show();

			this.setState({
				visible: true,
				presetId: params.presetId || null,
				search: searchText,
			});
		}

		onTextChangeHandler(params)
		{
			if (this.state.presetId)
			{
				// eslint-disable-next-line no-param-reassign
				params.preset = this.getPresetById(this.state.presetId);
			}

			this.debounceSearch(params);
		}

		onPresetSelectedHandler(params, active)
		{
			const searchParams = active ? params : {};

			this.searchHandler(searchParams);
		}

		onHideHandler()
		{
			if (!this.state.visible)
			{
				return;
			}

			this.setState({
				visible: false,
			});
		}

		onCancelHandler()
		{
			const newState = {
				search: '',
				presetId: null,
			};

			const params = {
				isCancel: true,
				text: newState.search,
				preset: {
					id: newState.presetId,
				},
			};

			this.setState(
				newState,
				() => BX.postComponentEvent('Calendar.EventListView::onSearch', [params]),
			);
		}

		onDoneHandler()
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
			params.text = params.text === undefined ? this.state.search : params.text;

			const newState = {
				search: params.text,
				presetId: null,
			};

			const { preset } = params;
			if (preset)
			{
				newState.presetId = preset.id || null;
			}

			// eslint-disable-next-line no-param-reassign
			params.preset = preset === undefined ? this.getDefaultPreset() : preset;

			this.setState(
				newState,
				() => BX.postComponentEvent('Calendar.EventListView::onSearch', [params]),
			);
		}

		getPresetById(presetId)
		{
			let result = null;
			const { presets } = this.state;

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

		isVisible()
		{
			return this.state.visible;
		}

		close()
		{
			this.props.layout.search.close();
		}

		render()
		{
			const { visible } = this.state;

			return View(
				{
					style: styles.container(visible),
				},
				visible && View(
					{},
					ScrollView(
						{
							horizontal: true,
							style: styles.presetsScrollView,
						},
						View(
							{
								style: styles.presets,
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
			const { presets, presetId } = this.state;
			const presetLength = Object.keys(presets).length;

			return Object.values(presets).map((preset, index) => {
				// eslint-disable-next-line no-param-reassign
				preset = {
					...preset,
					active: (presetId === preset.id),
					last: (index === presetLength - 1),
					onPresetSelected: this.onPresetSelected,
				};

				return new Preset(preset);
			});
		}
	}

	const styles = {
		container: (visible) => {
			return {
				paddingHorizontal: 5,
				position: visible ? 'absolute' : 'relative',
				zIndex: 10,
				height: visible ? PRESET_LIST_LAYOUT_HEIGHT : 0,
				width: '100%',
				backgroundColor: Color.bgNavigation.toHex(),
			};
		},
		presetsScrollView: {
			height: PRESET_LIST_LAYOUT_HEIGHT,
		},
		presets: {
			flexDirection: 'row',
			alignItems: 'center',
			marginTop: -6,
			paddingRight: 10,
		},
	};

	module.exports = { Search };
});
