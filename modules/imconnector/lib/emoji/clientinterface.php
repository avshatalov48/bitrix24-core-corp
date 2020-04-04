<?php
namespace Bitrix\ImConnector\Emoji;

interface ClientInterface
{
	/**
	 * First pass changes unicode characters into emoji markup.
	 * Second pass changes any shortnames into emoji markup.
	 *
	 * @param   string  $string The input string.
	 * @return  string  String with appropriate html for rendering emoji.
	 */
	public function toImage($string);

	/**
	 * This will output image markup (for png or svg) from shortname input.
	 *
	 * @param   string  $string The input string.
	 * @return  string  String with appropriate html for rendering emoji.
	 */
	public function shortnameToImage($string);

	/**
	 * This will output image markup (for png or svg) from unicode input.
	 *
	 * @param   string  $string The input string.
	 * @return  string  String with appropriate html for rendering emoji.
	 */
	public function unicodeToImage($string);
}
