(() => {
	const require = (ext) => jn.require(ext);
	const { md5 } = require('utils/hash');
	const { Random } = require('utils/random');

	/**
	 * @class Utils
	 * @deprecated Please use specific utils from utils/string, utils/object, etc.
	*/
	class Utils
	{
		static md5(any)
		{
			return md5(any);
		}

		static getRandom(length = 8)
		{
			return Random.getString(length);
		}
	}

	// todo remove after all global usages in other modules will be cleaned
	jnexport(Utils);
	jnexport([Utils, 'CommonUtils']);
})();
