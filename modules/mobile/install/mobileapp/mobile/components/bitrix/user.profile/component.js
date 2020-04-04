/**
 * @bxjs_lang_path component.php
 * @var {BaseList} form
 */

(function ()
{
	let userId = BX.componentParameters.get("userId", "0");
	let formFields = BX.componentParameters.get("items", []);
	let formSections = BX.componentParameters.get("sections", []);
	let mode = BX.componentParameters.get("mode", "view");

	if(mode == "edit")
	{
		(new ProfileEdit(userId, form, formFields, formSections)).init();
	}
	else
	{
		ProfileView.open({userId: userId}, form);
	}

})();
