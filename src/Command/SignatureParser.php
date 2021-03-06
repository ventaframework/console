<?php declare(strict_types = 1);

namespace Venta\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Venta\Contracts\Console\SignatureParser as SignatureParserContract;

/**
 * Class SignatureParser
 *
 * @package Venta\Console
 */
final class SignatureParser implements SignatureParserContract
{
    /**
     * RegExp to match arguments.
     * name[]?:description
     *
     * @var string
     */
    private $argumentsMatcher = '/^(?:\-\-)?([a-z]+)?(\[\])?(=)?(.*?)?$/';

    /**
     * Parameters matcher string.
     *
     * @var string
     */
    private $parametersMatcher = '/{(.*?)}/';

    /**
     * Full signature string holder.
     *
     * @var string
     */
    private $signature;

    /**
     * @throws \Exception
     * {@inheritdoc}
     */
    public function parse(string $signature): array
    {
        $signature = explode(' ', $this->signature = $signature);

        return array_merge($this->parseParameters(), [
            'name' => array_shift($signature),
        ]);
    }

    /**
     * Defines type of an argument or option based on options
     *
     * @param bool $array
     * @param bool $optional
     * @return int
     */
    private function defineType($array = false, $optional = false): int
    {
        $type = ($optional) ? InputArgument::OPTIONAL : InputArgument::REQUIRED;

        if ($array) {
            $type = InputArgument::IS_ARRAY | $type;
        }

        return $type;
    }

    /**
     * Returns array of parameters matches
     *
     * @return array
     */
    private function getParameters(): array
    {
        $matches = [];
        preg_match_all($this->parametersMatcher, $this->signature, $matches);

        return $matches[1];
    }

    /**
     * Parses arguments and options from signature string,
     * returns an array with definitions
     *
     * @return array
     */
    private function parseParameters(): array
    {
        $arguments = [];
        $options = [];
        $signatureArguments = $this->getParameters();

        foreach ($signatureArguments as $value) {
            $item = [];
            $matches = [];
            $exploded = explode(':', $value);

            if (count($exploded) > 0 && preg_match($this->argumentsMatcher, $exploded[0], $matches)) {
                $item['name'] = $matches[1];
                $item['type'] = $this->defineType($matches[2] === '[]', $matches[3] === '=');
                $item['default'] = $matches[4] !== '' ? $matches[4] : null;
                $item['description'] = count($exploded) === 2 ? $exploded[1] : null;

                if ($matches[2] === '[]' && $item['default'] !== null) {
                    $item['default'] = explode(',', $item['default']);
                }

                if (substr($exploded[0], 0, 2) === '--') {
                    $options[] = $item;
                } else {
                    $arguments[] = $item;
                }
            }
        }

        return [
            'arguments' => $arguments,
            'options' => $options,
        ];
    }
}