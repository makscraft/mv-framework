<?php
/**
 * Cache manager for media files (css and javascript).
 * Keeps data in files in ~/userfiles/cache/media folder.
 */
class CacheMedia
{
    /**
	 * Instance of singleton pattern to keep only one copy of object.
     * @var CacheMedia
	 */
	private static $instance;

    /**
     * Folder with cached media files.
     * @var string
     */
    private static $folder;

    /**
     * File with map connwcting routes with cached nedia files.
     * @var string
     */
    private static $map_file;

    /**
     * Current build number from config/setup.php
     * @var int
     */
    private static $build;

    /**
     * Media files added before instance() method call.
     * These files will go later at the end of combined media files list.
     * @var array
     */
    public static $preloads = [];

    /**
     * Media files list, containing all files divided into subarrays.
     * @var array
     */
    public static $files = [];

    /**
     * Current route passed from Router object.
     * @var string
     */
    private static $route;

    /**
     * Flags to memorize current state and use it during the work.
     * @var array
     */
    private static $flags = [
        'save_map' => false,
        'check_filetimes' => false
    ];
		
	private function __construct() {}
	
	/**
	 * Creates or returns the Registry object as singleton pattern.
     * @return self
	 */
	static public function instance(Router $router = null)
	{
		if(!isset(self :: $instance))
        {
            self :: $build = Registry :: get('Build') ?? 0;
            self :: $folder = Registry :: get('IncludePath').'userfiles/cache/';

            if(!is_dir(self :: $folder))
                mkdir(self :: $folder);

            self :: $map_file = self :: $folder.'media-map-'.self :: $build.'.php';
            self :: $folder .= 'media/';

            if(!is_dir(self :: $folder))
                mkdir(self :: $folder);

            if(Registry :: onDevelopment() || Registry :: get('CheckConfigFilesUntil') - time() > 0)
                self :: $flags['check_filetimes'] = true;

            if(is_object($router))
                self :: $route = $router -> getRoute();

			self :: $instance = new self();
        }

        return self :: $instance;
	}

    /**
     * Returns all media files, added after instance method call.
     * @return array
     */
    static public function getFiles()
    {
        return self :: $files;
    }

    /**
     * Returns all media files, added before instance method call.
     * @return array
     */
    static public function getPreloads()
    {
        return self :: $preloads;
    }

    /**
     * Combines all media files, added before and after instance method call.
     * @return array
     */
    static private function combineFiles(string $type)
    {
        $result = self :: $files[$type] ?? [];

        if(array_key_exists($type, self :: $preloads) && is_array(self :: $preloads[$type]))
            foreach(self :: $preloads[$type] as $file)
                if(!in_array($file, $result))
                    $result[] = $file;

        return $result;
    }

    /**
     * Returns all media files, combined by type, and placed into one array.
     * @return array
     */
    static public function getAllFilesCombined()
    {
        $all = [];

        foreach(self :: $files as $type => $files)
        {
            $files = self :: combineFiles($type);
            $all = array_merge($all, $files);
        }

        return $all;
    }

    /**
     * Returns cache drop mark, based on MV version.
     * @return string
     */
    static public function getDropMark()
    {
        return "?v".str_replace('.', '', (string) Registry :: getVersion());
    }

    /**
     * Adds one or more media files into local array.
     */
    static public function addFile(mixed $file, string $type)
    {
        $files = is_string($file) ? [$file] : $file;
        $preload = self :: $instance ? false : true;

        foreach($files as $file)
        {
            $folder = (strpos($file, '/') === false) ? 'media/'.$type : dirname($file);
            $file = Registry :: get('IncludePath').$folder.'/'.basename($file);

            if(!is_file($file))
                continue;

            if($preload)
            {
                if(!array_key_exists($type, self :: $preloads))
                    self :: $preloads[$type] = [];

                if(!in_array($file, self :: $preloads[$type]))
                    self :: $preloads[$type][] = $file;
            }
            else
            {
                if(!array_key_exists($type, self :: $files))
                    self :: $files[$type] = [];

                if(!in_array($file, self :: $files[$type]))
                    self :: $files[$type][] = $file;
            }
        }
    }

    /**
     * Adds one or more js files into local array.
     */
    static public function addJavaScriptFile(mixed $file)
    {
        self :: addFile($file, 'js');
    }

    /**
     * Adds one or more css files into local array.
     */
    static public function addCssFile(mixed $file)
    {
        self :: addFile($file, 'css');
    }

    /**
     * Generates final combined cache file, according to type (css or js).
     * @return string path to file
     */
    static public function getCachedFile(string $type)
    {
        $files = self :: combineFiles($type);
        $file_path = '';

        if(!count($files))
            return '';

        if(self :: $flags['check_filetimes'])
        {
            $files_ = Service :: addFilesWithModificationTimes($files);
            $hash = md5(json_encode($files_).strval(self :: $route));
        }
        else
            $hash = md5(json_encode($files).strval(self :: $route));

        $file_path = self :: $folder.$type.'-'.self :: $build.'-'.$hash.'.'.$type;

        if(!is_file($file_path))
        {
            self :: cleanupMediaFiles($type);
            self :: compressFiles($files, $file_path);
            self :: $flags['save_map'] = true;
        }

        $file = Service :: addRootPath($file_path);
        
        return $file;
    }

    /**
     * Generates final combined js cache file.
     * @return string js link to file
     */
    static public function getJavaScriptCache()
    {
        $file = self :: getCachedFile('js');

        return "<script type=\"text/javascript\" src=\"".$file."\"></script>\n";
    }

    /**
     * Generates final combined css cache file.
     * @return string css link to file
     */
    static public function getCssCache()
    {
        $file = self :: getCachedFile('css');
                
        return "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$file."\" />\n";
    }

    /**
     * Returns generated final combined css and js cache files links.
     * @return string links to css and js files
     */
    static public function getAllCache()
    {
        $all = self :: getCssCache();
        $all .= self :: getJavaScriptCache();

        return $all;
    }

    /**
     * Returns links to initial css and js files.
     * @param string $type optional param of type (if empty js and css links will be returned) 
     * @return string links to css and js files
     */
    static public function getInitialFiles(string $type = '')
    {
        $html = '';
        $hash = substr(md5(self :: $build), 5, 7);

        if($type === 'css' || $type === '')
        {
            $files = self :: combineFiles('css');

            foreach($files as $file)
            {
                $drop =  '?'.(self :: $flags['check_filetimes'] ? filemtime($file) : $hash);
                $file = Service :: addRootPath($file).$drop;
                
                $html .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$file."\" />\n";
            }
        }

        if($type === 'js' || $type === '')
        {
            $files = self :: combineFiles('js');

            foreach($files as $file)
            {
                $drop =  '?'.(self :: $flags['check_filetimes'] ? filemtime($file) : $hash);
                $file = Service :: addRootPath($file).$drop;

                $html .= "<script type=\"text/javascript\" src=\"".$file."\"></script>\n";
            }
        }

        return $html;
    }

    /**
     * Removes not actual cached files by type.
     */
    static public function cleanupMediaFiles(string $type)
    {
        if(!is_dir(self :: $folder))
            return;

        $files = scandir(self :: $folder);
        $begin = $type.'-'.self :: $build.'-';

        foreach($files as $file)
            if(strpos($file, $begin) !== 0 && Service :: getExtension($file) === $type)
                unlink(self :: $folder.$file);
    }

    /**
     * Combines files' contents into one file.
     */
    static private function compressFiles(array $files, string $file_path)
    {
        if(!is_dir(self :: $folder))
            return false;

        $content = '';

        foreach($files as $file)
        {
            $data = file_get_contents($file);

            if(Service :: getExtension($file) === 'css')
            {
                $path = dirname(Service :: addRootPath($file)).'/';
                $data = str_replace(':url(', ': url(', $data);
                $data = preg_replace('/\Wurl\s*\(\s*(\'|\")?\s*/', ' url($1'.$path, $data);
            }

            $content .= "\n/* File: ".basename($file)." */\n\n";
            $content .= $data;
            $content .= "\n/* End of file */\n\n";
        }

        file_put_contents($file_path, $content);

        return true;
    }

    static public function findByRoute(Router $router)
    {
        self :: $route = $router -> getRoute();
        self :: $flags['save_map'] = true;

        if(is_file(self :: $map_file))
        {
            $cache = include(self :: $map_file);

            if($cache['Build'] != self :: $build)
                return false;

            if(array_key_exists(self :: $route, $cache['Routes']))
            {
                $files = $cache['Routes'][self :: $route];

                if(Registry :: get('Mode') == 'development')
                {
                    $hash = $files['check_hash'] ?? null;
                    $sources = $files['sources'] ?? null;
                    
                    if($hash == null || $sources == null || $hash != $sources)
                        return false;
                }

                unset($files['check_hash'], $files['sources']);

                foreach($files as $file)
                    if(!is_file(self :: $folder.$file))
                        return false;
                
                if(isset($files['css']) && $files['css'])
                    echo self :: getCssCache($files['css']);

                if(isset($files['js']) && $files['js'])
                    echo self :: getJavaScriptCache($files['js']);

                self :: $flags['save_map'] = false;

                return true;
            }
        }

        return false;
    }

    static public function addDataIntoMediaCacheMap()
    {
        $map = ['Build' => self :: $build, 'Common' => [], 'Routes' => []];

        if(is_file(self :: $map_file))
            $map = include(self :: $map_file);
        else
            Cache :: cleanConfigCacheFilesByKey('media-map');

        if(self :: $route === null)
        {
            $map['Common'] = [
                'css' => self :: combineFiles('css'),
                'js' => self :: combineFiles('js')
            ];
        }
        
        Cache :: saveConfigCacheIntoFile($map, 'media-map');
    }
}