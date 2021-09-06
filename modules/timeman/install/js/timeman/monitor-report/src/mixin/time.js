export const Time = {
	computed:
	{
		fullTime()
		{
			return this.workingTime + this.personalTime;
		},
		workingTime()
		{
			return this.$store.getters['monitor/getWorkingEntities'].reduce((sum, entry) => sum + entry.time, 0);
		},
		personalTime()
		{
			return this.$store.getters['monitor/getPersonalEntities'].reduce((sum, entry) => sum + entry.time, 0);
		},
		inactiveTime()
		{
			return 86400 - (this.workingTime + this.personalTime);
		},
	},
	methods:
	{
		formatSeconds(seconds)
		{
			if (seconds < 1)
			{
				return 0 + ' ' + this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIXIN_TIME_MINUTES_SHORT');
			}
			else if (seconds < 60)
			{
				return this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIXIN_TIME_LESS_THAN_MINUTE');
			}

			let hours = Math.floor(seconds / 3600);
			let minutes = Math.round(seconds / 60 % 60);

			if (minutes === 60)
			{
				hours += 1;
				minutes = 0;
			}

			if (hours > 0)
			{
				hours = hours + ' ' +  this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIXIN_TIME_HOUR_SHORT');

				if (minutes > 0)
				{
					minutes = minutes + ' ' + this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIXIN_TIME_MINUTES_SHORT');

					return hours + ' ' + minutes;
				}

				return hours;
			}

			return minutes + ' ' + this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIXIN_TIME_MINUTES_SHORT');
		},
		calculateEntryTime(entry)
		{
			const time = entry.time
				.map(interval => {
					const finish = interval.finish ? new Date(interval.finish) : new Date();

					return finish - new Date(interval.start);
				})
				.reduce((sum, interval) => sum + interval, 0);

			return Math.round(time / 1000);
		},
		getEntityByPrivateCode(privateCode)
		{
			return this.monitor.entity.find(entity => entity.privateCode === privateCode);
		}
	}
};