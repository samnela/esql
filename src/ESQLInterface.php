<?php

/*
 * This file is part of the ESQL project.
 *
 * (c) Antoine Bluchet <soyuka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Soyuka\ESQL;

interface ESQLInterface
{
    /**
     * Retrieves columns for a given resource.
     */
    public function columns(?array $fields = null, string $glue = ', '): string;

    /**
     * Retrieves a column for a given resource.
     */
    public function column(string $fieldName): ?string;

    /**
     * Retrieves identifiers predicate, for example id = :id.
     */
    public function identifier(): string;

    /**
     * Retrieves join predicate, for example car.model_id = model.id.
     */
    public function join(string $relationClass): string;

    /**
     * Retrieves identifiers predicate, for example foo = :foo.
     * When no fields are provided it will output every columns.
     */
    public function predicates(?array $fields = null, string $glue = ', '): string;

    /**
     * Normalize this sql value according to the field type.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function toSQLValue(string $fieldName, $value);

    /**
     * Map the array data to the class.
     *
     * If we had generics we'd type this to $this->class
     *
     * @return mixed
     */
    public function map(array $data);

    /**
     * Retrieves a list of binded parameters.
     * more a helper for persistence not used.
     */
    public function parameters(array $bindings): string;

    /**
     * Get closures to ease HEREDOC calls.
     *
     * @param object|string $objectOrClass
     */
    public function __invoke($objectOrClass): self;

    /**
     * Get the class alias.
     *
     * @param object|string $objectOrClass
     */
    public static function getAlias($objectOrClass): string;

    /**
     * Get the class matching a given alias.
     */
    public static function getClass(string $alias): string;
}
