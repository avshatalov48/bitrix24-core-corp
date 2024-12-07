/**
 * @module ui-system/blocks/file/icon
 */
jn.define('ui-system/blocks/file/icon', (require, exports, module) => {
	const { FileIconSize } = require('ui-system/blocks/file/icon/src/size-enum');
	const { FileIconFormat } = require('ui-system/blocks/file/icon/src/format-enum');
	const { IconView } = require('ui-system/blocks/icon');
	const { DiskIcon } = require('assets/icons');
	const { Color, Component } = require('tokens');
	const { withCurrentDomain } = require('utils/url');
	const { SafeImage } = require('layout/ui/safe-image');

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

	const extToFormat = {
		pdf: FileIconFormat.PDF,
		doc: FileIconFormat.DOC,
		docx: FileIconFormat.DOCX,
		ppt: FileIconFormat.PPT,
		pptx: FileIconFormat.PPTX,
		xls: FileIconFormat.XLS,
		xlsx: FileIconFormat.XLSX,
		txt: FileIconFormat.TXT,
		php: FileIconFormat.PHP,
		psd: FileIconFormat.PSD,
		rar: FileIconFormat.RAR,
		zip: FileIconFormat.ZIP,
	};

	const typeToFormat = {
		[Types.IMAGE]: FileIconFormat.IMAGE,
		[Types.VIDEO]: FileIconFormat.VIDEO,
		// 4: FileIconFormat.DOCUMENT,
		// 5: FileIconFormat.ARCHIVE,
		[Types.SCRIPTS]: FileIconFormat.SCRIPTS,
		[Types.UNKNOWN]: FileIconFormat.EMPTY,
		[Types.PDF]: FileIconFormat.PDF,
		[Types.AUDIO]: FileIconFormat.AUDIO,
		[Types.KNOWN]: FileIconFormat.EMPTY,
		[Types.VECTOR_IMAGE]: FileIconFormat.COMPLEX_GRAPHIC,
	};

	/**
	* @returns {FileIconFormat | undefined}
	* @param {string} extension
	* @param {number} typeFile
	*/
	function resolveFileIconFormat(extension, typeFile)
	{
		const format = extToFormat[extension.toLowerCase()];

		if (format)
		{
			return format;
		}

		return typeToFormat[typeFile];
	}

	/**
	 * @typedef {Object} FilePreviewProps
	 * @property {string} previewUrl
	 * @property {FileIconSize} size
	 */
	function FilePreview(props)
	{
		const isVideo = props.type === Types.VIDEO;
		const resolvedSize = FileIconSize.resolve(props.size, FileIconSize.NORMAL);
		const size = resolvedSize.getSize();

		return View(
			{
				style: {
					position: 'relative',
					width: size,
					height: size,
					testId: props.testId,
				},
			},
			SafeImage({
				style: {
					width: size,
					height: size,
					borderRadius: Component.elementSCorner.toNumber(),
				},
				wrapperStyle: {
					position: 'absolute',
					top: 0,
					left: 0,
				},
				resizeMode: 'cover',
				uri: withCurrentDomain(props.previewUrl),
			}),
			isVideo && View(
				{
					style: {
						bottom: 3,
						right: 3,
						position: 'absolute',
					},
				},
				IconView({
					size: 8,
					color: null,
					icon: DiskIcon.PLAY_FILLED,
				}),
			),
		);
	}

	/**
	 * @typedef {Object} FileIconProps
	 * @property {FileIconFormat} [mode=FileIconFormat.PLAIN]
	 * @property {FileIconSize} [design=FolderIconSize.NORMAL]
	 * @property {String} previewUrl
	 * @property {testId} testId
	 */
	function FileIcon(props)
	{
		const resolvedFormat = FileIconFormat.resolve(props.format, FileIconFormat.EMPTY);
		const resolvedSize = FileIconSize.resolve(props.size, FileIconSize.NORMAL);

		const icon = resolvedFormat.getIcon();
		const isMedia = resolvedFormat.isMedia();

		const size = resolvedSize.getSize();
		const backgroundIconSize = resolvedSize.getBackgroundIconSize();

		const iconSize = isMedia ? resolvedSize.getMediaIconSize() : resolvedSize.getIconSize();
		const iconCoordinates = isMedia ? resolvedSize.getMediaIconCoordinates() : resolvedSize.getIconCoordinates();
		const iconColor = isMedia ? Color.accentMainPrimaryalt : null;

		return View(
			{
				style: {
					width: size,
					height: size,
					position: 'relative',
					display: 'flex',
					justifyContent: 'center',
					alignItems: 'center',
					alignContent: 'center',
				},
				testId: props.testId,
			},
			IconView({
				icon: DiskIcon.BLANK_FILE,
				color: null,
				size: backgroundIconSize,
			}),
			icon && IconView({
				icon,
				size: iconSize,
				color: iconColor,
				style: {
					position: 'absolute',
					...iconCoordinates,
				},
			}),
		);
	}

	FileIcon.propTypes = {
		testId: PropTypes.string.isRequired,
		size: PropTypes.instanceOf(FileIconSize),
		format: PropTypes.instanceOf(FileIconFormat),
	};

	module.exports = { FileIcon, FileIconFormat, FileIconSize, FilePreview, resolveFileIconFormat };
});
