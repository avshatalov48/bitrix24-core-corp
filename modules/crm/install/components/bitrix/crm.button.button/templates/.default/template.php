<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\SiteButton\Manager;

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */

ob_start();
require ('layout.html');
echo str_replace(
	['%location%', '%colorBackground%', '%colorIcon%'],
	[$arResult['LOCATION'], $arResult['COLOR_BACKGROUND'], $arResult['COLOR_ICON']],
	ob_get_clean()
);
return;
?>
<div>
	<div data-b24-crm-button-shadow="" class="b24-widget-button-shadow"></div>
	<div style="display: none;">
		<a data-b24-crm-button-widget-blank="" data-b24-crm-button-widget="" class="b24-widget-button-social-item" title="">
			<i></i>
			<span data-b24-crm-button-tooltip="" class="b24-widget-button-social-tooltip"></span>
		</a>
	</div>

	<div dir="ltr" data-b24-crm-button-cont="" class="b24-widget-button-wrapper b24-widget-button-position-<?=$arResult['LOCATION']?>">
		<div data-b24-crm-hello-cont="" class="b24-widget-button-popup" style="border-color: <?=$arResult['COLOR_BACKGROUND']?>;">
			<span data-b24-hello-btn-hide="" class="b24-widget-button-popup-btn-hide"></span>
			<div class="b24-widget-button-popup-inner">
				<div class="b24-widget-button-popup-image">
					<span data-b24-hello-icon="" class="b24-widget-button-popup-image-item"></span>
				</div>
				<div class="b24-widget-button-popup-content">
					<div data-b24-hello-name="" class="b24-widget-button-popup-name"></div>
					<div data-b24-hello-text="" class="b24-widget-button-popup-description"></div>
				</div>
			</div>
			<div class="b24-widget-button-popup-triangle" style="border-color: <?=$arResult['COLOR_BACKGROUND']?>;"></div>
		</div>

		<div data-b24-crm-button-block="" class="b24-widget-button-social">

		</div>
		<div data-b24-crm-button-block-button="" class="b24-widget-button-inner-container">
			<div data-b24-crm-button-block-border="" class="b24-widget-button-inner-mask" style="background: <?=$arResult['COLOR_BACKGROUND']?>;"></div>
			<div class="b24-widget-button-block">
				<div data-b24-crm-button-pulse="" class="b24-widget-button-pulse" style="border-color: <?=$arResult['COLOR_BACKGROUND']?>;"></div>
				<div data-b24-crm-button-block-inner="" class="b24-widget-button-inner-block" style="background: <?=$arResult['COLOR_BACKGROUND']?>;">
					<div class="b24-widget-button-icon-container">
						<?if(in_array(Manager::ENUM_TYPE_CRM_FORM, $arResult['WIDGETS'])):?>
							<div data-b24-crm-button-icon="<?=Manager::ENUM_TYPE_CRM_FORM?>" class="b24-widget-button-inner-item">
								<svg class="b24-crm-button-icon" width="28" height="29" xmlns="http://www.w3.org/2000/svg">
									<path class="b24-crm-button-webform-icon" d="M25.99 7.744a2 2 0 012 2v11.49a2 2 0 01-2 2h-1.044v5.162l-4.752-5.163h-7.503a2 2 0 01-2-2v-1.872h10.073a3 3 0 003-3V7.744zM19.381 0a2 2 0 012 2v12.78a2 2 0 01-2 2h-8.69l-5.94 6.453V16.78H2a2 2 0 01-2-2V2a2 2 0 012-2h17.382z"
										  fill="<?=$arResult['COLOR_ICON']?>" fill-rule="evenodd"/>
								</svg>
							</div>
						<?endif;?>
						<?if(array_intersect([Manager::ENUM_TYPE_CALLBACK, Manager::ENUM_TYPE_WHATSAPP], $arResult['WIDGETS'])):?>
							<div data-b24-crm-button-icon="<?=Manager::ENUM_TYPE_CALLBACK?>" class="b24-widget-button-inner-item">
								<svg class="b24-crm-button-icon" xmlns="http://www.w3.org/2000/svg" width="28" height="28"
									viewBox="0 0 28 30">
									<path class="b24-crm-button-call-icon" fill="<?=$arResult['COLOR_ICON']?>" fill-rule="evenodd"
										d="M940.872414,978.904882 C939.924716,977.937215 938.741602,977.937215 937.79994,978.904882 C937.08162,979.641558 936.54439,979.878792 935.838143,980.627954 C935.644982,980.833973 935.482002,980.877674 935.246586,980.740328 C934.781791,980.478121 934.286815,980.265859 933.840129,979.97868 C931.757607,978.623946 930.013117,976.882145 928.467826,974.921839 C927.701216,973.947929 927.019115,972.905345 926.542247,971.731659 C926.445666,971.494424 926.463775,971.338349 926.6509,971.144815 C927.36922,970.426869 927.610672,970.164662 928.316918,969.427987 C929.300835,968.404132 929.300835,967.205474 928.310882,966.175376 C927.749506,965.588533 927.206723,964.77769 926.749111,964.14109 C926.29156,963.50449 925.932581,962.747962 925.347061,962.154875 C924.399362,961.199694 923.216248,961.199694 922.274586,962.161118 C921.55023,962.897794 920.856056,963.653199 920.119628,964.377388 C919.437527,965.045391 919.093458,965.863226 919.021022,966.818407 C918.906333,968.372917 919.274547,969.840026 919.793668,971.269676 C920.856056,974.228864 922.473784,976.857173 924.43558,979.266977 C927.085514,982.52583 930.248533,985.104195 933.948783,986.964613 C935.6148,987.801177 937.341181,988.444207 939.218469,988.550339 C940.510236,988.625255 941.632988,988.288132 942.532396,987.245549 C943.148098,986.533845 943.842272,985.884572 944.494192,985.204083 C945.459999,984.192715 945.466036,982.969084 944.506265,981.970202 C943.359368,980.777786 942.025347,980.091055 940.872414,978.904882 Z M940.382358,973.54478 L940.649524,973.497583 C941.23257,973.394635 941.603198,972.790811 941.439977,972.202844 C940.97488,970.527406 940.107887,969.010104 938.90256,967.758442 C937.61538,966.427182 936.045641,965.504215 934.314009,965.050223 C933.739293,964.899516 933.16512,965.298008 933.082785,965.905204 L933.044877,966.18514 C932.974072,966.707431 933.297859,967.194823 933.791507,967.32705 C935.117621,967.682278 936.321439,968.391422 937.308977,969.412841 C938.23579,970.371393 938.90093,971.53815 939.261598,972.824711 C939.401641,973.324464 939.886476,973.632369 940.382358,973.54478 Z M942.940854,963.694228 C940.618932,961.29279 937.740886,959.69052 934.559939,959.020645 C934.000194,958.902777 933.461152,959.302642 933.381836,959.8878 L933.343988,960.167112 C933.271069,960.705385 933.615682,961.208072 934.130397,961.317762 C936.868581,961.901546 939.347628,963.286122 941.347272,965.348626 C943.231864,967.297758 944.53673,969.7065 945.149595,972.360343 C945.27189,972.889813 945.766987,973.232554 946.285807,973.140969 L946.55074,973.094209 C947.119782,972.993697 947.484193,972.415781 947.350127,971.835056 C946.638568,968.753629 945.126778,965.960567 942.940854,963.694228 Z"
										transform="translate(-919 -959)"/>
								</svg>
							</div>
						<?endif;?>
						<?if(in_array(Manager::ENUM_TYPE_OPEN_LINE, $arResult['WIDGETS'])):?>
							<div data-b24-crm-button-icon="<?=Manager::ENUM_TYPE_OPEN_LINE?>" class="b24-widget-button-inner-item">
								<svg class="b24-crm-button-icon b24-crm-button-icon-active" xmlns="http://www.w3.org/2000/svg"
									width="28" height="28" viewBox="0 0 28 29">
									<path class="b24-crm-button-chat-icon" fill="<?=$arResult['COLOR_ICON']?>" fill-rule="evenodd"
										d="M878.289968,975.251189 L878.289968,964.83954 C878.289968,963.46238 876.904379,962 875.495172,962 L857.794796,962 C856.385491,962 855,963.46238 855,964.83954 L855,975.251189 C855,976.924031 856.385491,978.386204 857.794796,978.090729 L860.589592,978.090729 L860.589592,981.876783 L860.589592,983.76981 L861.521191,983.76981 C861.560963,983.76981 861.809636,982.719151 862.45279,982.823297 L866.179185,978.090729 L875.495172,978.090729 C876.904379,978.386204 878.289968,976.924031 878.289968,975.251189 Z M881.084764,971.465135 L881.084764,976.197702 C881.43316,978.604561 879.329051,980.755508 876.426771,980.93027 L868.042382,980.93027 L866.179185,982.823297 C866.400357,983.946455 867.522357,984.94992 868.973981,984.716324 L876.426771,984.716324 L879.221567,988.502377 C879.844559,988.400361 880.153166,989.448891 880.153166,989.448891 L881.084764,989.448891 L881.084764,987.555864 L881.084764,984.716324 L882.947962,984.716324 C884.517696,984.949819 885.742758,983.697082 885.742758,981.876783 L885.742758,974.304675 C885.742659,972.717669 884.517597,971.465135 882.947962,971.465135 L881.084764,971.465135 Z"
										transform="translate(-855 -962)"/>
								</svg>
							</div>
						<?endif;?>
					</div>
					<div class="b24-widget-button-inner-item b24-widget-button-close">
						<svg class="b24-widget-button-icon b24-widget-button-close-item" xmlns="http://www.w3.org/2000/svg" width="29" height="29" viewBox="0 0 29 29"><path fill="#FFF" fill-rule="evenodd" d="M18.866 14.45l9.58-9.582L24.03.448l-9.587 9.58L4.873.447.455 4.866l9.575 9.587-9.583 9.57 4.418 4.42 9.58-9.577 9.58 9.58 4.42-4.42"/></svg>
					</div>
				</div>
			</div>
		</div>
	</div>

</div>
