/**
 * @module call/settings-manager
 */
jn.define('call/settings-manager', (require, exports, module) => {
	class CallSettings
	{
		constructor()
		{
			this.jwtCallsEnabled = BX.componentParameters.get('jwtCallsEnabled');
			this.plainCallsUseJwt = BX.componentParameters.get('jwtInPlainCallsEnabled');
			this.callBalancerUrl = BX.componentParameters.get('callBalancerUrl');
		}

		/**
		 * @param {CallSettingsType} settings
		 */
		setup(settings)
		{
			if (settings.jwtCallsEnabled !== undefined)
			{
				this.jwtCallsEnabled = settings.jwtCallsEnabled;
			}

			if (settings.plainCallsUseJwt !== undefined)
			{
				this.plainCallsUseJwt = settings.plainCallsUseJwt;
			}

			if (settings.callBalancerUrl !== undefined)
			{
				this.callBalancerUrl = settings.callBalancerUrl;
			}
		}

		/**
		 * @return {boolean}
		 */
		get jwtCallsEnabled()
		{
			return this._jwtCallsEnabled;
		}

		/**
		 * @param {boolean} flag
		 */
		set jwtCallsEnabled(flag)
		{
			this._jwtCallsEnabled = flag;
		}

		/**
		 * @return {boolean}
		 */
		get plainCallsUseJwt()
		{
			return this._plainCallsUseJwt;
		}

		/**
		 * @param {boolean} flag
		 */
		set plainCallsUseJwt(flag)
		{
			this._plainCallsUseJwt = flag;
		}

		/**
		 * @return {string}
		 */
		get callBalancerUrl()
		{
			return this._callBalancerUrl;
		}

		/**
		 * @param {string} value
		 */
		set callBalancerUrl(value)
		{
			this._callBalancerUrl = value;
		}

		/**
		 * @return {boolean}
		 */
		isJwtInPlainCallsEnabled()
		{
			return this.isJwtCallsEnabled() && this.plainCallsUseJwt;
		}

		/**
		 * @return {boolean}
		 */
		isJwtCallsEnabled()
		{
			return this.isJwtCallsSupported && this.jwtCallsEnabled;
		}

		/**
		 * @return {boolean}
		 */
		isJwtCallsSupported()
		{
			return 'startCall' in BXClient.getInstance();
		}
	}

	module.exports = {
		CallSettingsManager: new CallSettings(),
	};
});
