/**
 * @module im/messenger/model/dialogues/collab/default-element
 */

jn.define('im/messenger/model/dialogues/collab/default-element', (require, exports, module) => {
	const collabDefaultElement = Object.freeze({
		guestCount: 0,
		collabId: 0,
		entities: {},
	});

	module.exports = {
		collabDefaultElement,
	};
});
