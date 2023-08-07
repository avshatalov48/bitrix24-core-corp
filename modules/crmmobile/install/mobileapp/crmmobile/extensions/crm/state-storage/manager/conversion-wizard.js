/**
 * @module crm/state-storage/manager/conversion-wizard
 */
jn.define('crm/state-storage/manager/conversion-wizard', (require, exports, module) => {
	const { Base } = require('crm/state-storage/manager/base');

	/**
	 * @class ConversionWizardStoreManager
	 */
	class ConversionWizardStoreManager extends Base
	{
		storeOptions()
		{
			return {
				storeName: 'crm.conversion-wizard',
				shareState: true,
			};
		}

		getEntityTypeIds(key)
		{
			return this.store.getters['conversionWizardModel/getEntityTypeIds'](key);
		}

		setEntityTypeIds(data = {})
		{
			this.store.dispatch('conversionWizardModel/setEntityTypeIds', { data });
		}
	}

	module.exports = { ConversionWizardStoreManager };
});
