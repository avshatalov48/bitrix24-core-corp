;(function()
{
	var client = null;
	var login = '';
	var server = '';
	var restClient = '';

	var accessTokenKey = 'bx-voximplant-at';
	var loginKey = 'bx-voximplant-login';
	var serverKey = 'bx-voximplant-server';
	var ttl = 30 * 24 * 60 * 60;

	BX.namespace("BX.Voximplant");

	var load = function()
	{
		var sdkUrl = BX.message("voximplantSdkUrl");
		
		return new Promise(function(resolve, reject)
		{
			var cancelTimeout = setTimeout(function()
			{
				var e = {
					name: "NetworkingTimeout",
					code: "NETWORK_ERROR",
					message: "Could not load VoxImplant SDK"
				};
				reject(e);
			}, 5000);

			BX.loadScript(sdkUrl, function()
			{
				clearTimeout(cancelTimeout);

				if ("VoxImplant" in window)
				{
					resolve()
				}
				else
				{
					var e = {
						name: "NetworkingError",
						code: "NETWORK_ERROR",
						message: "VoxImplant SDK not found"
					};
					reject(e);
				}
			})
		})
	};

	var init = function(config)
	{
		return new Promise(function(resolve, reject)
		{
			if(client.alreadyInitialized)
			{
				return resolve();
			}
			client.init(config).then(resolve).catch(reject)
		});
	};

	var connect = function()
	{
		return new Promise(function(resolve, reject)
		{
			if(client.connected())
			{
				return resolve();
			}
			client.connect().then(resolve).catch((err) => {
				reject(new VoxConnectionError())
			})
		});
	};

	var authorize = function()
	{
		return new Promise(function(resolve, reject)
		{
			var authData;
			getAuthData()
				.then(function(result)
				{
					authData = result;
					return tryLoginWithToken(authData)
				})
				.then(function(success)
				{
					if (success)
					{
						return resolve();
					}

					getOneTimeKey(authData)
						.then(getKeyHash)
						.then(function(oneTimeKeyHash)
						{
							return client.loginWithOneTimeKey(login + '@' + server, oneTimeKeyHash);
						})
						.then(function(loginResult)
						{
							if(loginResult.tokens)
							{
								storeTokens(loginResult.tokens);
							}

							resolve(loginResult)
						})
						.catch(function(err)
						{
							BX.localStorage.remove(loginKey);
							BX.localStorage.remove(serverKey);
							reject(err)
						})

				}).catch(reject);
		});
	};

	var tryLoginWithToken = function(authData)
	{
		return new Promise(function(resolve, reject)
		{
			var accessToken = BX.localStorage.get(accessTokenKey);
			if(!accessToken)
			{
				return resolve(false);
			}

			client.loginWithToken(authData.login + "@" + authData.server, accessToken).then(
				function (result)
				{
					storeTokens(result.tokens);
					resolve(true)
				},
				function (error)
				{
					BX.localStorage.remove(accessTokenKey);
					resolve(false);
				}
			)
		})
	};

	/*var tryRefreshToken = function(authData)
	{
		return new Promise(function(resolve, reject)
		{
			if(!BX.localStorage)
			{
				return resolve(false);
			}
			var accessToken = BX.localStorage.get(accessTokenKey);
			if(accessToken)
			{
				// token is not expired yet, no need to refresh
				return resolve(true);
			}
			var refreshToken = BX.localStorage.get(refreshTokenKey);
			if(refreshToken)
			{
				console.log("trying to refresh token");
				client.tokenRefresh(authData.login + "@" + authData.server, refreshToken).then(function(response)
				{
					console.log(response);
					if(!response.result || !response.tokens)
					{
						BX.localStorage.remove(accessTokenKey);
						BX.localStorage.remove(refreshTokenKey);
						return resolve(false);
					}
					storeTokens(response.tokens);
					resolve(true);
				}).catch(function(err)
				{
					resolve(false);
				})
			}
		});
	};*/

	/**
	 * Stores received tokens for future usage
	 *
	 * @see https://voximplant.com/docs/references/websdk/voximplant/eventhandlers/authresult
	 * @param {object} tokens
	 * @param {string} tokens.accessToken
	 * @param {number} tokens.accessExpire
	 * @param {string} tokens.refreshToken
	 * @param {number} tokens.refreshExpire
	 */
	var storeTokens = function(tokens)
	{
		BX.localStorage.set(accessTokenKey, tokens.accessToken, tokens.accessExpire);
		//BX.localStorage.set(refreshTokenKey, tokens.refreshToken, tokens.refreshExpire);
	};

	var getAuthData = function()
	{
		return new Promise(function(resolve, reject)
		{
			login = login || BX.localStorage.get(loginKey);
			server = server || BX.localStorage.get(serverKey);
			if(BX.type.isNotEmptyString(login) && BX.type.isNotEmptyString(server))
			{
				return resolve({
					login: login,
					server: server
				});
			}

			restClient.callMethod('voximplant.authorization.get').then(function(result)
			{
				var data = result.data();

				if(BX.type.isNotEmptyString(data.LOGIN) && BX.type.isNotEmptyString(data.SERVER))
				{
					login = data.LOGIN;
					server = data.SERVER;
					BX.localStorage.set(serverKey, server, ttl);
					BX.localStorage.set(loginKey, login, ttl);

					resolve({
						login: login,
						server: server
					});
				}
				else
				{
					var e = {
						name: "AuthResult",
						code: "LOGIN_EMPTY",
						message: "Could not get voximplant login for user"
					};

					reject(e);
				}
			}).catch(function(error)
			{
				reject(error);
			})
		});
	};

	var getOneTimeKey = function(authData)
	{
		return new Promise(function(resolve, reject)
		{
			var onAuthResult = function(e)
			{
				client.removeEventListener(VoxImplant.Events.AuthResult, onAuthResult);
				if(e.code == 302 && e.key)
				{
					resolve(e.key);
				}
				else
				{
					reject(e);
				}
			};
			client.addEventListener(VoxImplant.Events.AuthResult, onAuthResult);
			client.requestOneTimeLoginKey(authData.login + '@' + authData.server).catch(function(e)
			{
				reject(e);
			});
		});
	};

	var getKeyHash = function(oneTimeKey)
	{
		return new Promise(function(resolve, reject)
		{
			restClient.callMethod('voximplant.authorization.signOneTimeKey', {KEY: oneTimeKey}).then(function(result)
			{
				var data = result.data();

				if(data.HASH)
				{
					resolve(data.HASH)
				}
			}).catch(function(error)
			{
				reject(error);
			})
		})
	};

	BX.Voximplant.getClient = function(config)
	{
		config = BX.type.isPlainObject(config) ? config : {};
		var result = new BX.Promise();
		restClient = config.restClient || BX.rest;

		if(client && client.getClientState() === "LOGGED_IN")
		{
			result.resolve(client);
			return result;
		}

		load().then(function()
		{
			client = VoxImplant.getInstance();

			var mediaServer = BX.message("voximplantMediaServer");

			var clientParameters = {
				micRequired: false,
				progressTone: false,
				/*experiments: {
					preventRendering: true
				}*/
			};

			if (mediaServer !== '')
			{
				clientParameters.serverIp = mediaServer;
			}

			if(config.debug === true)
			{
				clientParameters.showDebugInfo = true;
				clientParameters.showWarnings = true;
				clientParameters.prettyPrint = true;
			}

			if(BX.type.isPlainObject(config.apiParameters))
			{
				clientParameters = BX.util.objectMerge(clientParameters, config.apiParameters);
			}

			return clientParameters;
		}).then(function(clientParameters)
		{
			return init(clientParameters)
		}).then(connect)
			.then(authorize)
			.then(function(e)
			{
				result.resolve(client);
			})
			.catch(function(err)
			{
				console.error(err);

				if("name" in err && err.name === "AuthResult")
				{
					restClient.callMethod('voximplant.authorization.onError');
				}

				result.reject(err);
			});

		return result;
	}

	class VoxConnectionError extends Error
	{
		code = 'NETWORK_ERROR';
		name = 'VoxConnectionError';
	}
})();