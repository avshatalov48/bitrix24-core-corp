/**
* @bxjs_lang_path extension.php
*/
(()=>
{
	BX.addCustomEvent("onMenuLoaded", ()=>{
		BX.addCustomEvent("onStressMeasureChanged", params=>{
			window.stressResult = params;
			let color = stressIndication[params["type"]];
			let value = params["value"];
			let data = {tag: value+"%", styles: {tag: {backgroundColor: color, cornerRadius: 15}}};
			updateMenuItem("stress", data);
			BX.onCustomEvent("shouldReloadMenu", []);
		});
	})
})();


