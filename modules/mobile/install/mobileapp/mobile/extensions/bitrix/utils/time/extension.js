/**
 * @module utils/time
 */
jn.define('utils/time', (require, exports, module) => {
	/**
	 * @param {number} ms
	 * @returns {string}
	 */
	function formatHHMMSS(ms)
	{
		const totalSec = Math.floor(ms / 1000);
		const h = Math.floor(totalSec / 3600);
		const m = Math.floor((totalSec % 3600) / 60);
		const s = totalSec % 60;
		const pad = (num) => String(num).padStart(2, '0');

		return `${pad(h)}:${pad(m)}:${pad(s)}`;
	}

	const toMs = (v) => {
		if (v === null || v === undefined)
		{
			return null;
		}
		const num = (typeof v === 'number') ? v : (typeof v === 'string' ? Number(v.trim()) : NaN);

		if (Number.isNaN(num))
		{
			return null;
		}

		return (num > 1e12 ? num : num * 1000);
	};

	const parseDateToSec = (v) => {
		if (!v)
		{
			return null;
		}

		const ts = Date.parse(v);

		return Number.isFinite(ts) ? Math.floor(ts / 1000) : null;
	};

	const parseHmsToSec = (v) => {
		if (v === null || v === undefined)
		{
			return null;
		}

		if (typeof v === 'number' && Number.isFinite(v))
		{
			return v;
		}

		if (typeof v !== 'string')
		{
			return null;
		}

		const m = v.match(/^(\d{1,2}):(\d{2}):(\d{2})$/);
		if (!m)
		{
			const num = Number(v);

			return Number.isFinite(num) ? num : null;
		}
		const h = Number(m[1]);
		const mi = Number(m[2]);
		const s = Number(m[3]);

		return (h * 3600) + (mi * 60) + s;
	};

	module.exports = { formatHHMMSS, toMs, parseHmsToSec, parseDateToSec };
});
