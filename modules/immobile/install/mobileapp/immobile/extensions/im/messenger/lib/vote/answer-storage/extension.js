/**
 * @module im/messenger/lib/vote/answer-storage
 */
jn.define('im/messenger/lib/vote/answer-storage', (require, exports, module) => {
	const { StorageCache } = require('storage-cache');

	/**
	 * @class VoteAnswerStorage
	 */
	class VoteAnswerStorage
	{
		constructor()
		{
			this.storage = new StorageCache('immobileVote', 'answerSelection');
		}

		getBallot(messageId)
		{
			return this.storage.get()[messageId] || {};
		}

		getSelectedAnswers(messageId)
		{
			return Object.values(this.storage.get()[messageId] || {}).flat();
		}

		onAnswerTap(messageId, questionId, answerId)
		{
			const cache = this.storage.get();
			const messageCache = cache[messageId] || {};
			const selectedAnswers = messageCache[questionId] || [];

			if (selectedAnswers.includes(answerId))
			{
				this.storage.set({
					...cache,
					[messageId]: {
						...messageCache,
						[questionId]: selectedAnswers.filter((selectedAnswerId) => selectedAnswerId !== answerId),
					},
				});
			}
			else
			{
				this.storage.set({
					...cache,
					[messageId]: {
						...messageCache,
						[questionId]: [...selectedAnswers, answerId],
					},
				});
			}
		}

		clearByMessageId(messageId)
		{
			const { [messageId]: _, ...cache } = this.storage.get();

			this.storage.set(cache);
		}

		clear()
		{
			this.storage.set({});
		}
	}

	module.exports = { VoteAnswerStorage };
});
