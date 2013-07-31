# Cache
## A simple caching interface for multiple backends

_Copyright (c) 2007-2011 Will Bond, others_.
_Please see the LICENSE file at the root of this distribution_

#### Namespace

`Dotink\Flourish`

#### Authors

<table>
	<thead>
		<th>Name</th>
		<th>Handle</th>
		<th>Email</th>
	</thead>
	<tbody>
	
		<tr>
			<td>
				Will Bond
			</td>
			<td>
				wb
			</td>
			<td>
				will@flourishlib.com
			</td>
		</tr>
	
		<tr>
			<td>
				Matthew J. Sahagian
			</td>
			<td>
				mjs
			</td>
			<td>
				msahagian@dotink.org
			</td>
		</tr>
	
	</tbody>
</table>

## Properties

### Instance Properties
#### <span style="color:#6a6e3d;">$config</span>

The cache configuration, used for database, directory and file caches

#### <span style="color:#6a6e3d;">$dataStore</span>

The data store to use

##### Details

Either the:
- array structure for file cache
- Memcache or Memcached object for memcache
- Redis object for redis

Not used for apc, directory or xcache

#### <span style="color:#6a6e3d;">$type</span>

The type of cache

##### Details

The valid values are:
- `'apc'`
- `'database'`
- `'directory'`
- `'file'`
- `'memcache'`
- `'redis'`
- `'xcache'`




## Methods

### Instance Methods
<hr />

#### <span style="color:#3e6a6e;">__construct()</span>

Set the type and master key for the cache

##### Details

A `file` cache uses a single file to store values in an associative
array and is probably not suitable for a large number of keys.

Using an `apc` or `xcache` cache will have far better performance
than a file or directory, however please remember that keys are shared
server-wide.

`$config` is an associative array of configuration options for the various
backends. Some backend require additional configuration, while others
provide provide optional settings.

The following `$config` options must be set for the `database` backend:

- `table`: The database table to use for caching
- `key_column`: The column to store the cache key in - must support at least 250 character strings
- `value_column`: The column to store the serialized value in - this should probably be a `TEXT` column to support large values, or `BLOB` if binary serialization is used
- `value_data_type`: If a `BLOB` column is being used for the `value_column`, this should be set to 'blob', otherwise `string`
- `ttl_column`: The column to store the expiration timestamp of the cached entry - this should be an integer

The following `$config` for the following items can be set for all backends:

- `serializer`: A callback to serialize data with, defaults to the PHP function `serialize()`
- `unserializer`: A callback to unserialize data with, defaults to the PHP function `unserialize()`

Common serialization callbacks include:

- `json_encode`/`json_decode`
- `igbinary_serialize`/`igbinary_unserialize`

Please note that using JSON for serialization will exclude all non-public
properties of objects from being serialized.

A custom `serialize` and `unserialze` option is `string`, which will cast
all values to a string when storing, instead of serializing them. If a
`__toString()` method is provided for objects, it will be called.

###### Parameters

<table>
	<thead>
		<th>Name</th>
		<th>Type(s)</th>
		<th>Description</th>
	</thead>
	<tbody>
			
		<tr>
			<td>
				$type
			</td>
			<td>
									<a href="http://www.php.net/language.types.string.php">string</a>
				
			</td>
			<td>
				The type of caching to use: `'apc'`, `'database'`, `'directory'`, `'file'`, `'memcache'`, `'redis'`, `'xcache'`
			</td>
		</tr>
					
		<tr>
			<td>
				$data_store
			</td>
			<td>
									<a href="http://www.php.net/language.pseudo-types.php">mixed</a>
				
			</td>
			<td>
				The path for a `file` or `directory` cache, an `Memcache` or `Memcached` object for a `memcache` cache, an fDatabase object for a `database` cache or a `Redis` object for a `redis` cache - not used for `apc` or `xcache`
			</td>
		</tr>
					
		<tr>
			<td>
				$config
			</td>
			<td>
									<a href="http://www.php.net/language.types.array.php">array</a>
				
			</td>
			<td>
				Configuration options - see method description for details
			</td>
		</tr>
			
	</tbody>
</table>

###### Returns

<dl>
	
		<dt>
			void
		</dt>
		<dd>
			Provides no return value.
		</dd>
	
</dl>


<hr />

#### <span style="color:#3e6a6e;">__destruct()</span>

Cleans up after the cache object

###### Returns

<dl>
	
		<dt>
			void
		</dt>
		<dd>
			Provides no return value.
		</dd>
	
</dl>


<hr />

#### <span style="color:#3e6a6e;">add()</span>

Tries to set a value to the cache, but stops if a value already exists

###### Parameters

<table>
	<thead>
		<th>Name</th>
		<th>Type(s)</th>
		<th>Description</th>
	</thead>
	<tbody>
			
		<tr>
			<td>
				$key
			</td>
			<td>
									<a href="http://www.php.net/language.types.string.php">string</a>
				
			</td>
			<td>
				The key to store as, this should not exceed 250 characters
			</td>
		</tr>
					
		<tr>
			<td>
				$value
			</td>
			<td>
									<a href="http://www.php.net/language.pseudo-types.php">mixed</a>
				
			</td>
			<td>
				The value to store, this will be serialized
			</td>
		</tr>
					
		<tr>
			<td>
				$ttl
			</td>
			<td>
									<a href="http://www.php.net/language.types.integer.php">integer</a>
				
			</td>
			<td>
				The number of seconds to keep the cache valid for, 0 for no limit
			</td>
		</tr>
			
	</tbody>
</table>

###### Returns

<dl>
	
		<dt>
			boolean
		</dt>
		<dd>
			If the key/value pair were added successfully
		</dd>
	
</dl>


<hr />

#### <span style="color:#3e6a6e;">clean()</span>

Removes all cache entries that have expired

###### Returns

<dl>
	
		<dt>
			void
		</dt>
		<dd>
			Provides no return value.
		</dd>
	
</dl>


<hr />

#### <span style="color:#3e6a6e;">clear()</span>

Clears the WHOLE cache of every key, use with caution!

###### Returns

<dl>
	
		<dt>
			boolean
		</dt>
		<dd>
			If the cache was successfully cleared
		</dd>
	
</dl>


<hr />

#### <span style="color:#3e6a6e;">delete()</span>

Deletes a value from the cache

###### Parameters

<table>
	<thead>
		<th>Name</th>
		<th>Type(s)</th>
		<th>Description</th>
	</thead>
	<tbody>
			
		<tr>
			<td>
				$key
			</td>
			<td>
									<a href="http://www.php.net/language.types.string.php">string</a>
				
			</td>
			<td>
				The key to delete
			</td>
		</tr>
			
	</tbody>
</table>

###### Returns

<dl>
	
		<dt>
			boolean
		</dt>
		<dd>
			If the delete succeeded
		</dd>
	
</dl>


<hr />

#### <span style="color:#3e6a6e;">get()</span>

Returns a value from the cache

###### Parameters

<table>
	<thead>
		<th>Name</th>
		<th>Type(s)</th>
		<th>Description</th>
	</thead>
	<tbody>
			
		<tr>
			<td>
				$key
			</td>
			<td>
									<a href="http://www.php.net/language.types.string.php">string</a>
				
			</td>
			<td>
				The key to return the value for
			</td>
		</tr>
					
		<tr>
			<td>
				$default
			</td>
			<td>
									<a href="http://www.php.net/language.pseudo-types.php">mixed</a>
				
			</td>
			<td>
				The value to return if the key did not exist
			</td>
		</tr>
			
	</tbody>
</table>

###### Returns

<dl>
	
		<dt>
			mixed
		</dt>
		<dd>
			The cached value or the default value if no cached value was found
		</dd>
	
</dl>


<hr />

#### <span style="color:#3e6a6e;">save()</span>

Only valid for `file` caches, saves the file to disk

###### Returns

<dl>
	
		<dt>
			void
		</dt>
		<dd>
			Provides no return value.
		</dd>
	
</dl>


<hr />

#### <span style="color:#3e6a6e;">serialize()</span>

Serializes a value before storing it in the cache

###### Parameters

<table>
	<thead>
		<th>Name</th>
		<th>Type(s)</th>
		<th>Description</th>
	</thead>
	<tbody>
			
		<tr>
			<td>
				$value
			</td>
			<td>
									<a href="http://www.php.net/language.pseudo-types.php">mixed</a>
				
			</td>
			<td>
				The value to serialize
			</td>
		</tr>
			
	</tbody>
</table>

###### Returns

<dl>
	
		<dt>
			string
		</dt>
		<dd>
			The serialized value
		</dd>
	
</dl>


<hr />

#### <span style="color:#3e6a6e;">set()</span>

Sets a value to the cache, overriding any previous value

###### Parameters

<table>
	<thead>
		<th>Name</th>
		<th>Type(s)</th>
		<th>Description</th>
	</thead>
	<tbody>
			
		<tr>
			<td>
				$key
			</td>
			<td>
									<a href="http://www.php.net/language.types.string.php">string</a>
				
			</td>
			<td>
				The key to store as, this should not exceed 250 characters
			</td>
		</tr>
					
		<tr>
			<td>
				$value
			</td>
			<td>
									<a href="http://www.php.net/language.pseudo-types.php">mixed</a>
				
			</td>
			<td>
				The value to store, this will be serialized
			</td>
		</tr>
					
		<tr>
			<td>
				$ttl
			</td>
			<td>
									<a href="http://www.php.net/language.types.integer.php">integer</a>
				
			</td>
			<td>
				The number of seconds to keep the cache valid for, 0 for no limit
			</td>
		</tr>
			
	</tbody>
</table>

###### Returns

<dl>
	
		<dt>
			boolean
		</dt>
		<dd>
			If the value was successfully saved
		</dd>
	
</dl>


<hr />

#### <span style="color:#3e6a6e;">unserialize()</span>

Unserializes a value before returning it

###### Parameters

<table>
	<thead>
		<th>Name</th>
		<th>Type(s)</th>
		<th>Description</th>
	</thead>
	<tbody>
			
		<tr>
			<td>
				$value
			</td>
			<td>
									<a href="http://www.php.net/language.types.string.php">string</a>
				
			</td>
			<td>
				The serialized value
			</td>
		</tr>
			
	</tbody>
</table>

###### Returns

<dl>
	
		<dt>
			mixed
		</dt>
		<dd>
			The PHP value
		</dd>
	
</dl>






