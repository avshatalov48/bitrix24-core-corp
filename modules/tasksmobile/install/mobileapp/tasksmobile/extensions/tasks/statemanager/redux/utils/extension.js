/**
 * @module tasks/statemanager/redux/utils
 */
jn.define('tasks/statemanager/redux/utils', (require, exports, module) => {
	function stringifyWithKeysSort(input)
	{
		if (input === null)
		{
			return 'null';
		}
		const splitter = ' ';
		let result = '';
		switch (typeof input)
		{
			case 'undefined':
			case 'symbol':
			case 'function':
				result += 'undefined';
				break;
			case 'string':
				result += `"${input}"`;
				break;
			case 'number':
				result += input;
				break;
			case 'bigint':
				result += input;
				break;
			case 'boolean':
				result += input;
				break;
			case 'object':
				if (input instanceof Map
					|| input instanceof Set
					|| input instanceof WeakMap
					|| input instanceof WeakSet)
				{
					result += '{}';
					break;
				}

				if (Array.isArray(input))
				{
					const sorted = input.sort();
					result += '[';
					for (let i = 0; i < sorted.length; i++)
					{
						result += `${stringifyWithKeysSort(sorted[i])}${sorted.length - 1 === i ? '' : ','}`;
						result += sorted.length - 1 === i ? '' : splitter;
					}
					result += ']';
				}
				else
				{
					result += `{${splitter}`;
					let tempResult = '';
					Object.getOwnPropertyNames(input).sort().forEach((key, index, arr) => {
						const childResult = stringifyWithKeysSort(input[key]);
						if (childResult !== 'undefined')
						{
							tempResult += `"${key}":${splitter}${childResult},${splitter}`;
						}
					});
					if (tempResult !== '')
					{
						tempResult = tempResult.slice(0, -1 - splitter.length);
					}
					result += `${tempResult}${splitter}}`;
				}
				break;
			default:
				break;
		}

		return result;
	}

	module.exports = {
		stringifyWithKeysSort,
	};
});
