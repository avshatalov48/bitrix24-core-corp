/**
 * @bxjs_lang_path extension.php
 */

/**
 * @module crm/entity-detail/component/header-processor
 */
jn.define('crm/entity-detail/component/header-processor', (require, exports, module) => {
	const { Type } = require('crm/type');
	const { EntitySvg } = require('crm/assets/entity');

	/**
	 * @param {Object} header
	 * @param {DetailCardComponent} detailCard
	 * @returns {Object}
	 */
	const headerProcessor = (header, detailCard) => {
		if (header.imageUrl)
		{
			// clear svg, has higher priority
			header.svg = {};

			if (header.imageUrl.indexOf(currentDomain) !== 0)
			{
				header.imageUrl = encodeURI(header.imageUrl);
				header.imageUrl = header.imageUrl.replace(String(currentDomain), '');
				header.imageUrl = (
					header.imageUrl.indexOf('http') === 0
						? header.imageUrl
						: `${currentDomain}${header.imageUrl}`
				);
			}
		}
		else
		{
			const { entityTypeId } = detailCard.getComponentParams();
			const entityTypeName = Type.resolveNameById(entityTypeId);
			if (entityTypeName)
			{
				const iconFunctionName = `${entityTypeName.toLowerCase()}Inverted`;
				if (EntitySvg[iconFunctionName])
				{
					header.svg = {
						content: EntitySvg[iconFunctionName](),
					};
				}
			}
		}

		return header;
	};

	module.exports = { headerProcessor };
});
