(function () {
	/**
	 * @global {{open: qrauth.open}} qrauth
	 */

	let qrauth = {
		urlTemplate: "https://b24.to/a/",
		open: ({title, redirectUrl, external, urlData, type, showHint}) => {
			let componentUrl = availableComponents["qrcodeauth"].publicUrl;
			PageManager.openComponent("JSStackComponent", {
				scriptPath: componentUrl,
				componentCode: "qrauth",
				params: {redirectUrl, external, urlData, type, showHint},
				rootWidget: {
					name: "layout",
					settings: {
						objectName: "layout",
						title: title,
						backdrop: {
							bounceEnable: true,
							mediumPositionHeight:500
						},
					}
				}
			});
		},
		listenUniversalLink: () => {
			let handler = (data)=> {
				qrauth.open({
					urlData: data,
					external: true,
					title: BX.message("QR_EXTERNAL_AUTH")
				})
				// qrauth.authorizeByUrl(url)
				// 	.then( _ => Notify.showIndicatorSuccess({hideAfter: 2000}))
				// 	.catch( error => Notify.showIndicatorError({text: error.message, hideAfter: 2000}));
			};
			let unhandled = Application.getUnhandledUniversalLink();
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
					let path = url.replace(qrauth.urlTemplate, "")
					let [siteId, uniqueId, channelTag] = path.split("/");
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