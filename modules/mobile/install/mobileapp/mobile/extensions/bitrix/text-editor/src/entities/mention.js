/**
 * @module text-editor/entities/mention
 */
jn.define('text-editor/entities/mention', (require, exports, module) => {
	const { Type } = require('type');

	class Mention
	{
		/**
		 * @param props {{
		 *     type: 'user' | 'project' | 'department',
		 *     data: {
		 *     		id: number | string,
		 *     		title: string,
		 *     		subtitle: string,
		 *     		imageUrl: ?string,
		 *     		params: {[key: string]: any},
		 *     },
		 * }}
		 */
		constructor(props = {})
		{
			/**
			 * @private
			 */
			this.props = { ...props };
		}

		/**
		 * Gets mention id
		 * @returns {string | number}
		 */
		getId()
		{
			return this.props.data.id;
		}

		/**
		 * Gets mention type
		 * @returns {'user' | 'project' | 'department'}
		 */
		getType()
		{
			return this.props.type;
		}

		/**
		 * Gets mention title
		 * @returns {string}
		 */
		getTitle()
		{
			return this.props.data.title;
		}

		/**
		 * Gets subtitle
		 * @returns {string}
		 */
		getSubtitle()
		{
			return this.props.data.subtitle;
		}

		/**
		 * Gets avatar url
		 * @returns {?string}
		 */
		getAvatarUrl()
		{
			return this.props.data.imageUrl;
		}

		/**
		 * Gets mention BBCode
		 * @returns {string}
		 */
		toBbcode()
		{
			const type = this.getType();
			if (Type.isStringFilled(type))
			{
				return `[${type}=${this.getId()}]${this.getTitle()}[/${type}]`;
			}

			return '';
		}
	}

	module.exports = {
		Mention,
	};
});
