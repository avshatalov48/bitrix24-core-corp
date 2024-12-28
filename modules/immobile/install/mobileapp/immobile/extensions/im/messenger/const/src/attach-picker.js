/**
 * @module im/messenger/const/attach-picker
 */
jn.define('im/messenger/const/attach-picker', (require, exports, module) => {
	const AttachPickerId = Object.freeze({
		camera: 'camera',
		mediateka: 'mediateka',
		disk: 'disk',
		task: 'task',
		meeting: 'meeting',
	});

	module.exports = {
		AttachPickerId,
	};
});
