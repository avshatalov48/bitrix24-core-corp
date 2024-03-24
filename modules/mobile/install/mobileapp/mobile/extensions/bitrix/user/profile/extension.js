/**
 * @module user/profile
 */
jn.define('user/profile', (require, exports, module) => {
	const { Profile } = require('user/profile/src/profile');
	const { ProfileView } = require('user/profile/src/profile-view');
	const { openUserProfile } = require('user/profile/src/backdrop-profile');

	module.exports = { Profile, ProfileView, openUserProfile };
});
