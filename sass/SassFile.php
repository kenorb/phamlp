<?php
/* SVN FILE: $Id$ */
/**
 * SassFile class file.
 * File handling utilites.
 * @author			Chris Yates <chris.l.yates@gmail.com>
 * @copyright 	Copyright (c) 2010 PBM Web Development
 * @license			http://phamlp.googlecode.com/files/license.txt
 * @package			PHamlP
 * @subpackage	Sass
 */

/**
 * SassFile class.
 * @package			PHamlP
 * @subpackage	Sass
 */
class SassFile {
	const SASS = 'sass';
	const SCSS = 'scss';
	const SASSC = 'sassc';

	/**
	 * Returns the parse tree for a file.
	 * If caching is enabled a cached version will be used if possible; if not the
	 * parsed file will be cached.
	 * @param string filename to parse
	 * @param SassParser Sass parser
	 * @return SassRootNode
	 */
	public static function getTree($filename, $parser) {
		if ($parser->cache) {
			$cached = self::getCachedFile($filename, $parser->cache_location);
			if ($cached !== false) {
				return $cached;
			}
		}

		$sassParser = new SassParser(array_merge($parser->options, array('line'=>1)));
		$tree = $sassParser->parse($filename);
		if ($parser->cache) {
			self::setCachedFile($tree, $filename, $parser->cache_location);
		}
		return $tree;
	 }

	/**
	 * Returns the full path to a file to parse.
	 * The file is looked for recursively under the load_paths directories and
	 * the template_location directory.
	 * If the filename does not end in .sass or .scss add the current sysntax.
	 * @param string filename to find
	 * @param SassParser Sass parser
	 * @return string path to file
	 * @throws SassException if file not found
	 */
	public static function getFile($filename, $parser) {
		$ext = substr($filename, -5);
		if ($ext !== '.'.self::SASS && $ext !== '.'.self::SCSS) {
			$filename .= ".{$parser->syntax}";
		}

		if (file_exists($filename)) {
			return $filename;
		}
		elseif (file_exists(dirname($parser->filename) . DIRECTORY_SEPARATOR . $filename)) {
			return dirname($parser->filename) . DIRECTORY_SEPARATOR . $filename;
		}

		foreach ($parser->load_paths as $loadPath) {
			$path = self::findFile($filename, realpath($loadPath));
			if ($path !== false) {
				return $path;
			}
		} // foreach

		if (!empty($parser->template_location)) {
			$path = self::findFile($filename, realpath($parser->template_location));
			if ($path !== false) {
				return $path;
			}
		}
		throw new SassException('Unable to find {what}: {filename}', array('{what}'=>'import file', '{filename}'=>$filename));
	}

	/**
	 * Looks for the file recursively in the specified directory.
	 * @param string filename to look for
	 * @param string path to directory to look in and under
	 * @return mixed string: full path to file if found, false if not
	 */
	public static function findFile($filename, $dir) {
		if (file_exists($dir . DIRECTORY_SEPARATOR . $filename)) {
			return $dir . DIRECTORY_SEPARATOR . $filename;
		}

		$files = array_slice(scandir($dir), 2);

		foreach ($files as $file) {
			if (is_dir($file)) {
				$path = self::findFile($filename, $dir . DIRECTORY_SEPARATOR . $file);
				if ($path !== false) {
					return $path;
				}
			}
		} // foreach
	  return false;
	}

	/**
	 * Returns a cached version of the file if available.
	 * @param string filename to fetch
	 * @param string path to cache location
	 * @return mixed the cached file if available or false if it is not
	 */
	public static function getCachedFile($filename, $cacheLocation) {
		$cached = realpath($cacheLocation) . DIRECTORY_SEPARATOR .
			md5($filename) . '.'.self::SASSC;

		if ($cached && file_exists($cached) &&
				filemtime($cached) >= filemtime($filename)) {
			return unserialize(file_get_contents($cached));
		}
		return false;
	}

	/**
	 * Saves a cached version of the file.
	 * @param SassRootNode Sass tree to save
	 * @param string filename to save
	 * @param string path to cache location
	 * @return mixed the cached file if available or false if it is not
	 */
	public static function setCachedFile($sassc, $filename, $cacheLocation) {
		$cacheDir = realpath($cacheLocation);

		if (!$cacheDir) {
			mkdir($cacheLocation);
			@chmod($cacheLocation, 0777);
			$cacheDir = realpath($cacheLocation);
		}

		$cached = $cacheDir . DIRECTORY_SEPARATOR . md5($filename) . '.'.self::SASSC;

		return file_put_contents($cached, serialize($sassc));
	}
}