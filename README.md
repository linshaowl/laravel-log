## 介绍

> 环境变量值参考：[env](docs/ENV.md)

## 安装配置

使用以下命令安装：
```
$ composer require lswl/laravel-log
```

## 快速使用

- 配置环境变量
- `LSWL_LOG_DEBUG=false` 时 `debug` 级别日志不写入(默认为 `fasle`)
- 方法介绍:
    - `dir(string $dir)` 设置日志保存路径,相对于 `storage/logs`
    - `name(string $dir)` 设置日志名称(默认为 `laravel.log`)
    - `withDateToDir(bool $isBefore = true, string $format = 'Ymd')` 给路径添加日期
    - `withDateToName(bool $isBefore = true, string $format = 'Ymd')` 给名称添加日期
    - `withRequestInfo(bool $with = true)` 日志添加请求消息
    - `withMessageLineBreak(bool $with = true)` 日志添加消息换行
    - `throwable(Throwable $e, array $context = [])` 记录异常日志
- 书写业务代码

**业务代码：**
```php
use Lswl\Log\Log;

// 添加各个级别日志
// emergency、alert、 critical、 error、 warning、 notice、 info 和 debug
Log::info('This is info log');

// 添加指定路径
// app/storage/logs/d/laravel.log
Log::dir('d')
->info('This is info log');
// app/storage/logs/20200101/d/laravel.log
Log::dir('d')
->withDateToDir()
->info('This is info log');

// 添加指定名称路径
// app/storage/logs/d/i.log
Log::dir('d')
->name('i')
->info('This is info log');
// app/storage/logs/d/20200101-i.log
Log::dir('d')
->name('i')
->withDateToName()
->info('This is info log');
```
