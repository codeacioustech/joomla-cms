<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Sellacious\HTML;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Environment\Browser;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

\JLoader::import('joomla.environment.browser');
\JLoader::import('joomla.filesystem.file');
\JLoader::import('joomla.filesystem.path');

/**
 * Utility class for all HTML drawing classes
 *
 * @since  1.5
 */
abstract class HTMLHelper extends \Joomla\CMS\HTML\HTMLHelper
{
	/**
	 * Compute the files to be included
	 *
	 * @param   string   $folder          Folder name to search in (i.e. images, css, js).
	 * @param   string   $file            Path to file.
	 * @param   boolean  $relative        Flag if the path to the file is relative to the /media folder (and searches in template).
	 * @param   boolean  $detect_browser  Flag if the browser should be detected to include specific browser files.
	 * @param   boolean  $detect_debug    Flag if debug mode is enabled to include uncompressed files if debug is on.
	 *
	 * @return  array    files to be included.
	 *
	 * @throws  \Exception
	 *
	 * @see     JBrowser
	 *
	 * @since   1.6
	 */
	protected static function includeRelativeFiles($folder, $file, $relative, $detect_browser, $detect_debug)
	{
		// If http is present in filename just return it as an array
		if (strpos($file, 'http') === 0 || strpos($file, '//') === 0)
		{
			return array($file);
		}

		// Extract extension and strip the file
		$ext   = \JFile::getExt($file);
		$strip = \JFile::stripExt($file);

		// Detect browser and compute potential files
		if ($detect_browser)
		{
			$navigator = Browser::getInstance();
			$browser   = $navigator->getBrowser();
			$major     = $navigator->getMajor();
			$minor     = $navigator->getMinor();

			// Try to include files named filename.ext, filename_browser.ext, filename_browser_major.ext, filename_browser_major_minor.ext
			// where major and minor are the browser version names
			$potential = array(
				$strip,
				$strip . '_' . $browser,
				$strip . '_' . $browser . '_' . $major,
				$strip . '_' . $browser . '_' . $major . '_' . $minor,
			);
		}
		else
		{
			$potential = array($strip);
		}

		$is_debug = $detect_debug && Factory::getConfig()->get('debug');
		$files    = array();

		foreach ($potential as $i => $strip)
		{
			if ($is_debug)
			{
				/*
				 * Detect debug mode
				 *
				 * Detect if we received a file in the format name.min.ext
				 * If so, strip the .min part out, otherwise append -uncompressed
				 */
				if (strlen($strip) > 4 && preg_match('#\.min$#', $strip))
				{
					$files[$i][] = preg_replace('#\.min$#', '.', $strip) . $ext;
				}
				else
				{
					$files[$i][] = $strip . '-uncompressed.' . $ext;
				}
			}

			$files[$i][] = $strip . '.' . $ext;
		}

		/*
		 * Loop on 1 or 2 files and break on first found.
		 * Add the content of the MD5SUM file located in the same folder to URL to ensure cache browser refresh
		 * This MD5SUM file must represent the signature of the folder content
		 */
		$includes = array();

		if ($relative)
		{
			foreach ($files as $fileset)
			{
				foreach ($fileset as $file)
				{
					$include = static::findRelativeFile($folder, $file);

					if ($include)
					{
						$includes[] = $include;

						break;
					}
				}
			}
		}
		else
		{
			// If not relative and http is not present in filename
			foreach ($files as $fileset)
			{
				foreach ($fileset as $file)
				{
					$path = "/$file";

					if (file_exists(JPATH_BASE . $path))
					{
						$includes[] = Uri::base(true) . $path . static::getMd5Version(JPATH_BASE . $path);

						break;
					}
					elseif (file_exists(JPATH_ROOT . $path))
					{
						$includes[] = Uri::root(true) . $path . static::getMd5Version(JPATH_ROOT . $path);

						break;
					}
				}
			}
		}

		return $includes;
	}

	/**
	 * Attempt to find a relative file for inclusion
	 *
	 * @param   string  $folder  The folder in which to lookup. Viz. - js, css, images
	 * @param   string  $file    The filename to search for
	 *
	 * @return  string
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected static function findRelativeFile($folder, $file)
	{
		// If relative search in template directory or media directory
		$template = Factory::getApplication()->getTemplate();

		// If the file is in the template folder
		$path = "/templates/$template/$folder/$file";

		if (file_exists(JPATH_BASE . $path))
		{
			return Uri::base(true) . $path . static::getMd5Version(JPATH_BASE . $path);
		}

		// If the file contains any /: it can be in a media extension subfolder
		if (strpos($file, '/'))
		{
			// Divide the file extracting the extension as the first part before /
			list($extension, $file) = explode('/', $file, 2);

			// If the file yet contains any /: it can be a plugin
			if (strpos($file, '/'))
			{
				// Divide the file extracting the element as the first part before /
				list($element, $file) = explode('/', $file, 2);

				// Try to deal with plugins group in the media folder
				$path = "/media/$extension/$element/$folder/$file";

				if (file_exists(JPATH_BASE . $path))
				{
					return Uri::base(true) . $path . static::getMd5Version(JPATH_BASE . $path);
				}
				elseif (file_exists(JPATH_ROOT . $path))
				{
					return Uri::root(true) . $path . static::getMd5Version(JPATH_ROOT . $path);
				}

				// Try to deal with classical file in a media subfolder called element
				$path = "/media/$extension/$folder/$element/$file";

				if (file_exists(JPATH_BASE . $path))
				{
					return Uri::base(true) . $path . static::getMd5Version(JPATH_BASE . $path);
				}
				elseif (file_exists(JPATH_ROOT . $path))
				{
					return Uri::root(true) . $path . static::getMd5Version(JPATH_ROOT . $path);
				}

				// Try to deal with system files in the template folder
				$path = "/templates/$template/$folder/system/$element/$file";

				if (file_exists(JPATH_BASE . $path))
				{
					return Uri::base(true) . $path . static::getMd5Version(JPATH_BASE . $path);
				}

				// Try to deal with system files in the media folder
				$path = "/media/system/$folder/$element/$file";

				if (file_exists(JPATH_BASE . $path))
				{
					return Uri::base(true) . $path . static::getMd5Version(JPATH_BASE . $path);
				}
				elseif (file_exists(JPATH_ROOT . $path))
				{
					return Uri::root(true) . $path . static::getMd5Version(JPATH_ROOT . $path);
				}
			}
			else
			{
				// Try to deals in the extension media folder
				$path = "/media/$extension/$folder/$file";

				if (file_exists(JPATH_BASE . $path))
				{
					return Uri::base(true) . $path . static::getMd5Version(JPATH_BASE . $path);
				}
				elseif (file_exists(JPATH_ROOT . $path))
				{
					return Uri::root(true) . $path . static::getMd5Version(JPATH_ROOT . $path);
				}

				// Try to deal with system files in the template folder
				$path = "/templates/$template/$folder/system/$file";

				if (file_exists(JPATH_BASE . $path))
				{
					return Uri::base(true) . $path . static::getMd5Version(JPATH_BASE . $path);
				}

				// Try to deal with system files in the media folder
				$path = "/media/system/$folder/$file";

				if (file_exists(JPATH_BASE . $path))
				{
					return Uri::base(true) . $path . static::getMd5Version(JPATH_BASE . $path);
				}
				elseif (file_exists(JPATH_ROOT . $path))
				{
					return Uri::root(true) . $path . static::getMd5Version(JPATH_ROOT . $path);
				}
			}
		}
		// Try to deal with system files in the media folder
		else
		{
			$path = "/media/system/$folder/$file";

			if (file_exists(JPATH_BASE . $path))
			{
				return Uri::base(true) . $path . static::getMd5Version(JPATH_BASE . $path);
			}
			elseif (file_exists(JPATH_ROOT . $path))
			{
				return Uri::root(true) . $path . static::getMd5Version(JPATH_ROOT . $path);
			}
		}

		return null;
	}
}
