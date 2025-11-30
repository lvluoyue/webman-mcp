# webman-mcp

![Packagist Version](https://img.shields.io/packagist/v/luoyue/webman-mcp) ![Packagist License](https://img.shields.io/packagist/l/luoyue/webman-mcp) ![PHP Version](https://img.shields.io/packagist/dependency-v/luoyue/webman-mcp/php) ![SDK Version](https://img.shields.io/packagist/dependency-v/luoyue/webman-mcp/mcp%2Fsdk?label=sdk) ![Packagist Downloads](https://img.shields.io/packagist/dt/luoyue/webman-mcp) ![Packagist Stars](https://img.shields.io/packagist/stars/luoyue/webman-mcp)

这是一个Webman框架与官方MCP PHP SDK深度集成的插件，并在SDK基础上进行了扩展，可快速创建MCP服务器。

> 此插件依赖于官方的[MCP PHP SDK](https://github.com/modelcontextprotocol/php-sdk)，我们正在努力完善与SDK的兼容性。

> SDK文档与此插件无较大语法差异，所以文档仅展示插件功能和sdk的差异。

## 特性

- 一键启动，安装后即可启动，同时支持配置复杂的功能。
- 一个项目支持多个MCP服务器，并按服务器名称隔离配置。
- 与Webman框架深度集成，HTTP支持路由模式和自定义进程模式。
- 自动注册MCP服务到主流IDE（VSCode、Cursor、通义灵码等）。
- 支持 STDIO、Streamable HTTP 高性能传输。
- 支持协程与非协程，从而提高了在sse场景下高性能传输。
- 内置MCP命令行开发工具。

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

## 启动方式

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

[//]: # (### MCP开发工具)

## 日志记录

### 发送客户端日志

请参考[官方文档](https://github.com/modelcontextprotocol/php-sdk/blob/main/docs/client-communication.md)

### 记录服务器错误日志

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
        'logger' => config('app.debug', true) ? 'mcp_error_stderr' : 'mcp_file_log'
  ]
]
```

## 与webman的兼容问题

### McpTool注解如何将Controller结合使用

由于webman控制器和mcp消息处理机制差异，无法完美兼容，需要稍加改动即可适配。具体操作如下：

1. mcp执行`controller`行为与配置`app.controller_reuse=true`相同，实例化后放入容器中复用。
2. 无法使用webman^2.1的参数绑定和Request注入，orm注入等，但可使用助手函数`request()`和`response()`获取请求响应对象。
3. 判断是否是mcp执行环境可使用`Context::get('McpServerRequest', false);`返回true时为mcp环境。
4. 可根据第三条方法为不同的环境返回不同的响应。

示例：

```php
<?php

use support\Context;
use Workerman\Protocols\Http\Response;

class McpController
{

    /**
     * tool示例代码
     *
     * @return array 返回包含会话ID的状态信息
     */
    #[McpTool(name: 'example_tool')]
    public function exampleTool(): Response|array
    {
        $result = [
            'status' => 'ok',
            'params' => request()->all(),
        ];
        if (Context::get('McpServerRequest'), false) {
            return  $result;
        }
        return response($result);
    }
}

```

### 协程环境与非协程环境的限制

此插件已将SDK中SSE轮询阻塞函数`usleep`替换为workerman自带的非阻塞方法`Timer::sleep()`，结果如下：

- 协程环境下：阻塞部分变为非阻塞，原有业务代码不受影响。
- 非协程环境下：相关代码依然为阻塞状态，严重影响业务代码，阻塞周期为0.1秒为单位，可使用自定义进程与webman进程分离，从而达到互不干扰。

### STDIO传输和phpunit单元测试的限制

STDIO传输与phpunit单元测试中具有相同的缺点：

- 在linux/macos系统中此功能可能不受影响，在windows系统中，由于平台限制，无法将其设置为非阻塞。
- 根据上面的问题，在webman中无法使用依赖workerman环境中的函数：定时器、定时任务、协程、http-client等。

相关讨论：[Discussions #3](https://github.com/lvluoyue/webman-mcp/discussions/3)

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
