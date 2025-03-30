(() => {
	const require = (ext) => jn.require(ext);
	const { SignDocument } = require('sign/document');
	const { SignDialog } = require('sign/dialog');
	const { getSigningLinkPromise } = require('sign/connector');
	const { MemberRole } = require('sign/type/member-role');

	const ROLE_REVIEWER = 'reviewer';
	const ROLE_SIGNER = 'signer';

	const memberId = BX.componentParameters.get('memberId', false);
	const preinstalledUrl = BX.componentParameters.get('url', false);
	const preinstalledRole = BX.componentParameters.get('role', ROLE_SIGNER);
	const preinstalledTitle = BX.componentParameters.get('title', '');
	const preinstalledIsGoskey = BX.componentParameters.get('isGoskey', false);
	const preinstalledIsExternal = BX.componentParameters.get('isExternal', false);
	const initiatedByType = BX.componentParameters.get('initiatedByType');

	function addHeader(role = preinstalledRole)
	{
		setTitleByRole(role);
		layout.setRightButtons([{
			type: 'cross',
			callback: () => layout.close(),
		}]);
	}

	function setTitleByRole(role = preinstalledRole)
	{
		if (MemberRole.isReviewerRole(role))
		{
			layout.setTitle({
				text: BX.message('SIGN_MOBILE_SIGN_DOCUMENT_UNAVAILABLE_DIALOG_SUBTITLE_REVIEWER'),
				useLargeTitleMode: true,
			});
		}
		else
		{
			layout.setTitle({
				text: BX.message('SIGN_MOBILE_SIGN_DOCUMENT_UNAVAILABLE_DIALOG_SUBTITLE'),
				useLargeTitleMode: true,
			});
		}
	}

	if (preinstalledUrl !== false)
	{
		layout.showComponent(new SignDocument({
			url: preinstalledUrl,
			widget: layout,
			memberId,
			title: preinstalledTitle,
			isGoskey: preinstalledIsGoskey,
			isExternal: preinstalledIsExternal,
			initiatedByType: initiatedByType,
			role: preinstalledRole,
		}));
		addHeader();
	}
	else if (memberId !== false)
	{
		getSigningLinkPromise(memberId).then(({ data }) => {
			const {
				url,
				isReadyForSigning,
				isGoskey,
				isExternal,
				state,
				role,
				documentTitle = '',
				initiatedByType,
			} = data;

			if (isReadyForSigning)
			{
				layout.showComponent(new SignDocument({
					role,
					url,
					widget: layout,
					memberId,
					title: documentTitle,
					isGoskey,
					isExternal,
					initiatedByType,
				}));
				addHeader(role);
			}
			else
			{
				SignDialog.show({
					type: state,
					memberId,
					layoutWidget: layout,
					fileDownloadUrl: url,
					documentTitle,
					initiatedByType,
				});
			}
		}).catch(({ errors }) => {
			let accessDeniedHandled = false;

			if (Array.isArray(errors))
			{
				errors.forEach((error) => {
					if (error.code === 'ACCESS_DENIED')
					{
						SignDialog.show({
							type: SignDialog.ERROR_ACCESS_DENIED_BANNER_TYPE,
							layoutWidget: layout,
							initiatedByType,
						});
						accessDeniedHandled = true;
					}
				});
			}

			if (!accessDeniedHandled)
			{
				SignDialog.show({
					type: SignDialog.ERROR_BANNER_TYPE,
					layoutWidget: layout,
					initiatedByType,
				});
			}
		});
	}
})();
