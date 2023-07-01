/**
 * @module layout/ui/entity-editor/controller
 */
jn.define('layout/ui/entity-editor/controller', (require, exports, module) => {

	const { EntityEditorProductController } = require('layout/ui/entity-editor/controller/product');

	const Type = {
		PRODUCT_LIST: 'product_list',
		CATALOG_STORE_DOCUMENT_PRODUCT_LIST: 'catalog_store_document_product_list',
	};

	/**
	 * @class EntityEditorControllerFactory
	 */
	class EntityEditorControllerFactory
	{
		static create(props)
		{
			const { type } = props;

			if (type === Type.PRODUCT_LIST || type === Type.CATALOG_STORE_DOCUMENT_PRODUCT_LIST)
			{
				return new EntityEditorProductController(props);
			}

			return null;
		}
	}

	module.exports = { EntityEditorControllerFactory };
});
