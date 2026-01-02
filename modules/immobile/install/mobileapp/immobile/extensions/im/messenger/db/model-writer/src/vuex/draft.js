/**
 * @module im/messenger/db/model-writer/src/vuex/draft
 */
jn.define('im/messenger/db/model-writer/src/vuex/draft', (require, exports, module) => {
	const { Type } = require('type');
	const { isEmpty } = require('utils/object');
	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');

	class DraftWriter extends Writer
	{
		subscribeEvents()
		{
			this.storeManager
				.on('draftModel/add', this.addRouter)
				.on('draftModel/update', this.addRouter)
				.on('draftModel/delete', this.deleteRouter);
		}

		unsubscribeEvents()
		{
			this.storeManager
				.off('draftModel/add', this.addRouter)
				.off('draftModel/update', this.addRouter)
				.off('draftModel/delete', this.deleteRouter);
		}

		addRouter(mutation)
		{
			const data = mutation?.payload?.data || {};

			if (this.checkIsValidMutation(mutation) === false || isEmpty(data))
			{
				return;
			}

			this.repository.draft.saveFromModel(data);
		}

		deleteRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false || isEmpty(mutation))
			{
				return;
			}

			const { dialogId } = mutation?.payload?.data || {};

			if (Type.isNumber(dialogId) || Type.isStringFilled(dialogId))
			{
				this.repository.draft.deleteById(dialogId);
			}
		}
	}

	module.exports = {
		DraftWriter,
	};
});
