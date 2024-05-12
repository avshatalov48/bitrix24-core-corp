/**
 * @module bizproc/helper/duration
*/
jn.define('bizproc/helper/duration', (require, exports, module) => {
	const { Duration } = require('utils/date/duration');

	/**
	 * @param {number} timeInSeconds
	 * @return {{s: number, i: number, H: number, d: number, m: number, Y: number}}
	 */
	function roundTimeInSeconds(timeInSeconds)
	{
		const duration = Duration.createFromSeconds(timeInSeconds);

		// 0y 1m 0d 15h 5i 1s -> 0y 1m 1d 15h 5i 1s -> 0y 1m 0d 0h 0i 0s
		// 0y 0m 0d 23h 35i 0s -> 0y 0m 0d 24h 35i 0s -> 0y 0m 1d 0h 0i 0s

		const seconds = duration.getUnitPropertyModByFormat('s');

		let minutes = duration.getUnitPropertyModByFormat('i');
		if ((minutes !== 0 && seconds >= 30) || (minutes === 0 && seconds === 60))
		{
			minutes += 1;
		}

		let hours = duration.getUnitPropertyModByFormat('H');
		if ((hours !== 0 && minutes >= 30) || (hours === 0 && minutes === 60))
		{
			hours += 1;
		}

		let days = duration.getUnitPropertyModByFormat('d');
		if ((days !== 0 && hours >= 12) || (days === 0 && hours === 24))
		{
			days += 1;
		}

		let months = duration.getUnitPropertyModByFormat('m');
		if ((months !== 0 && days >= 15) || (months === 0 && days === 30))
		{
			months += 1;
		}

		let years = duration.getUnitPropertyModByFormat('Y');
		if ((years !== 0 && months >= 6) || (years === 0 && months === 12))
		{
			years += 1;
		}

		const elderRank = findElderRank({
			s: seconds,
			i: minutes,
			H: hours,
			d: days,
			m: months,
			Y: years,
		});

		const roundedTime = { s: 0, i: 0, H: 0, d: 0, m: 0, Y: 0 };
		roundedTime[elderRank.rank] = elderRank.value;

		return roundedTime;
	}

	/**
	 * @param {number} timeInSeconds
	 * @return {{s: number, i: number, H: number, d: number, m: number, Y: number}}
	 */
	function roundUpTimeInSeconds(timeInSeconds)
	{
		const duration = Duration.createFromSeconds(timeInSeconds);

		let years = duration.getUnitPropertyModByFormat('Y');
		let months = duration.getUnitPropertyModByFormat('m');
		let days = duration.getUnitPropertyModByFormat('d');
		let hours = duration.getUnitPropertyModByFormat('H');
		let minutes = duration.getUnitPropertyModByFormat('i');
		const seconds = duration.getUnitPropertyModByFormat('s');

		// 0y 1m 0d 15h 5i 1s -> 0y 2m 0d 16h 6i 1s -> 0y 2m 0d 0h 0i 0s

		if ((years !== 0) && (seconds || minutes || hours || days || months))
		{
			years += 1;
		}

		if ((months !== 0) && (seconds || minutes || hours || days))
		{
			months += 1;
		}

		if ((days !== 0) && (seconds || minutes || hours))
		{
			days += 1;
		}

		if ((hours !== 0) && (seconds || minutes))
		{
			hours += 1;
		}

		if ((minutes !== 0) && (seconds))
		{
			minutes += 1;
		}

		const elderRank = findElderRank({
			s: seconds,
			i: minutes,
			H: hours,
			d: days,
			m: months,
			Y: years,
		});

		const roundedTime = { s: 0, i: 0, H: 0, d: 0, m: 0, Y: 0 };
		roundedTime[elderRank.rank] = elderRank.value;

		return roundedTime;
	}

	/**
	 * @param {number} timeInSeconds
	 * @return {number}
	 */
	function roundSeconds(timeInSeconds)
	{
		const remainder = (timeInSeconds % 60);

		if (remainder < 30)
		{
			return timeInSeconds - remainder;
		}

		return timeInSeconds - remainder + 60;
	}

	/**
	 * @param {{s: number, i: number, H: number, d: number, m: number, Y: number}} roundedTime
	 * @return {string}
	 */
	function formatRoundedTime(roundedTime)
	{
		const elderRank = findElderRank(roundedTime);

		const lengthFormats = {
			Y: Duration.getLengthFormat().YEAR,
			m: Duration.getLengthFormat().MONTH,
			d: Duration.getLengthFormat().DAY,
			H: Duration.getLengthFormat().HOUR,
			i: Duration.getLengthFormat().MINUTE,
			s: Duration.getLengthFormat().SECOND,
		};

		return (new Duration(elderRank.value * lengthFormats[elderRank.rank])).format(elderRank.rank);
	}

	/**
	 * @param {[{s: number, i: number, H: number, d: number, m: number, Y: number}]} times
	 * @returns {number}
	 */
	function calculateSum(times)
	{
		const sum = { s: 0, i: 0, H: 0, d: 0, m: 0, Y: 0 };

		times.forEach((time) => {
			sum.s += time.s;
			sum.i += time.i;
			sum.H += time.H;
			sum.d += time.d;
			sum.m += time.m;
			sum.Y += time.Y;
		});

		const milliseconds = (
			sum.s * Duration.getLengthFormat().SECOND
			+ sum.i * Duration.getLengthFormat().MINUTE
			+ sum.H * Duration.getLengthFormat().HOUR
			+ sum.d * Duration.getLengthFormat().DAY
			+ sum.m * Duration.getLengthFormat().MONTH
			+ sum.Y * Duration.getLengthFormat().YEAR
		);

		return (new Duration(milliseconds)).seconds;
	}

	/**
	 * @param {{s: number, i: number, H: number, d: number, m: number, Y: number}} time
	 * @returns {{rank: string, value: number}}
	 */
	function findElderRank(time)
	{
		if (time.Y !== 0)
		{
			return { rank: 'Y', value: time.Y };
		}

		if (time.m !== 0)
		{
			return { rank: 'm', value: time.m };
		}

		if (time.d !== 0)
		{
			return { rank: 'd', value: time.d };
		}

		if (time.H !== 0)
		{
			return { rank: 'H', value: time.H };
		}

		if (time.i !== 0)
		{
			return { rank: 'i', value: time.i };
		}

		return { rank: 's', value: time.s };
	}

	module.exports = {
		roundTimeInSeconds,
		roundUpTimeInSeconds,
		formatRoundedTime,
		calculateSum,
		findElderRank,
		roundSeconds,
	};
});
