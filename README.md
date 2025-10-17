# webman-mcp

这是一个Webman框架与官方MCP PHP SDK深度集成的插件，并在SDK基础上进行了扩展，可快速创建MCP服务器。

> [!IMPORTANT]
> 此插件依赖于官方的[MCP PHP SDK](https://github.com/modelcontextprotocol/php-sdk)，并且在官方SDK发布第一个正式版本之前，此插件将始终标记为实验版本。

## 特性
- [x] 一键启动，安装后即可启动，同时支持配置复杂的功能。
- [x] 一个项目支持多个MCP服务器，并按服务器名称隔离配置。
- [x] 与Webman框架深度集成，HTTP支持路由模式和自定义进程模式。
- [x] 自动注册MCP服务到主流IDE（VSCode、Cursor、通义灵码等）
- [x] 支持 STDIO、HTTP 传输
- [x] 适配官方SDK的所有功能
- [ ] STDIO模式下支持非阻塞IO，并支持workerman进程环境下的特定功能
- [ ] 内置MCP开发工具

## 安装

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
# 使用 MCP Inspector 测试
npx @modelcontextprotocol/inspector php webman mcp:server mcp

# 您的 AI 助手现在可以调用：
# - add: 将两个整数相加
# - calculate: 执行算术运算
# - 读取 config://calculator/settings 资源
```

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
