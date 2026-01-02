/**
 * @module disk/dialogs/external-link/death-time-util
 */
jn.define('disk/dialogs/external-link/death-time-util', (require, exports, module) => {

	const { Loc } = require('loc');

	const ONE_DAY_IN_SECONDS = 86400;
	const ONE_HOUR_IN_SECONDS = 3600;
	const ONE_MINUTE_IN_SECONDS = 60;

	const DeathTimeType = {
		DAYS: 'days',
		HOURS: 'hours',
		MINUTES: 'minutes',
	};

	const DeathTimeByType = {
		[DeathTimeType.DAYS]: ONE_DAY_IN_SECONDS,
		[DeathTimeType.HOURS]: ONE_HOUR_IN_SECONDS,
		[DeathTimeType.MINUTES]: ONE_MINUTE_IN_SECONDS,
	};

	function getDeathTimeTypeByDeathTime(deathTime)
	{
		if (deathTime === null)
		{
			return DeathTimeType.DAYS;
		}

		for (let i = 0; i < Object.values(DeathTimeType).length; i++)
		{
			const durationType = Object.values(DeathTimeType)[i];
			const durationInDurationType = getMultiplier(durationType);

			if (deathTime % durationInDurationType === 0)
			{
				return durationType;
			}
		}

		return DeathTimeType.MINUTES;
	}

	function getDeathTimeApproximateType(deathTime)
	{
		if (deathTime === null)
		{
			return DeathTimeType.MINUTES;
		}

		if (deathTime >= ONE_DAY_IN_SECONDS)
		{
			return DeathTimeType.DAYS;
		}

		if (deathTime >= ONE_HOUR_IN_SECONDS)
		{
			return DeathTimeType.HOURS;
		}

		return DeathTimeType.MINUTES;
	}

	function getMultiplier(deathTimeType)
	{
		switch (deathTimeType)
		{
			case DeathTimeType.DAYS:
				return ONE_DAY_IN_SECONDS;

			case DeathTimeType.HOURS:
				return ONE_HOUR_IN_SECONDS;

			case DeathTimeType.MINUTES:
				return ONE_MINUTE_IN_SECONDS;

			default:
				return 1;
		}
	}

	function getDeathTimeValueByType(deathTime, deathTimeType)
	{
		if (deathTime === null)
		{
			return 0;
		}

		return Math.floor(deathTime / getMultiplier(deathTimeType));
	}

	function deathTimeTypeNamePlural(count)
	{
		return {
			days: Loc.getMessagePlural('M_DISK_SECURITY_SETTING_DIALOG_DEATH_TIME_TYPE_RULE_DAY', count),
			hours: Loc.getMessagePlural('M_DISK_SECURITY_SETTING_DIALOG_DEATH_TIME_TYPE_RULE_HOUR', count),
			minutes: Loc.getMessagePlural('M_DISK_SECURITY_SETTING_DIALOG_DEATH_TIME_TYPE_RULE_MINUTE', count),
		};
	}

	function getDeathTimeMessage(deathTime)
	{
		if (!deathTime)
		{
			return Loc.getMessage('M_DISK_SECURITY_SETTING_DIALOG_DEATH_TIME_TYPE_FOREVER');
		}

		const type = getDeathTimeApproximateType(deathTime);
		const value = getDeathTimeValueByType(deathTime, type);

		return Loc.getMessage('M_DISK_SECURITY_SETTING_DIALOG_DEATH_TIME_MESSAGE', {
			'#DURATION#': value,
			'#TIME_UNIT#': deathTimeTypeNamePlural(value)[type],
		});
	}

	module.exports = {
		DeathTimeByType,
		DeathTimeType,
		getDeathTimeValueByType,
		getDeathTimeTypeByDeathTime,
		deathTimeTypeNamePlural,
		getDeathTimeMessage,
	};
});
