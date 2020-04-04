
function BXFaceIdStart(settings)
{
	var deviceId = BX.localStorage.get("faceid-default-camera");

	var video = document.getElementById('faceid-video');
	var canvas = document.getElementById('faceid-canvas');
	var startbutton = document.getElementById('faceid-startbutton');

	var streaming = false;

	// collection of sizes and proportions
	var sizes = {
		cameraRatio: 0,
		screenRatio: window.screen.width/window.screen.height,

		cameraSmallWidth: 640,
		cameraSmallHeight: 0,

		snapshotWidth: 640,
		snapshotHeight: 0,

		cameraWidth: 0, // current value
		cameraHeight: 0 // current value
	};

	//if (window.FACEID_AGREEMENT)
	{
		buildCameraList();
	}

	function buildCameraList()
	{
		navigator.mediaDevices.enumerateDevices().then(function(devices)
		{
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
						label = BX.message('FACEID_TRACKER1C_CMP_JS_CAMERA_DEFAULT');
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
			startupFailed(err.message);
		});
	}

	function startup()
	{
		initStream();

		video.addEventListener('canplay', function(ev)
		{
			if (!streaming)
			{
				// define different sizes and props
				sizes.cameraRatio = video.videoWidth / video.videoHeight;

				// camera
				sizes.cameraSmallHeight = video.videoHeight / (video.videoWidth / sizes.cameraSmallWidth);

				// snapshots
				sizes.snapshotHeight = video.videoHeight / (video.videoWidth / sizes.snapshotWidth);

				// current values
				sizes.cameraWidth = sizes.cameraSmallWidth;
				sizes.cameraHeight = sizes.cameraSmallHeight;

				video.setAttribute('width', sizes.cameraWidth);
				video.setAttribute('height', sizes.cameraHeight);

				BX('faceid-sent-snapshot-canvas').setAttribute('width', sizes.cameraWidth);
				BX('faceid-sent-snapshot-canvas').setAttribute('height', sizes.cameraHeight);

				canvas.setAttribute('width', sizes.snapshotWidth);
				canvas.setAttribute('height', sizes.snapshotHeight);

				streaming = true;

				// show video
				BX.hide(BX('faceid-1c-loader'));
				BX.show(video.parentNode);
			}
		}, false);

		// manual photo button
		startbutton.addEventListener('click', function(ev){
			takepicture();
		}, false);
	}

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
					msg = BX.message('FACEID_TRACKER1C_CMP_JS_CAMERA_NOT_FOUND');
				}
				else
				{
					msg = BX.message('FACEID_TRACKER1C_CMP_JS_CAMERA_NO_SUPPORT');
				}

				startupFailed(msg);
			}
		);
	}

	function startupFailed(msg)
	{
		BX('faceid-camera-error').innerHTML = msg;
		BX.show(BX('faceid-camera-error'));
	}

	function takepicture(snapshotSrc)
	{
		if (!snapshotSrc)
		{
			var context = canvas.getContext('2d');
			context.drawImage(video, 0, 0, sizes.snapshotWidth, sizes.snapshotHeight);
			snapshotSrc = canvas.toDataURL('image/jpeg', 0.85);
		}

		// split to every face
		var tmpImg = new Image();
		tmpImg.onload = function ()
		{
			// single face
			handleNewVisitorFace(tmpImg.getAttribute('src'));

		};

		tmpImg.src = snapshotSrc;
		BX('faceid-sent-snapshot').setAttribute('src', snapshotSrc);
	}

	function handleNewVisitorFace(imageData)
	{
		toggleProgressButton();

		BX.ajax({
			url: settings.AJAX_IDENTIFY_URL,
			method: 'POST',
			data: {action: 'identify', image: imageData, auth: settings.OAUTH_TOKEN},
			dataType: 'json',
			processData: false,
			start: true,
			onsuccess: function (json) {

				var ok = false;
				var errorMessage = BX.message('FACEID_TRACKER1C_CMP_JS_AJAX_ERROR');
				var response;
				var delayClose = 2000;

				// do we see the same user?
				if (json.length)
				{
					//var result = JSON.parse(json);
					// return raw json
					response = json;

					var res = JSON.parse(json);

					if (res.error && res.error.msg)
					{
						response = '{"contragents":{}, "error": "'+res.error.msg+'"}';
						showAjaxError(res.error.msg);
						delayClose = 5000;
					}
					else
					{
						var context = BX('faceid-sent-snapshot-canvas').getContext('2d');

						var color = [145, 255, 79, 255];

						for (var i in res.contragents)
						{
							var overlayDetection = {
								x: res.contragents[i].FACE_X,
								y: res.contragents[i].FACE_Y,
								width: res.contragents[i].FACE_WIDTH,
								height: res.contragents[i].FACE_HEIGHT
							};

							visualizeDetection(context, overlayDetection, 10, color);
						}
					}
				}
				else
				{
					// return json with error ajax
					response = '{"contragents":{}, "error": "'+errorMessage+'"}';
					showAjaxError(errorMessage);
				}


				setTimeout(function(){
					sendResponseTo1C(response);
				}, delayClose);
			},
			onfailure: function () {
				var errorMessage = BX.message('FACEID_TRACKER1C_CMP_JS_AJAX_ERROR');
				response = '{"contragents":{}, "error": "'+errorMessage+'"}';
				sendResponseTo1C(response);
			}
		});
	}

	function sendResponseTo1C(response)
	{
		toggleProgressButton();

		BX.ajax({
			url: 'http://127.0.0.1:22017/stop_browser.bx',
			method: 'POST',
			data: {response: response},
			dataType: 'json',
			processData: false,
			start: true
		});
	}

	function visualizeDetection(ctx, xywh, radius, color) {
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
			ctx.stroke();
			ctx.restore();
		} catch (err) {
			console.log("BAD VALUE OF DETECTION");
			console.log(err);
		}
	}

	function toggleProgressButton()
	{
		BX.toggle(BX('faceid-startbutton'));
		BX.toggle(BX('faceid-startbutton-progress'));
	}

	function showAjaxError(err)
	{
		BX.addClass(BX('faceid-1c-ajax-error'), 'faceid-error-message-active');
		BX.findChild(BX('faceid-1c-ajax-error'), {class:'faceid-error-message-text'}).innerText = err;
	}

	// settings button
	BX.bind(BX('faceid-settings-button'), 'click', function(){
		BX.toggle(BX('faceid-settings-container'));
	});
}