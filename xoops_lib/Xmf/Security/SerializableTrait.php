<?php

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

declare(strict_types=1);

namespace Xmf\Security;

/**
 * Trait providing serialization utilities for object properties
 *
 * @method void setVar(string $key, mixed $value)
 *
 * @category  Xmf\Security
 * @package   Xmf
 * @author    MAMBA <mambax7@gmail.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
trait SerializableTrait
{
    /**
     * Serialize a property for database storage
     *
     * Uses Serializer::toJson() for Format::JSON or Serializer::toPhp()
     * for Format::PHP. Other formats are not supported for serialization.
     *
     * @param mixed  $value  The value to serialize
     * @param string $format The target format (Format::JSON or Format::PHP)
     *
     * @return string The serialized representation
     *
     * @throws \InvalidArgumentException On unsupported format
     */
    protected function serializeProperty($value, string $format = Format::JSON): string
    {
        if ($format === Format::JSON) {
            return Serializer::toJson($value);
        }
        if ($format === Format::PHP) {
            return Serializer::toPhp($value);
        }

        throw new \InvalidArgumentException(
            sprintf('Unsupported serialization format "%s"', $format)
        );
    }

    /**
     * Deserialize a property from database
     *
     * Uses Serializer::tryFrom() with Format::AUTO to auto-detect format.
     *
     * @param string                    $data           The serialized data
     * @param mixed                     $default        Default value if deserialization fails
     * @param array<int, string>  $allowedClasses Whitelist of allowed class names
     *
     * @return mixed The deserialized value or default
     */
    protected function unserializeProperty(string $data, $default = null, array $allowedClasses = [])
    {
        return Serializer::tryFrom($data, $default, Format::AUTO, $allowedClasses);
    }

    /**
     * Serialize all marked properties
     *
     * Iterates getSerializableProperties() and uses serializeProperty()
     * to produce an associative array of serialized values.
     *
     * @return array<string, string> Map of property names to serialized values
     *
     * @throws \InvalidArgumentException On unsupported format in serializeProperty()
     */
    public function serializeProperties(): array
    {
        $serialized = [];

        foreach ($this->getSerializableProperties() as $property => $format) {
            if (property_exists($this, $property)) {
                try {
                    $serialized[$property] = $this->serializeProperty($this->$property, $format);
                } catch (\Error $e) {
                    // Typed property may not be initialized — skip it
                }
            }
        }

        return $serialized;
    }

    /**
     * Define which properties should be serialized
     *
     * Override in your class to specify properties and their formats.
     * Example: return ['forum_moderators' => Format::JSON];
     *
     * @return array<string, string> Map of property name to Format constant
     */
    protected function getSerializableProperties(): array
    {
        return [
            // 'property_name' => Format::JSON
        ];
    }

    /**
     * Migrate serialized data from old to new format
     *
     * Requires the using class to implement setVar() (e.g. XoopsObject).
     * Migration is automatically tracked via Serializer::setLegacyLogger()
     * when the legacy logger is configured.
     *
     * @param string $property Property name to migrate
     * @param string $oldData  Current serialized data
     *
     * @return bool True if migration was performed
     */
    public function migrateSerializedData(string $property, string $oldData): bool
    {
        $format = Serializer::detect($oldData);

        if ($format === Format::PHP || $format === Format::LEGACY) {
            try {
                $value = $this->unserializeProperty($oldData);
                $newData = $this->serializeProperty($value, Format::JSON);
                $this->setVar($property, $newData);

                return true;
            } catch (\Throwable $e) {
                \error_log(\sprintf(
                    'Serializer migration failed for property "%s": %s',
                    $property,
                    $e->getMessage()
                ));

                return false;
            }
        }

        return false;
    }
}
