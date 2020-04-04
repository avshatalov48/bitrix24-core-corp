;(function()
{
	var client = null;
	var login = '';
	var server = '';

	BX.namespace("BX.Voximplant");

	var connect = function()
	{
		return new Promise(function(resolve, reject)
		{
			if(client.connected())
			{
				return resolve();
			}
			client.connect().then(resolve).catch(reject)
		});
	};

	var authorize = function()
	{
		return new Promise(function(resolve, reject)
		{
			getAuthData().then(getOneTimeKey).then(getKeyHash).then(function(oneTimeKeyHash)
			{
				return client.loginWithOneTimeKey(login + '@' + server, oneTimeKeyHash);
			}).then(resolve).catch(reject)
		});
	};

	var getAuthData = function()
	{
		return new Promise(function(resolve, reject)
		{
			if(BX.type.isNotEmptyString(login))
			{
				return resolve({
					login: login,
					server: server
				});
			}
			else if(BX.message('voximplantLogin'))
			{
				login = BX.message('voximplantLogin');
				server = BX.message('voximplantServer');

				return resolve({
					login: login,
					server: server
				});
			}
			else
			{
				BX.rest.callMethod('voximplant.authorization.get').then(function(result)
				{
					var data = result.data();

					if(BX.type.isNotEmptyString(data.LOGIN) && BX.type.isNotEmptyString(data.SERVER))
					{
						login = data.LOGIN;
						server = data.SERVER;
						resolve({
							login: login,
							server: server
						});
					}
					else
					{
						var e = new Error("Could not get voximplant login for user");
						e.name = "AuthResult";
						reject(e);
					}
				}).catch(function(error)
				{
					reject(error);
				})
			}
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
			client.requestOneTimeLoginKey(authData.login + '@' + authData.server);
		});
	};

	var getKeyHash = function(oneTimeKey)
	{
		return new Promise(function(resolve, reject)
		{
			BX.rest.callMethod('voximplant.authorization.signOneTimeKey', {KEY: oneTimeKey}).then(function(result)
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
		if(!client)
		{
			client = VoxImplant.getInstance();
		}

		if(client.getClientState() === "LOGGED_ID")
		{
			result.resolve(client);
			return result;
		}

		var clientParameters = {
			micRequired: false,
			progressTone: false,
			experiments: {
				preventRendering: true
			}
		};

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

		client.init(clientParameters)
			.then(connect)
			.then(authorize)
			.then(function(e)
			{
				result.resolve(client);
			})
			.catch(function(err)
			{
				console.error(err);

				if(err.name === "AuthResult")
				{
					BX.rest.callMethod('voximplant.authorization.onError');
				}

				result.reject(err);
			});

		return result;
	}
})();