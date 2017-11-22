<?php
use jpuck\avhost\Configuration;

class ConfigurationTest extends PHPUnit_Framework_TestCase
{
    protected static $tmp = '/tmp';

    public static function setUpBeforeClass()
    {
        $tmp = static::$tmp;
        if (!is_dir($tmp)) {
            throw new Exception("$tmp is not a directory.");
        }
    }

    public function virtualHostConfigurationDataProvider()
    {
        $tmp = static::$tmp;
        return [
            'plain' => ['example.com', $tmp],
            'ssl' => ['ssl.example.com', $tmp, [
                    'meta' => [
                        'realpaths' => false,
                    ],
                    'ssl' => [
                        'key' => '/etc/ssl/private/ssl.example.com.key',
                        'certificate' => '/etc/ssl/certs/ssl.example.com.pem',
                        'required' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider virtualHostConfigurationDataProvider
     */
    public function test_can_generate_virtual_host_configuration_file($name, $root, $options = [])
    {
        $expected = file_get_contents(__DIR__."/confs/$name.conf");

        $actual = (string)(new Configuration($name, $root, $options));

        $this->assertEquals($expected, $actual);
    }

    public function arrayConfigurationDataProvider()
    {
        return [
            [[
                'hostname' => 'example.com',
                'documentRoot' => static::$tmp,
            ]],
        ];
    }

    /**
     * @dataProvider arrayConfigurationDataProvider
     */
    public function test_can_cast_to_json(array $expected)
    {
        $configuration = new Configuration($expected['hostname'], $expected['documentRoot']);
        $actual = json_decode(json_encode($configuration), true);

        $this->assertArraySubset($expected, $actual);
    }
}
