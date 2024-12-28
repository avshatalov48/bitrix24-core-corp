/**
 * @module calendar/event-list-view/search/preset
 */
jn.define('calendar/event-list-view/search/preset', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { Indent } = require('tokens');
	const { ChipFilter } = require('ui-system/blocks/chips/chip-filter');

	const Preset = (props) => {
		const { id, active, last, name } = props;

		return ChipFilter(
			{
				testId: `calendar_filter_preset_${id}`,
				text: name,
				selected: active,
				onClick: () => onClickHandler(props),
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
	};

	const onClickHandler = (props) => {
		const { id, name, fields, active, onPresetSelected } = props;

		const preset = { id, name, fields };

		Haptics.impactLight();

		const params = {
			preset,
		};

		onPresetSelected(params, !active);
	};

	module.exports = { Preset };
});
