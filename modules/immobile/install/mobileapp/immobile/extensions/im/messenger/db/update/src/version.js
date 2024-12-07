/**
 * @module im/messenger/db/update/version
 */
jn.define('im/messenger/db/update/version', (require, exports, module) => {
	const { Type } = require('type');

	const { OptionRepository } = require('im/messenger/db/repository/option');
	const { Updater } = require('im/messenger/db/update/updater');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('database-update--version');

	class Version
	{
		constructor()
		{
			this.version = 0;
			this.cachedVersion = null;
			this.optionName = '~database_schema_version';
			this.versionExtensionPrefix = 'im/messenger/db/update/version/';

			this.init();
		}

		init()
		{
			this.optionRepostory = new OptionRepository();
			this.updater = new Updater();
		}

		getUpdater()
		{
			return this.updater;
		}

		async get()
		{
			if (Type.isNumber(this.cachedVersion))
			{
				return this.cachedVersion;
			}

			this.version = Number(await this.optionRepostory.get(this.optionName, 0));
			this.cachedVersion = this.version;

			return this.version;
		}

		async set(version)
		{
			await this.optionRepostory.set(this.optionName, version);
			this.version = version;
			this.cachedVersion = version;

			logger.warn('[DATABASE-UPDATE] Version.set: ', version);

			return this.version;
		}

		async execute(version)
		{
			if (version === 0)
			{
				return true;
			}

			const currentVersion = await this.get();
			if (currentVersion >= version)
			{
				return true;
			}

			const versionExtension = this.versionExtensionPrefix + version;
			if (jn.define.moduleMap[versionExtension])
			{
				const executeVersion = require(versionExtension);
				await executeVersion(this.getUpdater());
			}

			logger.warn('[DATABASE-UPDATE] Version executed:', version);

			await this.set(version);

			return true;
		}
	}

	module.exports = {
		Version,
	};
});
