BX.ready(function()
{
	if (!BX.Sign || !BX.Sign.Error)
	{
		return;
	}

	let errorTimeout = null;
	BX.Sign.Error.getInstance().onError(function(errors)
	{
		if (errors.length > 0)
		{
			let firstError = errors[0];
			let errorArea = document.querySelector('.sign-editor-content-document-error');
			errorArea.style.display = 'block';
			errorArea.style.zIndex = '9';
			errorArea.innerHTML = firstError.message;

			if (errorTimeout)
			{
				clearTimeout(errorTimeout);
			}
			errorTimeout = setTimeout(function() {
				errorArea.style.display = 'none';
				errorArea.style.zIndex = null;
			}, 10000);
		}
	});
});
