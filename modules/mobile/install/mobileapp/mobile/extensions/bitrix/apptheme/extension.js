/**
 * @module apptheme
 * @return {Object}
 */
jn.define("apptheme", function (require, exports, module) {
	const themes = {
		"light": {
			"accentBrandBlue": "#2FC6F6",
			"accentBrandGreen": "#BBED21",
			"accentExtraAqua": "#55D0E0",
			"accentExtraBrown": "#AE914B",
			"accentExtraDarkblue": "#0091E3",
			"accentExtraGrass": "#29AD49",
			"accentExtraPink": "#FB6DBA",
			"accentExtraPurple": "#C15FD2",
			"accentMainAlert": "#FF5752",
			"accentMainLinks": "#2066B0",
			"accentMainPrimary": "#00A2E8",
			"accentMainPrimaryalt": "#29A8DF",
			"accentMainSuccess": "#9DCF00",
			"accentMainWarning": "#FFA900",
			"accentSoftBlue1": "#C3F0FF",
			"accentSoftBlue2": "#E5F9FF",
			"accentSoftBlue3": "#ECFAFE",
			"accentSoftElementBlue1": "#00789E",
			"accentSoftElementGreen1": "#688800",
			"accentSoftElementOrange1": "#9F6A00",
			"accentSoftElementRed1": "#C21B16",
			"accentSoftGray2": "#FCFCFD",
			"accentSoftGreen1": "#E2F1B3",
			"accentSoftGreen2": "#F1FBD0",
			"accentSoftGreen3": "#F5FCDE",
			"accentSoftOrange1": "#FFE1A6",
			"accentSoftOrange2": "#FFF1D6",
			"accentSoftOrange3": "#FFF5E3",
			"accentSoftRed1": "#FFCDCC",
			"accentSoftRed2": "#FFE8E8",
			"accentSoftRed3": "#FFF0F0",
			"base0": "#000000",
			"base1": "#333333",
			"base2": "#525C69",
			"base3": "#828B95",
			"base4": "#A8ADB4",
			"base5": "#C9CCD0",
			"base6": "#DFE0E3",
			"base7": "#EDEEF0",
			"base8": "#FFFFFF",
			"baseBlackFixed": "#000000",
			"baseWhiteFixed": "#FFFFFF",
			"bgContentPrimary": "#FFFFFF",
			"bgContentSecondary": "#FCFCFD",
			"bgContentTertiary": "#F5F5F7",
			"bgNavigation": "#F5F7F8",
			"bgPrimary": "#F1F4F6",
			"bgSecondary": "#F1F4F6",
			"bgSeparatorPrimary": "#E6E7E9",
			"bgSeparatorSecondary": "#E6E7E9",
			"graphicsBase1": "#FFFFFF",
			"graphicsBase2": "#BDC1C6",
			"graphicsBase3": "#EBECEE",
			"techFocus": "#0D000000",
			"techOpacity": "#00FFFFFF",
			"techOverlay": "#99000000",
			"techPush": "#14000000",
			"techTest": "#FB30FF"
		},
		"dark": {
			"accentBrandBlue": "#24B4E1",
			"accentBrandGreen": "#9CC51F",
			"accentExtraAqua": "#47AAB7",
			"accentExtraBrown": "#8E7840",
			"accentExtraGrass": "#248E3E",
			"accentExtraPink": "#CC5B99",
			"accentExtraPurple": "#8966B5",
			"accentMainAlert": "#CE4845",
			"accentMainLinks": "#3BA5E1",
			"accentMainPrimary": "#0082BA",
			"accentMainPrimaryalt": "#1181B1",
			"accentMainSuccess": "#779E00",
			"accentMainWarning": "#CE8A03",
			"base0": "#FFFFFF",
			"base1": "#F8F8F8",
			"base2": "#CBCED1",
			"base3": "#A7ABAF",
			"base4": "#82878B",
			"base5": "#65696F",
			"base6": "#54595F",
			"base7": "#363C41",
			"base8": "#242A2F",
			"baseBlackFixed": "#000000",
			"baseWhiteFixed": "#FFFFFF",
			"bgContentPrimary": "#1B1F23",
			"bgContentSecondary": "#22272B",
			"bgContentTertiary": "#262B2F",



			"accentSoftBlue1": "#234A5E",
			"accentSoftBlue2": "#1B2B34",
			"accentSoftBlue3": "#192229",
			"accentSoftElementBlue1": "#2392C4",
			"accentSoftElementGreen1": "#9FC037",
			"accentSoftElementOrange1": "#CA8D17",
			"accentSoftElementRed1": "#D95451",
			"accentSoftGray2": "#22272B",
			"accentSoftGreen1": "#485723",
			"accentSoftGreen2": "#262C19",
			"accentSoftGreen3": "#1F2418",
			"accentSoftOrange1": "#644B16",
			"accentSoftOrange2": "#372B11",
			"accentSoftOrange3": "#272112",
			"accentSoftRed1": "#6A302E",
			"accentSoftRed2": "#371B1B",
			"accentSoftRed3": "#271719",

			"bgNavigation": "#23272C",
			"bgPrimary": "#101214",
			"bgSecondary": "#101214",
			"bgSeparatorPrimary": "#2E3237",
			"bgSeparatorSecondary": "#2E3237",
			"graphicsBase1": "#6A737F",
			"graphicsBase2": "#BDC1C6",
			"graphicsBase3": "#959CA4",
			"techFocus": "#1AFFFFFF",
			"techOpacity": "#00FFFFFF",
			"techOverlay": "#D9000000",
			"techPush": "#24FFFFFF",
			"techTest": "#4DFF30"
		}
	};

	const nativeAppTheme = jn.require("native/apptheme")?.AppTheme
	let componentTokens = {
		dark: {},
		light: {},
	}
	class AppTheme {
		static get colors() {
			let systemColors= themes["light"];
			let customTokens = componentTokens[AppTheme.id];
			if (nativeAppTheme) {
				systemColors = nativeAppTheme.getColors()
			}

			return {...customTokens, ... systemColors}
		}

		static get id() {
			if (nativeAppTheme) {
				return nativeAppTheme.getId()
			}

			return "light";
		}

		static extend(namespace, values = {}) {
			Object.keys(values).forEach( token => {
				let colors = values[token];
				if (colors.length >= 2) {
					componentTokens.light[`${namespace}${token}`] = colors[0];
					componentTokens.dark[`${namespace}${token}`] = colors[1];
				}
			})
		}

		static setId(id = null) {
			if (nativeAppTheme) {
				return nativeAppTheme.setId(id)
			}
		}


		static toggle(ontoggle = null) {
			let currentId = AppTheme.id
			let newId = currentId === "dark" ? "light": "dark"
			if (nativeAppTheme) {
				nativeAppTheme.setId(newId)
			}

			if (ontoggle != null && typeof ontoggle === "function") {
				ontoggle.apply(null, [newId])
			}
			else
			{
				reload()
			}
		}
 	}

	module.exports = AppTheme
});