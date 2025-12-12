<?php

namespace Luoyue\WebmanMcp\Command;

use Luoyue\WebmanMcp\McpServerManager;
use Mcp\Schema\Enum\ProtocolVersion;
use support\Container;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('mcp:make', 'Create MCP service or template')]
final class McpMakeCommand extends Command
{

    public function __invoke(
        InputInterface  $input,
        OutputInterface $output,
        #[Argument('type name', suggestedValues: ['config', 'template'])]
        string          $type
    ): int
    {
        $style = new SymfonyStyle($input, $output);
        switch ($type) {
            case 'config':
                return $this->makeConfig($style);
            case 'template':
                return $this->makeTemplate($style);
            default:
                $style->error('Please specify a type name');
                return Command::INVALID;
        }
    }

    private function makeConfig(SymfonyStyle $style): int
    {
        $style->title('MCP Service Configuration Generator');
        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        $servers = iterator_to_array($mcpServerManager->getServiceNames());
        $questions = [
            'service' => [
                'question' => 'Please enter service name',
                'regex' => '/^[a-z_\x80-\xff][a-z0-9_\x80-\xff]*$/i',
                'validator' => function ($answer) use ($style, $servers) {
                    if (!in_array($answer, $servers)) {
                        return true;
                    }
                    $style->error('Service name already exists. Please choose another one.');
                    return false;
                }
            ],
            'version' => [
                'question' => 'Please enter version',
                'default' => '1.0.0',
                'regex' => '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9A-Za-z-][0-9A-Za-z-]*)(?:\.(?:0|[1-9A-Za-z-][0-9A-Za-z-]*))*))?(?:\+([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?$/'
            ],
            'protocol_version' => [
                'question' => 'Please choice protocol version',
                'choice' => ProtocolVersion::class,
                'default' => ProtocolVersion::V2025_06_18->value,
            ],
            'description' => [
                'question' => 'Please enter description',
                'default' => 'MCP Service description',
                'regex' => '/^.*$/'
            ],
            'instructions' => [
                'question' => 'Please enter instructions',
                'default' => 'MCP Service instructions',
                'regex' => '/^.*$/'
            ],
            'pagination_limit' => [
                'question' => 'Please enter pagination limit',
                'default' => 50,
                'regex' => '/^[1-9][0-9]*$/'
            ],
            'logger' => [
                'question' => 'Please choice logger',
                'choice' => ['', ...array_keys(config(McpServerManager::PLUGIN_REWFIX . 'log', []))],
                'default' => '',
            ],
        ];

        $questions = QuestionHelper::handleQuestions($questions, $style);

        $template = <<<EOF
            '{$questions['service']}' => [
                // MCP功能配置
                'configure' => function (Builder \$server) {
                    // 设置服务信息
                    \$server->setServerInfo('{$questions['service']}', '{$questions['version']}', '{$questions['description']}');
                    // 设置协议版本
                    \$server->setProtocolVersion({$questions['protocol_version']});
                    // 设置使用说明
                    \$server->setInstructions('{$questions['instructions']}');
                    // 设置分页大小
                    \$server->setPaginationLimit({$questions['pagination_limit']});
                    //设置需要开启的功能
                    \$server->setCapabilities(new ServerCapabilities(
                        tools: true,
                        toolsListChanged: false,
                        resources: true,
                        resourcesSubscribe: false,
                        resourcesListChanged: false,
                        prompts: true,
                        promptsListChanged: false,
                        logging: false,
                        completions: true,
                        experimental: null,
                    ));
                    // 添加开发环境工具，仅debug模式下启用
                    config('app.debug') && \$server->addLoader(new DevelopmentMcpLoader);
                },
                // 服务日志，对应插件下的log配置文件
                'logger' => '{$questions['logger']}',
                // 服务注册配置
                'discover' => [
                    // 注解扫描路径
                    'scan_dirs' => [
                        'app/mcp',
                    ],
                    // 排除扫描路径
                    'exclude_dirs' => [
                    ],
                    // 缓存扫描结果，cache.php中的缓存配置名称，对于webman常驻内存框架无提升并且无法及时清理缓存，建议关闭。
                    'cache' => null,
                ],
                // session设置
                'session' => [
                    'store' => '', // 对应cache.php中的缓存配置名称, null为使用默认的内存缓存
                    'prefix' => 'mcp-',
                    'ttl' => 86400,
                ],
                'transport' => [
                    'stdio' => [
                        'enable' => true,
                    ],
                    'streamable_http' => [
                        // mcp端点
                        'endpoint' => '/mcp',
                        // 额外响应头，可配置CORS跨域
                        'headers' => [
        
                        ],
                        // 启用后将mcp端点注入到您的路由中
                        'router' => [
                            'enable' => true
                        ],
                        // 额外的自定义进程配置（与process.php配置相同）使用port代替listen
                        'process' => [
                            'enable' => false,
                            'port' => 8080,
                            'count' => 1,
                            'eventloop' => ''
                        ]
                    ]
                ]
            ]
        EOF;

        $file = config_path('plugin/luoyue/webman-mcp/mcp.php');
        $content = file_get_contents($file);

        $returnPos = strrpos($content, '];');
        if ($returnPos === false) {
            $style->error("Invalid configuration file format: missing closing bracket");
            return Command::FAILURE;
        }

        $before = rtrim(substr($content, 0, $returnPos));
        $before .= str_ends_with($before, ',') ? '' : ',';
        $after = substr($content, $returnPos);
        $newContent = $before . PHP_EOL . $template . PHP_EOL . $after;

        if (file_put_contents($file, $newContent) === false) {
            $style->error("Failed to write configuration to file: {$file}");
            return Command::FAILURE;
        }

        $style->success("Service '{$questions['service']}' successfully added to configuration file.");
        return Command::SUCCESS;
    }

    private function makeTemplate(SymfonyStyle $style): int
    {
        $style->title('MCP Service Template Generator');
        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        $servers = iterator_to_array($mcpServerManager->getServiceNames());
        $questions = [
            'service' => [
                'question' => 'Please choice service name',
                'choice' => $servers,
            ],
            'file_name' => [
                'question' => 'Please enter file name',
                'default' => 'example',
                'regex' => '/^[a-z_][a-z0-9_.-]*(\/[a-z0-9_.-]+)*$/i'
            ],
            'generator_type' => [
                'question' => 'Please enter generator type',
                'multi_select' => true,
                'choice' => ['mcp-tool', 'mcp-resource', 'mcp-resource-template', 'mcp-prompt'],
            ]
        ];

        $questions = QuestionHelper::handleQuestions($questions, $style);
        $config = $mcpServerManager->getServiceConfig($questions['service']);
        $questions += QuestionHelper::handleQuestions([
            'save_dir' => [
                'question' => 'Please enter save dir',
                'choice' => $config['discover']['scan_dirs'],
                'default' => $config['discover']['scan_dirs'][0] ?? null
            ]
        ], $style);

        $templates = [
            'mcp-tool' => <<<MCP_TOOL
                /**
                 * tool示例代码
                 *
                 * @param ClientGateway \$client 客户端网关实例
                 * @param Session \$_session 会话实例
                 * @return array 返回包含会话ID的状态信息
                 */
                #[McpTool(name: 'example_tool')]
                public function exampleTool(ClientGateway \$client): array
                {
                    \$client->log(LoggingLevel::Debug, 'example_tool called');
                    return [
                        'status' => 'ok',
                        'result' => 'hello world'
                    ];
                }
            MCP_TOOL,
            'mcp-resource' => <<<MCP_RESOURCE
                /**
                 * resource示例代码
                 *
                 * @return array app信息
                 */
                #[McpResource(uri: 'config://app')]
                public function exampleResource(): array
                {
                    return [
                        'app_name' => 'demo',
                        'php_version' => '8.1'
                    ];
                }
            MCP_RESOURCE,
            'mcp-resource-template' => <<<MCP_RESOURCE_TEMPLATE
                /**
                 * resource template示例代码
                 *
                 * @param ClientGateway \$client 客户端网关实例
                 * @param Session \$_session 会话实例
                 * @return array 返回包含会话ID的状态信息
                 */
                #[McpResourceTemplate(uriTemplate: 'user://{userId}/profile')]
                public function exampleResourceTemplate(
                    #[CompletionProvider(values: ['101', '102', '103'])]
                    string \$userId
                ): array
                {
                    \$uesrs =  [
                        '101' => ['name' => 'Alice', 'email' => 'alice@example.com', 'role' => 'admin'],
                        '102' => ['name' => 'Bob', 'email' => 'bob@example.com', 'role' => 'user'],
                        '103' => ['name' => 'Charlie', 'email' => 'charlie@example.com', 'role' => 'user'],
                    ];
                    if (!isset(\$users[\$userId])) {
                        throw new ResourceReadException("User not found for ID: {\$userId}");
                    }
            
                    return \$users[\$userId];
                }
            MCP_RESOURCE_TEMPLATE,
            'mcp-prompt' => <<<MCP_PROMPT
                /**
                 * prompt示例代码
                 *
                 * @param ClientGateway \$client 客户端网关实例
                 * @param Session \$_session 会话实例
                 * @return array 返回包含会话ID的状态信息
                 */
                #[McpPrompt(name: 'example_prompt')]
                public function generateBio(
                    #[CompletionProvider(provider: UserIdCompletionProvider::class)]
                    string \$userId,
                    string \$tone = 'professional',
                ): array {
                    \$uesrs =  [
                        '101' => ['name' => 'Alice', 'email' => 'alice@example.com', 'role' => 'admin'],
                        '102' => ['name' => 'Bob', 'email' => 'bob@example.com', 'role' => 'user'],
                        '103' => ['name' => 'Charlie', 'email' => 'charlie@example.com', 'role' => 'user'],
                    ];
                    if (!isset(\$users[\$userId])) {
                        throw new PromptGetException("User not found for bio prompt: {\$userId}");
                    }
                    \$user = \$users[\$userId];
            
                    return [
                        ['role' => 'user', 'content' => "Write a short, {\$tone} biography for {\$user['name']} (Role: {\$user['role']}, Email: {\$user['email']}). Highlight their role within the system."],
                    ];
                }
            MCP_PROMPT,
        ];

        $useClass = [
            'mcp-tool' => 'use Mcp\Capability\Attribute\McpTool;',
            'mcp-resource' => 'use Mcp\Capability\Attribute\McpResource;',
            'mcp-resource-template' => 'use Mcp\Capability\Attribute\McpResourceTemplate;',
            'mcp-prompt' => 'use Mcp\Capability\Attribute\McpPrompt;',
        ];

        $example = array_filter($templates, fn($type) => in_array($type, $questions['generator_type']), ARRAY_FILTER_USE_KEY);
        $example = implode(PHP_EOL, $example);

        $useClass = array_filter($useClass, fn($type) => in_array($type, $questions['generator_type']), ARRAY_FILTER_USE_KEY);
        $useClass = implode(PHP_EOL, $useClass);

        $namespace = str_replace('/', '\\', $questions['save_dir']);

        $template = <<<CODE
        <?php
        
        namespace {$namespace};
        
        use Mcp\Schema\Enum\LoggingLevel;
        use Mcp\Server\ClientGateway;
        {$useClass}
        
        class {$questions['file_name']}
        {
        {$example}
        }
        CODE;

        $file = base_path($questions['save_dir']) . DIRECTORY_SEPARATOR . $questions['file_name'] . '.php';
        file_put_contents($file, $template);

        return Command::SUCCESS;
    }
}