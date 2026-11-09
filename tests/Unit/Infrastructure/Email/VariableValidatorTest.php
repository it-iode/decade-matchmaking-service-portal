<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Email;

use App\Infrastructure\Email\Exceptions\MissingVariableException;
use App\Infrastructure\Email\Exceptions\ValidationException;
use App\Infrastructure\Email\Services\VariableValidator;
use Tests\TestCase;

class VariableValidatorTest extends TestCase
{
    private VariableValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new VariableValidator;
    }

    /**
     * Test validation with all required variables present
     */
    public function test_validates_required_variables(): void
    {
        // Arrange
        $variables = [
            'user_name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $rules = [
            'user_name' => 'required|string',
            'email' => 'required|email',
        ];

        // Act
        $result = $this->validator->validate($variables, $rules, 'test.event');

        // Assert
        $this->assertArrayHasKey('user_name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertEquals('John Doe', $result['user_name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    /**
     * Test validation with missing required variable
     */
    public function test_throws_exception_for_missing_required_variable(): void
    {
        // Arrange
        $variables = [
            'user_name' => 'John Doe',
        ];

        $rules = [
            'user_name' => 'required|string',
            'email' => 'required|email',
        ];

        // Assert
        $this->expectException(MissingVariableException::class);
        $this->expectExceptionMessage('Required variables missing for template "test.event": email');

        // Act
        $this->validator->validate($variables, $rules, 'test.event');
    }

    /**
     * Test validation with optional variables
     */
    public function test_handles_optional_variables(): void
    {
        // Arrange
        $variables = [
            'user_name' => 'John Doe',
        ];

        $rules = [
            'user_name' => 'required|string',
            'company' => 'optional|string',
        ];

        // Act
        $result = $this->validator->validate($variables, $rules, 'test.event');

        // Assert
        $this->assertArrayHasKey('user_name', $result);
        $this->assertEquals('John Doe', $result['user_name']);
        // Optional variable not present, which is fine
    }

    /**
     * Test validation with invalid email format
     */
    public function test_throws_exception_for_invalid_email(): void
    {
        // Arrange
        $variables = [
            'email' => 'not-an-email',
        ];

        $rules = [
            'email' => 'required|email',
        ];

        // Assert
        $this->expectException(ValidationException::class);

        // Act
        $this->validator->validate($variables, $rules, 'test.event');
    }

    /**
     * Test validation with invalid URL format
     */
    public function test_throws_exception_for_invalid_url(): void
    {
        // Arrange
        $variables = [
            'website' => 'not-a-url',
        ];

        $rules = [
            'website' => 'required|url',
        ];

        // Assert
        $this->expectException(ValidationException::class);

        // Act
        $this->validator->validate($variables, $rules, 'test.event');
    }

    /**
     * Test validation with integer type
     */
    public function test_validates_integer_type(): void
    {
        // Arrange
        $variables = [
            'user_id' => 123,
            'age' => '25', // String that can be converted to int
        ];

        $rules = [
            'user_id' => 'required|integer',
            'age' => 'required|integer',
        ];

        // Act
        $result = $this->validator->validate($variables, $rules, 'test.event');

        // Assert
        $this->assertEquals(123, $result['user_id']);
        $this->assertEquals('25', $result['age']); // Laravel validator doesn't type-cast
    }

    /**
     * Test validation with date format
     */
    public function test_validates_date_format(): void
    {
        // Arrange
        $variables = [
            'created_at' => '2024-01-15',
            'expires_at' => '2024-12-31 23:59:59',
        ];

        $rules = [
            'created_at' => 'required|date',
            'expires_at' => 'required|datetime',
        ];

        // Act
        $result = $this->validator->validate($variables, $rules, 'test.event');

        // Assert
        $this->assertEquals('2024-01-15', $result['created_at']);
        $this->assertEquals('2024-12-31 23:59:59', $result['expires_at']);
    }

    /**
     * Test sanitization removes HTML tags and escapes special characters
     */
    public function test_sanitizes_html_tags(): void
    {
        // Arrange
        $variables = [
            'message' => '<script>alert("XSS")</script>Hello World',
            'name' => 'John <b>Doe</b>',
        ];

        $rules = [
            'message' => 'required|string',
            'name' => 'required|string',
        ];

        // Act
        $result = $this->validator->validate($variables, $rules, 'test.event');

        // Assert
        // HTML tags are stripped, special characters are escaped
        $this->assertEquals('alert(&quot;XSS&quot;)Hello World', $result['message']);
        $this->assertEquals('John Doe', $result['name']);

        // Verify no script tags remain
        $this->assertStringNotContainsString('<script>', $result['message']);
        $this->assertStringNotContainsString('<b>', $result['name']);
    }

    /**
     * Test merge with defaults
     */
    public function test_merges_with_defaults(): void
    {
        // Arrange
        $variables = [
            'name' => 'John',
            'age' => 30,
        ];

        $defaults = [
            'name' => 'Default Name',
            'country' => 'USA',
            'age' => 25,
        ];

        // Act
        $result = $this->validator->mergeWithDefaults($variables, $defaults);

        // Assert
        $this->assertEquals('John', $result['name']); // User value takes precedence
        $this->assertEquals(30, $result['age']); // User value takes precedence
        $this->assertEquals('USA', $result['country']); // Default value used
    }

    /**
     * Test validation with array type
     */
    public function test_validates_array_type(): void
    {
        // Arrange
        $variables = [
            'tags' => ['php', 'laravel', 'testing'],
            'meta' => ['key' => 'value'],
        ];

        $rules = [
            'tags' => 'required|array',
            'meta' => 'required|array',
        ];

        // Act
        $result = $this->validator->validate($variables, $rules, 'test.event');

        // Assert
        $this->assertIsArray($result['tags']);
        $this->assertCount(3, $result['tags']);
        $this->assertIsArray($result['meta']);
        $this->assertEquals('value', $result['meta']['key']);
    }
}
