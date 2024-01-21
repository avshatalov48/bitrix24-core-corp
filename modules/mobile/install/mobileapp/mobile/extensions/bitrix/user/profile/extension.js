/**
 * @module user/profile
 */
jn.define('user/profile', (require, exports, module) => {
	const { Profile } = require('user/profile/src/profile');
	const { ProfileView } = require('user/profile/src/profile-view');

	module.exports = { Profile, ProfileView };
});
