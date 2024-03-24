/**
 * @module crm/timeline/item/ui/styles
 */
jn.define('crm/timeline/item/ui/styles', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { stringify } = require('utils/string');

	const TimelineItemBackground = {
		PINNED: AppTheme.colors.accentSoftOrange3,
		SCHEDULED: AppTheme.colors.accentSoftOrange3,
		DEFAULT: AppTheme.colors.bgContentPrimary,

		/**
		 * @param {TimelineItemModel} model
		 */
		getByModel(model)
		{
			if (model.isPinned)
			{
				return this.PINNED;
			}

			if (model.isScheduled)
			{
				return this.SCHEDULED;
			}

			return this.DEFAULT;
		},
	};

	const TimelineFontColor = {
		GREEN: AppTheme.colors.accentSoftElementGreen1,
		BASE_50: AppTheme.colors.base4,
		BASE_70: AppTheme.colors.base3,
		BASE_90: AppTheme.colors.base2,

		/**
		 * @param {string} code
		 * @param {string|null} defaultValue
		 * @return {string}
		 */
		get(code, defaultValue = null)
		{
			code = stringify(code).toUpperCase();

			return this[code] || defaultValue || this.BASE_90;
		},
	};

	const TimelineFontWeight = {
		NORMAL: '400',
		MEDIUM: '450',
		BOLD: '500',

		get(code, defaultValue = null)
		{
			code = stringify(code).toUpperCase();

			return this[code] || defaultValue || this.NORMAL;
		},
	};

	const TimelineFontSize = {
		XS: 11,
		SM: 13,
		MD: 14,

		/**
		 * @param {string} code
		 * @param {number|null} defaultValue
		 * @return {number}
		 */
		get(code, defaultValue = null)
		{
			code = stringify(code).toUpperCase();

			return this[code] || defaultValue || this.MD;
		},
	};

	const TimelineScope = {
		MOBILE: 'mobile',
		WEB: 'web',
	};

	const TimelineButtonState = {
		HIDDEN: 'hidden',
		DISABLED: 'disabled',
		LOADING: 'loading',
		DEFAULT: 'default',
	};

	const TimelineButtonType = {
		PRIMARY: 'primary',
		SECONDARY: 'secondary',
	};

	const toNumber = (val) => (val === undefined ? 0 : Number(val));
	const skipHidden = (state) => state !== TimelineButtonState.HIDDEN;
	const isScopeMobile = (scope) => scope === undefined || scope === TimelineScope.MOBILE;

	const TimelineButtonVisibilityFilter = ({ state, scope, hideIfReadonly }, isReadonly) => {
		return isScopeMobile(scope) && skipHidden(state) && !(hideIfReadonly && isReadonly);
	};

	const TimelineButtonSorter = (a, b) => toNumber(a.sort) - toNumber(b.sort);

	module.exports = {
		TimelineItemBackground,
		TimelineFontSize,
		TimelineFontWeight,
		TimelineFontColor,
		TimelineButtonType,
		TimelineScope,
		TimelineButtonState,
		TimelineButtonVisibilityFilter,
		TimelineButtonSorter,
		isScopeMobile,
	};
});
