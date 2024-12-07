/**
 * @module calendar/event-list-view/search/preset
 */
jn.define('calendar/event-list-view/search/preset', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { Indent } = require('tokens');
	const { ChipFilter } = require('ui-system/blocks/chips/chip-filter');

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

			return ChipFilter(
				{
					testId: `calendar_filter_preset_${this.preset.id}`,
					text: name,
					selected: active,
					onClick: () => this.onClickHandler(),
					style: {
						marginRight: (last) ? 0 : Indent.M.toNumber(),
						flexShrink: null,
						flexGrow: 2,
					},
					textStyles: {
						maxWidth: 250,
					},
				},
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

	module.exports = { Preset };
});
