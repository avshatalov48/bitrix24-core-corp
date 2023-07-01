/**
 * @module crm/timeline/item/ui/body/blocks/player-alert
 */
jn.define('crm/timeline/item/ui/body/blocks/player-alert', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { stringify } = require('utils/string');

	/**
	 * @class TimelineItemBodyPlayerAlertBlock
	 */
	class TimelineItemBodyPlayerAlertBlock extends TimelineItemBodyBlock
	{
		render()
		{
			const icon = this.props.icon && ThemeIcons[this.props.icon];
			const { color, backgroundColor } = ThemeColors.get(this.props.color);

			return View(
				{
					style: {
						backgroundColor,
						flexDirection: 'row',
						justifyContent: 'center',
						alignItems: 'center',
						paddingHorizontal: 12,
						paddingVertical: 8,
						borderRadius: 32,
					},
				},
				icon && Image({
					svg: { content: icon.content },
					style: {
						width: icon.width,
						height: icon.height,
						marginRight: 8,
						marginLeft: 8,
					},
				}),
				View(
					{
						style: {
							flexDirection: 'row',
							flexWrap: 'wrap',
						},
					},
					...this.renderInnerContent(color),
				),
			);
		}

		renderInnerContent(color)
		{
			const blocks = BX.prop.getObject(this.props, 'blocks', {});
			return Object.values(blocks).map(({ rendererName, properties }) => this.factory.make(
				rendererName,
				{
					...properties,
					color,
				},
			));
		}
	}

	const ThemeColors = {
		'ui-alert-danger': {
			backgroundColor: '#ffdcdb',
			color: '#c21b16',
		},
		'ui-alert-default': {
			backgroundColor: '#A8ADB4',
			color: '#6A737F',
		},

		get(code)
		{
			code = stringify(code);
			return this[code] || this['ui-alert-default'];
		},
	};

	const ThemeIcons = {
		'ui-alert-icon-danger': {
			content: '<svg width="14" height="12" viewBox="0 0 14 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M13.0118 9.93375L7.85248 1.34049C7.45496 0.680773 6.50767 0.680773 6.1186 1.34049L0.959266 9.93375C0.553286 10.6104 1.04385 11.4646 1.83043 11.4646H12.1491C12.9272 11.4646 13.4178 10.6104 13.0118 9.93375ZM6.24547 4.3346C6.24547 3.95399 6.54996 3.64951 6.93056 3.64951H7.0236C7.40421 3.64951 7.70869 3.95399 7.70869 4.3346V6.89735C7.70869 7.27796 7.40421 7.58244 7.0236 7.58244H6.93056C6.54996 7.58244 6.24547 7.27796 6.24547 6.89735V4.3346ZM7.84402 9.30786C7.84402 9.78151 7.45496 10.1706 6.98131 10.1706C6.50767 10.1706 6.1186 9.78151 6.1186 9.30786C6.1186 8.83422 6.50767 8.44515 6.98131 8.44515C7.45496 8.44515 7.84402 8.83422 7.84402 9.30786Z" fill="#ee0101"/></svg>',
			width: 14,
			height: 12,
		},
		'ui-alert-icon-forbidden': {
			content: '<svg width="14" height="13" viewBox="0 0 14 13" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.75154 9.71218C5.40717 10.1141 6.17841 10.3458 7.00377 10.3458C9.38722 10.3458 11.3194 8.41359 11.3194 6.03013C11.3194 5.20478 11.0877 4.43354 10.6858 3.77791L4.75154 9.71218ZM3.32172 8.28236L9.25599 2.34808C8.60036 1.94619 7.82912 1.7145 7.00376 1.7145C4.62031 1.7145 2.68813 3.64667 2.68813 6.03013C2.68813 6.85549 2.91982 7.62673 3.32172 8.28236ZM7.00377 12.0417C3.68366 12.0417 0.992188 9.35023 0.992188 6.03013C0.992188 2.71003 3.68366 0.0185547 7.00377 0.0185547C10.3239 0.0185547 13.0153 2.71003 13.0153 6.03013C13.0153 9.35023 10.3239 12.0417 7.00377 12.0417Z" fill="#828B95"/></svg>',
			width: 14,
			height: 14,
		},
	};

	module.exports = { TimelineItemBodyPlayerAlertBlock };
});
