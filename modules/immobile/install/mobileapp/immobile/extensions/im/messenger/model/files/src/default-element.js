/**
 * @module im/messenger/model/files/default-element
 */

jn.define('im/messenger/model/files/default-element', (require, exports, module) => {
	const { FileStatus } = require('im/messenger/const');

	const fileDefaultElement = Object.freeze({
		id: 0,
		templateId: '',
		chatId: 0,
		dialogId: '0',
		name: 'File is deleted',
		date: new Date(),
		type: 'file',
		extension: '',
		size: 0,
		image: false,
		status: FileStatus.done,
		progress: 100,
		authorId: 0,
		authorName: '',
		urlPreview: '',
		urlLocalPreview: '',
		urlShow: '',
		urlDownload: '',
		localUrl: '',
		viewerAttrs: null,
		uploadData: {
			byteSent: 0,
			byteTotal: 0,
		},
		mediaUrl: {},
	});

	module.exports = {
		fileDefaultElement,
	};
});
