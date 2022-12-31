/**
 * @module layout/ui/detail-card/tabs/loader-factory
 */
jn.define('layout/ui/detail-card/tabs/loader-factory', (require, exports, module) => {

	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');
	const { EditorTabLoader } = require('layout/ui/detail-card/tabs/editor/loader');
	const { CrmProductTabLoader } = require('layout/ui/detail-card/tabs/crm-product/loader');
	const { TimelineTabLoader } = require('layout/ui/detail-card/tabs/timeline/loader');

	/**
	 * @class TabLoaderFactory
	 */
	class TabLoaderFactory
	{
		static createLoader(type, props = {})
		{
			switch (type)
			{
				case TabType.EDITOR:
					return EditorTabLoader(props);

				case TabType.CRM_PRODUCT:
					return CrmProductTabLoader(props);

				case TabType.TIMELINE:
					return TimelineTabLoader(props);
			}

			return null;
		}
	}

	module.exports = { TabLoaderFactory };

});
