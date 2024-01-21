/**
 * @module layout/ui/search-bar/counter
 */
jn.define('layout/ui/search-bar/counter', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { BaseItem } = require('layout/ui/search-bar/base-item');
	const { CloseIcon, Title, CounterValue } = require('layout/ui/search-bar/ui');

	const COUNTER_COLORS = {
		8: AppTheme.colors.accentMainSuccess, // incoming
		20: AppTheme.colors.accentMainAlert, // current
		999: AppTheme.colors.accentBrandBlue,
		default: AppTheme.colors.base5,
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

		/**
		 * @protected
		 * @override
		 * @return {object[]}
		 */
		renderContent()
		{
			const { active, showValue, typeId, title } = this.props;

			return [
				Title({
					text: title,
				}),
				showValue && CounterValue({
					color: this.getCounterColor(typeId),
					value: this.getCounterValue(),
				}),
				active && CloseIcon(),
			];
		}

		/**
		 * @protected
		 * @override
		 * @return {string}
		 */
		getSearchButtonBackgroundColor()
		{
			return this.getCounterColor(this.props.typeId);
		}

		/**
		 * @protected
		 * @return {string}
		 */
		getCounterColor(typeId)
		{
			return COUNTER_COLORS[Number(typeId)] ?? COUNTER_COLORS.default;
		}

		/**
		 * @private
		 * @return {string}
		 */
		getCounterValue()
		{
			if (this.props.value > 99)
			{
				return '99+';
			}

			return String(this.props.value);
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
