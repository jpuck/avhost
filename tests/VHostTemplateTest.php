<?php
use jpuck\avhost\VHostTemplate;

class VHostTemplateTest extends PHPUnit_Framework_TestCase
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
                    'key' => '/etc/ssl/private/ssl.example.com.key',
                    'crt' => '/etc/ssl/certs/ssl.example.com.pem',
                ],
            ],
        ];
    }

    /**
     * @dataProvider virtualHostConfigurationDataProvider
     */
    public function testCanGenerateVirtualHostConfiguration($name, $root, $options = [])
    {
        $expected = file_get_contents(__DIR__."/confs/$name.conf");

        $actual = (string)(new VHostTemplate($name, $root, $options));

        $this->assertEquals($expected, $actual);
    }
}
