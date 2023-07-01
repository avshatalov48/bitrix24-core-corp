/**
 * @bxjs_lang_path component.php
 * @var {Notify} notify
 */

(()=>{
	const { TabPresetsComponent } = jn.require("tab.presets")
	const component = new TabPresetsComponent({}, layout)
	layout.showComponent(component)
})();
