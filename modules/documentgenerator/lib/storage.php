<?php

namespace Bitrix\DocumentGenerator;

use Bitrix\Main\Entity\AddResult;

interface Storage
{
	/**
	 * Try to read content. Returns string on success, false on failure.
	 *
	 * @param mixed $path
	 * @return false|string
	 */
	public function read($path);

	/**
	 * Save $content. Returns value that can be used in Storage::read to get the same content.
	 *
	 * @param string $content
	 * @param array $options
	 * @return AddResult
	 */
	public function write($content, array $options = []);

	/**
	 * @param $path
	 * @param string $fileName
	 * @return bool
	 */
	public function download($path, $fileName = '');

	/**
	 * @param mixed $path
	 * @return bool
	 */
	public function delete($path);

	/**
	 * @param mixed $path
	 * @return int|false
	 */
	public function getModificationTime($path);

	/**
	 * @param array $file
	 * @return AddResult
	 */
	public function upload(array $file);

	/**
	 * @param mixed $path
	 * @return int
	 */
	public function getSize($path);
}

