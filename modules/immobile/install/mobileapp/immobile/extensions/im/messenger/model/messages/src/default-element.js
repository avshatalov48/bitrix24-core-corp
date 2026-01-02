/**
 * @module im/messenger/model/messages/default-element
 */

jn.define('im/messenger/model/messages/default-element', (require, exports, module) => {
	const messageDefaultElement = Object.freeze({
		id: 0,
		templateId: '',
		previousId: 0,
		nextId: 0,
		chatId: 0,
		authorId: 0,
		date: new Date(),
		text: '',
		loadText: '',
		uploadFileId: '',
		params: {},
		files: [],
		unread: false,
		viewed: true,
		viewedByOthers: false,
		sending: false,
		error: false,
		errorReason: 0, // code from rest/classes/general/rest.php:25 or main/install/js/main/core/core_ajax.js:1044
		retry: false,
		audioPlaying: false,
		playingTime: 0,
		attach: [],
		keyboard: [],
		richLinkId: null,
		forward: {},
		push: false,
	});

	module.exports = {
		messageDefaultElement,
	};
});
