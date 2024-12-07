/**
 * @module layout/ui/search-bar/search-layout
 */
jn.define('layout/ui/search-bar/search-layout', (require, exports, module) => {
	const { debounce } = require('utils/function');
	const { isEqual } = require('utils/object');
	const { PropTypes } = require('utils/validation');
	const { Type } = require('type');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const {
		MINIMAL_SEARCH_LENGTH,
		DEFAULT_ICON_BACKGROUND,
		ENTER_PRESSED_EVENT,
	} = require('layout/ui/search-bar/ui');
	const { SearchLayoutView } = require('layout/ui/search-bar/search-layout-view');

	/**
	 * @class SearchLayout
	 */
	class SearchLayout
	{
		// region init

		constructor(props)
		{
			this.props = props;

			this.setupNativeSearchField();

			this.counters = [];
			this.presets = [];
			this.selectedPresetBackground = null;
			this.presetsLoaded = false;

			this.text = '';
			this.presetId = (props.presetId || null);
			this.counterId = (props.counterId || null);

			this.presetsBackendProvider = {
				route: props.searchDataAction,
				params: props.searchDataActionParams || {},
			};

			this.show = this.show.bind(this);
			this.onCancel = this.onCancel.bind(this);
			this.onTextChanged = this.onTextChanged.bind(this);
			this.onPresetClick = this.onPresetClick.bind(this);
			this.search = this.search.bind(this);

			this.debounceSearch = debounce(() => this.search(), 500, this);
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

			search.mode = this.props.disablePresets ? 'bar' : 'layout';

			search.removeAllListeners('cancel');
			search.removeAllListeners('hide');
			search.removeAllListeners('textChanged');
			search.removeAllListeners(ENTER_PRESSED_EVENT);

			search.on('textChanged', (params) => this.onTextChanged(params));
			search.on('cancel', () => this.onCancel());
			search.on(ENTER_PRESSED_EVENT, () => this.close());

			search.setReturnKey('done');
		}

		// endregion

		// region public api

		/**
		 * @public
		 */
		show()
		{
			const search = this.nativeSearchField;
			search.text = this.text;

			this.createSearchLayoutView();

			if (this.props.disablePresets)
			{
				search.show();
			}
			else
			{
				search.show(this.searchLayoutView, 44);
				this.fetchPresets();
			}
		}

		createSearchLayoutView()
		{
			const params = {
				presetsLoaded: this.presetsLoaded,
				counters: this.counters,
				counterId: this.counterId,
				presets: this.presets,
				presetId: this.presetId,
				search: this.search,
				text: this.text,
				onPresetClick: this.onPresetClick,
			};
			this.searchLayoutView = new SearchLayoutView(params);
		}

		/**
		 * @public
		 */
		close()
		{
			this.nativeSearchField.close();
		}

		getSearchButton = () => {
			return {
				type: 'search',
				id: 'search',
				testId: 'search',
				callback: this.show,
				accent: this.hasChanges(),
			};
		};

		/**
		 * @public
		 * @return {string|null}
		 * @deprecated - now you need to use getSearchButton
		 */
		getSearchButtonBackgroundColor()
		{
			if (this.hasChanges())
			{
				return this.selectedPresetBackground || DEFAULT_ICON_BACKGROUND;
			}

			return null;
		}

		/**
		 * @public
		 * @return {string|null}
		 */
		getDefaultPresetId()
		{
			if (Type.isFunction(this.props.getDefaultPresetId))
			{
				return this.props.getDefaultPresetId();
			}

			if (!this.presetsLoaded)
			{
				return null;
			}

			const defaultPreset = this.presets.find((preset) => (preset.default === true && preset.disabled !== true));

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

			this.counters.forEach(({ code, value }, index) => {
				if (!propertyExists(counters, code))
				{
					return;
				}

				const nextValue = counters[code];
				if (nextValue > 0 && nextValue !== value)
				{
					this.counters[index].value = nextValue;
					stateWasChanged = true;
				}
			});

			if (stateWasChanged)
			{
				this.searchLayoutView.setPresets({ counters: this.counters });
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
			const executor = this.getRunActionExecutor();
			const cacheExpired = executor.getCache().getData() === null;
			if (this.presetsLoaded && !force && !cacheExpired)
			{
				return;
			}

			executor.call(true);
		}

		/**
		 * @private
		 * @return {RunActionExecutor}
		 */
		getRunActionExecutor()
		{
			const { route, params } = this.presetsBackendProvider;

			return new RunActionExecutor(route, params)
				.setCacheId(this.getCacheId())
				.setCacheHandler((response) => this.onLoadPresets(response))
				.setHandler((response) => this.onLoadPresets(response))
				.setCacheTtl(3600);
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

			const { counters = [], presets } = response.data;

			if (!this.presetsLoaded || !isEqual(this.counters, counters) || !isEqual(this.presets, presets))
			{
				this.counters = counters;
				this.presets = Object.entries(presets).map(([id, preset]) => ({ id, ...preset }));

				this.searchLayoutView.setPresets(this.presets, this.counters);
			}

			this.presetsLoaded = true;
		}

		// endregion

		// region searching

		/**
		 * @private
		 */
		onCancel()
		{
			this.text = '';
			this.counterId = null;
			this.presetId = this.getDefaultPresetId();

			if (this.props.onCancel)
			{
				this.props.onCancel({ text: this.text, counterId: this.counterId, presetId: this.presetId });
			}
		}

		/**
		 * @private
		 */
		onTextChanged({ text = '' })
		{
			this.text = text;

			this.debounceSearch();
		}

		onPresetClick(patch)
		{
			this.counterId = patch.counterId;
			this.selectedPresetBackground = patch.selectedPresetBackground;
			if (this.presetId === patch.presetId)
			{
				this.presetId = null;
			}
			else
			{
				this.presetId = patch.presetId;
			}
			this.search();
		}

		/**
		 * @private
		 */
		search()
		{
			if (this.text.length > 0 && this.hasRestrictions())
			{
				this.text = '';
				this.nativeSearchField.text = '';

				return;
			}

			if (this.text.length > 0 && this.text.length < MINIMAL_SEARCH_LENGTH)
			{
				return;
			}

			this.searchLayoutView.setPresetId(this.presetId, this.counterId);

			if (this.props.onSearch)
			{
				this.props.onSearch({ text: this.text, counterId: this.counterId, presetId: this.presetId });
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

		/**
		 * @private
		 * @return {boolean}
		 */
		hasChanges()
		{
			if (this.text.length > 0)
			{
				return true;
			}

			if (this.presetsLoaded)
			{
				return this.presetId !== this.getDefaultPresetId();
			}

			if (Type.isFunction(this.props.getDefaultPresetId))
			{
				return this.presetId !== this.props.getDefaultPresetId();
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
		disablePresets: PropTypes.bool,
	};

	module.exports = { SearchLayout };
});
