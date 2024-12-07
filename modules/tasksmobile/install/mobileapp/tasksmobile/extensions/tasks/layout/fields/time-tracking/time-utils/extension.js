/**
 * @module tasks/layout/fields/time-tracking/time-utils
 */
jn.define('tasks/layout/fields/time-tracking/time-utils', (require, exports, module) => {
	const toHours = (seconds = 0) => Math.floor(seconds / 3600);

	const toMinutes = (seconds = 0) => {
		const secondsLeft = Math.floor(seconds % 3600);

		return Math.floor(secondsLeft / 60);
	};

	const toSeconds = (seconds = 0) => Math.floor(seconds % 60);

	const sumSeconds = (hours = 0, minutes = 0, seconds = 0) => (hours * 3600) + (minutes * 60) + seconds;

	const toTimer = (seconds = 0) => {
		const hours = toHours(seconds).toString().padStart(2, '0');
		const minutes = toMinutes(seconds).toString().padStart(2, '0');
		const sec = toSeconds(seconds).toString().padStart(2, '0');

		return `${hours}:${minutes}:${sec}`;
	};

	module.exports = {
		toHours,
		toMinutes,
		toSeconds,
		toTimer,
		sumSeconds,
	};
});
