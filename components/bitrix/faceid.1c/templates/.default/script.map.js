{"version":3,"sources":["script.js"],"names":["BXFaceIdStart","settings","deviceId","BX","localStorage","get","video","document","getElementById","canvas","startbutton","streaming","sizes","cameraRatio","screenRatio","window","screen","width","height","cameraSmallWidth","cameraSmallHeight","snapshotWidth","snapshotHeight","cameraWidth","cameraHeight","buildCameraList","navigator","mediaDevices","enumerateDevices","then","devices","cont","checkedClassName","forEach","device","kind","set","classes","label","replace","length","message","domNode","create","text","attrs","class","data-camera-id","appendChild","bind","hasClass","this","i","els","findChildren","removeClass","addClass","getAttribute","initStream","toggle","startup","catch","err","console","log","name","startupFailed","addEventListener","ev","videoWidth","videoHeight","setAttribute","hide","show","parentNode","takepicture","getMedia","getUserMedia","webkitGetUserMedia","mozGetUserMedia","msGetUserMedia","exact","audio","stream","mozSrcObject","srcObject","error","vendorURL","URL","webkitURL","src","createObjectURL","play","remove","msg","toString","indexOf","innerHTML","snapshotSrc","context","getContext","drawImage","toDataURL","tmpImg","Image","onload","handleNewVisitorFace","imageData","toggleProgressButton","ajax","url","AJAX_IDENTIFY_URL","method","data","action","image","auth","OAUTH_TOKEN","dataType","processData","start","onsuccess","json","ok","errorMessage","response","delayClose","res","JSON","parse","showAjaxError","color","contragents","overlayDetection","x","FACE_X","y","FACE_Y","FACE_WIDTH","FACE_HEIGHT","visualizeDetection","setTimeout","sendResponseTo1C","onfailure","ctx","xywh","radius","save","strokeStyle","lineWidth","sx","sy","ex","ey","r","r2d","Math","PI","beginPath","moveTo","lineTo","arc","closePath","stroke","restore","findChild","innerText"],"mappings":"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","file":"script.map.js"}