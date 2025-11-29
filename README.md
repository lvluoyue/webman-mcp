# webman-mcp

![Packagist Version](https://img.shields.io/packagist/v/luoyue/webman-mcp) ![Packagist License](https://img.shields.io/packagist/l/luoyue/webman-mcp) ![PHP Version](https://img.shields.io/packagist/dependency-v/luoyue/webman-mcp/php) ![SDK Version](https://img.shields.io/packagist/dependency-v/luoyue/webman-mcp/mcp%2Fsdk?label=sdk) ![Packagist Downloads](https://img.shields.io/packagist/dt/luoyue/webman-mcp) ![Packagist Stars](https://img.shields.io/packagist/stars/luoyue/webman-mcp)

这是一个Webman框架与官方MCP PHP SDK深度集成的插件，并在SDK基础上进行了扩展，可快速创建MCP服务器。

> 此插件依赖于官方的[MCP PHP SDK](https://github.com/modelcontextprotocol/php-sdk)，我们正在努力完善与SDK的兼容性。

## 特性

- 一键启动，安装后即可启动，同时支持配置复杂的功能。
- 一个项目支持多个MCP服务器，并按服务器名称隔离配置。
- 与Webman框架深度集成，HTTP支持路由模式和自定义进程模式。
- 自动注册MCP服务到主流IDE（VSCode、Cursor、通义灵码等）
- 支持 STDIO、Streamable HTTP 高性能传输
- 内置MCP命令行开发工具

## 安装

```bash
composer require luoyue/webman-mcp
```

### 环境要求

- PHP >= 8.1
- webman^2.1
- webman/cache^2.1
- webman/redis（可选）
- Swoole/Swow/Fiber协程（可选，提升SSE性能）
- monolog/monolog（可选，用于记录服务器日志）
- phpunit/phpunit（可选，用于无关传输的测试）

### 启动方式

```shell
# 启动 MCP STDIO 服务器, mcp为服务器名称，配置文件中定义
php webman mcp:server mcp

# 启动 MCP HTTP 服务器(分为两种，一种是嵌入到路由中，另一种是自定义进程)
php webman start
```

## 快速开始

### 1. 使用命令行工具创建模板代码（也可直接使用插件自带的配置）

```bash
# 创建文件后可根据模板代码实现逻辑
php webman mcp:make template
```

### 2. 配置客户端连接配置

打开app.php，修改`auto_register_client`配置为您常用的客户端。

```php
<?php

use Luoyue\WebmanMcp\Enum\McpClientRegisterEnum;

return [
    'enable' => true,
    // 自动注册MCP服务到ide中
    'auto_register_client' => McpClientRegisterEnum::CURSOR_IDE,
];
```

什么？没有您的客户端？我们非常欢迎您提交相关PR。

### 3. 测试您的服务器

```bash
# 使用 MCP Inspector 测试（需要node与npx）
php webman mcp:inspector mcp
```

## 内置工具

### 命令行工具

| 工具            |   参数    |         描述          |
|:--------------|:-------:|:-------------------:|
| mcp:server    | service |      启动MCP服务器       |
| mcp:list      |         |       MCP服务列表       |
| mcp:make      |  type   |    生成MCP配置或模板代码     |
| mcp:inspector | service | 启动MCP Inspector调试工具 |

示例：

```shell
## 查看定义的mcp服务列表以及配置信息
php webman mcp:list
```

## 如何正确记录错误日志

根据`2025-11-25`规范，STDIO传输允许将任何日志记录到stderr中且客户端可以捕获stderr并视为非致命错误，stdout则必须用于传输json-rpc消息。

|  日志模式  | STDIO传输 | Streamable HTTP传输 |
|:------:|:-------:|:-----------------:|
|  file  |    ✅    |         ✅         |
| stdout |    ❌    |         ✅         |
| stderr |    ✅    |         ✅         |

从以上表格中看出：

- 在开发环境中使用stderr很方便的将日在控制台中且不影响运行。
- 在生产环境中使用file记录日志可以将日志保存在磁盘中，方便后续维护。

配置monolog（必须是插件目录下的log.php）：

```php
<?php

return [
    //文件日志记录
    'mcp_file_log' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/mcp.log',
                    7, //$maxFiles
                    Monolog\Logger::NOTICE,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [null, 'Y-m-d H:i:s', true],
                ],
            ]
        ]
    ],
    // stderr日志记录
    'mcp_error_stderr' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\StreamHandler::class,
                'constructor' => [
                    STDERR, // stderr流
                    Monolog\Logger::NOTICE, // 设置NOTICE可减少不必要的调试信息
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [null, 'Y-m-d H:i:s', true],
                ],
            ]
        ]
    ]
];
```

然后我们可以在`mcp.php`中配置以下逻辑：

```php
return [
    'mcp' => [
        'logger' => config('app.debug', true) ? 'mcp_error_stdout' : 'mcp_file_log'
  ]
]
```

## 常见问题

### STDIO和Streamable HTTP是什么，与路由模式、进程模式有什么区别

`STDIO`和`Streamable HTTP`属于MCP中客户端与服务器的通信方式，`STDIO`通过**标准输入输出**进行通信，而`Streamable HTTP`则通过
**HTTP**进行通信。  
`路由模式`和`进程模式`则分别对应服务端的启动方式，路由模式下，MCP服务运行在`Webman`的**路由**中，进程模式下，MCP服务运行在单独的
**自定义进程**中。

### 我通过Streamable HTTP开发的MCP切换到STDIO时无法调用MCP工具

由于标准输入输出在读取时是**阻塞**的，因此无法使用`webman`
中的部分功能，如您有更好的解决方案，欢迎到此处讨论：[Discussions #3](https://github.com/lvluoyue/webman-mcp/discussions/3)

### 关于两种日志记录的区别

- 服务端日志：MCP执行过程种产生的日志。记录了错误信息及调试信息。生产环境可设置为`error`级别。
- 客户端日志：在服务端执行过程中服务端向客户端发送日志，使用方法参考
  [官方文档](https://github.com/modelcontextprotocol/php-sdk/blob/main/docs/client-communication.md)。

## 参考文档

**学习资料：**

- [MCP 元素](https://github.com/modelcontextprotocol/php-sdk/blob/main/docs/mcp-elements.md) - 创建工具、资源和提示
- [示例](https://github.com/modelcontextprotocol/php-sdk/blob/main/docs/examples.md) - 全面的示例演练

**外部资源：**

- [模型上下文协议文档](https://modelcontextprotocol.io)
- [模型上下文协议规范](https://modelcontextprotocol.io/specification/2025-11-25)
- [MCP服务器列表](https://github.com/modelcontextprotocol/servers)
- [MCP PHP SDK](https://github.com/modelcontextprotocol/php-sdk)

## 许可证

本项目采用 MIT 许可证 - 详情请见 [LICENSE](LICENSE) 文件。
