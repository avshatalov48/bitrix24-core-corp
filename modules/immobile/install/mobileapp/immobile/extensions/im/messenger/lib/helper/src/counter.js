/**
 * @module im/messenger/lib/helper/counter
 */
jn.define('im/messenger/lib/helper/counter', (require, exports, module) => {
	const { Type } = require('type');
	const { DialogType, CounterType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class CounterHelper
	 */
	class CounterHelper
	{
		static get currentUserId()
		{
			return serviceLocator.get('core').getUserId();
		}

		static getCounterTypeByDialogType(dialogType)
		{
			switch (dialogType)
			{
				case DialogType.copilot:
				{
					return CounterType.copilot;
				}

				case DialogType.collab:
				{
					return CounterType.collab;
				}

				case DialogType.lines:
				{
					return CounterType.openline;
				}

				case DialogType.comment:
				{
					return CounterType.comment;
				}

				case DialogType.tasksTask:
				{
					return CounterType.tasksTask;
				}

				default:
				{
					return CounterType.chat;
				}
			}
		}

		static getDisabledByMuteList(muteList)
		{
			if (Type.isPlainObject(muteList))
			{
				return muteList[this.currentUserId] === true;
			}

			if (Type.isArrayFilled(muteList))
			{
				return muteList.includes(this.currentUserId);
			}

			return false;
		}
	}

	module.exports = { CounterHelper };
});
