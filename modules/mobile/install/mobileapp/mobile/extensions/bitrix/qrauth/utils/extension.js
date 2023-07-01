(function () {
	/**
	 * @global {{open: qrauth.open}} qrauth
	 */

	const qrauth = {
		urlTemplate: "https://b24.to/a/",
		open: ({
			title,
			redirectUrl,
			external,
			urlData,
			type,
			showHint,
			hintText,
			layout
		}) => {
			layout = (layout && layout !== PageManager) ? layout : null;
			const componentUrl = availableComponents["qrcodeauth"].publicUrl;
			PageManager.openComponent("JSStackComponent", {
				scriptPath: componentUrl,
				componentCode: "qrauth",
				params: {
					redirectUrl,
					external,
					urlData,
					type,
					showHint,
					hintText
				},
				rootWidget: {
					name: "layout",
					settings: {
						objectName: "layout",
						title: title || BX.message('LOGIN_ON_DESKTOP_DEFAULT_TITLE_MSGVER_1'),
						backdrop: {
							bounceEnable: true,
							mediumPositionHeight:500
						},
					}
				}
			}, layout);
		},
		listenUniversalLink: () => {
			const handler = (data)=> {
				if (!data["url"] || !String(data["url"]).startsWith("https://b24.to/a/"))
				{
					return;
				}
				qrauth.open({
					urlData: data,
					external: true,
					title: BX.message("QR_EXTERNAL_AUTH")
				})
			};
			const unhandled = Application.getUnhandledUniversalLink();
			if (unhandled)
			{
				handler(unhandled)
			}
			Application.on("universalLinkReceived", handler);
		},
		authorizeByUrl(url, redirectUrl = "")
		{
			return new Promise((resolve, reject) => {
				if (url && url.startsWith(qrauth.urlTemplate))
				{
					const path = url.replace(qrauth.urlTemplate, "")
					const [siteId, uniqueId, channelTag] = path.split("/");
					BX.ajax.runAction('main.qrcodeauth.pushToken', {data: {channelTag, siteId, uniqueId, redirectUrl}})
						.then(({status, errors}) => {
								if (status === "success")
								{
									resolve()
								}
								else
								{
									reject(errors[0])
								}
							}
						)
						.catch(({errors}) => {
							if (errors.length > 0) {
								reject(errors[0])
							}
						})
					;
				}
				else
				{
					reject({message: BX.message("WRONG_QR")})
				}
			})
		}
	}

	jnexport([qrauth, "qrauth"]);
})();
