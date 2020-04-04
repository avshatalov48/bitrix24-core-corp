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

		let startCount = Application.storage.getNumber("start_count", 0);
		if(startCount < 5)
		{
			Application.storage.setNumber("start_count", ++startCount);
		}

		let shown = false;
		let initSpotlight = result =>{
			if(shown || !result["releaseStressLevel"])
				return;
			ifApi(31, ()=>{
				shown = true;
				setTimeout(()=>{
					let seen = Application.storage.getBoolean("seen_stress_spotlight", false);
					if(!seen && startCount >= 5)
					{
						if(!PageManager.getNavigator().isVisible() || !PageManager.getNavigator().isActiveTab())
						{
							let spotlight = dialogs.createSpotlight();
							spotlight.setTarget("more");
							spotlight.setHint({text:BX.message("WELLTORY_SPOTLIGHT"), icon:"lightning"});
							spotlight.show();
							Application.storage.setBoolean("seen_stress_spotlight", true);
						}
					}
				}, 100);
			});
		};

		initSpotlight(result);
		BX.addCustomEvent("onMenuResultUpdated", newResult => initSpotlight(newResult));
	})
})();


