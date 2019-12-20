<?php
// https://github.com/erusev/parsedown
// 导入Parsedown依赖
require_once('Parsedown.php');
require_once('ParsedownExtra.php');
require_once('ParsedownExtraPlugin.php');
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

        // 构建应用程序URL（主机域名地址）
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
     * 如果允许压缩整个目录
     *
     * @param string $directory 列出目录的相对路径
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

            // 清理目录路径
            $directory = $this->setDirectoryPath($directory);

            if ($directory != '.' && $this->_isHidden($directory)) {
                echo "Access denied.";
            }

            $filename_no_ext = basename($directory);

            if ($directory == '.') {
                $filename_no_ext = $this->_config['web_title'];
            }

            // 我们提供一个zip文件
            header('Content-Type: archive/zip');

            // 浏览器的文件名保存zip文件
            header("Content-Disposition: attachment; filename=\"$filename_no_ext.zip\"");

            // 更改目录，以便该zip文件中没有树结构。
            chdir($directory);

            // 待办事项：可能我们必须更仔细地分析排除列表
            $exclude_list = implode(' ', array_merge($this->_config['hidden_files'], array('index.php')));
            $exclude_list = str_replace("*", "\*", $exclude_list);

            if ($this->_config['zip_stream']) {

                // 将内容（dir和所有内容）压缩到流式zip文件中
                $stream = popen('/usr/bin/zip -' . $this->_config['zip_compression_level'] . ' -r -q - * -x ' . $exclude_list, 'r');

                if ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                }
            } else {

                // 获取.zip的tmp名称
                $tmp_zip = tempnam('tmp', 'tempzip') . '.zip';

                // 将东西（dir和所有内容）压缩到tmp_zip文件中
                exec('zip -' . $this->_config['zip_compression_level'] . ' -r ' . $tmp_zip . ' * -x ' . $exclude_list);

                // 计算拉链的长度。浏览器的进度条需要它
                $filesize = filesize($tmp_zip);
                header("Content-Length: $filesize");

                // 传送zip文件
                $fp = fopen($tmp_zip, 'r');
                echo fpassthru($fp);

                // 清理tmp zip文件
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
        return $this->_readDirectory($directory);
    }


    /**
     * 解析并返回面包屑导航数组
     *
     * @param string $directory 面包屑的路径
     * @return array 面包屑数组
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

        // 生成面包屑
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
        return $breadcrumbsArray;
    }


    /**
     * 确定目录是否包含索引文件
     *
     * @param string $dirPath 要检查索引的目录路径
     * @return boolean Returns 如果目录包含有效的索引文件，则为true；否则为false
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
     * 获取列出目录的全（绝对）路径
     *
     * @return string 列出目录的路径
     * @access public
     */
    public function getListedFullPath()
    {
        // 如果当前目录为根目录
        if ($this->_directory == '.') {
            $path = $this->_appURL;
        } else {
            $path = $this->_appURL . $this->_directory;
        }
        return $path;
    }

    /**
     * 获取列出目录的路径（不包含网站主机域名地址）
     *
     * @return string 列出目录的路径
     * @access public
     */
    public function getListedPath()
    {
        // 如果当前目录为根目录
        if ($this->_directory == '.') {
            $path = "";
        } else {
            $path = $this->_directory;
        }
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
     * 返回所选主题目录的路径
     *
     * @param bool $absolute 返回的路径是否为绝对路径（默认为false）。
     * @return string Path to theme
     * @access public
     */
    public function getThemePath($absolute = false)
    {
        if ($absolute) {
            // 设置主题路径
            $themePath = $this->_appDir . '/themes/' . $this->_themeName;
        } else {
            // 获取应用程序目录的相对路径
            $realtivePath = $this->_getRelativePath(getcwd(), $this->_appDir);

            // 设置主题路径
            $themePath = $realtivePath . '/themes/' . $this->_themeName;
        }
        return $themePath;
    }


    /**
     * 获取错误消息数组；如果为空，则返回false
     *
     * @return array|bool 错误消息数组或false
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
     * 以可读格式返回文件大小的字符串
     *
     * @param  string $filePath 文件路径
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
        return sprintf('%.2f', $bytes / pow(1024, $factor)) . $sizes[$factor];
    }


    /**
     * 返回文件哈希值的数组
     *
     * @param  string $filePath Path to file
     * @return array Array of file hashes
     * @access public
     */
    public function getFileHash($filePath)
    {
        // 占位符数组
        $hashArray = array();

        // 验证文件路径是否存在并且是目录
        if (!file_exists($filePath)) {
            return json_encode($hashArray);
        }

        // 禁止访问隐藏文件
        if ($this->_isHidden($filePath)) {
            return json_encode($hashArray);
        }
        // 禁止访问父文件夹
        if (
            strpos($filePath, '<') !== false || strpos($filePath, '>') !== false
            || strpos($filePath, '..') !== false || strpos($filePath, '/') === 0
        ) {
            return json_encode($hashArray);
        }
        // 如果文件太大，防止散列
        if (filesize($filePath) > $this->_config['hash_size_limit']) {
            // 通知用户文件太大
            $hashArray['md5']  = '[ 文件大小超过阈值 ]';
            $hashArray['sha1'] = '[ 文件大小超过阈值 ]';
        } else {
            // 生成文件哈希
            $hashArray['md5']  = hash_file('md5', $filePath);
            $hashArray['sha1'] = hash_file('sha1', $filePath);
        }
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
     * 获取README内容
     * 
     * @return string config  配置值
     * @access public
     */
    public function getReadme()
    {
        // 获取当前目录
        $md_path = $this->getListedPath();
        if ($md_path == "") {
            $md_path = "./";
        }
        // 获取config文件中的readme_mode的解析模式
        $readme_mode = $this->getConfig("readme_mode");
        // 如果配置为空，默认使用Markdown方式
        if ($readme_mode == "" || strtoupper($readme_mode) != "HTML") {
            $md_path =  $md_path . "/README.md";

            if (!file_exists($md_path)) {
                return "";
            }
            // https://github.com/erusev/parsedown
            // https://github.com/erusev/parsedown-extra
            // https://github.com/tovic/parsedown-extra-plugin
            $Parsedown = new ParsedownExtraPlugin();

            $Parsedown->headerAttributes = function ($Text, $Attributes, &$Element, $Level) {
                $Id = $Attributes['id'] ?? trim(
                    preg_replace(['/[^a-z\d\x{4e00}-\x{9fa5}]+/u'], '-', strtolower($Text)),
                    '-'
                );
                return ['id' => $Id];
            };
            $Parsedown->headerText = function ($Text, $Attributes, &$Element, $Level) {
                $Id = $Attributes['id'] ?? trim(
                    preg_replace(['/[^a-z\d\x{4e00}-\x{9fa5}]+/u'], '-', strtolower($Text)),
                    '-'
                );
                return '<a href="#' . $Id . '" class="header-anchor">#</a>' . $Text;
            };
            $Parsedown->linkAttributes = function ($Text, $Attributes, &$Element, $Internal) {
                $href = strtolower($Attributes['href']);
                // https://www.chrisyue.com/the-fastest-way-to-implement-starts-with-in-php.html
                if (!$Internal && (strpos($href, "https://") === 0 || strpos($href, "http://") === 0)) {
                    return [
                        'target' => '_blank'
                    ];
                }
                return [];
            };
            return $Parsedown->text(file_get_contents($md_path));
        } else {
            $md_path =  $md_path . "/README.html";
            if (!file_exists($md_path)) {
                return "";
            }
            return file_get_contents($md_path);
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

            if ($file == '.') {
                continue;
            }
            // 如果我们在根目录中，请不要检查父目录
            if ($this->_directory == '.' && $file == '..') {

                continue;
            }
            // 获取文件相对路径
            $relativePath = $directory . '/' . $file;

            if (substr($relativePath, 0, 2) == './') {
                $relativePath = substr($relativePath, 2);
            }


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


            if ($file == '..' && ($this->_directory != '.')) {
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
                $directoryArray['返回上一层'] = array(
                    'file_path'  => $this->_appURL . $directoryPath,
                    'url_path'   => $this->_appURL . $directoryPath,
                    'file_size'  => '-',
                    'mod_time'   => date('Y-m-d H:i:s', filemtime($realPath)),
                    'icon_class' => 'fa-level-up',
                    'sort'       => 0
                );
            } elseif

            // 将所有非隐藏文件添加到数组中
            (!$this->_isHidden($relativePath) && ($this->_directory != '.' || $file != 'index.php')) {

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
        // 排序数组
        $reverseSort = in_array($this->_directory, $this->_config['reverse_sort']);
        return $this->_arraySort($directoryArray, $this->_config['list_sort_order'], $reverseSort);;
    }


    /**
     * 通过提供的sort方法对数组进行排序。
     *
     * @param array $array 要排序的数组
     * @param string $sortMethod 排序方法（可接受的输入：natsort，natcasesort等）
     * @param boolen $reverse 如果为true，则反转排序的数组顺序（默认= false）
     * @return array
     * @access protected
     */
    protected function _arraySort($array, $sortMethod, $reverse = false)
    {
        // 创建空数组
        $sortedArray = array();
        $finalArray  = array();

        // 创建仅键的新数组并对其进行排序
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

        // 遍历排序的值并移至数据上
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
        return $finalArray;
    }


    /**
     * 确定文件是否指定为隐藏
     *
     * @param string $filePath 隐藏文件检查路径
     * @return boolean Returns 如果文件位于隐藏数组中，则为true；否则为false
     * @access protected
     */
    protected function _isHidden($filePath)
    {
        // 将点文件添加到隐藏文件数组
        if ($this->_config['hide_dot_files']) {

            $this->_config['hidden_files'] = array_merge(
                $this->_config['hidden_files'],
                array('.*', '*/.*')
            );
        }

        // 比较路径数组与所有隐藏文件的路径
        foreach ($this->_config['hidden_files'] as $hiddenPath) {

            if (fnmatch($hiddenPath, $filePath)) {

                return true;
            }
        }
        return false;
    }


    /**
     * 根据服务器变量构建根应用程序URL。
     *
     * @return string 这个应用的URL地址
     * @access protected
     */
    protected function _getAppUrl()
    {
        // 获取服务器协议
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }

        // 获取服务器主机名
        $host = $_SERVER['HTTP_HOST'];
        // 获取网页地址
        // $_SERVER['PHP_SELF'];
        // 获取网址参数
        // $_SERVER["QUERY_STRING"];
        // 获取用户代理
        // $_SERVER['HTTP_REFERER'];
        // 获取请求URL
        //$_SERVER['REQUEST_URI'];
        // 获取网址参数
        //$_SERVER['QUERY_STRING'];
        // 获取主机名称
        //$_SERVER['SERVER_NAME'];
        // 获取端口号
        //$_SERVER["SERVER_PORT"];

        // 获取URL路径
        $pathParts = pathinfo($_SERVER['PHP_SELF']);
        $path      = $pathParts['dirname'];

        // 从路径中删除反斜杠（Windows修复）
        if (substr($path, -1) == '\\') {
            $path = substr($path, 0, -1);
        }

        // 确保路径以正斜杠结尾
        if (substr($path, -1) != '/') {
            $path = $path . '/';
        }
        // 组成网址
        return $protocol . $host . $path;
    }


    /**
     * 比较两条路径并返回一条到另一条的相对路径
     *
     * @param string $fromPath 起始路径
     * @param string $toPath 结束路径
     * @return string $relativePath Relative path from $fromPath to $toPath
     * @access protected
     */
    protected function _getRelativePath($fromPath, $toPath)
    {
        // 定义操作系统特定的目录分隔符
        if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

        // 从路径字符串中删除双斜杠
        $fromPath = str_replace(DS . DS, DS, $fromPath);
        $toPath = str_replace(DS . DS, DS, $toPath);

        // 分解工作目录并将目录缓存到数组中
        $fromPathArray = explode(DS, $fromPath);
        $toPathArray = explode(DS, $toPath);

        // 从空数组元素中删除最后一个（如果为空）
        $x = count($fromPathArray) - 1;

        if (!trim($fromPathArray[$x])) {
            array_pop($fromPathArray);
        }

        // 删除最后一个toPath数组元素（如果为空）
        $x = count($toPathArray) - 1;

        if (!trim($toPathArray[$x])) {
            array_pop($toPathArray);
        }

        // 获得最大的阵列数
        $arrayMax = max(count($fromPathArray), count($toPathArray));

        // 设置一些默认变量
        $diffArray = array();
        $samePath = true;
        $key = 1;

        // 生成路径差异数组
        while ($key <= $arrayMax) {

            // 到达路径变量
            $toPath = isset($toPathArray[$key]) ? $toPathArray[$key] : null;

            // 从路径变量获取
            $fromPath = isset($fromPathArray[$key]) ? $fromPathArray[$key] : null;

            if ($toPath !== $fromPath || $samePath !== true) {

                // 对于必须遍历的每个级别，将".."作为前缀
                if (isset($fromPathArray[$key])) {
                    array_unshift($diffArray, '..');
                }

                // 为必须遍历的每个目录追加目录名称
                if (isset($toPathArray[$key])) {
                    $diffArray[] = $toPathArray[$key];
                }

                // 目录路径已分开
                $samePath = false;
            }

            // 增量键
            $key++;
        }

        // 设置相对缩略图目录路径
        return implode('/', $diffArray);
    }
}
