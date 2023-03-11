/**
 * @module layout/pure-component/logger
 */
jn.define('layout/pure-component/logger', (require, exports, module) => {
	const { Type } = require('type');
	const { isEqual } = require('utils/object');

	const isAndroid = Application.getPlatform() === 'android';

	const FONT = {
		NAME: 'color: #ffcc00; background: #000; font-weight: bold;',
		CHANGED: 'color: #cc3300; font-weight: bold;',
		NOT_CHANGED: 'color: #339900; font-weight: bold;',
	};

	const log = (name, prevProps, nextProps, prevState, nextState) => {
		const propsChanged = !isEqual(prevProps, nextProps);
		const stateChanged = !isEqual(prevState, nextState);

		// Android console doesn't support groupCollapsed
		const consoleGroup = isAndroid ? console.group : console.groupCollapsed;
		consoleGroup(...getMainMessage(name, propsChanged, stateChanged));

		if (propsChanged)
		{
			showDiffMessage('PROPS: ', prevProps, nextProps);
		}

		if (stateChanged)
		{
			showDiffMessage('STATE: ', prevState, nextState);
		}

		console.groupEnd();
	};

	const getMainMessage = (name, hasPropsChanged, hasStateChanged) => {
		let message = `%c${name}:`;
		const styles = [FONT.NAME];

		if (hasPropsChanged)
		{
			message += '%c props ❌';
			styles.push(FONT.CHANGED);
		}
		else
		{
			message += '%c props ✅';
			styles.push(FONT.NOT_CHANGED);
		}

		if (hasStateChanged)
		{
			message += '%c state ❌';
			styles.push(FONT.CHANGED);
		}
		else
		{
			message += '%c state ✅';
			styles.push(FONT.NOT_CHANGED);
		}

		// Android console doesn't support font styles
		if (isAndroid)
		{
			message = message.replace(/%c/g, '');
		}

		return [message, ...styles];
	};

	const showDiffMessage = (name, prevProps, nextProps) => {
		const diffProps = findDiffRecursive(prevProps, nextProps);
		console.log(...diffProps);
	};

	const findDiffRecursive = (prev, next, path = []) => {
		const diff = [];

		for (const key in next)
		{
			if (next.hasOwnProperty(key))
			{
				const prevValue = prev[key];
				const nextValue = next[key];

				if (!isEqual(prevValue, nextValue))
				{
					if (
						Type.isPlainObject(prevValue) && Type.isPlainObject(nextValue)
						|| Type.isArray(prevValue) && Type.isArray(nextValue)
					)
					{
						diff.push(...findDiffRecursive(prevValue, nextValue, [...path, key]));
					}
					else if (Type.isFunction(prevValue) && Type.isFunction(nextValue))
					{
						diff.push({
							path: [...path, key],
							warning: 'Function changes every render, use .bind(this) in constructor instead (or useCallback() if you have some deps).',
						});
					}
					else
					{
						diff.push({
							path: [...path, key],
							prevValue,
							nextValue,
						});
					}
				}
			}
		}

		return diff;
	};

	module.exports = { log };
});
