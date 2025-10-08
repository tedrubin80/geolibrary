<?php

declare(strict_types=1);

namespace GEOOptimizer\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use GEOOptimizer\Cache\MemoryCache;

class MemoryCacheTest extends TestCase
{
    private MemoryCache $cache;

    protected function setUp(): void
    {
        $this->cache = new MemoryCache();
    }

    public function testSetAndGet(): void
    {
        $key = 'test_key';
        $value = 'test_value';

        $this->assertTrue($this->cache->set($key, $value));
        $this->assertEquals($value, $this->cache->get($key));
    }

    public function testGetWithDefault(): void
    {
        $this->assertEquals('default', $this->cache->get('non_existent', 'default'));
    }

    public function testDelete(): void
    {
        $key = 'test_key';
        $value = 'test_value';

        $this->cache->set($key, $value);
        $this->assertTrue($this->cache->has($key));

        $this->assertTrue($this->cache->delete($key));
        $this->assertFalse($this->cache->has($key));
    }

    public function testClear(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');

        $this->assertTrue($this->cache->clear());

        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
    }

    public function testGetMultiple(): void
    {
        $values = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3'
        ];

        foreach ($values as $key => $value) {
            $this->cache->set($key, $value);
        }

        $retrieved = $this->cache->getMultiple(array_keys($values));

        foreach ($retrieved as $key => $value) {
            $this->assertEquals($values[$key], $value);
        }
    }

    public function testSetMultiple(): void
    {
        $values = [
            'key1' => 'value1',
            'key2' => 'value2'
        ];

        $this->assertTrue($this->cache->setMultiple($values));

        foreach ($values as $key => $value) {
            $this->assertEquals($value, $this->cache->get($key));
        }
    }

    public function testDeleteMultiple(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->set('key3', 'value3');

        $this->assertTrue($this->cache->deleteMultiple(['key1', 'key3']));

        $this->assertFalse($this->cache->has('key1'));
        $this->assertTrue($this->cache->has('key2'));
        $this->assertFalse($this->cache->has('key3'));
    }

    public function testExpiredCache(): void
    {
        $key = 'test_key';
        $value = 'test_value';

        // Set with 1 second TTL
        $this->cache->set($key, $value, 1);
        $this->assertEquals($value, $this->cache->get($key));

        // Wait for expiration
        sleep(2);

        $this->assertNull($this->cache->get($key));
        $this->assertFalse($this->cache->has($key));
    }

    public function testMemoryCacheIsNotPersistent(): void
    {
        $this->cache->set('key', 'value');
        $this->assertTrue($this->cache->has('key'));

        // Create new instance - should be empty
        $newCache = new MemoryCache();
        $this->assertFalse($newCache->has('key'));
    }

    public function testComplexDataTypes(): void
    {
        $object = new \stdClass();
        $object->property = 'value';

        $array = ['nested' => ['data' => 'structure']];

        $this->cache->set('object', $object);
        $this->cache->set('array', $array);

        $retrievedObject = $this->cache->get('object');
        $retrievedArray = $this->cache->get('array');

        $this->assertEquals($object->property, $retrievedObject->property);
        $this->assertEquals($array['nested']['data'], $retrievedArray['nested']['data']);
    }
}