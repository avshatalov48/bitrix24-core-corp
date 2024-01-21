/**
 * @bxjs_lang_path component.php
 * @var {BaseList} form
 */

(function() {
	const require = (ext) => jn.require(ext);

	const ProfileEdit = require('user/profile.edit');
	const { ProfileView } = require('user/profile');
	const userId = BX.componentParameters.get('userId', '0');
	const formFields = BX.componentParameters.get('items', []);
	const formSections = BX.componentParameters.get('sections', []);
	const mode = BX.componentParameters.get('mode', 'view');
	const isBackdrop = BX.componentParameters.get('isBackdrop', false);

	if (mode === 'edit')
	{
		(new ProfileEdit(userId, form, formFields, formSections)).init();
	}
	else
	{
		ProfileView.open({ userId, isBackdrop }, form);
	}
})();
