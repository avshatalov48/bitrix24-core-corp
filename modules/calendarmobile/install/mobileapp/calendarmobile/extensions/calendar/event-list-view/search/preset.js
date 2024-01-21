/**
 * @module calendar/event-list-view/search/preset
 */
jn.define('calendar/event-list-view/search/preset', (require, exports, module) => {

	const AppTheme = jn.require('apptheme');
	const { Haptics } = require('haptics');

	/**
	 * @class Preset
	 */
	class Preset extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.preset = {
				id: props.id,
				name: props.name,
				fields: props.fields,
			};
		}

		render()
		{
			const { active, last, name } = this.props;

			return View(
				{
					style: styles.wrapper(active, last),
					onClick: () => this.onClickHandler(),
					testId: `preset_${this.preset.id}`,
				},
				Text(
					{
						style: styles.presetName,
						text: name,
						ellipsize: 'middle',
						testId: `preset_${this.preset.id}_name`
					},
				),
				active && Image(
					{
						style: styles.closeIcon,
						svg: {
							content: closeIconSvg,
						},
						testId: `preset_${this.preset.id}_cross`,
					},
				)
			);
		}

		onClickHandler()
		{
			Haptics.impactLight();

			const params = this.getOnClickParams();
			const active = !this.props.active;

			this.props.onPresetSelected(params, active);
		}

		getOnClickParams()
		{
			return {
				preset: this.preset,
			};
		}
	}

	const styles = {
		wrapper: (active, isLast = false) => {
			return {
				paddingHorizontal: 10,
				backgroundColor: active ? AppTheme.colors.accentSoftBlue1 : 'inherit',
				borderRadius: 30,
				justifyContent: 'center',
				alignItems: 'center',
				flexDirection: 'row',
				height: 32,
				marginRight: (isLast && active) ? 8 : 0,
			};
		},
		presetName: {
			color: AppTheme.colors.base1,
			fontWeight: '500',
			fontSize: 16,
			lineHeight: 10,
			maxWidth: 300,
		},
		closeIcon: {
			marginLeft: 13,
			marginRight: 2,
			width: 8,
			height: 8,
		},
	}

	const closeIconSvg = '<svg width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.05882 0.000222688L8 0.941373L0.941178 8L1.38837e-06 7.05885L7.05882 0.000222688Z" fill="#828B95"/><path d="M0 0.94115L0.941176 0L8 7.05863L7.05882 7.99978L0 0.94115Z" fill="#828B95"/></svg>';

	module.exports = { Preset };
});
