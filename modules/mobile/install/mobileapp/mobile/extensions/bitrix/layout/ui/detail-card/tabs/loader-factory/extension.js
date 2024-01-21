/**
 * @module layout/ui/detail-card/tabs/loader-factory
 */
jn.define('layout/ui/detail-card/tabs/loader-factory', (require, exports, module) => {
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');
	const { EditorTabShimmer } = require('layout/ui/detail-card/tabs/shimmer/editor');
	const { CrmProductTabShimmer } = require('layout/ui/detail-card/tabs/shimmer/crm-product');
	const { TimelineTabShimmer } = require('layout/ui/detail-card/tabs/shimmer/timeline');

	/**
	 * @class TabLoaderFactory
	 */
	class TabLoaderFactory
	{
		static createLoader(type, props = {})
		{
			// eslint-disable-next-line default-case
			switch (type)
			{
				case TabType.EDITOR:
					return new EditorTabShimmer(props);

				case TabType.CRM_PRODUCT:
					return new CrmProductTabShimmer(props);

				case TabType.TIMELINE:
					return new TimelineTabShimmer(props);
			}

			return null;
		}
	}

	module.exports = { TabLoaderFactory };
});
