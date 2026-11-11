<?php
declare(strict_types=1);

namespace Core;

/**
 * Checks form input and collects one error message per field.
 *   $v = Validator::make($input)->required('title', 'Title')->maxLength('title', 'Title', 150);
 *   if ($v->fails()) { $errors = $v->errors(); }
 */
class Validator
{
    private array $errors = [];

    public function __construct(private array $data) {}

    public static function make(array $data): self
    {
        return new self($data);
    }

    private function value(string $field): string
    {
        $value = $this->data[$field] ?? '';
        return is_string($value) ? trim($value) : '';
    }

    public function required(string $field, string $label): self
    {
        if ($this->value($field) === '') {
            $this->addError($field, "$label is required.");
        }
        return $this;
    }

    public function maxLength(string $field, string $label, int $max): self
    {
        if (mb_strlen($this->value($field)) > $max) {
            $this->addError($field, "$label must be at most $max characters.");
        }
        return $this;
    }

    public function inList(string $field, string $label, array $allowed): self
    {
        if (!in_array($this->value($field), $allowed, true)) {
            $this->addError($field, "$label is not valid.");
        }
        return $this;
    }

    public function number(string $field, string $label, float $min, float $max, bool $required = true): self
    {
        $value = $this->value($field);
        if ($value === '') {
            if ($required) {
                $this->addError($field, "$label is required.");
            }
            return $this;
        }
        if (!is_numeric($value) || (float) $value < $min || (float) $value > $max) {
            $this->addError($field, "$label must be a number between $min and $max.");
        }
        return $this;
    }

    public function integer(string $field, string $label, int $min, int $max, bool $required = true): self
    {
        $value = $this->value($field);
        if ($value === '') {
            if ($required) {
                $this->addError($field, "$label is required.");
            }
            return $this;
        }
        $options = ['options' => ['min_range' => $min, 'max_range' => $max]];
        if (filter_var($value, FILTER_VALIDATE_INT, $options) === false) {
            $this->addError($field, "$label must be a whole number between $min and $max.");
        }
        return $this;
    }

    /** Keeps the first error for each field. */
    public function addError(string $field, string $message): self
    {
        $this->errors[$field] ??= $message;
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
