/**
 * @bxjs_lang_path component.php
 * @var {BaseList} form
 */

(function ()
{
	const ProfileEdit = jn.require("user/profile.edit")
	const { ProfileView } = jn.require("user/profile")
	let userId = BX.componentParameters.get("userId", "0");
	let formFields = BX.componentParameters.get("items", []);
	let formSections = BX.componentParameters.get("sections", []);
	let mode = BX.componentParameters.get("mode", "view");
	let isBackdrop = BX.componentParameters.get("isBackdrop", false);

	if(mode == "edit")
	{
		(new ProfileEdit(userId, form, formFields, formSections)).init();
	}
	else
	{
		ProfileView.open({userId, isBackdrop}, form);
	}

})();
