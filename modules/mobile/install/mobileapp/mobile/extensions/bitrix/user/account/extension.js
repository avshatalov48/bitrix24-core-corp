jn.define("user/account-delete", (require, exports, module) => {
	const imagePath = `${currentDomain}/bitrix/mobileapp/mobile/extensions/bitrix/user/account/images/`
	class Disclaimer extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.onAccept = ()=>{}
			this.onClose = ()=>{}
		}

		render()
		{
			let padding = 12
			return View({
					style: {
						paddingLeft: 30,
						paddingRight: 20,
						flex: 1,
					}
				},
				View({
						style: { flexDirection:"row", height: 100, alignItems:'center'}
					},
					Image({ style: { width: 40,  height: 40 , selfAlign:'center', marginRight: 18}, uri: `${imagePath}warn.png`}, ),
					Text({text: BX.message("DELETE_ACCOUNT_WARNING"), style: {fontSize: 30}})
				),
				View({
						style: {
							paddingRight: padding,
							paddingBottom: 18,
							borderRadius: 10,
						}
					},
					Text({
						text: BX.message('DISCLAIMER_DELETE_ACCOUNT'),
						style: {
							display: 'flex',
							fontSize: 18,
							fontColor: '#333333',
							textAlign: 'justify'
						}
					})
				),
				Button({
					onClick: () => {
						this.onAccept()
					},
					text: BX.message("DELETE_ACCOUNT_CONTINUE"),
					style: {
						alignSelf: 'center',
						color: {default: "#ffffff", pressed: "#A8ffffff"},
						height: 50,
						minWidth: 200,
						fontSize: 18,
						backgroundColor: "#3bc8f5",
						marginTop: 20,
						fontWeight: '600',
						borderRadius: 25,
					}
				})
			)
		}
	}

	function openDeleteDialog(opener = PageManager)
	{
		let params = {titleParams: {
			text: BX.message("DELETE_ACCOUNT_TITLE"),
				useLargeTitleMode: true
			},
			backdrop: { mediumPositionPercent: 70, swipeAllowed: false }};
		let disclaimer = new Disclaimer()
		opener.openWidget("layout", params).then(layout => {
			disclaimer.onAccept = () => {
				let url = getDeleteFormUrl();
				layout.close(() =>  {
					if (url != null) {
						Application.openUrl(getDeleteFormUrl())
					}
				})
				disclaimer.onAccept = null;
			}
			layout.showComponent(disclaimer)
		})
	}

	function getDeleteFormUrl() {
		const regex = /^.+\.(bitrix24\.\w+|br\.\w+)$/i;
		let components = currentDomain.match(regex)
		if (components != null && components.length === 2) {
			let strippedDomain = currentDomain.replace(/https:\/\/|http:\/\/|/,"")
			return `https://${components[1]}/delete-profile.php?domain=${encodeURIComponent(strippedDomain)}`;
		}

		return null;
	}

	function isCloudAccount() {
		const regExp = /^.+\.(bitrix24\.(\w+|com.br|com.tr))$/i
		return currentDomain.match(regExp) != null
	}

	module.exports = { openDeleteDialog, isCloudAccount, Disclaimer}

});