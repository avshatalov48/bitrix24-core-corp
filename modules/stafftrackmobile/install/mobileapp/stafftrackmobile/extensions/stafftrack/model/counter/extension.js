/**
 * @module stafftrack/model/counter
 */
jn.define('stafftrack/model/counter', (require, exports, module) => {
	const { MuteEnum } = require('stafftrack/model/counter/mute');

	/**
	 * @class CounterModel
	 */
	class CounterModel
	{
		constructor(props)
		{
			this.id = BX.prop.getNumber(props, 'id', 0);
			this.userId = BX.prop.getNumber(props, 'userId', 0);
			this.muteStatus = BX.prop.getNumber(props, 'muteStatus', MuteEnum.DISABLED.getValue());

			const muteUntil = BX.prop.getString(props, 'muteUntil', '');
			this.muteUntil = muteUntil === '' ? new Date() : new Date(muteUntil);
		}

		getId()
		{
			return this.id;
		}

		isMuted()
		{
			const currentDate = new Date();

			return this.muteStatus === MuteEnum.PERMANENT.getValue()
				|| (
					this.muteStatus === MuteEnum.TEMPORALLY.getValue()
					&& this.muteUntil.getTime() > currentDate.getTime()
				)
			;
		}
	}

	module.exports = {
		CounterModel,
		MuteEnum,
	};
});
