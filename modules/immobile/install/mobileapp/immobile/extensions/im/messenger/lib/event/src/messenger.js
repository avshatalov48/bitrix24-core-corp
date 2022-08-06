/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/event/messenger
 */
jn.define('im/messenger/lib/event/messenger', (require, exports, module) => {

	const { Type } = jn.require('type');
	const { MessengerParams } = jn.require('im/messenger/lib/params');

	class MessengerEvent
	{
		constructor(name, data)
		{
			if (!Type.isStringFilled(name))
			{
				throw new Error('MessengerEvent: ' + name + 'is not a filled string');
			}

			this.name = name;
			this.data = data;

			return this;
		}

		send()
		{
			BX.postComponentEvent(this.name, [this.data], MessengerParams.get('COMPONENT_CODE'));
		}
	}

	module.exports = {
		MessengerEvent,
	};
});
