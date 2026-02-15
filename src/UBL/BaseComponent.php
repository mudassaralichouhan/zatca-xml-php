<?php

namespace ZATCA\UBL;

use DOMDocument;

class BaseComponent
{
    public array $elements;
    public array $attributes;
    public string $value;
    public string $name;
    public ?int $index;

    public function __construct(
        array  $elements = [],
        array  $attributes = [],
        string $value = '',
        string $name = '',
        ?int   $index = null
    ) {
        $this->elements = $elements;
        $this->attributes = $attributes;
        $this->value = $value;
        $this->name = $name;
        $this->index = $index;
    }

    public static function build(
        array  $elements = [],
        array  $attributes = [],
        string $value = '',
        string $name = '',
        ?int   $index = null
    ): ?self {
        if (empty($elements) && empty($attributes) && $value === '') {
            return null;
        }

        return new self($elements, $attributes, $value, $name, $index);
    }

    public function get(string $name): ?BaseComponent
    {
        foreach ($this->elements as $el) {
            if ($el->name === $name) {
                return $el;
            }
        }
        return null;
    }

    public function dig(string $key, string ...$args)
    {
        $value = $this->get($key);
        if (count($args) === 0 || $value === null) {
            return $value;
        }
        return $value->dig(...$args);
    }

    public function toXml(): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElement('root');
        $doc->appendChild($root);

        $this->buildXml($doc, $root);

        return $doc->saveXML();
    }

    public function buildXml(DOMDocument $doc, \DOMNode $parent): void
    {
        $el = $doc->createElement($this->name);

        foreach ($this->attributes as $attr => $val) {
            $el->setAttribute($attr, (string)$val);
        }

        if (!empty($this->elements)) {
            foreach ($this->elements as $child) {
                $child?->buildXml($doc, $el);
            }
        } elseif ($this->value !== '') {
            $el->appendChild($doc->createTextNode($this->value));
        }

        $parent->appendChild($el);
    }

    public function generateXml(
        bool $canonicalized = false,
        int  $spaces = 2,
        bool $applyInvoiceHacks = false,
        bool $removeRootXmlTag = false
    ): string {
        $builder = new Builder(element: $this);
        return $builder->build(
            canonicalized: $canonicalized,
            spaces: $spaces,
            applyInvoiceHacks: $applyInvoiceHacks,
            removeRootXmlTag: $removeRootXmlTag,
        );
    }
}
