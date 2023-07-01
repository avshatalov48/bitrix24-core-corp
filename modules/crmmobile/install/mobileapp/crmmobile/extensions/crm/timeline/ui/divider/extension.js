/**
 * @module crm/timeline/ui/divider
 */
jn.define('crm/timeline/ui/divider', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Moment } = require('utils/date');
	const { dayMonth, longDate } = require('utils/date/formats');

	function DateDivider({ moment = null, showLine = true, onLayout })
	{
		if (moment === null)
		{
			moment = new Moment();
		}

		const color = moment.isToday ? '#2fc6f6' : '#dfe0e3';
		const textColor = moment.isToday ? '#ffffff' : '#6e7273';
		const text = (() => {
			switch (true)
			{
				case moment.isToday: return Loc.getMessage('CRM_TIMELINE_HISTORY_TODAY');
				case moment.isYesterday: return Loc.getMessage('CRM_TIMELINE_HISTORY_YESTERDAY');
				case moment.inThisYear: return moment.format(dayMonth());
				default: return moment.format(longDate());
			}
		})();

		return Divider({
			color,
			text,
			textColor,
			showLine,
			onLayout,
		});
	}

	function Divider({ text, color, textColor = '#ffffff', counter = {}, showLine = true, onLayout })
	{
		return View(
			{
				onLayout,
				style: {
					flexDirection: 'row',
					justifyContent: 'center',
					marginBottom: 16,
				},
			},
			showLine && Line(),
			Badge({
				color,
				text,
				textColor,
				counter,
			}),
		);
	}

	function Line()
	{
		return View(
			{
				style: {
					height: 1,
					width: '100%',
					backgroundColor: '#DFE0E3',
					position: 'absolute',
					top: 10,
				},
			},
		);
	}

	function Badge({ color, text, textColor, counter = {} })
	{
		return View(
			{
				style: {
					backgroundColor: color,
					borderRadius: 100,
					paddingHorizontal: 18,
					height: 21,
					flexDirection: 'row',
					justifyContent: 'center',
				},
			},
			Text({
				text,
				style: {
					color: textColor,
					fontSize: 11,
					fontWeight: '700',
				},
			}),
			Counter(counter),
		);
	}

	function Counter({ value, backgroundColor, borderColor })
	{
		value = parseInt(value, 10);
		if (!value)
		{
			return null;
		}

		backgroundColor = backgroundColor || '#f0371b';
		borderColor = borderColor || backgroundColor;

		return View(
			{
				style: {
					backgroundColor,
					borderColor,
					borderWidth: 1,
					borderRadius: 100,
					paddingHorizontal: 7,
					height: 16,
					flexDirection: 'column',
					justifyContent: 'center',
					marginLeft: 4,
					marginTop: 4,
				},
			},
			Text({
				text: String(value),
				style: {
					color: '#ffffff',
					fontSize: 10,
					fontWeight: 'bold',
				},
			}),
		);
	}

	module.exports = { Divider, DateDivider };
});
