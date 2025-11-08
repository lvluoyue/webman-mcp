<?php

use Luoyue\WebmanMcp\Enum\McpClientRegisterEnum;

return [
    'enable' => true,
    // 自动注册MCP服务到ide中
    'auto_register_client' => McpClientRegisterEnum::CURSOR_IDE,
];