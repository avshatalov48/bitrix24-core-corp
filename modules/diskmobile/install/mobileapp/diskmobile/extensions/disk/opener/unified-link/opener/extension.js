/**
 * @module disk/opener/unified-link/opener
 */
jn.define('disk/opener/unified-link/opener', (require, exports, module) => {
	const { showInternalAlert } = require('error');
	const { isEmpty } = require('utils/object');
	const { FileType } = require('disk/enum');
	const { getUnifiedLinkData } = require('disk/opener/unified-link/rest');

	/**
	 * @class UnifiedOpener
	 */
	class UnifiedOpener
	{
		#uniqueCode;
		#props = {};

		/**
		 * @typedef UnifiedOpenerProps
		 * @property props
		 * @property props.uniqueCode {string}
		 * @property props.url {string}
		 * @property [props.parentWidget] {Object}
		 * @property [props.queryParams] {Object}
		 * @property [props.canOpenInDefault] {boolean}
		 *
		 * @param {UnifiedOpenerProps} props
		 */
		constructor(props)
		{
			this.#props = props ?? {};
			this.#uniqueCode = props.uniqueCode || null;
		}

		async open()
		{
			if (isEmpty(this.#uniqueCode))
			{
				return Promise.reject(new Error('uniqueCode is required'));
			}

			const linkData = await this.#getUnifiedLinkData();

			if (isEmpty(linkData) || linkData.status !== 'success')
			{
				void showInternalAlert();

				return Promise.reject(new Error('Failed to retrieve unified link data'));
			}

			return this.factoryOpeners(linkData?.data?.object || {});
		}

		async factoryOpeners(fileData)
		{
			if (FileType.FLIPCHART === fileData.typeFile)
			{
				const { boardOpener } = await requireLazy('disk:opener/board');
				const { queryParams, ...restProps } = this.#props;

				return boardOpener({
					...restProps,
					...queryParams,
					fileData,
				});
			}

			return Promise.reject(new Error('Unsupported file type for unified opener'));
		}

		#getUnifiedLinkData = () => {
			const { version, versionId, attachedId } = this.#getQueryParams();

			return getUnifiedLinkData(this.#uniqueCode, attachedId, version || versionId);
		};

		#getQueryParams()
		{
			const { queryParams } = this.#props;

			return queryParams;
		}
	}

	module.exports = {
		unifiedOpener: (props) => {
			return (new UnifiedOpener(props)).open();
		},
	};
});
