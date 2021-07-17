import {Loc} from 'main.core';
export default class Utils {
	static formatInterval(timestamp: Number)
	{
		const item = {
			DAY: Math.floor(timestamp / 86400),
			HOUR: Math.floor(timestamp % 86400 / 3600),
			MINUTE: Math.floor(timestamp % 86400 % 3600 / 60),
			SECOND: timestamp % 86400 % 3600 % 60,
		};
		const result = [];
		for (let ii in item)
		{
			if (item[ii] > 0)
			{
				result.push([
					item[ii],
					Loc.getMessage(['INTERVAL', ii, (item[ii] === 1 ? 'SINGLE' : 'PLURAL')].join('_'))
				].join(' '));
			}
		}
		if (result.length <= 0)
		{
			result.push([
				'0',
				Loc.getMessage(['INTERVAL_SECOND_SINGLE'].join('_'))
			].join(' '));
		}
		return result.join(' ');
	}
}