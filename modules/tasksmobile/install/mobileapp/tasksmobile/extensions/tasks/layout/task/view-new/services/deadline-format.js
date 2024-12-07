/**
 * @module tasks/layout/task/view-new/services/deadline-format
 */
jn.define('tasks/layout/task/view-new/services/deadline-format', (require, exports, module) => {
	const { DynamicDateFormatter } = require('utils/date');
	const { longDate, shortTime, dayMonth } = require('utils/date/formats');
	const { Loc } = require('loc');

	const dayOfWeek = () => 'EEEE';

	const config = {
		[DynamicDateFormatter.scope.PAST]: {
			[DynamicDateFormatter.periods.YESTERDAY]: (moment) => {
				return Loc.getMessage('M_TASK_DETAILS_DEADLINE_YESTERDAY', {
					'#TIME#': moment.format(shortTime()),
				});
			},
		},
		[DynamicDateFormatter.scope.FUTURE]: {
			120: () => Loc.getMessage('M_TASK_DETAILS_DEADLINE_IN_ONE_MINUTE'),
			1800: (moment) => {
				const remainSeconds = moment.timestamp - moment.getNow().timestamp;
				const remainMinutes = Math.ceil(remainSeconds / 60);

				return Loc.getMessagePlural('M_TASK_DETAILS_DEADLINE_IN_N_MINUTES', remainMinutes, {
					'#MINUTES#': remainMinutes,
				});
			},
			[DynamicDateFormatter.periods.TOMORROW]: (moment) => {
				return Loc.getMessage('M_TASK_DETAILS_DEADLINE_TOMORROW', {
					'#TIME#': moment.format(shortTime()),
				});
			},
			[DynamicDateFormatter.periods.WEEK]: (moment) => {
				return Loc.getMessage('M_TASK_DETAILS_DEADLINE_DATE_WITH_TIME', {
					'#DATE#': moment.format(dayOfWeek()),
					'#TIME#': moment.format(shortTime()),
				});
			},
		},
		[DynamicDateFormatter.periods.DAY]: (moment) => {
			return Loc.getMessage('M_TASK_DETAILS_DEADLINE_TODAY', {
				'#TIME#': moment.format(shortTime()),
			});
		},
		[DynamicDateFormatter.periods.YEAR]: (moment) => {
			return Loc.getMessage('M_TASK_DETAILS_DEADLINE_DATE_WITH_TIME', {
				'#DATE#': moment.format(dayMonth()),
				'#TIME#': moment.format(shortTime()),
			});
		},
	};

	const defaultFormat = (moment) => {
		return Loc.getMessage('M_TASK_DETAILS_DEADLINE_DATE_WITH_TIME', {
			'#DATE#': moment.format(longDate()),
			'#TIME#': moment.format(shortTime()),
		});
	};

	module.exports = {
		Formatter: new DynamicDateFormatter({ config, defaultFormat }),
	};
});
