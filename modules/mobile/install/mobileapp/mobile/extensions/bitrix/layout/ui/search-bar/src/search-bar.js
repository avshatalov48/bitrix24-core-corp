/**
 * @module layout/ui/search-bar/search-bar
 */
jn.define('layout/ui/search-bar/search-bar', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { debounce } = require('utils/function');
	const { stringify } = require('utils/string');
	const { clone, isEqual, mergeImmutable, merge } = require('utils/object');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { PropTypes } = require('utils/validation');
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
	 * @class SearchBar
	 * @typedef {LayoutComponent<SearchBarProps, SearchBarState>}
	 */
	class SearchBar extends PureComponent
	{
		// region init

		constructor(props)
		{
			super(props);

			this.wrapperRef = null;

			this.state = this.getInitialState();

			this.presetsBackendProvider = {
				route: this.props.searchDataAction,
				params: this.props.searchDataActionParams || {},
			};

			this.show = this.show.bind(this);
			this.onHide = this.onHide.bind(this);
			this.onDone = this.onDone.bind(this);
			this.onCancel = this.onCancel.bind(this);
			this.onItemClick = this.onItemClick.bind(this);
			this.onTextChanged = this.onTextChanged.bind(this);

			this.debounceSearch = debounce((params) => this.search(params, false), 500, this);
		}

		/**
		 * @private
		 * @return {SearchBarState}
		 */
		getInitialState()
		{
			return {
				visible: false,
				counters: null,
				presets: null,
				search: '',
				presetId: (this.props.presetId || null),
				counterId: (this.props.counterId || null),
				iconBackground: DEFAULT_ICON_BACKGROUND,
			};
		}

		/**
		 * @param {SearchBarProps} nextProps
		 */
		componentWillReceiveProps(nextProps)
		{
			if (this.props.id !== nextProps.id)
			{
				this.presetsBackendProvider = {
					route: nextProps.searchDataAction,
					params: nextProps.searchDataActionParams || {},
				};
				this.fetchPresetsAndCounters(true, false);
			}
		}

		componentDidMount()
		{
			BX.removeCustomEvent('UI.SearchBar::show', this.show);
			BX.addCustomEvent('UI.SearchBar::show', this.show);

			const { layout } = this.props;

			layout.search.removeAllListeners('cancel');
			layout.search.removeAllListeners('hide');
			layout.search.removeAllListeners('textChanged');
			layout.search.removeAllListeners(ENTER_PRESSED_EVENT);

			layout.search.on('hide', () => this.onHide());
			layout.search.on('textChanged', (params) => this.onTextChanged(params));
			layout.search.on('cancel', () => this.onCancel());

			layout.search.on(ENTER_PRESSED_EVENT, () => this.onDone());

			if (Application.getApiVersion() > 44)
			{
				layout.search.setReturnKey('done');
			}
		}

		// endregion

		// region public api

		/**
		 * @public
		 * @param {{
		 *     searchBarId?: string,
		 *     search?: string,
		 *     presetId?: string,
		 *     counterId?: string,
		 * }} params
		 */
		show(params = {})
		{
			if (params.searchBarId && params.searchBarId !== this.props.id)
			{
				return;
			}

			if (this.state.visible)
			{
				return;
			}

			this.fetchPresetsAndCounters();

			const text = params.search || '';

			const { search } = this.props.layout;

			search.mode = 'bar';
			search.text = text;
			search.show();

			this.setState({
				visible: true,
				presetId: params.presetId || null,
				counterId: params.counterId || null,
				search: text,
			});
		}

		/**
		 * @public
		 */
		close()
		{
			this.props.layout.search.close();
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		fadeOut()
		{
			if (!this.wrapperRef)
			{
				this.close();

				return Promise.resolve();
			}

			return new Promise((resolve) => {
				this.wrapperRef.animate({ opacity: 0, duration: 300 }, () => {
					this.close();
					resolve();
				});
			});
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isVisible()
		{
			return this.state.visible;
		}

		/**
		 * @public
		 * @return {string|null}
		 */
		getDefaultPresetId()
		{
			if (!this.presetsWasLoaded())
			{
				return null;
			}

			const defaultPreset = this.state.presets.find((preset) => (preset.default === true && preset.disabled !== true));

			return (defaultPreset ? defaultPreset.id : null);
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		presetsWasLoaded()
		{
			return Array.isArray(this.state.presets);
		}

		/**
		 * @public
		 * @param {Object.<string, number>} counters
		 */
		updateCounters(counters)
		{
			if (!Array.isArray(this.state.counters))
			{
				return;
			}

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
		 * @private
		 * @param {boolean} force
		 * @param {boolean} visible
		 */
		fetchPresetsAndCounters(force = false, visible = true)
		{
			if (this.presetsWasLoaded() && !force)
			{
				return;
			}

			const { route, params } = this.presetsBackendProvider;

			new RunActionExecutor(route, params)
				.setCacheId(this.getCacheId())
				.setCacheHandler((response) => this.onLoadSearchData(response, visible))
				.setHandler((response) => this.onLoadSearchData(response, visible))
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
		 * @param {boolean} visible
		 */
		onLoadSearchData(response, visible = true)
		{
			if (!response || !response.data)
			{
				return;
			}

			const { counters, presets } = response.data;

			if (!isEqual(this.state.counters, counters) || !isEqual(this.state.presets, presets))
			{
				this.setState({ counters, presets, visible });
			}
		}

		// endregion

		// region searching

		/**
		 * @private
		 */
		onHide()
		{
			if (!this.state.visible)
			{
				return;
			}

			this.setState({ visible: false }, () => {
				BX.postComponentEvent('UI.SearchBar::onSearchHide', [
					{
						searchBarId: this.props.id,
					},
				]);
			});
		}

		/**
		 * @private
		 */
		onDone()
		{
			this.close();
			this.onHide();
		}

		/**
		 * @private
		 */
		onCancel()
		{
			const newState = mergeImmutable(this.state, {
				search: '',
				counterId: null,
				presetId: this.getDefaultPresetId(),
			});

			if (isEqual(this.state, newState))
			{
				return;
			}

			const params = {
				searchBarId: this.props.id,
				counter: {
					id: newState.counterId,
				},
				preset: {
					id: newState.presetId,
				},
				isCancel: true,
			};

			this.setState(newState, () => BX.postComponentEvent('UI.SearchBar::onSearch', [params]));
		}

		/**
		 * @private
		 * @param {object} params
		 */
		onTextChanged(params = {})
		{
			if (this.state.presetId)
			{
				merge(params, {
					preset: { id: this.state.presetId },
				});
			}

			if (this.state.counterId)
			{
				const counter = this.state.counters.find((item) => item.code === this.state.counterId);
				merge(params, {
					counter: {
						code: this.state.counterId,
						id: counter.typeId,
						excludeUsers: counter.excludeUsers,
					},
				});
			}

			this.debounceSearch(params);
		}

		/**
		 * @private
		 * @param {{
		 *     text?: string,
		 *     preset?: {
		 *         id: string,
		 *     },
		 *     counter?: {
		 *         code: string,
		 *     },
		 *     data?: {
		 *         background: string,
		 *     }
		 * }} params
		 * @param {boolean} closeLayout
		 */
		search(params = {}, closeLayout = true)
		{
			if (closeLayout)
			{
				this.close();
			}

			if (params.text && params.text.length < MINIMAL_SEARCH_LENGTH)
			{
				return;
			}

			const text = stringify(params.text === undefined ? this.state.search : params.text);

			if (text.length > 0 && this.hasRestrictions())
			{
				this.props.layout.search.text = '';

				return;
			}

			const newState = {
				search: text,
				counterId: null,
				presetId: null,
			};

			const { preset, counter, data = {} } = params;
			if (preset)
			{
				newState.presetId = preset.id || null;
			}
			else if (counter)
			{
				newState.counterId = counter.code || null;
			}

			if (newState.search || newState.presetId !== this.getDefaultPresetId())
			{
				if (!data.background && newState.search)
				{
					data.background = DEFAULT_ICON_BACKGROUND;
				}
				else if (!newState.search && !newState.presetId && !newState.counterId)
				{
					data.background = DEFAULT_ICON_BACKGROUND;
				}
				else if (data.background)
				{
					newState.iconBackground = data.background;
				}
				else
				{
					data.background = this.state.iconBackground;
				}
			}

			this.setState(newState, () => {
				BX.postComponentEvent('UI.SearchBar::onSearch', [
					{
						...params,
						text,
						data,
						searchBarId: this.props.id,
					},
				]);
			});
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
			const { visible } = this.state;
			const presets = (visible ? this.getPreparedPresets() : []);

			return View(
				{
					testId: 'search-presets-list-wrapper',
					style: styles.wrapper(visible),
					ref: (ref) => {
						this.wrapperRef = ref;
					},
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
			if (this.presetsWasLoaded())
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
			if (!Array.isArray(this.state.counters))
			{
				return [];
			}

			const counters = clone(this.state.counters);

			return counters.map((counter) => new Counter({
				...counter,
				active: (this.state.counterId === counter.code),
				onClick: this.onItemClick,
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
			if (!this.presetsWasLoaded())
			{
				return [];
			}

			const presets = clone(this.state.presets);

			return presets.map((preset, index) => new Preset({
				...preset,
				active: (this.state.presetId === preset.id),
				onClick: this.onItemClick,
				last: (index === presets.length - 1),
			}));
		}

		/**
		 * @private
		 * @param {{presetId: string, counterId: string}} params
		 * @param {Boolean} active
		 */
		onItemClick(params, active)
		{
			const searchParams = (active ? params : {});
			this.search(searchParams, false);
		}

		// endregion
	}

	SearchBar.propTypes = {
		id: PropTypes.string.isRequired,
		cacheId: PropTypes.string,
		layout: PropTypes.object.isRequired,
		searchDataAction: PropTypes.string.isRequired,
		onCheckRestrictions: PropTypes.func,
		onMoreButtonClick: PropTypes.func,
		presetId: PropTypes.string,
		counterId: PropTypes.string,
		searchDataActionParams: PropTypes.object,
	};

	const styles = {
		wrapper: (isVisible) => {
			return {
				top: 3,
				position: isVisible ? 'absolute' : 'relative',
				zIndex: 10,
				height: isVisible ? 44 : 0,
				width: '100%',
				backgroundColor: AppTheme.colors.bgNavigation,
			};
		},
		presetsScrollView: {
			height: 44,
		},
		presetsWrapper: {
			flexDirection: 'row',
			alignItems: 'center',
			marginTop: -6,
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

	module.exports = { SearchBar };
});
