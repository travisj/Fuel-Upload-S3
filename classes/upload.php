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
	public static function save()
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

		// check for parameters
		if (func_num_args())
		{
			foreach(func_get_args() as $param)
			{
				// string => new path to save to
				if (is_string($param))
				{
					$path = $param;
				}
				// array => list of $files indexes to save
				elseif(is_array($param))
				{
					$files = array();
					foreach($param as $key)
					{
						if (isset(static::$files[(int) $key]))
						{
							$files[(int) $key] = static::$files[(int) $key];
						}
					}
				}
				// integer => files index to save
				elseif(is_numeric($param))
				{
					if (isset(static::$files[$param]))
					{
						$files = array(static::$files[$param]);
					}
				}
			}
		}
		else
		{
			// save all files
			$files = static::$files;
		}

		// anything to save?
		if (empty($files))
		{
			throw new Exception('No uploaded files are selected.');
		}

		// now that we have a path, let's save the files
		foreach($files as $key => $file)
		{
			// skip all files in error
			if ($file['error'] != 0)
			{
				continue;
			}

			// do we need to generate a random filename?
			if ( (bool) static::$config['randomize'])
			{
				$filename = md5(serialize($file));
			}
			else
			{
				$filename  = $file['filename'];
				if ( (bool) static::$config['normalize'])
				{
					$filename = \Inflector::friendly_title($filename, '_');
				}
			}

			// array with the final filename
			$save_as = array(
				static::$config['prefix'],
				$filename,
				static::$config['suffix'],
				'',
				'.',
				empty(static::$config['extension']) ? $file['extension'] : static::$config['extension']
			);
			// remove the dot if no extension is present
			if (empty($save_as[5]))
			{
				$save_as[4] = '';
			}

			// need to modify case?
			switch(static::$config['change_case'])
			{
			case 'upper':
				$save_as = array_map(function($var) { return strtoupper($var); }, $save_as);
				break;

			case 'lower':
				$save_as = array_map(function($var) { return strtolower($var); }, $save_as);
				break;

			default:
				break;
			}


			// check if the file already exists
			/*
			if (false !== ($object = $s3->getObject($bucket_name, $path.implode('', $save_as))))
			{
				if ( (bool) static::$config['auto_rename'])
				{
					$counter = 0;
					do
					{
						$save_as[3] = '_'.++$counter;
					}
					while ($s3->getObject($bucket_name, $path.implode('', $save_as)));
				}
				else
				{
					if ( ! (bool) static::$config['overwrite'])
					{
						static::$files[$key]['error'] = static::UPLOAD_ERR_DUPLICATE_FILE;
						continue;
					}
				}
			}
			 */

			// no need to store it as an array anymore
			$save_as = implode('', $save_as);

			// does the filename exceed the maximum length?
			if ( ! empty(static::$config['max_length']) and strlen($save_as) > static::$config['max_length'])
			{
				static::$files[$key]['error'] = static::UPLOAD_ERR_MAX_FILENAME_LENGTH;
				continue;
			}

			// if no error was detected, move the file
			if (static::$files[$key]['error'] == UPLOAD_ERR_OK)
			{
				// save the additional information
				static::$files[$key]['saved_to'] = $path;
				static::$files[$key]['saved_as'] = $save_as;
				static::$files[$key]['s3_url'] = $bucket_name.$path.$save_as;

				// before callback defined?
				if (array_key_exists('before', static::$callbacks) and ! is_null(static::$callbacks['before']))
				{
					// get the callback method
					$callback = static::$callbacks['before'][0];

					// call the callback
					if (is_callable($callback))
					{
						$result = call_user_func_array($callback, array(&static::$files[$key]));
						if (is_numeric($result))
						{
							static::$files[$key]['error'] = $result;
						}
					}
				}

				// move the uploaded file
				if (static::$files[$key]['error'] == UPLOAD_ERR_OK)
				{
					// send this file up to aamazon s3
					$s3->putObject(file_get_contents($file['file']), $bucket_name, $path.$save_as, \S3::ACL_PUBLIC_READ);

					// after callback defined?
					if (array_key_exists('after', static::$callbacks) and ! is_null(static::$callbacks['after']))
					{
						// get the callback method
						$callback = static::$callbacks['after'][0];

						// call the callback
						if (is_callable($callback))
						{
							$result = call_user_func_array($callback, array(&static::$files[$key]));
							if (is_numeric($result))
							{
								static::$files[$key]['error'] = $result;
							}
						}
					}
				}
			}
		}
	}
}
