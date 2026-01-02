/**
 * @module calendar/event-list-view/search/layout-view
 */
jn.define('calendar/event-list-view/search/layout-view', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Preset } = require('calendar/event-list-view/search/preset');
	const { observeState } = require('calendar/event-list-view/state');

	const PRESET_LIST_LAYOUT_HEIGHT = 46;
	const PRESET_WRAPPER_HEIGHT = 34;

	/**
	 * @class SearchLayoutView
	 */
	class SearchLayoutView extends LayoutComponent
	{
		get onPresetSelected()
		{
			return this.props.onPresetSelected;
		}

		render()
		{
			return View(
				{
					style: styles.container,
				},
				ScrollView(
					{
						showsHorizontalScrollIndicator: false,
						horizontal: true,
						style: styles.presetsScrollView,
					},
					View(
						{
							style: styles.presetsWrapper,
							testId: 'presetList',
						},
						...this.renderPresets(),
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
		container: {
			paddingHorizontal: Indent.XL3.toNumber(),
			paddingVertical: Indent.XS.toNumber(),
			height: PRESET_LIST_LAYOUT_HEIGHT,
			width: '100%',
			backgroundColor: Color.bgNavigation.toHex(),
			borderBottomWidth: 1,
			borderBottomColor: Color.bgSeparatorSecondary.toHex(),
		},
		presetsScrollView: {
			height: PRESET_LIST_LAYOUT_HEIGHT,
		},
		presetsWrapper: {
			flexDirection: 'row',
			alignItems: 'center',
			alignContent: 'center',
			height: PRESET_WRAPPER_HEIGHT,
		},
	};

	const mapStateToProps = (state) => ({
		presetId: state.presetId,
	});

	module.exports = { SearchLayoutView: observeState(SearchLayoutView, mapStateToProps) };
});
