<?php

namespace Hewcode\Hewcode\Mcp;

use Hewcode\Hewcode\Mcp\Tools\CreateFormTool;
use Hewcode\Hewcode\Mcp\Tools\CreateListingTool;
use Hewcode\Hewcode\Mcp\Tools\CreateResourceTool;
use Hewcode\Hewcode\Mcp\Tools\ScaffoldCrudTool;
use Laravel\Mcp\Server;

class HewcodeServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Hewcode';

    /**
     * The MCP server's version.
     */
    protected string $version = '1.0.1';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = 'Hewcode MCP server provides tools for scaffolding Laravel applications using the Hewcode framework. Use these tools to generate Listings, Forms, Resources, and Panels following Hewcode best practices. Always ensure proper authorization checks are in place when generating code. Use scaffold_crud for complete features from scratch.';

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        ScaffoldCrudTool::class,
        CreateResourceTool::class,
        CreateListingTool::class,
        CreateFormTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
