jn.define('call:followup/util', (require, exports, module) => {

	function getScoreColor(value)
	{
		if (value < 25)
		{
			return '#FF5752';
		}

		if (value < 50)
		{
			return '#FAA72C';
		}

		return '#1BCE7B';
	}

	function getTimeInSeconds(timestamp)
	{
		const [seconds, minutes, hours] = timestamp.split(':').map(Number).reverse();

		return hours === undefined
			? minutes * 60 + seconds
			: hours * 3600 + minutes * 60 + seconds;
	}

	function formatTimeRange(start, end)
	{
		const pad = (num) => num.toString().padStart(2, '0');

		const startDate = new Date(start);
		const endDate = new Date(end);

		const startHours = pad(startDate.getHours());
		const startMinutes = pad(startDate.getMinutes());

		const endHours = pad(endDate.getHours());
		const endMinutes = pad(endDate.getMinutes());

		return `${startHours}:${startMinutes} - ${endHours}:${endMinutes}`;
	}

	function getFullDuration(start, end)
	{
		const startDate = new Date(start);
		const endDate = new Date(end);
		const diffMs = endDate - startDate;

		const totalSeconds = Math.floor(diffMs / 1000);
		const hours = Math.floor(totalSeconds / 3600);
		const minutes = Math.floor((totalSeconds % 3600) / 60);
		const seconds = totalSeconds % 60;

		const parts = [];
		if (hours > 0) parts.push(`${hours} ${BX.message('MOBILE_HOUR')}`);
		if (minutes > 0 || hours > 0) parts.push(`${minutes} ${BX.message('MOBILE_MIN')}`);
		parts.push(`${seconds} ${BX.message('MOBILE_SEC')}`);

		return parts.join(' ');
	}

	function formatDateForFollowup(dateStr)
	{
		const date = new Date(dateStr);
		const day = date.getDate();
		const monthIndex = date.getMonth() + 1;
		const year = date.getFullYear();

		const monthName = BX.message(`MOBILE_MONTH_${monthIndex}`);
		return `${day} ${monthName}, ${year}`;
	};

	module.exports = {
		getScoreColor,
		getTimeInSeconds,
		formatTimeRange,
		getFullDuration,
		formatDateForFollowup,
	};
});
