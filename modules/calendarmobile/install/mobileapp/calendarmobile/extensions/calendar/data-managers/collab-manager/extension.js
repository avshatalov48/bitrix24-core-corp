/**
 * @module calendar/data-managers/collab-manager
 */
jn.define('calendar/data-managers/collab-manager', (require, exports, module) => {
	const { CollabModel } = require('calendar/model/collab');

	class CollabManager
	{
		constructor()
		{
			this.collabs = [];
		}

		setCollabs(collabInfo)
		{
			collabInfo.forEach((collabRaw) => {
				const collab = new CollabModel(collabRaw);
				this.collabs[collab.getId()] = collab;
			});
		}

		getCollab(id)
		{
			return this.collabs[id] || {};
		}

		getCollabName(id)
		{
			return this.getCollab(id)?.name;
		}

		getCollabChatId(id)
		{
			return this.getCollab(id)?.chatId;
		}
	}

	module.exports = {
		CollabManager: new CollabManager(),
	};
});
