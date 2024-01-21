/**
 * @module crm/in-app-url/url/base
 */
jn.define('crm/in-app-url/url/base', (require, exports, module) => {
	const { Route } = require('in-app-url/route');
	const { Type } = require('crm/type');

	const LIST = 'list';
	const DETAIL = 'detail';

	/**
	 * @class CrmUrlBase
	 */
	class CrmUrlBase
	{
		/**
		 * @param {Object} props
		 * @param {string} props.url
		 * @param {?string} props.entityTypeName
		 * @param {?number} props.entityTypeId
		 * @param {string} props.entityId
		 */
		constructor(props)
		{
			this.url = props.url;
			this.props = props;
			this.entityTypeName = null;
			this.typePage = null;

			this.initializeUrlEntity();
		}

		getQueryParam(key)
		{
			return this.url.queryParams[key];
		}

		getMobileDetailParams()
		{
			return `${this.entityTypeName}_id`;
		}

		getListPattern()
		{
			return `/crm/${this.entityTypeName}/`;
		}

		getDetailPattern()
		{
			return `${this.getListPattern()}details/:id/`;
		}

		getDetailId()
		{
			const { entityId } = this.props;

			return entityId || this.getQueryParam(this.getMobileDetailParams());
		}

		getPagePattern(typePage)
		{
			const patterns = {
				list: this.getListPattern(),
				detail: this.getDetailPattern(),
			};

			return patterns[typePage];
		}

		getUrl()
		{
			if (this.isExistUrl())
			{
				return this.url.toString();
			}

			return this.createUrl();
		}

		setEntityTypeName(entityTypeName)
		{
			this.entityTypeName = entityTypeName.toLowerCase();
		}

		getTypePage(type)
		{
			return this.isDetailPage(type) ? DETAIL : LIST;
		}

		isDetailPage(type)
		{
			return ['details', 'show'].includes(type) || this.getDetailId();
		}

		initializeUrlEntity()
		{
			const url = this.url.toString();
			const { entityTypeId } = this.props;
			let { entityTypeName } = this.props;

			if (entityTypeId)
			{
				entityTypeName = Type.resolveNameById(entityTypeId);
			}

			if (!url)
			{
				this.setEntityTypeName(entityTypeName);
				this.typePage = this.getTypePage();

				return;
			}

			const regExp = /\/crm\/(deal|lead|company|contact|type)\/(show|details)?/i;
			const type = url.match(regExp);

			if (!type)
			{
				return;
			}

			this.setEntityTypeName(type[1] || entityTypeName);
			this.typePage = this.getTypePage(type[2]);
		}

		createUrl()
		{
			const pattern = this.getPagePattern(this.typePage);
			const id = this.getDetailId();

			return this.generatePath(pattern, { id });
		}

		isExistUrl()
		{
			return Boolean(this.url.toString());
		}

		generatePath(pattern, params)
		{
			const route = new Route({ pattern });

			return route.makeUrl(params);
		}
	}

	module.exports = { CrmUrlBase };
});
