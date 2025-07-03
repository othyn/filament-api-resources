# Filament API Resources

A Laravel package that enables Filament to work seamlessly with API-backed resources instead of traditional Eloquent models. This package provides all the necessary components to build Filament admin panels that consume REST APIs. The standard approach is to reach for the superb package known as [Sushi](https://usesushi.dev/), but I've found that a bit limiting when it came to supporting the rest of Filaments feature suite, as well as pagination being an awkward solve. So with that in mind, I created this package.

## Filament V4 Beta

**Exciting news:** Over the weekend, the new Filament V4 Beta was released with official support for ['Tables with custom data'](https://filamentphp.com/content/leandrocfe-filament-v4-beta-feature-overview#tables-with-custom-data), allowing API's to be used with "... supporting features like columns, sorting, searching, pagination, and actions."

Their [documentation](https://filamentphp.com/docs/4.x/tables/custom-data) has extensive information on how to implement custom data now officially, including detailed documentation and extensive examples of ['Using an external API as a table data source'](https://filamentphp.com/docs/4.x/tables/custom-data#using-an-external-api-as-a-table-data-source).

I was mid way through initial development of this package, but I can't imagine it will be useful for much longer. So with that said, I'm not going to persue this further- so be warned, the code is a bit rough and ready in places. Glad to see upstream support for this use case, well done to the Filament team as always!

## Features

- 🚀 **API-First**: Built specifically for API-backed resources
- 🔄 **Livewire Integration**: Custom synthesizer for proper Livewire state management
- 📄 **Pagination Support**: Automatic pagination handling for API responses
- 💾 **Caching**: Built-in response caching with configurable TTL
- 🛠 **Flexible**: Configurable response structure and headers
- 🎯 **Filament Native**: Works with all standard Filament features (tables, forms, actions, etc.)

## Installation

Install the package via Composer:

```bash
composer require othyn/filament-api-resources
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=filament-api-resources-config
```

## Configuration

Configure your API settings in `config/filament-api-resources.php`:

```php
return [
    'base_url' => env('FILAMENT_API_BASE_URL', 'https://api.example.com'),
    'default_headers' => [
        'Authorization' => 'Bearer ' . env('FILAMENT_API_TOKEN', ''),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ],
    // ... more configuration options
];
```

Add the following environment variables to your `.env` file:

```env
FILAMENT_API_BASE_URL=https://your-api.com/api
FILAMENT_API_TOKEN=your-api-token
```

## Usage

### 1. Create an API Model

Extend the `BaseApiModel` class to create your API-backed models:

```php
<?php

namespace App\Models;

use Othyn\FilamentApiResources\Models\BaseApiModel;

class User extends BaseApiModel
{
    protected static string $endpoint = '/users';

    protected $fillable = [
        'id',
        'name',
        'email',
        'created_at',
    ];

    protected function makeInstance(array $data): self
    {
        $user = new self();

        // In production, validate the API response data here
        // to ensure data integrity before creating the instance

        $user->fill([
            'id' => $data['id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'created_at' => $data['created_at'],
        ]);

        $user->exists = true;

        return $user;
    }

    public static function get(int|string $id, bool $forceCacheRefresh = false): ?self
    {
        $instance = new self();
        $response = $instance->fetchResource(
            params: ['id' => $id],
            cacheSeconds: 60,
            forceCacheRefresh: $forceCacheRefresh
        );

        if (empty($response['data'])) {
            return null;
        }

        return $instance->makeInstance($response['data']);
    }
}
```

### 2. Create Filament Resource Pages

Use the provided base classes for your Filament resource pages:

**List Page:**

```php
<?php

namespace App\Filament\Resources\UserResource\Pages;

use Othyn\FilamentApiResources\Resources\Pages\ListApiRecords;
use App\Filament\Resources\UserResource;

class ListUsers extends ListApiRecords
{
    protected static string $resource = UserResource::class;
}
```

**View Page:**

```php
<?php

namespace App\Filament\Resources\UserResource\Pages;

use Othyn\FilamentApiResources\Resources\Pages\ViewApiRecord;
use App\Filament\Resources\UserResource;

class ViewUser extends ViewApiRecord
{
    protected static string $resource = UserResource::class;
}
```

### 3. Create Your Filament Resource

Create a standard Filament resource that uses your API model:

> **Important:** When using API resources, you must add `->paginated()`, and optionally `->deferLoading()` to not block page loading on the API response, to your table configuration to ensure proper pagination and loading behavior. The pagination will automatically be forwarded to API calls.

```php
<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->paginated()
            ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }
}
```

## Advanced Usage

### Custom Response Structure

If your API uses a different response structure, you can customize it per model using dot notation:

```php
class User extends BaseApiModel
{
    protected static string $totalKey = 'meta.total';        // For APIs that return total in meta.total
    protected static string $resultsKey = 'response.users';  // For APIs that return data in response.users

    // ... rest of your model
}
```

### Custom Headers per Request

You can add custom headers for specific requests:

```php
protected function fetchResource(array $params = [], ?int $currentPage = null, ?int $cacheSeconds = null, bool $forceCacheRefresh = false): array
{
    return $this->getApiService()->fetch(
        endpoint: static::$endpoint,
        params: $params,
        currentPage: $currentPage,
        cacheSeconds: $cacheSeconds,
        forceCacheRefresh: $forceCacheRefresh,
        headers: ['X-Custom-Header' => 'value']
    );
}
```

### Refreshing Data

In your view pages, you can refresh data from the API manually, in the case that you've performed some form of Livewire action on the page and wish to refresh the page state and repaint:

```php
public function refreshData(): void
{
    $this->refreshRecord(forceCacheRefresh: true);

    $this->notify('Data refreshed successfully!');
}
```

## API Response Format

By default, the package expects your API to return responses in Laravel's standard paginated format:

```json
{
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "name": "John Doe",
                "email": "john@example.com"
            }
        ],
        "total": 100,
        "per_page": 15
    }
}
```

This matches Laravel's default API resource pagination format. You can customize the response structure keys in the configuration file if your API uses a different format.

## Pagination Query Parameters

When fetching paginated data, the package automatically sends the following query parameters to your API:

- `page` - The current page number (e.g., `?page=2`)
- `per_page` - The number of items per page (e.g., `?per_page=15`)

These parameter names can be customized in the configuration file:

```php
// config/filament-api-resources.php
'pagination_params' => [
    'page' => 'page',           // Change to 'p' if your API uses ?p=2
    'per_page' => 'per_page',   // Change to 'limit' if your API uses ?limit=15
],
```

## Error Handling

The package includes built-in error handling for API requests. Failed requests will throw exceptions with detailed error messages. You can catch and handle these in your application as needed.

### Debugging API Requests

When debugging API requests, Laravel's default behavior truncates request exception messages which can make it difficult to see the full API response. To get complete error details, you can disable request truncation in your `bootstrap/app.php` file:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;

return Application::configure(basePath: dirname(__DIR__))
    ->withExceptions(function (Exceptions $exceptions) {
        // Disable truncation completely for full error details
        $exceptions->dontTruncateRequestExceptions();

        // Or set a custom length (default is 100 characters)
        // $exceptions->truncateRequestExceptionsAt(260);
    })
    ->create();
```

This will ensure you see the complete API response in your logs and error messages, making debugging much easier.

## Caching

API responses are automatically cached based on your configuration. You can:

- Set default cache TTL in config
- Override cache TTL per request
- Force cache refresh when needed
- Disable caching by setting `cacheSeconds` to `null`

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).
