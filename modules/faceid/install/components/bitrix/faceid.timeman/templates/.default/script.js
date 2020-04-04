var domNode = BX.create("script", { attrs : { src : "/bitrix/js/main/webrtc/adapter.js", type : "text/javascript" }});
document.head.insertBefore(domNode, document.head.firstChild);

var Module, isModuleInitialized, isBxFaceIdInitialized;

if(typeof Module==="undefined")
{
	Module = {};
}

Module.onRuntimeInitialized = function()
{
	isModuleInitialized = true;
	_BXFaceIdStart();
};


function BXFaceIdStart()
{
	isBxFaceIdInitialized = true;
	_BXFaceIdStart();
}

function _BXFaceIdStart ()
{
	if (!isModuleInitialized || !isBxFaceIdInitialized)
	{
		// document should be ready and WebPhotoMaker should be initialized
		return;
	}

	// The width and height of the captured photo. We will set the
	// width to the value defined here, but the height will be
	// calculated based on the aspect ratio of the input stream.

	var jsDetectionInProgress = false;

	//var width = 340;    // We will scale the photo width to this
	//var height = 0;     // This will be computed based on the input stream

	// |streaming| indicates whether or not we're currently streaming
	// video from the camera. Obviously, we start at false.

	var streaming = false;

	// The various HTML elements we need to configure or control. These
	// will be set by the startup() function.

	var video = null;
	var canvas = null;
	var photo = null;
	var startbutton = null;

	var deviceId = BX.localStorage.get("faceid-default-camera");

	var lastVisitorTs = null;

	// collection of sizes and proportions
	var sizes = {
		cameraRatio: 0,
		screenRatio: window.screen.width/window.screen.height,

		cameraSmallWidth: 340,
		cameraSmallHeight: 0,
		cameraFullWidth: 0,
		cameraFullHeight: 0,

		snapshotWidth: 640,
		snapshotHeight: 0,

		trackingSmallRatio: 0,
		trackingFullRatio: 0,

		cameraWidth: 0, // current value
		cameraHeight: 0, // current value
		trackingRatio: 0 // current value
	};

	// auto face tracking
	var overlay = null;
	var overlayCC = null;

	// numeric if automatic best shot id, or uniq string for one-time query
	var currentAjaxConnections = [];

	// remember visitors name
	var trackingVisitorsName = {};

	// vision labs detector
	var photoMaker = new Module.WebPhotoMakerM();
	photoMaker.setStopAfterBestShot(true);
	photoMaker.setMovementThreshold(0.03);
	photoMaker.setBestShotScoreThreshold(0.05);
	photoMaker.setRotationThreshold(50);
	photoMaker.setMinFaceScaleFactor(0.15);
	photoMaker.setMaxNumberOfFramesWithoutDetection(8);

	var bufferSize = sizes.snapshotWidth * sizes.snapshotWidth * 4;

	// allocate staging memory
	var nativeFrameBuffer = Module._malloc(bufferSize);

	var nativeFrameBufferData = new Uint8ClampedArray(
		Module.HEAPU8.buffer,
		nativeFrameBuffer, bufferSize);

	var trackIds = [-1, -1, -1, -1, -1, -1];
	var trackNames = ["", "", "", "", "", ""];


	// current user info
	var currentUserInfo = null;
	var showingRecognizedFace = false;
	var showingRecognizedFacePrev = false;

	function buildCameraList()
	{
		navigator.mediaDevices.enumerateDevices()
			.then(function(devices) {

				var cont = document.getElementById('faceid-cameralist');
				var checkedClassName = 'faceid-tracker-sidebar-photo-settings-checked';

				devices.forEach(function(device) {

					if (device.kind == "videoinput")
					{
						// default value
						if (deviceId == null)
						{
							deviceId = device.deviceId;
							BX.localStorage.set("faceid-default-camera", deviceId, 3600*24*360);
						}

						// create html
						var classes = "faceid-tracker-sidebar-photo-settings-inner-list-item";
						var label = device.label.replace(/\(\w{4}:\w{4}\)/g, '');

						if (!label.length)
						{
							label = BX.message('FACEID_TRACKERWD_CMP_JS_CAMERA_DEFAULT');
						}


						if (device.deviceId == deviceId)
						{
							classes += " "+checkedClassName;
						}

						var domNode = BX.create("div", { text: label, attrs : { class : classes, 'data-camera-id': device.deviceId }});
						cont.appendChild(domNode);

						// bind event to change
						BX.bind(domNode, 'click', function ()
						{
							// skip self click
							if (BX.hasClass(BX(this), checkedClassName))
							{
								return;
							}

							// change visually
							var i, els = BX.findChildren(cont);
							for (i in els)
							{
								BX.removeClass(els[i], checkedClassName);
							}
							BX.addClass(BX(this), checkedClassName);

							// change stream
							deviceId = this.getAttribute("data-camera-id");
							BX.localStorage.set("faceid-default-camera", deviceId, 3600*24*360);
							initStream();
							BX.toggle(BX('faceid-settings-container'));
						});
					}

				});

				startup();
			})
			.catch(function(err) {
				console.log(err.name + ": " + err.message);
				startupFailed(BX.message('FACEID_TRACKERWD_CMP_JS_CAMERA_ERROR'));
			});
	}

	if (window.FACEID_AGREEMENT)
	{
		buildCameraList();
	}

	// settings button
	BX.bind(BX('faceid-settings-button'), 'click', function(){
		BX.toggle(BX('faceid-settings-container'));
	});

	function initStream()
	{
		navigator.getMedia = ( navigator.getUserMedia ||
		navigator.webkitGetUserMedia ||
		navigator.mozGetUserMedia ||
		navigator.msGetUserMedia);

		navigator.getMedia(
			{
				video: {deviceId: {exact: deviceId}},
				audio: false
			},
			function(stream) {
				if (navigator.mozGetUserMedia) {
					video.mozSrcObject = stream;
				} else {
					try {
						video.srcObject = stream;
					} catch (error) {
						var vendorURL = window.URL || window.webkitURL;
						video.src = vendorURL.createObjectURL(stream);
					}
				}
				video.play();
			},
			function(err) {
				console.log("An error occured! " + err);

				BX.localStorage.remove("faceid-default-camera");
				var msg;

				if (err.toString().indexOf("DevicesNotFound") >= 0)
				{
					msg = BX.message('FACEID_TRACKERWD_CMP_JS_CAMERA_NOT_FOUND');
				}
				else
				{
					msg = BX.message('FACEID_TRACKERWD_CMP_JS_CAMERA_NO_SUPPORT');
				}

				startupFailed(msg);
			}
		);
	}

	function startup() {
		video = document.getElementById('faceid-video');
		canvas = document.getElementById('faceid-canvas');
		//photo = document.getElementById('faceid-photo');
		startbutton = document.getElementById('faceid-startbutton');

		initStream();

		// auto face tracking
		overlay = document.getElementById('faceid-overlay-face-border');
		overlayCC = overlay.getContext('2d');

		video.addEventListener('canplay', function(ev){
			if (!streaming)
			{
				//height = video.videoHeight / (video.videoWidth / width);

				// Firefox currently has a bug where the height can't be read from
				// the video, so we will make assumptions if this happens.

				//if (isNaN(height))
				{
					//height = width / (4 / 3);
				}

				// define different sizes and props
				sizes.cameraRatio = video.videoWidth / video.videoHeight;

				// camera
				sizes.cameraSmallHeight = video.videoHeight / (video.videoWidth / sizes.cameraSmallWidth);

				if (sizes.screenRatio >= sizes.cameraRatio)
				{
					// blacks - left & right or empty
					//sizes.cameraFullHeight = window.screen.height;
					//sizes.cameraFullWidth = video.videoWidth / (video.videoHeight / sizes.cameraFullHeight);
					// let's cut ears
					sizes.cameraFullWidth = window.screen.width;
					sizes.cameraFullHeight = video.videoHeight / (video.videoWidth / sizes.cameraFullWidth);
				}
				else
				{
					// blacks - top & bottom
					// sizes.cameraFullWidth = window.screen.width;
					// sizes.cameraFullHeight = video.videoHeight / (video.videoWidth / sizes.cameraFullWidth);
					// let's cut ears
					sizes.cameraFullHeight = window.screen.height;
					sizes.cameraFullWidth = video.videoWidth / (video.videoHeight / sizes.cameraFullHeight);
				}

				// snapshots
				sizes.snapshotHeight = video.videoHeight / (video.videoWidth / sizes.snapshotWidth);

				// rt tracking
				sizes.trackingSmallRatio = sizes.cameraSmallWidth / sizes.snapshotWidth;
				sizes.trackingFullRatio = sizes.cameraFullWidth / sizes.snapshotWidth;

				// current values
				sizes.cameraWidth = sizes.cameraSmallWidth;
				sizes.cameraHeight = sizes.cameraSmallHeight;
				sizes.trackingRatio = sizes.trackingSmallRatio;

				video.setAttribute('width', sizes.cameraWidth);
				video.setAttribute('height', sizes.cameraHeight);

				canvas.setAttribute('width', sizes.snapshotWidth);
				canvas.setAttribute('height', sizes.snapshotHeight);

				overlay.setAttribute('width', sizes.cameraWidth);
				overlay.setAttribute('height', sizes.cameraHeight);

				streaming = true;
				capture();
			}
		}, false);

		startbutton.addEventListener('click', function(ev){
			//takepicture();
			ev.preventDefault();
		}, false);

		// show visitors from db
		if (window.FACEID_LAST_VISITORS)
		{
			for (i in window.FACEID_LAST_VISITORS)
			{
				prependVisitor(window.FACEID_LAST_VISITORS[i], false);
			}
		}

		// auto photo
		// find zone
		//window.setInterval(capture, 2000);
	}

	function startupFailed(msg)
	{
		BX('faceid-camera-error').innerHTML = msg;
		BX.show(BX('faceid-camera-error'));
	}

	function capture()
	{
		requestAnimationFrame(capture);

		if (!BX('faceid-auto-identify').checked)
		{
			return;
		}

		if (jsDetectionInProgress)
		{
			return;
		}

		if (currentAjaxConnections.length)
		{
			// check inside vlGetFaces and dont return faces in progress
			//return;
		}

		jsDetectionInProgress = true;

		var context = canvas.getContext('2d');
		context.drawImage(video, 0, 0, sizes.snapshotWidth, sizes.snapshotHeight);

		var snapshotSrc = canvas.toDataURL('image/jpeg', 1.0);

		var tmpImg = new Image();
		tmpImg.onload = function () {

			var faces = vlGetFaces(canvas, true);

			if (faces.length > 0)
			{
				// check faces[i].frameId and unset if already has been recognized
				var unknownFaces = [];
				for (var j in faces)
				{
					if (!trackingVisitorsName[faces[j].frameId] // if not recognized yet
					// and it's kind of best shot
						&& photoMaker.getBestShotFrameNumber(faces[j].frameId) >= 0 && photoMaker.getCurrentFrameNumber() >= photoMaker.getBestShotFrameNumber(faces[j].frameId))
					{
						unknownFaces.push(faces[j]);
					}
				}

				if (unknownFaces.length)
				{
					recognizeMultiFaces(unknownFaces, tmpImg);
				}

				// found face, but is it big enough?
				/*var found = false;
				if (faces[0].width/2 > 24)
				{
					found = true;
				}

				if (found)
				{
					recognizeMultiFaces(faces, tmpImg);
				}*/
			}

			jsDetectionInProgress = false;

			/*$(tmpImg).faceDetection({
			 confidence: 0,
			 complete: function (faces) {
			 console.log(faces);
			 if (faces.length > 0)
			 {
			 // found face, but is it big enough?
			 var found = false;

			 if (faces[0].width/2 > 24)
			 {
			 found = true;
			 }

			 if (found)
			 {
			 for (i = 0; i < faces.length; i++)
			 {
			 //takepicture(snapshotSrc);
			 break;
			 }
			 }
			 }
			 jsDetectionInProgress = false;
			 }
			 });*/
		};

		tmpImg.onerror = function(){
			jsDetectionInProgress = false;
		};

		tmpImg.src = snapshotSrc;

		requestAnimationFrame(capture);
	}

	function fellIds(ids) {
		for (var j = 0; j < trackIds.length; j++) {
			var countBad = 0;
			for (var i = 0; i < ids.length; i++) {
				if (ids[i] != trackIds[j])
					countBad++;
				else
					break
			}
			if (countBad == ids.length) {
				trackIds[j] = -1;
				trackNames[j] = "";
			}
		}

		for (var i = 0; i < ids.length; i++) {
			var newTrack = true;
			for (var j = 0; j < trackIds.length; j++) {
				if (ids[i] == trackIds[j]) {
					newTrack = false;
					break;
				}
			}
			if (newTrack) {
				for (var j = 0; j < trackIds.length; j++) {
					if (trackIds[j] == -1) {
						trackIds[j] = ids[i];
						break;
					}
				}
			}
		}
	}

	// Draw current detection rectangle.
	function visualizeDetection(ctx, xywh, radius, color, trackId) {
		ctx.save();
		ctx.strokeStyle = "rgba("
			+ color[0] + ", "
			+ color[1] + ", "
			+ color[2] + ", "
			+ color[3] + ")";


		ctx.lineWidth = 3;

		try {
			var sx = xywh.x;
			var sy = xywh.y;
			var ex = xywh.x + xywh.width;
			var ey = xywh.y + xywh.height;
			var r = radius;

			var r2d = Math.PI / 180;

			if ((ex - sx) - (2 * r) < 0) {
				r = ((ex - sx) / 2);
			} //ensure that the radius isn't too large for x
			if ((ey - sy) - (2 * r) < 0) {
				r = ((ey - sy) / 2);
			} //ensure that the radius isn't too large for y

			ctx.beginPath();
			ctx.moveTo(sx + r, sy);
			ctx.lineTo(ex - r, sy);
			ctx.arc(ex - r, sy + r, r, r2d * 270, r2d * 360, false);
			ctx.lineTo(ex, ey - r);
			ctx.arc(ex - r, ey - r, r, r2d * 0, r2d * 90, false);
			ctx.lineTo(sx + r, ey);
			ctx.arc(sx + r, ey - r, r, r2d * 90, r2d * 180, false);
			ctx.lineTo(sx, sy + r);
			ctx.arc(sx + r, sy + r, r, r2d * 180, r2d * 270, false);
			ctx.closePath();

			if (sizes.cameraWidth == sizes.cameraFullWidth)
			{
				ctx.font = "bold 24px opensans";
			}
			else
			{
				ctx.font = "bold 11px opensans";
			}
			ctx.fillStyle = "#91ff4f";
			//var name = getName(trackId);
			var name = "";
			if (trackingVisitorsName[trackId])
			{
				name = trackingVisitorsName[trackId].name;
			}
			ctx.fillText(name.toUpperCase(), sx, sy - 5);
			ctx.stroke();
			ctx.restore();
		} catch (err) {
			console.log("BAD VALUE OF DETECTION");
			console.log(err);
		}
	}

	// Capture a photo by fetching the current contents of the video
	// and drawing it into a canvas, then converting that to a PNG
	// format data URL. By drawing it on an offscreen canvas and then
	// drawing that to the screen, we can change its size and/or apply
	// other changes before drawing it.

	function takepicture(snapshotSrc) {

		if (!snapshotSrc)
		{
			var context = canvas.getContext('2d');
			context.drawImage(video, 0, 0, sizes.snapshotWidth, sizes.snapshotHeight);

			snapshotSrc = canvas.toDataURL('image/jpeg', 1.0);
		}

		// split to every face
		var tmpImg = new Image();
		tmpImg.onload = function ()
		{
			var multiFaces = true;

			if (multiFaces)
			{
				// jquery.facedetection.sj
				/*$(this).faceDetection({
					confidence: 0,
					complete: function(faces) {
						recognizeMultiFaces(faces, tmpImg);
					}
				});*/

				// custom
				var faces = vlGetFaces(canvas);
				recognizeMultiFaces(faces, tmpImg);
			}
			else
			{
				// single face
				handleNewVisitorFace(tmpImg.getAttribute('src'));
			}
		};

		tmpImg.src = snapshotSrc;
	}

	function vlGetFaces(canvas, modeAuto)
	{
		var faces = [];
		var vizFaces = []; // faces to visualize
		var unknownFaces = [];
		showingRecognizedFace = false;

		var context = canvas.getContext('2d');
		context.drawImage(video, 0, 0, sizes.snapshotWidth, sizes.snapshotHeight);

		// var buffer = document.createElement("canvas"); // put here our canvas or what?
		var buffer = canvas; // put here our canvas or what?
		var bufferCC = buffer.getContext('2d');

		// take the buffer contents
		var bufferImageData = bufferCC.getImageData(0, 0, sizes.snapshotWidth, sizes.snapshotHeight);

		// convert image data to typed byte array (raw bytes)
		nativeFrameBufferData.set(bufferImageData.data, 0);

		photoMaker.submitRawImage(nativeFrameBuffer, sizes.snapshotWidth, sizes.snapshotHeight);
		photoMaker.update();

		//overlayCC.clearRect(0, 0, overlay.width, overlay.height);

		var strIds = photoMaker.getIds();
		var jIds = JSON.parse(strIds);
		var ids = jIds["ids"];

		fellIds(ids);

		if (ids.length)//photoMaker.haveFaceDetection())
		{
			for (var i = 0; i < ids.length; i++)
			{
				var id = ids[i];

				var detection = photoMaker.getSmoothedFaceDetection(id);
				var predicted = photoMaker.faceDetectionIsPredicted(id);
				var color = [145, 255, 79, 255];
				var verdict = "";
				var speedSlow = photoMaker.isSlowMovement(id);

				if (!speedSlow) verdict = "TOO FAST!!!";
				if (predicted) verdict = "PREDICT";

				if (predicted || !speedSlow) {

					color = [255, 0, 0, 255];
				}

				// adapt x&y from snapshot to overlay
				var overlayDetection = JSON.parse(JSON.stringify(detection));

				overlayDetection.x = detection.x * sizes.trackingRatio;
				overlayDetection.y = detection.y * sizes.trackingRatio;
				overlayDetection.width = detection.width * sizes.trackingRatio;
				overlayDetection.height = detection.height * sizes.trackingRatio;

				//visualizeDetection(overlayCC, overlayDetection, 10, color, id);

				faces.push({
					frameId: id,
					width: detection.width,
					height: detection.height,
					positionX: detection.x,
					positionY: detection.y
				});
			} // endfor

			// there only one face should be marked
			var thereCanBeOnlyOne = true;

			for (var j in faces)
			{
				// if face in progress, draw it and break everything
				if (currentAjaxConnections.indexOf(faces[j].frameId) > -1)
				{
					vizFaces.push(faces[j]);

					showingRecognizedFace = true;

					if (thereCanBeOnlyOne)
					{
						break;
					}
				}

				// if face is known, draw it and break everything
				if (trackingVisitorsName[faces[j].frameId])
				{
					vizFaces.push(faces[j]);

					showingRecognizedFace = true;

					if (thereCanBeOnlyOne)
					{
						break;
					}
				}
			}

			if (!thereCanBeOnlyOne || (thereCanBeOnlyOne && vizFaces.length == 0))
			{
				for (var j in faces)
				{
					// take unknown face with best shot, draw it and break everything
					if (!trackingVisitorsName[faces[j].frameId] // if not recognized yet
						// and it's kind of best shot
						&& photoMaker.getBestShotFrameNumber(faces[j].frameId) >= 0 && photoMaker.getCurrentFrameNumber() >= photoMaker.getBestShotFrameNumber(faces[j].frameId))
					{
						unknownFaces.push(faces[j]);
						vizFaces.push(faces[j]);

						if (thereCanBeOnlyOne)
						{
							break;
						}
					}
				}
			}
		}

		visualizeFaces(vizFaces);

		// hide controls if we lost known face
		if (!showingRecognizedFace && showingRecognizedFacePrev)
		{
			// hide
			onUserLeft();
		}

		showingRecognizedFacePrev = showingRecognizedFace;

		if (!modeAuto)
		{
			resetPhotoMaker();
		}

		// return faces to identify
		return unknownFaces;
	}

	function onUserLeft()
	{
		BX.hide(BX('faceid-tracker-workday-started'));
		BX.hide(BX('faceid-tracker-workday-opened'));
		BX.hide(BX('faceid-tracker-workday-paused'));
		BX.hide(BX('faceid-tracker-workday-ended'));

		currentUserInfo = null;
	}

	function visualizeFaces(faces)
	{
		overlayCC.clearRect(0, 0, overlay.width, overlay.height);

		var color, speedSlow, predicted;

		for (var j in faces)
		{
			var face = faces[j];
			var overlayDetection = {};

			predicted = photoMaker.faceDetectionIsPredicted(face.frameId);
			color = [145, 255, 79, 255];
			speedSlow = photoMaker.isSlowMovement(face.frameId);

			if (predicted || !speedSlow) {

				color = [255, 0, 0, 255];
			}

			overlayDetection.x = face.positionX * sizes.trackingRatio;
			overlayDetection.y = face.positionY * sizes.trackingRatio;
			overlayDetection.width = face.width * sizes.trackingRatio;
			overlayDetection.height = face.height * sizes.trackingRatio;

			visualizeDetection(overlayCC, overlayDetection, 10, color, face.frameId);
		}
	}

	function resetPhotoMaker()
	{
		// double call to make sure checkbox change has been implemented and auto mode is off now
		requestAnimationFrame(function ()
		{
			requestAnimationFrame(function ()
			{
				photoMaker.reset();
				overlayCC.clearRect(0, 0, overlay.width, overlay.height);
			});
		});
	}

	function recognizeMultiFaces(faces, image)
	{
		var found = false;

		if (faces.length > 0)
		{
			for (i = 0; i < faces.length; i++)
			{
				//if (faces[i].width / 2 > 24)
				{
					var face = faces[i];
					// found face, but is it big enough?
					var portraitCanvas = document.createElement("canvas");
					portraitCanvas.setAttribute('width', 100);
					portraitCanvas.setAttribute('height', 100);

					var portraitContext = portraitCanvas.getContext('2d');

					//BX.prepend(portraitCanvas, BX('faceid-tracker-main-user-container'));

					// increase size of face
					var addSize = 0.6;

					var sourceX = Math.max(0, face.positionX - (face.width*addSize/2));
					var sourceY = Math.max(0, face.positionY - (face.height*addSize/2));
					var sourceWidth = face.width + face.width*addSize;
					var sourceHeight = face.height + face.height*addSize;
					var destWidth = 100;
					var destHeight = 100;
					var destX = 0;
					var destY = 0;

					portraitContext.drawImage(image, sourceX, sourceY, sourceWidth, sourceHeight, destX, destY, destWidth, destHeight);

					var portraitData = portraitCanvas.toDataURL('image/jpeg');

					handleNewVisitorFace(portraitData, face.frameId);

					found = true;
				}
				/*else
				{
					console.log('small face');
				}*/
			}
		}

		if (!found)
		{
			handleNewVisitorFace(image.src, false, true);
		}

	} // complete of face detection


	function handleNewVisitorFace(imageData, trackingFrameId, faceNotFound)
	{
		BX.addClass(BX('faceid-tracker-main-user-start-block'), 'faceid-tracker-animate-hidden');

		// append to stack in state of loading
		var newVisitorDiv = BX.create("div", { attrs : { class : 'faceid-tracker-main-user-item faceid-tracker-first-item' }});
		newVisitorDiv.innerHTML = '<div class="faceid-tracker-main-user-photo"> \
					<div class="faceid-tracker-main-user-photo-platform"> \
						<span class="faceid-tracker-main-user-photo-platform-item">CRM</span> \
					</div> \
					<div class="faceid-tracker-main-user-photo-item"> \
						<img width="100" height="100"> \
					</div> \
					<div class="faceid-tracker-main-user-photo-count"> \
						<span class="faceid-tracker-main-user-photo-count-item">58%</span> \
						<div class="faceid-tracker-main-user-photo-count-info"> \
							<div class="faceid-tracker-main-user-photo-count-info-desc"></div> \
							<div class="faceid-tracker-main-user-photo-item"></div> \
						</div> \
					</div> \
				</div> \
				<div class="faceid-tracker-main-user-info"> \
					<div class="faceid-tracker-user-loader"> \
						<div class="faceid-tracker-user-loader-item"> \
							<div class="faceid-tracker-loader"> \
								<svg class="faceid-tracker-circular" viewBox="25 25 50 50"> \
									<circle class="faceid-tracker-path" cx="50" cy="50" r="20" fill="none" stroke-width="1" stroke-miterlimit="10"/> \
								</svg> \
							</div> \
						</div> \
					</div> \
				</div>';
		BX.prepend(newVisitorDiv, BX('faceid-tracker-main-user-container'));

		// clean stack
		var visitorLimit = 20;

		var currentVisitors = BX.findChildren(BX('faceid-tracker-main-user-container'), {class:'faceid-tracker-main-user-item'});

		if (currentVisitors.length > visitorLimit)
		{
			// rewrite last visit ts
			lastVisitorTs = currentVisitors[currentVisitors.length-2].getAttribute('data-last-visit-ts');

			// remove last child
			var lastChild = currentVisitors[currentVisitors.length-1];
			lastChild.remove();
		}

		// put photo
		var imgCont = BX.findChild(newVisitorDiv, {class: 'faceid-tracker-main-user-photo-item'}, true);
		var imgEl = BX.findChild(imgCont, {tag: 'img'});
		imgEl.setAttribute('src', imageData);

		if (faceNotFound)
		{
			BX.addClass(newVisitorDiv, 'faceid-tracker-user-warning');
			var loaderCont = BX.findChild(newVisitorDiv, {class: 'faceid-tracker-main-user-info'}, true);
			loaderCont.innerHTML = '<div class="faceid-tracker-main-user-info-name"> \
				<div class="faceid-tracker-main-user-info-name-warning">'+BX.message('FACEID_TRACKERWD_CMP_JS_FACE_NOT_FOUND')+'</div> \
			</div>';
		}
		else
		{
			// uniq ajax id
			var uniqAjaxId = trackingFrameId >= 0 ? trackingFrameId : 'faceid-ajax-unique-'+Math.random();
			ajaxConnectionAdd(uniqAjaxId);

			currentUserInfo = {trackingFrameId: trackingFrameId};

			// go ajax
			BX.ajax({
				url: '/bitrix/components/bitrix/faceid.timeman/ajax.php',
				method: 'POST',
				data: {action: 'identify', image: imageData, 'autoOpen': 1},
				dataType: 'json',
				processData: false,
				start: true,
				onsuccess: function (json) {

					ajaxConnectionRemove(uniqAjaxId);

					var ok = false;
					var errorMessage = BX.message('FACEID_TRACKERWD_CMP_JS_FACE_NOT_FOUND');

					// do we see the same user?
					if (json.length && currentUserInfo !== null && currentUserInfo.trackingFrameId == trackingFrameId)
					{
						var result = JSON.parse(json);

						if (result.visitor && !isEmpty(result.visitor))
						{
							ok = true;
							var visitor = result.visitor;

							currentUserInfo = visitor;

							if (result.action == 'OPENED')
							{
								BX.show(BX('faceid-tracker-workday-started'));
							}
							else if (visitor.workday_status == 'OPENED')
							{
								BX.show(BX('faceid-tracker-workday-opened'));
							}
							else if (visitor.workday_status == 'PAUSED')
							{
								BX.show(BX('faceid-tracker-workday-paused'));
							}

							// remember visitor name
							if (trackingFrameId >= 0)
							{
								trackingVisitorsName[trackingFrameId] = {name: visitor.full_name};
							}
						}
						else if (result.error && result.error.msg)
						{
							// show error
							if (trackingFrameId >= 0 && result.error.code == 'OK_UNKNOWN_PERSON')
							{
								trackingVisitorsName[trackingFrameId] = {name: 'UNKNOWN PERSON'};
							}
							errorMessage = result.error.msg;
						}
					}

					if (!ok)
					{
						photoMaker.discardBestShot(trackingFrameId);
					}
				},
				onfailure: function () {
					ajaxConnectionRemove(uniqAjaxId);
					photoMaker.discardBestShot(trackingFrameId);
				}
			});
		}
	}

	function ajaxConnectionAdd(id)
	{
		currentAjaxConnections.push(id);
	}

	function ajaxConnectionRemove(id)
	{
		var index = currentAjaxConnections.indexOf(id);
		if (index > -1)
		{
			currentAjaxConnections.splice(index, 1);
		}
	}


	function prependVisitor(json, updateDailyCounters)
	{
		BX.addClass(BX('faceid-tracker-main-user-start-block'), 'faceid-tracker-animate-hidden');

		var newVisitorDiv = BX.create("div", { attrs : { class : 'faceid-tracker-main-user-item faceid-tracker-first-item' }});
		var updateCounters = renderVisitor(newVisitorDiv, json);

		BX.prepend(newVisitorDiv, BX('faceid-tracker-main-user-container'));

		if (json.last_visit_ts < lastVisitorTs || lastVisitorTs == null)
		{
			lastVisitorTs = json.last_visit_ts;
		}

		// increment visitors count
		if (updateCounters)
		{
			updateVisitorsCount(json, updateDailyCounters);
		}
	}

	function appendVisitor(json, updateCounters)
	{
		BX.addClass(BX('faceid-tracker-main-user-start-block'), 'faceid-tracker-animate-hidden');

		var newVisitorDiv = BX.create("div", { attrs : { class : 'faceid-tracker-main-user-item faceid-tracker-first-item' }});
		var updateCountersByRender = renderVisitor(newVisitorDiv, json);

		if (BX('faceid-tracker-main-user-more'))
		{
			BX('faceid-tracker-main-user-container').insertBefore(newVisitorDiv, BX('faceid-tracker-main-user-more'));
		}
		else
		{
			BX.append(newVisitorDiv, BX('faceid-tracker-main-user-container'));
		}

		if (json.last_visit_ts < lastVisitorTs || lastVisitorTs == null)
		{
			lastVisitorTs = json.last_visit_ts;
		}

		// increment visitors count
		if (updateCountersByRender)
		{
			updateVisitorsCount(json, updateCounters);
		}
	}

	function updateVisitorsCount(visitor, updateDailyCounters)
	{
		updateDailyCounters = (typeof updateDailyCounters !== 'undefined') ? updateDailyCounters : true;

		// common case
		BX('faceid-stats-current-count').innerText = parseInt(BX('faceid-stats-current-count').innerText) + 1;

		if (updateDailyCounters)
		{
			if (visitor.visits_count <= 1)
			{
				BX('faceid-stats-new-count').innerText = parseInt(BX('faceid-stats-new-count').innerText) + 1;
			}
			else
			{
				BX('faceid-stats-old-count').innerText = parseInt(BX('faceid-stats-old-count').innerText) + 1;
			}

			BX('faceid-stats-total-count').innerText = parseInt(BX('faceid-stats-total-count').innerText) + 1;

			if (visitor.crm_url.length)
			{
				BX('faceid-stats-crm-count').innerText = parseInt(BX('faceid-stats-crm-count').innerText) + 1;
			}
		}
	}


	function renderVisitor(newVisitorDiv, json)
	{
		var updateCounters = true;

		var result = (typeof json) == 'string' ? JSON.parse(json) : json;

		// id
		newVisitorDiv.setAttribute('data-visitor-id', result.visitor_id);

		// actually one visitor can be presented only once on the page
		var ex = BX.findChildren(BX('faceid-tracker-main-user-container'), {attr: {'data-visitor-id': result.visitor_id}});
		if (ex)
		{
			var k;
			for (k in ex)
			{
				if (ex[k] != newVisitorDiv)
				{
					ex[k].remove();
					updateCounters = false;
				}
			}
		}

		// for existed visitors
		if (result.confidence > 0)
		{
			BX.addClass(newVisitorDiv, 'faceid-tracker-photo');
		}

		// for crm users
		if (result.crm_url.length > 0)
		{
			BX.addClass(newVisitorDiv, 'faceid-tracker-platform');
		}

		// for vk users
		if (result.vk_id.length > 0)
		{
			BX.addClass(newVisitorDiv, 'faceid-tracker-social-state');
		}

		// write ts in data-attr
		newVisitorDiv.setAttribute('data-last-visit-ts', result.last_visit_ts);

		var html = '<div class="faceid-tracker-main-user-photo"> \
						<div class="faceid-tracker-main-user-photo-platform"> \
							<span class="faceid-tracker-main-user-photo-platform-item">CRM</span> \
						</div> \
						<div class="faceid-tracker-main-user-photo-item"> \
							<img width="100" height="100"> \
						</div> \
						<div class="faceid-tracker-main-user-photo-count"> \
							<span class="faceid-tracker-main-user-photo-count-item">'+result.confidence+'%</span> \
							<div class="faceid-tracker-main-user-photo-count-info"> \
								<div class="faceid-tracker-main-user-photo-count-info-desc">'+BX.message('FACEID_TRACKERWD_CMP_JS_FACE_ORIGINAL')+'</div> \
								<div class="faceid-tracker-main-user-photo-item" style="background-image: url('+result.image_src+')"></div> \
							</div> \
						</div> \
					</div> \
					<div class="faceid-tracker-main-user-info"> \
						<div class="faceid-tracker-main-user-info-description"> \
							<span class="faceid-tracker-main-user-info-description-item">'+result.visit_info+'</span> \
						</div> \
						<div class="faceid-tracker-main-user-info-name">';

						if (result.crm_url.length)
							html += '<a href="'+result.crm_url+'" class="faceid-tracker-main-user-info-name-item">'+result.name+'</a>';
						else
							html += '<div class="faceid-tracker-main-user-info-name-item">'+result.name+'</div>';

		html += '\
						</div> \
						<div class="faceid-tracker-main-user-info-control">';

						if (!result.crm_url.length)
							html += '\
							<div class="faceid-tracker-main-user-info-control-block'+(result.crm_url.length?" faceid-tracker-button-state":"")+'"> \
								<div class="webform-small-button webform-small-button-blue faceid-tracker-main-user-info-control-button">'+BX.message('FACEID_TRACKERWD_CMP_JS_SAVE_CRM')+' \
									<div class="faceid-tracker-user-loader"> \
										<div class="faceid-tracker-user-loader-item"> \
											<div class="faceid-tracker-loader"> \
												<svg class="faceid-tracker-circular" viewBox="25 25 50 50"> \
													<circle class="faceid-tracker-path" cx="50" cy="50" r="20" fill="none" stroke-width="1" stroke-miterlimit="10"/> \
												</svg> \
											</div> \
										</div> \
									</div> \
								</div> \
								<div class="webform-small-button webform-small-button-transparent faceid-tracker-main-user-info-control-button">'+BX.message('FACEID_TRACKERWD_CMP_JS_SAVE_CRM_DONE')+'</div> \
							</div>';

		html += '\
							<div class="faceid-tracker-main-user-info-control-social"> \
								<span class="faceid-tracker-main-user-info-control-social-name">'+BX.message('FACEID_TRACKERWD_CMP_JS_VK_LINK')+'</span>';

								if (result.vk_id.length)
									html += '\
									<a href="http://vk.com/'+BX.util.htmlspecialchars(result.vk_id)+'" class="faceid-tracker-main-user-info-control-social-item">'+"VK.com/"+BX.util.htmlspecialchars(result.vk_id)+'</a>';
								else
									html += '\
									<span class="faceid-tracker-main-user-info-control-social-item"><span>'+BX.message('FACEID_TRACKERWD_CMP_JS_VK_LINK_ACTION')+'</span></span>';

		html += '\
							</div> \
						</div> \
					</div>';

		newVisitorDiv.innerHTML = html;

		// put photo
		var imgCont = BX.findChild(newVisitorDiv, {class: 'faceid-tracker-main-user-photo-item'}, true);
		var imgEl = BX.findChild(imgCont, {tag: 'img'});
		imgEl.setAttribute('src', result.shot_src);

		// put vk search
		if (!result.vk_id.length)
		{
			var searchPopup = BX.clone(BX('faceid-tracker-profile-search-example'));
			searchPopup.id = '';

			searchPopup.style.top = (document.body.scrollTop + 596 + 30) + "px";

			var socialCont = BX.findChild(newVisitorDiv, {class: 'faceid-tracker-main-user-info-control-social-item'}, true);
			var header = BX.findChild(searchPopup, {class: 'faceid-tracker-profile-search-header'}, true);

			var clientX = 0;
			var clientY = 0;
			var mousemove = function(event) {
				var deltaX = clientX - event.clientX;
				var deltaY = clientY - event.clientY;
				searchPopup.style.left = parseInt(searchPopup.style.left) - deltaX + "px";
				searchPopup.style.top = parseInt(searchPopup.style.top) - deltaY + "px";

				clientX = event.clientX;
				clientY = event.clientY;
			};

			var mouseup = function() {
				BX.unbind(document, "mousemove", mousemove);
				BX.unbind(document, "mouseup", mouseup);
			};

			BX.bind(header, "mousedown", function(event) {
				var pos = BX.pos(searchPopup);
				searchPopup.style.left = pos.left + "px";
				searchPopup.style.top = pos.top + "px";

				clientX = event.clientX;
				clientY = event.clientY;
				searchPopup.style.transform = "translate(0)";
				BX.bind(document, "mousemove", mousemove);
				BX.bind(document, "mouseup", mouseup);
			});
			document.body.appendChild(searchPopup);

			// set events
			var searchVkButton = BX.findChild(socialCont, {tag: 'span'});
			BX.bind(searchVkButton, 'click', function () {
				
				BX.show(searchPopup);

				if (searchPopup.getAttribute('data-in-progress'))
				{
					return;
				}

				searchPopup.setAttribute('data-in-progress', '1');

				/*setTimeout(function(){
					console.log(searchPopup);
					var loader = BX.findChild(searchPopup, {class: 'faceid-tracker-profile-search-loading'}, true);
					BX.addClass(loader, 'faceid-tracker-animate-hidden');
				}, 2000);*/

				BX.ajax({
					url: '/bitrix/components/bitrix/faceid.tracker/ajax_vk.php',
					method: 'POST',
					data: {action: 'identify', image: result.shot_src, visitor_id: result.visitor_id},
					dataType: 'json',
					processData: false,
					start: true,
					onsuccess: function (json)
					{
						var jsonResult = JSON.parse(json);
						var vkResult = jsonResult.items;

						// update balance
						if (jsonResult.status && jsonResult.status.balance >= 0)
						{
							BX('faceid-credits-balance').innerText = jsonResult.status.balance;
						}

						// handle result
						if (vkResult.length)
						{
							var j, _html = '';

							// first featured element
							_html = renderVkItem(vkResult[0], true);
							var itemsCont = BX.findChild(searchPopup, {class: 'faceid-tracker-profile-search-main'}, true);
							itemsCont.innerHTML = _html + itemsCont.innerHTML;
							_html = '';


							// all the rest
							vkResult.splice(0,1);

							for (j in vkResult)
							{
								_html += renderVkItem(vkResult[j]);
							}

							itemsCont = BX.findChild(searchPopup, {class: 'faceid-tracker-profile-search-found-container'}, true);
							itemsCont.innerHTML = _html;

							// count of the rest
							var countCont = BX.findChild(searchPopup, {class: 'faceid-tracker-profile-search-found-more-count'}, true);
							countCont.innerHTML = vkResult.length + " "+BX.message('FACEID_TRACKERWD_CMP_JS_VK_FOUND_PEOPLE');


							// hide loader
							var loader = BX.findChild(searchPopup, {class: 'faceid-tracker-profile-search-loading'}, true);
							BX.addClass(loader, 'faceid-tracker-animate-hidden');

							// events
							var vkButtons = BX.findChildren(searchPopup, {class: 'faceid-tracker-main-user-info-control-button'}, true);
							for (j in vkButtons)
							{
								BX.bind(vkButtons[j], 'click', function ()
								{
									var userInfo = BX(this).parentNode.parentNode;
									var addr = BX.findChild(userInfo, {class: 'faceid-tracker-main-user-social-link-item'}, true).innerText;

									// replace search with an url
									var socParent = socialCont.parentNode;
									socialCont.remove();

									socParent.innerHTML += '<a href="http://' + addr + '" class="faceid-tracker-main-user-info-control-social-item">' + addr.replace('vk.com', 'VK.com') + '</a>';
									BX.addClass(newVisitorDiv, 'faceid-tracker-social-state');

									BX.hide(searchPopup);

									// save to backend
									BX.ajax({
										url: '/bitrix/components/bitrix/faceid.tracker/ajax_vk.php',
										method: 'POST',
										data: {action: 'save', vk_id: addr.replace('vk.com/', ''), visitor_id: result.visitor_id},
										dataType: 'json',
										processData: false,
										start: true,
										onsuccess: function (json)
										{
											var crmResult = JSON.parse(json);

											var nameCont = BX.findChild(newVisitorDiv, {class: 'faceid-tracker-main-user-info-name'}, true);
											nameCont.innerHTML = '<a href="'+crmResult.url+'" class="faceid-tracker-main-user-info-name-item">'+result.name+'</a>';
										}
									});
								});
							}
						}
						else if (jsonResult.error && jsonResult.error.msg)
						{
							// hide loader
							var loader = BX.findChild(searchPopup, {class: 'faceid-tracker-loader'}, true);
							BX.hide(loader);

							var loaderDesc = BX.findChild(searchPopup, {class: 'faceid-tracker-profile-search-loading-desc'}, true);
							BX.hide(loaderDesc);

							// show error
							var errorDesc = BX.findChild(searchPopup, {class: 'faceid-tracker-error'}, true);
							errorDesc.innerHTML = jsonResult.error.msg;
							BX.show(errorDesc);
						}

						//var nameCont = BX.findChild(newVisitorDiv, {class: 'faceid-tracker-main-user-info-name'}, true);
						//nameCont.innerHTML = '<a href="'+crmResult.url+'" class="faceid-tracker-main-user-info-name-item">'+result.name+'</a>';
					}
				});
			});

			var closeButton = BX.findChild(searchPopup, {class: 'faceid-tracker-header-description-close-item'}, true);
			BX.bind(closeButton, 'click', function () {
				BX.hide(searchPopup);
			});

			// recognize vk
			/*var vkButton = BX.findChild(searchPopup, {class: 'faceid-tracker-main-user-info-control-button'}, true);
			BX.bind(vkButton, 'click', function() {
				var userInfo = BX(this).parentNode.parentNode;
				var addr = BX.findChild(userInfo, {class: 'faceid-tracker-main-user-social-link-item'}, true).innerText;
				console.log(addr);

				// replace search with an url
				var socParent = socialCont.parentNode;
				socialCont.remove();

				socParent.innerHTML += '<a href="http://'+addr+'" class="faceid-tracker-main-user-info-control-social-item">'+addr.replace('vk.com', 'VK.com')+'</a>';
				BX.addClass(newVisitorDiv, 'faceid-tracker-social-state');
			});*/
		}

		// crm add
		var crmButton = BX.findChild(newVisitorDiv, {class: 'webform-small-button-blue'}, true);
		BX.bind(crmButton, 'click', function() {

			var buttonParent = BX(this).parentNode;

			if (!BX.hasClass(buttonParent, 'faceid-tracker-button-state') && !BX.hasClass(buttonParent, 'faceid-control-loader-state'))
			{
				BX.addClass(buttonParent, 'faceid-control-loader-state'); // loading

				BX.ajax({
					url: '/bitrix/components/bitrix/faceid.tracker/ajax_crm.php',
					method: 'POST',
					data: {action: 'addLead', lead_title: result.name, visitor_id: result.visitor_id},
					dataType: 'json',
					processData: false,
					start: true,
					onsuccess: function (json)
					{
						var crmResult = JSON.parse(json);

						var nameCont = BX.findChild(newVisitorDiv, {class: 'faceid-tracker-main-user-info-name'}, true);
						nameCont.innerHTML = '<a href="'+crmResult.url+'" class="faceid-tracker-main-user-info-name-item">'+result.name+'</a>';

						BX.removeClass(buttonParent, '.faceid-control-loader-state'); // loading
						BX.addClass(buttonParent, 'faceid-tracker-button-state'); // saved
					}
				});
			}
		});

		return updateCounters;
	}

	function renderVkItem(item, featured)
	{
		var html = '<div class="faceid-tracker-main-user-item faceid-tracker-profile-search-found faceid-tracker-photo'+(featured?' faceid-tracker-found-user':'')+'"> \
					<div class="faceid-tracker-main-user-photo"> \
						<div class="faceid-tracker-main-user-photo-platform"> \
							<span class="faceid-tracker-main-user-photo-platform-item">CRM</span> \
						</div> \
						<div class="faceid-tracker-main-user-photo-item" style="background-image: url('+BX.util.htmlspecialchars(item.photo)+')"></div> \
						<div class="faceid-tracker-main-user-photo-count"> \
							<span class="faceid-tracker-main-user-photo-count-item">'+Math.round(item.confidence*100)+'%</span> \
						</div> \
					</div> \
					<div class="faceid-tracker-main-user-info"> \
						<div class="faceid-tracker-main-user-info-name"> \
							<div class="faceid-tracker-main-user-info-name-item">'+BX.util.htmlspecialchars(item.name)+'</div> \
							<div class="faceid-tracker-main-user-date"> \
								<span class="faceid-tracker-main-user-date-item">'+BX.util.htmlspecialchars(item.personal)+'</span> \
							</div> \
							<div class="faceid-tracker-main-user-social-link"> \
								<a href="http://vk.com/'+BX.util.htmlspecialchars(item.id)+'" class="faceid-tracker-main-user-social-link-item">vk.com/'+BX.util.htmlspecialchars(item.id)+'</a> \
							</div> \
						</div> \
						<div class="faceid-tracker-main-user-info-control"> \
							<div class="webform-small-button webform-small-button-blue faceid-tracker-main-user-info-control-button '+(featured?'':'faceid-tracker-search-button')+'">'+BX.message('FACEID_TRACKERWD_CMP_JS_VK_SELECT')+'</div> \
						</div> \
					</div> \
				</div>';

		return html;
	}

	/*function identify(imageData)
	{
		$('.loader').show();
		$('.newperson').hide();
		$('.result').hide();


		$.ajax({
			url: "identify.php",
			data: {
				image: imageData
			},
			type: "POST",
			dataType : "html"
		}).done(function( html ) {
			console.log('ok');
			//console.log(html);
			$('.result').html(html).show();
			$('.newperson').show();
		}).fail(function( xhr, status, errorThrown ) {
			alert( "Sorry, there was a problem!" );
			console.log( "Error: " + errorThrown );
			console.log( "Status: " + status );
			console.dir( xhr );
		}).always(function( xhr, status ) {
			$('.loader').hide();
		});
	}

	function identified(xhttp)
	{
		console.log(xhttp);
	}*/

	BX.ready(function ()
	{
		BX.bind(BX('faceid-tracker-main-user-more'), 'click', function(){

			if (!lastVisitorTs)
			{
				return;
			}

			BX.ajax({
				url: '/bitrix/components/bitrix/faceid.tracker/ajax_more.php',
				method: 'POST',
				data: {last: lastVisitorTs},
				dataType: 'json',
				processData: false,
				start: true,
				onsuccess: function (json)
				{
					var j, moreResult = JSON.parse(json);

					if (moreResult.items.length)
					{
						for (j in moreResult.items)
						{
							appendVisitor(moreResult.items[j], false);
						}
					}

					if (moreResult.more == 0)
					{
						// no more
						BX('faceid-tracker-main-user-more').remove();
					}
				}
			});
		});

		BX.bind(BX('faceid-tracker-header-description-close'), 'click', function() {
			BX.localStorage.set("faceid-description-read", "1", 3600*24*360);
			BX(this).parentNode.remove();
		});

		if (!BX.localStorage.get("faceid-description-read"))
		{
			BX.show(BX('faceid-tracker-header-description-close').parentNode);
		}

		// disable auto mode
		BX.bind(BX('faceid-auto-identify'), 'change', function ()
		{
			if (!BX(this).checked)
			{
				resetPhotoMaker();
			}
		});
	});

	function isEmpty(obj) {
		if (obj.hasOwnProperty('length'))
			return obj.length == 0;

		for(var prop in obj) {
			if(obj.hasOwnProperty(prop))
				return false;
		}

		return JSON.stringify(obj) === JSON.stringify({});
	}

	BX.bind(BX('faceid-fullscreen-button'), 'click', function ()
	{
		var videoElement = video;
		var wrapper = BX.findParent(videoElement, {class:'faceid-tracker-wrapper'});

		var fsContainer = BX('faceid-video').parentNode;

		if (document.webkitFullscreenElement)
		{
			document.webkitCancelFullScreen();
		}
		else
		{
			fsContainer.webkitRequestFullscreen();
		}
	});

	BX.bind(document, 'webkitfullscreenchange', function ()
	{
		var wrapper = BX.findParent(video, {class:'faceid-tracker-wrapper'});
		var button = BX('faceid-fullscreen-button');

		BX.toggleClass(wrapper, 'faceid-tracker-full-mode');

		// hide instead of changing style
		BX.toggle(button);
		// and disable context menu
		BX.bind(wrapper, 'contextmenu', function(e){
			e.preventDefault();
		});

		// set height/width for the video
		if (!document.webkitFullscreenElement)
		{
			// small
			sizes.cameraWidth = sizes.cameraSmallWidth;
			sizes.cameraHeight = sizes.cameraSmallHeight;
			sizes.trackingRatio = sizes.trackingSmallRatio;
		}
		else
		{
			// full screen
			sizes.cameraWidth = sizes.cameraFullWidth;
			sizes.cameraHeight = sizes.cameraFullHeight;
			sizes.trackingRatio = sizes.trackingFullRatio;
		}

		video.setAttribute('width', sizes.cameraWidth);
		video.setAttribute('height', sizes.cameraHeight);

		overlay.setAttribute('width', sizes.cameraWidth);
		overlay.setAttribute('height', sizes.cameraHeight);
	});


	BX.bind(BX('faceid-tracker-workday-action-end'), 'click', function ()
	{
		var userId = currentUserInfo.id;
		endWorkday(userId);
	});

	function endWorkday(userId)
	{
		BX.hide(BX('faceid-tracker-workday-opened'));

		// ajax
		BX.ajax({
			url: '/bitrix/components/bitrix/faceid.timeman/ajax.php',
			method: 'POST',
			data: {action: 'close', id: userId},
			dataType: 'json',
			processData: false,
			start: true,
			onsuccess: function (json)
			{
				// if we still see the same user
				if (currentUserInfo && currentUserInfo.id == userId)
				{
					BX.show(BX('faceid-tracker-workday-ended'));
				}
			}
		});

	}
}