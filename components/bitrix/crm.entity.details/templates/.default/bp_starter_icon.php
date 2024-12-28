<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
?>
<svg width="30" height="30" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
	<!-- First Path -->
	<path id="rectangle_1" d="M3.567,4.965966c0-.514004.464808-.929966,1.038107-.929966h8.093194c.574363,0,1.039171.416914,1.039171.929966v2.51957c0,.514004-.465872.929014-1.039171.929014h-8.093194C4.03202,8.414026,3.567586,7.998399,3.567,7.485536v-2.51957Z" transform="matrix(0.939971 0.002939000000000025 -0.0032849999999999824 1.050571 0.2447600000000003 -0.2105870000000003)" fill="#2fc6f6"></path>

	<!-- Second Path -->
	<path id="rectangle_2" d="M7.094,10.674c0-.54.437-.977.977-.977h7.859c.539,0,.976.438.976.977v2.647c0,.539-.437.976-.976.976h-7.86c-.538801-.000551-.975449-.437199-.976-.976v-2.647Z" transform="matrix(0.999994 -0.003381999999999996 0.003381999999999996 0.999994 -0.04050499999999957 0.04365299999999994)" fill="#2fc6f6"></path>

	<!-- Third Path -->
	<path id="rectangle_3" d="M11.022,16.335c0-.54.437-.977.976-.977h7.609c.54,0,.977.438.977.977v2.647c0,.54-.438.976-.977.976h-7.609c-.538801-.000551-.975449-.437199-.976-.976v-2.647Z" transform="matrix(0.999997 -0.0025780000000000247 0.002593999999999985 1.006283 -0.048753000000000046 -0.06819999999999915)" fill="#2fc6f6"></path>

	<!-- Fourth Path -->
	<path id="top_right_corner" d="M14.733,6.338c0-.212173.084285-.415656.234315-.565685s.353512-.234315.565685-.234315h2.008c1.215026,0,2.2.984974,2.2,2.2v2.831c0,.441828-.358172.8-.8.8s-.8-.358172-.8-.8v-2.831c0-.15913-.063214-.311742-.175736-.424264s-.265134-.175736-.424264-.175736h-2.008c-.212173,0-.415656-.084285-.565685-.234315s-.234315-.353512-.234315-.565685Z" opacity="0" fill="#2fc6f6">
		<animate attributeName="opacity" from="0" to="1" dur="2s" begin="1s" fill="freeze"></animate>
	</path>

	<!-- Fifth Path -->
	<path id="bottom_left_corner" d="M6.158,16.56h2.4c.441828,0,.8.358172.8.8s-.358172.8-.8.8h-2.4c-1.215026,0-2.2-.984974-2.2-2.2v-2.556c0-.441828.358172-.8.8-.8s.8.358172.8.8v2.555c0,.15913.063214.311742.175736.424264s.265134.175736.424264.175736v.001Z" opacity="0" fill="#2fc6f6">
		<animate attributeName="opacity" from="0" to="1" dur="1.5s" begin="1.5s" fill="freeze"></animate>
	</path>
</svg>
<script>
	BX.Event.ready(() => { //TODO temporary animation, delete it at next release
		function animateIcon()
		{
			animateElement(
				'rectangle_1',
				[0.80042, -0.492_831, 0.550_819, 0.8946, -5.2571, 5.590_011],
				[0.939_971, 0.002_939, -0.003_285, 1.050_571, 0.24476, -0.210_587],
			);
			animateElement(
				'rectangle_2',
				[0.963_765, 0.266_754, -0.266_754, 0.963_765, 4.872_652, -3.823_654],
				[0.999_994, -0.003_382, 0.003_382, 0.999_994, -0.040_505, 0.043_653],
			);
			animateElement(
				'rectangle_3',
				[0.948_251, -0.317_523, 0.319_519, 0.954_211, -2.046_983, 8.455_588],
				[0.999_997, -0.002_578, 0.002_594, 1.006_283, -0.048_753, -0.0682],
			);
		}

		function animateElement(elementId, startMatrix, endMatrix, duration = 1000)
		{
			const element = document.getElementById(elementId);
			let startTime = 0;
			if (!element)
			{
				return;
			}

			const stepAnimation = (timestamp) => {
				if (!startTime) {
					startTime = timestamp;
				}

				const elapsed = timestamp - startTime;
				const progress = Math.min(elapsed / duration, 1);
				const currentMatrix = animateMatrix(startMatrix, endMatrix, progress);

				element.setAttribute('transform', `matrix(${currentMatrix.join(' ')})`);

				if (progress < 1) {
					requestAnimationFrame(stepAnimation);
				}
			};

			requestAnimationFrame(stepAnimation);
		}

		function lerp(start, end, t)
		{
			return start + (end - start) * t;
		}

		function animateMatrix(startMatrix, endMatrix, progress)
		{
			return startMatrix.map(
				(startVal, index) => lerp(startVal, endMatrix[index], progress),
			);
		}

		animateIcon();
	});
</script>