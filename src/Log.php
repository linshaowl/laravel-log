<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\Log;

use Throwable;

/**
 * 日志
 * @method static LogManager dir(string $dir)
 * @method static LogManager name(string $filename)
 * @method static LogManager withDateToDir(bool $isBefore = true, string $format = 'Ymd')
 * @method static LogManager withDateToName(bool $isBefore = true, string $format = 'Ymd')
 * @method static LogManager withRequestInfo(bool $with = true)
 * @method static LogManager withMessageLineBreak(bool $with = true)
 * @method static LogManager throwable(Throwable $e, array $context = [])
 */
class Log extends \Illuminate\Support\Facades\Log
{
    protected static function getFacadeAccessor()
    {
        // 存在实例初始化并返回
        if (static::$app->has(LogManager::class)) {
            static::$app->get(LogManager::class)->init();
            return LogManager::class;
        }

        // 创建单例实例
        app()->instance(LogManager::class, new LogManager(static::$app));

        return LogManager::class;
    }
}
