# webman-mcp

![Packagist Version](https://img.shields.io/packagist/v/luoyue/webman-mcp)
![Packagist License](https://img.shields.io/packagist/l/luoyue/webman-mcp)
![Packagist Dependency Version](https://img.shields.io/packagist/dependency-v/luoyue/webman-mcp/php)
![Packagist Downloads](https://img.shields.io/packagist/dt/luoyue/webman-mcp)
![Packagist Stars](https://img.shields.io/packagist/stars/luoyue/webman-mcp)

这是一个Webman框架与官方MCP PHP SDK深度集成的插件，并在SDK基础上进行了扩展，可快速创建MCP服务器。

> [!IMPORTANT]
> 此插件依赖于官方的[MCP PHP SDK](https://github.com/modelcontextprotocol/php-sdk)，并且在官方SDK发布第一个正式版本之前，此插件将始终标记为实验版本。

## 特性
- [x] 一键启动，安装后即可启动，同时支持配置复杂的功能。
- [x] 一个项目支持多个MCP服务器，并按服务器名称隔离配置。
- [x] 与Webman框架深度集成，HTTP支持路由模式和自定义进程模式。
- [x] 自动注册MCP服务到主流IDE（VSCode、Cursor、通义灵码等）
- [x] 支持 STDIO、Streamable HTTP 高性能传输
- [ ] 内置MCP开发工具

## 安装
开始前请确保您已了解MCP相关知识，以便后续理解这些操作。如需了解请[点击此处查看](#参考文档)。

### 环境要求

- PHP >= 8.1
- webman^2.1
- webman/cache^2.1
- redis（可选）
- Swoole/Swow/Fiber协程（可选，提升SSE性能）

```bash
composer require luoyue/webman-mcp
```

## 启动方式
```shell
# 启动 MCP STDIO 服务器, mcp为服务器名称，配置文件中定义
php webman mcp:server mcp

# 启动 MCP HTTP 服务器(分为两种，一种是嵌入到路由中，另一种是自定义进程)
php webman start
```

## 快速开始

### 1. 创建一个具有 MCP 功能的类

```php
<?php

namespace App\mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\McpResource;

class CalculatorElements
{
    /**
     * 将两个数字相加。
     * 
     * @param int $a 第一个数字
     * @param int $b 第二个数字
     * @return int 两个数字的和
     */
    #[McpTool]
    public function add(int $a, int $b): int
    {
        return $a + $b;
    }

    /**
     * 执行基本算术运算。
     */
    #[McpTool(name: 'calculate')]
    public function calculate(float $a, float $b, string $operation): float|string
    {
        return match($operation) {
            'add' => $a + $b,
            'subtract' => $a - $b,
            'multiply' => $a * $b,
            'divide' => $b != 0 ? $a / $b : '错误：除零',
            default => '错误：未知操作'
        };
    }

    #[McpResource(
        uri: 'config://calculator/settings',
        name: 'calculator_config',
        mimeType: 'application/json'
    )]
    public function getSettings(): array
    {
        return ['precision' => 2, 'allow_negative' => true];
    }
}
```

### 2. 手动配置 MCP 客户端
自动配置可直接跳过此步骤。

```json
{
    "mcpServers": {
        "php-calculator": {
            "command": "php",
            "args": ["webman", "mcp:server", "mcp"]
        }
    }
}
```

### 3. 测试您的服务器

```bash
# 使用 MCP Inspector 测试（需要node环境）
npx @modelcontextprotocol/inspector php webman mcp:server mcp

# 您的 AI 助手现在可以调用：
# - add: 将两个整数相加
# - calculate: 执行算术运算
# - 读取 config://calculator/settings 资源
```

## 常见问题

### STDIO和Streamable HTTP是什么，与路由模式、进程模式有什么区别
`STDIO`和`Streamable HTTP`属于MCP中客户端与服务器的通信方式，`STDIO`通过**标准输入输出**进行通信，而`Streamable HTTP`则通过**HTTP**进行通信。  
而`路由模式`和`进程模式`则分别对应服务端的启动方式，路由模式下，MCP服务运行在`Webman`的**路由**中，进程模式下，MCP服务运行在单独的**自定义进程**中。

### 我通过Streamable HTTP开发的MCP切换到STDIO时无法调用MCP工具
由于标准输入输出在读取时是**阻塞**的，因此无法使用`webman`中的部分功能，如您有更好的解决方案，欢迎到此处讨论：[Discussions #3](https://github.com/lvluoyue/webman-mcp/discussions/3)

### 关于两种日志记录的区别
1. 服务端日志：MCP执行过程种产生的日志。记录了错误信息及调试信息。生产环境可设置为`error`级别。
3. 客户端日志：在服务端执行过程中服务端向客户端发送日志，使用方法参考[官方文档](https://github.com/modelcontextprotocol/php-sdk/blob/main/docs/client-communication.md)。

## 参考文档

**学习资料：**
- [MCP 元素](https://github.com/modelcontextprotocol/php-sdk/blob/main/docs/mcp-elements.md) - 创建工具、资源和提示
- [示例](https://github.com/modelcontextprotocol/php-sdk/blob/main/docs/examples.md) - 全面的示例演练

**外部资源：**
- [模型上下文协议文档](https://modelcontextprotocol.io)
- [模型上下文协议规范](https://spec.modelcontextprotocol.io)
- [官方支持的服务器](https://github.com/modelcontextprotocol/servers)
- [MCP PHP SDK](https://github.com/modelcontextprotocol/php-sdk)

## 许可证

本项目采用 MIT 许可证 - 详情请见 [LICENSE](LICENSE) 文件。
