<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\Log;

use Illuminate\Log\Logger;
use Throwable;

/**
 * 日志管理
 */
class LogManager extends \Illuminate\Log\LogManager
{
    /**
     * @var string
     */
    protected $dir;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var WithDate
     */
    protected $withDateToDir;

    /**
     * @var WithDate
     */
    protected $withDateToName;

    /**
     * @var bool
     */
    protected $withRequestInfo;

    /**
     * @var bool
     */
    protected $withMessageLineBreak;

    /**
     * @var string
     */
    protected $configKey = 'lswl_log';

    public function __construct($app)
    {
        parent::__construct($app);

        // 初始化
        $this->init();
    }

    /**
     * 初始化
     * @return $this
     */
    public function init()
    {
        $this->dir = '';
        $this->name = 'laravel.log';
        $this->withDateToDir = null;
        $this->withDateToName = null;
        $this->withRequestInfo = false;
        $this->withMessageLineBreak = false;

        return $this;
    }

    /**
     * 设置路径
     * @param string $dir
     * @return $this
     */
    public function dir(string $dir)
    {
        $this->dir = $dir;

        return $this;
    }

    /**
     * 设置名称
     * @param string $name
     * @return $this
     */
    public function name(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 添加时间给路径
     * @param bool $isBefore
     * @param string $format
     * @return $this
     */
    public function withDateToDir(bool $isBefore = true, string $format = 'Ymd')
    {
        $this->withDateToDir = new WithDate($format, $isBefore);

        return $this;
    }

    /**
     * 添加时间给名称
     * @param bool $isBefore
     * @param string $format
     * @return $this
     */
    public function withDateToName(bool $isBefore = true, string $format = 'Ymd')
    {
        $this->withDateToName = new WithDate($format, $isBefore);

        return $this;
    }

    /**
     * 添加请求信息
     * @param bool $with
     * @return $this
     */
    public function withRequestInfo(bool $with = true)
    {
        $this->withRequestInfo = $with;

        return $this;
    }

    /**
     * 添加消息换行
     * @param bool $with
     * @return $this
     */
    public function withMessageLineBreak(bool $with = true)
    {
        $this->withMessageLineBreak = $with;

        return $this;
    }

    /**
     * 添加异常消息
     * @param Throwable $e
     * @param array $context
     * @return $this
     */
    public function throwable(Throwable $e, array $context = [])
    {
        // 保存消息
        $msg = $e->getMessage() . "\n" . $e->getTraceAsString();

        $this->emergency($msg, $context);

        return $this;
    }

    /**
     * 获取路径
     * @return string
     */
    protected function getDir()
    {
        $dir = trim($this->dir, '/');

        if ($this->withDateToDir instanceof WithDate) {
            $dir = rtrim(
                sprintf('%s/%s', ...$this->withDateToDir->getParams($dir)),
                '/'
            );
        }

        return storage_path('logs') . ($dir ? '/' . $dir : '');
    }

    /**
     * 获取文件名称
     * @return string
     */
    protected function getName()
    {
        $name = trim($this->name, '/');

        if ($this->withDateToName instanceof WithDate) {
            $name = rtrim(
                sprintf('%s-%s', ...$this->withDateToName->getParams($name)),
                '-'
            );
        }

        $name = preg_replace('/\.log$/i', '', $name);
        return ($name ? $name : 'laravel') . '.log';
    }

    /**
     * 获取路径
     * @return string
     */
    protected function getPath()
    {
        return $this->getDir() . '/' . $this->getName();
    }

    /**
     * 获取实例名称
     * @param string $name
     * @return string
     */
    protected function getInstanceName(string $name)
    {
        return sprintf('%s-%s', $name, md5($this->getPath()));
    }

    /**
     * {@inheritdoc}
     */
    protected function configurationFor($name)
    {
        $config = parent::configurationFor($this->configKey);
        $config['path'] = $this->getPath();
        $config['with_request_info'] = $this->withRequestInfo;
        $config['with_message_line_break'] = $this->withMessageLineBreak;

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    protected function get($name, ?array $config = null)
    {
        try {
            // 实例名称
            $instance = $this->getInstanceName($name);

            // 返回实例
            return $this->channels[$instance] ?? with($this->resolve($name), function ($logger) use ($name, $instance) {
                return $this->channels[$instance] = $this->tap($name, new Logger($logger, $this->app['events']));
            });
        } catch (Throwable $e) {
            return tap($this->createEmergencyLogger(), function ($logger) use ($e) {
                $logger->emergency('Unable to create configured logger. Using emergency logger.', [
                    'exception' => $e,
                ]);
            });
        }
    }
}
