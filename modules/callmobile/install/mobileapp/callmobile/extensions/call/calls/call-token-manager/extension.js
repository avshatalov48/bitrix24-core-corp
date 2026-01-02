'use strict';
(function() {
	class TokenManager
	{
		#tokenList;
		#pendingTokenList;
		#userToken;

		constructor()
		{
			this.#tokenList = {};
			this.#pendingTokenList = {};
			this.#userToken = BX.componentParameters.get('userJwt');
		}

		getTokenCached(chatId)
		{
			return this.#tokenList[chatId];
		}

		async getToken(chatId)
		{
			const token = this.#tokenList[chatId];
			const pendingToken = this.#pendingTokenList[chatId];

			if (token)
			{
				return token;
			}
			else if (pendingToken)
			{
				return pendingToken;
			}

			this.#pendingTokenList[chatId] = new Promise((resolve) =>
			{
				this.#loadToken(chatId).then(() =>
				{
					delete this.#pendingTokenList[chatId];
					resolve(this.#tokenList[chatId]);
				});
			});

			return this.#pendingTokenList[chatId];
		}

		setToken(chatId, token)
		{
			this.#tokenList[chatId] = token;
		}

		async getUserToken(chatId)
		{
			const pendingToken = this.#pendingTokenList[chatId];

			if (this.#userToken)
			{
				return this.#userToken;
			}
			else if (pendingToken)
			{
				return pendingToken;
			}

			this.#pendingTokenList[chatId] = new Promise((resolve) =>
			{
				this.#loadToken(chatId).then(() =>
				{
					delete this.#pendingTokenList[chatId];
					resolve(this.#userToken);
				});
			});

			return this.#pendingTokenList[chatId];
		}

		setUserToken(token)
		{
			this.#userToken = token;
		}

		clearTokenList()
		{
			this.#tokenList = {};
			this.#pendingTokenList = {};
			this.#userToken = null;
		}

		async #loadToken(chatId)
		{
			try
			{
				const response = await BX.rest.callMethod('call.Call.getCallToken', { chatId });
				const callToken = response.data()?.callToken;
				const userToken = response.data()?.userToken;

				if (callToken)
				{
					this.setToken(chatId, callToken);
				}

				if (userToken)
				{
					this.setUserToken(userToken);
				}
			}
			catch (error)
			{
				console.error('Error during call token retrieving', error);
			}
		}
	}

	window.TokenManager = TokenManager;
})();