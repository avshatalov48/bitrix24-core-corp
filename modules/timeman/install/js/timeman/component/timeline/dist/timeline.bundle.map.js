{"version":3,"sources":["timeline.bundle.js"],"names":["this","BX","Timeman","exports","timeman_timeformatter","ui_vue_components_hint","ui_vue","Interval","BitrixVue","localComponent","props","type","String","start","Date","finish","size","Number","fixedSize","Boolean","showStartMarker","showFinishMarker","clickable","hint","isFirst","isLast","computed","intervalItemClass","intervalInlineStyle","style","width","methods","toShortTime","time","TimeFormatter","isInit","toShort","date","format","intervalClick","$emit","template","Chart","components","intervals","Array","fixedSizeType","readOnly","processedIntervals","_this","oneHour","map","interval","totalTime","reduce","sum","totalDynamicTime","filter","stretchable","lastStartMarkerTime","index","pureIntervals","length","i","onIntervalClick","event","Item","Legend","Vue","component","chart","legend","Component","window"],"mappings":"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","file":"timeline.bundle.map.js"}