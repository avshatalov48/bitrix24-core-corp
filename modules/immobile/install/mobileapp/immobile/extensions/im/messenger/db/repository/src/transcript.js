/**
 * @module im/messenger/db/repository/transcript
 */
jn.define('im/messenger/db/repository/transcript', (require, exports, module) => {
	const { Type } = require('type');

	const { TranscriptTable } = require('im/messenger/db/table/transcript');

	/**
	 * @class TranscriptRepository
	 */
	class TranscriptRepository
	{
		constructor()
		{
			this.transcriptTable = new TranscriptTable();
		}

		/**
		 *
		 * @param {ChatId} chatId
		 * @return {Promise<Array<TranscriptModelState> | null>}
		 */
		async getByChatId(chatId)
		{
			const transcriptRows = await this.transcriptTable.getList({
				filter: {
					chatId,
				},
				limit: 50,
			});

			if (!Type.isArrayFilled(transcriptRows.items))
			{
				return [];
			}

			return transcriptRows.items;
		}

		/**
		 *
		 * @param {FileId} fileId
		 * @return {Promise<TranscriptModelState | null>}
		 */
		async getByFileId(fileId)
		{
			const transcriptRows = await this.transcriptTable.getList({
				filter: {
					fileId,
				},
			});

			if (!Type.isArrayFilled(transcriptRows.items))
			{
				return null;
			}

			return transcriptRows.items[0];
		}

		async saveFromModel(transcriptList)
		{
			const transcriptListToAdd = transcriptList.map((transcript) => this.transcriptTable.validate(transcript));

			return this.transcriptTable.add(transcriptListToAdd, true);
		}

		/**
		 * @param {ChatId} chatId
		 */
		async deleteByChatId(chatId)
		{
			return this.transcriptTable.delete({ chatId });
		}

		/**
		 * @param {FileId} fileId
		 */
		async deleteByFileId(fileId)
		{
			return this.transcriptTable.delete({ fileId });
		}
	}

	module.exports = {
		TranscriptRepository,
	};
});
