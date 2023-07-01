/**
 * @module im/calls-card/card-content/elements/avatar
 */
jn.define('im/calls-card/card-content/elements/avatar', (require, exports, module) => {
	const pathToExtension = '/bitrix/mobileapp/immobile/extensions/im/calls-card/card-content/';
	const clientBackground = `${currentDomain}${pathToExtension}images/client-background.png`;
	const clientIcon = `<svg width="67" height="73" viewBox="0 0 67 73" fill="none" xmlns="http://www.w3.org/2000/svg"><g filter="url(#filter0_d_1367_123739)"><path d="M58.6932 62.7696C61.7652 61.8132 63.449 58.7798 62.8259 55.7777L61.9501 51.5583C61.51 48.9207 58.271 45.9597 51.0259 44.2201C48.5713 43.5844 46.2379 42.5986 44.1091 41.2979C43.6436 41.0509 43.7144 38.7686 43.7144 38.7686L41.3809 38.4388C41.3809 38.2536 41.1814 35.5167 41.1814 35.5167C43.9733 34.6455 43.6861 29.5068 43.6861 29.5068C45.4591 30.42 46.6138 26.3534 46.6138 26.3534C48.7109 20.7035 45.5695 21.0451 45.5695 21.0451C46.1191 17.596 46.1191 14.0906 45.5695 10.6415C44.1728 -0.800155 23.1452 2.30596 25.6385 6.04275C19.4929 4.99162 20.8953 17.9758 20.8953 17.9758L22.2282 21.3381C20.3806 22.4509 20.7435 23.728 21.1488 25.1545C21.3177 25.7493 21.4941 26.37 21.5207 27.0156C21.6495 30.2557 23.7848 29.5843 23.7848 29.5843C23.9164 34.932 26.7564 35.6283 26.7564 35.6283C27.2899 38.9867 26.9573 38.4152 26.9573 38.4152L24.43 38.699C24.4643 39.4627 24.3973 40.2275 24.2305 40.976C22.7621 41.5837 21.8632 42.0673 20.9731 42.546C20.0619 43.0361 19.16 43.5213 17.6661 44.1294C11.9606 46.4511 6.23857 47.7507 5.13625 51.816C4.88272 52.751 4.51666 54.3583 4.16474 56.0583C3.56956 58.9335 5.24598 61.7744 8.18468 62.6994C15.3494 64.9545 23.3072 66.284 31.7055 66.4544H35.3967C43.7075 66.2858 51.5869 64.9821 58.6932 62.7696Z" fill="white"/></g><defs><filter id="filter0_d_1367_123739" x="0.0454102" y="0.63623" width="66.9092" height="71.8184" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="2"/><feGaussianBlur stdDeviation="2"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.12 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1367_123739"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1367_123739" result="shape"/></filter></defs></svg>`;
	const { CallsCardType } = require('im/calls-card/card-content/enum');

	const avatarSize = 243;
	const iconSize = 108;

	const Avatar = ({ avatarUrl = null, type = null }) => {
		return View(
			{
				style: {
					width: avatarSize,
					height: avatarSize,
					alignSelf: 'center',
					justifyContent: 'center',
					alignItems: 'center',
					position: 'absolute',
					top: 0,
					left: (device.screen.width / 2) - avatarSize / 2,
				},
			},
			View(
				{
					style: {
						height: avatarSize,
						width: avatarSize,
					},
				},
				(type !== CallsCardType.finished && type !== CallsCardType.started) && LottieView(
					{
						style: {
							height: avatarSize,
							width: avatarSize,
						},
						data: {
							content: '{"v":"5.9.0","fr":25,"ip":0,"op":41,"w":243,"h":243,"nm":"Call_User","ddd":0,"assets":[],"layers":[{"ddd":0,"ind":1,"ty":4,"nm":"Vector 5","sr":1,"ks":{"o":{"a":1,"k":[{"i":{"x":[0.833],"y":[0.833]},"o":{"x":[0.167],"y":[0.167]},"t":15,"s":[100]},{"t":41,"s":[0]}],"ix":11},"r":{"a":0,"k":0,"ix":10},"p":{"a":0,"k":[121.5,121.5,0],"ix":2,"l":2},"a":{"a":0,"k":[0,0,0],"ix":1,"l":2},"s":{"a":1,"k":[{"i":{"x":[0.667,0.667,0.667],"y":[1,1,1]},"o":{"x":[0.775,0.775,0.333],"y":[0,0,0]},"t":15,"s":[101.21,101.21,100]},{"t":41,"s":[191.575,191.575,100]}],"ix":6,"l":2}},"ao":0,"sy":[{"c":{"a":0,"k":[1,1,1,0.639999985695],"ix":2},"o":{"a":0,"k":64,"ix":3},"a":{"a":0,"k":135,"ix":5},"s":{"a":0,"k":32,"ix":8},"d":{"a":0,"k":2.828,"ix":6},"ch":{"a":0,"k":0,"ix":7},"bm":{"a":0,"k":1,"ix":1},"no":{"a":0,"k":0,"ix":9},"ty":2,"nm":"Inner Shadow"}],"shapes":[{"ty":"gr","it":[{"ind":0,"ty":"sh","ix":1,"ks":{"a":0,"k":{"i":[[0,-29.823],[29.824,0],[0,29.824],[-29.823,0]],"o":[[0,29.824],[-29.823,0],[0,-29.823],[29.824,0]],"v":[[54,0],[0,54],[-54,0],[0,-54]],"c":true},"ix":2},"nm":"Path 1","mn":"ADBE Vector Shape - Group","hd":false},{"ty":"gf","o":{"a":0,"k":100,"ix":10},"r":1,"bm":0,"g":{"p":5,"k":{"a":0,"k":[0,0.184,0.776,0.965,0.267,0.244,0.803,0.698,0.533,0.304,0.829,0.431,0.767,0.519,0.85,0.367,1,0.733,0.871,0.302],"ix":9}},"s":{"a":0,"k":[-13.897,-15.484],"ix":5},"e":{"a":0,"k":[62.474,-15.484],"ix":6},"t":2,"h":{"a":0,"k":0,"ix":7},"a":{"a":0,"k":0,"ix":8},"nm":"Gradient Fill 1","mn":"ADBE Vector Graphic - G-Fill","hd":false},{"ty":"tr","p":{"a":0,"k":[0,0],"ix":2},"a":{"a":0,"k":[0,0],"ix":1},"s":{"a":0,"k":[100,100],"ix":3},"r":{"a":0,"k":0,"ix":6},"o":{"a":0,"k":100,"ix":7},"sk":{"a":0,"k":0,"ix":4},"sa":{"a":0,"k":0,"ix":5},"nm":"Transform"}],"nm":"Vector","np":2,"cix":2,"bm":0,"ix":1,"mn":"ADBE Vector Group","hd":false}],"ip":15,"op":41,"st":15,"bm":0},{"ddd":0,"ind":2,"ty":4,"nm":"Vector 4","sr":1,"ks":{"o":{"a":1,"k":[{"i":{"x":[0.833],"y":[0.833]},"o":{"x":[0.167],"y":[0.167]},"t":10,"s":[100]},{"t":36,"s":[0]}],"ix":11},"r":{"a":0,"k":0,"ix":10},"p":{"a":0,"k":[121.5,121.5,0],"ix":2,"l":2},"a":{"a":0,"k":[0,0,0],"ix":1,"l":2},"s":{"a":1,"k":[{"i":{"x":[0.667,0.667,0.667],"y":[1,1,1]},"o":{"x":[0.775,0.775,0.333],"y":[0,0,0]},"t":10,"s":[101.21,101.21,100]},{"t":36,"s":[191.575,191.575,100]}],"ix":6,"l":2}},"ao":0,"sy":[{"c":{"a":0,"k":[1,1,1,0.639999985695],"ix":2},"o":{"a":0,"k":64,"ix":3},"a":{"a":0,"k":135,"ix":5},"s":{"a":0,"k":32,"ix":8},"d":{"a":0,"k":2.828,"ix":6},"ch":{"a":0,"k":0,"ix":7},"bm":{"a":0,"k":1,"ix":1},"no":{"a":0,"k":0,"ix":9},"ty":2,"nm":"Inner Shadow"}],"shapes":[{"ty":"gr","it":[{"ind":0,"ty":"sh","ix":1,"ks":{"a":0,"k":{"i":[[0,-29.823],[29.824,0],[0,29.824],[-29.823,0]],"o":[[0,29.824],[-29.823,0],[0,-29.823],[29.824,0]],"v":[[54,0],[0,54],[-54,0],[0,-54]],"c":true},"ix":2},"nm":"Path 1","mn":"ADBE Vector Shape - Group","hd":false},{"ty":"gf","o":{"a":0,"k":100,"ix":10},"r":1,"bm":0,"g":{"p":5,"k":{"a":0,"k":[0,0.184,0.776,0.965,0.267,0.244,0.803,0.698,0.533,0.304,0.829,0.431,0.767,0.519,0.85,0.367,1,0.733,0.871,0.302],"ix":9}},"s":{"a":0,"k":[-13.897,-15.484],"ix":5},"e":{"a":0,"k":[62.474,-15.484],"ix":6},"t":2,"h":{"a":0,"k":0,"ix":7},"a":{"a":0,"k":0,"ix":8},"nm":"Gradient Fill 1","mn":"ADBE Vector Graphic - G-Fill","hd":false},{"ty":"tr","p":{"a":0,"k":[0,0],"ix":2},"a":{"a":0,"k":[0,0],"ix":1},"s":{"a":0,"k":[100,100],"ix":3},"r":{"a":0,"k":0,"ix":6},"o":{"a":0,"k":100,"ix":7},"sk":{"a":0,"k":0,"ix":4},"sa":{"a":0,"k":0,"ix":5},"nm":"Transform"}],"nm":"Vector","np":2,"cix":2,"bm":0,"ix":1,"mn":"ADBE Vector Group","hd":false}],"ip":10,"op":36,"st":10,"bm":0},{"ddd":0,"ind":3,"ty":4,"nm":"Vector 3","sr":1,"ks":{"o":{"a":1,"k":[{"i":{"x":[0.833],"y":[0.833]},"o":{"x":[0.167],"y":[0.167]},"t":5,"s":[100]},{"t":31,"s":[0]}],"ix":11},"r":{"a":0,"k":0,"ix":10},"p":{"a":0,"k":[121.5,121.5,0],"ix":2,"l":2},"a":{"a":0,"k":[0,0,0],"ix":1,"l":2},"s":{"a":1,"k":[{"i":{"x":[0.667,0.667,0.667],"y":[1,1,1]},"o":{"x":[0.775,0.775,0.333],"y":[0,0,0]},"t":5,"s":[101.21,101.21,100]},{"t":31,"s":[191.575,191.575,100]}],"ix":6,"l":2}},"ao":0,"sy":[{"c":{"a":0,"k":[1,1,1,0.639999985695],"ix":2},"o":{"a":0,"k":64,"ix":3},"a":{"a":0,"k":135,"ix":5},"s":{"a":0,"k":32,"ix":8},"d":{"a":0,"k":2.828,"ix":6},"ch":{"a":0,"k":0,"ix":7},"bm":{"a":0,"k":1,"ix":1},"no":{"a":0,"k":0,"ix":9},"ty":2,"nm":"Inner Shadow"}],"shapes":[{"ty":"gr","it":[{"ind":0,"ty":"sh","ix":1,"ks":{"a":0,"k":{"i":[[0,-29.823],[29.824,0],[0,29.824],[-29.823,0]],"o":[[0,29.824],[-29.823,0],[0,-29.823],[29.824,0]],"v":[[54,0],[0,54],[-54,0],[0,-54]],"c":true},"ix":2},"nm":"Path 1","mn":"ADBE Vector Shape - Group","hd":false},{"ty":"gf","o":{"a":0,"k":100,"ix":10},"r":1,"bm":0,"g":{"p":5,"k":{"a":0,"k":[0,0.184,0.776,0.965,0.267,0.244,0.803,0.698,0.533,0.304,0.829,0.431,0.767,0.519,0.85,0.367,1,0.733,0.871,0.302],"ix":9}},"s":{"a":0,"k":[-13.897,-15.484],"ix":5},"e":{"a":0,"k":[62.474,-15.484],"ix":6},"t":2,"h":{"a":0,"k":0,"ix":7},"a":{"a":0,"k":0,"ix":8},"nm":"Gradient Fill 1","mn":"ADBE Vector Graphic - G-Fill","hd":false},{"ty":"tr","p":{"a":0,"k":[0,0],"ix":2},"a":{"a":0,"k":[0,0],"ix":1},"s":{"a":0,"k":[100,100],"ix":3},"r":{"a":0,"k":0,"ix":6},"o":{"a":0,"k":100,"ix":7},"sk":{"a":0,"k":0,"ix":4},"sa":{"a":0,"k":0,"ix":5},"nm":"Transform"}],"nm":"Vector","np":2,"cix":2,"bm":0,"ix":1,"mn":"ADBE Vector Group","hd":false}],"ip":5,"op":31,"st":5,"bm":0},{"ddd":0,"ind":4,"ty":4,"nm":"Vector 2","sr":1,"ks":{"o":{"a":1,"k":[{"i":{"x":[0.833],"y":[0.833]},"o":{"x":[0.167],"y":[0.167]},"t":0,"s":[100]},{"t":26,"s":[0]}],"ix":11},"r":{"a":0,"k":0,"ix":10},"p":{"a":0,"k":[121.5,121.5,0],"ix":2,"l":2},"a":{"a":0,"k":[0,0,0],"ix":1,"l":2},"s":{"a":1,"k":[{"i":{"x":[0.667,0.667,0.667],"y":[1,1,1]},"o":{"x":[0.775,0.775,0.333],"y":[0,0,0]},"t":0,"s":[101.21,101.21,100]},{"t":26,"s":[191.575,191.575,100]}],"ix":6,"l":2}},"ao":0,"sy":[{"c":{"a":0,"k":[1,1,1,0.639999985695],"ix":2},"o":{"a":0,"k":64,"ix":3},"a":{"a":0,"k":135,"ix":5},"s":{"a":0,"k":32,"ix":8},"d":{"a":0,"k":2.828,"ix":6},"ch":{"a":0,"k":0,"ix":7},"bm":{"a":0,"k":1,"ix":1},"no":{"a":0,"k":0,"ix":9},"ty":2,"nm":"Inner Shadow"}],"shapes":[{"ty":"gr","it":[{"ind":0,"ty":"sh","ix":1,"ks":{"a":0,"k":{"i":[[0,-29.823],[29.824,0],[0,29.824],[-29.823,0]],"o":[[0,29.824],[-29.823,0],[0,-29.823],[29.824,0]],"v":[[54,0],[0,54],[-54,0],[0,-54]],"c":true},"ix":2},"nm":"Path 1","mn":"ADBE Vector Shape - Group","hd":false},{"ty":"gf","o":{"a":0,"k":100,"ix":10},"r":1,"bm":0,"g":{"p":5,"k":{"a":0,"k":[0,0.184,0.776,0.965,0.267,0.244,0.803,0.698,0.533,0.304,0.829,0.431,0.767,0.519,0.85,0.367,1,0.733,0.871,0.302],"ix":9}},"s":{"a":0,"k":[-13.897,-15.484],"ix":5},"e":{"a":0,"k":[62.474,-15.484],"ix":6},"t":2,"h":{"a":0,"k":0,"ix":7},"a":{"a":0,"k":0,"ix":8},"nm":"Gradient Fill 1","mn":"ADBE Vector Graphic - G-Fill","hd":false},{"ty":"tr","p":{"a":0,"k":[0,0],"ix":2},"a":{"a":0,"k":[0,0],"ix":1},"s":{"a":0,"k":[100,100],"ix":3},"r":{"a":0,"k":0,"ix":6},"o":{"a":0,"k":100,"ix":7},"sk":{"a":0,"k":0,"ix":4},"sa":{"a":0,"k":0,"ix":5},"nm":"Transform"}],"nm":"Vector","np":2,"cix":2,"bm":0,"ix":1,"mn":"ADBE Vector Group","hd":false}],"ip":0,"op":26,"st":0,"bm":0}],"markers":[]}\n',
						},
						params: {
							loopMode: "loop"
						},
						autoPlay: true,
					}
				),
			),
			View(
				{
					style: {
						width: avatarSize,
						height: avatarSize,
						marginTop: -avatarSize,
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				avatarUrl
					? Image({
						style: {
							width: iconSize,
							height: iconSize,
							borderRadius: iconSize / 2,
						},
						resizeMode: 'cover',
						uri: avatarUrl,
					})
					: View(
						{
							style: {
								backgroundImage: !avatarUrl && clientBackground,
								backgroundResizeMode: 'cover',
								width: iconSize,
								height: iconSize,
								justifyContent: 'center',
								alignItems: 'center',
							},
						},
						Image({
							style: {
								width: 67,
								height: 73,
							},
							svg: {
								content: clientIcon,
							},
						})
					),
			)
		);
	};

	module.exports = { Avatar };
});