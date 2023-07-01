/**
 * @module im/messenger/const/file-type
 */
jn.define('im/messenger/const/file-type', (require, exports, module) => {

	const FileType = Object.freeze({
		image: 'image',
		video: 'video',
		audio: 'audio',
		file: 'file',
	});

	const FileEmojiType = Object.freeze({
		file: 'file',
		image: 'image',
		audio: 'audio',
		video: 'video',
		code: 'code',
		call: 'call',
		attach: 'attach',
		quote: 'quote;'
	});

	module.exports = {
		FileType,
		FileEmojiType,
	};
});
