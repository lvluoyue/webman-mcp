<?php

namespace Luoyue\WebmanMcp\Enum;

enum McpClientRegisterEnum: string
{
    /** cursor编辑器(工作区安装) */
    case CURSOR_IDE = './.cursor/mcp.json';
    
    /** vscode编辑器(工作区安装) */
    case VSCODE_IDE = './.vscode/mcp.json';
    
    /** 通义灵码编辑器(工作区安装) */
    case LINGMA_IDE = '%APPDATA%/Lingma/SharedClientCache/lingma_mcp.json';
    
    /** 通义灵码插件(全局安装) */
    case LINGMA_PLUGIN = '%USERPROFILE%/.lingma/lingma_mcp.json';
    
    /** cline插件(全局安装) */
    case CLINE_PLUGIN = '%APPDATA%/Code/User/globalStorage/saoudrizwan.claude-dev/settings/cline_mcp_settings.json';

    public function getKey(): string
    {
        return match ($this) {
            self::VSCODE_IDE => 'servers',
            default => 'mcpServers',
        };
    }

    public function getPath(): string
    {
        $path = preg_replace_callback('/%?([^%]+)%?/', fn($matches) => getenv($matches[1]) ?: $matches[0], $this->value);
        if (str_starts_with($path, './')) {
            $path = base_path(substr($path, 2));
        }
        return $path;
    }
}
