'use strict';

(function()
{
	BX.DoNothing = function()
	{};

	class BitrixCall
	{
		constructor(config)
		{
			this.eventEmitter = new JNEventEmitter();
		}

		_onPullEvent(command, params, extra)
		{
			const handlers = {
				'Call::usersInvited': this._onPullEventUsersInvited.bind(this),
			};

			if (handlers[command])
			{
				this.log(`Signaling: ${command}; Parameters: ${JSON.stringify(params)}`);
				handlers[command].call(this, params);
			}
		}

		_onPullEventUsersInvited(params)
		{
			this.log('__onPullEventUsersInvited', params);
			const users = params.users;

			this.addInvitedUsers(users);
		}

		/**
		 * Adds users, invited by you or someone else
		 * @param {Number[]} users
		 */
		addInvitedUsers(users)
		{}

		log()
		{
			const text = CallUtil.getLogMessage.apply(CallUtil, arguments);
			if (console && callEngine.debugFlag)
			{
				const a = [`Call log [${CallUtil.getTimeForLog()}]: `];
				console.log.apply(this, a.concat(Array.prototype.slice.call(arguments)));
			}

			if (this.logger)
			{
				this.logger.log(text);
			}
		}
	}

	window.BitrixCall = BitrixCall;
})
();
