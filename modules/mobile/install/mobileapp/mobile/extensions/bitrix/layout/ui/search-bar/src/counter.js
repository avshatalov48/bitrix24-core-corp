/**
 * @module layout/ui/search-bar/counter
 */
jn.define('layout/ui/search-bar/counter', (require, exports, module) => {
	const { BaseItem } = require('layout/ui/search-bar/base-item');
	const { ChipFilter, BadgeCounterDesign } = require('ui-system/blocks/chips/chip-filter');
	const { Indent } = require('tokens');

	const COUNTER_DESIGNS = {
		8: BadgeCounterDesign.SUCCESS, // incoming
		20: BadgeCounterDesign.ALERT, // current
		999: BadgeCounterDesign.PRIMARY,
		default: BadgeCounterDesign.GREY,
	};

	/**
	 * @class Counter
	 * @typedef {LayoutComponent<SearchBarCounterProps, {}>}
	 */
	class Counter extends BaseItem
	{
		constructor(props)
		{
			super(props);

			this.counter = {
				id: props.typeId,
				code: props.code,
				typeName: props.typeName,
				excludeUsers: props.excludeUsers || false,
			};
		}

		render()
		{
			const {
				id,
				title,
				active,
				showValue,
				value,
				last,
				typeId,
			} = this.props;

			return ChipFilter(
				{
					testId: id,
					text: title,
					selected: active,
					counterValue: showValue ? value : null,
					counterDesign: this.getCounterDesign(typeId),
					onClick: () => this.onClick(),
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

		/**
		 * @protected
		 * @override
		 * @return {string}
		 */
		getSearchButtonBackgroundColor()
		{
			return this.getCounterDesign(this.props.typeId).getBackgroundColor().toHex();
		}

		/**
		 * @protected
		 * @return {string}
		 */
		getCounterDesign(typeId)
		{
			return COUNTER_DESIGNS[Number(typeId)] ?? COUNTER_DESIGNS.default;
		}

		/**
		 * @protected
		 * @return {object}
		 */
		getOnClickParams()
		{
			const params = super.getOnClickParams();
			params.counter = this.counter;
			params.counterId = this.counter.code;

			return params;
		}
	}

	module.exports = { Counter };
});
