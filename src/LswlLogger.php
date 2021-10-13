<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\Log;

use Illuminate\Log\ParsesLogConfiguration;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger as Monolog;

/**
 * 自定义日志通道
 */
class LswlLogger
{
    use ParsesLogConfiguration;

    /**
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * Prepare the handler for usage by Monolog.
     *
     * @param HandlerInterface $handler
     * @param array $config
     * @return HandlerInterface
     */
    protected function prepareHandler(HandlerInterface $handler, array $config = [])
    {
        $isHandlerFormattable = false;

        if (Monolog::API === 1) {
            $isHandlerFormattable = true;
        } elseif (Monolog::API === 2 && $handler instanceof FormattableHandlerInterface) {
            $isHandlerFormattable = true;
        }

        if ($isHandlerFormattable && !isset($config['formatter'])) {
            $handler->setFormatter($this->formatter());
        } elseif ($isHandlerFormattable && $config['formatter'] !== 'default') {
            $handler->setFormatter(app($config['formatter'], $config['formatter_with'] ?? []));
        }

        return $handler;
    }

    /**
     * Get a Monolog formatter instance.
     *
     * @return FormatterInterface
     */
    protected function formatter()
    {
        return tap(new LineFormatter(null, $this->dateFormat, true, true), function ($formatter) {
            $formatter->includeStacktraces();
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function getFallbackChannelName()
    {
        return app()->bound('env') ? app()->environment() : 'production';
    }

    public function __invoke(array $config)
    {
        return new Logger($config['with_message_line_break'], $this->parseChannel($config), [
            $this->prepareHandler(
                new StreamHandler(
                    $config['path'],
                    $config['debug'],
                    $config['with_request_info'],
                    $config['max_size'],
                    $config['max_files'],
                    $this->level($config),
                    $config['bubble'],
                    $config['permission'],
                    $config['locking']
                ),
                $config
            ),
        ]);
    }
}
