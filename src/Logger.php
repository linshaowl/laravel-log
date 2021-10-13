<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\Log;

use DateTimeZone;

/**
 * 日志通道
 */
class Logger extends \Monolog\Logger
{
    /**
     * @var bool
     */
    protected $withMessageLineBreak;

    public function __construct(
        bool $withMessageLineBreak,
        string $name,
        array $handlers = [],
        array $processors = [],
        ?DateTimeZone $timezone = null
    ) {
        $this->withMessageLineBreak = $withMessageLineBreak;

        parent::__construct($name, $handlers, $processors, $timezone);
    }

    /**
     * {@inheritdoc}
     */
    public function addRecord(int $level, string $message, array $context = []): bool
    {
        // 添加消息换行
        if ($this->withMessageLineBreak) {
            $message = "\n" . $message;
        }

        return parent::addRecord($level, $message, $context);
    }
}
