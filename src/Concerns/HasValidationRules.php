<?php

namespace Hewcode\Hewcode\Concerns;

use Closure;
use Illuminate\Validation\Rule;

trait HasValidationRules
{
    protected bool $required = false;
    protected array $rules = [];

    public function required(bool $required = true): static
    {
        $this->required = $required;
        $this->rules[] = 'required';

        return $this;
    }

    public function nullable(): static
    {
        $this->rules[] = 'nullable';

        return $this;
    }

    public function email(array $validations = []): static
    {
        if (empty($validations)) {
            $this->rules[] = 'email';
        } else {
            $this->rules[] = 'email:' . implode(',', $validations);
        }

        return $this;
    }

    public function min(int $value): static
    {
        $this->rules[] = "min:$value";

        return $this;
    }

    public function max(int $value): static
    {
        $this->rules[] = "max:$value";

        return $this;
    }

    public function maxLength(int $value): static
    {
        $this->rules[] = "max:$value";

        return $this;
    }

    public function between(int $min, int $max): static
    {
        $this->rules[] = "between:$min,$max";

        return $this;
    }

    public function numeric(): static
    {
        $this->rules[] = 'numeric';

        return $this;
    }

    public function integer(): static
    {
        $this->rules[] = 'integer';

        return $this;
    }

    public function string(): static
    {
        $this->rules[] = 'string';

        return $this;
    }

    public function boolean(): static
    {
        $this->rules[] = 'boolean';

        return $this;
    }

    public function date(): static
    {
        $this->rules[] = 'date';

        return $this;
    }

    public function url(): static
    {
        $this->rules[] = 'url';

        return $this;
    }

    public function confirmed(): static
    {
        $this->rules[] = 'confirmed';

        return $this;
    }

    public function unique(string $table, ?string $column = null, bool $ignore = false, ?Closure $callback = null): static
    {
        $rule = Rule::unique($table, $column);

        if ($ignore) {
            $record = $this->getRecord();
            if ($record) {
                $rule->ignore($record->id);
            }
        }

        if ($callback) {
            $callback($rule);
        }

        $this->rules[] = $rule;
        return $this;
    }

    public function in(array $values): static
    {
        $this->rules[] = 'in:' . implode(',', $values);

        return $this;
    }

    public function regex(string $pattern): static
    {
        $this->rules[] = "regex:$pattern";

        return $this;
    }

    public function alpha(): static
    {
        $this->rules[] = 'alpha';

        return $this;
    }

    public function alphaNum(): static
    {
        $this->rules[] = 'alpha_num';

        return $this;
    }

    public function alphaDash(): static
    {
        $this->rules[] = 'alpha_dash';

        return $this;
    }

    public function accepted(): static
    {
        $this->rules[] = 'accepted';

        return $this;
    }

    public function activeUrl(): static
    {
        $this->rules[] = 'active_url';

        return $this;
    }

    public function after(string $date): static
    {
        $this->rules[] = "after:$date";

        return $this;
    }

    public function afterOrEqual(string $date): static
    {
        $this->rules[] = "after_or_equal:$date";

        return $this;
    }

    public function before(string $date): static
    {
        $this->rules[] = "before:$date";

        return $this;
    }

    public function beforeOrEqual(string $date): static
    {
        $this->rules[] = "before_or_equal:$date";

        return $this;
    }

    public function array(): static
    {
        $this->rules[] = 'array';

        return $this;
    }

    public function ascii(): static
    {
        $this->rules[] = 'ascii';

        return $this;
    }

    public function dateFormat(string $format): static
    {
        $this->rules[] = "date_format:$format";

        return $this;
    }

    public function dateEquals(string $date): static
    {
        $this->rules[] = "date_equals:$date";

        return $this;
    }

    public function decimal(int $min, ?int $max = null): static
    {
        $rule = "decimal:$min";
        if ($max !== null) {
            $rule .= ",$max";
        }
        $this->rules[] = $rule;

        return $this;
    }

    public function different(string $field): static
    {
        $this->rules[] = "different:$field";

        return $this;
    }

    public function digits(int $value): static
    {
        $this->rules[] = "digits:$value";

        return $this;
    }

    public function digitsBetween(int $min, int $max): static
    {
        $this->rules[] = "digits_between:$min,$max";

        return $this;
    }

    public function distinct(): static
    {
        $this->rules[] = 'distinct';

        return $this;
    }

    public function doesntStartWith(array $values): static
    {
        $this->rules[] = 'doesnt_start_with:' . implode(',', $values);

        return $this;
    }

    public function doesntEndWith(array $values): static
    {
        $this->rules[] = 'doesnt_end_with:' . implode(',', $values);

        return $this;
    }

    public function endsWith(array $values): static
    {
        $this->rules[] = 'ends_with:' . implode(',', $values);

        return $this;
    }

    public function enum(string $class): static
    {
        $this->rules[] = "enum:$class";

        return $this;
    }

    public function exists(string $table, ?string $column = null, ?Closure $callback = null): static
    {
        $rule = Rule::exists($table, $column);

        if ($callback) {
            $callback($rule);
        }

        $this->rules[] = $rule;
        return $this;
    }

    public function filled(): static
    {
        $this->rules[] = 'filled';

        return $this;
    }

    public function gt(string $field): static
    {
        $this->rules[] = "gt:$field";

        return $this;
    }

    public function gte(string $field): static
    {
        $this->rules[] = "gte:$field";

        return $this;
    }

    public function hexColor(): static
    {
        $this->rules[] = 'hex_color';

        return $this;
    }

    public function inArray(string $anotherField): static
    {
        $this->rules[] = "in_array:$anotherField";

        return $this;
    }

    public function ip(): static
    {
        $this->rules[] = 'ip';

        return $this;
    }

    public function ipv4(): static
    {
        $this->rules[] = 'ipv4';

        return $this;
    }

    public function ipv6(): static
    {
        $this->rules[] = 'ipv6';

        return $this;
    }

    public function json(): static
    {
        $this->rules[] = 'json';

        return $this;
    }

    public function lt(string $field): static
    {
        $this->rules[] = "lt:$field";

        return $this;
    }

    public function lte(string $field): static
    {
        $this->rules[] = "lte:$field";

        return $this;
    }

    public function lowercase(): static
    {
        $this->rules[] = 'lowercase';

        return $this;
    }

    public function macAddress(): static
    {
        $this->rules[] = 'mac_address';

        return $this;
    }

    public function maxDigits(int $value): static
    {
        $this->rules[] = "max_digits:$value";

        return $this;
    }

    public function minDigits(int $value): static
    {
        $this->rules[] = "min_digits:$value";

        return $this;
    }

    public function multipleOf(int $value): static
    {
        $this->rules[] = "multiple_of:$value";

        return $this;
    }

    public function notIn(array $values): static
    {
        $this->rules[] = 'not_in:' . implode(',', $values);

        return $this;
    }

    public function notRegex(string $pattern): static
    {
        $this->rules[] = "not_regex:$pattern";

        return $this;
    }

    public function present(): static
    {
        $this->rules[] = 'present';

        return $this;
    }

    public function prohibited(): static
    {
        $this->rules[] = 'prohibited';

        return $this;
    }

    public function prohibitedIf(string $anotherField, mixed $value): static
    {
        $this->rules[] = "prohibited_if:$anotherField,$value";

        return $this;
    }

    public function prohibitedUnless(string $anotherField, mixed $value): static
    {
        $this->rules[] = "prohibited_unless:$anotherField,$value";

        return $this;
    }

    public function prohibits(array $fields): static
    {
        $this->rules[] = 'prohibits:' . implode(',', $fields);

        return $this;
    }

    public function requiredIf(string $anotherField, mixed $value): static
    {
        $this->rules[] = "required_if:$anotherField,$value";

        return $this;
    }

    public function requiredUnless(string $anotherField, mixed $value): static
    {
        $this->rules[] = "required_unless:$anotherField,$value";

        return $this;
    }

    public function requiredWith(array $fields): static
    {
        $this->rules[] = 'required_with:' . implode(',', $fields);

        return $this;
    }

    public function requiredWithAll(array $fields): static
    {
        $this->rules[] = 'required_with_all:' . implode(',', $fields);

        return $this;
    }

    public function requiredWithout(array $fields): static
    {
        $this->rules[] = 'required_without:' . implode(',', $fields);

        return $this;
    }

    public function requiredWithoutAll(array $fields): static
    {
        $this->rules[] = 'required_without_all:' . implode(',', $fields);

        return $this;
    }

    public function same(string $field): static
    {
        $this->rules[] = "same:$field";

        return $this;
    }

    public function size(int $value): static
    {
        $this->rules[] = "size:$value";

        return $this;
    }

    public function startsWith(array $values): static
    {
        $this->rules[] = 'starts_with:' . implode(',', $values);

        return $this;
    }

    public function timezone(): static
    {
        $this->rules[] = 'timezone';

        return $this;
    }

    public function ulid(): static
    {
        $this->rules[] = 'ulid';

        return $this;
    }

    public function uppercase(): static
    {
        $this->rules[] = 'uppercase';

        return $this;
    }

    public function uuid(?int $version = null): static
    {
        if ($version) {
            $this->rules[] = "uuid:$version";
        } else {
            $this->rules[] = 'uuid';
        }

        return $this;
    }

    public function bail(): static
    {
        $this->rules[] = 'bail';
        return $this;
    }

    public function excludeIf(string $field, mixed $value): static
    {
        $this->rules[] = "exclude_if:$field,$value";
        return $this;
    }

    public function excludeUnless(string $field, mixed $value): static
    {
        $this->rules[] = "exclude_unless:$field,$value";
        return $this;
    }

    public function excludeWith(string $field): static
    {
        $this->rules[] = "exclude_with:$field";
        return $this;
    }

    public function excludeWithout(string $field): static
    {
        $this->rules[] = "exclude_without:$field";
        return $this;
    }

    public function rules(array|string $rules): static
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $this->rules = array_merge($this->rules, (array) $rules);

        return $this;
    }

    public function rule(callable|object|string $rule): static
    {
        $this->rules[] = $rule;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
