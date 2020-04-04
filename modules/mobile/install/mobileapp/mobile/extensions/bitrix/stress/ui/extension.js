/**
* @bxjs_lang_path extension.php
*/
(()=>{

	this.stressDesc = (color, value)=>{

		color = color.toUpperCase();
		value = Number(value);
		let intervals = {
			YELLOW: [[10,20], [56,60], [61,69]],
			GREEN: [[21,30], [31,44], [45,55]],
			RED:[[61,69],[70,80],[81,89],[90,98],[99,100],[0,9],[10,20]]
		};

		let values = intervals["RED"].concat(intervals["YELLOW"]).concat(intervals["GREEN"]);
		let message = "";
		for (let i = 0; i < values.length; i++)
		{
			if(value>=values[i][0] && value<=values[i][1])
			{
				let interval =`${values[i][0]}-${values[i][1]}`;
				message = BX.message(`STRESS_LEVEL_${color}_${interval}`);
				if(typeof message === "undefined" || message === "")
				{
					message = Object.keys(intervals).reduce((res, color)=>{
						let message =  BX.message(`STRESS_LEVEL_${color}_${interval}`);
						if(typeof message !== "undefined" && message !== "" && res === "")
						{
							res = message;
						}

						return res;

					}, "")
				}
				break;
			}
		}

		return message;
	};

	this.stressDesc = stressDesc;



	this.stressIndication =  {
		green: "#9DCF00",
		yellow: "#F7A700",
		red: "#FF5752",
		unknown: "#C8CBCE",
		getDesc:(color, value) => ({
			style: {cornerRadius: 15, backgroundColor: stressIndication[color]},
			text: stressDesc(color, value)
		})
	};

	this.Buttons = class {
		static blueButton(text, id, margin = {top: 16}, params = {})
		{
			return Object.assign({
				text: text,
				id: id,
				style: {
					cornerRadius: 5,
					font: {color: "#ffffff", size: 18, fontStyle: "semibold"},
					margin: margin,
					size: {
						height: 48,
						width: 100
					},
					backgroundColor: "#3BC8F5",
					backgroundColorActive: "#3195b9",
				}
			}, params)
		}

		static grayBorderedButton(text, id, margin = {top: 16}, params)
		{
			return Object.assign({
				text: text,
				id: id,
				style: {
					cornerRadius: 5,
					font: {color: "#525C69", size: 18, fontStyle: "semibold"},
					border: {
						width: 1,
						color: "#8C525C69",
					},
					margin: margin,
					size: {
						height: 48,
						width: 100
					},
					backgroundColor: "#ffffff",
					backgroundColorActive: "#b7b7b7",
				}
			}, params)
		}

		static greyButton(text, id, margin = {top: 16}, params = {})
		{
			return Object.assign({
				text: text,
				id: id,
				style: {
					cornerRadius: 5,
					font: {color: "#525C69", size: 18, fontStyle: "semibold"},
					image: {
						name: "status_online"
					},
					border: {
						width: 0,
						color: "#8C525C69",
					},
					margin: margin,
					size: {
						height: 48,
						width: 100
					},
					backgroundColor: "#ffffff",
					backgroundColorActive: "#fb0000",
				}
			}, params);
		}

		static linkButton(text, id, margin = {top: 14})
		{
			return {
				text: text,
				id: id,
				style: {
					cornerRadius: 5,
					font: {color: "#2066B0", size: 18, fontStyle: "normal"},
					margin: margin,
					size: {
						height: 48,
						width: 100
					},
					backgroundColor: "#ffffff",
					backgroundColorActive: "#fb0000",
				}
			}
		}
	};

	/**
	 * @readonly
	 * @typedef {Object} StateTemplates
	 * @enum {StateTemplates}
	 */
	this.StateTemplates = {
		Clear: () =>
		{
			return {
				"state": {
					value: -1000,
					labels: {},
				},
				"labels": [
					{
						text: BX.message("STRESS_DO_MEASURE_RIGHT_NOW"),
						style: {
							margin: {top: 50},
							font: {color: "#333333", size: 20, fontStyle: "normal"},
						}
					},
					{
						text: BX.message("STRESS_INFLUENT_STRESS_LEVEL"),
						style: {
							margin: {top: 14},
							font: {color: "#8C525C69", size: 17, fontStyle: "normal"},
						}
					}
				],
				"buttons": [
					Buttons.blueButton(BX.message("STRESS_MAKE_MEASURE"), "measure", {top: 50})
				],
				"footer":{ text:BX.message("WELLTORY_PROVIDED"),  style:{font:{color:"#525C69", size:15}}}
			}
		},
		NewMeasureResult: (params) =>
		{
			return {
				"state": {
					value: -1000,
					labels: {},
				},
				"labels": [
					{
						text: BX.message("STRESS_WHAT_DOES_IT_MEAN"),
						id: "show_description",
						params: params,
						style: {
							cornerRadius: 5,
							font: {color: "#2066B0", size: 18, fontStyle: "normal"},
							margin: {top: 10},
						}
					}
				],
				"buttons": [
					Buttons.blueButton(BX.message("STRESS_SHARE_TO_PROFILE"), "save_result", {top: 32}, {params}),
					Buttons.grayBorderedButton(BX.message("STRESS_SAVE_PRIVATE"), "save_private", {top: 16}, {params}),
					Buttons.greyButton(BX.message("STRESS_CANCEL_MEASURE_RESULT"), "cancel_measure")
				],
				"footer":{ text:BX.message("WELLTORY_PROVIDED"),  style:{font:{color:"#73525C69", size:15}}}
			}
		},
		ExistsMeasureResult: (params) =>
		{
			return {
				"state": {
					value: -1000,
					labels: {},
				},
				"labels": [
					{
						text: BX.message("STRESS_WHAT_DOES_IT_MEAN"),
						id: "show_description",
						params: params,
						style: {
							cornerRadius: 5,
							font: {color: "#2066B0", size: 18, fontStyle: "normal"},
							margin: {top: 10},
						}
					}
				],
				"buttons": [
					Buttons.blueButton(BX.message("STRESS_SHARE"), "share", {top: 32}, {params}),
					Buttons.grayBorderedButton(BX.message("STRESS_MAKE_ANOTHER_ONE_MEASURE"), "measure"),
				],
				"footer":{ text:BX.message("WELLTORY_PROVIDED"),  style:{font:{color:"#73525C69", size:15}}}
			}
		},
		StressLevelDescription: () =>
		{
			return {
				"state": {
					value: -1000,
					labels: {},
				},
				"labels": [
					{
						text: "",
						style: {
							font: {color: "#333333", size: 26, fontStyle: "normal"},
							margin: {top: 10},
							alignment: "left"
						}
					},
					{
						text: "",
						style: {
							font: {color: "#525C69", size: 16, fontStyle: "normal"},
							margin: {top: 10},
							alignment:"left"
						}
					}
				],
				"footer":{}

			}
		}
	};

	this.StateUtils = class
	{
		static exitingMeasure(rowState)
		{
			return StateUtils._measure(rowState, StateTemplates.ExistsMeasureResult(rowState));
		}

		static newMeasure(rowState)
		{
			return StateUtils._measure(rowState, StateTemplates.NewMeasureResult(rowState));
		}

		static stressDescription(rowState, description = "")
		{
			let data = StateUtils.preparedState(rowState);
			let template = StateTemplates.StressLevelDescription();
			delete  data.state["date"];
			template.state = data.state;
			template.state.date = undefined;
			template.labels[0].text = data.comment;
			template.labels[1].text = description;
			return template;
		}

		/**
		 *
		 * @param rowState
		 * @param template
		 * @return {StateTemplates}
		 */
		static _measure(rowState, template)
		{
			let data = StateUtils.preparedState(rowState);
			template.state = data.state;
			template.labels.unshift({
				text: data["comment"],
				style: {
					font: {color: "#333333", size: 25, fontStyle: "normal"},
					margin: {top: 16},
				}
			});

			return template;
		}

		static preparedState(rowData = null)
		{
			if (rowData == null)
			{
				return null;
			}

			let state = {
				value: 0,
				labels: {}
			};

			let comment = "";

			if (rowData.value)
			{
				state.value = rowData.value;
			}

			if (rowData.type && stressIndication[rowData.type])
			{
				state.labels.title = stressIndication.getDesc(rowData.type, rowData.value);
			}

			if (rowData.date)
			{
				state.labels.subtitle = {
					text: new Date(rowData.date).toLocaleString(),
					style: {font: {color: "#8C525C69", size: 14}}
				};
			}

			if (rowData.comment)
			{
				comment = rowData.comment;
			}

			return {state, comment};
		}
	};

	this.openStressWidget = (initData = null, shouldLoad = true) =>
	{
		let params = {
			scriptPath: availableComponents["stress"].publicUrl,
			componentCode: "stress",
			params: {
				params: {
					setCurrentData: false,
					currentData: initData,
					shouldLoad
				}
			},
			rootWidget: {
				name: "stress",
				settings: {
					title: BX.message("STRESS_LEVEL_TITLE"),
					objectName: "stress",
				}
			}
		};

		if(initData !== null)
		{
			params.rootWidget.settings.data = StateUtils.exitingMeasure(initData)
		}

		PageManager.openComponent("JSStackComponent", params);
	};

})();