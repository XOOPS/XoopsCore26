<?php
/**
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xoops\Form;

/**
 * Factory to build form elements
 *
 * @category  ElementFactory
 * @package   Xoops\Form
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class ElementFactory
{
    const CLASS_KEY = ':class';
    const FORM_KEY = ':form';

    /** @var ContainerInterface  */
    protected $container = null;

    /**
     * Create the specified Form\Element
     *
     * @param array $specification associative array of attributes and controls defining an Element
     *
     * @return Element
     *
     * @throws \DomainException
     */
    public function create($specification)
    {
        $this->validateSpec($specification);
        return new $specification[self::CLASS_KEY]($specification);
    }

    /**
     * Validate the specification, adding container if needed
     *
     * @param array $specification associative array of attributes and controls defining an Element
     *
     * @return Element
     *
     * @throws \DomainException
     */
    protected function validateSpec(&$specification)
    {
        if (!isset($specification[self::CLASS_KEY])) {
            throw new \DomainException('Specification CLASS_KEY required.');
        }
        $elementClass = $specification[self::CLASS_KEY];
        if (false === strpos($elementClass, '\\')) {
            $elementClass = '\Xoops\Form\\' . $elementClass;
        }

        if (!class_exists($elementClass)) {
            throw new \DomainException('Unknown element class: ' . $specification[self::CLASS_KEY]);
        }

        if (!is_a($elementClass, '\Xoops\Form\Element', true)) {
            throw new \DomainException('Not an Element subclass: ' . $specification[self::CLASS_KEY]);
        }

        $specification[self::CLASS_KEY] = $elementClass;
        if (!isset($specification[self::FORM_KEY]) && isset($this->container)) {
            $specification[self::FORM_KEY] = $this->container;
        }
    }

    /**
     * Set a the container to be applied to new elements
     *
     * @param ContainerInterface $container form or tray to contain generated elements
     *
     * @return void
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
