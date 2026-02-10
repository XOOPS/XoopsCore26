<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Core;

use Assetic\AssetManager;
use Assetic\FilterManager;
use Assetic\Filter;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\Worker\CacheBustingWorker;
use Assetic\AssetWriter;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\GlobAsset;
use Xoops\Core\Filter\MinifyCssFilter;
use Xoops\Core\Filter\MinifyJsFilter;
use Xmf\Yaml;

/**
 * Provides a standardized asset strategy
 *
 * @category  Assets
 * @package   Assets
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2014-2017 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     2.6.0
 */
class Assets
{
    /** @var array  */
    protected $assetPrefs;

    /** @var boolean */
    private $debug = false;

    /** @var array of default filter strings - may be overridden by prefs */
    private $default_filters = array(
            'css' => 'cssimport,cssembed,?cssmin',
            'js'  => '?jsmin',
    );

    /** @var array of output locations in assets directory */
    private $default_output = array(
            'css' => 'css/*.css',
            'js'  => 'js/*.js',
    );

    /** @var array of asset reference definitions - may be overridden by prefs */
    private $default_asset_refs = array(
        array(
            'name' => 'jquery',
            'assets' => array('media/jquery/jquery.js'),
            'filters' => null,
        ),
        array(
            'name' => 'jqueryui',
            'assets' => array('media/jquery/ui/jquery-ui.js'),
            'filters' => null,
        ),
        array(
            'name' => 'jgrowl',
            'assets' => array('media/jquery/plugins/jquery.jgrowl.js'),
            'filters' => null,
        ),
        array(
            'name' => 'fontawesome',
            'assets' => array('media/font-awesome/css/font-awesome.min.css'),
            'filters' => null,
        ),
    );

    /**
     * @var array of file assets to copy to assets
     */
    private $default_file_assets = array(
        array(
            'type' => 'fonts',
            'path' => 'media/font-awesome/fonts',
            'pattern' => '*',
        ),
    );


    /** @var AssetManager */
    private $assetManager = null;

    /** @var string config file with assets prefs */
    private $assetsPrefsFilename = 'var/configs/system_assets_prefs.yml';

    /** @var string config cache key */
    private $assetsPrefsCacheKey = 'system/assets/prefs';

    /** @var string string to identify Assetic filters using instanceof */
    private $filterInterface = '\Assetic\Contracts\Filter\FilterInterface';

    /**
     * __construct
     */
    public function __construct()
    {
        $this->assetManager = new AssetManager();
        if (isset($_REQUEST['ASSET_DEBUG'])) {
            $this->setDebug();
        }
        $this->assetPrefs = $this->readAssetsPrefs();
        // register any asset references
        foreach ($this->default_asset_refs as $ref) {
            $this->registerAssetReference($ref['name'], $ref['assets'], $ref['filters']);
        }
    }

    /**
     * readAssetsPrefs - read configured asset preferences
     *
     * @return array of assets preferences
     */
    protected function readAssetsPrefs()
    {
        $xoops = \Xoops::getInstance();

        try {
            $assetsPrefs = $xoops->cache()->read($this->assetsPrefsCacheKey);
            $file = $xoops->path($this->assetsPrefsFilename);
            $mtime = filemtime($file);
            if ($assetsPrefs===false || !isset($assetsPrefs['mtime']) || !$mtime
                || (isset($assetsPrefs['mtime']) && $assetsPrefs['mtime']<$mtime)) {
                if ($mtime) {
                    $assetsPrefs = Yaml::read($file);
                    if (!is_array($assetsPrefs)) {
                        $xoops->logger()->error("Invalid config in system_assets_prefs.yml");
                        $assetsPrefs = array();
                    } else {
                        $assetsPrefs['mtime']=$mtime;
                        $xoops->cache()->write($this->assetsPrefsCacheKey, $assetsPrefs);
                        $this->copyBaseFileAssets();
                    }
                } else {
                    // use defaults to create file
                    $assetsPrefs = array(
                        'default_filters' => $this->default_filters,
                        'default_asset_refs' => $this->default_asset_refs,
                        'default_file_assets' => $this->default_file_assets,
                        'mtime' => time(),
                    );
                    $this->saveAssetsPrefs($assetsPrefs);
                    $this->copyBaseFileAssets();
                }
            }
            if (!empty($assetsPrefs['default_filters']) && is_array($assetsPrefs['default_filters'])) {
                $this->default_filters = $assetsPrefs['default_filters'];
            }
            if (!empty($assetsPrefs['default_asset_refs']) && is_array($assetsPrefs['default_asset_refs'])) {
                $this->default_asset_refs = $assetsPrefs['default_asset_refs'];
            }
            if (!empty($assetsPrefs['default_file_assets']) && is_array($assetsPrefs['default_file_assets'])) {
                $this->default_file_assets = $assetsPrefs['default_file_assets'];
            }
        } catch (\Exception $e) {
            $xoops->events()->triggerEvent('core.exception', $e);
            $assetsPrefs = array();
        }
        return $assetsPrefs;
    }

    /**
     * saveAssetsPrefs - record array of assets preferences in config file, and
     * update cache
     *
     * @param array $assets_prefs array of asset preferences to save
     *
     * @return void
     */
    protected function saveAssetsPrefs($assets_prefs)
    {
        if (is_array($assets_prefs)) {
            $xoops = \Xoops::getInstance();
            try {
                Yaml::save($assets_prefs, $xoops->path($this->assetsPrefsFilename));
                $xoops->cache()->write($this->assetsPrefsCacheKey, $assets_prefs);
            } catch (\Exception $e) {
                $xoops->events()->triggerEvent('core.exception', $e);
            }
        }
    }


    /**
     * getUrlToAssets
     *
     * Create an asset file from a list of assets
     *
     * @param string       $type    type of asset, css or js
     * @param array        $assets  list of source files to process
     * @param string|array $filters either a comma separated list of known namsed filters
     *                              or an array of named filters and/or filter object
     * @param string       $target  target path, will default to assets directory
     *
     * @return string URL to asset file
     */
    public function getUrlToAssets($type, $assets, $filters = 'default', $target = null)
    {
        if (is_scalar($assets)) {
            $assets = array($assets); // just a single path name
        }

        if ($filters==='default') {
            if (isset($this->default_filters[$type])) {
                $filters = $this->default_filters[$type];
            } else {
                $filters = '';
            }
        }

        if (!is_array($filters)) {
            if (empty($filters)) {
                $filters = array();
            } else {
                $filters = explode(',', str_replace(' ', '', $filters));
            }
        }

        if (isset($this->default_output[$type])) {
            $output = $this->default_output[$type];
        } else {
            $output = '';
        }

        $xoops = \Xoops::getInstance();

        if (isset($target)) {
            $target_path = $target;
        } else {
            $target_path = $xoops->path('assets');
        }

        try {
            $am = $this->assetManager;
            $fm = new FilterManager();

            foreach ($filters as $filter) {
                if (is_object($filter) && $filter instanceof $this->filterInterface) {
                    $filterArray[] = $filter;
                } else {
                    $filterName = ltrim($filter, '?');
                    switch ($filterName) {
                        case 'cssembed':
                            $fm->set('cssembed', new Filter\PhpCssEmbedFilter());
                            break;
                        case 'cssmin':
                            $fm->set('cssmin', new MinifyCssFilter());
                            break;
                        case 'cssimport':
                            $fm->set('cssimport', new Filter\CssImportFilter());
                            break;
                        case 'cssrewrite':
                            $fm->set('cssrewrite', new Filter\CssRewriteFilter());
                            break;
                        case 'lessphp':
                            $fm->set('lessphp', new Filter\LessphpFilter());
                            break;
                        case 'scssphp':
                            $fm->set('scssphp', new Filter\ScssphpFilter());
                            break;
                        case 'jsmin':
                        case 'jsqueeze':
                            $fm->set($filterName, new MinifyJsFilter());
                            break;
                        default:
                            throw new \Exception(sprintf('%s filter not implemented.', $filter));
                            break;
                    }
                }
            }

            // Factory setup
            $factory = new AssetFactory($target_path);
            $factory->setAssetManager($am);
            $factory->setFilterManager($fm);
            $factory->setDefaultOutput($output);
            $factory->setDebug($this->debug);
            $factory->addWorker(new CacheBustingWorker());

            // Prepare the assets writer
            $writer = new AssetWriter($target_path);

            // Translate asset paths, remove duplicates
            $translated_assets = array();
            foreach ($assets as $k => $v) {
                // translate path if not a reference or absolute path
                if (0 == preg_match("/^\\/|^\\\\|^[a-zA-Z]:|^@/", $v)) {
                    $v = $xoops->path($v);
                }
                if (!in_array($v, $translated_assets)) {
                    $translated_assets[] = $v;
                }
            }

            // Create the asset
            $asset = $factory->createAsset(
                $translated_assets,
                $filters
            );
            $asset_path = $asset->getTargetPath();
            if (!is_readable($target_path . $asset_path)) {
                $assetKey = 'Asset '.$asset_path;
                $xoops->events()->triggerEvent('debug.timer.start', $assetKey);
                $oldumask = umask(0002);
                $writer->writeAsset($asset);
                umask($oldumask);
                $xoops->events()->triggerEvent('debug.timer.stop', $assetKey);
            }

            return $xoops->url('assets/' . $asset_path);

        } catch (\Exception $e) {
            $xoops->events()->triggerEvent('core.exception', $e);
            return null;
        }
    }


    /**
     * setDebug enable debug mode, will skip filters prefixed with '?'
     *
     * @return true
     */
    public function setDebug()
    {
        $this->debug = true;
        return true;
    }

    /**
     * Add an asset reference to the asset manager
     *
     * @param string       $name    the name of the reference to be added
     * @param mixed        $assets  a string asset path, or an array of asset paths,
     *                              may include wildcard
     * @param string|array $filters either a comma separated list of known named filters
     *                              or an array of named filters and/or filter object
     *
     * @return boolean true if asset registers, false on error
     */
    public function registerAssetReference($name, $assets, $filters = null)
    {
        $xoops = \Xoops::getInstance();

        $assetArray = array();
        $filterArray = array();

        try {
            if (is_scalar($assets)) {
                $assets = array($assets);  // just a single path name
            }
            foreach ($assets as $a) {
                // translate path if not a reference or absolute path
                if ((substr_compare($a, '@', 0, 1) != 0)
                    && (substr_compare($a, '/', 0, 1) != 0)) {
                    $a = $xoops->path($a);
                }
                if (false===strpos($a, '*')) {
                    $assetArray[] = new FileAsset($a); // single file
                } else {
                    $assetArray[] = new GlobAsset($a);  // wild card match
                }
            }

            if (!is_array($filters)) {
                if (empty($filters)) {
                    $filters = array();
                } else {
                    $filters = explode(',', str_replace(' ', '', $filters));
                }
            }
            foreach ($filters as $filter) {
                if (is_object($filter) && $filter instanceof $this->filterInterface) {
                    $filterArray[] = $filter;
                } else {
                    switch (ltrim($filter, '?')) {
                        case 'cssembed':
                            $filterArray[] = new Filter\PhpCssEmbedFilter();
                            break;
                        case 'cssmin':
                            $filterArray[] = new MinifyCssFilter();
                            break;
                        case 'cssimport':
                            $filterArray[] = new Filter\CssImportFilter();
                            break;
                        case 'cssrewrite':
                            $filterArray[] = new Filter\CssRewriteFilter();
                            break;
                        case 'lessphp':
                            $filterArray[] = new Filter\LessphpFilter();
                            break;
                        case 'scssphp':
                            $filterArray[] = new Filter\ScssphpFilter();
                            break;
                        case 'jsmin':
                        case 'jsqueeze':
                            $filterArray[] = new MinifyJsFilter();
                            break;
                        default:
                            throw new \Exception(sprintf('%s filter not implemented.', $filter));
                            break;
                    }
                }
            }

            $collection = new AssetCollection($assetArray, $filterArray);
            $this->assetManager->set($name, $collection);

            return true;
        } catch (\Exception $e) {
            $xoops->events()->triggerEvent('core.exception', $e);
            return false;
        }
    }

    public function copyBaseFileAssets()
    {
        foreach ($this->default_file_assets as $fileSpec) {
            $this->copyFileAssets($fileSpec['path'], trim($fileSpec['pattern']), $fileSpec['type']);
        }
    }

    /**
     * copyFileAssets - copy files to the appropriate asset directory.
     *
     * Copying is normally only needed for fonts or images when they are referenced by a
     * relative url in stylesheet, or are located outside of the web root.
     *
     * @param string $fromPath path to files to copy
     * @param string $pattern  glob pattern to match files to be copied
     * @param string $output   output type (css, fonts, images, js)
     *
     * @return mixed boolean false if target directory is not writable, otherwise
     *               integer count of files copied
     */
    public function copyFileAssets($fromPath, $pattern, $output)
    {
        $xoops = \Xoops::getInstance();

        $fromPath = $xoops->path($fromPath) . '/';
        $toPath = $xoops->path('assets') . '/' . $output . '/';
        $from = glob($fromPath . '/' . $pattern);

        if (!is_dir($toPath)) {
            $oldUmask = umask(0);
            mkdir($toPath, 0775, true);
            umask($oldUmask);
        }

        if (is_writable($toPath)) {
            $count = 0;
            $oldUmask = umask(0002);
            foreach ($from as $filepath) {
                $xoops->events()->triggerEvent('debug.timer.start', $filepath);
                $filename = basename($filepath);
                $status=copy($filepath, $toPath.$filename);
                if (false===$status) {
                    $xoops->logger()->warning('Failed to copy asset '.$filename);
                } else {
                    //$xoops->logger()->debug('Copied asset '.$filename);
                    ++$count;
                }
                $xoops->events()->triggerEvent('debug.timer.stop', $filepath);
            }
            umask($oldUmask);
            return $count;
        } else {
            $xoops->logger()->warning('Asset directory is not writable. ' . $output);
            return false;
        }
    }
}
