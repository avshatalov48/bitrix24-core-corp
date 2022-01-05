(function () {

	include("SharedBundle")

	function getDomain() {
		let domain = currentDomain
		const regex = /^.+\.(bitrix24\.\w+|br\.\w+)$/i;
		let components = domain.match(regex)
		if (components != null && components.length === 2) {
			return components[1];
		}
		return domain;
	}

	const cloud = Boolean(this.jnExtensionData.get("qrauth")["cloud"])
	const pathToExtension = `/bitrix/mobileapp/mobile/extensions/bitrix/qrauth/`;
	const styles = {
		guideNumber: {
			textAlign: "center",
			borderRadius: 13,
			backgroundColor: "#D5F4FD",
			fontSize: 14,
			fontWeight: "bold",
			color: "#333333",
			width: 26,
			height: 26,
			marginRight: 10,
		},
		hint:{
			height:54,
			paddingLeft: 24,
			backgroundColor: '#D5F4FD'
		},
		browserNote: {
			opacity: 0.5,
			color: "#000000",
			fontSize: 12,
		}
	}
	const guideStepsTitles = [
		BX.message("STEP_OPEN_SITE").replace("#DOMAIN#", getDomain()),
		BX.message(cloud ? "STEP_PRESS_CLOUD" : "STEP_PRESS_SELF_HOSTED").replace("#URL#", `${currentDomain}${pathToExtension}images/qrinline.png`),
		BX.message("STEP_SCAN")
	]

	class QRCodeGuide extends LayoutComponent
	{
		constructor({showHint})
		{
			super({showHint});
			this.setState({showHint: Boolean(showHint)})
		}

		render() {
			let {showHint} = this.state
			return View({},
				showHint ? this.hint() : null,
				View({
						style: {
							marginTop: 12,
							marginLeft: 18,
							marginRight: 18,
							marginBottom:30,
						}
					},
					Text({ style:{fontSize: 18, paddingBottom: 18}, text:BX.message("QR_HOW_TO_AUTH")+"↓"}),
					cloud && Application.getApiVersion() >=41 ? this.demoVideo(): null,
					this.guideSteps(guideStepsTitles)
				)
			);
		}

		guideSteps(points) {
			return View({
				style: {
					marginTop: 12
				},
			}, ...points.map( (text, index) => this.guidePoint(index+1, text, index + 1 < points.length)))
		}

		demoVideo()
		{
			return View({
					style:{
						height: 144,
						borderRadius: 6,
						borderWidth: 2,
						borderColor: "#C0C9CE",
					}
				},
				Video(
					{
						style: {
							height: 144,
							backgroundColor: "#ffffff"
						},
						onReadyPlay: () => {
							console.log("can play")
						},
						scaleMode: "fit",
						uri: sharedBundle.getVideo("demo.mp4"),
						enableControls: false,
						loop: true,
					}
				))
		}

		hint(){
			return View({
				style: styles.hint
				},View({
						style: {
							flex: 1,
							alignItems: 'center',
							flexDirection: 'row'
						}
					},
					Image({
						resizeMode: 'contain',
						style: {
							width: 23,
							height: 23
						},
						svg: {uri:`${currentDomain}${pathToExtension}images/hint.svg?2`}
					}),
				Text({
					style: {flex:1, fontSize: 15, color: "#333333", marginLeft: 18},
					text: BX.message("QR_SCANNER_HINT")
				})

				)

			)
		}

		guidePoint(number, text, showBorder = false)
		{
			return View({
					style: {
						height:40,
						justifyContent:"center",
						alignItems:'center',
						flexDirection: 'row',
						justifyContent: "flex-start"
					}
				},
				Text({
					style: styles.guideNumber,
					text: String(number)
				}),
				View({style: {flex:1, justifyContent:"center" }},
					View({style:{justifyContent:"center", height:40}},
						BBCodeText({
							style: {fontSize: 15, color: "#333333"},
							value: text
						})
					),
					showBorder ? View({style:{ height:1, backgroundColor: "#EBEBEB"}}) : null
				),
			)
		}

	}

	/**
	 * @class QRCodeAuthComponent
	 */
	class QRCodeAuthComponent extends LayoutComponent
	{
		/**
		 *
		 * @param props
		 * @param {LayoutComponent} description
		 */
		constructor({redirectUrl, showHint}, description)
		{
			super({redirectUrl, showHint});
			this.description = description;
			this.redirectUrl = redirectUrl ? redirectUrl : "";
			this.showHint = Boolean(showHint)
		}

		render()
		{
			return View({ style: { flexDirection: 'column' } },
				this.description,
				new QRCodeGuide({showHint: this.showHint}),
				this.scanButton()
			);
		}

		scanButton(){

			return View({
					style: {
						justifyContent: "center",
						alignSelf: "center",
						borderColor: "#00A2E8",
						borderRadius:6,
						borderWidth:1,
						backgroundColor:{ default: "#ffffff", pressed: "#f0f0f0" },
						height:40,
						width:284,
						alignItems: "center"
					},
					onClick:()=>{
						PageManager.openWidget("layout",{
							title: BX.message("STEP_CAMERA_TITLE"),
							onReady:ui => {
								let component = new QRCodeScannerComponent({redirectUrl: this.redirectUrl, ui})
								ui.showComponent(component)
							}
						})
					}
				},
				View({
						style: {
							flex: 1,
							flexDirection: 'row',
						}
					},
					Image({
						resizeMode: 'contain',
						style: {
							alignSelf: 'center',
							alignItems: "center",
							width: 20,
							height: 20
						},
						svg: {uri: `${currentDomain}${pathToExtension}images/photo.svg?2`}
					}),
					Text({
						style: {fontSize: 17, color: "#525C69", marginLeft: 8, fontWeight:'500'},
						text: BX.message("SCAN_QR_BUTTON")
					})
				)
			)
		}
	}


	/**
	 * @class QRCodeScannerComponent
	 */
	class QRCodeScannerComponent extends LayoutComponent {
		constructor(props)
		{
			let {
				ui,
				redirectUrl = "",
				external = false,
				url = null,
				onsuccess = function (){}
			} = props
			super(props);

			this.redirectUrl = redirectUrl
			this.ui = ui
			this.onsuccess = onsuccess;
			this.url = url
			this.setState({external}, ()=>{
				if (url) {
					this.onResult({value: url})
				}
			})
		}

		render() {
			let {external = false} = this.state;
			if(external)
			{
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
			else
			{
				return View({}, this.cameraView())
			}
		}

		cameraView()
		{

			return View(
				{
					style: {
						backgroundColor: "#ffffff",
						alignItems: 'center',
						justifyContent: "center",
						padding:10,
					}
				},
				CameraView({
					style: {
						borderRadius: 12,
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
							borderRadius:12,
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
							opacity:0.8
						})
					}


					setTimeout(() => {
						this.ui.close();
						this.ui = null
					}, 1000);
				})
				.catch(error => {
					Notify.showIndicatorError({text: error.message, hideAfter: 2000});
					if(this.cameraRef)
						setTimeout(() => this.cameraRef.setScanEnabled(true), 3000);
				})
		}
	}


	jnexport(QRCodeAuthComponent, QRCodeScannerComponent)

})();
