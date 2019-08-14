<?php
// https://github.com/erusev/parsedown
// 导入Parsedown依赖
require_once('Parsedown.php');
/**
 * 一个简单的基于PHP的目录列表器，列出了内容
 * 目录及其所有子目录，并且允许轻松
 * 导航内的文件。
 *
 * 此软件根据MIT许可证分发
 * http://www.opensource.org/licenses/mit-license.php
 *
 * 有关更多信息，请访问http://www.directorylister.com
 *
 * @author Chris Kankiewicz (http://www.chriskankiewicz.com)
 * @copyright 2015 Chris Kankiewicz
 */
class DirectoryLister
{

    // 定义应用程序版本
    const VERSION = '2.6.1';

    // 保留一些变量
    protected $_themeName     = null;
    protected $_directory     = null;
    protected $_appDir        = null;
    protected $_appURL        = null;
    protected $_config        = null;
    protected $_fileTypes     = null;
    protected $_systemMessage = null;


    /**
     * DirectoryLister构造函数。运行对象创建。
     */
    public function __construct()
    {

        // 设置class目录常量
        if (!defined('__DIR__')) {
            define('__DIR__', dirname(__FILE__));
        }

        // 设置应用程序目录
        $this->_appDir = __DIR__;

        // 构建应用程序URL
        $this->_appURL = $this->_getAppUrl();

        // 加载配置文件
        $configFile = $this->_appDir . '/config.php';

        // 将配置数组设置为全局变量
        if (file_exists($configFile)) {
            $this->_config = require_once($configFile);
        } else {
            die('ERROR: Missing application config file at ' . $configFile);
        }

        // 将文件类型数组设置为全局变量
        $this->_fileTypes = require_once($this->_appDir . '/fileTypes.php');

        // 设置主题名称
        $this->_themeName = $this->_config['theme_name'];
    }

    /**
     * If it is allowed to zip whole directories
     *
     * @param string $directory Relative path of directory to list
     * @return true or false
     * @access public
     */
    public function isZipEnabled()
    {
        foreach ($this->_config['zip_disable'] as $disabledPath) {
            if (fnmatch($disabledPath, $this->_directory)) {
                return false;
            }
        }
        return $this->_config['zip_dirs'];
    }

    /**
     * 创建目录的zipfile
     *
     * @param string $directory 要列出的目录的相对路径
     * @access public
     */
    public function zipDirectory($directory)
    {

        if ($this->_config['zip_dirs']) {

            // Cleanup directory path
            $directory = $this->setDirectoryPath($directory);

            if ($directory != '.' && $this->_isHidden($directory)) {
                echo "Access denied.";
            }

            $filename_no_ext = basename($directory);

            if ($directory == '.') {
                $filename_no_ext = $this->_config['web_title'];
            }

            // We deliver a zip file
            header('Content-Type: archive/zip');

            // 浏览器的文件名保存zip文件
            header("Content-Disposition: attachment; filename=\"$filename_no_ext.zip\"");

            //change directory so the zip file doesnt have a tree structure in it.
            chdir($directory);

            // TODO: Probably we have to parse exclude list more carefully
            $exclude_list = implode(' ', array_merge($this->_config['hidden_files'], array('index.php')));
            $exclude_list = str_replace("*", "\*", $exclude_list);

            if ($this->_config['zip_stream']) {

                // zip the stuff (dir and all in there) into the streamed zip file
                $stream = popen('/usr/bin/zip -' . $this->_config['zip_compression_level'] . ' -r -q - * -x ' . $exclude_list, 'r');

                if ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                }
            } else {

                // get a tmp name for the .zip
                $tmp_zip = tempnam('tmp', 'tempzip') . '.zip';

                // zip the stuff (dir and all in there) into the tmp_zip file
                exec('zip -' . $this->_config['zip_compression_level'] . ' -r ' . $tmp_zip . ' * -x ' . $exclude_list);

                // calc the length of the zip. it is needed for the progress bar of the browser
                $filesize = filesize($tmp_zip);
                header("Content-Length: $filesize");

                // deliver the zip file
                $fp = fopen($tmp_zip, 'r');
                echo fpassthru($fp);

                // clean up the tmp zip file
                unlink($tmp_zip);
            }
        }
    }


    /**
     * 创建目录列表并返回格式化的XHTML
     *
     * @param string $directory 要列出的目录的相对路径
     * @return array 列出的目录数组
     * @access public
     */
    public function listDirectory($directory)
    {

        // 设置目录，给_directory赋值为$directory
        $directory = $this->setDirectoryPath($directory);

        // 如果留空，设置目录变量
        if ($directory === null) {
            $directory = $this->_directory;
        }

        // 获取目录数组
        $directoryArray = $this->_readDirectory($directory);

        // 返回数组
        return $directoryArray;
    }


    /**
     * Parses and returns an array of breadcrumbs
     *
     * @param string $directory Path to be breadcrumbified
     * @return array Array of breadcrumbs
     * @access public
     */
    public function listBreadcrumbs($directory = null)
    {

        // 如果留空则设置目录变量
        if ($directory === null) {
            $directory = $this->_directory;
        }

        // 将路径分解为数组
        $dirArray = explode('/', $directory);

        // 静态设置主页路径
        $breadcrumbsArray[] = array(
            'link' => $this->_appURL,
            'text' => $this->_config['web_title']
        );

        // Generate breadcrumbs
        foreach ($dirArray as $key => $dir) {

            if ($dir != '.') {

                $dirPath  = null;

                // 构建目录路径
                for ($i = 0; $i <= $key; $i++) {
                    $dirPath = $dirPath . $dirArray[$i] . '/';
                }

                // 删除尾部斜杠
                if (substr($dirPath, -1) == '/') {
                    $dirPath = substr($dirPath, 0, -1);
                }

                // 组合基本路径和dir路径
                $link = $this->_appURL . '?dir=' . rawurlencode($dirPath);

                $breadcrumbsArray[] = array(
                    'link' => $link,
                    'text' => $dir
                );
            }
        }

        // 返回breadcrumb数组
        return $breadcrumbsArray;
    }


    /**
     * Determines if a directory contains an index file
     *
     * @param string $dirPath Path to directory to be checked for an index
     * @return boolean Returns true if directory contains a valid index file, false if not
     * @access public
     */
    public function containsIndex($dirPath)
    {

        // 检查目录是否包含索引文件
        foreach ($this->_config['index_files'] as $indexFile) {

            if (file_exists($dirPath . '/' . $indexFile)) {

                return true;
            }
        }

        return false;
    }


    /**
     * 获取列出的目录的路径
     *
     * @return string 列出目录的路径
     * @access public
     */
    public function getListedPath()
    {

        // Build the path
        if ($this->_directory == '.') {
            $path = $this->_appURL;
        } else {
            $path = $this->_appURL . $this->_directory;
        }

        // Return the path
        return $path;
    }


    /**
     * 返回主题名称。
     *
     * @return string Theme name
     * @access public
     */
    public function getThemeName()
    {
        // Return the theme name
        return $this->_config['theme_name'];
    }


    /**
     * 返回另一个窗口中的打开链接
     *
     * @return boolean 如果在启用配置中打开另一个窗口中的链接，则返回true，否则返回false
     * @access public
     */
    public function externalLinksNewWindow()
    {
        return $this->_config['external_links_new_window'];
    }


    /**
     * Returns the path to the chosen theme directory
     *
     * @param bool $absolute Whether or not the path returned is absolute (default = false).
     * @return string Path to theme
     * @access public
     */
    public function getThemePath($absolute = false)
    {
        if ($absolute) {
            // Set the theme path
            $themePath = $this->_appDir . '/themes/' . $this->_themeName;
        } else {
            // Get relative path to application dir
            $realtivePath = $this->_getRelativePath(getcwd(), $this->_appDir);

            // Set the theme path
            $themePath = $realtivePath . '/themes/' . $this->_themeName;
        }

        return $themePath;
    }


    /**
     * Get an array of error messages or false when empty
     *
     * @return array|bool Array of error messages or false
     * @access public
     */
    public function getSystemMessages()
    {
        if (isset($this->_systemMessage) && is_array($this->_systemMessage)) {
            return $this->_systemMessage;
        } else {
            return false;
        }
    }


    /**
     * Returns string of file size in human-readable format
     *
     * @param  string $filePath Path to file
     * @return string Human-readable file size
     * @access public
     */
    function getFileSize($filePath)
    {

        // 获取文件大小
        $bytes = filesize($filePath);

        // 文件大小后缀数组
        $sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');

        // 计算文件大小后缀系数
        $factor = floor((strlen($bytes) - 1) / 3);

        // 计算文件大小
        $fileSize = sprintf('%.2f', $bytes / pow(1024, $factor)) . $sizes[$factor];

        return $fileSize;
    }


    /**
     * Returns array of file hash values
     *
     * @param  string $filePath Path to file
     * @return array Array of file hashes
     * @access public
     */
    public function getFileHash($filePath)
    {

        // Placeholder array
        $hashArray = array();

        // Verify file path exists and is a directory
        if (!file_exists($filePath)) {
            return json_encode($hashArray);
        }

        // Prevent access to hidden files
        if ($this->_isHidden($filePath)) {
            return json_encode($hashArray);
        }

        // Prevent access to parent folders
        if (
            strpos($filePath, '<') !== false || strpos($filePath, '>') !== false
            || strpos($filePath, '..') !== false || strpos($filePath, '/') === 0
        ) {
            return json_encode($hashArray);
        }

        // Prevent hashing if file is too big
        if (filesize($filePath) > $this->_config['hash_size_limit']) {

            // Notify user that file is too large
            $hashArray['md5']  = '[ 文件大小超过阈值 ]';
            $hashArray['sha1'] = '[ 文件大小超过阈值 ]';
        } else {

            // Generate file hashes
            $hashArray['md5']  = hash_file('md5', $filePath);
            $hashArray['sha1'] = hash_file('sha1', $filePath);
        }

        // Return the data
        return $hashArray;
    }


    /**
     * 设置目录路径变量
     *
     * @param string $path 目录的路径
     * @return string Sanitizd 目录的路径
     * @access public
     */
    public function setDirectoryPath($path = null)
    {

        // 设置目录全局变量，验证并返回目录路径
        $this->_directory = $this->_setDirectoryPath($path);

        return $this->_directory;
    }

    /**
     * 获取目录路径变量
     *
     * @return string Sanitizd 目录的路径
     * @access public
     */
    public function getDirectoryPath()
    {
        return $this->_directory;
    }

    /**
     * 获取配置
     * 
     * @param string $text      配置名称
     * @return string config    配置值
     * @access public
     */
    public function getConfig($text)
    {
        return $this->_config[$text];
    }


    /**
     * 获取README目录
     * 
     * @return string config  路径
     * @access public
     */
    public function getReadmePath()
    {
        $md_path_all = $this->getListedPath();
        $suffix_array = explode('.', $_SERVER['HTTP_HOST']);
        $suffix = end($suffix_array);
        $md_path = explode($suffix, $md_path_all);

        return $md_path;
    }


    /**
     * 获取README未转换的Text内容
     * 
     * @param string $text      配置名称
     * @return string config    配置值
     * @access public
     */
    public function getMarkdownText()
    {
        $md_path = $this->getReadmePath();
        if ($md_path[1] == "") {
            return "";
        }
        $md_path_last = substr($md_path[1], -1);
        if ($md_path_last != "/") {
            $md_file = "." . $md_path[1] . "/README.md";
        } else {
            $md_file = "." . $md_path[1] . "README.md";
        }
        if (file_exists($md_file)) {
            return file_get_contents($md_file);
        }
        return "";
    }

    /**
     * 获取README.md转换为HTML的文本内容
     * 
     * @return string config   html内容
     * @access public
     */
    public function getMarkdownHtml()
    {
        $md_text = $this->getMarkdownText();
        if ($md_text != "") {
            // https://github.com/erusev/parsedown
            $Parsedown = new Parsedown();
            return $Parsedown->text($md_text);
        }
        return "";
    }



    /**
     * 获取README.html的文本内容
     * 
     * @return string config  html内容
     * @access public
     */
    public function getReadmeHtml()
    {

        $md_path = $this->getReadmePath();
        if ($md_path[1] == "") {
            return "";
        }
        $md_path_last = substr($md_path[1], -1);

        if ($md_path_last != "/") {
            $md_file = "." . $md_path[1] . "/README.html";
        } else {
            $md_file = "." . $md_path[1] . "README.html";
        }
        if (file_exists($md_file)) {
            return file_get_contents($md_file);
        }
        return "";
    }


    /**
     * 将消息添加到系统消息数组
     *
     * @param string $type 消息的类型 (ie - error, success, notice, etc.)
     * @param string $message 要显示给用户的消息
     * @return bool true on success
     * @access public
     */
    public function setSystemMessage($type, $text)
    {

        // 创建空消息数组（如果它尚不存在）
        if (isset($this->_systemMessage) && !is_array($this->_systemMessage)) {
            $this->_systemMessage = array();
        }

        // Set the error message
        $this->_systemMessage[] = array(
            'type'  => $type,
            'text'  => $text
        );

        return true;
    }


    /**
     * 验证并返回目录路径
     *
     * @param string $dir Directory path
     * @return string Directory path to be listed
     * @access protected
     */
    protected function _setDirectoryPath($dir)
    {

        // 检查一个空变量
        if (empty($dir) || $dir == '.') {
            return '.';
        }

        // 消除双斜线
        while (strpos($dir, '//')) {
            $dir = str_replace('//', '/', $dir);
        }

        // 如果存在，删除尾部斜杠
        if (substr($dir, -1, 1) == '/') {
            $dir = substr($dir, 0, -1);
        }

        // 验证文件路径是否存在并且是目录
        if (!file_exists($dir) || !is_dir($dir)) {
            // 设置错误消息
            $this->setSystemMessage('danger', '<b>ERROR:</b> 文件路径不存在');

            // 返回Web根目录
            return '.';
        }

        // 阻止访问隐藏文件
        if ($this->_isHidden($dir)) {
            // 设置错误消息
            $this->setSystemMessage('danger', '<b>ERROR:</b> 拒绝访问');

            // 返回Web根目录
            return '.';
        }

        // 阻止访问父文件夹
        // strpos() 函数查找字符串在另一字符串中第一次出现的位置
        if (
            strpos($dir, '<') !== false || strpos($dir, '>') !== false
            || strpos($dir, '..') !== false || strpos($dir, '/') === 0
        ) {
            // 设置错误消息
            $this->setSystemMessage('danger', '<b>ERROR:</b> 检测到无效的路径字符串');

            // 返回Web根目录
            return '.';
        } else {
            // 应该停止所有URL包装器（感谢Hexatex）
            $directoryPath = $dir;
        }

        // Return
        return $directoryPath;
    }


    /**
     * 循环目录并返回包含文件信息的数组
     * 文件路径，大小，修改时间，图标和排序顺序。
     *
     * @param string $ directory目录路径
     * @param string $ sort Sort方法（默认= natcase）
     * @return array目录内容的数组
     * @access protected
     */
    protected function _readDirectory($directory, $sort = 'natcase')
    {

        // 初始化数组
        $directoryArray = array();

        // 获取目录内容
        $files = scandir($directory);

        // 从目录中读取文件/文件夹
        foreach ($files as $file) {

            if ($file != '.') {

                // 获取文件相对路径
                $relativePath = $directory . '/' . $file;

                if (substr($relativePath, 0, 2) == './') {
                    $relativePath = substr($relativePath, 2);
                }

                // 如果我们在根目录中，请不要检查父目录
                if ($this->_directory == '.' && $file == '..') {

                    continue;
                } else {

                    // 获取文件绝对路径
                    $realPath = realpath($relativePath);

                    // 按扩展名确定文件类型
                    if (is_dir($realPath)) {
                        $iconClass = 'fa-folder';
                        $sort = 1;
                    } else {
                        // 获取文件扩展名
                        // pathinfo() 函数以数组的形式返回文件路径的信息
                        // strtolower() 函数把字符串转换为小写。
                        $fileExt = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));

                        // isset() 判断一个变量是否已经声明
                        if (isset($this->_fileTypes[$fileExt])) {
                            $iconClass = $this->_fileTypes[$fileExt];
                        } else {
                            $iconClass = $this->_fileTypes['blank'];
                        }

                        $sort = 2;
                    }
                }

                if ($file == '..') {

                    if ($this->_directory != '.') {
                        // 获取父目录路径
                        $pathArray = explode('/', $relativePath);
                        // 销毁单个数组元素
                        unset($pathArray[count($pathArray) - 1]);
                        unset($pathArray[count($pathArray) - 1]);
                        // implode() 把数组元素按/组合为字符串
                        $directoryPath = implode('/', $pathArray);

                        if (!empty($directoryPath)) {
                            // 转码并拼接url
                            $directoryPath = '?dir=' . rawurlencode($directoryPath);
                        }

                        // 将文件信息添加到数组中
                        $directoryArray['..'] = array(
                            'file_path'  => $this->_appURL . $directoryPath,
                            'url_path'   => $this->_appURL . $directoryPath,
                            'file_size'  => '-',
                            'mod_time'   => date('Y-m-d H:i:s', filemtime($realPath)),
                            'icon_class' => 'fa-level-up',
                            'sort'       => 0
                        );
                    }
                } elseif (!$this->_isHidden($relativePath)) {

                    // 将所有非隐藏文件添加到数组中
                    if ($this->_directory != '.' || $file != 'index.php') {

                        // 构建文件路径
                        // implode() 把数组元素按/组合为字符串
                        // array_map() 函数作用到数组中的每个值上，并返回带有新值的数组
                        // explode() 函数把字符串打散为数组
                        $urlPath = implode('/', array_map('rawurlencode', explode('/', $relativePath)));

                        if (is_dir($relativePath)) {
                            $urlPath = '?dir=' . $urlPath;
                        } else {
                            $urlPath = $urlPath;
                        }

                        // 由larry将信息添加到主数组中
                        preg_match('/\/([^\/]*)$/', $relativePath, $matches);
                        // isset() 判断一个变量是否已经声明
                        $pathname = isset($matches[1]) ? $matches[1] : $relativePath;
                        //$directoryArray[pathinfo($relativePath, PATHINFO_BASENAME)] = array(
                        $directoryArray[$pathname] = array(
                            'file_path'  => $relativePath,
                            'url_path'   => $urlPath,
                            'file_size'  => is_dir($realPath) ? '-' : $this->getFileSize($realPath),
                            'mod_time'   => date('Y-m-d H:i:s', filemtime($realPath)),
                            'icon_class' => $iconClass,
                            'sort'       => $sort
                        );
                    }
                }
            }
        }

        // 排序数组
        $reverseSort = in_array($this->_directory, $this->_config['reverse_sort']);
        $sortedArray = $this->_arraySort($directoryArray, $this->_config['list_sort_order'], $reverseSort);

        // 返回数组
        return $sortedArray;
    }


    /**
     * Sorts an array by the provided sort method.
     *
     * @param array $array Array to be sorted
     * @param string $sortMethod Sorting method (acceptable inputs: natsort, natcasesort, etc.)
     * @param boolen $reverse Reverse the sorted array order if true (default = false)
     * @return array
     * @access protected
     */
    protected function _arraySort($array, $sortMethod, $reverse = false)
    {
        // Create empty arrays
        $sortedArray = array();
        $finalArray  = array();

        // Create new array of just the keys and sort it
        $keys = array_keys($array);

        switch ($sortMethod) {
            case 'asort':
                asort($keys);
                break;
            case 'arsort':
                arsort($keys);
                break;
            case 'ksort':
                ksort($keys);
                break;
            case 'krsort':
                krsort($keys);
                break;
            case 'natcasesort':
                natcasesort($keys);
                break;
            case 'natsort':
                natsort($keys);
                break;
            case 'shuffle':
                shuffle($keys);
                break;
        }

        // Loop through the sorted values and move over the data
        if ($this->_config['list_folders_first']) {

            foreach ($keys as $key) {
                if ($array[$key]['sort'] == 0) {
                    $sortedArray['0'][$key] = $array[$key];
                }
            }

            foreach ($keys as $key) {
                if ($array[$key]['sort'] == 1) {
                    $sortedArray[1][$key] = $array[$key];
                }
            }

            foreach ($keys as $key) {
                if ($array[$key]['sort'] == 2) {
                    $sortedArray[2][$key] = $array[$key];
                }
            }

            if ($reverse) {
                $sortedArray[1] = array_reverse($sortedArray[1]);
                $sortedArray[2] = array_reverse($sortedArray[2]);
            }
        } else {

            foreach ($keys as $key) {
                if ($array[$key]['sort'] == 0) {
                    $sortedArray[0][$key] = $array[$key];
                }
            }

            foreach ($keys as $key) {
                if ($array[$key]['sort'] > 0) {
                    $sortedArray[1][$key] = $array[$key];
                }
            }

            if ($reverse) {
                $sortedArray[1] = array_reverse($sortedArray[1]);
            }
        }

        // Merge the arrays
        foreach ($sortedArray as $array) {
            if (empty($array)) continue;
            foreach ($array as $key => $value) {
                $finalArray[$key] = $value;
            }
        }

        // Return sorted array
        return $finalArray;
    }


    /**
     * Determines if a file is specified as hidden
     *
     * @param string $filePath Path to file to be checked if hidden
     * @return boolean Returns true if file is in hidden array, false if not
     * @access protected
     */
    protected function _isHidden($filePath)
    {

        // Add dot files to hidden files array
        if ($this->_config['hide_dot_files']) {

            $this->_config['hidden_files'] = array_merge(
                $this->_config['hidden_files'],
                array('.*', '*/.*')
            );
        }

        // Compare path array to all hidden file paths
        foreach ($this->_config['hidden_files'] as $hiddenPath) {

            if (fnmatch($hiddenPath, $filePath)) {

                return true;
            }
        }

        return false;
    }


    /**
     * Builds the root application URL from server variables.
     *
     * @return string The application URL
     * @access protected
     */
    protected function _getAppUrl()
    {

        // Get the server protocol
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }

        // Get the server hostname
        $host = $_SERVER['HTTP_HOST'];

        // Get the URL path
        $pathParts = pathinfo($_SERVER['PHP_SELF']);
        $path      = $pathParts['dirname'];

        // Remove backslash from path (Windows fix)
        if (substr($path, -1) == '\\') {
            $path = substr($path, 0, -1);
        }

        // Ensure the path ends with a forward slash
        if (substr($path, -1) != '/') {
            $path = $path . '/';
        }

        // Build the application URL
        $appUrl = $protocol . $host . $path;

        // Return the URL
        return $appUrl;
    }


    /**
     * Compares two paths and returns the relative path from one to the other
     *
     * @param string $fromPath Starting path
     * @param string $toPath Ending path
     * @return string $relativePath Relative path from $fromPath to $toPath
     * @access protected
     */
    protected function _getRelativePath($fromPath, $toPath)
    {

        // Define the OS specific directory separator
        if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

        // Remove double slashes from path strings
        $fromPath = str_replace(DS . DS, DS, $fromPath);
        $toPath = str_replace(DS . DS, DS, $toPath);

        // Explode working dir and cache dir into arrays
        $fromPathArray = explode(DS, $fromPath);
        $toPathArray = explode(DS, $toPath);

        // Remove last fromPath array element if it's empty
        $x = count($fromPathArray) - 1;

        if (!trim($fromPathArray[$x])) {
            array_pop($fromPathArray);
        }

        // Remove last toPath array element if it's empty
        $x = count($toPathArray) - 1;

        if (!trim($toPathArray[$x])) {
            array_pop($toPathArray);
        }

        // Get largest array count
        $arrayMax = max(count($fromPathArray), count($toPathArray));

        // Set some default variables
        $diffArray = array();
        $samePath = true;
        $key = 1;

        // Generate array of the path differences
        while ($key <= $arrayMax) {

            // Get to path variable
            $toPath = isset($toPathArray[$key]) ? $toPathArray[$key] : null;

            // Get from path variable
            $fromPath = isset($fromPathArray[$key]) ? $fromPathArray[$key] : null;

            if ($toPath !== $fromPath || $samePath !== true) {

                // Prepend '..' for every level up that must be traversed
                if (isset($fromPathArray[$key])) {
                    array_unshift($diffArray, '..');
                }

                // Append directory name for every directory that must be traversed
                if (isset($toPathArray[$key])) {
                    $diffArray[] = $toPathArray[$key];
                }

                // Directory paths have diverged
                $samePath = false;
            }

            // Increment key
            $key++;
        }

        // Set the relative thumbnail directory path
        $relativePath = implode('/', $diffArray);

        // Return the relative path
        return $relativePath;
    }
}
