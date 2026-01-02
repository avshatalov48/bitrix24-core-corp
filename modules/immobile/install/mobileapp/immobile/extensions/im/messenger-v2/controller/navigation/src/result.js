/**
 * @module im/messenger-v2/controller/navigation/result
 */
jn.define('im/messenger-v2/controller/navigation/result', (require, exports, module) => {
	const ErrorCode = {
		incorrectTabId: 'INCORRECT_TAB_ID',
		tabIsNotAvailable: 'TAB_IS_NOT_AVAILABLE',
	};

	/**
	 * @class SelectionTabResult
	 */
	class SelectionTabResult
	{
		/** @type {{message: string, code: string} | null} */
		#error = null;

		static createByIncorrectTabId(tabId)
		{
			return new SelectionTabResult({
				message: `Incorrect tab id: ${tabId}`,
				code: ErrorCode.incorrectTabId,
			});
		}

		static createByTabIsNotAvailable(tabId)
		{
			return new SelectionTabResult({
				message: `Tab is not available: ${tabId}`,
				code: ErrorCode.tabIsNotAvailable,
			});
		}

		/**
		 * @param {{message: string, code: string} | null} error
		 */
		constructor(error = null)
		{
			this.#error = error;
		}

		isSuccess()
		{
			return this.#error === null;
		}

		/**
		 * @return {{message: string, code: string} | null}
		 */
		getError()
		{
			return this.#error;
		}

		/**
		 * @return {boolean}
		 */
		fromIncorrectTabId()
		{
			return this.getError()?.code === ErrorCode.incorrectTabId;
		}

		/**
		 * @return {boolean}
		 */
		fromTabIsNotAvailable()
		{
			return this.getError()?.code === ErrorCode.tabIsNotAvailable;
		}
	}

	module.exports = { SelectionTabResult };
});
