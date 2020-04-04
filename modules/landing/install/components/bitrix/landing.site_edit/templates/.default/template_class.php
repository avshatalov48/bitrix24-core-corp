<?php
namespace Bitrix\Landing\Components\LandingEdit;

use \Bitrix\Main\Localization\Loc;

class Template
{
	/**
	 * Result of template.
	 * @var array
	 */
	private $result = array();

	/**
	 * Constructor.
	 * @param array $result Result of template.
	 */
	public function __construct($result)
	{
		$this->result = $result;
	}

	/**
	 * Display simple hook.
	 * @param string $code Code of hook.
	 * @return void
	 */
	public function showSimple($code)
	{
		$code = strtoupper($code);
		$hooks = isset($this->result['HOOKS'])
					? $this->result['HOOKS']
					: array();

		if (isset($hooks[$code]))
		{
			$pageFields = $hooks[$code]->getPageFields();

			?><div class="ui-checkbox-hidden-input"><?

				// use-checkbox
				if (isset($pageFields[$code . '_USE']))
				{
					$type = $pageFields[$code . '_USE']->getType();
					$pageFields[$code . '_USE']->viewForm(array(
					  	'class' => self::getCssByType($type),
						'id' => 'checkbox-' . strtolower($code) . '-use',
						'name_format' => 'fields[ADDITIONAL_FIELDS][#field_code#]'
					));
				}

				?><div class="ui-checkbox-hidden-input-inner"><?

				// use-label
				if (isset($pageFields[$code . '_USE']))
				{
					?>
						<label class="ui-checkbox-label" for="<?= 'checkbox-' . strtolower($code) . '-use';?>">
							<?= $pageFields[$code . '_USE']->getLabel();?>
						</label>
					<?
					if ($hooks[$code]->isLocked())
					{
						?>
						<span class="landing-icon-lock"></span>
						<script type="text/javascript">
							BX.ready(function()
							{
								if (typeof BX.Landing.PaymentAlert !== 'undefined')
								{
									BX.Landing.PaymentAlert({
										nodes: [BX('<?= 'checkbox-' . strtolower($code) . '-use';?>')],
										title: '<?= \CUtil::jsEscape(Loc::getMessage('LANDING_TPL_HTML_DISABLED_TITLE'));?>',
										message: '<?= \CUtil::jsEscape($hooks[$code]->getLockedMessage());?>'
									});
								}
							});
						</script>
						<?
					}
					unset($pageFields[$code . '_USE']);
				}

				// display field
				foreach ($pageFields as $key => $field)
				{
					$type = $field->getType();
					echo '<div class="ui-checkbox-hidden-input-hook">';
					echo $field->viewForm(array(
						'id' => 'field-' . strtolower($key) . '-use',
						'class' => self::getCssByType($type),
						'name_format' => 'fields[ADDITIONAL_FIELDS][#field_code#]'
					));
					if ($type == 'checkbox')
					{
						echo '<label for="field-' . strtolower($key) . '-use">' .
								$field->getLabel() .
							'</label>';
					}
					if ($help  = $field->getHelpValue())
					{
						echo '<div class="ui-checkbox-hidden-input-hook-help">' . $help . '</div>';
					}
					echo '</div>';
				}

				?></div><?

			?></div><?
		}
	}

	/**
	 * Display picture.
	 * @param \Bitrix\Landing\Field $field Picture field for display.
	 * @param string $imgPath Path to img by default.
	 * @param array $params Some params.
	 * @return void
	 */
	public function showPictureJS(\Bitrix\Landing\Field $field, $imgPath = '', $params = array())
	{
		if (!isset($params['imgId']))
		{
			return;
		}

		$imgId = $field->getValue();
		$code = strtolower($field->getCode());
		$code = preg_replace('/[^a-z]+/', '', $code);
		?>
		<script type="text/javascript">
			BX.ready(function()
			{
				var imageFieldWrapper = BX('<?= $params['imgId']?>');
				var imageFieldInput = BX('landing-form-<?= $code?>-input');

				if (imageFieldWrapper)
				{
					var imageField = new BX.Landing.UI.Field.Image({
						id: 'page_settings_<?= $code?>',
						disableLink: true,
                        disableAltField: true,
                        allowClear: true
						<?if ($imgId):?>
						,content: {
							src: '<?= \CUtil::jsEscape(str_replace(' ', '%20', \htmlspecialcharsbx((int) $imgId > 0 ? \Bitrix\Landing\File::getFilePath($imgId) : $imgId)));?>',
							id : <?= $imgId ? intval($imgId) : -1?>,
							alt : ''
						}
						<?else:?>
						,content: {
							src: '<?= \CUtil::jsEscape(str_replace(' ', '%20', \htmlspecialcharsbx($imgPath)));?>',
							id : -1,
							alt : ''
						}
						<?endif;?>
						<?if (isset($params['width']) && isset($params['height'])):?>
						,dimensions: {
							width: <?= (int)$params['width']?>,
							height: <?= (int)$params['height']?>
						}
						<?endif;?>
						<?if (isset($params['uploadParams']) && !empty($params['uploadParams'])):?>
						,uploadParams: <?= \CUtil::phpToJsObject($params['uploadParams']);?>
						<?endif;?>
					});

					if (imageFieldWrapper)
					{
						imageFieldWrapper.appendChild(imageField.layout);
						if (imageFieldInput)
						{
							imageField.layout.addEventListener('input', function()
							{
								var img = imageField.getValue();
								imageFieldInput.value = parseInt(img.id) > 0
													? img.id
													: img.src;
							});
						}
						<?if (isset($params['imgEditId'])):?>
						BX.bind(BX('<?= $params['imgEditId']?>'), 'click', function (event)
						{
							imageField.onUploadClick(event);
						});
						<?endif;?>
					}
				}
			});
		</script>
		<?
		$field->viewForm(array(
			'id' => 'landing-form-' . $code . '-input',
			'name_format' => 'fields[ADDITIONAL_FIELDS][#field_code#]'
		));
	}

	/**
	 * Get css-class by field type.
	 * @param $type
	 * @return string
	 */
	public function getCssByType($type)
	{
		$css = '';

		switch ($type)
		{
			case 'select':
				{
					$css = 'ui-select';
					break;
				}
			case 'text':
				{
					$css = 'ui-input';
					break;
				}
			case 'checkbox':
				{
					$css = 'ui-checkbox';
					break;
				}
		}

		return $css;
	}
}
