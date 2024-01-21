/**
 * @module layout/ui/search-bar/search-layout
 */
jn.define('layout/ui/search-bar/search-layout', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { debounce } = require('utils/function');
	const { clone, isEqual, mergeImmutable } = require('utils/object');
	const { PropTypes } = require('utils/validation');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { Preset } = require('layout/ui/search-bar/preset');
	const { Counter } = require('layout/ui/search-bar/counter');
	const {
		MoreButton,
		MINIMAL_SEARCH_LENGTH,
		DEFAULT_ICON_BACKGROUND,
		ENTER_PRESSED_EVENT,
	} = require('layout/ui/search-bar/ui');
	const { PureComponent } = require('layout/pure-component');

	/**
	 * @class SearchLayout
	 * @typedef {LayoutComponent<SearchBarProps, SearchBarState>}
	 */
	class SearchLayout extends PureComponent
	{
		// region init

		constructor(props)
		{
			super(props);

			this.setupNativeSearchField();

			this.state = {
				counters: [],
				presets: [],
				text: '',
				presetId: (this.props.presetId || null),
				counterId: (this.props.counterId || null),
				selectedPresetBackground: null,
				presetsLoaded: false,
			};

			this.presetsBackendProvider = {
				route: this.props.searchDataAction,
				params: this.props.searchDataActionParams || {},
			};

			this.show = this.show.bind(this);
			this.onCancel = this.onCancel.bind(this);
			this.onPresetClick = this.onPresetClick.bind(this);
			this.onTextChanged = this.onTextChanged.bind(this);

			this.debounceSearch = debounce(() => this.search(false), 500, this);
		}

		get nativeSearchField()
		{
			return this.props.layout.search;
		}

		/**
		 * @private
		 */
		setupNativeSearchField()
		{
			const search = this.nativeSearchField;

			search.mode = 'layout';

			search.removeAllListeners('cancel');
			search.removeAllListeners('hide');
			search.removeAllListeners('textChanged');
			search.removeAllListeners(ENTER_PRESSED_EVENT);

			search.on('textChanged', (params) => this.onTextChanged(params));
			search.on('cancel', () => this.onCancel());
			search.on(ENTER_PRESSED_EVENT, () => this.close());

			if (Application.getApiVersion() > 44)
			{
				search.setReturnKey('done');
			}
		}

		// endregion

		// region public api

		/**
		 * @public
		 */
		show()
		{
			this.fetchPresets();

			const search = this.nativeSearchField;

			search.text = this.state.text;
			search.show(this, 44);
		}

		/**
		 * @public
		 */
		close()
		{
			this.nativeSearchField.close();
		}

		/**
		 * @public
		 * @return {string|null}
		 */
		getSearchButtonBackgroundColor()
		{
			if (this.hasChanges())
			{
				return this.state.selectedPresetBackground || DEFAULT_ICON_BACKGROUND;
			}

			return null;
		}

		/**
		 * @public
		 * @return {string|null}
		 */
		getDefaultPresetId()
		{
			if (!this.state.presetsLoaded)
			{
				return null;
			}

			const defaultPreset = this.state.presets.find((preset) => (preset.default === true && preset.disabled !== true));

			return (defaultPreset ? defaultPreset.id : null);
		}

		/**
		 * @public
		 * @param {Object.<string, number>} counters
		 */
		updateCounters(counters)
		{
			let stateWasChanged = false;
			const propertyExists = (obj, prop) => Object.prototype.hasOwnProperty.call(obj, prop);

			this.state.counters.forEach(({ code, value }, index) => {
				if (!propertyExists(counters, code))
				{
					return;
				}

				const nextValue = counters[code];
				if (nextValue > 0 && nextValue !== value)
				{
					this.state.counters[index].value = nextValue;
					stateWasChanged = true;
				}
			});

			if (stateWasChanged)
			{
				this.setState({});
			}
		}

		// endregion

		// region data fetching

		/**
		 * @public
		 * @param {boolean} force
		 */
		fetchPresets(force = false)
		{
			if (this.state.presetsLoaded && !force)
			{
				return;
			}

			const { route, params } = this.presetsBackendProvider;

			new RunActionExecutor(route, params)
				.setCacheId(this.getCacheId())
				.setCacheHandler((response) => this.onLoadPresets(response))
				.setHandler((response) => this.onLoadPresets(response))
				.call(true);
		}

		/**
		 * @private
		 * @return {string}
		 */
		getCacheId()
		{
			return this.props.cacheId || this.props.id;
		}

		/**
		 * @private
		 * @param {object} response
		 */
		onLoadPresets(response)
		{
			if (!response || !response.data)
			{
				return;
			}

			const { counters, presets } = response.data;
			if (this.state.presetsLoaded)
			{
				if (!isEqual(this.state.counters, counters) || !isEqual(this.state.presets, presets))
				{
					this.setState({ counters, presets });
				}
			}
			else
			{
				this.setState({ counters, presets, presetsLoaded: true });
			}
		}

		// endregion

		// region searching

		/**
		 * @private
		 */
		onCancel()
		{
			const patch = {
				text: '',
				counterId: null,
				presetId: this.getDefaultPresetId(),
			};

			const newState = mergeImmutable(this.state, patch);

			if (isEqual(this.state, newState))
			{
				return;
			}

			this.setState(newState);

			if (this.props.onCancel)
			{
				this.props.onCancel(patch);
			}
		}

		/**
		 * @private
		 */
		onTextChanged({ text = '' })
		{
			this.state.text = text;

			this.debounceSearch();
		}

		/**
		 * @private
		 */
		search()
		{
			const { text, presetId, counterId } = this.state;
			if (text.length > 0 && text.length < MINIMAL_SEARCH_LENGTH)
			{
				return;
			}

			if (text.length > 0 && this.hasRestrictions())
			{
				this.state.text = '';
				this.nativeSearchField.text = '';

				return;
			}

			if (this.props.onSearch)
			{
				this.props.onSearch({ text, presetId, counterId });
			}
		}

		/**
		 * @private
		 * @return {boolean}
		 */
		hasRestrictions()
		{
			if (this.props.onCheckRestrictions)
			{
				return Boolean(this.props.onCheckRestrictions(this));
			}

			return false;
		}

		// endregion

		// region render

		render()
		{
			const presets = this.getPreparedPresets();

			return View(
				{
					testId: 'search-presets-list-wrapper',
					style: styles.wrapper,
				},
				View(
					{},
					ScrollView(
						{
							horizontal: true,
							style: styles.presetsScrollView,
						},
						View(
							{
								testId: 'search-presets-list',
								style: styles.presetsWrapper,
							},
							this.renderLoader(),
							...this.renderDefaultPreset(presets),
							...this.renderCounters(),
							...this.renderPresets(presets),
							this.renderMoreButton(),
						),
					),
				),
			);
		}

		renderLoader()
		{
			if (this.state.presetsLoaded)
			{
				return null;
			}

			return Loader({
				style: {
					width: 50,
					height: 50,
				},
				tintColor: AppTheme.colors.base3,
				animating: true,
				size: 'small',
			});
		}

		renderDefaultPreset(presets)
		{
			return presets.filter((preset) => preset.isDefault());
		}

		/**
		 * @returns {Counter[]}
		 */
		renderCounters()
		{
			const counters = clone(this.state.counters);

			return counters.map((counter) => new Counter({
				...counter,
				active: (this.state.counterId === counter.code),
				onClick: this.onPresetClick,
			}));
		}

		renderPresets(presets)
		{
			return presets.filter((preset) => !preset.isDefault());
		}

		renderMoreButton()
		{
			if (!this.props.onMoreButtonClick)
			{
				return null;
			}

			return MoreButton({
				onClick: () => this.props.onMoreButtonClick(),
			});
		}

		/**
		 * @returns {Preset[]}
		 */
		getPreparedPresets()
		{
			if (!this.state.presetsLoaded)
			{
				return [];
			}

			const presets = clone(this.state.presets);

			return presets.map((preset, index) => new Preset({
				...preset,
				active: (this.state.presetId === preset.id),
				onClick: this.onPresetClick,
				last: (index === presets.length - 1),
			}));
		}

		/**
		 * @private
		 * @param {{
		 * 	presetId: string | null,
		 * 	counterId: string | null,
		 * 	searchButtonBackgroundColor: string | undefined
		 * }} params
		 * @param {Boolean} active
		 */
		onPresetClick(params, active)
		{
			const { counterId, presetId, searchButtonBackgroundColor } = params;

			const patch = {
				counterId: active && counterId ? counterId : null,
				presetId: active && presetId ? presetId : null,
				selectedPresetBackground: active && searchButtonBackgroundColor ? searchButtonBackgroundColor : null,
			};

			this.setState(patch, () => this.search());
		}

		// endregion

		/**
		 * @private
		 * @return {boolean}
		 */
		hasChanges()
		{
			if (this.state.text.length > 0)
			{
				return true;
			}

			if (this.state.presetsLoaded)
			{
				return this.state.presetId !== this.getDefaultPresetId();
			}

			return false;
		}
	}

	SearchLayout.propTypes = {
		id: PropTypes.string.isRequired,
		cacheId: PropTypes.string,
		layout: PropTypes.object.isRequired,
		searchDataAction: PropTypes.string.isRequired,
		searchDataActionParams: PropTypes.object,
		onCheckRestrictions: PropTypes.func,
		onMoreButtonClick: PropTypes.func,
		presetId: PropTypes.string,
		counterId: PropTypes.string,
	};

	const styles = {
		wrapper: {
			height: 44,
			width: '100%',
			backgroundColor: AppTheme.colors.bgNavigation,
		},
		presetsScrollView: {
			height: 44,
		},
		presetsWrapper: {
			flexDirection: 'row',
			alignItems: 'center',
			marginTop: 0,
			paddingRight: 10,
		},
		contentWrapper: {
			borderTopLeftRadius: 20,
			borderTopRightRadius: 20,
		},
		listWrapper: {
			width: '100%',
			height: 600,
		},
		emptyResultsWrapper: {
			justifyContent: 'center',
			alignItems: 'center',
			width: '100%',
			height: '100%',
		},
		emptyResultsIcon: {
			width: 86,
			height: 86,
			marginTop: -86,
		},
		searchContentTitle: {
			fontSize: 13,
			color: AppTheme.colors.baseWhiteFixed,
			marginBottom: 10,
			marginLeft: 20,
		},
	};

	module.exports = { SearchLayout };
});
