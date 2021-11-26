(function () {

	const cloud = Boolean(this.jnExtensionData.get("qrauth")["cloud"])
	const pathToExtension = `/bitrix/mobileapp/mobile/extensions/bitrix/qrauth/`;
	const styles = {
		guideNumber: {
			textAlign: "center",
			borderWidth: 2,
			borderColor: "#9BC6F3",
			borderRadius: 11,
			backgroundColor: "#ffffff",
			fontSize: 13,
			fontWeight: "bold",
			color: "#333333",
			width: 22,
			height: 22,
			marginRight: 10,
		},
		browserNote: {
			opacity: 0.5,
			color: "#000000",
			fontSize: 12,
		}
	}

	class QRCodeGuide extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			console.log(cloud, this.cloud);
		}

		getDomain() {
			let domain = currentDomain
			const regex = /^.+\.(bitrix24\.\w+|br\.\w+)$/i;
			let components = domain.match(regex)
			if (components != null && components.length === 2) {
				return components[1];
			}
			return domain;
		}

		render() {
			return View({
					style: {
						marginTop: 18,
						marginLeft: 18,
						marginBottom:30,
					}
				},
				this.browserNote(BX.message("GET_MORE")),
				this.guidePoint(1, BX.message("OPEN_BROWSER").replace("#DOMAIN#", this.getDomain())),
				this.guidePoint(2, BX.message("SCAN_QR")),
			);
		}

		browserNote(text)
		{
			return View({
				style: {
					marginBottom:20,
				},
			}, Text({style: styles.browserNote, text}))
		}

		guidePoint(number, text)
		{
			return View({
					style: {
						marginBottom: 14,
						flexDirection: 'row',
						justifyContent: "flex-start"
					}
				},
				View({style: {backgroundColor: "#ffffff"}},
					Text({
						style: styles.guideNumber,
						text: String(number)
					})
				),
				View({style: {backgroundColor: "#ffffff", flex:1 }},
					BBCodeText({
						style: {fontSize: 16, color: "#333333"},
						value: text
					})
				),
			)
		}

	}

	/**
	 * @class QRCodeAuthScanner
	 */
	class QRCodeAuthScanner extends LayoutComponent
	{
		/**
		 *
		 * @param props
		 * @param {LayoutComponent} description
		 */
		constructor(props, description)
		{
			super(props);
			this.onsuccess = props["onsuccess"] || function(){};
			this.description = description;
			this.redirectUrl = props["redirectUrl"] || "";
			this.isExternalScan = Boolean(props["external"]);
			this.setState({external: this.isExternalScan}, ()=>{
				if (props["urlData"] && props["urlData"]["url"]) {
					this.onResult({value: props["urlData"]["url"]})
				}
			})
		}

		render()
		{
			if(this.isExternalScan) {
				return View({
					style: {
						justifyContent:'top',
						padding:50,
						alignItems:'center'
					}
				}, Image({
					style:{
						opacity: 0.8,
						width:200,
						height:200
					},
					svg:{uri: `${currentDomain}${pathToExtension}images/qr.svg`}
				}))
			}

			return View({
					style: {
						flexDirection: 'column',
					}
				},
				this.description,
				new QRCodeGuide(),
				this.cameraView()
			);
		}

		cameraView()
		{
			return View(
				{
					style: {
						height: "80%",
						backgroundColor: "#000000",
						alignItems: 'center',
						justifyContent: "center",
					}
				},
				CameraView({
					style: {
						height: "100%",
						width: "100%",
						backgroundColor: "#000000"
					},
					scanTypes: ["qr_code"],
					result: this.onResult.bind(this),
					error: error => console.error(error),
					ref: ref => this.cameraRef = ref
				}),
				View({
					style:{
						position: "absolute",
						height: "100%",
						width: "100%",
						opacity: 0.0,
						borderRadius:0,
						justifyContent: "center",
						backgroundColor:"#9DCF00"
					},
					ref: view => {
						this.successOverlay = view;
					}
					},Image({
						style:{
							alignSelf:'center',
							alignItems: "center",
							resizeMode:'contain',
							width:180,
							height:180
						},
						svg:{uri: `${currentDomain}${pathToExtension}images/success.svg?2`}
					})
				)
			)
		}

		onResult({value})
		{
			if(this.cameraRef)
				this.cameraRef.setScanEnabled(false);
			Notify.showIndicatorLoading();

			qrauth.authorizeByUrl(value, this.redirectUrl)
				.then(() => {
					this.onsuccess();
					Notify.hideCurrentIndicator();


					if (this.successOverlay) {
						this.successOverlay.animate({
							duration:1000,
							opacity:0.8,
							top:0
						})
					}

					setTimeout(() => layout.close(), 1000);
				})
				.catch(error => {
					Notify.showIndicatorError({text: error.message, hideAfter: 2000});
					if(this.cameraRef)
						setTimeout(() => this.cameraRef.setScanEnabled(true), 3000);
				})
		}
	}

	jnexport(QRCodeAuthScanner)

})();
