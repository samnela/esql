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

namespace Soyuka\ESQL\Tests;

use Soyuka\ESQL\Bridge\Doctrine\ESQL;
use Soyuka\ESQL\Filter\FilterParser;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Car;
use Soyuka\ESQL\Tests\Fixtures\TestBundle\Entity\Model;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @psalm-suppress MissingDependency
 */
class FilterParserTest extends KernelTestCase
{
    /**
     * @dataProvider filters
     *
     * @param mixed $filter
     * @param mixed $result
     */
    public function testParseCarFilter($filter, $result): void
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();
        $registry = $container->get('doctrine');
        $esql = new ESQL($registry);
        $filterParser = new FilterParser($esql);
        ESQL::getAlias(Car::class);
        ESQL::getAlias(Model::class);

        $this->assertEquals($filterParser->parse($filter, Car::class), $result);
    }

    public function filters(): iterable
    {
        yield [
            'and(price.eq.100,sold.is.true)',
            ['car.price = :price_1 AND car.sold IS :sold_1', ['price_1' => '100', 'sold_1' => '1']],
        ];

        yield [
            'and(price.gt.100,price.gte.90,price.lt.90,price.lt.0,price.neq.0,price.eq.0)',
            ['car.price > :price_1 AND car.price >= :price_2 AND car.price < :price_3 AND car.price < :price_4 AND car.price != :price_5 AND car.price = :price_6', ['price_1' => '100', 'price_2' => '90', 'price_3' => '90', 'price_4' => '0', 'price_5' => '0', 'price_6' => '0']],
        ];

        yield [
            'or(name.like.*test*,sold.eq.true)',
            ['car.name LIKE :name_1 OR car.sold = :sold_1', ['name_1' => '%test%', 'sold_1' => '1']],
        ];

        yield [
            'and(sold.is.true,or(sold.is.true,price.gte.100))',
            ['car.sold IS :sold_1 OR (car.sold IS :sold_2 OR car.price >= :price_1)', ['sold_1' => '1', 'sold_2' => '1', 'price_1' => '100']],
        ];

        yield [
            'and(sold.is.true,or(sold.is.true,and(price.gte.100)))',
            ['car.sold IS :sold_1 OR (car.sold IS :sold_2 AND (car.price >= :price_1))', ['sold_1' => '1', 'sold_2' => '1', 'price_1' => '100']],
        ];

        yield [
            'and(sold.is.true,or(sold.is.true,and(price.gte.100)))',
            ['car.sold IS :sold_1 OR (car.sold IS :sold_2 AND (car.price >= :price_1))', ['sold_1' => '1', 'sold_2' => '1', 'price_1' => '100']],
        ];

        yield [
            'and(sold.not.is.true,price.not.eq.10)',
            ['car.sold IS NOT :sold_1 AND car.price != :price_1', ['sold_1' => '1', 'price_1' => '10']],
        ];
    }
}