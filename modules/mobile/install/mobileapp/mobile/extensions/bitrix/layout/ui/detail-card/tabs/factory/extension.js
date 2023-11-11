/**
 * @module layout/ui/detail-card/tabs/factory
 */
jn.define('layout/ui/detail-card/tabs/factory', (require, exports, module) => {
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');
	const { EditorTab } = require('layout/ui/detail-card/tabs/editor');
	const { ProductTab } = require('layout/ui/detail-card/tabs/product');
	const { CrmProductTab } = require('layout/ui/detail-card/tabs/crm-product');
	const { TimelineTab } = require('layout/ui/detail-card/tabs/timeline');

	/**
	 * @class TabFactory
	 */
	class TabFactory
	{
		static create(type, props)
		{
			let Tab = null;

			switch (type)
			{
				case TabType.EDITOR:
					Tab = EditorTab;
					break;

				case TabType.PRODUCT:
					Tab = ProductTab;
					break;

				case TabType.CRM_PRODUCT:
					Tab = CrmProductTab;
					break;

				case TabType.TIMELINE:
					Tab = TimelineTab;
					break;
			}

			if (!Tab)
			{
				throw new Error(`Tab implementation {${type}} not found.`);
			}

			return new Tab(props);
		}
	}

	module.exports = { TabFactory };
});
