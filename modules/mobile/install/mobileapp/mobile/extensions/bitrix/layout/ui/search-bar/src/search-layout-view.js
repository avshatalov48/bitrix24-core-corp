/**
 * @module layout/ui/search-bar/search-layout-view
 */
jn.define('layout/ui/search-bar/search-layout-view', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { clone } = require('utils/object');
	const { Preset } = require('layout/ui/search-bar/preset');
	const { Counter } = require('layout/ui/search-bar/counter');
	const {
		MoreButton,
	} = require('layout/ui/search-bar/ui');

	/**
	 * @class SearchLayoutView
	 */
	class SearchLayoutView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				presets: props.presets,
				presetsLoaded: props.presetsLoaded,
				presetId: props.presetId,
			};

			this.onPresetClick = this.onPresetClick.bind(this);
		}

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
			const counters = clone(this.props.counters);

			return counters.map((counter) => new Counter({
				...counter,
				active: (this.props.counterId === counter.code),
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
			this.props.onPresetClick(patch);
		}

		/**
		 * @public
		 * @param {Array} presets
		 * @param {Array} counters
		 */
		setPresets(presets = [], counters = [])
		{
			this.setState({ presets, counters, presetsLoaded: true });
		}

		/**
		 * @public
		 * @param {String} presetId
		 * @param {String} counterId
		 */
		setPresetId(presetId = null, counterId = null)
		{
			this.setState({ presetId, counterId });
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
	}

	SearchLayoutView.propTypes = {
		presets: PropTypes.array,
		presetsLoaded: PropTypes.bool,
		presetId: PropTypes.string,
		counters: PropTypes.array,
		counterId: PropTypes.string,
		onMoreButtonClick: PropTypes.func,
		onPresetClick: PropTypes.func,
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

	module.exports = { SearchLayoutView };
});
