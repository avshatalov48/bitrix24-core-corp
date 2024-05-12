/**
 * @module bizproc/workflow/timeline/components/step/subtitle
 */

jn.define('bizproc/workflow/timeline/components/step/subtitle', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Type } = require('type');

	const { Moment } = require('utils/date');
	const { shortTime, dayMonth, longDate } = require('utils/date/formats');

	const { FriendlyDate } = require('layout/ui/friendly-date');
	const { SafeImage } = require('layout/ui/safe-image');

	/**
	 * @param {{
	 *		title: ?string,
	 *		testId: ?string,
	 *		timestamp: ?number,
	 *		icon: ?string,
	 * }}
	 */
	function Subtitle({ title, testId, timestamp, icon })
	{
		const momentFromTimestamp = timestamp && Type.isNumber(timestamp) && Moment.createFromTimestamp(timestamp);

		return View(
			{
				style: {
					flexDirection: 'row',
					justifyContent: 'space-between',
					alignContent: 'center',
					marginTop: 4,
					marginBottom: 2,
					paddingBottom: 8,
				},
			},
			title && Text({
				text: title,
				testId,
				style: {
					flexShrink: 1,
					color: AppTheme.colors.base4,
					fontSize: 11,
					fontWeight: '500',
				},
			}),
			momentFromTimestamp && new FriendlyDate({
				moment: momentFromTimestamp,
				timeSeparator: ' ',
				defaultFormat: (moment) => {
					const day = moment.format(momentFromTimestamp.inThisYear ? dayMonth() : longDate());
					const time = moment.format(shortTime());

					return `${day} ${time}`;
				},
				showTime: true,
				useTimeAgo: false,
				style: {
					color: AppTheme.colors.base4,
					fontSize: 12,
					fontWeight: '500',
				},
			}),
			icon && SafeImage({
				style: { width: 18, height: 18 },
				resizeMode: 'contain',
				placeholder: { content: icon },
			}),
		);
	}

	module.exports = { Subtitle };
});
