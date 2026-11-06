# Request Service

## Overview

The `RequestService` is a simplified, unified service that handles all request-related operations. It automatically manages both JSON and normalized data storage internally, providing a clean API for controllers.

## Features

- ✅ **Single Service Class** - No complex architecture
- ✅ **Automatic Data Handling** - Works with both JSON and normalized data
- ✅ **Backward Compatible** - Existing code continues to work
- ✅ **Easy to Test** - Simple, focused unit tests
- ✅ **Performance Optimized** - Uses normalized data when available

## Usage

### Controller Integration

```php
class OcdRequestController extends Controller
{
    public function __construct(private RequestService $service)
    {
    }

    public function store(StoreOcdRequest $request)
    {
        $ocdRequest = $this->service->storeRequest(
            $request->user(), 
            $request->validated()
        );
        
        return response()->json([
            'message' => 'Request submitted successfully',
            'request_data' => $ocdRequest->attributesToArray()
        ], 201);
    }
}
```

### Key Methods

| Method | Purpose | Returns |
|--------|---------|---------|
| `storeRequest()` | Create/update request | OCDRequest |
| `saveDraft()` | Save as draft | OCDRequest |
| `getUserRequests()` | Get user's requests | Collection |
| `getPublicRequests()` | Get public requests | Collection |
| `findRequest()` | Find with auth | OCDRequest \| null |
| `updateRequestStatus()` | Update status | array |
| `deleteRequest()` | Delete request | bool |
| `searchRequests()` | Search with filters | Collection |
| `getRequestStats()` | User statistics | array |
| `getAnalytics()` | System analytics | array |

## Data Storage Strategy

The service automatically handles data storage:

1. **JSON Storage** - Always stores data in `request_data` field for backward compatibility
2. **Normalized Storage** - If normalized tables exist, also stores structured data
3. **JSON Arrays** - Subthemes, support types, and target audiences stored as JSON arrays in separate fields
4. **Automatic Sync** - Keeps all formats in sync automatically

### JSON Array Fields

The `request_details` table now includes JSON array fields for better performance:

- `subthemes` - Array of subtheme codes (e.g., `['ocean_health', 'sustainable_fisheries']`)
- `support_types` - Array of support type codes (e.g., `['technical_support', 'capacity_building']`)
- `target_audience` - Array of target audience codes (e.g., `['researchers', 'policy_makers']`)

This provides:
- **Better Performance** - Direct JSON queries instead of joins
- **Simpler Queries** - No need for pivot table joins
- **Flexible Storage** - Easy to add/remove items
- **Backward Compatibility** - Still maintains relationship tables

## Benefits

### For Developers
- **Simple API** - One service, clear methods
- **No Migration Complexity** - Works with existing data
- **Easy Testing** - Focused unit tests
- **Clear Responsibilities** - Each method has one job

### For Performance
- **Optimized Queries** - Uses normalized data when available
- **Full-Text Search** - When normalized tables exist
- **Proper Indexing** - Database indexes on structured data
- **Eager Loading** - Optimized relationship loading

### For Maintenance
- **Single Point of Truth** - All request logic in one place
- **Easy Debugging** - Clear error messages and logging
- **Consistent API** - Same interface for all operations
- **Future-Proof** - Easy to extend and modify

## Testing

```bash
# Run the service tests
php artisan test tests/Unit/RequestServiceTest.php

# Run all tests
php artisan test
```

## Migration from Old Services

The service automatically handles the transition:

1. **Existing JSON Data** - Continues to work unchanged
2. **New Requests** - Automatically use normalized storage if available
3. **Search** - Automatically uses best available method
4. **Analytics** - Provides enhanced analytics when normalized data exists

## Configuration

No complex configuration needed! The service automatically detects available database tables and uses the best approach for each operation.

## Error Handling

The service provides clear error messages:

```php
try {
    $request = $this->service->storeRequest($user, $data);
} catch (Exception $e) {
    // Clear error message: "Failed to store request"
    Log::error($e->getMessage());
}
```

## Performance Tips

1. **Use Eager Loading** - Service automatically loads relationships
2. **Batch Operations** - Use collections for multiple requests
3. **Caching** - Consider caching for frequently accessed data
4. **Indexes** - Ensure database indexes are in place

## Future Enhancements

The simplified architecture makes it easy to add features:

- **Caching Layer** - Add Redis/Memcached caching
- **Event System** - Add Laravel events for request lifecycle
- **API Versioning** - Easy to version the service API
- **Microservices** - Can be extracted to separate service if needed 