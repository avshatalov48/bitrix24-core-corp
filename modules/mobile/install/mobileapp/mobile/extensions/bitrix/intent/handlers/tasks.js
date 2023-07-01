BX.addCustomEvent("onIntentHandle", intent => {
	/** @var {MobileIntent} intent */
	intent.addHandler( () => {
		intent.notify("preset_task", "preset_task_"+ Application.getPlatform())
	})
});