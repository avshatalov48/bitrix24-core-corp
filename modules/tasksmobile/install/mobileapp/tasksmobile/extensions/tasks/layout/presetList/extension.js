/**
 * @module tasks/layout/presetList
 */
jn.define('tasks/layout/presetList', (require, exports, module) => {
	const {Haptics} = require('haptics');
	const {smallCross} = require('assets/common');

	class PresetList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				presets: props.presets,
				currentPreset: props.currentPreset,
			};
		}

		updateState(newState)
		{
			this.setState({
				presets: newState.presets,
				currentPreset: newState.currentPreset,
			});
		}

		render()
		{
			return ScrollView(
				{
					horizontal: true,
					showsHorizontalScrollIndicator: false,
					style: {
						backgroundColor: '#f5f7f8',
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
						},
						testId: 'presetList',
					},
					this.renderLoader(),
					...this.renderPresets(),
				),
			);
		}

		arePresetsLoaded()
		{
			return Array.isArray(this.state.presets);
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
					tintColor: '#82888f',
					animating: true,
					size: 'small',
					testId: 'loader',
				});
			}

			return null;
		}

		renderPresets()
		{
			if (!this.arePresetsLoaded())
			{
				return [];
			}

			return Object.values(this.state.presets).map((preset, index) => {
				const isActive = (this.state.currentPreset === preset.id);
				return View(
					{
						style: {
							flexDirection: 'row',
							height: 32,
							justifyContent: 'center',
							alignItems: 'center',
							backgroundColor: (isActive ? '#c3f2ff' : 'inherit'),
							borderRadius: 30,
							paddingHorizontal: 10,
							marginLeft: (index === 0 ? 8 : 0),
							marginRight: ((index === Object.keys(this.state.presets).length - 1 && isActive) ? 8 : 0),
						},
						onClick: () => {
							Haptics.impactLight();
							this.emit('presetSelected', [preset]);
							this.setState({currentPreset: (isActive ? null : preset.id)});
						},
						testId: `preset_${preset.id}`,
					},
					Text({
						style: {
							fontWeight: '500',
							fontSize: 16,
							lineHeight: 10,
							maxWidth: 300,
						},
						text: preset.name,
						ellipsize: 'middle',
						testId: `preset_${preset.id}_name`,
					}),
					(isActive && Image({
						style: {
							marginLeft: 13,
							marginRight: 2,
							width: 8,
							height: 8,
						},
						svg: {
							content: smallCross(),
						},
						testId: `preset_${preset.id}_cross`,
					})),
				);
			});
		}
	}

	module.exports = {PresetList};
});