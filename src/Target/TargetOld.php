<?php

namespace Drutiny\Target;

use Doctrine\Common\Annotations\AnnotationReader;
use Drutiny\Container;
use Drutiny\Driver\Exec;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Basic function of a Target.
 */
abstract class TargetOld implements TargetInterface
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

    private $uri = false;


  /**
   *
   */
    public function parse($data):TargetInterface
    {
        return $this;
    }

    public function validate()
    {
        return true;
    }

  /**
   *
   */
    final public function uri()
    {
        return $this->uri;
    }

    final public function setUri($uri)
    {
        $this->uri = $uri;
        if (!$this->validate()) {
            throw new InvalidTargetException(strtr("@uri is an invalid target", [
            '@uri' => $uri
            ]));
        }
        return $this;
    }

  /**
   * @inheritdoc
   * Implements ExecInterface::exec().
   */
    public function exec($command, $args = [])
    {
        $process = new Exec();
        return $process->exec($command, $args);
    }

  /**
   * Parse a target argument into the target driver and data.
   */
    public static function parseTarget($target)
    {
        $target_name = 'drush';
        $target_data = $target;
        if (strpos($target, ':') !== false) {
            list($target_name, $target_data) = explode(':', $target, 2);
        }
        return [$target_name, $target_data];
    }

  /**
   * Alias for Registry::getTarget().
   */
    public static function getTarget($name, $options = [])
    {
        return Registry::getTarget($name, $options);
    }

  /**
   * {@inheritdoc}
   */
    public function metadataUri()
    {
        return $this->uri();
    }

  /**
   * {@inheritdoc}
   */
    public function metadataDomain()
    {
        return parse_url($this->uri(), PHP_URL_HOST);
    }

  /**
   * Pull metadata from Drutiny\Target\Metadata interfaces.
   *
   * @return array of metatdata keyed by metadata name.
   */
    final public function getMetadata()
    {
        $item = Container::cache('target')->getItem('metadata');

        if (!$item->isHit()) {
            $metadata = [];
            $reflection = new \ReflectionClass($this);
            $interfaces = $reflection->getInterfaces();
            $reader = new AnnotationReader();

            foreach ($interfaces as $interface) {
                $methods = $interface->getMethods(\ReflectionMethod::IS_PUBLIC);
                foreach ($methods as $method) {
                    $annotation = $reader->getMethodAnnotation($method, 'Drutiny\Annotation\Metadata');
                    if (empty($annotation)) {
                        continue;
                    }
                    $metadata[$annotation->name] = $method->name;
                }
            }
            Container::cache('target')->save($item->set($metadata));
        }
        return $item->get();
    }
}