<?php
namespace S3;
use \Fuel\Core\Upload as F;

require_once PKGPATH . 's3/s3-php5-curl/S3.php';

class Upload extends \Fuel\Core\Upload {

	public static function _init()
	{
		parent::_init();
		\Config::load('s3', true);
		static::$config = array_merge(static::$config, \Config::get('s3', array()));
	}

	/**
	 * save uploaded file(s)
	 *
	 * @param	mixed	if int, $files element to move. if array, list of elements to move, if none, move all elements
	 * @param	string	path to move to
	 * @return	void
	 */
	public static function save($file, $save_as)
	{
		// get access keys
		$access_key_id = static::$config['access_key_id'];
		$secret_access_key = static::$config['secret_access_key'];
		$bucket_name = static::$config['bucket_name'];
		$enable_ssl = static::$config['enable_ssl'];

		// create s3 object
		$s3 = new \S3($access_key_id, $secret_access_key, $enable_ssl);

		// path to save the files to
		$path = static::$config['path'];

		// files to save
		$files = array();
		$s3->putObject(file_get_contents($file), $bucket_name, $save_as, \S3::ACL_PUBLIC_READ);
	}
}
