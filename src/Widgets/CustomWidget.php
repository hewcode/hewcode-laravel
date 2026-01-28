<?php

namespace Hewcode\Hewcode\Widgets;

/**
 * Base class for custom widgets with React components.
 *
 * Custom widgets automatically map to React components at:
 * resources/js/hewcode/widgets/{ClassName}.jsx
 *
 * Example:
 * ```php
 * // app/Widgets/MyCustomWidget.php
 * class MyCustomWidget extends CustomWidget
 * {
 *     public function toData(): array
 *     {
 *         return array_merge(parent::toData(), [
 *             'customProp' => 'value',
 *         ]);
 *     }
 * }
 * ```
 *
 * And create the corresponding React component:
 * ```jsx
 * // resources/js/hewcode/widgets/MyCustomWidget.jsx
 * export default function MyCustomWidget({ customProp, seal }) {
 *     return <div>{customProp}</div>;
 * }
 * ```
 */
abstract class CustomWidget extends Widget
{
    public function getType(): string
    {
        // Return the class name without namespace
        $className = class_basename(static::class);

        return $className;
    }
}
