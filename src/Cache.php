<?php namespace Dotink\Flourish {

	/**
	 * A simple caching interface for multiple backends
	 *
	 * @copyright Copyright (c) 2007-2015 Will Bond, Matthew J. Sahagian, others
	 * @author Will Bond [wb] <will@flourishlib.com>
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 *
	 * @license Please reference the LICENSE.md file at the root of this distribution
	 *
	 * @package Flourish
	 */
	class Cache
	{
		/**
		 * The cache configuration, used for database, directory and file caches
		 *
		 * @access protected
		 * @var array
		 */
		protected $config;

		/**
		 * The data store to use
		 *
		 * Either the:
		 *  - array structure for file cache
		 *  - Memcache or Memcached object for memcache
		 *  - Redis object for redis
		 *
		 * Not used for apc, directory or xcache
		 *
		 * @var mixed
		 */
		protected $dataStore;

		/**
		 * The type of cache
		 *
		 * The valid values are:
		 *  - `'apc'`
		 *  - `'database'`
		 *  - `'directory'`
		 *  - `'file'`
		 *  - `'memcache'`
		 *  - `'redis'`
		 *  - `'xcache'`
		 *
		 * @var string
		 */
		protected $type;

		/**
		 * Set the type and master key for the cache
		 *
		 * A `file` cache uses a single file to store values in an associative
		 * array and is probably not suitable for a large number of keys.
		 *
		 * Using an `apc` or `xcache` cache will have far better performance
		 * than a file or directory, however please remember that keys are shared
		 * server-wide.
		 *
		 * `$config` is an associative array of configuration options for the various
		 * backends. Some backend require additional configuration, while others
		 * provide provide optional settings.
		 *
		 * The following `$config` options must be set for the `database` backend:
		 *
		 *  - `table`: The database table to use for caching
		 *  - `key_column`: The column to store the cache key in - must support at least 250 character strings
		 *  - `value_column`: The column to store the serialized value in - this should probably be a `TEXT` column to support large values, or `BLOB` if binary serialization is used
		 *  - `value_data_type`: If a `BLOB` column is being used for the `value_column`, this should be set to 'blob', otherwise `string`
		 *  - `ttl_column`: The column to store the expiration timestamp of the cached entry - this should be an integer
		 *
		 * The following `$config` for the following items can be set for all backends:
		 *
		 *  - `serializer`: A callback to serialize data with, defaults to the PHP function `serialize()`
		 *  - `unserializer`: A callback to unserialize data with, defaults to the PHP function `unserialize()`
		 *
		 * Common serialization callbacks include:
		 *
		 *  - `json_encode`/`json_decode`
		 *  - `igbinary_serialize`/`igbinary_unserialize`
		 *
		 * Please note that using JSON for serialization will exclude all non-public
		 * properties of objects from being serialized.
		 *
		 * A custom `serialize` and `unserialze` option is `string`, which will cast
		 * all values to a string when storing, instead of serializing them. If a
		 * `__toString()` method is provided for objects, it will be called.
		 *
		 * @param string $type The type of caching to use: `'apc'`, `'database'`, `'directory'`, `'file'`, `'memcache'`, `'redis'`, `'xcache'`
		 * @param mixed $data_store The path for a `file` or `directory` cache, an `Memcache` or `Memcached` object for a `memcache` cache, an fDatabase object for a `database` cache or a `Redis` object for a `redis` cache - not used for `apc` or `xcache`
		 * @param array $config Configuration options - see method description for details
		 * @return void
		 */
		public function __construct($type, $data_store=NULL, $config=array())
		{
			switch ($type) {
				case 'directory':
					$exists = file_exists($data_store);

					if (!$exists) {
						throw new EnvironmentException(
							'The directory specified, %s, does not exist',
							$data_store
						);
					}

					if (!is_dir($data_store)) {
						throw new EnvironmentException(
							'The path specified, %s, is not a directory',
							$data_store
						);
					}

					if (!is_writable($data_store)) {
						throw new EnvironmentException(
							'The directory specified, %s, is not writable',
							$data_store
						);
					}

					$this->config['path'] = realpath($data_store) . DIRECTORY_SEPARATOR;
					break;

				case 'file':
					$exists = file_exists($data_store);

					if (!$exists && !is_writable(dirname($data_store))) {
						throw new EnvironmentException(
							'The file specified, %s, does not exist and the directory ' .
							'it in inside of is not writable',
							$data_store
						);
					}

					if ($exists && !is_writable($data_store)) {
						throw new EnvironmentException(
							'The file specified, %s, is not writable',
							$data_store
						);
					}

					$this->config['path'] = $data_store;

					if ($exists) {
						$this->dataStore = unserialize(file_get_contents($data_store));

					} else {
						$this->dataStore = array();
					}

					$this->config['state'] = 'clean';
					break;

				case 'memcache':
					$data_store = '\\'. $data_store;

					if (!class_exists($data_store)) {
						throw new ProgrammerException(
							'The data store provided, %s, is not an available class',
							$data_store
						);
					}

					$this->dataStore = new $data_store();
					break;

				case 'redis':
					$data_store = '\\'. $data_store;

					if (!class_exists($data_store)) {
						throw new ProgrammerException(
							'The data store provided, %s, is not an available class',
							$data_store
						);
					}

					$this->dataStore = new $data_store();

					if (count($config)) {
						$params  = array();
						$host    = isset($config['host'])    ? $config['host']    : '127.0.0.1';
						$port    = isset($config['port'])    ? $config['port']    : 6379;
						$timeout = isset($config['timeout']) ? $config['timeout'] : 2.5;

						$this->dataStore->connect($host, $port, $timeout);
					}

					break;

				case 'apc':
				case 'xcache':
					if (!extension_loaded($type)) {
						throw new EnvironmentException(
							'The %s extension does not appear to be installed',
							$type
						);
					}
					break;

				default:
					throw new ProgrammerException(
						'The type specified, %s, is not a valid cache type. Must be one of: %s.',
						$type,
						join(', ', ['apc', 'directory', 'file', 'memcache', 'redis', 'xcache'])
					);
			}

			$this->config['serializer'] = isset($config['serializer'])
				? $config['serializer']
				: 'serialize';

			$this->config['unserializer'] = isset($config['unserializer'])
				? $config['unserializer']
				: 'unserialize';

			$this->type = $type;
		}


		/**
		 * Cleans up after the cache object
		 *
		 * @access public
		 * @return void
		 */
		public function __destruct()
		{
			// Only sometimes clean the cache of expired values
			if (rand(0, 99) == 50) {
				$this->clean();
			}

			$this->save();
		}


		/**
		 * Tries to set a value to the cache, but stops if a value already exists
		 *
		 * @access public
		 * @param string $key The key to store as, this should not exceed 250 characters
		 * @param mixed $value The value to store, this will be serialized
		 * @param integer $ttl The number of seconds to keep the cache valid for, 0 for no limit
		 * @return boolean If the key/value pair were added successfully
		 */
		public function add($key, $value, $ttl=0)
		{
			$value = $this->serialize($value);

			switch ($this->type) {
				case 'apc':
					return apc_add($key, $value, $ttl);

				case 'file':
					$key_added = isset($this->dataStore[$key]) && (
						(
							(
								$this->dataStore[$key]['expire']
								&& $this->dataStore[$key]['expire'] >= time()
							)
							|| !$this->dataStore[$key]['expire']
						)
					);

					if ($key_added) {
						return FALSE;
					}

					$this->config['state'] = 'dirty';
					$this->dataStore[$key] = array(
						'value'  => $value,
						'expire' => (!$ttl) ? 0 : time() + $ttl
					);

					return TRUE;

				case 'directory':
					if (file_exists($this->config['path'] . $key)) {
						return FALSE;
					}

					$expiration_date = (!$ttl) ? 0 : time() + $ttl;

					file_put_contents(
						$this->config['path'] . $key,
						$expiration_date . "\n" . $value
					);

					return TRUE;

				case 'memcache':
					if ($ttl > 2592000) {
						$ttl = time() + 2592000;
					}

					if ($this->dataStore instanceof Memcache) {
						return $this->dataStore->add($key, $value, 0, $ttl);
					}

					return $this->dataStore->add($key, $value, $ttl);

				case 'redis':
					if (!$ttl) {
						return $this->dataStore->setnx($key, $value);
					}

					if ($this->dataStore->exists($key)) {
						return FALSE;
					}

					$this->dataStore->setex($key, $ttl, $value);

					return TRUE;

				case 'xcache':
					if (xcache_isset($key)) {
						return FALSE;
					}

					xcache_set($key, $value, $ttl);

					return TRUE;
			}
		}


		/**
		 * Removes all cache entries that have expired
		 *
		 * @access public
		 * @return void
		 */
		public function clean()
		{
			switch ($this->type) {
				case 'directory':
					$clear_before = time();
					$files        = array_diff(scandir($this->config['path']), array('.', '..'));

					foreach ($files as $file) {
						if (!file_exists($this->config['path'] . $file)) {
							continue;
						}

						$handle          = fopen($this->config['path'] . $file, 'r');
						$expiration_date = trim(fgets($handle));

						fclose($handle);

						if ($expiration_date && $expiration_date < $clear_before) {
							unlink($this->config['path'] . $file);
						}
					}

					break;

				case 'file':
					$clear_before = time();

					foreach ($this->dataStore as $key => $value) {
						if ($value['expire'] && $value['expire'] < $clear_before) {
							unset($this->dataStore[$key]);

							$this->config['state'] = 'dirty';
						}
					}

					break;
			}
		}


		/**
		 * Clears the WHOLE cache of every key, use with caution!
		 *
		 * @return boolean If the cache was successfully cleared
		 */
		public function clear()
		{
			switch ($this->type) {
				case 'apc':
					return apc_clear_cache('user');

				case 'directory':
					$files = array_diff(scandir($this->config['path']), array('.', '..'));
					$success = TRUE;
					foreach ($files as $file) {
						$success = unlink($this->config['path'] . $file) && $success;
					}
					return $success;

				case 'file':
					$this->dataStore = array();
					$this->config['state'] = 'dirty';
					return TRUE;

				case 'memcache':
					return $this->dataStore->flush();

				case 'redis':
					return $this->dataStore->flushDB();

				case 'xcache':
					Core::startErrorCapture();
					xcache_clear_cache(XC_TYPE_VAR, 0);

					return (bool) Core::stopErrorCapture();
			}
		}


		/**
		 * Deletes a value from the cache
		 *
		 * @access public
		 * @param string $key The key to delete
		 * @return boolean If the delete succeeded
		 */
		public function delete($key)
		{
			switch ($this->type) {
				case 'apc':
					return apc_delete($key);

				case 'directory':
					return unlink($this->config['path'] . $key);

				case 'file':
					if (isset($this->dataStore[$key])) {
						unset($this->dataStore[$key]);
						$this->config['state'] = 'dirty';
					}

					return TRUE;

				case 'memcache':
					return $this->dataStore->delete($key, 0);

				case 'redis':
					return (bool) $this->dataStore->delete($key);

				case 'xcache':
					return xcache_unset($key);
			}
		}


		/**
		 * Returns a value from the cache
		 *
		 * @access public
		 * @param string $key The key to return the value for
		 * @param mixed $default The value to return if the key did not exist
		 * @return mixed The cached value or the default value if no cached value was found
		 */
		public function get($key, $default=NULL)
		{
			switch ($this->type) {
				case 'apc':
					$value = apc_fetch($key);

					if ($value === FALSE) {
						return $default;
					}

					break;

				case 'directory':
					if (!file_exists($this->config['path'] . $key)) {
						return $default;
					}

					$handle          = fopen($this->config['path'] . $key, 'r');
					$expiration_date = fgets($handle);

					if ($expiration_date != 0 && $expiration_date < time()) {
						return $default;
					}

					$value = '';

					while (!feof($handle)) {
						$value .= fread($handle, 524288);
					}

					fclose($handle);

					break;

				case 'file':
					if (isset($this->dataStore[$key])) {
						$expire = $this->dataStore[$key]['expire'];

						if (!$expire || $expire >= time()) {
							$value = $this->dataStore[$key]['value'];

						} elseif ($expire) {
							unset($this->dataStore[$key]);
							$this->config['state'] = 'dirty';
						}
					}

					if (!isset($value)) {
						return $default;
					}

					break;

				case 'memcache':
					$value = $this->dataStore->get($key);

					if ($value === FALSE) {
						return $default;
					}

					break;

				case 'redis':
					$value = $this->dataStore->get($key);

					if ($value === FALSE) {
						return $default;
					}

					break;

				case 'xcache':
					$value = xcache_get($key);

					if ($value === FALSE) {
						return $default;
					}
			}

			return $this->unserialize($value);
		}


		/**
		 * Only valid for `file` caches, saves the file to disk
		 *
		 * @access public
		 * @return void
		 */
		public function save()
		{
			if ($this->type != 'file' || $this->config['state'] == 'clean') {
				return;
			}

			file_put_contents($this->config['path'], serialize($this->dataStore));

			$this->config['state'] = 'clean';
		}


		/**
		 * Serializes a value before storing it in the cache
		 *
		 * @param mixed $value The value to serialize
		 * @return string The serialized value
		 */
		protected function serialize($value)
		{
			if ($this->config['serializer'] == 'string') {
				if (is_object($value) && method_exists($value, '__toString')) {
					return $value->__toString();
				}

				return (string) $value;
			}

			return call_user_func($this->config['serializer'], $value);
		}


		/**
		 * Sets a value to the cache, overriding any previous value
		 *
		 * @access public
		 * @param string $key The key to store as, this should not exceed 250 characters
		 * @param mixed $value The value to store, this will be serialized
		 * @param integer $ttl The number of seconds to keep the cache valid for, 0 for no limit
		 * @return boolean If the value was successfully saved
		 */
		public function set($key, $value, $ttl=0)
		{
			$value = $this->serialize($value);

			switch ($this->type) {
				case 'apc':
					return apc_store($key, $value, $ttl);

				case 'directory':
					$expiration_date = (!$ttl) ? 0 : time() + $ttl;

					return (bool) file_put_contents(
						$this->config['path'] . $key,
						$expiration_date . "\n" . $value
					);

				case 'file':
					$this->dataStore[$key] = array(
						'value'  => $value,
						'expire' => (!$ttl) ? 0 : time() + $ttl
					);

					$this->config['state'] = 'dirty';
					return TRUE;

				case 'memcache':
					if ($ttl > 2592000) {
						$ttl = time() + 2592000;
					}

					if ($this->dataStore instanceof Memcache) {
						$result = $this->dataStore->replace($key, $value, 0, $ttl);

						if (!$result) {
							return $this->dataStore->set($key, $value, 0, $ttl);
						}

						return $result;
					}

					return $this->dataStore->set($key, $value, $ttl);

				case 'redis':
					if ($ttl) {
						return $this->dataStore->setex($key, $value, $ttl);
					}

					return $this->dataStore->set($key, $value);

				case 'xcache':
					return xcache_set($key, $value, $ttl);
			}
		}


		/**
		 * Unserializes a value before returning it
		 *
		 * @access protected
		 * @param string $value The serialized value
		 * @return mixed The PHP value
		 */
		protected function unserialize($value)
		{
			if ($this->config['unserializer'] == 'string') {
				return $value;
			}

			return call_user_func($this->config['unserializer'], $value);
		}
	}
}
