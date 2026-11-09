<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Request;

use App\Domains\Request\Exceptions\RequestAuthorizationException;
use App\Domains\Request\Exceptions\RequestException;
use App\Domains\Request\Exceptions\RequestNotFoundException;
use App\Domains\Request\Exceptions\RequestStatusException;
use App\Domains\Request\Exceptions\RequestStorageException;
use App\Domains\Request\Exceptions\RequestValidationException;
use Exception;
use Tests\TestCase;

class RequestExceptionTest extends TestCase
{
    public function test_base_request_exception_has_default_status_code(): void
    {
        $exception = new RequestException('Test message');

        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function test_base_request_exception_accepts_custom_status_code(): void
    {
        $exception = new RequestException('Test message', 418);

        $this->assertEquals(418, $exception->getStatusCode());
    }

    public function test_base_request_exception_accepts_previous_exception(): void
    {
        $previous = new Exception('Previous error');
        $exception = new RequestException('Test message', 500, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_request_not_found_exception_for_id(): void
    {
        $exception = RequestNotFoundException::forId(123);

        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertEquals('Request with ID 123 not found.', $exception->getMessage());
    }

    public function test_request_not_found_exception_for_update(): void
    {
        $exception = RequestNotFoundException::forUpdate(456);

        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertStringContainsString('Cannot update', $exception->getMessage());
        $this->assertStringContainsString('456', $exception->getMessage());
    }

    public function test_request_not_found_exception_for_deletion(): void
    {
        $exception = RequestNotFoundException::forDeletion(789);

        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertStringContainsString('Cannot delete', $exception->getMessage());
        $this->assertStringContainsString('789', $exception->getMessage());
    }

    public function test_request_not_found_exception_for_status_change(): void
    {
        $exception = RequestNotFoundException::forStatusChange(321);

        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertStringContainsString('Cannot change status', $exception->getMessage());
        $this->assertStringContainsString('321', $exception->getMessage());
    }

    public function test_request_authorization_exception_for_view(): void
    {
        $exception = RequestAuthorizationException::forView(123, 456);

        $this->assertEquals(403, $exception->getStatusCode());
        $this->assertStringContainsString('User 456', $exception->getMessage());
        $this->assertStringContainsString('view request 123', $exception->getMessage());
    }

    public function test_request_authorization_exception_for_update(): void
    {
        $exception = RequestAuthorizationException::forUpdate(123, 456);

        $this->assertEquals(403, $exception->getStatusCode());
        $this->assertStringContainsString('update request 123', $exception->getMessage());
    }

    public function test_request_authorization_exception_for_deletion(): void
    {
        $exception = RequestAuthorizationException::forDeletion(123, 456);

        $this->assertEquals(403, $exception->getStatusCode());
        $this->assertStringContainsString('delete request 123', $exception->getMessage());
    }

    public function test_request_authorization_exception_for_status_change(): void
    {
        $exception = RequestAuthorizationException::forStatusChange(123, 456);

        $this->assertEquals(403, $exception->getStatusCode());
        $this->assertStringContainsString('change status', $exception->getMessage());
    }

    public function test_request_authorization_exception_for_action(): void
    {
        $exception = RequestAuthorizationException::forAction('publish', 456);

        $this->assertEquals(403, $exception->getStatusCode());
        $this->assertStringContainsString('publish', $exception->getMessage());
        $this->assertStringContainsString('User 456', $exception->getMessage());
    }

    public function test_request_storage_exception_failed_to_create(): void
    {
        $exception = RequestStorageException::failedToCreate();

        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertStringContainsString('Failed to create', $exception->getMessage());
    }

    public function test_request_storage_exception_failed_to_create_with_previous(): void
    {
        $previous = new Exception('Database error');
        $exception = RequestStorageException::failedToCreate($previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_request_storage_exception_failed_to_update(): void
    {
        $exception = RequestStorageException::failedToUpdate(123);

        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertStringContainsString('Failed to update', $exception->getMessage());
        $this->assertStringContainsString('123', $exception->getMessage());
    }

    public function test_request_storage_exception_failed_to_delete(): void
    {
        $exception = RequestStorageException::failedToDelete(456);

        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertStringContainsString('Failed to delete', $exception->getMessage());
        $this->assertStringContainsString('456', $exception->getMessage());
    }

    public function test_request_storage_exception_failed_to_update_status(): void
    {
        $exception = RequestStorageException::failedToUpdateStatus(123, 2);

        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertStringContainsString('Failed to update status', $exception->getMessage());
        $this->assertStringContainsString('123', $exception->getMessage());
        $this->assertStringContainsString('2', $exception->getMessage());
    }

    public function test_request_storage_exception_transaction_failed(): void
    {
        $exception = RequestStorageException::transactionFailed('update request');

        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertStringContainsString('transaction failed', $exception->getMessage());
        $this->assertStringContainsString('update request', $exception->getMessage());
    }

    public function test_request_status_exception_invalid_status_code(): void
    {
        $exception = RequestStatusException::invalidStatusCode(999);

        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertStringContainsString('Invalid', $exception->getMessage());
        $this->assertStringContainsString('999', $exception->getMessage());
    }

    public function test_request_status_exception_illegal_transition(): void
    {
        $exception = RequestStatusException::illegalTransition(123, 1, 5);

        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertStringContainsString('Illegal status transition', $exception->getMessage());
        $this->assertStringContainsString('123', $exception->getMessage());
        $this->assertStringContainsString('1', $exception->getMessage());
        $this->assertStringContainsString('5', $exception->getMessage());
    }

    public function test_request_status_exception_missing_status(): void
    {
        $exception = RequestStatusException::missingStatus(123);

        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertStringContainsString('no status', $exception->getMessage());
    }

    public function test_request_status_exception_update_failed(): void
    {
        $exception = RequestStatusException::updateFailed(123, 'Invalid transition');

        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertStringContainsString('Failed to update status', $exception->getMessage());
        $this->assertStringContainsString('Invalid transition', $exception->getMessage());
    }

    public function test_request_validation_exception_invalid_field(): void
    {
        $exception = RequestValidationException::invalidField('email', 'must be a valid email');

        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertStringContainsString('email', $exception->getMessage());
        $this->assertStringContainsString('must be a valid email', $exception->getMessage());
    }

    public function test_request_validation_exception_missing_required_field(): void
    {
        $exception = RequestValidationException::missingRequiredField('title');

        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertStringContainsString('Missing required', $exception->getMessage());
        $this->assertStringContainsString('title', $exception->getMessage());
    }

    public function test_request_validation_exception_invalid_mode(): void
    {
        $exception = RequestValidationException::invalidMode('invalid');

        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertStringContainsString('Invalid mode', $exception->getMessage());
        $this->assertStringContainsString('invalid', $exception->getMessage());
        $this->assertStringContainsString('create', $exception->getMessage());
        $this->assertStringContainsString('update', $exception->getMessage());
    }

    public function test_request_validation_exception_business_rule_violation(): void
    {
        $exception = RequestValidationException::businessRuleViolation('Cannot submit draft request');

        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertStringContainsString('Business rule violation', $exception->getMessage());
        $this->assertStringContainsString('Cannot submit draft', $exception->getMessage());
    }

    public function test_request_validation_exception_failed(): void
    {
        $exception = RequestValidationException::failed('Data is invalid');

        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertStringContainsString('Validation failed', $exception->getMessage());
        $this->assertStringContainsString('Data is invalid', $exception->getMessage());
    }

    public function test_request_validation_exception_invalid_format(): void
    {
        $exception = RequestValidationException::invalidFormat('date', 'YYYY-MM-DD');

        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertStringContainsString('Invalid format', $exception->getMessage());
        $this->assertStringContainsString('date', $exception->getMessage());
        $this->assertStringContainsString('YYYY-MM-DD', $exception->getMessage());
    }
}
