(() => {
	const resolveArgs = function() {
		let version = null;
		let func = null;

		const functionDetect = (arg) => {
			if (typeof arg !== 'function')
			{
				throw new TypeError(`The argument must be "function" type ${arg}`);
			}

			return arg;
		};

		if (arguments.length === 0)
		{
			throw new Error('Arguments not passed');
		}

		if (arguments.length === 1)
		{
			func = functionDetect(arguments[0]);
		}
		else
		{
			func = functionDetect(arguments[1]);
			version = arguments[0];
		}

		return { func, version };
	};

	class apiExec
	{
		constructor(ver = null, func = null)
		{
			this.func = func;
			this.ver = ver;
			this.preventElse = false;
			this.executeFunction();
		}

		/**
		 * Calls function if the previous call in the chain was unsuccessful(if version is passed checking)
		 * @param ver
		 * @param func
		 * @returns {apiExec}
		 */
		else(ver = null, func = null)
		{
			if (this.preventElse === true)
			{
				return this;
			}

			return this.next.apply(this, arguments);
		}

		/**
		 * Calls function if the previous call in the chain was unsuccessful(if version is passed checking)
		 * @param condition
		 * @param func
		 * @returns {apiExec}
		 */
		elseIf(condition = null, func = null)
		{
			if (this.preventElse === true || Boolean(condition) === false)
			{
				return this;
			}

			return this.next.call(this, null, func);
		}

		/**
		 * Always calls function after previous call (if version is passed checking)
		 * @returns {apiExec}
		 */
		next(ver = null, func = null)
		{
			const resolvedArgs = resolveArgs.apply(null, arguments);
			this.ver = resolvedArgs.version;
			this.func = resolvedArgs.func;
			this.executeFunction();

			return this;
		}

		executeFunction()
		{
			if (this.ver === false)
			{
				this.preventElse = true;
			}
			else if (Application.getApiVersion() >= this.ver || this.ver == null)
			{
				this.preventElse = true;
				this.func.apply();
			}
		}

		static call()
		{
			const resolvedArgs = resolveArgs.apply(null, arguments);

			return new apiExec(resolvedArgs.version, resolvedArgs.func);
		}
	}

	window.ifApi = apiExec.call;
})();
