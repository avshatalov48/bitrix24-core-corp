/**
 * @module crm/entity-tab/search
 */
jn.define('crm/entity-tab/search', (require, exports, module) => {
	const { debounce } = require('utils/function');
	const { clone, isEqual, mergeImmutable } = require('utils/object');
	const { Preset } = require('crm/entity-tab/search/preset');
	const { Counter } = require('crm/entity-tab/search/counter');
	const { PlanRestriction } = require('layout/ui/plan-restriction');
	const { getEntityMessage } = require('crm/loc');

	const MINIMAL_SEARCH_LENGTH = 3;
	const DEFAULT_ICON_BACKGROUND = '#2fc6f6';

	/**
	 * @class Search
	 */
	class Search extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.entityTypeName = props.entityTypeName;
			this.categoryId = props.categoryId;
			this.wrapperRef = null;

			this.isApiGreaterThen44 = (Application.getApiVersion() > 44);
			this.nameOfClickEnterEvent = (this.isApiGreaterThen44 ? 'clickEnter' : 'clickSearch');

			this.state = this.getInitialState();

			this.show = this.showHandler.bind(this);
			this.onHide = this.onHideHandler.bind(this);
			this.onDone = this.onDoneHandler.bind(this);
			this.onCancel = this.onCancelHandler.bind(this);
			this.onItemClick = this.onItemClickHandler.bind(this);
			this.onTextChanged = this.onTextChangedHandler.bind(this);

			this.debounceSearch = debounce((params) => this.search(params, false), 500, this);
		}

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

		componentWillReceiveProps(nextProps)
		{
			if (
				this.categoryId !== nextProps.categoryId
				|| this.entityTypeName !== nextProps.entityTypeName
			)
			{
				this.entityTypeName = nextProps.entityTypeName;
				this.categoryId = nextProps.categoryId;
				this.fetchPresetsAndCounters(true, false);
			}
		}

		componentDidMount()
		{
			BX.removeCustomEvent('Crm.EntityTab::onSearchShow', this.show);
			BX.addCustomEvent('Crm.EntityTab::onSearchShow', this.show);

			const { layout } = this.props;

			layout.search.removeAllListeners('cancel');
			layout.search.removeAllListeners('hide');
			layout.search.removeAllListeners('textChanged');
			layout.search.removeAllListeners(this.nameOfClickEnterEvent);

			layout.search.on('hide', () => this.onHide());
			layout.search.on('textChanged', (params) => this.onTextChanged(params));
			layout.search.on('cancel', () => this.onCancel());

			layout.search.on(this.nameOfClickEnterEvent, () => this.onDone());

			if (this.isApiGreaterThen44)
			{
				layout.search.setReturnKey('done');
			}
		}

		showHandler(params)
		{
			if (!this.state.visible)
			{
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
		}

		onHideHandler()
		{
			if (!this.state.visible)
			{
				return;
			}

			this.setState({ visible: false }, () => BX.postComponentEvent('Crm.EntityTab::onSearchHide'));
		}

		onDoneHandler()
		{
			this.close();
			this.onHide();
		}

		onCancelHandler()
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
				counter: {
					id: newState.counterId,
				},
				preset: {
					id: newState.presetId,
				},
				isCancel: true,
			};

			this.setState(newState, () => BX.postComponentEvent('Crm.EntityTab::onSearch', [params]));
		}

		getDefaultPresetId()
		{
			if (!Array.isArray(this.state.presets))
			{
				return null;
			}

			const defaultPreset = this.state.presets.find((preset) => preset.default === true);

			return (defaultPreset ? defaultPreset.id : null);
		}

		onTextChangedHandler(params)
		{
			if (this.state.presetId)
			{
				params.preset = { id: this.state.presetId };
			}

			if (this.state.counterId)
			{
				const counter = this.state.counters.find((counter) => counter.code === this.state.counterId);
				params.counter = {
					code: this.state.counterId,
					id: counter.typeId,
					excludeUsers: counter.excludeUsers,
				};
			}

			this.debounceSearch(params);
		}

		arePresetsLoaded()
		{
			return Array.isArray(this.state.presets);
		}

		// @todo change the counters in realtime
		fetchPresetsAndCounters(force = false, visible = true)
		{
			if (this.arePresetsLoaded() && !force)
			{
				return null;
			}

			const cacheId = this.getCacheId();

			const data = {
				entityTypeName: this.entityTypeName,
				categoryId: this.categoryId,
			};

			new RunActionExecutor(this.props.getSearchDataAction, data)
				.setCacheId(cacheId)
				.setCacheHandler((response) => this.setSearchDataFromResponse(response, visible))
				.setHandler((response) => this.setSearchDataFromResponse(response, visible))
				.call(true);
		}

		getCacheId()
		{
			const categoryId = (this.categoryId === null ? 'all' : this.categoryId);

			return `Crm.SearchBar.${this.entityTypeName}.${categoryId}.${env.userId}`;
		}

		updateCounters(counters)
		{
			if (!Array.isArray(this.state.counters))
			{
				return;
			}

			let needSetState = false;
			this.state.counters.forEach((counter) => {
				if (
					counters.hasOwnProperty(counter.code)
					&& counter.value !== counters[counter.code]
					&& counters[counter.code] >= 0
				)
				{
					counter.value = counters[counter.code];
					needSetState = true;
				}
			});

			if (needSetState)
			{
				this.setState({});
			}
		}

		setSearchDataFromResponse(response, visible = true)
		{
			if (
				response.data
				&& (
					!isEqual(this.state.counters, response.data.counters)
					|| !isEqual(this.state.presets, response.data.presets)
				)
			)
			{
				const { counters, presets } = response.data;
				this.setState({
					counters,
					presets,
					visible,
				});
			}
		}

		render()
		{
			const { visible } = this.state;
			const presets = (visible ? this.getPreparedPresets() : []);

			return View(
				{
					style: styles.wrapper(visible),
					ref: (ref) => this.wrapperRef = ref,
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
			if (!this.arePresetsLoaded())
			{
				return Loader({
					style: {
						width: 50,
						height: 50,
					},
					tintColor: '#828b95',
					animating: true,
					size: 'small',
				});
			}

			return null;
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

			return counters.map((counter) => {
				counter.active = (this.state.counterId === counter.code);
				counter.onClick = this.onItemClick;
				return new Counter(counter);
			});
		}

		renderPresets(presets)
		{
			return presets.filter((preset) => !preset.isDefault());
		}

		/**
		 * @returns {Preset[]}
		 */
		getPreparedPresets()
		{
			if (!this.arePresetsLoaded())
			{
				return [];
			}

			const presets = clone(this.state.presets);

			return presets.map((preset, index) => {
				preset.active = (this.state.presetId === preset.id);
				preset.onClick = this.onItemClick;
				preset.last = (index === presets.length - 1);
				return new Preset(preset);
			});
		}

		/**
		 * @param {{presetId: string, counterId: string}} params
		 * @param {Boolean} active
		 */
		onItemClickHandler(params, active)
		{
			const searchParams = (active ? params : {});
			this.search(searchParams, false);
		}

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

			params.text = (params.text === undefined ? this.state.search : params.text);

			if (params.text.length > 0 && this.props.restrictions.isExceeded)
			{
				this.props.layout.search.text = '';
				void PlanRestriction.open(
					{
						title: BX.message('M_CRM_ET_SEARCH_PLAN_RESTRICTION_TITLE'),
					},
					this.props.layout,
				);

				return;
			}

			const newState = {
				search: params.text,
				counterId: null,
				presetId: null,
			};

			const { preset, counter } = params;
			if (preset)
			{
				newState.presetId = preset.id || null;
			}
			else if (counter)
			{
				newState.counterId = counter.code || null;
			}

			params.data = params.data || {};
			if (newState.search || newState.presetId !== this.getDefaultPresetId())
			{
				if (!params.data.background && newState.search)
				{
					params.data.background = DEFAULT_ICON_BACKGROUND;
				}
				else if (!newState.search && !newState.presetId && !newState.counterId)
				{
					params.data.background = DEFAULT_ICON_BACKGROUND;
				}
				else if (params.data.background)
				{
					this.state.iconBackground = params.data.background;
				}
				else
				{
					params.data.background = this.state.iconBackground;
				}
			}

			this.setState(newState, () => BX.postComponentEvent('Crm.EntityTab::onSearch', [params]));
		}

		renderMoreButton()
		{
			return View(
				{
					style: styles.moreButtonView,
					onClick: () => {
						this.showFilterSettings();
					},
				},
				Image({
					style: styles.moreButtonImage,
					svg: {
						content: MORE_BUTTON_ICON,
					},
				}),
			);
		}

		showFilterSettings()
		{
			const pathToExtension = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/entity-tab/search/`;
			const imagePath = `${pathToExtension}images/settings.png`;

			this.menu = new ContextMenu({
				banner: {
					featureItems: [
						BX.message('M_CRM_ENTITY_TAB_SEARCH_FILTER_SETTINGS_CREATE_FILTER'),
						BX.message('M_CRM_ENTITY_TAB_SEARCH_FILTER_SETTINGS_MORE_SETTINGS'),
						BX.message('M_CRM_ENTITY_TAB_SEARCH_FILTER_SETTINGS_RESPONSIBLE'),
						getEntityMessage(
							'M_CRM_ENTITY_TAB_SEARCH_FILTER_SETTINGS_CUSTOMIZATION',
							this.props.entityTypeName,
						),
					],
					imagePath,
					qrauth: {
						redirectUrl: this.props.link,
						type: 'crm',
					},
				},
				params: {
					title: BX.message('M_CRM_ENTITY_TAB_SEARCH_FILTER_SETTINGS_TITLE'),
				},
			});

			this.menu.show(PageManager);
		}

		fadeOut()
		{
			return new Promise((resolve) => {
				this.wrapperRef.animate({
					opacity: 0,
					duration: 300,
				}, resolve);
			});
		}

		isVisible()
		{
			return this.state.visible;
		}

		close()
		{
			this.props.layout.search.close();
		}
	}

	const styles = {
		wrapper: (isVisible) => {
			return {
				top: 3,
				position: isVisible ? 'absolute' : 'relative',
				zIndex: 10,
				height: isVisible ? 44 : 0,
				width: '100%',
				backgroundColor: '#f5f7f8',
			};
		},
		moreButtonView: {
			width: 50,
			borderColor: '#c3f0ff',
			borderRadius: 20,
			borderWidth: 2,
			height: 34,
			justifyContent: 'center',
			alignItems: 'center',
		},
		moreButtonImage: {
			width: 16,
			height: 4,
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
			backgroundColor: '#fff',
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
			color: '#525c69',
			marginBottom: 10,
			marginLeft: 20,
		},
	};

	const MORE_BUTTON_ICON = '<svg width="16" height="4" viewBox="0 0 16 4" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 4C3.10457 4 4 3.10457 4 2C4 0.89543 3.10457 0 2 0C0.89543 0 0 0.89543 0 2C0 3.10457 0.89543 4 2 4Z" fill="#828b95"/><path d="M8 4C9.10457 4 10 3.10457 10 2C10 0.89543 9.10457 0 8 0C6.89543 0 6 0.89543 6 2C6 3.10457 6.89543 4 8 4Z" fill="#828b95"/><path d="M16 2C16 3.10457 15.1046 4 14 4C12.8954 4 12 3.10457 12 2C12 0.89543 12.8954 0 14 0C15.1046 0 16 0.89543 16 2Z" fill="#828b95"/></svg>';

	module.exports = { Search };
});
