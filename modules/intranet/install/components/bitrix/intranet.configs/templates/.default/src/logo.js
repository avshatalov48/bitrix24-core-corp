export class Logo
{
	constructor(parent)
	{
		this.ajaxPath = parent.ajaxPath;

		if (BX("configLogoPostForm") && BX("configLogoPostForm").client_logo)
		{
			BX.bind(BX("configLogoPostForm").client_logo, "change", () => {
				this.LogoChange();
			});
		}

		if (BX("configDeleteLogo"))
		{
			BX.bind(BX("configDeleteLogo"), "click", () => {
				this.LogoDelete(BX("configDeleteLogo"));
			});
		}

		if (BX("configLogoRetinaPostForm") && BX("configLogoRetinaPostForm").client_logo_retina)
		{
			BX.bind(BX("configLogoRetinaPostForm").client_logo_retina, "change", () => {
				this.LogoChange("retina");
			});
		}

		if (BX("configDeleteLogoretina"))
		{
			BX.bind(BX("configDeleteLogoretina"), "click", () => {
				this.LogoDelete(BX("configDeleteLogoretina"), "retina");
			});
		}
	}

	LogoChange(mode)
	{
		mode = (mode == "retina" ? "retina" : "");

		BX('configWaitLogo' + mode).style.display='inline-block';
		BX.ajax.submit(
			BX(mode == "retina" ? 'configLogoRetinaPostForm' : 'configLogoPostForm'),
			function(reply)
			{
				try {
					var json = JSON.parse(reply);

					if (json.error)
					{
						BX('config_logo_error_block').style.display = 'block';
						var error_block = BX.findChild(BX('config_logo_error_block'), {class: 'content-edit-form-notice-text'}, true, false);
						error_block.innerHTML = '<span class=\'content-edit-form-notice-icon\'></span>'+json.error;
					}
					else if (json.path)
					{
						BX('config_logo_error_block').style.display = 'none';
						BX('configImgLogo' + mode).src = json.path;
						BX('configBlockLogo' + mode).style.display = 'inline-block';
						BX('configDeleteLogo' + mode).style.display = 'inline-block';
					}
					BX('configWaitLogo' + mode).style.display='none';
				} catch (e) {
					BX('configWaitLogo' + mode).style.display='none';
					return false;
				}
			}
		);
	}

	LogoDelete(curLink, mode)
	{
		mode = (mode == "retina" ? "retina" : "");

		if (confirm(BX.message("LogoDeleteConfirm")))
		{
			BX('configWaitLogo' + mode).style.display='inline-block';

			BX.ajax.post(
				this.ajaxPath,
				{
					client_delete_logo: 'Y',
					sessid : BX.bitrix_sessid(),
					mode: mode
				},
				function(){

					BX('configBlockLogo' + mode).style.display = 'none';
					curLink.style.display = 'none';
					BX('config_error_block').style.display = 'none';
					BX('configWaitLogo' + mode).style.display='none';
				}
			);
		}
	}
}