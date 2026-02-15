<?php

namespace ZATCA;

use Exception;

class Tags
{
    private array $tags;

    public function __construct(array $tags)
    {
        // Coerce all keys except 'timestamp' to string
        $keysToCoerce = array_diff(array_keys($tags), ['timestamp']);
        $stringified = [];
        foreach ($tags as $key => $value) {
            if (in_array($key, $keysToCoerce, true)) {
                $stringified[$key] = (string) $value;
            } else {
                $stringified[$key] = $value;
            }
        }

        // Validate against schema
        $schemaResult = TagsSchema::call($stringified);
        if ($schemaResult->failure()) {
            $errors = $schemaResult->errors(true)->toArray();
            throw new Exception("Parsing tags failed due to:\n" . print_r($errors, true));
        }

        // Ensure timestamp is string
        $stringified['timestamp'] = (string) $tags['timestamp'];

        // Build Tag objects and sort by ID
        $built = [];
        foreach ($stringified as $key => $value) {
            $built[] = new Tag(key: $key, value: $value);
        }
        usort($built, fn (Tag $a, Tag $b): int => $a->id <=> $b->id);

        $this->tags = $built;
    }

    public function get(int $index): Tag
    {
        return $this->tags[$index];
    }

    public function toBase64(): string
    {
        return base64_encode($this->toTlv());
    }

    public function toHexTlv(): string
    {
        return bin2hex($this->toTlv());
    }

    public function toTlv(): string
    {
        $tlv = '';
        foreach ($this->tags as $tag) {
            $tlv .= $tag->toTlv();
        }
        return $tlv;
    }
}
