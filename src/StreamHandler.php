<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\Log;

use Lswl\Support\Utils\RequestInfo;
use Lswl\Support\Utils\StorageUnitConversion;
use Monolog\Logger;

/**
 * 流操作
 */
class StreamHandler extends \Monolog\Handler\StreamHandler
{
    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var bool
     */
    protected $withRequestInfo;

    /**
     * @var string
     */
    protected $maxSize;

    /**
     * @var int
     */
    protected $maxFiles;

    public function __construct(
        $stream,
        bool $debug = false,
        bool $withRequestInfo = false,
        string $maxSize = '0',
        int $maxFiles = 0,
        $level = Logger::DEBUG,
        bool $bubble = true,
        ?int $filePermission = null,
        bool $useLocking = false
    ) {
        $this->debug = $debug;
        $this->withRequestInfo = $withRequestInfo;
        $this->maxSize = $maxSize;
        $this->maxFiles = $maxFiles;

        parent::__construct($stream, $level, $bubble, $filePermission, $useLocking);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        // debug 日志,并且调试未开启
        if ($record['level'] == Logger::DEBUG && !$this->debug) {
            return;
        }

        // 写入请求信息
        if ($this->withRequestInfo) {
            $record['formatted'] = $this->writeRequestInfo($record['formatted'], RequestInfo::get() . "\n");
        }

        // 写入前操作
        $this->writeBefore();

        parent::write($record);
    }

    /**
     * 写入请求信息
     * @param string $formatted
     * @param string $info
     * @return string
     */
    protected function writeRequestInfo(string $formatted, string $info): string
    {
        // 异常栈开始结束位置
        $start = mb_strpos($formatted, '#0 /');
        $end = mb_strpos($formatted, '{main}');

        // 不存在异常栈
        if ($start === false || $end === false) {
            return $formatted . $info;
        }

        // 组装请求信息
        return mb_substr($formatted, 0, $start) . $info . mb_substr($formatted, $start, $end);
    }

    /**
     * 写入前操作
     */
    protected function writeBefore()
    {
        // 文件不存在
        if (!file_exists($this->url)) {
            return;
        }

        // 文件信息
        $fileInfo = pathinfo($this->url);

        // 检测文件大小
        $this->checkMaxSize($fileInfo);

        // 检测文件数量
        $this->checkMaxFiles($fileInfo);
    }

    /**
     * 检测文件大小
     * @param array $fileInfo
     */
    protected function checkMaxSize(array $fileInfo)
    {
        if (!$this->maxSize) {
            return;
        }

        // 清除文件缓存
        clearstatcache();

        // 验证大小
        if (StorageUnitConversion::str2byte($this->maxSize) > filesize($this->url)) {
            return;
        }

        // 关闭当前句柄
        $this->close();

        // 获取文件
        $files = $this->getFiles($fileInfo);

        // 获取最大数量
        $max = $this->getMaxNumByFiles($files, $fileInfo['extension']);

        // 重命名文件
        rename(
            $this->url,
            sprintf(
                '%s/%s.%d.%s',
                $fileInfo['dirname'],
                $fileInfo['filename'],
                $max + 1,
                $fileInfo['extension']
            )
        );
    }

    /**
     * 检测最大数量
     * @param array $fileInfo
     */
    protected function checkMaxFiles(array $fileInfo)
    {
        if (!$this->maxFiles) {
            return;
        }

        // 获取文件
        $files = $this->getFiles($fileInfo);

        // 判断数量
        if (count($files) <= $this->maxFiles) {
            return;
        }

        // 关闭当前句柄
        $this->close();

        // 文件排序
        $files = $this->filesSort($files, $fileInfo['extension'], 'desc');

        // 删除文件
        foreach (array_slice($files, $this->maxFiles) as $file) {
            if (is_writable($file)) {
                set_error_handler(function () {
                    return false;
                });
                unlink($file);
                restore_error_handler();
            }
        }
    }

    /**
     * 获取文件
     * @param $fileInfo
     * @return array
     */
    protected function getFiles($fileInfo): array
    {
        return glob(
            sprintf(
                '%s/%s.*%s',
                $fileInfo['dirname'],
                $fileInfo['filename'],
                $fileInfo['extension']
            )
        );
    }

    /**
     * 文件排序
     * @param array $files
     * @param string $extension
     * @param string $order
     * @return array
     */
    protected function filesSort(array $files, string $extension, string $order = 'desc'): array
    {
        uasort($files, function ($a, $b) use ($extension, $order) {
            preg_match(sprintf('/(\d+)\.%s$/', $extension), $a, $matchA);
            preg_match(sprintf('/(\d+)\.%s$/', $extension), $b, $matchB);
            if (empty($matchA[1])) {
                return $order == 'desc' ? -1 : 1;
            }
            if (empty($matchB[1])) {
                return $order == 'desc' ? 1 : -1;
            }
            return $order == 'desc' ? $matchB[1] - $matchA[1] : $matchA[1] - $matchB[1];
        });

        return array_values($files);
    }

    /**
     * 获取最大数量
     * @param array $files
     * @param string $extension
     * @return int|mixed
     */
    protected function getMaxNumByFiles(array $files, string $extension)
    {
        $max = 0;
        foreach ($files as $v) {
            preg_match(sprintf('/(\d+)\.%s$/', $extension), $v, $match);
            if (empty($match[1])) {
                continue;
            }

            if ($match[1] > $max) {
                $max = $match[1];
            }
        }
        return $max;
    }
}
