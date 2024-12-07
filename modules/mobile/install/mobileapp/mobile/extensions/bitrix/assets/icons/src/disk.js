/**
 * @module assets/icons/src/disk
 */
jn.define('assets/icons/src/disk', (require, exports, module) => {
	const { Icon } = require('assets/icons/src/main');

	/**
	 * @class DiskIcon
	 * @extends {Icon}
	 */
	class DiskIcon extends Icon
	{
		static DISK_FOLDER_BLUE = new DiskIcon('DISK_FOLDER_BLUE', {
			name: 'disk_folder_blue',
			path: '/bitrix/images/mobile/disk-icons/disk_folder_blue.svg',
			content: '',
		});

		static DISK_FOLDER_GREEN = new DiskIcon('DISK_FOLDER_GREEN', {
			name: 'disk_folder_green',
			path: '/bitrix/images/mobile/disk-icons/disk_folder_green.svg',
			content: '',
		});

		static BLANK_FILE = new DiskIcon('BLANK_FILE', {
			name: 'blank_file',
			path: '/bitrix/images/mobile/disk-icons/blank_file.svg',
			content: '',
		});

		static PHP = new DiskIcon('PHP', {
			name: 'php',
			path: '/bitrix/images/mobile/disk-icons/php.svg',
			content: '',
		});

		static TXT = new DiskIcon('TXT', {
			name: 'txt',
			path: '/bitrix/images/mobile/disk-icons/txt.svg',
			content: '',
		});

		static PSD = new DiskIcon('PSD', {
			name: 'psd',
			path: '/bitrix/images/mobile/disk-icons/psd.svg',
			content: '',
		});

		static RAR = new DiskIcon('RAR', {
			name: 'rar',
			path: '/bitrix/images/mobile/disk-icons/rar.svg',
			content: '',
		});

		static ZIP = new DiskIcon('ZIP', {
			name: 'zip',
			path: '/bitrix/images/mobile/disk-icons/zip.svg',
			content: '',
		});

		static PPTX = new DiskIcon('PPTX', {
			name: 'pptx',
			path: '/bitrix/images/mobile/disk-icons/pptx.svg',
			content: '',
		});

		static PPT = new DiskIcon('PPT', {
			name: 'ppt',
			path: '/bitrix/images/mobile/disk-icons/ppt.svg',
			content: '',
		});

		static XLSX = new DiskIcon('XLSX', {
			name: 'xlsx',
			path: '/bitrix/images/mobile/disk-icons/xlsx.svg',
			content: '',
		});

		static XLS = new DiskIcon('XLS', {
			name: 'xls',
			path: '/bitrix/images/mobile/disk-icons/xls.svg',
			content: '',
		});

		static PDF = new DiskIcon('PDF', {
			name: 'pdf',
			path: '/bitrix/images/mobile/disk-icons/pdf.svg',
			content: '',
		});

		static DOCX = new DiskIcon('DOCX', {
			name: 'docx',
			path: '/bitrix/images/mobile/disk-icons/docx.svg',
			content: '',
		});

		static DOC = new DiskIcon('DOC', {
			name: 'doc',
			path: '/bitrix/images/mobile/disk-icons/doc.svg',
			content: '',
		});

		static PLAY_FILLED = new DiskIcon('PLAY_FILLED', {
			name: 'play_filled',
			path: '/bitrix/images/mobile/disk-icons/play_filled.svg',
			content: '',
		});
	}

	module.exports = { DiskIcon };
});
