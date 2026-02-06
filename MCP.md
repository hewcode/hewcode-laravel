# Hewcode MCP Server

The Hewcode MCP (Model Context Protocol) server provides AI-powered tools for scaffolding Laravel applications using Hewcode components.

## What is MCP?

MCP is a protocol that allows AI assistants (like Claude Code) to interact with external tools and services. The Hewcode MCP server exposes scaffolding tools that help AI agents generate Hewcode components following best practices.

## Setup

The MCP server is automatically set up when you install Hewcode.

### Automatic Setup (Recommended)

When you run `php artisan hew:install`, the installer automatically creates `routes/ai.php` with the Hewcode MCP server registered.

### Manual Setup

If you need to set it up manually, create `routes/ai.php`:

```php
<?php

use Hewcode\Hewcode\Mcp\HewcodeServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('hewcode', HewcodeServer::class);
```

### 2. Using with Claude Code

Configure your `.claude/config.json` file to use the Hewcode MCP server:

```json
{
  "mcpServers": {
    "hewcode": {
      "command": "php",
      "args": ["artisan", "mcp:start", "hewcode"]
    }
  }
}
```

## Available Tools

### scaffold_crud ⭐ RECOMMENDED

Scaffolds complete CRUD functionality including migration, model, and Hewcode resource. **This is the highest-level tool** that creates everything needed for a working feature from scratch.

**Example Usage:**
```
Create a complete product management system with fields: name (string, required),
slug (string, unique), description (text, nullable), price (decimal, required),
stock (integer, default 0), status (string, required), is_featured (boolean),
and published_at (datetime, nullable). Add to admin panel.
```

**Generated Output:**
- Creates migration file with all fields and constraints
- Creates `app/Models/Product.php` with fillable fields and type casts
- Creates `app/Hewcode/Resources/ProductResource.php`
- Creates `app/Hewcode/Listings/ProductListing.php`
- Creates `app/Hewcode/Forms/ProductForm.php`
- Intelligently configures listing (searchable/sortable columns, filters)
- Intelligently configures form (proper field types, validation)

**Parameters:**
- `model_name` (required): Model name (singular, e.g., "Product")
- `table_fields` (required): Array of field definitions
  - `name`: Field name
  - `type`: Laravel migration type (string, text, integer, decimal, boolean, date, datetime, json, etc.)
  - `nullable`: Boolean (default: false)
  - `default`: Default value
  - `unique`: Boolean (default: false)
  - `index`: Boolean (default: false)
- `panels`: Array of panel names (default: ["admin"])
- `generate_migration`: Boolean (default: true)
- `generate_model`: Boolean (default: true)
- `generate_resource`: Boolean (default: true)

**Smart Features:**
- Auto-detects searchable columns (string, text types)
- Auto-detects sortable columns (string, text, numeric types)
- Creates filters for status-like fields
- Maps types to form fields (text → textarea, boolean → select, datetime → date_time_picker)
- Generates proper validation (required, maxLength, numeric, email)
- Creates model casts based on field types

---

### create_listing

Creates a Hewcode Listing definition class with columns, filters, and actions.

**Example Usage:**
```
Create a UserListing with columns for id (sortable), name (sortable, searchable),
email (searchable), and created_at (sortable). Include edit and delete actions.
```

**Generated Output:**
- Creates `app/Hewcode/Listings/UserListing.php`
- Includes proper authorization checks
- Follows Hewcode best practices
- Auto-generates locale-friendly labels

**Parameters:**
- `name` (required): Listing class name (e.g., "UserListing")
- `model` (required): Model class name (e.g., "User")
- `columns`: Array of column definitions with name, type, sortable, searchable
- `filters`: Array of filter names
- `actions`: Array of action names (edit, delete, restore)
- `relationships`: Array of relationships to eager load
- `generate`: Boolean to auto-generate from table schema

---

### create_form

Creates a Hewcode Form definition class with fields, validation, and submission logic.

**Example Usage:**
```
Create a PostForm with title (text input, required, max:255), content (textarea, required),
status (select from PostStatus enum), category_id (relationship select with search),
and published_at (date time picker).
```

**Generated Output:**
- Creates `app/Hewcode/Forms/PostForm.php`
- Includes all field types with proper validation
- Handles relationship selects automatically
- Follows Hewcode best practices

**Parameters:**
- `name` (required): Form class name (e.g., "PostForm")
- `model` (required): Model class name (e.g., "Post")
- `fields`: Array of field definitions
  - `name`: Field name
  - `type`: text_input, textarea, select, date_time_picker, file_upload
  - `validation`: Array of Laravel validation rules
  - `options`: Field-specific options (enum, relationship, rows, multiple, searchable, preload)
- `generate`: Boolean to auto-generate from table schema

---

### create_resource

Creates a complete Hewcode Resource with listing and form methods, including index, create, and edit pages.

**Example Usage:**
```
Create a ProductResource for the admin panel with name and price columns in the listing,
and a form with name (required, max:255) and price (required, numeric) fields.
Generate separate reusable Listing and Form definitions.
```

**Generated Output:**
- Creates `app/Hewcode/Resources/ProductResource.php`
- Optionally creates `app/Hewcode/Listings/ProductListing.php`
- Optionally creates `app/Hewcode/Forms/ProductForm.php`
- Includes all CRUD pages (index, create, edit)
- Auto-registers in specified panels

**Parameters:**
- `name` (required): Resource class name (e.g., "ProductResource")
- `model` (required): Model class name (e.g., "Product")
- `panels`: Array of panel names (default: ["admin"])
- `listing_config`: Configuration for the listing
  - `columns`: Array of column definitions
  - `filters`: Array of filter names
  - `actions`: Array of action names
  - `relationships`: Array of relationships to eager load
- `form_config`: Configuration for the form
  - `fields`: Array of field definitions
- `generate_definitions`: Boolean to create separate Listing and Form classes (default: false)
- `generate`: Boolean to auto-generate from table schema

**Modes:**
- **Inline mode** (`generate_definitions: false`): Listing and form logic defined directly in resource
- **Separate mode** (`generate_definitions: true`): Creates reusable ListingDefinition and FormDefinition classes

---

## Testing

You can test the MCP server using Laravel's built-in inspector:

```bash
php artisan mcp:inspector hewcode
```

Or programmatically in your tests:

```php
use Hewcode\Hewcode\Mcp\HewcodeServer;
use Hewcode\Hewcode\Mcp\Tools\CreateListingTool;

$response = HewcodeServer::tool(CreateListingTool::class, [
    'name' => 'ProductListing',
    'model' => 'Product',
    'columns' => [
        ['name' => 'name', 'searchable' => true],
        ['name' => 'price', 'sortable' => true],
    ],
]);

$response->assertOk();
```

## Optional Tools (Nice-to-Have)

The following tools could be added for advanced use cases:

- `create_panel_page` - Generate custom panel pages (for dashboards, reports, etc.)
- `create_action` - Generate custom actions (for specialized operations)
- `create_widget` - Generate dashboard widgets (for analytics, stats)

**Note:** The current toolkit (scaffold_crud, create_resource, create_listing, create_form) covers all essential use cases for building Laravel applications.

## Platform Integration

This MCP server is designed to work seamlessly with the Hewcode platform, where it can be shared across multiple user containers. Each container's Claude Code instance connects to the server and generates Hewcode components in the user's project context.
