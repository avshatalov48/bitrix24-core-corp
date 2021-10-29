;(function()
{
	BX.namespace('BX.Voximplant');

	BX.Voximplant.Documents = {
		initUploader: function(countryCode)
		{
			var uploadBtn = BX('vi_docs_upload_btn_' + countryCode);
			var uploadForm = BX('vi_docs_upload_form_' + countryCode);
			console.log(countryCode, uploadBtn, uploadForm);
			BX.bind(uploadBtn, 'click', function(e)
			{
				if (uploadForm.style.display == 'none')
				{
					BX.removeClass(uploadBtn, 'ui-btn-primary');
					BX.addClass(uploadBtn, 'ui-btn-light-border');
					BX.addClass(uploadForm, 'tel-connect-pbx-animate');
					uploadForm.style.display = 'block';
				}
				else
				{
					BX.removeClass(uploadBtn, 'ui-btn-light-border');
					BX.addClass(uploadBtn, 'ui-btn-primary');
					BX.removeClass(uploadForm, 'tel-connect-pbx-animate');
					uploadForm.style.display = 'none';
				}

				BX.PreventDefault(e);
			});
		},

		initAdditionalDocumentsUploader: function()
		{
			document.querySelectorAll('[data-role="upload-additional"]').forEach(function(element)
			{
				element.addEventListener('click', function ()
				{
					var verificationId = element.dataset.verificationId;
					BX.ajax.runAction("voximplant.urlmanager.getAdditionalDocumentsUploadUrl", {
						data: {verificationId: verificationId}
					}).then(function (response)
					{
						var data = response.data;
						console.log(data.url)
						window.open(data.url);
					}).catch(function (response)
					{
						console.error(response.errors);
					})
				})
			})
		}
	}
})();