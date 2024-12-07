/**
 * @module ui-system/blocks/file/icon/src/format-enum
 */
jn.define('ui-system/blocks/file/icon/src/format-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { DiskIcon } = require('assets/icons');

	/**
	 * @class FileIconFormat
	 * @template TFileIconFormat
	 * @extends {BaseEnum<FileIconFormat>}
	 */
	class FileIconFormat extends BaseEnum
	{
		static EMPTY = new FileIconFormat('EMPTY', {
			icon: null,
			isMedia: false,
		});

		static TAKE_PHOTO = new FileIconFormat('TAKE_PHOTO', {
			icon: DiskIcon.CAMERA,
			isMedia: true,
		});

		static ADD = new FileIconFormat('ADD', {
			icon: DiskIcon.PLUS,
			isMedia: true,
		});

		static TEXT = new FileIconFormat('TEXT', {
			icon: DiskIcon.TEXT,
			isMedia: true,
		});

		static SCRIPTS = new FileIconFormat('SCRIPTS', {
			icon: DiskIcon.SETTINGS,
			isMedia: true,
		});

		static FOR_SIGN = new FileIconFormat('FOR_SIGN', {
			icon: DiskIcon.SIGN,
			isMedia: true,
		});

		static COMPLEX_GRAPHIC = new FileIconFormat('COMPLEX_GRAPHIC', {
			icon: DiskIcon.DESIGN,
			isMedia: true,
		});

		static VIDEO = new FileIconFormat('VIDEO', {
			icon: DiskIcon.RECORD_VIDEO,
			isMedia: true,
		});

		static IMAGE = new FileIconFormat('IMAGE', {
			icon: DiskIcon.IMAGE,
			isMedia: true,
		});

		static AUDIO = new FileIconFormat('AUDIO', {
			icon: DiskIcon.MUSIC,
			isMedia: true,
		});

		// static LOADER = new FileIconFormat('LOADER', {
		// 	icon: null,
		// 	isMedia: true,
		// });

		static PHP = new FileIconFormat('PHP', {
			icon: DiskIcon.PHP,
			isMedia: false,
		});

		static TXT = new FileIconFormat('TXT', {
			icon: DiskIcon.TXT,
			isMedia: false,
		});

		static PSD = new FileIconFormat('PSD', {
			icon: DiskIcon.PSD,
			isMedia: false,
		});

		static RAR = new FileIconFormat('RAR', {
			icon: DiskIcon.RAR,
			isMedia: false,
		});

		static ZIP = new FileIconFormat('ZIP', {
			icon: DiskIcon.ZIP,
			isMedia: false,
		});

		static PPTX = new FileIconFormat('PPTX', {
			icon: DiskIcon.PPTX,
			isMedia: false,
		});

		static PPT = new FileIconFormat('PPT', {
			icon: DiskIcon.PPT,
			isMedia: false,
		});

		static XLSX = new FileIconFormat('XLSX', {
			icon: DiskIcon.XLSX,
			isMedia: false,
		});

		static XLS = new FileIconFormat('XLS', {
			icon: DiskIcon.XLS,
			isMedia: false,
		});

		static PDF = new FileIconFormat('PDF', {
			icon: DiskIcon.PDF,
			isMedia: false,
		});

		static DOCX = new FileIconFormat('DOCX', {
			icon: DiskIcon.DOCX,
			isMedia: false,
		});

		static DOC = new FileIconFormat('DOC', {
			icon: DiskIcon.DOC,
			isMedia: false,
		});

		/**
		 * @return {DiskIcon}
		 */
		getIcon()
		{
			return this.getValue().icon;
		}

		/**
		 * @return {Boolean}
		 */
		isMedia()
		{
			return this.getValue().isMedia;
		}
	}

	module.exports = { FileIconFormat };
});
