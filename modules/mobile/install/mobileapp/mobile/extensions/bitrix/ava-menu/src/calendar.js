/**
 * @module ava-menu/calendar
 */
jn.define('ava-menu/calendar', (require, exports, module) => {
	let Entry = null;
	try
	{
		Entry = require('calendar/entry').Entry;
	}
	catch (e)
	{
		console.warn(e);
	}

	class Calendar
	{
		static open(customData)
		{
			if (Entry)
			{
				Entry.openUserCalendarView({
					userId: env.userId,
					title: customData.title,
				});

				return true;
			}

			return false;
		}
	}

	module.exports = { Calendar };
});
