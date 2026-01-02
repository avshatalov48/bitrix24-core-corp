import { Type, Loc, Extension } from 'main.core';

const { callInstalled } = Extension.getSettings('call.core');
class TokenManager
{
	#tokenList;
	#pendingTokenList;
	#queryParams;
	#userToken;

	constructor()
	{
		this.#tokenList = {};
		this.#pendingTokenList = {};
		this.#queryParams = {};

		if (callInstalled)
		{
			this.#userToken = Loc.getMessage('user_jwt');
		}
	}

	setQueryParams(queryParams)
	{
		if (!Type.isPlainObject(queryParams))
		{
			return;
		}
		this.#queryParams = queryParams;
	}

	getTokenCached(chatId): ?string
	{
		return this.#tokenList[chatId];
	}

	async getToken(chatId): Promise<?string>
	{
		const token = this.#tokenList[chatId];
		const pendingToken = this.#pendingTokenList[chatId];

		if (token)
		{
			return token;
		}

		if (pendingToken)
		{
			return pendingToken;
		}

		this.#pendingTokenList[chatId] = new Promise((resolve) => {
			this.#loadToken(chatId).then(() => {
				delete this.#pendingTokenList[chatId];
				resolve(this.#tokenList[chatId]);
			});
		});

		return this.#pendingTokenList[chatId];
	}

	setToken(chatId, token): void
	{
		this.#tokenList[chatId] = token;
	}

	async getUserToken(chatId): Promise<?string>
	{
		const pendingToken = this.#pendingTokenList[chatId];

		if (this.#userToken)
		{
			return this.#userToken;
		}

		if (pendingToken)
		{
			return pendingToken;
		}

		this.#pendingTokenList[chatId] = new Promise((resolve) => {
			this.#loadToken(chatId).then(() => {
				delete this.#pendingTokenList[chatId];
				resolve(this.#userToken);
			});
		});

		return this.#pendingTokenList[chatId];
	}

	setUserToken(token): void
	{
		this.#userToken = token;
	}

	clearTokenList(): void
	{
		this.#tokenList = {};
		this.#pendingTokenList = {};
		this.#userToken = null;
	}

	async #loadToken(chatId): void
	{
		try
		{
			const params = {
				chatId,
				...this.#queryParams,
			};
			const response = await BX.rest.callMethod('call.Call.getCallToken', params);
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

export const CallTokenManager = new TokenManager();