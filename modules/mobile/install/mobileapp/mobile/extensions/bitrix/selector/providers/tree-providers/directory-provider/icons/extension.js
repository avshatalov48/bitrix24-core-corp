/**
 * @module selector/providers/tree-providers/directory-provider/icons
 */
jn.define('selector/providers/tree-providers/directory-provider/icons', (require, exports, module) => {
	const { getExtension } = require('utils/file');
	const { withCurrentDomain } = require('utils/url');

	const pathToIcons = withCurrentDomain('/bitrix/mobileapp/mobile/extensions/bitrix/selector/providers/tree-providers/directory-provider/icons/');

	const Types = {
		IMAGE: 2,
		VIDEO: 3,
		DOCUMENT: 4,
		ARCHIVE: 5,
		SCRIPTS: 6,
		UNKNOWN: 7,
		PDF: 8,
		AUDIO: 9,
		KNOWN: 10,
		VECTOR_IMAGE: 11,
	};

	const extToUrl = {
		pdf: `${pathToIcons}/files/pdf.svg`,
		doc: `${pathToIcons}/files/doc.svg`,
		docx: `${pathToIcons}/files/docx.svg`,
		ppt: `${pathToIcons}/files/ppt.svg`,
		pptx: `${pathToIcons}/files/pptx.svg`,
		xls: `${pathToIcons}/files/xls.svg`,
		xlsx: `${pathToIcons}/files/xlsx.svg`,
		txt: `${pathToIcons}/files/txt.svg`,
		php: `${pathToIcons}/files/php.svg`,
		psd: `${pathToIcons}/files/psd.svg`,
		rar: `${pathToIcons}/files/rar.svg`,
		zip: `${pathToIcons}/files/zip.svg`,
	};

	const typeToUrl = {
		[Types.IMAGE]: `${pathToIcons}/files/image.svg`,
		[Types.VIDEO]: `${pathToIcons}/files/video.svg`,
		[Types.SCRIPTS]: `${pathToIcons}/files/scripts.svg`,
		[Types.UNKNOWN]: `${pathToIcons}/files/empty.svg`,
		[Types.PDF]: `${pathToIcons}/files/pdf.svg`,
		[Types.AUDIO]: `${pathToIcons}/files/audio.svg`,
		[Types.KNOWN]: `${pathToIcons}/files/empty.svg`,
		[Types.VECTOR_IMAGE]: `${pathToIcons}/files/complex-graphic.svg`,
	};

	function resolveFolderIconUrl(type)
	{
		switch (type)
		{
			case 'shared': return `${pathToIcons}folders/shared.svg`;
			case 'collab': return `${pathToIcons}folders/collab.svg`;
			case 'group': return `${pathToIcons}folders/group.svg`;
			default: return `${pathToIcons}folders/basic.svg`;
		}
	}

	function resolveFileIconUrl(type, name)
	{
		const extension = getExtension(name);
		const url = extToUrl[extension.toLowerCase()];

		if (url)
		{
			return url;
		}

		return typeToUrl[type];
	}

	module.exports = { resolveFolderIconUrl, resolveFileIconUrl };
});
