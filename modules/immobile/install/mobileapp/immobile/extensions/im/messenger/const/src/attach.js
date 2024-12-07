/**
 * @module im/messenger/const/attach
 */
jn.define('im/messenger/const/attach', (require, exports, module) => {
	const AttachType = Object.freeze({
		delimiter: 'delimiter',
		file: 'file',
		grid: 'grid',
		html: 'html',
		image: 'image',
		link: 'link',
		message: 'message',
		rich: 'richLink',
		user: 'user',
	});

	const AttachDescription = Object.freeze({
		firstMessage: 'FIRST_MESSAGE',
		skipMessage: 'SKIP_MESSAGE',
	});

	const AttachGridItemDisplay = Object.freeze({
		block: 'BLOCK',
		line: 'LINE',
		row: 'ROW', // listed as a 'COLUMN' in the REST-documentation
	});

	const AttachColorToken = Object.freeze({
		primary: 'primary',
		secondary: 'secondary',
		alert: 'alert',
		base: 'base',
	});

	module.exports = {
		AttachType,
		AttachDescription,
		AttachGridItemDisplay,
		AttachColorToken,
	};
});