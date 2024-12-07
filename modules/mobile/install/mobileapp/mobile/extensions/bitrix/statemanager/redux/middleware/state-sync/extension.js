/*
Copyright (c) 2018 MU AOHUA

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

/**
 * @module statemanager/redux/middleware/state-sync
 */
jn.define('statemanager/redux/middleware/state-sync', (require, exports, module) => {
	const { StateCache } = require('statemanager/redux/state-cache');
	const { guid } = require('utils/guid');
	const { Logger, LogType } = require('utils/logger');

	const logger = new Logger([LogType.ERROR]);

	let lastUuid = 0;

	const defaultConfig = {
		predicate: null,
		blacklist: [],
		whitelist: [],
	};

	// generate current window unique id
	const WINDOW_STATE_SYNC_ID = guid();

	function generateUuidForAction(action)
	{
		const { meta = {} } = action;

		return {
			...action,
			meta: {
				...meta,
				$isSync: false,
				$wuid: WINDOW_STATE_SYNC_ID,
				$uuid: guid(),
			},
		};
	}

	function isActionAllowed({ predicate, blacklist, whitelist })
	{
		let allowed = (stampedAction) => true;

		if (predicate && typeof predicate === 'function')
		{
			allowed = predicate;
		}
		else if (Array.isArray(blacklist) && blacklist.length > 0)
		{
			allowed = (stampedAction) => !blacklist.includes(stampedAction.type);
		}
		else if (Array.isArray(whitelist) && whitelist.length > 0)
		{
			allowed = (stampedAction) => whitelist.includes(stampedAction.type);
		}

		return allowed;
	}

	const REDUX_STATE_SYNC_EVENT_NAME = 'redux/state-sync/post-message';

	function BroadcastChannel({ dispatch, allowed })
	{
		BX.addCustomEvent(REDUX_STATE_SYNC_EVENT_NAME, (message) => {
			logger.info('BroadcastChannel::onMessage', message);
			this.handleOnMessage(message);
		});

		this.postMessage = (message) => {
			logger.info('BroadcastChannel::postMessage', message);
			BX.postComponentEvent(REDUX_STATE_SYNC_EVENT_NAME, [message]);
		};

		this.handleOnMessage = (stampedAction) => {
			const { meta = {} } = stampedAction;
			// ignore if this action is triggered by this window
			if (meta.$wuid === WINDOW_STATE_SYNC_ID)
			{
				return;
			}

			// ignore other values that is not allowed
			if (meta.$uuid && meta.$uuid !== lastUuid && allowed(stampedAction))
			{
				lastUuid = meta.$uuid;

				dispatch({
					...stampedAction,
					meta: {
						...meta,
						$isSync: true,
					},
				});
			}
		};
	}

	const createStateSyncMiddleware = (config = defaultConfig) => {
		const allowed = isActionAllowed(config);
		let channel = null;

		return ({ dispatch, getState }) => (next) => (action) => {
			try
			{
				if (!channel)
				{
					channel = new BroadcastChannel({ dispatch, allowed });
				}

				if (action.type !== INIT_BROADCAST_CHANNEL)
				{
					const stampedAction = action.meta?.$uuid ? action : generateUuidForAction(action);
					// post messages
					if (!stampedAction.meta?.$isSync)
					{
						lastUuid = stampedAction.meta.$uuid;

						if (allowed(stampedAction))
						{
							channel.postMessage(stampedAction);
						}
					}

					const nextResult = next(stampedAction);

					if (!stampedAction.meta?.$isSync)
					{
						StateCache.setState(getState());
					}

					return nextResult;
				}
			}
			catch (error)
			{
				logger.error('createStateSyncMiddleware error', error);
			}

			return next(action);
		};
	};

	const INIT_BROADCAST_CHANNEL = '&_INIT_BROADCAST_CHANNEL';

	const initBroadcastChannel = ({ dispatch }) => {
		dispatch({ type: INIT_BROADCAST_CHANNEL });
	};

	module.exports = {
		WINDOW_STATE_SYNC_ID,
		createStateSyncMiddleware,
		INIT_BROADCAST_CHANNEL,
		initBroadcastChannel,
	};
});
