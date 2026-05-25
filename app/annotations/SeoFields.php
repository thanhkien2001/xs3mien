<?php

namespace App\Annotations;

use Phalcon\Annotations\Annotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class SeoFields
{
    public $fields = [];
    
    public function __construct($values = [])
    {
        if (isset($values['fields'])) {
            $this->fields = $values['fields'];
        }
    }
}

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class SeoField
{
    public $name;
    public $type;
    public $default;
    public $required = false;
    public $options = [];
    
    public function __construct($values = [])
    {
        $this->name = $values['name'] ?? '';
        $this->type = $values['type'] ?? 'text';
        $this->default = $values['default'] ?? '';
        $this->required = $values['required'] ?? false;
        $this->options = $values['options'] ?? [];
    }
}
